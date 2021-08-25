<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Date\Date as JoomlaDate;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Installer\Installer as JoomlaInstaller;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Table\Extension;

if (class_exists('file_fof40InstallerScript', false))
{
	return;
}

/**
 * Class file_fof40InstallerScript
 *
 * @noinspection PhpIllegalPsrClassPathInspection
 */
class file_fof40InstallerScript
{
	public $removeFiles;
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
	protected $maximumJoomlaVersion = '4.0.999';

	/**
	 * The name of the subdirectory under JPATH_LIBRARIES where this version of FOF is installed.
	 *
	 * @var   string
	 */
	protected $libraryFolder = 'fof40';

	/**
	 * Obsolete files and folders to remove.
	 *
	 * This is used when we refactor code. Some files inevitably become obsolete and need to be removed.
	 *
	 * All files and folders are relative to the library's root (JPATH_LIBRARIES . '/' . $this->libraryFolder).
	 *
	 * @var   array
	 */
	protected $removeFilesAllVersions = [
		'files'   => [
		],
		'folders' => [
		],
	];

	/**
	 * Joomla! pre-flight event. This runs before Joomla! installs or updates the component. This is our last chance to
	 * tell Joomla! if it should abort the installation.
	 *
	 * @param   string           $type    Installation type (install, update, discover_install)
	 * @param   JoomlaInstaller  $parent  Parent object
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

				Log::add($msg, Log::WARNING, 'jerror');

				return false;
			}
		}

		// Check the minimum Joomla! version
		if (!empty($this->minimumJoomlaVersion) && !version_compare(JVERSION, $this->minimumJoomlaVersion, 'ge'))
		{
			$jVersion = JVERSION;
			$msg      = "<p>You need Joomla! $this->minimumJoomlaVersion or later to install this package but you only have $jVersion installed.</p>";

			Log::add($msg, Log::WARNING, 'jerror');

			return false;
		}

		// Check the maximum Joomla! version
		if (!empty($this->maximumJoomlaVersion) && !version_compare(JVERSION, $this->maximumJoomlaVersion, 'le'))
		{
			$jVersion = JVERSION;
			$msg      = "<p>You need Joomla! $this->maximumJoomlaVersion or earlier to install this package but you have $jVersion installed</p>";

			Log::add($msg, Log::WARNING, 'jerror');

			return false;
		}

		// In case of an update, discovery etc I need to check if I am an update
		if (($type != 'install') && !$this->amIAnUpdate($parent))
		{
			$msg = "<p>You have a newer version of FOF installed. If you want to downgrade please uninstall FOF and install the older version.</p>";

			if (defined('AKEEBA_PACKAGE_INSTALLING'))
			{
				$msg = "<p>Your site has a newer version of FOF 3 than the one bundled with this package. Please note that <strong>you can safely ignore the “Custom install routine failure” message</strong> below. It is not a real error; it is an expected message which is always printed by Joomla! in this case and which cannot be suppressed.</p>";
			}

			Log::add($msg, Log::WARNING, 'jerror');

			return false;
		}

		return true;
	}

	/**
	 * Runs after install, update or discover_update. In other words, it executes after Joomla! has finished installing
	 * or updating your component. This is the last chance you've got to perform any additional installations, clean-up,
	 * database updates and similar housekeeping functions.
	 *
	 * @param   string            $type    install, update or discover_update
	 * @param   InstallerAdapter  $parent  Parent object
	 *
	 * @throws  Exception
	 */
	public function postflight($type, $parent)
	{
		// Do not run on uninstall.
		if ($type === 'uninstall')
		{
			return;
		}

		// Remove obsolete files and folders
		$this->removeFilesAndFolders($this->removeFiles);

		if ($type == 'update')
		{
			$this->bugfixFilesNotCopiedOnUpdate($parent);
		}

		$this->loadFOF40();

		if (!defined('FOF40_INCLUDED'))
		{
			return;
		}

		// Install or update database
		$db = JoomlaFactory::getDbo();

		/** @var JoomlaInstaller $grandpa */
		$grandpa   = $parent->getParent();
		$src       = $grandpa->getPath('source');
		$sqlSource = $src . '/fof/sql';

		// If we have an uppercase db prefix we can expect the database update to fail because we cannot detect reliably
		// the existence of database tables. See https://github.com/joomla/joomla-cms/issues/10928#issuecomment-228549658
		$prefix  = $db->getPrefix();
		$canFail = preg_match('/[A-Z]/', $prefix);

		try
		{
			$dbInstaller = new FOF40\Database\Installer($db, $sqlSource);
			$dbInstaller->updateSchema();
		}
		catch (\Exception $e)
		{
			if (!$canFail)
			{
				throw $e;
			}
		}

		// Since we're adding common table, I have to nuke the installer cache, otherwise checks on their existence would fail
		$dbInstaller->nukeCache();

		// Clear the FOF cache
		$fakeController = \FOF40\Container\Container::getInstance('com_FOOBAR');
		$fakeController->platform->clearCache();

		// Clear op-code caches
		$this->clearOpcodeCaches();
	}

	/**
	 * Runs on uninstallation
	 *
	 * @param   InstallerAdapter  $parent  The parent object
	 *
	 * @throws  RuntimeException  If the uninstallation is not allowed
	 */
	public function uninstall($parent)
	{
		// Check dependencies on FOF
		$dependencyCount = count($this->getDependencies('fof40'));

		if ($dependencyCount !== 0)
		{
			$msg = "<p>You have $dependencyCount extension(s) depending on this version of FOF. The package cannot be uninstalled unless these extensions are uninstalled first.</p>";

			Log::add($msg, Log::WARNING, 'jerror');

			throw new RuntimeException($msg, 500);
		}
	}

	/**
	 * Is this package an update to the currently installed FOF? If not (we're a downgrade) we will return false
	 * and prevent the installation from going on.
	 *
	 * @param   InstallerAdapter  $parent  The parent object
	 *
	 * @return  bool  The installation status
	 */
	protected function amIAnUpdate($parent): bool
	{
		/** @var JoomlaInstaller $grandpa */
		$grandpa = $parent->getParent();

		$source = $grandpa->getPath('source');

		$target = JPATH_LIBRARIES . '/fof40';

		// If FOF is not really installed (someone removed the directory instead of uninstalling?) I have to install it.
		if (!Folder::exists($target))
		{
			return true;
		}

		$fofVersion = [];

		if (File::exists($target . '/version.txt'))
		{
			$rawData                 = @file_get_contents($target . '/version.txt');
			$rawData                 = ($rawData === false) ? "0.0.0\n2011-01-01\n" : $rawData;
			$info                    = explode("\n", $rawData);
			$fofVersion['installed'] = [
				'version' => trim($info[0]),
				'date'    => new JoomlaDate(trim($info[1])),
			];
		}
		else
		{
			$fofVersion['installed'] = [
				'version' => '0.0',
				'date'    => new JoomlaDate('2011-01-01'),
			];
		}

		$rawData               = @file_get_contents($source . '/fof/version.txt');
		$rawData               = ($rawData === false) ? "0.0.0\n2011-01-01\n" : $rawData;
		$info                  = explode("\n", $rawData);
		$fofVersion['package'] = [
			'version' => trim($info[0]),
			'date'    => new JoomlaDate(trim($info[1])),
		];

		return $fofVersion['package']['date']->toUNIX() >= $fofVersion['installed']['date']->toUNIX();
	}

	/**
	 * Loads FOF 3.0 if it's not already loaded
	 */
	protected function loadFOF40()
	{
		// Load FOF if not already loaded
		if (!defined('FOF40_INCLUDED'))
		{
			$filePath = JPATH_LIBRARIES . '/fof40/include.php';

			if (defined('FOF40_INCLUDED'))
			{
				return;
			}

			if (!file_exists($filePath))
			{
				return;
			}

			@include_once $filePath;
		}
	}

	/**
	 * Get the dependencies for a package from the #__akeeba_common table
	 *
	 * @param   string  $package  The package
	 *
	 * @return  array  The dependencies
	 */
	protected function getDependencies($package)
	{
		$db = JoomlaFactory::getDbo();

		$query = $db->getQuery(true)
			->select($db->qn('value'))
			->from($db->qn('#__akeeba_common'))
			->where($db->qn('key') . ' = ' . $db->q($package));

		try
		{
			$dependencies = $db->setQuery($query)->loadResult();
			$dependencies = json_decode($dependencies, true);

			if (empty($dependencies))
			{
				$dependencies = [];
			}
		}
		catch (Exception $e)
		{
			$dependencies = [];
		}

		return $dependencies;
	}

	/**
	 * Sets the dependencies for a package into the #__akeeba_common table
	 *
	 * @param   string  $package       The package
	 * @param   array   $dependencies  The dependencies list
	 */
	protected function setDependencies($package, array $dependencies)
	{
		$db = JoomlaFactory::getDbo();

		$query = $db->getQuery(true)
			->delete('#__akeeba_common')
			->where($db->qn('key') . ' = ' . $db->q($package));

		try
		{
			$db->setQuery($query)->execute();
		}
		catch (Exception $e)
		{
			// Do nothing if the old key wasn't found
		}

		$object = (object) [
			'key'   => $package,
			'value' => json_encode($dependencies),
		];

		try
		{
			$db->insertObject('#__akeeba_common', $object, 'key');
		}
		catch (Exception $e)
		{
			// Do nothing if the old key wasn't found
		}
	}

	/**
	 * Adds a package dependency to #__akeeba_common
	 *
	 * @param   string  $package     The package
	 * @param   string  $dependency  The dependency to add
	 */
	protected function addDependency($package, $dependency)
	{
		$dependencies = $this->getDependencies($package);

		if (!in_array($dependency, $dependencies))
		{
			$dependencies[] = $dependency;

			$this->setDependencies($package, $dependencies);
		}
	}

	/**
	 * Removes a package dependency from #__akeeba_common
	 *
	 * @param   string  $package     The package
	 * @param   string  $dependency  The dependency to remove
	 */
	protected function removeDependency($package, $dependency)
	{
		$dependencies = $this->getDependencies($package);

		if (in_array($dependency, $dependencies))
		{
			$index = array_search($dependency, $dependencies);
			unset($dependencies[$index]);

			$this->setDependencies($package, $dependencies);
		}
	}

	/**
	 * Do I have a dependency for a package in #__akeeba_common
	 *
	 * @param   string  $package     The package
	 * @param   string  $dependency  The dependency to check for
	 *
	 * @return bool
	 */
	protected function hasDependency($package, $dependency)
	{
		$dependencies = $this->getDependencies($package);

		return in_array($dependency, $dependencies);
	}

	/**
	 * Try to log a warning / error with Joomla
	 *
	 * @param   string  $message   The message to write to the log
	 * @param   bool    $error     Is this an error? If not, it's a warning. (default: false)
	 * @param   string  $category  Log category, default jerror
	 *
	 * @return  void
	 */
	protected function log($message, $error = false, $category = 'jerror')
	{
		// Just in case...
		if (!class_exists('Log', true))
		{
			return;
		}

		$priority = $error ? Log::ERROR : Log::WARNING;

		try
		{
			Log::add($message, $priority, $category);
		}
		catch (Exception $e)
		{
			// Swallow the exception.
		}
	}

	/**
	 * Fix for Joomla bug: sometimes files are not copied on update.
	 *
	 * We have observed that ever since Joomla! 1.5.5, when Joomla! is performing an extension update some files /
	 * folders are not copied properly. This seems to be a bit random and seems to be more likely to happen the more
	 * added / modified files and folders you have. We are trying to work around it by retrying the copy operation
	 * ourselves WITHOUT going through the manifest, based entirely on the conventions we follow.
	 *
	 * @param   InstallerAdapter  $parent
	 */
	protected function bugfixFilesNotCopiedOnUpdate($parent)
	{
		$source = $parent->getParent()->getPath('source') . '/fof';
		$target = JPATH_LIBRARIES . '/' . $this->libraryFolder;

		$this->recursiveConditionalCopy($source, $target);
	}

	/**
	 * Clear PHP opcode caches
	 *
	 * @return  void
	 */
	protected function clearOpcodeCaches()
	{
		// Always reset the OPcache if it's enabled. Otherwise there's a good chance the server will not know we are
		// replacing .php scripts. This is a major concern since PHP 5.5 included and enabled OPcache by default.
		if (function_exists('opcache_reset'))
		{
			opcache_reset();
		}
		// Also do that for APC cache
		elseif (function_exists('apc_clear_cache'))
		{
			@apc_clear_cache();
		}
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
				$f = sprintf("%s/%s/%s", JPATH_LIBRARIES, $this->libraryFolder, $file);

				if (!is_file($f))
				{
					continue;
				}

				File::delete($f);
			}
		}
		// Remove folders
		if (!isset($removeList['folders']))
		{
			return;
		}

		if (empty($removeList['folders']))
		{
			return;
		}

		foreach ($removeList['folders'] as $folder)
		{
			$f = sprintf("%s/%s/%s", JPATH_LIBRARIES, $this->libraryFolder, $folder);

			if (!@file_exists($f) || !is_dir($f) || is_link($f))
			{
				continue;
			}

			Folder::delete($f);
		}
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
					/** @noinspection PhpComposerExtensionStubsInspection */
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
}
