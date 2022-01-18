<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace FOF40\Platform;

defined('_JEXEC') || die;

use DateTimeZone;
use Exception;
use FOF40\Container\Container;
use FOF40\Date\Date;
use FOF40\Input\Input;
use JDatabaseDriver;
use Joomla\CMS\Document\Document;
use Joomla\CMS\Language\Language;
use Joomla\CMS\User\User;
use Joomla\Registry\Registry;
use JsonSerializable;

/**
 * Part of the FOF Platform Abstraction Layer. It implements everything that
 * depends on the platform FOF is running under, e.g. the Joomla! CMS front-end,
 * the Joomla! CMS back-end, a CLI Joomla! Platform app, a bespoke Joomla!
 * Platform / Framework web application and so on.
 */
interface PlatformInterface
{
	/**
	 * Public constructor.
	 *
	 * @param   Container  $c  The component container
	 */
	public function __construct(Container $c);

	/**
	 * Checks if the current script is run inside a valid CMS execution
	 *
	 * @return bool
	 */
	public function checkExecution(): bool;

	/**
	 * Raises an error, using the logic requested by the CMS (PHP Exception or dedicated class)
	 *
	 * @param   integer  $code
	 * @param   string   $message
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 *
	 * @deprecated 5.0
	 */
	public function raiseError(int $code, string $message): void;

	/**
	 * Returns the version number string of the CMS/application we're running in
	 *
	 * @return  string
	 *
	 * @since  2.1.2
	 */
	public function getPlatformVersion(): string;

	/**
	 * Returns absolute path to directories used by the containing CMS/application.
	 *
	 * The return is a table with the following key:
	 * * root    Path to the site root
	 * * public  Path to the public area of the site
	 * * admin   Path to the administrative area of the site
	 * * tmp     Path to the temp directory
	 * * log     Path to the log directory
	 *
	 * @return  array  A hash array with keys root, public, admin, tmp and log.
	 */
	public function getPlatformBaseDirs(): array;

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
	public function getComponentBaseDirs(string $component): array;

	/**
	 * Returns the application's template name
	 *
	 * @param   null|array  $params  An optional associative array of configuration settings
	 *
	 * @return  string  The template name. "system" is the fallback.
	 */
	public function getTemplate(?array $params = null): string;

	/**
	 * Get application-specific suffixes to use with template paths. This allows
	 * you to look for view template overrides based on the application version.
	 *
	 * @return  array  A plain array of suffixes to try in template names
	 */
	public function getTemplateSuffixes(): array;

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
	public function getTemplateOverridePath(string $component, bool $absolute = true): string;

	/**
	 * Load the translation files for a given component.
	 *
	 * @param   string  $component  The name of the component, e.g. "com_example"
	 *
	 * @return  void
	 */
	public function loadTranslations(string $component): void;

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
	public function authorizeAdmin(string $component): bool;

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
	public function getUserStateFromRequest(string $key, string $request, Input $input, $default = null, string $type = 'none', bool $setUserState = true);

	/**
	 * Load plugins of a specific type. Obviously this seems to only be required
	 * in the Joomla! CMS itself.
	 *
	 * @param   string  $type  The type of the plugins to be loaded
	 *
	 * @return  void
	 */
	public function importPlugin(string $type): void;

	/**
	 * Execute plugins (system-level triggers) and fetch back an array with
	 * their return values.
	 *
	 * @param   string  $event  The event (trigger) name, e.g. onBeforeScratchMyEar
	 * @param   array   $data   A hash array of data sent to the plugins as part of the trigger
	 *
	 * @return  array  A simple array containing the results of the plugins triggered
	 */
	public function runPlugins(string $event, array $data = []): array;

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
	public function authorise(string $action, ?string $assetname = null): bool;

	/**
	 * Returns a user object.
	 *
	 * @param   integer  $id  The user ID to load. Skip or use null to retrieve
	 *                        the object for the currently logged in user.
	 *
	 * @return  User  The User object for the specified user
	 */
	public function getUser(?int $id = null): User;

	/**
	 * Returns the Document object which handles this component's response. You
	 * may also return null and FOF will a. try to figure out the output type by
	 * examining the "format" input parameter (or fall back to "html") and b.
	 * FOF will not attempt to load CSS and Javascript files (as it doesn't make
	 * sense if there's no Document to handle them).
	 *
	 * @return  Document|null
	 */
	public function getDocument(): ?Document;

	/**
	 * Returns an object to handle dates
	 *
	 * @param   mixed                     $time      The initial time
	 * @param   DateTimeZone|string|null  $tzOffset  The timezone offset
	 * @param   bool                      $locale    Should I try to load a specific class for current language?
	 *
	 * @return  Date object
	 */
	public function getDate(?string $time = 'now', $tzOffset = null, $locale = true): Date;

	/**
	 * Return the Language instance of the CMS/application
	 *
	 * @return Language
	 */
	public function getLanguage(): Language;

	/**
	 * Returns the database driver object of the CMS/application
	 *
	 * @return JDatabaseDriver
	 */
	public function getDbo(): JDatabaseDriver;

	/**
	 * Is this the administrative section of the component?
	 *
	 * @return  bool
	 */
	public function isBackend(): bool;

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
	public function isFrontend(bool $strict = false): bool;

	/**
	 * Is this a component running in a CLI application?
	 *
	 * @return  bool
	 */
	public function isCli(): bool;

	/**
	 * Is this a component running in an API application?
	 *
	 * @return  bool
	 */
	public function isApi(): bool;

	/**
	 * Saves something to the cache. This is supposed to be used for system-wide
	 * FOF data, not application data.
	 *
	 * @param   string  $key      The key of the data to save
	 * @param   string  $content  The actual data to save
	 *
	 * @return  bool  True on success
	 */
	public function setCache(string $key, string $content): bool;

	/**
	 * Retrieves data from the cache. This is supposed to be used for system-side
	 * FOF data, not application data.
	 *
	 * @param   string       $key      The key of the data to retrieve
	 * @param   string|null  $default  The default value to return if the key is not found or the cache is not populated
	 *
	 * @return  string|null  The cached value
	 */
	public function getCache(string $key, ?string $default = null): ?string;

	/**
	 * Clears the cache of system-wide FOF data. You are supposed to call this in
	 * your components' installation script post-installation and post-upgrade
	 * methods or whenever you are modifying the structure of database tables
	 * accessed by FOF. Please note that FOF's cache never expires and is not
	 * purged by Joomla!. You MUST use this method to manually purge the cache.
	 *
	 * @return  bool  True on success
	 */
	public function clearCache(): bool;

	/**
	 * Returns an object that holds the configuration of the current site.
	 *
	 * @return  Registry
	 */
	public function getConfig(): Registry;

	/**
	 * Is the global FOF cache enabled?
	 *
	 * @return  bool
	 */
	public function isGlobalFOFCacheEnabled(): bool;

	/**
	 * logs in a user
	 *
	 * @param   array  $authInfo  Authentication information
	 *
	 * @return  bool  True on success
	 */
	public function loginUser(array $authInfo): bool;

	/**
	 * logs out a user
	 *
	 * @return  bool  True on success
	 */
	public function logoutUser(): bool;

	/**
	 * Add a log file for FOF
	 *
	 * @param   string  $file
	 *
	 * @return  void
	 */
	public function logAddLogger($file): void;

	/**
	 * Logs a deprecated practice. In Joomla! this results in the $message being output in the
	 * deprecated log file, found in your site's log directory.
	 *
	 * @param   string  $message  The deprecated practice log message
	 *
	 * @return  void
	 */
	public function logDeprecated(string $message): void;

	/**
	 * Adds a message to the application's debug log
	 *
	 * @param   string  $message
	 *
	 * @return  void
	 */
	public function logDebug(string $message): void;

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
	public function logUserAction($title, string $logText, string $extension, User $user = null): void;

	/**
	 * Returns the root URI for the request.
	 *
	 * @param   bool         $pathonly  If false, prepend the scheme, host and port information. Default is false.
	 * @param   string|null  $path      The path
	 *
	 * @return  string  The root URI string.
	 */
	public function URIroot(bool $pathonly = false, ?string $path = null): string;

	/**
	 * Returns the base URI for the request.
	 *
	 * @param   bool  $pathonly  If false, prepend the scheme, host and port information. Default is false.
	 *
	 * @return  string  The base URI string
	 */
	public function URIbase(bool $pathonly = false): string;

	/**
	 * Method to set a response header.  If the replace flag is set then all headers
	 * with the given name will be replaced by the new one (only if the current platform supports header caching)
	 *
	 * @param   string  $name     The name of the header to set.
	 * @param   string  $value    The value of the header to set.
	 * @param   bool    $replace  True to replace any headers with the same name.
	 *
	 * @return  void
	 */
	public function setHeader(string $name, string $value, bool $replace = false): void;

	/**
	 * In platforms that perform header caching, send all headers.
	 *
	 * @return  void
	 */
	public function sendHeaders(): void;

	/**
	 * Immediately terminate the containing application's execution
	 *
	 * @param   int  $code  The result code which should be returned by the application
	 *
	 * @return  void
	 */
	public function closeApplication(int $code = 0): void;

	/**
	 * Perform a redirection to a different page, optionally enqueuing a message for the user.
	 *
	 * @param   string  $url     The URL to redirect to
	 * @param   int     $status  (optional) The HTTP redirection status code, default 301
	 * @param   string  $msg     (optional) A message to enqueue
	 * @param   string  $type    (optional) The message type, e.g. 'message' (default), 'warning' or 'error'.
	 *
	 * @return  void
	 */
	public function redirect(string $url, int $status = 301, ?string $msg = null, string $type = 'message'): void;

	/**
	 * Handle an exception in a way that results to an error page.
	 *
	 * @param   Exception  $exception  The exception to handle
	 *
	 * @throws  Exception  Possibly rethrown exception
	 */
	public function showErrorPage(Exception $exception): void;

	/**
	 * Set a variable in the user session
	 *
	 * @param   string  $name       The name of the variable to set
	 * @param   mixed   $value      (optional) The value to set it to, default is null
	 * @param   string  $namespace  (optional) The variable's namespace e.g. the component name. Default: 'default'
	 *
	 * @return  void
	 */
	public function setSessionVar(string $name, $value = null, string $namespace = 'default'): void;

	/**
	 * Get a variable from the user session
	 *
	 * @param   string  $name       The name of the variable to set
	 * @param   mixed   $default    (optional) The default value to return if the variable does not exit, default: null
	 * @param   string  $namespace  (optional) The variable's namespace e.g. the component name. Default: 'default'
	 *
	 * @return  mixed
	 */
	public function getSessionVar(string $name, $default = null, $namespace = 'default');

	/**
	 * Unset a variable from the user session
	 *
	 * @param   string  $name       The name of the variable to unset
	 * @param   string  $namespace  (optional) The variable's namespace e.g. the component name. Default: 'default'
	 *
	 * @return  void
	 */
	public function unsetSessionVar(string $name, string $namespace = 'default'): void;

	/**
	 * Return the session token. Two types of tokens can be returned:
	 *
	 * Session token ($formToken == false): Used for anti-spam protection of forms. This is specific to a session
	 *   object.
	 *
	 * Form token ($formToken == true): A secure hash of the user ID with the session token. Both the session and the
	 *   user are fetched from the application container.
	 *
	 * @param   bool  $formToken  Should I return a form token?
	 * @param   bool  $forceNew   Should I force the creation of a new token?
	 *
	 * @return  mixed
	 */
	public function getToken(bool $formToken = false, bool $forceNew = false): string;

	/**
	 * Are plugins allowed to run in CLI mode?
	 *
	 * @return  bool
	 */
	public function isAllowPluginsInCli(): bool;

	/**
	 * Set whether plugins are allowed to run in CLI mode
	 *
	 * @param   bool  $allowPluginsInCli
	 */
	public function setAllowPluginsInCli(bool $allowPluginsInCli): void;

	/**
	 * Set a script option.
	 *
	 * This allows the backend code to set up configuration options for frontend (JavaScript) code in a way that's safe
	 * for async / deferred scripts. The options are stored in the document's head as an inline JSON document. This
	 * JSON document is then parsed by a JavaScript helper function which makes the options available to the scripts
	 * that consume them.
	 *
	 * @param   string                  $key     The option key
	 * @param   mixed|JsonSerializable  $value   The option value. Must be a scalar or a JSON serializable object
	 * @param   bool                    $merge   Should I merge an array value with existing stored values? Default:
	 *                                           true
	 *
	 * @return  void
	 */
	public function addScriptOptions($key, $value, $merge = true);

	/**
	 * Get a script option, or all of the script options
	 *
	 * @param   string|null  $key  The script option to retrieve. Null for all options.
	 *
	 * @return  array|mixed  Options for given $key, or all script options
	 */
	public function getScriptOptions($key = null);
}
