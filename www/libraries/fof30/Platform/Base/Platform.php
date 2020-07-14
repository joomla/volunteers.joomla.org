<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Platform\Base;

use Exception;
use FOF30\Container\Container;
use FOF30\Input\Input;
use FOF30\Platform\PlatformInterface;
use Joomla\CMS\User\User;

defined('_JEXEC') or die;

/**
 * Abstract implementation of the Platform integration
 *
 * @package FOF30\Platform\Base
 */
abstract class Platform implements PlatformInterface
{
	/** @var  Container  The component container */
	protected $container = null;

	/** @var  bool  Are plugins allowed to run in CLI mode? */
	protected $allowPluginsInCli = false;

	/**
	 * Public constructor.
	 *
	 * @param   \FOF30\Container\Container  $c  The component container
	 */
	public function __construct(Container $c)
	{
		$this->container = $c;
	}

	/**
	 * Returns the base (root) directories for a given component.
	 *
	 * @param   string  $component  The name of the component. For Joomla! this
	 *                              is something like "com_example"
	 *
	 * @see F0FPlatformInterface::getComponentBaseDirs()
	 *
	 * @return  array  A hash array with keys main, alt, site and admin.
	 */
	public function getComponentBaseDirs($component)
	{
		return array(
			'main'	=> '',
			'alt'	=> '',
			'site'	=> '',
			'admin'	=> '',
		);
	}

	/**
	 * Returns the application's template name
	 *
	 * @param   boolean|array  $params  An optional associative array of configuration settings
	 *
	 * @return  string  The template name. System is the fallback.
	 */
	public function getTemplate($params = false)
	{
		return 'system';
	}

	/**
	 * Get application-specific suffixes to use with template paths. This allows
	 * you to look for view template overrides based on the application version.
	 *
	 * @return  array  A plain array of suffixes to try in template names
	 */
	public function getTemplateSuffixes()
	{
		return array();
	}

	/**
	 * Return the absolute path to the application's template overrides
	 * directory for a specific component. We will use it to look for template
	 * files instead of the regular component directories. If the application
	 * does not have such a thing as template overrides return an empty string.
	 *
	 * @param   string   $component  The name of the component for which to fetch the overrides
	 * @param   boolean  $absolute   Should I return an absolute or relative path?
	 *
	 * @return  string  The path to the template overrides directory
	 */
	public function getTemplateOverridePath($component, $absolute = true)
	{
		return '';
	}

	/**
	 * Load the translation files for a given component.
	 *
	 * @param   string  $component  The name of the component. For Joomla! this
	 *                              is something like "com_example"
	 *
	 * @see F0FPlatformInterface::loadTranslations()
	 *
	 * @return  void
	 */
	public function loadTranslations($component)
	{
		return null;
	}

	/**
	 * Authorise access to the component in the back-end.
	 *
	 * @param   string  $component  The name of the component.
	 *
	 * @see F0FPlatformInterface::authorizeAdmin()
	 *
	 * @return  boolean  True to allow loading the component, false to halt loading
	 */
	public function authorizeAdmin($component)
	{
		return true;
	}

	/**
	 * Returns the JUser object for the current user
	 *
	 * @param   integer  $id  The ID of the user to fetch
	 *
	 * @see F0FPlatformInterface::getUser()
	 *
	 * @return  \JDocument
	 */
	public function getUser($id = null)
	{
		return null;
	}

	/**
	 * Returns the JDocument object which handles this component's response.
	 *
	 * @see F0FPlatformInterface::getDocument()
	 *
	 * @return  \JDocument
	 */
	public function getDocument()
	{
		return null;
	}

	/**
	 * This method will try retrieving a variable from the request (input) data.
	 *
	 * @param   string    $key           The user state key for the variable
	 * @param   string    $request       The request variable name for the variable
	 * @param   Input     $input         The Input object with the request (input) data
	 * @param   mixed     $default       The default value. Default: null
	 * @param   string    $type          The filter type for the variable data. Default: none (no filtering)
	 * @param   boolean   $setUserState  Should I set the user state with the fetched value?
	 *
	 * @see F0FPlatformInterface::getUserStateFromRequest()
	 *
	 * @return  mixed  The value of the variable
	 */
	public function getUserStateFromRequest($key, $request, $input, $default = null, $type = 'none', $setUserState = true)
	{
		return $input->get($request, $default, $type);
	}

	/**
	 * Load plugins of a specific type. Obviously this seems to only be required
	 * in the Joomla! CMS.
	 *
	 * @param   string  $type  The type of the plugins to be loaded
	 *
	 * @see F0FPlatformInterface::importPlugin()
	 *
	 * @return void
	 */
	public function importPlugin($type)
	{
	}

	/**
	 * Execute plugins (system-level triggers) and fetch back an array with
	 * their return values.
	 *
	 * @param   string  $event  The event (trigger) name, e.g. onBeforeScratchMyEar
	 * @param   array   $data   A hash array of data sent to the plugins as part of the trigger
	 *
	 * @see F0FPlatformInterface::runPlugins()
	 *
	 * @return  array  A simple array containing the results of the plugins triggered
	 */
	public function runPlugins($event, $data)
	{
		return array();
	}

	/**
	 * Perform an ACL check.
	 *
	 * @param   string  $action     The ACL privilege to check, e.g. core.edit
	 * @param   string  $assetname  The asset name to check, typically the component's name
	 *
	 * @see F0FPlatformInterface::authorise()
	 *
	 * @return  boolean  True if the user is allowed this action
	 */
	public function authorise($action, $assetname)
	{
		return true;
	}

	/**
	 * Is this the administrative section of the component?
	 *
	 * @see F0FPlatformInterface::isBackend()
	 *
	 * @return  boolean
	 */
	public function isBackend()
	{
		return true;
	}

	/**
	 * Is this the public section of the component?
	 *
	 * @see F0FPlatformInterface::isFrontend()
	 *
	 * @return  boolean
	 */
	public function isFrontend()
	{
		return true;
	}

	/**
	 * Is this a component running in a CLI application?
	 *
	 * @see F0FPlatformInterface::isCli()
	 *
	 * @return  boolean
	 */
	public function isCli()
	{
		return true;
	}

	/**
	 * Is AJAX re-ordering supported? This is 100% Joomla!-CMS specific. All
	 * other platforms should return false and never ask why.
	 *
	 * @see F0FPlatformInterface::supportsAjaxOrdering()
	 *
	 * @return  boolean
	 */
	public function supportsAjaxOrdering()
	{
		return true;
	}

	/**
	 * Saves something to the cache. This is supposed to be used for system-wide
	 * F0F data, not application data.
	 *
	 * @param   string  $key      The key of the data to save
	 * @param   string  $content  The actual data to save
	 *
	 * @return  boolean  True on success
	 */
	public function setCache($key, $content)
	{
		return false;
	}

	/**
	 * Retrieves data from the cache. This is supposed to be used for system-side
	 * F0F data, not application data.
	 *
	 * @param   string  $key      The key of the data to retrieve
	 * @param   string  $default  The default value to return if the key is not found or the cache is not populated
	 *
	 * @return  string  The cached value
	 */
	public function getCache($key, $default = null)
	{
		return false;
	}

	/**
	 * Is the global F0F cache enabled?
	 *
	 * @return  boolean
	 */
	public function isGlobalFOFCacheEnabled()
	{
		return true;
	}

	/**
	 * Clears the cache of system-wide F0F data. You are supposed to call this in
	 * your components' installation script post-installation and post-upgrade
	 * methods or whenever you are modifying the structure of database tables
	 * accessed by F0F. Please note that F0F's cache never expires and is not
	 * purged by Joomla!. You MUST use this method to manually purge the cache.
	 *
	 * @return  boolean  True on success
	 */
	public function clearCache()
	{
		return false;
	}

	/**
	 * logs in a user
	 *
	 * @param   array  $authInfo  authentification information
	 *
	 * @return  boolean  True on success
	 */
	public function loginUser($authInfo)
	{
		return true;
	}

	/**
	 * logs out a user
	 *
	 * @return  boolean  True on success
	 */
	public function logoutUser()
	{
		return true;
	}

	/**
	 * Logs a deprecated practice. In Joomla! this results in the $message being output in the
	 * deprecated log file, found in your site's log directory.
	 *
	 * @param   string  $message  The deprecated practice log message
	 *
	 * @return  void
	 */
	public function logDeprecated($message)
	{
		// The default implementation does nothing. Override this in your platform classes.
	}

	/**
	 * Adds a message
	 *
	 * @param   string|array  $title      A title, or an array of additional fields to add to the log entry
	 * @param   string        $logText    The translation key to the log text
	 * @param   string        $extension  The name of the extension logging this entry
	 * @param   User|null     $user       The user the action is being logged for
	 *
	 * @return  void
	 */
	public function logUserAction($title, $logText, $extension, $user = null)
	{
		// The default implementation does nothing. Override this in your platform classes.
	}

	/**
	 * Returns the version number string of the platform, e.g. "4.5.6". If
	 * implementation integrates with a CMS or a versioned foundation (e.g.
	 * a framework) it is advisable to return that version.
	 *
	 * @return  string
	 *
	 * @since  2.1.2
	 */
	public function getPlatformVersion()
	{
		return '';
	}

	/**
	 * Handle an exception in a way that results to an error page.
	 *
	 * @param   Exception  $exception  The exception to handle
	 *
	 * @throws  Exception  Possibly rethrown exception
	 */
	public function showErrorPage(Exception $exception)
	{
		throw $exception;
	}

	/**
	 * Are plugins allowed to run in CLI mode?
	 *
	 * @return  bool
	 */
	public function isAllowPluginsInCli()
	{
		return $this->allowPluginsInCli;
	}

	/**
	 * Set whether plugins are allowed to run in CLI mode
	 *
	 * @param   bool  $allowPluginsInCli
	 */
	public function setAllowPluginsInCli($allowPluginsInCli)
	{
		$this->allowPluginsInCli = $allowPluginsInCli;
	}
}
