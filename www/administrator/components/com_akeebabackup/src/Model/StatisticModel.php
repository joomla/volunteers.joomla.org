<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Model;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\TriggerEventTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Model\Exceptions\FrozenRecordError;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Utilities\ArrayHelper;
use RuntimeException;

#[\AllowDynamicProperties]
class StatisticModel extends AdminModel
{
	use TriggerEventTrait;

	/**
	 * @inheritDoc
	 */
	public function getForm($data = [], $loadData = true)
	{
		$form = $this->loadForm(
			'com_akeebabackup.statistic',
			'statistic',
			[
				'control'   => 'jform',
				'load_data' => $loadData,
			]
		);

		if (empty($form))
		{
			return false;
		}

		// Modify the form based on access controls.
		if (!$this->canEditState((object) $data))
		{
			// Disable fields for display.
			$form->setFieldAttribute('frozen', 'disabled', 'true');

			// Disable fields while saving.
			// The controller has already verified this is a record you can edit.
			$form->setFieldAttribute('frozen', 'filter', 'unset');
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since   1.6
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$app  = JoomlaFactory::getApplication();
		$data = $app->getUserState('com_akeebabackup.edit.statistic.data', []);

		if (empty($data))
		{
			$data = (object) $this->getItem();
		}

		$this->preprocessData('com_akeebabackup.statistic', $data);

		return $data;
	}


	public function delete(&$pks)
	{
		$pks   = ArrayHelper::toInteger((array) $pks);
		$table = $this->getTable();

		// Include the plugins for the delete events.
		PluginHelper::importPlugin($this->events_map['delete']);

		// Iterate the items to delete each one.
		foreach ($pks as $i => $id)
		{
			if ((!is_numeric($id)) || ($id <= 0))
			{
				throw new RuntimeException(Text::_('COM_AKEEBABACKUP_BUADMIN_ERROR_INVALIDID'));
			}

			// Get the backup statistics record and the files to delete
			$stat = (array) Platform::getInstance()->get_statistics($id);

			$table->bind($stat);

			if ($stat['frozen'])
			{
				throw new FrozenRecordError(Text::_('COM_AKEEBABACKUP_BUADMIN_FROZENRECORD_ERROR'));
			}

			try
			{
				$result = $this->canDelete($table);
				$error  = null;
			}
			catch (\Exception $e)
			{
				$result = false;
				$error  = $e->getMessage();
			}

			if ($result === false && $error === null)
			{
				/** @deprecated 10.1.0 Only for Joomla 4 b/c. Remove in 11. */
				/** @noinspection PhpDeprecationInspection */
				$error = $table->getError();
			}

			if (!$result)
			{
				// Prune items that you can't change.
				unset($pks[$i]);

				if ($error)
				{
					Log::add($error, Log::WARNING, 'jerror');
				}
				else
				{
					Log::add(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), Log::WARNING, 'jerror');
				}

				return false;
			}

			$context = $this->option . '.' . $this->name;

			// Trigger the before delete event.
			$result = $this->triggerPluginEvent($this->event_before_delete, [$context, $table]);

			if (\in_array(false, $result, true))
			{
				/** @deprecated 10.1.0 Only for Joomla 4 b/c. Remove in 11. */
				/** @noinspection PhpDeprecationInspection */
				$error = $table->getError();

				throw new RuntimeException($error);
			}

			$ref = [$id];

			if (!$this->deleteFiles($ref))
			{
				throw new RuntimeException(Text::_('COM_AKEEBABACKUP_BUADMIN_ERR_CANNOT_DELETE_ARCHIVES'));
			}

			try
			{
				$ref = @intval($id);
			}
			catch (\Throwable $e)
			{
				throw new RuntimeException(Text::_('COM_AKEEBABACKUP_BUADMIN_ERROR_INVALIDID'));
			}

			if (!$table->delete($id))
			{
				throw new RuntimeException(Text::_('COM_AKEEBABACKUP_BUADMIN_ERR_CANNOT_DELETE_RECORD'));
			}

			// Trigger the after event.
			$this->triggerPluginEvent($this->event_after_delete, [$context, $table]);
		}

		// Clear the component's cache
		$this->cleanCache();

		return true;
	}

	/**
	 * Delete the backup files of one or more backup statistics records
	 *
	 * @param   int|array  $pks  The IDs of the backup statistics records to delete
	 *
	 * @return  bool  True on success
	 */
	public function deleteFiles(&$pks)
	{
		$pks    = ArrayHelper::toInteger((array) $pks);
		$result = true;

		foreach ($pks as $i => $id)
		{
			if ((!is_numeric($id)) || ($id <= 0))
			{
				throw new RuntimeException(Text::_('COM_AKEEBABACKUP_BUADMIN_ERROR_INVALIDID'));
			}

			// Get the backup statistics record and the files to delete
			$stat = (array) Platform::getInstance()->get_statistics($id);

			if ($stat['frozen'])
			{
				throw new FrozenRecordError(Text::_('COM_AKEEBABACKUP_BUADMIN_FROZENRECORD_ERROR'));
			}

			// Remove the custom log file if necessary
			$this->deleteLogs($stat);

			// Get all of the files
			$allFiles = Factory::getStatistics()->get_all_filenames($stat, false);

			// No files? Nothing to do.
			if (empty($allFiles))
			{
				continue;
			}

			foreach ($allFiles as $filename)
			{
				if (!@file_exists($filename))
				{
					continue;
				}

				if (@unlink($filename))
				{
					continue;
				}

				$result = true;

//				if (!File::delete($filename))
//				{
//					$result = false;
//				}

				$result = false;
			}

			$this->triggerEvent('onAfterDeleteFiles', [$id]);
		}

		return $result;
	}

	/**
	 * Deletes the backup-specific log files of a backup stats records
	 *
	 * @param   array  $stat  Stats record
	 *
	 * @return  void
	 */
	protected function deleteLogs(array $stat)
	{
		// We can't delete logs if there is no backup ID in the record
		if (!isset($stat['backupid']) || empty($stat['backupid']))
		{
			return;
		}

		$baseName     = 'akeeba.' . $stat['tag'] . '.' . $stat['backupid'];
		$logFileNames = [
			$baseName . '.log',
			$baseName . '.log.php',
			$baseName . '.php',
		];

		foreach ($logFileNames as $logFileName)
		{
			$logPath = dirname($stat['absolute_path']) . '/' . $logFileName;

			if (!@file_exists($logPath))
			{
				continue;
			}

			if (@unlink($logPath))
			{
				continue;
			}

			// File::delete($logPath);
		}
	}

	protected function canDelete($record)
	{
		// Allow the check to be overridden by the API task
		$override = $this->getState('workaround.override_canDelete');

		if ($override !== null && is_bool($override))
		{
			return $override;
		}

		return parent::canDelete($record);
	}
}