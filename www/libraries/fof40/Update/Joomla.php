<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace FOF40\Update;

defined('_JEXEC') || die;

class Joomla extends Extension
{
	/**
	 * The source for LTS updates
	 *
	 * @var  string
	 */
	protected static $lts_url = 'http://update.joomla.org/core/list.xml';

	/**
	 * The source for STS updates
	 *
	 * @var  string
	 */
	protected static $sts_url = 'http://update.joomla.org/core/sts/list_sts.xml';

	/**
	 * The source for test release updates
	 *
	 * @var  string
	 */
	protected static $test_url = 'http://update.joomla.org/core/test/list_test.xml';

	/**
	 * Reads a "collection" XML update source and picks the correct source URL
	 * for the extension update source.
	 *
	 * @param string      $url      The collection XML update source URL to read from
	 * @param string|null $jVersion Joomla! version to fetch updates for, or null to use JVERSION
	 *
	 * @return  string  The URL of the extension update source, or empty if no updates are provided / fetching failed
	 */
	public function getUpdateSourceFromCollection(string $url, ?string $jVersion = null): string
	{
		$provider = new Collection();

		return $provider->getExtensionUpdateSource($url, 'file', 'joomla', $jVersion);
	}

	/**
	 * Determines the properties of a version: STS/LTS, normal or testing
	 *
	 * @param string      $jVersion       The version number to check
	 * @param string|null $currentVersion The current Joomla! version number
	 *
	 * @return  array  The properties analysis
	 */
	public function getVersionProperties(string $jVersion, ?string $currentVersion = null): array
	{
		// Initialise
		$ret = [
			'lts'     => true,
			// Is this an LTS release? False means STS.
			'current' => false,
			// Is this a release in the $currentVersion branch?
			'upgrade' => 'none',
			// Upgrade relation of $jVersion to $currentVersion: 'none' (can't upgrade), 'lts' (next or current LTS), 'sts' (next or current STS) or 'current' (same release, no upgrade available)
			'testing' => false,
			// Is this a testing (alpha, beta, RC) release?
		];

		// Get the current version if none is defined
		if (is_null($currentVersion))
		{
			$currentVersion = JVERSION;
		}

		// Sanitise version numbers
		$sameVersion    = $jVersion === $currentVersion;
		$jVersion       = $this->sanitiseVersion($jVersion);
		$currentVersion = $this->sanitiseVersion($currentVersion);
		$sameVersion    = $sameVersion || ($jVersion === $currentVersion);

		// Get the base version
		$baseVersion = substr($jVersion, 0, 3);

		// Get the minimum and maximum current version numbers
		$current_minimum = substr($currentVersion, 0, 3);
		$current_maximum = $current_minimum . '.9999';

		// Initialise STS/LTS version numbers
		$sts_minimum = false;
		$sts_maximum = false;
		$lts_minimum = false;

		// Is it an LTS or STS release?
		switch ($baseVersion)
		{
			case '1.5':
				$ret['lts'] = true;
				break;

			case '1.6':
				$ret['lts']  = false;
				$sts_minimum = '1.7';
				$sts_maximum = '1.7.999';
				$lts_minimum = '2.5';
				break;

			case '1.7':
				$ret['lts']  = false;
				$sts_minimum = false;
				$lts_minimum = '2.5';
				break;

			case '2.5':
				$ret['lts']  = true;
				$sts_minimum = false;
				$lts_minimum = '2.5';
				break;

			default:
				$majorVersion = (int) substr($jVersion, 0, 1);
				//$minorVersion = (int) substr($jVersion, 2, 1);

				$ret['lts']  = true;
				$sts_minimum = false;
				$lts_minimum = $majorVersion . '.0';
				break;
		}

		// Is it a current release?
		if (version_compare($jVersion, $current_minimum, 'ge') && version_compare($jVersion, $current_maximum, 'le'))
		{
			$ret['current'] = true;
		}

		// Is this a testing release?
		$versionParts    = explode('.', $jVersion);
		$lastVersionPart = array_pop($versionParts);

		if (in_array(substr($lastVersionPart, 0, 1), ['a', 'b']))
		{
			$ret['testing'] = true;
		}
		elseif (substr($lastVersionPart, 0, 2) == 'rc')
		{
			$ret['testing'] = true;
		}
		elseif (substr($lastVersionPart, 0, 3) == 'dev')
		{
			$ret['testing'] = true;
		}

		// Find the upgrade relation of $jVersion to $currentVersion
		if (version_compare($jVersion, $currentVersion, 'eq'))
		{
			$ret['upgrade'] = 'current';
		}
		elseif (($sts_minimum !== false) && version_compare($jVersion, $sts_minimum, 'ge') && version_compare($jVersion, $sts_maximum, 'le'))
		{
			$ret['upgrade'] = 'sts';
		}
		elseif (($lts_minimum !== false) && version_compare($jVersion, $lts_minimum, 'ge'))
		{
			$ret['upgrade'] = 'lts';
		}
		elseif ($baseVersion === $current_minimum)
		{
			$ret['upgrade'] = $ret['lts'] ? 'lts' : 'sts';
		}
		else
		{
			$ret['upgrade'] = 'none';
		}

		if ($sameVersion)
		{
			$ret['upgrade'] = 'none';
		}

		return $ret;
	}


	/**
	 * Filters a list of updates, making sure they apply to the specified CMS
	 * release.
	 *
	 * @param array       $updates  A list of update records returned by the getUpdatesFromExtension method
	 * @param string|null $jVersion The current Joomla! version number
	 *
	 * @return  array  A filtered list of updates. Each update record also includes version relevance information.
	 */
	public function filterApplicableUpdates(array $updates, ?string $jVersion = null): array
	{
		if (empty($jVersion))
		{
			$jVersion = JVERSION;
		}

		$versionParts         = explode('.', $jVersion, 4);
		$platformVersionMajor = $versionParts[0];
		$platformVersionMinor = $platformVersionMajor . '.' . $versionParts[1];
		//$platformVersionNormal = $platformVersionMinor . '.' . $versionParts[2];
		//$platformVersionFull   = (count($versionParts) > 3) ? $platformVersionNormal . '.' . $versionParts[3] : $platformVersionNormal;

		$ret = [];

		foreach ($updates as $update)
		{
			// Check each update for platform match
			if (strtolower($update['targetplatform']['name']) != 'joomla')
			{
				continue;
			}

			$targetPlatformVersion = $update['targetplatform']['version'];

			if (!preg_match('/' . $targetPlatformVersion . '/', $platformVersionMinor))
			{
				continue;
			}

			// Get some information from the version number
			$updateVersion     = $update['version'];
			$versionProperties = $this->getVersionProperties($updateVersion, $jVersion);

			if ($versionProperties['upgrade'] == 'none')
			{
				continue;
			}

			// The XML files are ill-maintained. Maybe we already have this update?
			if (!array_key_exists($updateVersion, $ret))
			{
				$ret[$updateVersion] = array_merge($update, $versionProperties);
			}
		}

		return $ret;
	}

	/**
	 * Joomla! has a lousy track record in naming its alpha, beta and release
	 * candidate releases. The convention used seems to be "what the hell the
	 * current package maintainer thinks looks better". This method tries to
	 * figure out what was in the mind of the maintainer and translate the
	 * funky version number to an actual PHP-format version string.
	 *
	 * @param string $version The whatever-format version number
	 *
	 * @return  string  A standard formatted version number
	 */
	public function sanitiseVersion(string $version): string
	{
		$test                   = strtolower($version);
		$alphaQualifierPosition = strpos($test, 'alpha-');
		$betaQualifierPosition  = strpos($test, 'beta-');
		$betaQualifierPosition2 = strpos($test, '-beta');
		$rcQualifierPosition    = strpos($test, 'rc-');
		$rcQualifierPosition2   = strpos($test, '-rc');
		$rcQualifierPosition3   = strpos($test, 'rc');
		$devQualifiedPosition   = strpos($test, 'dev');

		if ($alphaQualifierPosition !== false)
		{
			$betaRevision = substr($test, $alphaQualifierPosition + 6);
			if ($betaRevision === '')
			{
				$betaRevision = 1;
			}
			$test = substr($test, 0, $alphaQualifierPosition) . '.a' . $betaRevision;
		}
		elseif ($betaQualifierPosition !== false)
		{
			$betaRevision = substr($test, $betaQualifierPosition + 5);
			if ($betaRevision === '')
			{
				$betaRevision = 1;
			}
			$test = substr($test, 0, $betaQualifierPosition) . '.b' . $betaRevision;
		}
		elseif ($betaQualifierPosition2 !== false)
		{
			$betaRevision = substr($test, $betaQualifierPosition2 + 5);

			if ($betaRevision === '')
			{
				$betaRevision = 1;
			}

			$test = substr($test, 0, $betaQualifierPosition2) . '.b' . $betaRevision;
		}
		elseif ($rcQualifierPosition !== false)
		{
			$betaRevision = substr($test, $rcQualifierPosition + 5);
			if ($betaRevision === '')
			{
				$betaRevision = 1;
			}
			$test = substr($test, 0, $rcQualifierPosition) . '.rc' . $betaRevision;
		}
		elseif ($rcQualifierPosition2 !== false)
		{
			$betaRevision = substr($test, $rcQualifierPosition2 + 3);

			if ($betaRevision === '')
			{
				$betaRevision = 1;
			}

			$test = substr($test, 0, $rcQualifierPosition2) . '.rc' . $betaRevision;
		}
		elseif ($rcQualifierPosition3 !== false)
		{
			$betaRevision = substr($test, $rcQualifierPosition3 + 5);

			if ($betaRevision === '')
			{
				$betaRevision = 1;
			}

			$test = substr($test, 0, $rcQualifierPosition3) . '.rc' . $betaRevision;
		}
		elseif ($devQualifiedPosition !== false)
		{
			$betaRevision = substr($test, $devQualifiedPosition + 6);
			if ($betaRevision === '')
			{
				$betaRevision = '';
			}
			$test = substr($test, 0, $devQualifiedPosition) . '.dev' . $betaRevision;
		}

		return $test;
	}

	/**
	 * Reloads the list of all updates available for the specified Joomla! version
	 * from the network.
	 *
	 * @param array       $sources  The enabled sources to look into
	 * @param string|null $jVersion The Joomla! version we are checking updates for
	 *
	 * @return   array  A list of updates for the installed, current, lts and sts versions
	 */
	public function getUpdates(array $sources = [], ?string $jVersion = null): array
	{
		// Make sure we have a valid list of sources
		if (empty($sources) || !is_array($sources))
		{
			$sources = [];
		}

		$defaultSources = ['lts' => true, 'sts' => true, 'test' => true, 'custom' => ''];

		$sources = array_merge($defaultSources, $sources);

		// Use the current JVERSION if none is specified
		if (empty($jVersion))
		{
			$jVersion = JVERSION;
		}

		// Get the current branch' min/max versions
		$versionParts      = explode('.', $jVersion, 4);
		$currentMinVersion = $versionParts[0] . '.' . $versionParts[1];
		$currentMaxVersion = $versionParts[0] . '.' . $versionParts[1] . '.9999';


		// Retrieve all updates
		$allUpdates = [];

		foreach ($sources as $source => $value)
		{
			if (($value === false) || empty($value))
			{
				continue;
			}

			switch ($source)
			{
				default:
				case 'lts':
					$url = self::$lts_url;
					break;

				case 'sts':
					$url = self::$sts_url;
					break;

				case 'test':
					$url = self::$test_url;
					break;

				case 'custom':
					$url = $value;
					break;
			}

			$url = $this->getUpdateSourceFromCollection($url, $jVersion);

			if (!empty($url))
			{
				$updates = $this->getUpdatesFromExtension($url);

				if (!empty($updates))
				{
					$applicableUpdates = $this->filterApplicableUpdates($updates, $jVersion);

					if (!empty($applicableUpdates))
					{
						$allUpdates = array_merge($allUpdates, $applicableUpdates);
					}
				}
			}
		}

		$ret = [
			// Currently installed version (used to reinstall, if available)
			'installed' => [
				'version' => '',
				'package' => '',
				'infourl' => '',
			],
			// Current branch
			'current'   => [
				'version' => '',
				'package' => '',
				'infourl' => '',
			],
			// Upgrade to STS release
			'sts'       => [
				'version' => '',
				'package' => '',
				'infourl' => '',
			],
			// Upgrade to LTS release
			'lts'       => [
				'version' => '',
				'package' => '',
				'infourl' => '',
			],
			// Upgrade to LTS release
			'test'      => [
				'version' => '',
				'package' => '',
				'infourl' => '',
			],
		];

		foreach ($allUpdates as $update)
		{
			$sections = [];

			if ($update['upgrade'] == 'current')
			{
				$sections[0] = 'installed';
			}
			elseif (version_compare($update['version'], $currentMinVersion, 'ge') && version_compare($update['version'], $currentMaxVersion, 'le'))
			{
				$sections[0] = 'current';
			}
			else
			{
				$sections[0] = '';
			}

			$sections[1] = $update['lts'] ? 'lts' : 'sts';

			if ($update['testing'])
			{
				$sections = ['test'];
			}

			foreach ($sections as $section)
			{
				if (empty($section))
				{
					continue;
				}

				$existingVersionForSection = $ret[$section]['version'];

				if (empty($existingVersionForSection))
				{
					$existingVersionForSection = '0.0.0';
				}

				if (version_compare($update['version'], $existingVersionForSection, 'ge'))
				{
					$ret[$section]['version'] = $update['version'];
					$ret[$section]['package'] = $update['downloads'][0]['url'];
					$ret[$section]['infourl'] = $update['infourl']['url'];
				}
			}
		}

		// Catch the case when the latest current branch version is the installed version (up to date site)
		if (empty($ret['current']['version']) && !empty($ret['installed']['version']))
		{
			$ret['current'] = $ret['installed'];
		}

		return $ret;
	}
}
