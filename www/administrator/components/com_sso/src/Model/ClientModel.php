<?php
/**
 * @package    SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

namespace Rolandd\Component\Sso\Administrator\Model;

defined('_JEXEC') or die;

use Exception;
use InvalidArgumentException;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Utilities\ArrayHelper;
use Rolandd\Component\Sso\Administrator\Sso\Attribute;
use Rolandd\Component\Sso\Administrator\Sso\CronSync;
use SimpleSAML\Module\metarefresh\MetaLoader;
use stdClass;

/**
 * Client model.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class ClientModel extends AdminModel
{
	/**
	 * Form context
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	private $context = 'com_sso.client';

	/**
	 * Get the form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A Form object on success | False on failure.
	 *
	 * @since   1.0.0
	 *
	 * @throws  Exception
	 */
	public function getForm($data = array(), $loadData = true)
	{
		$form = $this->loadForm($this->context, 'client', ['control' => 'jform', 'load_data' => $loadData]);

		if (!is_object($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Refresh the metadata from selected clients.
     *
     * Back up whatever is already on disk so a fetch/parse failure below can be detected
     * and the previously working file restored, instead of silently leaving it empty.
     * loadSource() swallows fetch/parse failures internally (logs and returns) rather than
     * throwing, and writeMetadataFiles() deletes the output file entirely when nothing
     * could be loaded for it.
	 *
	 * @param   array  $clientIds  The list of clients to refresh
	 *
	 * @return  void
	 *
	 * @throws Exception
	 *@since   1.0.0
	 *
	 */
	public function metaRefresh(array $clientIds): void
	{
		$clientIds = ArrayHelper::toInteger($clientIds);
		$db        = $this->getDatabase();
		$query     = $db->createQuery()
			->select(
				$db->quoteName(
					[
						'name',
						'source',
						'validateFingerprint',
						'expireAfter',
						'outputDir',
						'outputFormat',
						'certificates',
						'attributeMap',
					],
					[
						'name',
						'src',
						'validateFingerprint',
						'expireAfter',
						'outputDir',
						'outputFormat',
						'certificates',
						'attributeMap',
					]
				)
			)
			->from($db->quoteName('#__sso_clients'))
			->where($db->quoteName('id') . ' IN (' . implode(',', $clientIds) . ')')
			->where($db->quoteName('published') . ' = 1');
		$db->setQuery($query);
		$clients = $db->loadAssocList();

		// Process the clients
		if (!$clients)
		{
			return;
		}

		$outputDir = PATH_SIMPLESAMLPHP . '/metadata-generated/';

		foreach ($clients as $source)
		{
			$expire = $source['expireAfter'] ? time() + (60 * 60 * 24 * $source['expireAfter']) : null;

			// The metadata global variable will be filled with the metadata we extract
			$metaLoader = new MetaLoader($expire);

			$source['certificates']        = $source['certificates'] ? json_decode($source['certificates'], false) : null;
			$source['validateFingerprint'] = $source['validateFingerprint'] ?: null;

			$attributeMap = $source['attributeMap'] ? json_decode($source['attributeMap'], true) : null;

			if ($attributeMap)
			{
				$attributeFile                      = ApplicationHelper::stringURLSafe($source['name']);
				$source['template']['authproc'][50] = [
					'class' => 'core:AttributeMap',
					$attributeFile,
				];
				$this->createAttributeMap($source['name'], $attributeMap);
			}

			$clientName = $source['name'];

			unset($source['name'], $source['attributeMap']);

			$metaLoader->loadSource($source);

			if ($source['outputFormat'] === 'serialize')
			{
				$metaLoader->writeMetadataSerialize($outputDir . $source['outputDir'] . '/');
			}
			else
			{
				$clientOutputDir = $outputDir . $source['outputDir'] . '/';
				$remoteSpFile    = $clientOutputDir . 'saml20-sp-remote.php';
				$existingContent = is_file($remoteSpFile) ? file_get_contents($remoteSpFile) : null;

				$metaLoader->writeMetadataFiles($clientOutputDir);

				if (!is_file($remoteSpFile))
				{
					if ($existingContent !== null)
					{
						file_put_contents($remoteSpFile, $existingContent);
					}

					Factory::getApplication()->enqueueMessage(
						Text::sprintf('COM_SSO_CLIENT_METADATA_REFRESH_FAILED', $clientName),
						'warning'
					);
				}
			}
		}
	}

	/**
	 * Write a new attribute map.
	 *
	 * @param   string  $name          The name of the client
	 * @param   array   $attributeMap  The attribute mappings
	 *
	 * @return  void
	 *
	 * @since   1.3.0
	 */
	private function createAttributeMap(string $name, array $attributeMap): void
	{
		$attributeDir  = PATH_SIMPLESAMLPHP . '/attributemap/';
		$attribute     = new Attribute;
		$attributeFile = ApplicationHelper::stringURLSafe($name);

		foreach ($attributeMap as $map)
		{
			$attribute->set($map['attributeField'], $map['attributeName']);
		}

		$attribute->setFilename($attributeDir . $attributeFile . '.php')
			->write();
	}

    /**
     * Method to save the form data.
     *
     * @param   array  $data  The form data.
     *
     * @return  boolean  True on success, False on error.
     *
     * @throws  Exception
     * @since   1.0.0
     */
	public function save($data): bool
	{
		$previousOutputDir = ((int) ($data['id'] ?? 0) !== 0) ? ($this->getOutputDirs([$data['id']])[0] ?? '') : '';

		try
		{
			$this->checkOutputDirNotUsedByProfile($data['outputDir']);
		}
		catch (InvalidArgumentException $exception)
		{
			// Don't also enqueueMessage() here - FormController::save() already turns
			// getError() into a "Save failed" message once this returns false.
			$this->setError($exception->getMessage());

			return false;
		}

		if ($data['attributeMap'])
		{
			$this->createAttributeMap($data['name'], $data['attributeMap']);
		}

		$data['attributeMap'] = json_encode($data['attributeMap']);

        $data = $this->saveSourceXml($data);

		if (!parent::save($data))
		{
			return false;
		}

		(new CronSync($this->getDatabase()))->sync();

		if ($previousOutputDir !== '' && $previousOutputDir !== $data['outputDir'])
		{
			$this->removeOutputDir($previousOutputDir);
		}

		$id = (int) $this->getState($this->getName() . '.id');

		try
		{
			$this->metaRefresh([$id]);
		}
		catch (Exception $exception)
		{
			Factory::getApplication()->enqueueMessage($exception->getMessage(), 'warning');
		}

		return true;
	}

	/**
	 * Guard against a Client outputDir colliding with an existing Profile's alias.
	 *
	 * Clients and Profiles both write their generated metadata into
	 * metadata-generated/<outputDir-or-alias>/, and SimpleSAMLphp's
	 * MetaLoader::writeMetadataFiles() treats that directory as exclusive to a single metadata
	 * source: any metadata type it didn't just (re)generate there is deleted as "stale". Sharing
	 * a name between a Client and a Profile means whichever one refreshes second silently wipes
	 * out the other's generated metadata file, breaking it until it is refreshed again.
	 *
	 * @param   string  $outputDir  The outputDir being saved.
	 *
	 * @return  void
	 *
	 * @throws  InvalidArgumentException
	 * @since   2.3.0
	 */
	private function checkOutputDirNotUsedByProfile(string $outputDir): void
	{
		if ($outputDir === '')
		{
			return;
		}

		$db    = $this->getDatabase();
		$query = $db->createQuery()
			->select($db->quoteName('name'))
			->from($db->quoteName('#__sso_profiles'))
			->where($db->quoteName('alias') . ' = ' . $db->quote($outputDir));
		$db->setQuery($query);

		$profileName = $db->loadResult();

		if ($profileName !== null)
		{
			throw new InvalidArgumentException(Text::sprintf('COM_SSO_OUTPUTDIR_IN_USE_BY_PROFILE', $outputDir, $profileName));
		}
	}

	/**
	 * Method to change the published state of one or more records.
	 *
	 * An unpublished client should stop being scheduled for cron refresh and stop being
	 * trusted via metadata.sources, same as a deleted one - but without touching its
	 * generated metadata files, so re-publishing it needs nothing more than another sync.
	 *
	 * @param   array    &$pks   A list of the primary keys to change.
	 * @param   integer   $value The value of the published state.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   2.4.0
	 */
	public function publish(&$pks, $value = 1)
	{
		if (!parent::publish($pks, $value))
		{
			return false;
		}

		(new CronSync($this->getDatabase()))->sync();

		return true;
	}

	/**
	 * Method to delete one or more records.
	 *
	 * Deleting a client removes it from the clients table, but module_metarefresh.php and
	 * config.php's metadata.sources are generated from that table, so they need to be rebuilt
	 * afterwards too - otherwise the deleted client's entry keeps being scheduled for refresh
	 * by the metarefresh cron job indefinitely, and SimpleSAMLphp keeps trusting metadata from
	 * a directory Joomla no longer knows about. The generated metadata directory itself is
	 * also removed, since nothing else will ever clean it up once the client is gone.
	 *
	 * @param   array  &$pks  An array of record primary keys.
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 *
	 * @since   2.4.0
	 */
	public function delete(&$pks)
	{
		$outputDirs = $this->getOutputDirs((array) $pks);

		if (!parent::delete($pks))
		{
			return false;
		}

		(new CronSync($this->getDatabase()))->sync();

		foreach ($outputDirs as $outputDir)
		{
			$this->removeOutputDir($outputDir);
		}

		return true;
	}

	/**
	 * Remove a metadata-generated/<outputDir>/ directory, if it exists.
	 *
	 * @param   string  $outputDir  The outputDir whose generated directory should be removed.
	 *
	 * @return  void
	 *
	 * @since   2.4.0
	 */
	private function removeOutputDir(string $outputDir): void
	{
		$path = PATH_SIMPLESAMLPHP . '/metadata-generated/' . $outputDir;

		if (is_dir($path))
		{
			Folder::delete($path);
		}
	}

	/**
	 * Get the outputDir of each of the given clients.
	 *
	 * Must be called before the rows are deleted, since it reads them from the clients table.
	 *
	 * @param   array  $pks  An array of record primary keys.
	 *
	 * @return  array  The outputDir of each client.
	 *
	 * @since   2.4.0
	 */
	private function getOutputDirs(array $pks): array
	{
		$pks = ArrayHelper::toInteger($pks);

		if (!$pks)
		{
			return [];
		}

		$db    = $this->getDatabase();
		$query = $db->createQuery()
			->select($db->quoteName('outputDir'))
			->from($db->quoteName('#__sso_clients'))
			->where($db->quoteName('id') . ' IN (' . implode(',', $pks) . ')');
		$db->setQuery($query);

		return $db->loadColumn();
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  stdClass  The data for the form.
	 *
	 * @since   1.0.0
	 *
	 * @throws  Exception
	 */
	protected function loadFormData()
	{
		$data = Factory::getApplication()->getUserState('com_sso.edit.client.data', new stdClass());

		if (is_array($data))
		{
            $item = new stdClass();

            foreach ($data as $key => $value)
            {
                $item->{$key} = $value;
            }

            $data = $item;
		}

		if (($data->id ?? false) === false)
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  stdClass
	 *
	 * @since   1.3.0
	 *
	 * @throws  Exception
	 */
	public function getItem($pk = null)
	{
		$item = parent::getItem($pk);

		$item->attributeMap = isset($item->attributeMap) ? json_decode($item->attributeMap) : [];

		return $item;
	}

    private function saveSourceXml(array $data): array
    {
        if (empty($data['sourceXml']))
        {
            return $data;
        }

        $file = PATH_SIMPLESAMLPHP . '/metadata-generated/' . $data['outputDir'] . '/test.xml';

        if (!File::write($file, $data['sourceXml']))
        {
            return $data;
        }

        $data['source'] = $file;

        return $data;
    }
}
