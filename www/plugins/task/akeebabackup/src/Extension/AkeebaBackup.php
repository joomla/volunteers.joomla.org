<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\Task\AkeebaBackup\Extension;

defined('_JEXEC') or die;

use Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt\InitialiseEngine;
use Akeeba\Component\AkeebaBackup\Administrator\Model\BackupModel;
use Akeeba\Component\AkeebaBackup\Administrator\Model\StatisticsModel;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Exception;
use InvalidArgumentException;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Task\Task;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Event\EventInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Filesystem\Path;
use Joomla\Registry\Registry;
use RuntimeException;
use function array_key_exists;
use function strlen;

/**
 * The Akeeba Backup task plugin.
 *
 * @since 9.2.0
 */
class AkeebaBackup extends CMSPlugin implements SubscriberInterface, DatabaseAwareInterface
{
	use DatabaseAwareTrait;
	use TaskPluginTrait;
	use InitialiseEngine;

	private const TASKS_MAP = [
		'akeebabackup.backup'    => [
			'langConstPrefix' => 'PLG_TASK_AKEEBABACKUP_TASK_BACKUP',
			'method'          => 'takeBackup',
			'form'            => 'backupTaskForm',
		],
		'akeebabackup.clibackup' => [
			'langConstPrefix' => 'PLG_TASK_AKEEBABACKUP_TASK_CLIBACKUP',
			'method'          => 'takeCLIBackup',
			'form'            => 'cliBackupTaskForm',
		],
		'akeebabackup.failedcheck' => [
			'langConstPrefix' => 'PLG_TASK_AKEEBABACKUP_TASK_FAILEDCHECK',
			'method'          => 'checkFailedBackups',
		],
		'akeebabackup.faileduploadcheck' => [
			'langConstPrefix' => 'PLG_TASK_AKEEBABACKUP_TASK_FAILEDUPLOADCHECK',
			'method'          => 'checkFailedUploads',
		],
	];

	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  9.2.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * A registry object to keep track of parameters between subsequent calls of the resumable task.
	 *
	 * @var   Registry
	 * @since 9.2.0
	 */
	private $taskInfoRegistry;

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * This is mostly boilerplate code as per every built-in Task plugin in Joomla.
	 *
	 * @return  array
	 * @since   9.2.0
	 */
	public static function getSubscribedEvents(): array
	{
		// This task is disabled if the Akeeba Backup component is not installed or has been unpublished
		if (!ComponentHelper::isEnabled('com_akeebabackup'))
		{
			return [];
		}

		return [
			'onTaskOptionsList'    => 'advertiseRoutines',
			'onExecuteTask'        => 'standardRoutineHandler',
			'onContentPrepareForm' => 'enhanceTaskItemForm',
		];
	}

	/**
	 * Enhance the task form with routine-specific fields from an XML file declared through the TASKS_MAP constant.
	 * If a plugin only supports the task form and does not need additional logic, this method can be mapped to the
	 * `onContentPrepareForm` event through {@see SubscriberInterface::getSubscribedEvents()} and will take care
	 * of injecting the fields without additional logic in the plugin class.
	 *
	 * @param   EventInterface|Form  $context  The onContentPrepareForm event or the Form object.
	 * @param   mixed                $data     The form data, required when $context is a {@see Form} instance.
	 *
	 * @return  boolean  True if the form was successfully enhanced or the context was not relevant.
	 *
	 * @throws  Exception
	 * @since   9.2.0
	 */
	public function enhanceTaskItemForm($context, $data = null): bool
	{
		if ($context instanceof EventInterface)
		{
			/** @var Form $form */
			$form = $context->getArgument('0');
			$data = $context->getArgument('1');
		}
		elseif ($context instanceof Form)
		{
			$form = $context;
		}
		else
		{
			throw new InvalidArgumentException(
				sprintf(
					'Argument 0 of %1$s must be an instance of %2$s or %3$s',
					__METHOD__,
					EventInterface::class,
					Form::class
				)
			);
		}

		if ($form->getName() !== 'com_scheduler.task')
		{
			return true;
		}

		$routineId           = $this->getRoutineId($form, $data);
		$isSupported         = array_key_exists($routineId, self::TASKS_MAP);
		$enhancementFormName = self::TASKS_MAP[$routineId]['form'] ?? '';

		// Return if routine is not supported by the plugin or the routine does not have a form linked in TASKS_MAP.
		if (!$isSupported || strlen($enhancementFormName) === 0)
		{
			return true;
		}

		// We expect the form XML in "{PLUGIN_PATH}/forms/{FORM_NAME}.xml"
		$path                = JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name;
		$enhancementFormFile = $path . '/forms/' . $enhancementFormName . '.xml';

		try
		{
			$enhancementFormFile = Path::check($enhancementFormFile);
		}
		catch (Exception $e)
		{
			return false;
		}

		if (is_file($enhancementFormFile))
		{
			return $form->loadFile($enhancementFormFile);
		}

		return false;
	}

	/**
	 * Get an #__akeebabackup_storage key for the task being executed by the event.
	 *
	 * This is used to store the temporary information which survives consecutive calls to the resumable task.
	 *
	 * @param   ExecuteTaskEvent  $event
	 *
	 * @return  string
	 * @since   9.2.0
	 */
	private function getTaskKey(ExecuteTaskEvent $event): string
	{
		return 'task.' . $event->getRoutineId() . '.' . $event->getTaskId();
	}

	/**
	 * Load the temporary information for the resumable task being executed by the event.
	 *
	 * @param   ExecuteTaskEvent  $event
	 *
	 * @return  void
	 * @since   9.2.0
	 */
	private function loadTaskRegistry(ExecuteTaskEvent $event): void
	{
		$key = $this->getTaskKey($event);

		try
		{
			$db    = $this->getDatabase();
			$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
				->select($db->quoteName('data'))
				->from($db->quoteName('#__akeebabackup_storage'))
				->where($db->quoteName('tag') . ' = :key')
				->bind(':key', $key, ParameterType::STRING);
			$json  = $db->setQuery($query)->loadResult() ?: null;
		}
		catch (Exception $e)
		{
			$json = null;
		}

		$this->taskInfoRegistry = new Registry($json);

		$this->removeTaskRegistry($event);
	}

	/**
	 * Remove the temporary information for the resumable task being executed by the event.
	 *
	 * @param   ExecuteTaskEvent  $event
	 *
	 * @return  void
	 * @since   9.2.0
	 */
	private function removeTaskRegistry(ExecuteTaskEvent $event): void
	{
		$key = $this->getTaskKey($event);

		try
		{
			$db    = $this->getDatabase();
			$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
				->delete($db->quoteName('#__akeebabackup_storage'))
				->where($db->quoteName('tag') . ' = :key')
				->bind(':key', $key, ParameterType::STRING);
			$db->setQuery($query)->execute();
		}
		catch (Exception $e)
		{
			// No worries.
		}
	}

	/**
	 * Save the temporary information for the resumable task being executed by the event.
	 *
	 * @param   ExecuteTaskEvent  $event
	 *
	 * @return  void
	 * @since   9.2.0
	 */
	private function saveTaskRegistry(ExecuteTaskEvent $event): void
	{
		$this->removeTaskRegistry($event);

		$key = $this->getTaskKey($event);

		try
		{
			$o = (object) [
				'tag'  => $key,
				'data' => $this->taskInfoRegistry->toString(),
			];
			$this->getDatabase()->insertObject('#__akeebabackup_storage', $o, 'tag');
		}
		catch (Exception $e)
		{
			// No worries.
		}
	}

	/**
	 * Run a backup using a resumable task
	 *
	 * @param   ExecuteTaskEvent  $event
	 *
	 * @return  int
	 * @throws  Exception
	 * @since   9.2.0
	 */
	private function takeBackup(ExecuteTaskEvent $event): int
	{
		// Get some basic information about the task at hand.
		/** @var Task $task */
		$task    = $event->getArgument('subject');
		$profile = (int) $event->getArgument('params')->profileid ?? 1;

		// Make sure Akeeba Backup is installed and enabled.
		$component = ComponentHelper::isEnabled('com_akeebabackup')
			? $this->getApplication()->bootComponent('com_akeebabackup')
			: null;

		if (!($component instanceof MVCFactoryServiceInterface))
		{
			throw new RuntimeException('The Akeeba Backup component is not installed or has been disabled.');
		}

		if (!defined('AKEEBA_BACKUP_ORIGIN'))
		{
			define('AKEEBA_BACKUP_ORIGIN', 'joomla');
		}

		// Make sure $profile is a positive integer >= 1
		$profile = max(1, $profile);

		// Set the active profile
		define('AKEEBA_PROFILE', $profile);

		$this->initialiseComponent($this->getApplication());

		/**
		 * DO NOT REMOVE!
		 *
		 * The Model will only try to load the configuration after nuking the factory. This causes Profile 1 to be
		 * loaded first. Then it figures out it needs to load a different profile and it does – but the protected keys
		 * are NOT replaced, meaning that certain configuration parameters are not replaced. Most notably, the chain.
		 * This causes backups to behave weirdly. So, DON'T REMOVE THIS UNLESS WE REFACTOR THE MODEL.
		 */
		Platform::getInstance()->load_configuration($profile);

		// Set up the backup model
		$factory = $component->getMVCFactory();
		/** @var BackupModel $model */
		$model = $factory->createModel('Backup', 'Administrator', ['ignore_request' => true]);
		$model->setState('profile', $profile);

		// Am I resuming a backup or starting a new one?
		if ($task->get('last_exit_code', Status::OK) == Status::WILL_RESUME)
		{
			$this->logTask(sprintf('Resuming task %d', $task->get('id')));
			$this->loadTaskRegistry($event);

			$backupTag = $this->taskInfoRegistry->get('tag', null);
			$backupId  = $this->taskInfoRegistry->get('backup_id', null);

			if (empty($backupTag) || empty($backupId))
			{
				throw new RuntimeException(sprintf('Cannot resume backup task #%d; the temporary backup information is missing.', $event->getTaskId()));
			}

			$model->setState('tag', $backupTag);
			$model->setState('backupid', $backupId);

			$backupResult = $model->stepBackup(true);
		}
		else
		{
			$this->taskInfoRegistry = new Registry();

			$this->logTask(sprintf('Starting new task %d', $task->get('id')));

			$model->setState('tag', 'joomla');
			$model->setState('comment', 'Backup taken automatically using the Joomla Scheduled Tasks feature.');

			$timeStart    = microtime(true);
			$backupResult = $model->startBackup();
			$timeEnd      = microtime(true);

			$willResume = ($backupResult['HasRun'] ?: 0) != 1;
			$error      = $backupResult['Error'] ?? '';

			if (($timeEnd - $timeStart < 0.5) && empty($error) && $willResume)
			{
				$backupResult = $model->stepBackup(true);
			}
		}

		// Did the backup end in an error?
		if (isset($backupResult['Error']) && !empty($backupResult['Error']))
		{
			$this->removeTaskRegistry($event);

			throw new RuntimeException($backupResult['Error']);
		}

		// Should I resume the backup?
		$willResume = ($backupResult['HasRun'] ?: 0) != 1;

		if ($willResume)
		{
			$this->logTask(sprintf('Backup at %0.2f%%; will resume next time this scheduled task is told to run.', $backupResult['Progress'] ?: 0.0));

			$this->taskInfoRegistry->set('tag', 'joomla');
			$this->taskInfoRegistry->set('backup_id', $model->getState('backupid', null));

			$this->saveTaskRegistry($event);
		}
		else
		{
			$this->logTask('Backup completed successfully.');

			$this->removeTaskRegistry($event);
		}

		// Should I log any warnings?
		if (isset($backupResult['Warnings']) && !empty($backupResult['Warnings']))
		{
			$this->logTask('Backup step generated warnings');

			foreach ($backupResult['Warnings'] as $warning)
			{
				$this->logTask($warning, 'warning');
			}
		}

		return $willResume ? Status::WILL_RESUME : Status::OK;
	}

	/**
	 * Run a backup with an all–at–once, CLI–only task.
	 *
	 * This is similar to using Joomla's CLI application to run a backup, albeit with far less flexibility.
	 *
	 * @param   ExecuteTaskEvent  $event
	 *
	 * @return  int
	 * @throws  Exception
	 * @since   9.2.0
	 */
	private function takeCLIBackup(ExecuteTaskEvent $event): int
	{
        // Get some basic information about the task at hand.
		/** @var Task $task */
		$task    = $event->getArgument('subject');
		$profile = (int) $event->getArgument('params')->profileid ?? 1;

		if (!defined('AKEEBA_BACKUP_ORIGIN'))
		{
			define('AKEEBA_BACKUP_ORIGIN', 'joomla');
		}

		// Make sure Akeeba Backup is installed and enabled.
		$component = ComponentHelper::isEnabled('com_akeebabackup')
			? $this->getApplication()->bootComponent('com_akeebabackup')
			: null;

		if (!($component instanceof MVCFactoryServiceInterface))
		{
			throw new RuntimeException('The Akeeba Backup component is not installed or has been disabled.');
		}

		// Make sure $profile is a positive integer >= 1
		$profile = max(1, $profile);

		// Set the active profile
		define('AKEEBA_PROFILE', $profile);

		$this->initialiseComponent($this->getApplication());

		/**
		 * DO NOT REMOVE!
		 *
		 * The Model will only try to load the configuration after nuking the factory. This causes Profile 1 to be
		 * loaded first. Then it figures out it needs to load a different profile and it does – but the protected keys
		 * are NOT replaced, meaning that certain configuration parameters are not replaced. Most notably, the chain.
		 * This causes backups to behave weirdly. So, DON'T REMOVE THIS UNLESS WE REFACTOR THE MODEL.
		 */
		Platform::getInstance()->load_configuration($profile);

		// Set up the backup model
		$factory = $component->getMVCFactory();
		/** @var BackupModel $model */
		$model = $factory->createModel('Backup', 'Administrator', ['ignore_request' => true]);
		$model->setState('profile', $profile);

		$this->logTask(sprintf('Starting new task %d', $task->get('id')));

		$model->setState('tag', 'joomla');
		$model->setState('description', $model->getDefaultDescription() . ' (Joomla Scheduled Tasks, CLI-only task)');
		$model->setState('comment', 'Backup taken automatically using the Joomla Scheduled Tasks feature.');

		// Dummy backupResult so that the loop iterates once
		$backupResult = [
			'HasRun'       => 0,
			'Error'        => '',
			'cli_firstrun' => 1,
		];

		$hasWarnings = false;

		while (($backupResult['HasRun'] != 1) && (empty($backupResult['Error'])))
		{
			if (isset($backupResult['cli_firstrun']) && $backupResult['cli_firstrun'])
			{
				$this->logTask(sprintf('Starting a new backup with profile %u.', $profile));

				$backupResult = $model->startBackup([
					'akeeba.tuning.min_exec_time'           => 0,
					'akeeba.tuning.max_exec_time'           => 15,
					'akeeba.tuning.run_time_bias'           => 100,
					'akeeba.advanced.autoresume'            => 0,
					'akeeba.tuning.nobreak.beforelargefile' => 1,
					'akeeba.tuning.nobreak.afterlargefile'  => 1,
					'akeeba.tuning.nobreak.proactive'       => 1,
					'akeeba.tuning.nobreak.finalization'    => 1,
					'akeeba.tuning.settimelimit'            => 0,
					'akeeba.tuning.setmemlimit'             => 1,
					'akeeba.tuning.nobreak.domains'         => 0,
				]);
			}
			else
			{
				$this->logTask('Continuing the backup');

				$backupResult = $model->stepBackup();
			}

			// Print the new progress bar and info
			$this->logTask(sprintf('Last tick: %s', date('Y-m-d H:i:s \G\M\TO (T)')), 'debug');
			$this->logTask(sprintf('Domain: %s', $backupResult['Domain'] ?? ''), 'debug');
			$this->logTask(sprintf('Step: %s', $backupResult['Step'] ?? ''), 'debug');
			$this->logTask(sprintf('Substep: %s', $backupResult['Substep'] ?? ''), 'debug');
			$this->logTask(sprintf('Progress: %s', $backupResult['Progress'] ?? 0.0), 'debug');

			// Output any warnings
			if (!empty($backupResult['Warnings']))
			{
				$hasWarnings = true;

				foreach ($backupResult['Warnings'] as $warning)
				{
					$this->logTask($warning, 'warning');
				}
			}

			// Recycle the database connection to minimise problems with database timeouts
			$db = Factory::getDatabase();
			$db->close();
			$db->open();

			// Reset the backup timer
			Factory::getTimer()->resetTime();
		}

		// Did the backup end in an error?
		if (isset($backupResult['Error']) && !empty($backupResult['Error']))
		{
			throw new RuntimeException($backupResult['Error']);
		}

		if ($hasWarnings)
		{
			$this->logTask('Backup finished successfully, with warnings.');
		}
		else
		{
			$this->logTask('Backup finished successfully');
		}

		return Status::OK;
	}

	/**
	 * Check for failed backups and notify the site administrators.
	 *
	 * @param   ExecuteTaskEvent  $event
	 *
	 * @return  int
	 * @throws  Exception
	 * @since   10.2.0
	 */
	private function checkFailedBackups(ExecuteTaskEvent $event): int
	{
		// Make sure Akeeba Backup is installed and enabled.
		$component = ComponentHelper::isEnabled('com_akeebabackup')
			? $this->getApplication()->bootComponent('com_akeebabackup')
			: null;

		if (!($component instanceof MVCFactoryServiceInterface))
		{
			throw new RuntimeException('The Akeeba Backup component is not installed or has been disabled.');
		}

		$this->initialiseComponent($this->getApplication());

		/** @var StatisticsModel $model */
		$model = $component->getMVCFactory()->createModel('Statistics', 'Administrator');
		$model->setStateSetFlag(true);

		$result = $model->notifyFailed();

		$this->logTask($result['message'], $result['result'] ? 'info' : 'warning');

		return Status::OK;
	}

	/**
	 * Check for backups which failed to upload to remote storage and notify the site administrators.
	 *
	 * @param   ExecuteTaskEvent  $event
	 *
	 * @return  int
	 * @throws  Exception
	 * @since   10.2.0
	 */
	private function checkFailedUploads(ExecuteTaskEvent $event): int
	{
		// Make sure Akeeba Backup is installed and enabled.
		$component = ComponentHelper::isEnabled('com_akeebabackup')
			? $this->getApplication()->bootComponent('com_akeebabackup')
			: null;

		if (!($component instanceof MVCFactoryServiceInterface))
		{
			throw new RuntimeException('The Akeeba Backup component is not installed or has been disabled.');
		}

		$this->initialiseComponent($this->getApplication());

		/** @var StatisticsModel $model */
		$model = $component->getMVCFactory()->createModel('Statistics', 'Administrator');
		$model->setStateSetFlag(true);

		$result = $model->notifyFailedUploads();

		$this->logTask($result['message'], $result['result'] ? 'info' : 'warning');

		return Status::OK;
	}

}