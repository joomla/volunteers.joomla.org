<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Update;

defined('_JEXEC') || die;

use Exception;
use FOF40\Container\Container;
use FOF40\JoomlaAbstraction\CacheCleaner;
use FOF40\Model\Model;
use Joomla\CMS\Component\ComponentHelper as JComponentHelper;
use Joomla\CMS\Updater\Updater as JUpdater;
use SimpleXMLElement;

/**
 * A helper Model to interact with Joomla!'s extensions update feature
 */
class Update extends Model
{
	/** @var JUpdater The Joomla! updater object */
	protected $updater;

	/** @var int The extension_id of this component */
	protected $extension_id = 0;

	/** @var string The currently installed version, as reported by the #__extensions table */
	protected $version = 'dev';

	/** @var string The name of the component e.g. com_something */
	protected $component = 'com_foobar';

	/** @var string The URL to the component's update XML stream */
	protected $updateSite;

	/** @var string The name to the component's update site (description of the update XML stream) */
	protected $updateSiteName;

	/** @var string The extra query to append to (commercial) components' download URLs */
	protected $extraQuery;

	/** @var string The component Options key which stores a copy of the Download ID */
	protected $paramsKey = 'update_dlid';

	/**
	 * Caches the extension names to IDs so we don't query the database too many times.
	 *
	 * @var   array
	 */
	private $extensionIds = [];

	/**
	 * Public constructor. Initialises the protected members as well. Useful $config keys:
	 * update_component        The component name, e.g. com_foobar
	 * update_version        The default version if the manifest cache is unreadable
	 * update_site            The URL to the component's update XML stream
	 * update_extraquery    The extra query to append to (commercial) components' download URLs
	 * update_sitename        The update site's name (description)
	 * update_paramskey        The component parameters key which holds the license key in J3 (and a copy of it in J4)
	 *
	 * @param   array|Container|\ArrayAccess  $config
	 */
	public function __construct($config = [])
	{
		$container = Container::getInstance('com_FOOBAR');

		if (isset($config['update_container']) && is_object($config['update_container']) && ($config['update_container'] instanceof Container))
		{
			$container = $config['update_container'];
		}

		parent::__construct($container);

		// Get an instance of the updater class
		$this->updater = JUpdater::getInstance();

		$this->component = $config['update_component'] ?? $this->input->getCmd('option', '');

		// Get the component version
		if (isset($config['update_version']))
		{
			$this->version = $config['update_version'];
		}

		// Get the update site
		if (isset($config['update_site']))
		{
			$this->updateSite = $config['update_site'];
		}

		// Get the extra query
		if (isset($config['update_extraquery']))
		{
			$this->extraQuery = $config['update_extraquery'];
		}

		// Get the extra query
		if (isset($config['update_sitename']))
		{
			$this->updateSiteName = $config['update_sitename'];
		}

		// Get the extra query
		if (isset($config['update_paramskey']))
		{
			$this->paramsKey = $config['update_paramskey'];
		}

		// Get the extension type
		$extension = $this->getExtensionObject();

		if (is_object($extension))
		{
			$this->extension_id = $extension->extension_id;

			if (empty($this->version) || ($this->version == 'dev'))
			{
				$data = json_decode($extension->manifest_cache, true);

				if (isset($data['version']))
				{
					$this->version = $data['version'];
				}
			}

		}
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
		$legacyParamsKey = $this->getLegacyParamsKey();

		// Joomla 3 (Legacy): Download ID stored in the component options
		if ($forceLegacy || !version_compare(JVERSION, '3.999.999', 'gt'))
		{
			return $this->container->params->get($legacyParamsKey, '');
		}

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
			$extra_query = $this->extraQuery;
		}

		// Return the parsed results.
		return $this->getLicenseKeyFromExtraQuery($extra_query);
	}

	/**
	 * Get the contents of all the update sites of the configured extension
	 *
	 * @return  array|null
	 */
	public function getUpdateSites(): ?array
	{
		$updateSiteIDs = $this->getUpdateSiteIds();
		$db            = $this->container->db;
		$query         = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__update_sites'))
			->where($db->qn('update_site_id') . ' IN (' . implode(', ', $updateSiteIDs) . ')');

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

	public function setLicenseKey(string $licenseKey)
	{
		$legacyParamsKey = $this->getLegacyParamsKey();

		// Sanitize and validate the license key. If it's not valid we set an empty license key.
		$licenseKey = $this->sanitizeLicenseKey($licenseKey);

		if (!$this->isValidLicenseKey($licenseKey))
		{
			$licenseKey = '';
		}

		// Update $this->extraQuery.
		$this->extraQuery = $this->getExtraQueryString($licenseKey);

		// Save the license key in the component options ($legacyParamsKey)
		$this->container->params->set($legacyParamsKey, $licenseKey);
		$this->container->params->save();

		// Apply the new extra_query to the update site
		$this->refreshUpdateSite();
	}

	/**
	 * Copies a Joomla 3 license key from the Options storage to Joomla 4 download key storage (the extra_query column
	 * of the #__update_sites table).
	 *
	 * This method does nothing on Joomla 3.
	 *
	 * @return  void
	 */
	public function upgradeLicenseKey(): void
	{
		// Only applies to Joomla! 4
		if (!version_compare(JVERSION, '3.999.999', 'gt'))
		{
			return;
		}

		// Make sure we DO have a legacy license key
		$legacyKey = $this->getLicenseKey(true);

		if (empty($legacyKey))
		{
			return;
		}

		// Make sure we DO NOT have a J4 key. If we do, the J4 key wins and gets backported to legacy storage.
		$licenseKey = $this->getLicenseKey();

		if (!empty($licenseKey))
		{
			$this->backportLicenseKey();

			return;
		}

		// Save the legacy key as non-legacy. This updates the #__update_sites record, applying the license key.
		$this->setLicenseKey($legacyKey);
	}

	/**
	 * Copies a Joomla 4 license key from the download key storage (the extra_query column of the #__update_sites table)
	 * to the legacy Options storage.
	 *
	 * This method does nothing on Joomla 3.
	 *
	 * @return  void
	 */
	public function backportLicenseKey(): void
	{
		$legacyParamsKey = $this->getLegacyParamsKey();

		// Only applies to Joomla! 4
		if (!version_compare(JVERSION, '3.999.999', 'gt'))
		{
			return;
		}

		// Make sure we DO have a J4 key
		$licenseKey = $this->getLicenseKey();

		if (empty($licenseKey))
		{
			return;
		}

		// Make sure that the legacy key is NOT the same as the J4 key
		$legacyKey = $this->getLicenseKey(true);

		if ($legacyKey === $licenseKey)
		{
			return;
		}

		// Save the license key to the legacy storage (component options)
		$this->container->params->set($legacyParamsKey, $licenseKey);
		$this->container->params->save();
	}

	/**
	 * Get an extra query string based on the dlid element of the XML manifest file of the extension.
	 *
	 * If the extension does not exist, the manifest does not exist or it does not have a dlid element we fall back to
	 * the legacy implementation of extra_query (getExtraQueryStringLegacy)
	 *
	 * @param   string  $licenseKey
	 *
	 * @return  string
	 */
	public function getExtraQueryString(string $licenseKey): string
	{
		// Make sure the (sanitized) license key is valid. Otherwise we return an empty string.
		$licenseKey = $this->sanitizeLicenseKey($licenseKey);

		if (!$this->isValidLicenseKey($licenseKey))
		{
			return '';
		}

		// Get a fallback extra query using the legacy method
		$fallbackExtraQuery = $this->getExtraQueryStringLegacy($licenseKey);

		// Get the extension XML manifest. If the extension or the manifest don't exist use the fallback extra_query.
		$extension = $this->getExtensionObject();

		if (!$extension)
		{
			return $fallbackExtraQuery;
		}

		$installXmlFile = $this->getManifestXML(
			$extension->element,
			$extension->type,
			(int) $extension->client_id,
			$extension->folder
		);

		if (!$installXmlFile)
		{
			return $fallbackExtraQuery;
		}

		// If the manifest does not have the dlid element return the fallback extra_query.
		if (!isset($installXmlFile->dlid))
		{
			return $fallbackExtraQuery;
		}

		$prefix = (string) $installXmlFile->dlid['prefix'];
		$suffix = (string) $installXmlFile->dlid['suffix'];

		return $prefix . $this->sanitizeLicenseKey($licenseKey) . $suffix;
	}

	/**
	 * Retrieves the update information of the component, returning an array with the following keys:
	 *
	 * hasUpdate  True if an update is available
	 * version    The version of the available update
	 * infoURL    The URL to the download page of the update
	 *
	 * @param   bool  $force  Set to true if you want to forcibly reload the update information
	 *
	 * @return  array  See the method description for more information
	 */
	public function getUpdates(bool $force = false): array
	{
		$db = $this->container->db;

		// Default response (no update)
		$updateResponse = [
			'hasUpdate' => false,
			'version'   => '',
			'infoURL'   => '',
		];

		if (empty($this->extension_id))
		{
			return $updateResponse;
		}

		// If we had to update the version number stored in the database then we should force reload the updates
		if ($this->updatedCachedVersionNumber())
		{
			$force = true;
		}

		// If we are forcing the reload, set the last_check_timestamp to 0
		// and remove cached component update info in order to force a reload
		if ($force)
		{
			// Find the update site IDs
			$updateSiteIds = $this->getUpdateSiteIds();

			if (empty($updateSiteIds))
			{
				return $updateResponse;
			}

			// Set the last_check_timestamp to 0
			$query = $db->getQuery(true)
				->update($db->qn('#__update_sites'))
				->set($db->qn('last_check_timestamp') . ' = ' . $db->q('0'))
				->where($db->qn('update_site_id') . ' IN (' . implode(', ', $updateSiteIds) . ')');
			$db->setQuery($query);
			$db->execute();

			// Remove cached component update info from #__updates
			$query = $db->getQuery(true)
				->delete($db->qn('#__updates'))
				->where($db->qn('update_site_id') . ' IN (' . implode(', ', $updateSiteIds) . ')');
			$db->setQuery($query);
			$db->execute();
		}

		// Use the update cache timeout specified in com_installer
		$comInstallerParams = JComponentHelper::getParams('com_installer', false);
		$timeout            = 3600 * $comInstallerParams->get('cachetimeout', '6');

		// Load any updates from the network into the #__updates table
		$this->updater->findUpdates($this->extension_id, $timeout);

		// Get the update record from the database
		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__updates'))
			->where($db->qn('extension_id') . ' = ' . $db->q($this->extension_id));
		$db->setQuery($query);
		$updateRecord = $db->loadObject();

		// If we have an update record in the database return the information found there
		if (is_object($updateRecord))
		{
			$updateResponse = [
				'hasUpdate' => true,
				'version'   => $updateRecord->version,
				'infoURL'   => $updateRecord->infourl,
			];
		}

		return $updateResponse;
	}

	/**
	 * Gets the update site Ids for our extension.
	 *
	 * @return    array    An array of IDs
	 */
	public function getUpdateSiteIds(): array
	{
		$db    = $this->container->db;
		$query = $db->getQuery(true)
			->select($db->qn('update_site_id'))
			->from($db->qn('#__update_sites_extensions'))
			->where($db->qn('extension_id') . ' = ' . $db->q($this->extension_id));
		$db->setQuery($query);

		try
		{
			$ret = $db->loadColumn(0);
		}
		catch (Exception $e)
		{
			$ret = null;
		}

		return is_array($ret) ? $ret : [];
	}

	/**
	 * Get the currently installed version as reported by the #__extensions table
	 *
	 * @return  string
	 */
	public function getVersion(): string
	{
		return $this->version;
	}

	/**
	 * Override the currently installed version as reported by the #__extensions table
	 *
	 * @param   string  $version
	 */
	public function setVersion(string $version): void
	{
		$this->version = $version;
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

		/**
		 * Record whether I have made any changes to the update sites or the updates themselves so I can clear Joomla's
		 * undocumented cache.
		 */
		$madeChanges = [
			'updateSites' => false,
			'updates'     => false,
		];

		/**
		 * Create the update site definition we want to see in (or store into) the database.
		 *
		 * Since this is called primarily by our own software I am taking the liberty of setting up an extra_query if
		 * none is provided. The code here will prefer a manually set up extra_query. If none is present we will create
		 * one. There are two parts handled by $this->getExtraQueryString($this->getLicenseKey()).
		 *
		 * **License key**. On Joomla 3 we read it from the component's Options. On Joomla 4 we try to first read it
		 * from the #__update_sites table and the extra_query format in the XML manifest. If either is not present we
		 * will fall back to the component itself.
		 *
		 * **Extra query format**. We try to read the format from the XML manifest of the extension. If it's not set up
		 * we fall back to the Akeeba-comaptible format dlid=YOUR_DOWNLOAD_ID_HERE.
		 *
		 * This provides maximum flexibility and works with both Joomla 4 and Joomla 3 equally.
		 */
		$update_site = [
			'name'                 => $this->updateSiteName,
			'type'                 => 'extension',
			'location'             => $this->updateSite,
			'enabled'              => 1,
			'last_check_timestamp' => 0,
			'extra_query'          => $this->extraQuery ?? $this->getExtraQueryString($this->getLicenseKey()),
		];

		// Get a reference to the db driver
		$db = $this->container->db;

		// Get the #__update_sites columns
		$columns = $db->getTableColumns('#__update_sites', true);

		if (!array_key_exists('extra_query', $columns))
		{
			unset($update_site['extra_query']);
		}

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
			$query = $db->getQuery(true)
				->select('*')
				->from($db->qn('#__update_sites'))
				->where($db->qn('update_site_id') . ' = ' . $db->q($id));
			$db->setQuery($query);
			$aSite = $db->loadObject();

			if (empty($aSite))
			{
				// Update site does not exist?!
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

				// Do we have the extra_query property (J 3.2+) and does it match?
				if (property_exists($aSite, 'extra_query') && isset($update_site['extra_query'])
					&& ($aSite->extra_query != $update_site['extra_query']))
				{
					$mustUpdate         = true;
					$aSite->extra_query = $update_site['extra_query'];
				}

				// Update the update site if necessary
				if ($mustUpdate)
				{
					$madeChanges['updateSites'] = true;

					$db->updateObject('#__update_sites', $aSite, 'update_site_id', true);
				}

				// Make changes to any #__updates records linked to this update site ID
				$madeChanges['updates'] = $this->fixUpdates((int) $id, $aSite->extra_query);

				continue;
			}

			// In any other case we need to delete this update site, it's obsolete.
			$deleteOldSites[] = $aSite->update_site_id;
		}

		if (!empty($deleteOldSites))
		{
			try
			{
				$obsoleteIDsQuoted = array_map([$db, 'quote'], $deleteOldSites);

				// Delete update sites
				$query = $db->getQuery(true)
					->delete('#__update_sites')
					->where($db->qn('update_site_id') . ' IN (' . implode(',', $obsoleteIDsQuoted) . ')');
				$db->setQuery($query)->execute();

				// Delete update sites to extension ID records
				$query = $db->getQuery(true)
					->delete('#__update_sites_extensions')
					->where($db->qn('update_site_id') . ' IN (' . implode(',', $obsoleteIDsQuoted) . ')');
				$db->setQuery($query)->execute();
			}
			catch (\Exception $e)
			{
				// Do nothing on failure
			}
			finally
			{
				$madeChanges['updateSites'] = true;
			}

			// Clear the caches for #__update_sites and #__updates if necessary
			if ($madeChanges['updateSites'] || $madeChanges['updates'])
			{
				CacheCleaner::clearCacheGroups([
					'_system',
					'com_installer',
					'com_modules',
					'com_plugins',
					'mod_menu',
				]);
			}
		}

		// Do we still need to create a new update site?
		if ($needNewUpdateSite)
		{
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

		// Finally, adopt my extensions
		$this->adoptMyExtensions();
	}

	/**
	 * Removes any update sites which go by the same name or the same location as our update site but do not match the
	 * extension ID.
	 */
	public function removeObsoleteUpdateSites(): void
	{
		$db = $this->container->db;

		// Get update site IDs
		$updateSiteIDs = $this->getUpdateSiteIds();

		// Find update sites where the name OR the location matches BUT they are not one of the update site IDs
		$query = $db->getQuery(true)
			->select($db->qn('update_site_id'))
			->from($db->qn('#__update_sites'))
			->where(
				'((' . $db->qn('name') . ' = ' . $db->q($this->updateSiteName) . ') OR ' .
				'(' . $db->qn('location') . ' = ' . $db->q($this->updateSite) . '))'
			);

		if (!empty($updateSiteIDs))
		{
			$updateSitesQuoted = array_map([$db, 'quote'], $updateSiteIDs);
			$query->where($db->qn('update_site_id') . ' NOT IN (' . implode(',', $updateSitesQuoted) . ')');
		}

		try
		{
			$ids = $db->setQuery($query)->loadColumn();

			if (!empty($ids))
			{
				$obsoleteIDsQuoted = array_map([$db, 'quote'], $ids);

				// Delete update sites
				$query = $db->getQuery(true)
					->delete('#__update_sites')
					->where($db->qn('update_site_id') . ' IN (' . implode(',', $obsoleteIDsQuoted) . ')');
				$db->setQuery($query)->execute();

				// Delete update sites to extension ID records
				$query = $db->getQuery(true)
					->delete('#__update_sites_extensions')
					->where($db->qn('update_site_id') . ' IN (' . implode(',', $obsoleteIDsQuoted) . ')');
				$db->setQuery($query)->execute();
			}
		}
		catch (\Exception $e)
		{
			// Do nothing on failure
			return;
		}
	}

	/**
	 * Makes sure that the version number cached in the #__extensions table is consistent with the version number set in
	 * this model.
	 *
	 * @return  bool  True if we updated the version number cached in the #__extensions table.
	 *
	 * @since   3.1.2
	 */
	public function updatedCachedVersionNumber(): bool
	{
		$extension = $this->getExtensionObject();

		if (!is_object($extension))
		{
			return false;
		}

		$data       = json_decode($extension->manifest_cache, true);
		$mustUpdate = true;

		if (isset($data['version']))
		{
			$mustUpdate = $this->version != $data['version'];
		}

		if (!$mustUpdate)
		{
			return false;
		}

		// The cached version is wrong; let's update it
		$data['version']           = $this->version;
		$extension->manifest_cache = json_encode($data);
		$db                        = $this->container->db;

		return $db->updateObject('#__extensions', $extension, ['extension_id']);
	}

	/**
	 * Returns an object with the #__extensions table record for the current extension.
	 *
	 * @return  object|null
	 */
	public function getExtensionObject()
	{
		[$extensionPrefix, $extensionName] = explode('_', $this->component);

		switch ($extensionPrefix)
		{
			default:
			case 'com':
				$type = 'component';
				$name = $this->component;
				break;

			case 'pkg':
				$type = 'package';
				$name = $this->component;
				break;
		}

		// Find the extension ID
		$db    = $this->container->db;
		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__extensions'))
			->where($db->qn('type') . ' = ' . $db->q($type))
			->where($db->qn('element') . ' = ' . $db->q($name));

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
	 * Adopt the extensions included in the package.
	 *
	 * This modifies the package_id column of the #__extensions table for the records of the extensions declared in the
	 * new package's manifest. This allows you to use Discover to install new extensions without leaving them “orphan”
	 * of a package in the #__extensions table, something which could cause problems when running Joomla! Update.
	 *
	 * @return  void
	 */
	public function adoptMyExtensions(): void
	{
		// Get the extension ID of the new package
		$newPackageId = $this->extension_id;

		if (empty($newPackageId))
		{
			return;
		}

		// Get the extension IDs
		$extensionIDs = array_map([$this, 'getExtensionId'], $this->getExtensionsFromPackage($this->component));
		$extensionIDs = array_filter($extensionIDs, function ($x) {
			return !empty($x);
		});

		if (empty($extensionIDs))
		{
			return;
		}

		// Reassign all extensions
		$db    = $this->container->db;
		$query = $db->getQuery(true)
			->update($db->quoteName('#__extensions'))
			->set($db->qn('package_id') . ' = ' . $db->q($newPackageId))
			->where($db->qn('extension_id') . 'IN(' . implode(', ', array_map([$db, 'q'], $extensionIDs)) . ')');
		$db->setQuery($query)->execute();
	}

	/**
	 * Returns the extension ID for a Joomla extension given its name.
	 *
	 * This is deliberately public so that custom handlers can use it without having to reimplement it.
	 *
	 * @param   string  $extension  The extension name, e.g. `plg_system_example`.
	 *
	 * @return  int|null  The extension ID or null if no such extension exists
	 */
	public function getExtensionId(string $extension): ?int
	{
		if (isset($this->extensionIds[$extension]))
		{
			return $this->extensionIds[$extension];
		}

		$this->extensionIds[$extension] = null;

		$criteria = $this->extensionNameToCriteria($extension);

		if (empty($criteria))
		{
			return $this->extensionIds[$extension];
		}

		$db    = $this->container->db;
		$query = $db->getQuery(true)
			->select($db->quoteName('extension_id'))
			->from($db->quoteName('#__extensions'));

		foreach ($criteria as $key => $value)
		{
			$query->where($db->qn($key) . ' = ' . $db->q($value));
		}

		try
		{
			$this->extensionIds[$extension] = $db->setQuery($query)->loadResult();
		}
		catch (\RuntimeException $e)
		{
			return null;
		}

		return $this->extensionIds[$extension];
	}

	/**
	 * Returns the component Options key which holds a copy of the license key
	 *
	 * @return  string
	 */
	protected function getLegacyParamsKey(): string
	{
		if (!empty($this->paramsKey))
		{
			return $this->paramsKey;
		}

		$this->paramsKey = 'update_dlid';

		return $this->paramsKey;
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
	 * Get a legacy extra query string. Do NOT call this directly. Call getExtraQueryString() instead.
	 *
	 * YOU SHOULD OVERRIDE THIS METHOD. This returns dlid=SANITIZED_LICENSE_KEY which is what Akeeba Release System,
	 * used to deliver all Akeeba extensions, expects.
	 *
	 * @param   string  $licenseKey  The license key
	 *
	 * @return  string  The extra_query string to append to a download URL to implement the license key
	 */
	protected function getExtraQueryStringLegacy(string $licenseKey): string
	{
		if (empty($licenseKey) || !$this->isValidLicenseKey($licenseKey))
		{
			return '';
		}

		return 'dlid=' . $this->sanitizeLicenseKey($licenseKey);
	}

	/**
	 * Get the manifest XML file of a given extension.
	 *
	 * @param   string   $element    element of an extension
	 * @param   string   $type       type of an extension
	 * @param   integer  $client_id  client_id of an extension
	 * @param   string   $folder     folder of an extension
	 *
	 * @return  SimpleXMLElement
	 */
	protected function getManifestXML(string $element, string $type, int $client_id = 1, ?string $folder = null): SimpleXMLElement
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
	 * Fix updates Joomla has already found.
	 *
	 * Joomla sometimes has a stroke and clears the extra_query column of the #__update_sites. This means that it copies
	 * over an empty extra_query to the #__updates table. Even though we have fixed the #__update_sites already, the
	 * fact that it was previously broken means that Joomla STILL doesn't see the extra query for the udpates it has
	 * already found, therefore trying to install them will fail with a 403 error.
	 *
	 * This method addresses this madness. It trawls the #__updates table for any updates already found with the given
	 * update site ID and makes sure that their extra_query matches the one it should have been using. If not, it is
	 * updated.
	 *
	 * If at least one update record has been updated it returns true so that refresh_update_site() can then clear the
	 * undocumented query caches of Joomla. If we don't do that then EVEN THOUGH we have fixed BOTH the #__update_sites
	 * AND the #__updates records for our extension Joomla would STILL fail to download the updates with a 403, since it
	 * would be using the cached updates from the undocumented query cache.
	 *
	 * The fact that Joomla was trying to tell me that somehow it's my problem that it does all these stupid things is
	 * unconscionable. Still, I am proving ONCE AGAIN that I can work around Joomla's major bugs, even the ones that are
	 * left unfixed after a decade of me reporting them privately to the project. What's worse is that my public report
	 * on March 6th, 2021 was met with open hostility and threats instead of the project writing the total of 10 lines
	 * of code to address it. This is absolutely insane. No problem, though, here I am working around Joomla's bugs, as
	 * I have been doing since 2006...
	 *
	 * @param   int          $updateSiteId
	 * @param   string|null  $extraQuery
	 *
	 * @return bool
	 */
	private function fixUpdates(int $updateSiteId, ?string $extraQuery): bool
	{
		// Make sure the update site ID is valid.
		if ($updateSiteId <= 0)
		{
			return false;
		}

		// If the extra query is empty there's no reason for me to do anything. Bye!
		if (empty($extraQuery))
		{
			return false;
		}

		// Try to get the update records.
		try
		{
			$db      = $this->container->db;
			$query   = $db->getQuery(true)
				->select([
					$db->qn('update_id'),
					$db->qn('extra_query'),
				])
				->from($db->qn('#__updates'))
				->where($db->qn('update_site_id') . ' = ' . $db->q($updateSiteId));
			$updates = $db->setQuery($query)->loadObjectList();
		}
		catch (Exception $e)
		{
			return false;
		}

		// Do I even have any udpates found...?
		if (empty($updates))
		{
			return false;
		}

		// Process each udpate record.
		$madeChanges = false;

		foreach ($updates as $update)
		{
			// The extra query matches. Nothing to do here.
			if ($update->extra_query == $extraQuery)
			{
				continue;
			}

			try
			{
				$query = $db->getQuery(true)
					->update($db->qn('#__updates'))
					->set($db->qn('extra_query') . ' = ' . $db->q($extraQuery))
					->where($db->qn('update_id') . ' = ' . $db->q($update->update_id));
				$db->setQuery($query)->execute();
			}
			catch (Exception $e)
			{
				// Well, sometimes Joomla bites the bullet...
				continue;
			}
		}

		return $madeChanges;
	}

	/**
	 * Get the list of extensions included in a package
	 *
	 * @param   string  $package
	 *
	 * @return  array
	 */
	private function getExtensionsFromPackage(string $package): array
	{
		$extensions = [];
		$xml        = $this->getPackageXMLManifest($package);

		if (is_null($xml))
		{
			return $extensions;
		}

		foreach ($xml->xpath('//files/file') as $fileField)
		{
			$extension = $this->xmlNodeToExtensionName($fileField);

			if (is_null($extension))
			{
				continue;
			}

			$extensions[] = $extension;
		}

		return $extensions;
	}

	/**
	 * Gets a SimpleXMLElement representation of the cached manifest of the extension.
	 *
	 * @param   string  $package
	 *
	 * @return  SimpleXMLElement|null
	 */
	private function getPackageXMLManifest(string $package): ?SimpleXMLElement
	{
		$filePath = $this->getCachedManifestPath($package);

		if (!@file_exists($filePath) || !@is_readable($filePath))
		{
			return null;
		}

		$xmlContent = @file_get_contents($filePath);

		if (empty($xmlContent))
		{
			return null;
		}

		return new SimpleXMLElement($xmlContent);
	}

	/**
	 * Get the absolute filesystem path
	 *
	 * @param   string  $package
	 *
	 * @return  string
	 */
	private function getCachedManifestPath(string $package): string
	{
		return JPATH_MANIFESTS . '/packages/' . $package . '.xml';
	}

	/**
	 * Take a SimpleXMLElement `<file>` node of the package manifest and return the corresponding Joomla extension name
	 *
	 * @param   SimpleXMLElement  $fileField  The `<file>` node of the package manifest
	 *
	 * @return  string|null  The extension name, null if it cannot be determined.
	 */
	private function xmlNodeToExtensionName(SimpleXMLElement $fileField): ?string
	{
		$type = (string) $fileField->attributes()->type;
		$id   = (string) $fileField->attributes()->id;

		switch ($type)
		{
			case 'component':
			case 'file':
			case 'library':
				$extension = $id;
				break;

			case 'plugin':
				$group     = (string) $fileField->attributes()->group ?? 'system';
				$extension = 'plg_' . $group . '_' . $id;
				break;

			case 'module':
				$client    = (string) $fileField->attributes()->client ?? 'site';
				$extension = (($client != 'site') ? 'a' : '') . $id;
				break;

			default:
				$extension = null;
				break;
		}

		return $extension;
	}

	/**
	 * Convert a Joomla extension name to `#__extensions` table query criteria.
	 *
	 * The following kinds of extensions are supported:
	 * * `pkg_something` Package type extension
	 * * `com_something` Component
	 * * `plg_folder_something` Plugins
	 * * `mod_something` Site modules
	 * * `amod_something` Administrator modules. THIS IS CUSTOM.
	 * * `file_something` File type extension
	 * * `lib_something` Library type extension
	 *
	 * @param   string  $extensionName
	 *
	 * @return  string[]
	 */
	private function extensionNameToCriteria(string $extensionName): array
	{
		$parts = explode('_', $extensionName, 3);

		switch ($parts[0])
		{
			case 'pkg':
				return [
					'type'    => 'package',
					'element' => $extensionName,
				];

			case 'com':
				return [
					'type'    => 'component',
					'element' => $extensionName,
				];

			case 'plg':
				return [
					'type'    => 'plugin',
					'folder'  => $parts[1],
					'element' => $parts[2],
				];

			case 'mod':
				return [
					'type'      => 'module',
					'element'   => $extensionName,
					'client_id' => 0,
				];

			// That's how we note admin modules
			case 'amod':
				return [
					'type'      => 'module',
					'element'   => substr($extensionName, 1),
					'client_id' => 1,
				];

			case 'file':
				return [
					'type'    => 'file',
					'element' => $extensionName,
				];

			case 'lib':
				return [
					'type'    => 'library',
					'element' => $parts[1],
				];
		}

		return [];
	}

}
