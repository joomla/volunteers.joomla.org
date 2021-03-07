<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Platform\Base;

defined('_JEXEC') || die;

use Exception;
use FOF40\Container\Container;
use FOF40\Input\Input;
use FOF40\Platform\PlatformInterface;
use Joomla\CMS\Document\Document;
use Joomla\CMS\User\User;

/**
 * Abstract implementation of the Platform integration
 *
 * @package FOF40\Platform\Base
 */
abstract class Platform implements PlatformInterface
{
	/** @var  Container  The component container */
	protected $container;


	/** @var  bool  Are plugins allowed to run in CLI mode? */
	protected $allowPluginsInCli = false;

	/**
	 * Public constructor.
	 *
	 * @param   Container  $c  The component container
	 */
	public function __construct(Container $c)
	{
		$this->container = $c;
	}

	/**
	 * Returns the base (root) directories for a given component, i.e the application
	 * which is running inside our main application (CMS, web app).
	 *
	 * The return is a table with the following keys:
	 * * main    The normal location of component files. For a back-end Joomla!
	 *          component this is the administrator/components/com_example
	 *          directory.
	 * * alt    The alternate location of component files. For a back-end
	 *          Joomla! component this is the front-end directory, e.g.
	 *          components/com_example
	 * * site    The location of the component files serving the public part of
	 *          the application.
	 * * admin    The location of the component files serving the administrative
	 *          part of the application.
	 *
	 * All paths MUST be absolute. All four paths MAY be the same if the
	 * platform doesn't make a distinction between public and private parts,
	 * or when the component does not provide both a public and private part.
	 * All of the directories MUST be defined and non-empty.
	 *
	 * @param   string  $component  The name of the component. For Joomla! this
	 *                              is something like "com_example"
	 *
	 * @return  array  A hash array with keys main, alt, site and admin.
	 */
	public function getComponentBaseDirs(string $component): array
	{
		return [
			'main'  => '',
			'alt'   => '',
			'site'  => '',
			'admin' => '',
		];
	}

	/**
	 * Returns the application's template name
	 *
	 * @param   null|array  $params  An optional associative array of configuration settings
	 *
	 * @return  string  The template name. System is the fallback.
	 */
	public function getTemplate(?array $params = null): string
	{
		return 'system';
	}

	/**
	 * Get application-specific suffixes to use with template paths. This allows
	 * you to look for view template overrides based on the application version.
	 *
	 * @return  array  A plain array of suffixes to try in template names
	 */
	public function getTemplateSuffixes(): array
	{
		return [];
	}

	/**
	 * Return the absolute path to the application's template overrides
	 * directory for a specific component. We will use it to look for template
	 * files instead of the regular component directories. If the application
	 * does not have such a thing as template overrides return an empty string.
	 *
	 * @param   string  $component  The name of the component for which to fetch the overrides
	 * @param   bool    $absolute   Should I return an absolute or relative path?
	 *
	 * @return  string  The path to the template overrides directory
	 */
	public function getTemplateOverridePath(string $component, bool $absolute = true): string
	{
		return '';
	}

	/**
	 * Load the translation files for a given component.
	 *
	 * @param   string  $component  The name of the component. For Joomla! this
	 *                              is something like "com_example"
	 *
	 * @return  void
	 */
	public function loadTranslations(string $component): void
	{
	}

	/**
	 * By default FOF will only use the Controller's onBefore* methods to
	 * perform user authorisation. In some cases, like the Joomla! back-end,
	 * you also need to perform component-wide user authorisation in the
	 * Dispatcher. This method MUST implement this authorisation check. If you
	 * do not need this in your platform, please always return true.
	 *
	 * @param   string  $component  The name of the component.
	 *
	 * @return  bool  True to allow loading the component, false to halt loading
	 */
	public function authorizeAdmin(string $component): bool
	{
		return true;
	}

	/**
	 * Returns a user object.
	 *
	 * @param   integer  $id  The user ID to load. Skip or use null to retrieve
	 *                        the object for the currently logged in user.
	 *
	 * @return  User  The User object for the specified user
	 */
	public function getUser(?int $id = null): User
	{
		return new User();
	}

	/**
	 * Returns the Document object which handles this component's response. You
	 * may also return null and FOF will a. try to figure out the output type by
	 * examining the "format" input parameter (or fall back to "html") and b.
	 * FOF will not attempt to load CSS and Javascript files (as it doesn't make
	 * sense if there's no Document to handle them).
	 *
	 * @return  Document|null
	 */
	public function getDocument(): ?Document
	{
		return null;
	}


	/**
	 * This method will try retrieving a variable from the request (input) data.
	 * If it doesn't exist it will be loaded from the user state, typically
	 * stored in the session. If it doesn't exist there either, the $default
	 * value will be used. If $setUserState is set to true, the retrieved
	 * variable will be stored in the user session.
	 *
	 * @param   string  $key           The user state key for the variable
	 * @param   string  $request       The request variable name for the variable
	 * @param   Input   $input         The Input object with the request (input) data
	 * @param   mixed   $default       The default value. Default: null
	 * @param   string  $type          The filter type for the variable data. Default: none (no filtering)
	 * @param   bool    $setUserState  Should I set the user state with the fetched value?
	 *
	 * @return  mixed  The value of the variable
	 */
	public function getUserStateFromRequest(string $key, string $request, Input $input, $default = null, string $type = 'none', bool $setUserState = true)
	{
		return $input->get($request, $default, $type);
	}

	/**
	 * Load plugins of a specific type. Obviously this seems to only be required
	 * in the Joomla! CMS itself.
	 *
	 * @param   string  $type  The type of the plugins to be loaded
	 *
	 * @return  void
	 */
	public function importPlugin(string $type): void
	{
	}

	/**
	 * Execute plugins (system-level triggers) and fetch back an array with
	 * their return values.
	 *
	 * @param   string  $event  The event (trigger) name, e.g. onBeforeScratchMyEar
	 * @param   array   $data   A hash array of data sent to the plugins as part of the trigger
	 *
	 * @return  array  A simple array containing the results of the plugins triggered
	 */
	public function runPlugins(string $event, array $data = []): array
	{
		return [];
	}

	/**
	 * Perform an ACL check. Please note that FOF uses by default the Joomla!
	 * CMS convention for ACL privileges, e.g core.edit for the edit privilege.
	 * If your platform uses different conventions you'll have to override the
	 * FOF defaults using fof.xml or by specialising the controller.
	 *
	 * @param   string       $action     The ACL privilege to check, e.g. core.edit
	 * @param   string|null  $assetname  The asset name to check, typically the component's name
	 *
	 * @return  bool  True if the user is allowed this action
	 */
	public function authorise(string $action, ?string $assetname = null): bool
	{
		return true;
	}

	/**
	 * Is this the administrative section of the component?
	 *
	 * @return  boolean
	 */
	public function isBackend(): bool
	{
		return true;
	}

	/**
	 * Is this the public section of the component?
	 *
	 * @param   bool  $strict  True to only confirm if we're under the 'site' client. False to confirm if we're under
	 *                         either 'site' or 'api' client (both are front-end access). The default is false which
	 *                         causes the method to return true when the application is either 'client' (HTML frontend)
	 *                         or 'api' (JSON frontend).
	 *
	 * @return  bool
	 */
	public function isFrontend(bool $strict = false): bool
	{
		return true;
	}

	/**
	 * Is this a component running in a CLI application?
	 *
	 * @return  bool
	 */
	public function isCli(): bool
	{
		return true;
	}

	/**
	 * Is this a component running under the API application?
	 *
	 * @return bool
	 */
	public function isApi(): bool
	{
		return true;
	}

	/**
	 * Saves something to the cache. This is supposed to be used for system-wide
	 * FOF data, not application data.
	 *
	 * @param   string  $key      The key of the data to save
	 * @param   string  $content  The actual data to save
	 *
	 * @return  bool  True on success
	 */
	public function setCache(string $key, string $content): bool
	{
		return false;
	}

	/**
	 * Retrieves data from the cache. This is supposed to be used for system-side
	 * FOF data, not application data.
	 *
	 * @param   string       $key      The key of the data to retrieve
	 * @param   string|null  $default  The default value to return if the key is not found or the cache is not populated
	 *
	 * @return  string|null  The cached value
	 */
	public function getCache(string $key, ?string $default = null): ?string
	{
		return false;
	}

	/**
	 * Is the global FOF cache enabled?
	 *
	 * @return  bool
	 */
	public function isGlobalFOFCacheEnabled(): bool
	{
		return true;
	}

	/**
	 * Clears the cache of system-wide FOF data. You are supposed to call this in
	 * your components' installation script post-installation and post-upgrade
	 * methods or whenever you are modifying the structure of database tables
	 * accessed by FOF. Please note that FOF's cache never expires and is not
	 * purged by Joomla!. You MUST use this method to manually purge the cache.
	 *
	 * @return  bool  True on success
	 */
	public function clearCache(): bool
	{
		return false;
	}

	/**
	 * logs in a user
	 *
	 * @param   array  $authInfo  Authentication information
	 *
	 * @return  bool  True on success
	 */
	public function loginUser(array $authInfo): bool
	{
		return true;
	}

	/**
	 * logs out a user
	 *
	 * @return  bool  True on success
	 */
	public function logoutUser(): bool
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
	public function logDeprecated(string $message): void
	{
		// The default implementation does nothing. Override this in your platform classes.
	}

	/** @inheritDoc */
	public function logUserAction($title, string $logText, string $extension, User $user = null): void
	{
		// The default implementation does nothing. Override this in your platform classes.
	}

	/**
	 * Returns the version number string of the CMS/application we're running in
	 *
	 * @return  string
	 *
	 * @since  2.1.2
	 */
	public function getPlatformVersion(): string
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
	public function showErrorPage(Exception $exception): void
	{
		throw $exception;
	}

	/**
	 * Are plugins allowed to run in CLI mode?
	 *
	 * @return  bool
	 */
	public function isAllowPluginsInCli(): bool
	{
		return $this->allowPluginsInCli;
	}

	/**
	 * Set whether plugins are allowed to run in CLI mode
	 *
	 * @param   bool  $allowPluginsInCli
	 */
	public function setAllowPluginsInCli(bool $allowPluginsInCli): void
	{
		$this->allowPluginsInCli = $allowPluginsInCli;
	}
}
