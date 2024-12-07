<?php
/**
 * Akeeba Frontend Framework (FEF)
 *
 * @package   fef
 * @copyright (c) 2017-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

use Joomla\CMS\Date\Date as JDate;
use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Installer\Adapter\FileAdapter as JInstallerAdapterFile;
use Joomla\CMS\Installer\Installer as JInstaller;
use Joomla\CMS\Log\Log as JLog;

if (class_exists('file_fefInstallerScript'))
{
	// WTAF?!
	return;
}

/**
 * Akeeba FEF Installation Script
 *
 * @noinspection PhpUnused
 */
class file_fefInstallerScript
{
	/**
	 * The minimum PHP version required to install this extension
	 *
	 * @var   string
	 */
	protected $minimumPHPVersion = '7.2.0';

	/**
	 * The minimum Joomla! version required to install this extension
	 *
	 * @var   string
	 */
	protected $minimumJoomlaVersion = '3.9.0';

	/**
	 * The maximum Joomla! version this extension can be installed on
	 *
	 * @var   string
	 */
	protected $maximumJoomlaVersion = '4.999.999';

	/**
	 * Joomla! pre-flight event. This runs before Joomla! installs or updates the component. This is our last chance to
	 * tell Joomla! if it should abort the installation.
	 *
	 * @param   string                            $type    Installation type (install, update, discover_install)
	 * @param   JInstaller|JInstallerAdapterFile  $parent  Parent object
	 *
	 * @return  boolean  True to let the installation proceed, false to halt the installation
	 */
	public function preflight($type, $parent)
	{
		// Do not run on uninstall.
		if ($type === 'uninstall')
		{
			return true;
		}

		// Check the minimum PHP version
		if (!empty($this->minimumPHPVersion))
		{
			if (defined('PHP_VERSION'))
			{
				$version = PHP_VERSION;
			}
			elseif (function_exists('phpversion'))
			{
				$version = phpversion();
			}
			else
			{
				$version = '5.0.0'; // all bets are off!
			}

			if (!version_compare($version, $this->minimumPHPVersion, 'ge'))
			{
				$msg = "<p>You need PHP $this->minimumPHPVersion or later to install this package but you are currently using PHP  $version</p>";

				JLog::add($msg, JLog::WARNING, 'jerror');

				return false;
			}
		}

		// Check the minimum Joomla! version
		if (!empty($this->minimumJoomlaVersion) && !version_compare(JVERSION, $this->minimumJoomlaVersion, 'ge'))
		{
			$jVersion = JVERSION;
			$msg      = "<p>You need Joomla! $this->minimumJoomlaVersion or later to install this package but you only have $jVersion installed.</p>";

			JLog::add($msg, JLog::WARNING, 'jerror');

			return false;
		}

		// Check the maximum Joomla! version
		if (!empty($this->maximumJoomlaVersion) && !version_compare(JVERSION, $this->maximumJoomlaVersion, 'le'))
		{
			$jVersion = JVERSION;
			$msg = <<< HTML
<h3>FEF is no longer needed on Joomla 5</h3>
<p>
	<strong>Summary: FEF is no longer used on Joomla 5. Please uninstall it.</strong>
</p>
<hr/>
<p>
	Akeeba FEF a.k.a. the Akeeba Front-End Framework was a CSS and JavaScript framework used by Akeeba Ltd with the Joomla 3 versions of our software.
</p>
<p>
	Akeeba Ltd has stopped using the FEF framework for developing extensions. All of our extensions have new, Joomla 4 and later native versions which use the Bootstrap library, included in Joomla itself.
</p>
<p>
	You can no longer install or update FEF on Joomla 5.0 and later (you have {$jVersion}). In fact, you just need to uninstall it.
</p>
HTML;

			JLog::add($msg, JLog::WARNING, 'jerror');

			return false;
		}

		// In case of an update, discovery etc I need to check if I am an update
		if (($type == 'update') && !$this->amIAnUpdate($parent))
		{
			$msg = "<p>You already have a newer version of Akeeba Frontend Framework installed. If you want to downgrade please uninstall Akeeba Frontend Framework and install the older version.</p><p>If you see this message during the installation or update of an Akeeba extension please ignore it <em>and</em> the immediately following “Files Install: Custom install routine failure” message. They are expected but Joomla! won't allow us to prevent them from showing up.</p>";

			JLog::add($msg, JLog::WARNING, 'jerror');

			return false;
		}

		// Delete obsolete font files and folders
		if ($type == 'update')
		{
			// Use pathnames relative to your site's root
			$removeFiles = [
				'files'   => [
					// Non-WOFF fonts are not shipped as of 1.0.1 since all modern browsers we target use WOFF
					'media/fef/fonts/akeeba/Akeeba-Products.eot',
					'media/fef/fonts/akeeba/Akeeba-Products.svg',
					'media/fef/fonts/akeeba/Akeeba-Products.ttf',
					'media/fef/fonts/Ionicon/ionicons.eot',
					'media/fef/fonts/Ionicon/ionicons.svg',
					'media/fef/fonts/Ionicon/ionicons.ttf',
					// Files renamed in 1.0.8
					'css/reset.min.css',
					'css/style.min.css',
					// JavaScript: Irrelevant for Joomla
					'js/darkmode.js',
					'js/darkmode.min.js',
					'js/darkmode.map',
					'js/Darkmode.min.js',
					'js/Darkmode.map',
					'js/menu.js',
					'js/menu.min.js',
					'js/menu.map',
					'js/Menu.min.js',
					'js/Menu.map',
					// JavaScript: Uncompressed and map files
					'js/dropdown.js',
					'js/dropdown.map',
					'js/Dropdown.map',
					'js/loader.js',
					'js/loader.map',
					'js/Loader.map',
					'js/tabs.js',
					'js/tabs.map',
					'js/Tabs.map',
				],
				'folders' => [
				],
			];

			// Remove obsolete files and folders
			$this->removeFilesAndFolders($removeFiles);
		}

		return true;
	}

	/**
	 * Runs after install, update or discover_update. In other words, it executes after Joomla! has finished installing
	 * or updating your component. This is the last chance you've got to perform any additional installations, clean-up,
	 * database updates and similar housekeeping functions.
	 *
	 * @param   string                 $type    install, update or discover_update
	 * @param   JInstallerAdapterFile  $parent  Parent object
	 *
	 * @throws  Exception
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function postflight($type, JInstallerAdapterFile $parent)
	{
		// Do not run on uninstall.
		if ($type === 'uninstall')
		{
			return true;
		}

		// Auto-uninstall this package when it is no longer needed.
		if (($type != 'install') && ($this->countHardcodedDependencies() === 0))
		{
			$this->uninstallSelf($parent);

			return true;
		}

		$this->bugfixFilesNotCopiedOnUpdate($parent);

		return true;
	}

	/**
	 * Runs on uninstallation
	 *
	 * @param   JInstallerAdapterFile  $parent  The parent object
	 *
	 * @throws  RuntimeException  If the uninstallation is not allowed
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function uninstall($parent)
	{
		if (version_compare(JVERSION, '4.1.0', 'ge'))
		{
			return false;
		}

		// Check dependencies on FEF
		$dependencyCount = $this->countHardcodedDependencies();

		if ($dependencyCount)
		{
			$msg = "<p>You have $dependencyCount extension(s) depending on Akeeba Frontend Framework. The package cannot be uninstalled unless these extensions are uninstalled first.</p>";

			JLog::add($msg, JLog::WARNING, 'jerror');

			throw new RuntimeException($msg, 500);
		}

		Folder::delete(JPATH_SITE . '/media/fef');

		return true;
	}

	/**
	 * Removes obsolete files and folders
	 *
	 * @param   array  $removeList  The files and directories to remove
	 */
	protected function removeFilesAndFolders($removeList)
	{
		// Remove files
		if (isset($removeList['files']) && !empty($removeList['files']))
		{
			foreach ($removeList['files'] as $file)
			{
				$f = JPATH_ROOT . '/' . $file;

				if (!is_file($f))
				{
					continue;
				}

				File::delete($f);
			}
		}

		// Remove folders
		if (isset($removeList['folders']) && !empty($removeList['folders']))
		{
			foreach ($removeList['folders'] as $folder)
			{
				$f = JPATH_ROOT . '/' . $folder;

				if (!is_dir($f))
				{
					continue;
				}

				Folder::delete($f);
			}
		}
	}

	/**
	 * Is this package an update to the currently installed FEF? If not (we're a downgrade) we will return false
	 * and prevent the installation from going on.
	 *
	 * @param   JInstallerAdapterFile  $parent  The parent object
	 *
	 * @return  bool  Am I an update to an existing version>
	 */
	protected function amIAnUpdate($parent)
	{
		$grandpa = $parent->getParent();
		$source  = $grandpa->getPath('source');
		$target  = JPATH_ROOT . '/media/fef';

		if (!Folder::exists($source))
		{
			// WTF? I can't find myself. I can't install anything.
			return false;
		}

		// If FEF is not really installed (someone removed the directory instead of uninstalling?) I have to install it.
		if (!Folder::exists($target))
		{
			return true;
		}

		$fefVersion = [];

		if (File::exists($target . '/version.txt'))
		{
			$rawData                 = @file_get_contents($target . '/version.txt');
			$rawData                 = ($rawData === false) ? "0.0.0\n2011-01-01\n" : $rawData;
			$info                    = explode("\n", $rawData);
			$fefVersion['installed'] = [
				'version' => trim($info[0]),
				'date'    => new JDate(trim($info[1])),
			];
		}
		else
		{
			$fefVersion['installed'] = [
				'version' => '0.0',
				'date'    => new JDate('2011-01-01'),
			];
		}

		$rawData               = @file_get_contents($source . '/version.txt');
		$rawData               = ($rawData === false) ? "0.0.0\n2011-01-01\n" : $rawData;
		$info                  = explode("\n", $rawData);
		$fefVersion['package'] = [
			'version' => trim($info[0]),
			'date'    => new JDate(trim($info[1])),
		];

		return $fefVersion['package']['date']->toUNIX() >= $fefVersion['installed']['date']->toUNIX();
	}

	/**
	 * Fix for Joomla bug: sometimes files are not copied on update.
	 *
	 * We have observed that ever since Joomla! 1.5.5, when Joomla! is performing an extension update some files /
	 * folders are not copied properly. This seems to be a bit random and seems to be more likely to happen the more
	 * added / modified files and folders you have. We are trying to work around it by retrying the copy operation
	 * ourselves WITHOUT going through the manifest, based entirely on the conventions we follow.
	 *
	 * @param   \Joomla\CMS\Installer\Adapter\FileAdapter  $parent
	 */
	protected function bugfixFilesNotCopiedOnUpdate($parent)
	{
		$source = $parent->getParent()->getPath('source');
		$target = JPATH_SITE . '/media/fef';

		$this->recursiveConditionalCopy($source, $target);
	}

	/**
	 * Recursively copy a bunch of files, but only if the source and target file have a different size.
	 *
	 * @param   string  $source   Path to copy FROM
	 * @param   string  $dest     Path to copy TO
	 * @param   array   $ignored  List of entries to ignore (first level entries are taken into account)
	 *
	 * @return  void
	 */
	protected function recursiveConditionalCopy($source, $dest, $ignored = [])
	{
		// Make sure source and destination exist
		if (!@is_dir($source))
		{
			return;
		}

		if (!@is_dir($dest))
		{
			if (!@mkdir($dest, 0755))
			{
				Folder::create($dest, 0755);
			}
		}

		if (!@is_dir($dest))
		{
			// Cannot create folder $dest

			return;
		}

		// List the contents of the source folder
		try
		{
			$di = new DirectoryIterator($source);
		}
		catch (Exception $e)
		{
			return;
		}

		// Process each entry
		foreach ($di as $entry)
		{
			// Ignore dot dirs (. and ..)
			if ($entry->isDot())
			{
				continue;
			}

			$sourcePath = $entry->getPathname();
			$fileName   = $entry->getFilename();

			// Do not copy ignored files
			if (!empty($ignored) && in_array($fileName, $ignored))
			{
				continue;
			}

			// If it's a directory do a recursive copy
			if ($entry->isDir())
			{
				$this->recursiveConditionalCopy($sourcePath, $dest . DIRECTORY_SEPARATOR . $fileName);

				continue;
			}

			// If it's a file check if it's missing or identical
			$mustCopy   = false;
			$targetPath = $dest . DIRECTORY_SEPARATOR . $fileName;

			if (!@is_file($targetPath))
			{
				$mustCopy = true;
			}
			else
			{
				$sourceSize = @filesize($sourcePath);
				$targetSize = @filesize($targetPath);

				$mustCopy = $sourceSize != $targetSize;

				if ((substr($targetPath, -4) === '.php') && function_exists('opcache_invalidate'))
				{
					opcache_invalidate($targetPath);
				}
			}

			if (!$mustCopy)
			{
				continue;
			}

			if (!@copy($sourcePath, $targetPath))
			{
				File::copy($sourcePath, $targetPath);
			}
		}
	}

	/**
	 * Count the number of old FOF + FEF based extensions installed on this site
	 *
	 * @return  int
	 */
	private function countHardcodedDependencies()
	{
		// Look for fof.xml in the backend directories of the following components
		$hardcodedDependencies = [
			'com_admintools',
			'com_akeeba',
			'com_ars',
			'com_ats',
			'com_compatibility',
			'com_datacompliance',
			'com_contactus',
			'com_docimport',
			'com_loginguard',
		];

		$count = 0;

		foreach ($hardcodedDependencies as $component)
		{
			$filePath = JPATH_ADMINISTRATOR . '/components/' . $component . '/fof.xml';

			if (@file_exists($filePath))
			{
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Uninstall this package.
	 *
	 * This runs on update when there are no more dependencies left.
	 *
	 * @param  \Joomla\CMS\Installer\Adapter\FileAdapter $adapter
	 *
	 * @return void
	 */
	private function uninstallSelf($adapter)
	{
		$parent = $adapter->getParent();

		if (empty($parent) || !property_exists($parent, 'extension'))
		{
			return;
		}

		if (version_compare(JVERSION, '4.0', 'lt'))
		{
			$db = \Joomla\CMS\Factory::getDbo();
		}
		else
		{
			$db = \Joomla\CMS\Factory::getContainer()->get('DatabaseDriver');
		}

		try
		{
			$query = $db->getQuery(true)
				->select($db->quoteName('extension_id'))
				->from($db->quoteName('#__extensions'))
				->where($db->quoteName('type') . ' = ' . $db->quote('file'))
				->where($db->quoteName('name') . ' = ' . $db->quote('file_fef'));

			$id = $db->setQuery($query)->loadResult();
		}
		catch (Exception $e)
		{
			return;
		}

		if (empty($id))
		{
			return;
		}

		$msg = 'Automatically uninstalling FEF; this package is no longer required on your site.';
		\Joomla\CMS\Log\Log::add($msg, \Joomla\CMS\Log\Log::INFO, 'jerror');

		$parent->uninstall('file', $id);
	}
}
