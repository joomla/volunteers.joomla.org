<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\InstallScript;

defined('_JEXEC') || die;

use Exception;
use FOF40\Database\Installer;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\CMS\Installer\Adapter\PluginAdapter;
use Joomla\CMS\Log\Log;

// In case FOF's autoloader is not present yet, e.g. new installation
if (!class_exists('FOF40\\InstallScript\\BaseInstaller', true))
{
	require_once __DIR__ . '/BaseInstaller.php';
}

/**
 * A helper class which you can use to create plugin installation scripts.
 *
 * Example usage: class PlgSystemExampleInstallerScript extends FOF40\Utils\InstallScript\Module
 *
 * NB: The class name is always Plg<Plugin Folder><Plugin Name>InstallerScript per Joomla's conventions.
 *
 * This namespace contains more classes for creating installation scripts for other kinds of Joomla! extensions as well.
 * Do keep in mind that only components, modules and plugins could have post-installation scripts before Joomla! 3.3.
 */
class Plugin extends BaseInstaller
{
	/**
	 * The plugins's name, e.g. foobar (for plg_system_foobar). Auto-filled from the class name.
	 *
	 * @var   string
	 */
	protected $pluginName = '';

	/**
	 * The plugins's folder, e.g. system (for plg_system_foobar). Auto-filled from the class name.
	 *
	 * @var   string
	 */
	protected $pluginFolder = '';

	/**
	 * The path where the schema XML files are stored. The path is relative to the folder which contains the extension's
	 * files.
	 *
	 * @var string
	 */
	protected $schemaXmlPath = 'sql/xml';

	/**
	 * Plugin installer script constructor.
	 */
	public function __construct()
	{
		// Get the plugin name and folder from the class name (it's always plgFolderPluginInstallerScript) if necessary.
		if (empty($this->pluginFolder) || empty($this->pluginName))
		{
			$class      = get_class($this);
			$words      = preg_replace('/(\s)+/', '_', $class);
			$words      = strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $words));
			$classParts = explode('_', $words);

			if (empty($this->pluginFolder))
			{
				$this->pluginFolder = $classParts[1];
			}

			if (empty($this->pluginName))
			{
				$this->pluginName = $classParts[2];
			}
		}
	}

	/**
	 * Joomla! pre-flight event. This runs before Joomla! installs or updates the component. This is our last chance to
	 * tell Joomla! if it should abort the installation.
	 *
	 * @param   string         $type    Installation type (install, update, discover_install)
	 * @param   PluginAdapter  $parent  Parent object
	 *
	 * @return  boolean  True to let the installation proceed, false to halt the installation
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function preflight(string $type, PluginAdapter $parent): bool
	{
		// Check the minimum PHP version
		if (!$this->checkPHPVersion())
		{
			return false;
		}

		// Check the minimum Joomla! version
		if (!$this->checkJoomlaVersion())
		{
			return false;
		}

		// Clear op-code caches to prevent any cached code issues
		$this->clearOpcodeCaches();

		return true;
	}

	/**
	 * Runs after install, update or discover_update. In other words, it executes after Joomla! has finished installing
	 * or updating your component. This is the last chance you've got to perform any additional installations, clean-up,
	 * database updates and similar housekeeping functions.
	 *
	 * @param   string         $type    install, update or discover_update
	 * @param   PluginAdapter  $parent  Parent object
	 *
	 * @return  void
	 * @throws Exception
	 *
	 */
	public function postflight(string $type, PluginAdapter $parent): void
	{
		// Add ourselves to the list of extensions depending on FOF40
		$dependencyName = $this->getDependencyName();

		$this->addDependency('fof40', $dependencyName);
		$this->removeDependency('fof30', $dependencyName);

		// Install or update database
		$schemaPath = $parent->getParent()->getPath('source') . '/' . $this->schemaXmlPath;

		if (@is_dir($schemaPath))
		{
			$dbInstaller = new Installer(JoomlaFactory::getDbo(), $schemaPath);
			$dbInstaller->updateSchema();
		}

		// Make sure everything is copied properly
		$this->bugfixFilesNotCopiedOnUpdate($parent);

		// Add post-installation messages on Joomla! 3.2 and later
		$this->_applyPostInstallationMessages();

		// Clear the opcode caches again - in case someone accessed the extension while the files were being upgraded.
		$this->clearOpcodeCaches();

		// Finally, see if FOF 3.x is obsolete and remove it.
		// $this->uninstallFOF3IfNecessary();
	}

	/**
	 * Runs on uninstallation
	 *
	 * @param   PluginAdapter  $parent  The parent object
	 */
	public function uninstall(PluginAdapter $parent): void
	{
		// Uninstall database
		$schemaPath = $parent->getParent()->getPath('source') . '/' . $this->schemaXmlPath;

		// Uninstall database
		if (@is_dir($schemaPath))
		{
			$dbInstaller = new Installer(JoomlaFactory::getDbo(), $schemaPath);
			$dbInstaller->removeSchema();
		}

		// Uninstall post-installation messages on Joomla! 3.2 and later
		$this->uninstallPostInstallationMessages();

		// Remove ourselves from the list of extensions depending on FOF40
		$dependencyName = $this->getDependencyName();

		// Remove ourselves from the list of extensions depending of FOF 4
		$this->removeDependency('fof40', $dependencyName);

		// Uninstall FOF 4 if nothing else depends on it
		$this->uninstallFOF4IfNecessary();
	}

	/**
	 * Fix for Joomla bug: sometimes files are not copied on update.
	 *
	 * We have observed that ever since Joomla! 1.5.5, when Joomla! is performing an extension update some files /
	 * folders are not copied properly. This seems to be a bit random and seems to be more likely to happen the more
	 * added / modified files and folders you have. We are trying to work around it by retrying the copy operation
	 * ourselves WITHOUT going through the manifest, based entirely on the conventions we follow for Akeeba Ltd's
	 * extensions.
	 *
	 * @param   PluginAdapter  $parent
	 */
	protected function bugfixFilesNotCopiedOnUpdate(PluginAdapter $parent): void
	{
		Log::add("Joomla! extension update workaround for $this->pluginFolder plugin $this->pluginName", Log::INFO, 'fof4_extension_installation');

		$temporarySource = $parent->getParent()->getPath('source');

		$copyMap = [
			// Plugin files
			$temporarySource               => JPATH_ROOT . '/plugins/' . $this->pluginFolder . '/' . $this->pluginName,
			// Language (always stored in administrator for plugins)
			$temporarySource . '/language' => JPATH_ADMINISTRATOR . '/language',
			// Media files, e.g. /media/plg_system_foobar
			$temporarySource . '/media'    => JPATH_ROOT . '/media/' . $this->getDependencyName(),
		];

		foreach ($copyMap as $source => $target)
		{
			Log::add(__CLASS__ . ":: Conditional copy $source to $target", Log::DEBUG, 'fof4_extension_installation');

			$ignored = [];

			if ($source === $temporarySource)
			{
				$ignored = [
					'index.html', 'index.htm', 'LICENSE.txt', 'license.txt', 'readme.htm', 'readme.html', 'README.md',
					'script.php', 'language', 'media',
				];
			}

			$this->recursiveConditionalCopy($source, $target, $ignored);
		}
	}

	/**
	 * Get the extension name for FOF dependency tracking, e.g. plg_system_foobar
	 *
	 * @return  string
	 */
	protected function getDependencyName(): string
	{
		return 'plg_' . strtolower($this->pluginFolder) . '_' . $this->pluginName;
	}
}
