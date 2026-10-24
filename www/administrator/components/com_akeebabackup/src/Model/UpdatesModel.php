<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Model;

defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Extension;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;
use SimpleXMLElement;

#[\AllowDynamicProperties]
class UpdatesModel extends BaseDatabaseModel
{
	/** @var int The extension_id of this component */
	protected $extension_id = 0;

	/** @var string The currently installed version, as reported by the #__extensions table */
	protected $version = 'dev';

	/** @var string The URL to the component's update XML stream */
	protected $updateSite;

	/** @var string The name to the component's update site (description of the update XML stream) */
	protected $updateSiteName;

	protected $extensionKey = 'pkg_akeebabackup';

	public function __construct($config = [], ?MVCFactoryInterface $factory = null)
	{
		parent::__construct($config, $factory);

		$this->version        = AKEEBABACKUP_VERSION;
		$this->updateSite     = 'https://cdn.akeeba.com/updates/pkgakeebabackupcore.xml';
		$this->updateSiteName = 'Akeeba Backup Core for Joomla!';

		if (defined('AKEEBABACKUP_PRO') ? AKEEBABACKUP_PRO : 0)
		{
			$this->updateSite     = 'https://cdn.akeeba.com/updates/pkgakeebabackuppro.xml';
			$this->updateSiteName = 'Akeeba Backup Professional for Joomla!';
		}

		$this->extension_id = $this->findExtensionId($this->extensionKey, 'package');

		if (empty($this->extension_id))
		{
			$this->createFakePackageExtension();
			$this->extension_id = $this->findExtensionId($this->extensionKey, 'package');
		}
	}

	/**
	 * Refreshes the Joomla! update sites for this extension as needed
	 *
	 * @return  void
	 */
	public function refreshUpdateSite(): void
	{
		if (empty($this->extension_id))
		{
			return;
		}

		// Create the update site definition we want to store to the database
		$update_site = [
			'name'                 => $this->updateSiteName,
			'type'                 => 'extension',
			'location'             => $this->updateSite,
			'enabled'              => 1,
			'last_check_timestamp' => 0,
			// 'extra_query'          => 'dlid=' . $this->getLicenseKey(),
		];

		// Get a reference to the db driver
		$db = $this->getDatabase();

		// Get the update sites for our extension
		$updateSiteIds = $this->getUpdateSiteIds();

		if (empty($updateSiteIds))
		{
			$updateSiteIds = [];
		}

		/** @var boolean $needNewUpdateSite Do I need to create a new update site? */
		$needNewUpdateSite = true;

		/** @var int[] $deleteOldSites Old Site IDs to delete */
		$deleteOldSites = [];

		// Loop through all update sites
		foreach ($updateSiteIds as $id)
		{
			$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
				->select('*')
				->from($db->qn('#__update_sites'))
				->where($db->qn('update_site_id') . ' = :usid')
				->bind(':usid', $id, ParameterType::INTEGER);

			try
			{
				$aSite = $db->setQuery($query)->loadObject() ?: null;
			}
			catch (Exception $e)
			{
				$aSite = null;
			}

			if (empty($aSite))
			{
				// This update site no longer exists.
				continue;
			}

			// We have an update site that looks like ours
			if ($needNewUpdateSite && ($aSite->name == $update_site['name']) && ($aSite->location == $update_site['location']))
			{
				$needNewUpdateSite = false;
				$mustUpdate        = false;

				// Is it enabled? If not, enable it.
				if (!$aSite->enabled)
				{
					$mustUpdate     = true;
					$aSite->enabled = 1;
				}

				// Is the extra_query missing from this update site but already have an extra_query from an older one?
				if (empty($aSite->extra_query) && !empty($update_site['extra_query']))
				{
					$mustUpdate         = true;
					$aSite->extra_query = $update_site['extra_query'];
				}

				// Update the update site if necessary
				if ($mustUpdate)
				{
					$db->updateObject('#__update_sites', $aSite, 'update_site_id', true);
				}

				continue;
			}

			// Try to carry forward the first extra query (download key) found in the old update sites.
			$update_site['extra_query'] = ($update_site['extra_query'] ?? '') ?: ($aSite->extra_query ?: '');

			// In any other case we need to delete this update site, it's obsolete
			$deleteOldSites[] = $aSite->update_site_id;
		}

		if (!empty($deleteOldSites))
		{
			try
			{
				// Delete update sites
				$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
					->delete('#__update_sites')
					->whereIn($db->qn('update_site_id'), $deleteOldSites, ParameterType::INTEGER);
				$db->setQuery($query)->execute();

				// Delete update sites to extension ID records
				$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
					->delete('#__update_sites_extensions')
					->whereIn($db->qn('update_site_id'), $deleteOldSites, ParameterType::INTEGER);
				$db->setQuery($query)->execute();
			}
			catch (\Exception $e)
			{
				// Do nothing on failure
				return;
			}

		}

		// Do we still need to create a new update site?
		if ($needNewUpdateSite)
		{
			$update_site['extra_query'] = $update_site['extra_query'] ?? '';

			// No update sites defined. Create a new one.
			$newSite = (object) $update_site;
			$db->insertObject('#__update_sites', $newSite);

			$id                  = $db->insertid();
			$updateSiteExtension = (object) [
				'update_site_id' => $id,
				'extension_id'   => $this->extension_id,
			];
			$db->insertObject('#__update_sites_extensions', $updateSiteExtension);
		}
	}

	/**
	 * Gets the update site Ids for our extension.
	 *
	 * @return    array    An array of IDs
	 */
	public function getUpdateSiteIds(): array
	{
		$db    = $this->getDatabase();
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select($db->qn('update_site_id'))
			->from($db->qn('#__update_sites_extensions'))
			->where($db->qn('extension_id') . ' = :eid')
			->bind(':eid', $this->extension_id, ParameterType::INTEGER);

		try
		{
			$ret = $db->setQuery($query)->loadColumn(0);
		}
		catch (Exception $e)
		{
			$ret = null;
		}

		return is_array($ret) ? $ret : [];
	}

	/**
	 * Get the contents of all the update sites of the configured extension
	 *
	 * @return  array|null
	 */
	public function getUpdateSites(): ?array
	{
		$updateSiteIDs = $this->getUpdateSiteIds();
		$db            = $this->getDatabase();
		$query         = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select('*')
			->from($db->qn('#__update_sites'))
			->whereIn($db->qn('update_site_id'), $updateSiteIDs, ParameterType::INTEGER);

		try
		{
			$db->setQuery($query);

			$ret = $db->loadAssocList('update_site_id');
		}
		catch (Exception $e)
		{
			$ret = null;
		}

		return empty($ret) ? [] : $ret;
	}

	/**
	 * Gets the license key for a paid extension.
	 *
	 * On Joomla! 3 or when $forceLegacy is true we look in the component Options.
	 *
	 * On Joomla! 4 we use the information in the dlid element of the extension's XML manifest to parse the extra_query
	 * fields of all configured update sites of the extension. This is the same thing Joomla does when it tries to
	 * determine the license key of our extension when installing updates. If the extension is missing, it has no
	 * associated update sites, the update sites are missing / rebuilt / disassociated from the extension or the
	 * extra_query of all update site records is empty we parse the $extraQuery set in the constructor, if any. Also
	 * note that on Joomla 4 mode if the extension does not exist, does not have a manifest or does not have a valid
	 * dlid element in its manifest we will end up returning an empty string, just like Joomla! itself would have done
	 * when installing updates.
	 *
	 * @param   bool  $forceLegacy  Should I always retrieve the legacy license key, even in J4?
	 *
	 * @return  string
	 */
	public function getLicenseKey(bool $forceLegacy = false): string
	{
		// Joomla! 4. We need to parse the extra_query of the update sites to get the correct Download ID.
		$updateSites = $this->getUpdateSites();
		$extra_query = array_reduce($updateSites, function ($extra_query, $updateSite) {
			if (!empty($extra_query))
			{
				return $extra_query;
			}

			return $updateSite['extra_query'];
		}, '');

		// Fall back to legacy extra query
		if (empty($extra_query))
		{
			return '';
		}

		// Return the parsed results.
		return $this->getLicenseKeyFromExtraQuery($extra_query);
	}

	/**
	 * Returns an object with the #__extensions table record for the current extension.
	 *
	 * @return  object|null
	 */
	public function getExtensionObject()
	{
		[$extensionPrefix, $extensionName] = explode('_', $this->extensionKey);

		switch ($extensionPrefix)
		{
			default:
			case 'com':
				$type = 'component';
				$name = $this->extensionKey;
				break;

			case 'pkg':
				$type = 'package';
				$name = $this->extensionKey;
				break;
		}

		// Find the extension ID
		$db    = $this->getDatabase();
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select('*')
			->from($db->qn('#__extensions'))
			->where($db->qn('type') . ' = :type')
			->where($db->qn('element') . ' = :name')
			->bind(':type', $type)
			->bind(':name', $name);

		try
		{
			$db->setQuery($query);
			$extension = $db->loadObject();
		}
		catch (Exception $e)
		{
			return null;
		}

		return $extension;
	}

	/**
	 * Sanitizes the license key.
	 *
	 * YOU SHOULD OVERRIDE THIS METHOD. The default implementation returns a lowercase string with all characters except
	 * letters, numbers and colons removed.
	 *
	 * @param   string  $licenseKey
	 *
	 * @return  string  The sanitized license key
	 */
	public function sanitizeLicenseKey(string $licenseKey): string
	{
		return strtolower(preg_replace("/[^a-zA-Z0-9:]/", "", $licenseKey));
	}

	/**
	 * Is the provided string a valid license key?
	 *
	 * YOU SHOULD OVERRIDE THIS METHOD. The default implementation checks for valid Download IDs in the format used by
	 * Akeeba software.
	 *
	 * @param   string  $licenseKey
	 *
	 * @return  bool
	 */
	public function isValidLicenseKey(string $licenseKey): bool
	{
		return preg_match('/^(\d{1,}:)?[0-9a-f]{32}$/i', $licenseKey) === 1;
	}

	/**
	 * Extract the download ID from an extra_query based on the prefix and suffix information stored in the dlid element
	 * of the extension's XML manifest file.
	 *
	 * @param   string  $extra_query
	 *
	 * @return string
	 */
	protected function getLicenseKeyFromExtraQuery(?string $extra_query): string
	{
		$extra_query = trim($extra_query ?? '');

		if (empty($extra_query))
		{
			return '';
		}

		// Get the extension XML manifest. If the extension or the manifest don't exist return an empty string.
		$extension = $this->getExtensionObject();

		if (!$extension)
		{
			return '';
		}

		$installXmlFile = $this->getManifestXML(
			$extension->element,
			$extension->type,
			(int) $extension->client_id,
			$extension->folder
		);

		if (!$installXmlFile)
		{
			return '';
		}

		// If the manifest does not have a dlid element return an empty string.
		if (!isset($installXmlFile->dlid))
		{
			return '';
		}

		// Naive parsing of the extra_query, the same way Joomla does.
		$prefix     = (string) $installXmlFile->dlid['prefix'];
		$suffix     = (string) $installXmlFile->dlid['suffix'];
		$licenseKey = substr($extra_query, strlen($prefix));

		if ($licenseKey === false)
		{
			return '';
		}

		if ($suffix !== '')
		{
			$licenseKey = substr($licenseKey, 0, -strlen($suffix));
		}

		return ($licenseKey === false) ? '' : $licenseKey;
	}

	/**
	 * Get the manifest XML file of a given extension.
	 *
	 * @param   string   $element    element of an extension
	 * @param   string   $type       type of an extension
	 * @param   integer  $client_id  client_id of an extension
	 * @param   string   $folder     folder of an extension
	 *
	 * @return  SimpleXMLElement|bool False on failure
	 */
	protected function getManifestXML(string $element, string $type, int $client_id = 1, ?string $folder = null)
	{
		$path = ($client_id !== 0) ? JPATH_ADMINISTRATOR : JPATH_ROOT;

		switch ($type)
		{
			case 'component':
				$path .= '/components/' . $element . '/' . substr($element, 4) . '.xml';
				break;
			case 'plugin':
				$path .= '/plugins/' . $folder . '/' . $element . '/' . $element . '.xml';
				break;
			case 'module':
				$path .= '/modules/' . $element . '/' . $element . '.xml';
				break;
			case 'template':
				$path .= '/templates/' . $element . '/templateDetails.xml';
				break;
			case 'library':
				$path = JPATH_ADMINISTRATOR . '/manifests/libraries/' . $element . '.xml';
				break;
			case 'file':
				$path = JPATH_ADMINISTRATOR . '/manifests/files/' . $element . '.xml';
				break;
			case 'package':
				$path = JPATH_ADMINISTRATOR . '/manifests/packages/' . $element . '.xml';
		}

		return simplexml_load_file($path);
	}

	/**
	 * Gets the ID of an extension
	 *
	 * @param   string  $element  Extension element, e.g. com_foo, mod_foo, lib_foo, pkg_foo or foo (CAUTION: plugin,
	 *                            file!)
	 * @param   string  $type     Extension type: component, module, library, package, plugin or file
	 * @param   null    $folder   Plugins: plugin folder. Modules: admin/site
	 *
	 * @return  int  Extension ID or 0 on failure
	 */
	private function findExtensionId(string $element, string $type = 'component', ?string $folder = null): int
	{
		$db    = $this->getDatabase();
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select($db->qn('extension_id'))
			->from($db->qn('#__extensions'))
			->where($db->qn('element') . ' = :element')
			->where($db->qn('type') . ' = :type')
			->bind(':element', $element, ParameterType::STRING)
			->bind(':type', $type, ParameterType::STRING);

		// Plugin? We should look for a folder
		if ($type == 'plugin')
		{
			$folder = $folder ?: 'system';

			$query->where($db->qn('folder') . ' = :folder')
				->bind(':folder', $folder, ParameterType::STRING);
		}

		// Module? Use the folder to determine if it's site or admin module.
		if ($type == 'module')
		{
			$folder   = $folder ?: 'site';
			$clientId = ($folder == 'site') ? 0 : 1;

			$query->where($db->qn('client_id') . ' = :client_id')
				->bind(':client_id', $clientId, ParameterType::INTEGER);
		}

		try
		{
			$id = $db->setQuery($query, 0, 1)->loadResult();
		}
		catch (Exception $e)
		{
			$id = 0;
		}

		return empty($id) ? 0 : (int) $id;
	}

	private function createFakePackageExtension()
	{
		/** @var DatabaseDriver $db */
		$db = $this->getDatabase();

		$manifestCacheJson = json_encode([
			'name'         => 'Akeeba Backup for Joomla! package',
			'type'         => 'package',
			'creationDate' => gmdate('Y-m-d'),
			'author'       => 'Nicholas K. Dionysopoulos',
			'copyright'    => sprintf('Copyright (c)2006-%d Akeeba Ltd / Nicholas K. Dionysopoulos', gmdate('Y')),
			'authorEmail'  => '',
			'authorUrl'    => 'https://www.akeeba.com',
			'version'      => $this->version,
			'description'  => sprintf('Akeeba Backup for Joomla! installation package v.%s', $this->version),
			'group'        => '',
			'filename'     => 'pkg_akeebabackup',
		]);

		$extensionRecord = [
			'name'             => 'Akeeba Backup for Joomla! package',
			'type'             => 'package',
			'element'          => 'pkg_akeebabackup',
			'folder'           => '',
			'client_id'        => 0,
			'enabled'          => 1,
			'access'           => 1,
			'protected'        => 0,
			'manifest_cache'   => $manifestCacheJson,
			'params'           => '{}',
			'checked_out'      => 0,
			'checked_out_time' => null,
			'state'            => 0,
		];

		$extension = new Extension($db);
		$extension->save($extensionRecord);

		$this->createFakePackageManifest();
	}

	private function createFakePackageManifest()
	{
		$path = sprintf("%s/manifests/packages/%s.xml", JPATH_ADMINISTRATOR, $this->extensionKey);

		if (file_exists($path))
		{
			return;
		}

		$isPro   = defined('AKEEBABACKUP_PRO') ? AKEEBABACKUP_PRO : 0;
		$proCore = $isPro ? 'pro' : 'core';
		$dlid    = $isPro ? '<dlid prefix="dlid=" suffix=""/>' : '';
		$year    = gmdate('Y');
		$date    = gmdate('Y-m-d');

		$proPlugins = <<< END
        <file type="plugin" group="console" id="akeebabackup">plg_console_akeebabackup.zip</file>
        <file type="plugin" group="system" id="backuponupdate">plg_system_backuponupdate.zip</file>
        <file type="plugin" group="actionlog" id="akeebabackup">plg_actionlog_akeebabackup.zip</file>
END;
		$proPlugins = $isPro ? $proPlugins : '';

		$content = <<< XML
<?xml version="1.0" encoding="utf-8"?>
<extension version="3.9.0" type="package" method="upgrade">
	$dlid
    <name>Akeeba Backup for Joomla! package</name>
    <author>Nicholas K. Dionysopoulos</author>
    <creationDate>$date</creationDate>
    <packagename>akeebabackup</packagename>
    <version>{$this->version}</version>
    <url>https://www.akeeba.com</url>
    <packager>Akeeba Ltd</packager>
    <packagerurl>https://www.akeeba.com</packagerurl>
    <copyright>Copyright (c)2006-$year Akeeba Ltd / Nicholas K. Dionysopoulos</copyright>
    <license>GNU GPL v3 or later</license>
    <description>Akeeba Backup for Joomla! installation package {$this->version}</description>

    <files>
        <file type="component" id="com_akeebabackup">com_akeebabackup-{$proCore}.zip</file>
        <file type="plugin" group="quickicon" id="akeebabackup">plg_quickicon_akeebabackup.zip</file>
        $proPlugins
    </files>

    <scriptfile>script.akeebabackup.php</scriptfile>
</extension>
XML;

		if (!@file_put_contents($content, $path))
		{
			// File::write($path, $content);
		}
	}
}