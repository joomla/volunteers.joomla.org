<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

/**
 * Akeeba Backup installer helper
 *
 * Adds the Download ID query string parameter to the download URL if for any reason your Joomla Update Sites had the
 * extra_query column wiped (e.g. by rebuilding the update sites list). This plugin is not necessary for most of our
 * users since our software uses the original method of changing the extra_query column of the #__update_sites entry
 * for the extension.
 *
 * @since  6.6.1
 */
class plgInstallerAkeebabackup extends CMSPlugin
{
	/**
	 * The name of the package in the download URL we should match in this plugin
	 *
	 * @var   string
	 * @since 6.6.1
	 */
	private $packageMatch = 'pkg_akeeba';

	/**
	 * List of URL prefixes we are allowed to work with
	 *
	 * @var   array
	 * @since 6.6.1
	 */
	private $allowedURLPrefixes = [
		'https://www.akeeba.com',
	];

	/**
	 * List of Akeeba components which may have a Download ID setting in their Options page. The first extension listed
	 * here takes precedence in case multiple, different download IDs are recovered from these extensions' settings.
	 *
	 * @var   array
	 * @since 6.6.1
	 */
	private $akeebaExtensions = [
		'com_akeeba',
		'com_admintools',
		'com_ats',
	];

	/**
	 * List of the Download ID setting key in Akeeba components. In case there are multiple keys found the first one
	 * listed here wins.
	 *
	 * @var   array
	 * @since 6.6.1
	 */
	private $possibleDownloadIDKeys = [
		'update_dlid',
		'downloadid',
		'dlid',
	];

	/**
	 * Handles Joomla's event fired before downloading an update package.
	 *
	 * @param   string  $url      The URL of the package Joomla is trying to install
	 * @param   array   $headers  The HTTP headers used for downloading the package
	 *
	 * @return  void
	 *
	 * @since   6.6.1
	 */
	public function onInstallerBeforePackageDownload(&$url, &$headers)
	{
		// This plugin only applies to Joomla! 3
		if (version_compare(JVERSION, '3.999.999', 'gt'))
		{
			return;
		}

		// Make sure the URL belongs to one of our domain names
		if (!$this->hasAllowedPrefix($url))
		{
			return;
		}

		// Make sure the URL seems to be for a package are meant to handle
		$uri      = Uri::getInstance($url);
		$path     = $uri->getPath();
		$baseName = basename($path);
		$pattern  = $this->packageMatch . '-*-pro*.zip';

		if (!fnmatch($pattern, $baseName))
		{
			return;
		}

		// Make sure this URL does not already have a download ID
		if ($this->hasDownloadID($uri))
		{
			return;
		}

		// Get the Download ID and make sure it's not empty
		try
		{
			$dlid = $this->getDownloadID();
		}
		catch (Exception $e)
		{
			$dlid = '';
		}

		if (empty($dlid))
		{
			return;
		}

		// Apply the download ID to the download URL
		$uri->setVar('dlid', $dlid);

		$url = $uri->toString();
	}

	/**
	 * Checks if the download URL is one we're supposed to handle. We have a whitelist of allowed URL prefixes set up
	 * at the top of this plugin.
	 *
	 * @param   string  $url  The download URL to check
	 *
	 * @return  bool
	 * @since   6.6.1
	 */
	private function hasAllowedPrefix($url)
	{
		$hasAllowedPrefix = false;

		foreach ($this->allowedURLPrefixes as $prefix)
		{
			$hasAllowedPrefix = $hasAllowedPrefix || (strpos($url, $prefix) === 0);
		}

		return $hasAllowedPrefix;
	}

	/**
	 * Does the download URL already have a non-empty Download ID query parameter?
	 *
	 * @param   Uri  $uri  The download URL to check
	 *
	 * @return  bool
	 * @since   6.6.1
	 */
	private function hasDownloadID($uri)
	{
		$dlid = $uri->getVar('dlid', null);

		return !empty($dlid);

	}

	/**
	 * Get the applicable Download ID. We prioritize the Download ID of the component listed first in the
	 * $this->akeebaExtensions array. If that component does not have a non-empty Download ID or is not currently
	 * installed we will return the first non-empty Download ID set up in any other extension. This protects the user
	 * against the Download ID being unset in one of our Pro extensions they are trying to update but set in another
	 * one. In this case we are essentially silently correcting their mistake and allow them to update their extension
	 * anyway.
	 *
	 * The reason we have prioritization by extension is that it's possible that the same site has Download IDs from
	 * multiple Akeeba clients. For example, the site integrator uses their own Download ID for Akeeba Backup
	 * Professional, the site security auditor uses their Download ID for Admin Tools Professional and the site owner
	 * uses their own Download ID for Akeeba Ticket System Professional. In this case each package needs a different
	 * Download ID to be downloaded since each one of these Download IDs is valid for a specific extension only.
	 *
	 * @return  string
	 * @since   6.6.1
	 */
	private function getDownloadID()
	{
		$downloadIDs = [];

		foreach ($this->akeebaExtensions as $extension)
		{
			// Make sure the component is installed and enabled
			if (!ComponentHelper::isInstalled($extension) || !ComponentHelper::isEnabled($extension))
			{
				return null;
			}

			// Get the component's parameters
			$params = ComponentHelper::getParams($extension);

			// Make sure the parameters object is valid
			if (is_null($params) || !is_object($params) || !($params instanceof Registry))
			{
				continue;
			}

			foreach ($this->possibleDownloadIDKeys as $key)
			{
				$value = $params->get($key, null);

				if (empty($value))
				{
					continue;
				}

				$downloadIDs[] = $value;

				break;
			}
		}

		if (empty($downloadIDs))
		{
			return '';
		}

		$downloadIDs = array_unique($downloadIDs);

		return array_shift($downloadIDs);
	}
}
