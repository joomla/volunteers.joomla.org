<?php
/**
 * Akeeba Frontend Framework (FEF)
 *
 * @package   fef
 * @copyright (c) 2017-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 *
 * Created by Crystal Dionysopoulou for Akeeba Ltd, https://www.akeeba.com
 */

defined('_JEXEC') or die();

class file_fefInstallerScript
{
	/**
	 * The minimum PHP version required to install this extension
	 *
	 * @var   string
	 */
	protected $minimumPHPVersion = '5.4.0';

	/**
	 * The minimum Joomla! version required to install this extension
	 *
	 * @var   string
	 */
	protected $minimumJoomlaVersion = '3.4.0';

	/**
	 * The maximum Joomla! version this extension can be installed on
	 *
	 * @var   string
	 */
	protected $maximumJoomlaVersion = '4.0.99';

	/**
	 * Joomla! pre-flight event. This runs before Joomla! installs or updates the component. This is our last chance to
	 * tell Joomla! if it should abort the installation.
	 *
	 * @param   string     $type   Installation type (install, update, discover_install)
	 * @param   JInstaller $parent Parent object
	 *
	 * @return  boolean  True to let the installation proceed, false to halt the installation
	 */
	public function preflight($type, $parent)
	{
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
			$msg = "<p>You need Joomla! $this->minimumJoomlaVersion or later to install this package but you only have $jVersion installed.</p>";

			JLog::add($msg, JLog::WARNING, 'jerror');

			return false;
		}

		// Check the maximum Joomla! version
		if (!empty($this->maximumJoomlaVersion) && !version_compare(JVERSION, $this->maximumJoomlaVersion, 'le'))
		{
			$jVersion = JVERSION;
			$msg = "<p>You need Joomla! $this->maximumJoomlaVersion or earlier to install this package but you have $jVersion installed</p>";

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
					// Files replaced with minified versions in 1.0.8
					'js/darkmode.js',
					'js/dropdown.js',
					'js/menu.js',
					'js/tabs.js',
				],
				'folders' => [
				],
			];

			// We need this trick to prevent the Akeeba font being removed on case-insensitive Windows and macOS filesystems
			$phpOS = strtoupper(PHP_OS);

			if (!in_array(substr($phpOS, 0, 3), ['MAC', 'WIN']))
			{
				// The beta had this folder uppercase, then we moved it to lowercase
				$removeFiles['folders'][] = 'media/fef/fonts/Akeeba';
			}

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
	 * @param   string                $type   install, update or discover_update
	 * @param   JInstallerAdapterFile $parent Parent object
	 *
	 * @throws  Exception
	 */
	public function postflight($type, JInstallerAdapterFile $parent)
	{
		// Remove the obsolete dependency to FOF
		$isFOFInstalled = @is_dir(JPATH_LIBRARIES . '/fof30');

		if ($isFOFInstalled)
		{
			// Remove self from FOF 3.0 dependencies
			$this->removeDependency('fof30', 'file_fef');
		}
	}

	/**
	 * Runs on uninstallation
	 *
	 * @param   JInstallerAdapterFile $parent The parent object
	 *
	 * @throws  RuntimeException  If the uninstallation is not allowed
	 */
	public function uninstall($parent)
	{
		// Check dependencies on FEF
		$dependencyCount = count($this->getDependencies('file_fef'));

		if ($dependencyCount)
		{
			$msg = "<p>You have $dependencyCount extension(s) depending on Akeeba Frontend Framework. The package cannot be uninstalled unless these extensions are uninstalled first.</p>";

			JLog::add($msg, JLog::WARNING, 'jerror');

			throw new RuntimeException($msg, 500);
		}

		JLoader::import('joomla.filesystem.folder');
		JFolder::delete(JPATH_SITE . '/media/fef');
	}

	/**
	 * Removes obsolete files and folders
	 *
	 * @param   array $removeList The files and directories to remove
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

				JFile::delete($f);
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

				JFolder::delete($f);
			}
		}
	}

	/**
	 * Is this package an update to the currently installed FEF? If not (we're a downgrade) we will return false
	 * and prevent the installation from going on.
	 *
	 * @param   \JInstallerAdapterFile $parent The parent object
	 *
	 * @return  bool  Am I an update to an existing version>
	 */
	protected function amIAnUpdate($parent)
	{
		/** @var JInstaller $grandpa */
		$grandpa = $parent->getParent();

		$source = $grandpa->getPath('source');

		$target = JPATH_ROOT . '/media/fef';

		if (!JFolder::exists($source))
		{
			// WTF? I can't find myself. I can't install anything.
			return false;
		}

		// If FEF is not really installed (someone removed the directory instead of uninstalling?) I have to install it.
		if (!JFolder::exists($target))
		{
			return true;
		}

		$fefVersion = array();

		if (JFile::exists($target . '/version.txt'))
		{
			$rawData = @file_get_contents($target . '/version.txt');
			$rawData = ($rawData === false) ? "0.0.0\n2011-01-01\n" : $rawData;
			$info = explode("\n", $rawData);
			$fefVersion['installed'] = array(
				'version' => trim($info[0]),
				'date'    => new JDate(trim($info[1]))
			);
		}
		else
		{
			$fefVersion['installed'] = array(
				'version' => '0.0',
				'date'    => new JDate('2011-01-01')
			);
		}

		$rawData = @file_get_contents($source . '/version.txt');
		$rawData = ($rawData === false) ? "0.0.0\n2011-01-01\n" : $rawData;
		$info = explode("\n", $rawData);
		$fefVersion['package'] = array(
			'version' => trim($info[0]),
			'date'    => new JDate(trim($info[1]))
		);

		$haveToInstallFEF = $fefVersion['package']['date']->toUNIX() >= $fefVersion['installed']['date']->toUNIX();

		return $haveToInstallFEF;
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
		$db = JFactory::getDbo();

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
				$dependencies = array();
			}
		}
		catch (Exception $e)
		{
			$dependencies = array();
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
		$db = JFactory::getDbo();

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

		$object = (object)array(
			'key' => $package,
			'value' => json_encode($dependencies)
		);

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
}
