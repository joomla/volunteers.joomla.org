<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Platform\Joomla;

defined('_JEXEC') || die;

use ActionlogsModelActionlog;
use DateTime;
use DateTimeZone;
use Exception;
use FOF40\Container\Container;
use FOF40\Date\Date;
use FOF40\Date\DateDecorator;
use FOF40\Input\Input;
use FOF40\Platform\Base\Platform as BasePlatform;
use InvalidArgumentException;
use JDatabaseDriver;
use JEventDispatcher;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Application\CliApplication;
use Joomla\CMS\Application\CliApplication as JApplicationCli;
use Joomla\CMS\Application\ConsoleApplication;
use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Authentication\AuthenticationResponse;
use Joomla\CMS\Cache\Cache;
use Joomla\CMS\Document\Document;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\User\UserHelper;
use Joomla\CMS\Version as JoomlaVersion;
use Joomla\Event\Event;
use Joomla\Registry\Registry;

/**
 * Part of the FOF Platform Abstraction Layer.
 *
 * This implements the platform class for Joomla! 3 and Joomla! 4
 *
 * @since    2.1
 */
class Platform extends BasePlatform
{
	/**
	 * Is this a CLI application?
	 *
	 * @var   bool
	 */
	protected static $isCLI;

	/**
	 * Is this an administrator application?
	 *
	 * @var   bool
	 */
	protected static $isAdmin;

	/**
	 * Is this an API application?
	 *
	 * @var   bool
	 */
	protected static $isApi;

	/**
	 * A fake session storage for CLI apps. Since CLI applications cannot have a session we are using a Registry object
	 * we manage internally.
	 *
	 * @var   Registry
	 */
	protected static $fakeSession;

	/**
	 * The table and table field cache object, used to speed up database access
	 *
	 * @var  Registry|null
	 */
	private $_cache;

	/**
	 * Public constructor.
	 *
	 * Overridden to cater for CLI applications not having access to a session object.
	 *
	 * @param   Container  $c  The component container
	 */
	public function __construct(Container $c)
	{
		parent::__construct($c);

		if ($this->isCli())
		{
			self::$fakeSession = new Registry();
		}
	}

	/**
	 * Checks if the current script is run inside a valid CMS execution
	 *
	 * @return bool
	 */
	public function checkExecution(): bool
	{
		return defined('_JEXEC');
	}

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
	 * @deprecated 5.0 Use showErrorPage with a real exception instead
	 */
	public function raiseError(int $code, string $message): void
	{
		$this->showErrorPage(new Exception($message, $code));
	}

	/**
	 * Returns absolute path to directories used by the containing CMS/application.
	 *
	 * The return is a table with the following key:
	 * * root    Path to the site root
	 * * public  Path to the public area of the site
	 * * admin   Path to the administrative area of the site
	 * * api     Path to the API application area of the site
	 * * tmp     Path to the temp directory
	 * * log     Path to the log directory
	 *
	 * @return  array  A hash array with keys root, public, admin, tmp and log.
	 */
	public function getPlatformBaseDirs(): array
	{
		return [
			'root'   => JPATH_ROOT,
			'public' => JPATH_SITE,
			'media'  => JPATH_SITE . '/media',
			'admin'  => JPATH_ADMINISTRATOR,
			'api'    => defined('JPATH_API') ? JPATH_API : (JPATH_ROOT . '/api'),
			'tmp'    => JoomlaFactory::getConfig()->get('tmp_path'),
			'log'    => JoomlaFactory::getConfig()->get('log_path'),
		];
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
	 * * api    The location of the component files serving the API part of the application
	 *
	 * All paths MUST be absolute. All paths MAY be the same if the
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
		if (!$this->isBackend())
		{
			$mainPath = JPATH_SITE . '/components/' . $component;
			$altPath  = JPATH_ADMINISTRATOR . '/components/' . $component;
		}
		else
		{
			$mainPath = JPATH_ADMINISTRATOR . '/components/' . $component;
			$altPath  = JPATH_SITE . '/components/' . $component;
		}

		return [
			'main'  => $mainPath,
			'alt'   => $altPath,
			'site'  => JPATH_SITE . '/components/' . $component,
			'admin' => JPATH_ADMINISTRATOR . '/components/' . $component,
			'api'   => (defined('JPATH_API') ? JPATH_API : (JPATH_ROOT . '/api')) . '/components/' . $component,
		];
	}

	/**
	 * Returns the application's template name
	 *
	 * @param   null|array  $params  An optional associative array of configuration settings
	 *
	 * @return  string  The template name. "system" is the fallback.
	 */
	public function getTemplate(?array $params = null): string
	{
		try
		{
			return JoomlaFactory::getApplication()->getTemplate($params ?? false);
		}
		catch (Exception $e)
		{
			return 'system';
		}
	}

	/**
	 * Get application-specific suffixes to use with template paths. This allows
	 * you to look for view template overrides based on the application version.
	 *
	 * @return  array  A plain array of suffixes to try in template names
	 */
	public function getTemplateSuffixes(): array
	{
		$jversion     = new JoomlaVersion;
		$versionParts = explode('.', $jversion->getShortVersion());
		$majorVersion = array_shift($versionParts);

		return [
			'.j' . str_replace('.', '', $jversion->getHelpVersion()),
			'.j' . $majorVersion,
		];
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
		if (!$this->isCli())
		{
			if ($absolute)
			{
				$path = JPATH_THEMES . '/';
			}
			else
			{
				$path = $this->isBackend() ? 'administrator/templates/' : 'templates/';
			}

			$directory = (substr($component, 0, 7) == 'media:/') ? ('media/' . substr($component, 7)) : ('html/' . $component);

			$path .= $this->getTemplate() .
				'/' . $directory;
		}
		else
		{
			$path = '';
		}

		return $path;
	}

	/**
	 * Load the translation files for a given component.
	 *
	 * @param   string  $component  The name of the component, e.g. "com_example"
	 *
	 * @return  void
	 */
	public function loadTranslations(string $component): void
	{
		$paths = $this->isBackend() ? [JPATH_ROOT, JPATH_ADMINISTRATOR] : [JPATH_ADMINISTRATOR, JPATH_ROOT];

		$jlang = $this->getLanguage();
		$jlang->load($component, $paths[0], 'en-GB', true);
		$jlang->load($component, $paths[0], null, true);
		$jlang->load($component, $paths[1], 'en-GB', true);
		$jlang->load($component, $paths[1], null, true);
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
		if ($this->isBackend())
		{
			// Master access check for the back-end, Joomla! 1.6 style.
			$user = $this->getUser();

			if (!$user->authorise('core.manage', $component)
				&& !$user->authorise('core.admin', $component)
			)
			{
				return false;
			}
		}

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
		/**
		 * If I'm in CLI I need load the User directly, otherwise JoomlaFactory will check the session (which doesn't exist
		 * in CLI)
		 */
		if ($this->isCli())
		{
			if ($id)
			{
				return User::getInstance($id) ?? new User();
			}

			return new User();
		}

		// Joomla 3
		if (version_compare(JVERSION, '3.999.999', 'lt'))
		{
			return JoomlaFactory::getUser($id) ?? new User();
		}

		// Joomla 4
		if (is_null($id))
		{
			return JoomlaFactory::getApplication()->getIdentity() ?? new User();
		}

		return JoomlaFactory::getContainer()->get(UserFactoryInterface::class)->loadUserById($id) ?? new User();
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
		$document = null;

		if (!$this->isCli())
		{
			try
			{
				$document = JoomlaFactory::getDocument();
			}
			catch (Exception $exc)
			{
				$document = null;
			}
		}

		return $document;
	}

	/**
	 * Returns an object to handle dates
	 *
	 * @param   mixed                     $time      The initial time
	 * @param   DateTimeZone|string|null  $tzOffset  The timezone offset
	 * @param   bool                      $locale    Should I try to load a specific class for current language?
	 *
	 * @return  Date object
	 */
	public function getDate(?string $time = 'now', $tzOffset = null, $locale = true): Date
	{
		$time = $time ?? $this->getDbo()->getNullDate() ?? 'now';

		if (!is_string($time) && (!is_object($time) || !($time instanceof DateTime)))
		{
			throw new InvalidArgumentException(sprintf('%s::%s -- $time expects a string or a DateTime object', __CLASS__, __METHOD__));
		}

		if ($locale)
		{
			// Work around a bug in Joomla! 3.7.0.
			if ($time == 'now')
			{
				$time = time();
			}

			$coreObject = JoomlaFactory::getDate($time, $tzOffset);

			return new DateDecorator($coreObject);
		}
		else
		{
			return new Date($time, $tzOffset);
		}
	}

	/**
	 * Return the Language instance of the CMS/application
	 *
	 * @return Language
	 */
	public function getLanguage(): Language
	{
		return JoomlaFactory::getLanguage();
	}

	/**
	 * Returns the database driver object of the CMS/application
	 *
	 * @return JDatabaseDriver
	 */
	public function getDbo(): JDatabaseDriver
	{
		return JoomlaFactory::getDbo();
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
		if ($this->isCli())
		{
			$ret = $input->get($request, $default, $type);

			if ($ret === $default)
			{
				$input->set($request, $ret);
			}

			return $ret;
		}

		try
		{
			$app = JoomlaFactory::getApplication();
		}
		catch (Exception $e)
		{
			$app = null;
		}

		$old_state = (!is_null($app) && method_exists($app, 'getUserState')) ? $app->getUserState($key, $default) : null;

		$cur_state = (!is_null($old_state)) ? $old_state : $default;
		$new_state = $input->get($request, null, $type);

		// Save the new value only if it was set in this request
		if ($setUserState)
		{
			if ($new_state !== null)
			{
				$app->setUserState($key, $new_state);
			}
			else
			{
				$new_state = $cur_state;
			}
		}
		elseif (is_null($new_state))
		{
			$new_state = $cur_state;
		}

		return $new_state;
	}

	/**
	 * Load plugins of a specific type. Obviously this seems to only be required
	 * in the Joomla! CMS.
	 *
	 * @param   string  $type  The type of the plugins to be loaded
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore
	 * @see PlatformInterface::importPlugin()
	 *
	 */
	public function importPlugin(string $type): void
	{
		// Should I actually run the plugins?
		$runPlugins = $this->isAllowPluginsInCli() || !$this->isCli();

		if ($runPlugins)
		{
			PluginHelper::importPlugin($type);
		}
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
		// Should I actually run the plugins?
		$runPlugins = $this->isAllowPluginsInCli() || !$this->isCli();

		if ($runPlugins)
		{
			if (class_exists('JEventDispatcher'))
			{
				return JEventDispatcher::getInstance()->trigger($event, $data);
			}

			// If there's no JEventDispatcher try getting JApplication
			try
			{
				$app = JoomlaFactory::getApplication();
			}
			catch (Exception $e)
			{
				// If I can't get JApplication I cannot run the plugins.
				return [];
			}

			// Joomla 3 and 4 have triggerEvent
			if (method_exists($app, 'triggerEvent'))
			{
				return $app->triggerEvent($event, $data);
			}

			// Joomla 5 (and possibly some 4.x versions) don't have triggerEvent. Go through the Events dispatcher.
			if (method_exists($app, 'getDispatcher') && class_exists('Joomla\Event\Event'))
			{
				try
				{
					$dispatcher = $app->getDispatcher();
				}
				catch (\UnexpectedValueException $exception)
				{
					return [];
				}

				if ($data instanceof Event)
				{
					$eventObject = $data;
				}
				elseif (\is_array($data))
				{
					$eventObject = new Event($event, $data);
				}
				else
				{
					throw new \InvalidArgumentException('The plugin data must either be an event or an array');
				}

				$result = $dispatcher->dispatch($event, $eventObject);

				return !isset($result['result']) || \is_null($result['result']) ? [] : $result['result'];
			}

			// No viable way to run the plugins :(
			return [];
		}
		else
		{
			return [];
		}
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
		if ($this->isCli())
		{
			return true;
		}

		$ret = JoomlaFactory::getUser()->authorise($action, $assetname);

		// Work around Joomla returning null instead of false in some cases.
		return (bool) $ret;
	}

	/**
	 * Is this the administrative section of the component?
	 *
	 * @return  bool
	 */
	public function isBackend(): bool
	{
		[$isCli, $isAdmin, $isApi] = $this->isCliAdminApi();

		return $isAdmin && !$isCli && !$isApi;
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
		[$isCli, $isAdmin, $isApi] = $this->isCliAdminApi();

		if ($strict)
		{
			return !$isAdmin && !$isCli && !$isApi;
		}

		return !$isAdmin && !$isCli;
	}

	/**
	 * Is this a component running in a CLI application?
	 *
	 * @return  bool
	 */
	public function isCli(): bool
	{
		[$isCli, $isAdmin, $isApi] = $this->isCliAdminApi();

		return !$isAdmin && !$isApi && $isCli;
	}

	/**
	 * Is this a component running under the API application?
	 *
	 * @return  bool
	 */
	public function isApi(): bool
	{
		[$isCli, $isAdmin, $isApi] = $this->isCliAdminApi();

		return $isApi && !$isAdmin && !$isCli;
	}

	/**
	 * Is the global FOF cache enabled?
	 *
	 * @return  bool
	 */
	public function isGlobalFOFCacheEnabled(): bool
	{
		return !(defined('JDEBUG') && JDEBUG);
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
		$registry = $this->getCacheObject();

		return $registry->get($key, $default);
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
		$registry = $this->getCacheObject();

		$registry->set($key, $content);

		return $this->saveCache();
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
		$false = false;
		$cache = JoomlaFactory::getCache('fof', '');

		return $cache->store($false, 'cache', 'fof');
	}

	/**
	 * Returns an object that holds the configuration of the current site.
	 *
	 * @return  Registry
	 *
	 * @codeCoverageIgnore
	 */
	public function getConfig(): Registry
	{
		return JoomlaFactory::getConfig();
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
		$options = ['remember' => false];

		$response         = new AuthenticationResponse();
		$response->type   = 'fof';
		$response->status = Authentication::STATUS_FAILURE;

		if (isset($authInfo['username']))
		{
			$authenticate = Authentication::getInstance();
			$response     = $authenticate->authenticate($authInfo, $options);
		}

		// Use our own authentication handler, onFOFUserAuthenticate, as a fallback
		if ($response->status != Authentication::STATUS_SUCCESS)
		{
			$this->container->platform->importPlugin('user');
			$this->container->platform->importPlugin('fof');
			$pluginResults = $this->container->platform->runPlugins('onFOFUserAuthenticate', [$authInfo, $options]);

			/**
			 * Loop through all plugin results until we find a successful login. On failure we fall back to Joomla's
			 * previous authentication response.
			 */
			foreach ($pluginResults as $result)
			{
				if (empty($result))
				{
					continue;
				}

				if (!is_object($result) || !($result instanceof AuthenticationResponse))
				{
					continue;
				}

				if ($result->status != Authentication::STATUS_SUCCESS)
				{
					continue;
				}

				$response = $result;

				break;
			}
		}

		// User failed to authenticate: maybe he enabled two factor authentication?
		// Let's try again "manually", skipping the check vs two factor auth
		// Due the big mess with encryption algorithms and libraries, we are doing this extra check only
		// if we're in Joomla 2.5.18+ or 3.2.1+
		if ($response->status != Authentication::STATUS_SUCCESS && method_exists('\Joomla\CMS\User\UserHelper', 'verifyPassword'))
		{
			$db     = JoomlaFactory::getDbo();
			$query  = $db->getQuery(true)
				->select($db->qn(['id', 'password']))
				->from('#__users')
				->where('username=' . $db->quote($authInfo['username']));
			$result = $db->setQuery($query)->loadObject();

			if ($result)
			{
				$match = UserHelper::verifyPassword($authInfo['password'], $result->password, $result->id);

				if ($match === true)
				{
					// Bring this in line with the rest of the system
					$user               = $this->getUser($result->id);
					$response->email    = $user->email;
					$response->fullname = $user->name;

					$response->language = $this->isBackend() ? $user->getParam('admin_language') : $user->getParam('language');

					$response->status        = Authentication::STATUS_SUCCESS;
					$response->error_message = '';
				}
			}
		}

		if ($response->status == Authentication::STATUS_SUCCESS)
		{
			$this->importPlugin('user');
			$results = $this->runPlugins('onLoginUser', [(array) $response, $options]);

			unset($results); // Just to make phpStorm happy

			$userid = UserHelper::getUserId($response->username);
			$user   = $this->getUser($userid);

			$session = $this->container->session;
			$session->set('user', $user);

			return true;
		}

		return false;
	}

	/**
	 * logs out a user
	 *
	 * @return  bool  True on success
	 */
	public function logoutUser(): bool
	{
		try
		{
			$app = JoomlaFactory::getApplication();
		}
		catch (Exception $e)
		{
			return false;
		}

		$user       = $this->getUser();
		$options    = ['remember' => false];
		$parameters = [
			'username' => $user->username,
			'id'       => $user->id,
		];

		// Set clientid in the options array if it hasn't been set already and shared sessions are not enabled.
		if (!$app->get('shared_session', '0'))
		{
			$options['clientid'] = $app->getClientId();
		}

		$ret = $app->triggerEvent('onUserLogout', [$parameters, $options]);

		return !in_array(false, $ret, true);
	}

	/**
	 * Add a log file for FOF
	 *
	 * @param   string  $file
	 *
	 * @return  void
	 */
	public function logAddLogger($file): void
	{
		Log::addLogger(['text_file' => $file], Log::ALL, ['fof']);
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
		Log::add($message, Log::WARNING, 'deprecated');
	}

	/**
	 * Adds a message to the application's debug log
	 *
	 * @param   string  $message
	 *
	 * @return  void
	 *
	 * @codeCoverageIgnore
	 */
	public function logDebug(string $message): void
	{
		Log::add($message, Log::DEBUG, 'fof');
	}

	/** @inheritDoc */
	public function logUserAction($title, string $logText, string $extension, User $user = null): void
	{
		if (!is_string($title) && !is_array($title))
		{
			throw new InvalidArgumentException(sprintf('%s::%s -- $title expects a string or an array', __CLASS__, __METHOD__));
		}

		static $joomlaModelAdded = false;

		// User Actions Log is available only under Joomla 3.9+
		if (version_compare(JVERSION, '3.9', 'lt'))
		{
			return;
		}

		// Do not perform logging if we're under CLI. Even if we _could_ have a logged user in CLI, ActionlogsModelActionlog
		// model always uses JoomlaFactory to fetch the current user, fetching data from the session. This means that under the CLI
		// (where there is no session) such session is started, causing warnings because usually output was already started before
		if ($this->isCli())
		{
			return;
		}

		// Include required Joomla Model
		if (!$joomlaModelAdded)
		{
			BaseDatabaseModel::addIncludePath(JPATH_ROOT . '/administrator/components/com_actionlogs/models', 'ActionlogsModel');
			$joomlaModelAdded = true;
		}

		$user = $this->getUser();

		// No log for guest users
		if ($user->guest)
		{
			return;
		}

		$message = [
			'title'       => $title,
			'username'    => $user->username,
			'accountlink' => 'index.php?option=com_users&task=user.edit&id=' . $user->id,
		];

		if (is_array($title))
		{
			unset ($message['title']);

			$message = array_merge($message, $title);
		}

		/** @var ActionlogsModelActionlog $model * */
		try
		{
			$model = BaseDatabaseModel::getInstance('Actionlog', 'ActionlogsModel');
			$model->addLog([$message], $logText, $extension, $user->id);
		}
		catch (Exception $e)
		{
			// Ignore any error
		}
	}

	/**
	 * Returns the root URI for the request.
	 *
	 * @param   bool         $pathonly  If false, prepend the scheme, host and port information. Default is false.
	 * @param   string|null  $path      The path
	 *
	 * @return  string  The root URI string.
	 *
	 * @codeCoverageIgnore
	 */
	public function URIroot(bool $pathonly = false, ?string $path = null): string
	{
		return Uri::root($pathonly, $path);
	}

	/**
	 * Returns the base URI for the request.
	 *
	 * @param   bool  $pathonly  If false, prepend the scheme, host and port information. Default is false.
	 *
	 * @return  string  The base URI string
	 */
	public function URIbase(bool $pathonly = false): string
	{
		return Uri::base($pathonly);
	}

	/**
	 * Method to set a response header.  If the replace flag is set then all headers
	 * with the given name will be replaced by the new one (only if the current platform supports header caching)
	 *
	 * @param   string  $name     The name of the header to set.
	 * @param   string  $value    The value of the header to set.
	 * @param   bool    $replace  True to replace any headers with the same name.
	 *
	 * @return  void
	 *
	 * @codeCoverageIgnore
	 */
	public function setHeader(string $name, string $value, bool $replace = false): void
	{
		try
		{
			JoomlaFactory::getApplication()->setHeader($name, $value, $replace);
		}
		catch (Exception $e)
		{
			return;
		}
	}

	/**
	 * In platforms that perform header caching, send all headers.
	 *
	 * @return  void
	 *
	 * @codeCoverageIgnore
	 */
	public function sendHeaders(): void
	{
		try
		{
			JoomlaFactory::getApplication()->sendHeaders();
		}
		catch (Exception $e)
		{
			return;
		}
	}

	/**
	 * Immediately terminate the containing application's execution
	 *
	 * @param   int  $code  The result code which should be returned by the application
	 *
	 * @return  void
	 */
	public function closeApplication(int $code = 0): void
	{
		// Necessary workaround for broken System - Page Cache plugin in Joomla! 3.7.0
		$this->bugfixJoomlaCachePlugin();

		try
		{
			JoomlaFactory::getApplication()->close($code);
		}
		catch (Exception $e)
		{
			exit($code);
		}
	}

	/**
	 * Perform a redirection to a different page, optionally enqueuing a message for the user.
	 *
	 * @param   string  $url     The URL to redirect to
	 * @param   int     $status  (optional) The HTTP redirection status code, default 303 (See Other)
	 * @param   string  $msg     (optional) A message to enqueue
	 * @param   string  $type    (optional) The message type, e.g. 'message' (default), 'warning' or 'error'.
	 *
	 * @return  void
	 */
	public function redirect(string $url, int $status = 301, ?string $msg = null, string $type = 'message'): void
	{
		// Necessary workaround for broken System - Page Cache plugin in Joomla! 3.7.0
		$this->bugfixJoomlaCachePlugin();

		try
		{
			$app = JoomlaFactory::getApplication();
		}
		catch (Exception $e)
		{
			die(sprintf('Please go to <a href="%s">%1$s</a>', $url));
		}

		if (!empty($msg))
		{
			if (empty($type))
			{
				$type = 'message';
			}

			$app->enqueueMessage($msg, $type);
		}

		// Joomla 4: redirecting to index.php in the backend takes you to the frontend. I need to address that.
		$isJoomla4   = version_compare(JVERSION, '3.999.999', 'gt');
		$isBareIndex = substr($url, 0, 9) === 'index.php';

		if ($isJoomla4 && $isBareIndex && $this->isBackend())
		{
			$givenUri = new Uri($url);
			$newUri   = new Uri(Uri::base());

			$newUri->setQuery($givenUri->getQuery());

			if ($givenUri->getFragment())
			{
				$newUri->setFragment($givenUri->getFragment());
			}

			$url = $newUri->toString();
		}

		// Finally, do the redirection
		$app->redirect($url, $status);
	}

	/**
	 * Handle an exception in a way that results to an error page. We use this under Joomla! to work around a bug in
	 * Joomla! 3.7 which results in error pages leading to white pages because Joomla's System - Page Cache plugin is
	 * broken.
	 *
	 * @param   Exception  $exception  The exception to handle
	 *
	 * @throws  Exception  We rethrow the exception
	 */
	public function showErrorPage(Exception $exception): void
	{
		// Necessary workaround for broken System - Page Cache plugin in Joomla! 3.7.0
		$this->bugfixJoomlaCachePlugin();

		throw $exception;
	}

	/**
	 * Set a variable in the user session
	 *
	 * @param   string       $name       The name of the variable to set
	 * @param   string|null  $value      (optional) The value to set it to, default is null
	 * @param   string       $namespace  (optional) The variable's namespace e.g. the component name. Default: 'default'
	 *
	 * @return  void
	 */
	public function setSessionVar(string $name, $value = null, string $namespace = 'default'): void
	{
		// CLI
		if ($this->isCli() && !class_exists('FOFApplicationCLI'))
		{
			static::$fakeSession->set("$namespace.$name", $value);

			return;
		}

		// Joomla 3
		if (version_compare(JVERSION, '3.9999.9999', 'le'))
		{
			$this->container->session->set($name, $value, $namespace);
		}

		// Joomla 4
		if (empty($namespace))
		{
			$this->container->session->set($name, $value);

			return;
		}

		$registry = $this->container->session->get('registry');

		if (is_null($registry))
		{
			$registry = new Registry();

			$this->container->session->set('registry', $registry);
		}

		$registry->set($namespace . '.' . $name, $value);
	}

	/**
	 * Get a variable from the user session
	 *
	 * @param   string  $name       The name of the variable to set
	 * @param   string  $default    (optional) The default value to return if the variable does not exit, default: null
	 * @param   string  $namespace  (optional) The variable's namespace e.g. the component name. Default: 'default'
	 *
	 * @return  mixed
	 */
	public function getSessionVar(string $name, $default = null, $namespace = 'default')
	{
		// CLI
		if ($this->isCli() && !class_exists('FOFApplicationCLI'))
		{
			return static::$fakeSession->get("$namespace.$name", $default);
		}

		// Joomla 3
		if (version_compare(JVERSION, '3.9999.9999', 'le'))
		{
			return $this->container->session->get($name, $default, $namespace);
		}

		// Joomla 4
		if (empty($namespace))
		{
			return $this->container->session->get($name, $default);
		}

		$registry = $this->container->session->get('registry');

		if (is_null($registry))
		{
			$registry = new Registry();

			$this->container->session->set('registry', $registry);
		}

		return $registry->get($namespace . '.' . $name, $default);
	}

	/**
	 * Unset a variable from the user session
	 *
	 * @param   string  $name       The name of the variable to unset
	 * @param   string  $namespace  (optional) The variable's namespace e.g. the component name. Default: 'default'
	 *
	 * @return  void
	 */
	public function unsetSessionVar(string $name, string $namespace = 'default'): void
	{
		$this->setSessionVar($name, null, $namespace);
	}

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
	public function getToken(bool $formToken = false, bool $forceNew = false): string
	{
		// For CLI apps we implement our own fake token system
		if ($this->isCli())
		{
			$token = $this->getSessionVar('session.token');

			// Create a token
			if (is_null($token) || $forceNew)
			{
				$token = UserHelper::genRandomPassword(32);
				$this->setSessionVar('session.token', $token);
			}

			if (!$formToken)
			{
				return $token;
			}

			$user = $this->getUser();

			return ApplicationHelper::getHash($user->id . $token);
		}

		// Web application, go through the regular Joomla! API.
		if ($formToken)
		{
			return Session::getFormToken($forceNew);
		}

		return $this->container->session->getToken($forceNew);
	}

	/** @inheritDoc */
	public function addScriptOptions($key, $value, $merge = true)
	{
		/** @var HtmlDocument $document */
		$document = $this->getDocument();

		if (!method_exists($document, 'addScriptOptions'))
		{
			return;
		}

		$document->addScriptOptions($key, $value, $merge);
	}

	/** @inheritDoc */
	public function getScriptOptions($key = null)
	{
		/** @var HtmlDocument $document */
		$document = $this->getDocument();

		if (!method_exists($document, 'getScriptOptions'))
		{
			return [];
		}

		return $document->getScriptOptions($key);
	}

	/**
	 * Main function to detect if we're running in a CLI environment, if we're admin or if it's an API application
	 *
	 * @return  array  isCLI and isAdmin. It's not an associative array, so we can use list().
	 */
	protected function isCliAdminApi(): array
	{
		if (is_null(static::$isCLI) && is_null(static::$isAdmin))
		{
			static::$isCLI   = false;
			static::$isAdmin = false;
			static::$isApi   = false;

			try
			{
				if (is_null(JoomlaFactory::$application))
				{
					static::$isCLI   = true;
					static::$isAdmin = false;

					return [static::$isCLI, static::$isAdmin, static::$isApi];
				}

				$app           = JoomlaFactory::getApplication();
				static::$isCLI = $app instanceof Exception || $app instanceof CliApplication;

				if (class_exists('Joomla\CMS\Application\CliApplication'))
				{
					static::$isCLI = static::$isCLI || $app instanceof JApplicationCli;
				}

				if (class_exists('Joomla\CMS\Application\ConsoleApplication'))
				{
					static::$isCLI = static::$isCLI || ($app instanceof ConsoleApplication);
				}
			}
			catch (Exception $e)
			{
				static::$isCLI = true;
			}

			if (static::$isCLI)
			{
				return [static::$isCLI, static::$isAdmin, static::$isApi];
			}

			try
			{
				$app = JoomlaFactory::getApplication();
			}
			catch (Exception $e)
			{
				return [static::$isCLI, static::$isAdmin, static::$isApi];
			}

			if (method_exists($app, 'isAdmin'))
			{
				static::$isAdmin = $app->isAdmin();
			}
			elseif (method_exists($app, 'isClient'))
			{
				static::$isAdmin = $app->isClient('administrator');
				static::$isApi   = $app->isClient('api');
			}
		}

		return [static::$isCLI, static::$isAdmin, static::$isApi];
	}

	/**
	 * Gets a reference to the cache object, loading it from the disk if
	 * needed.
	 *
	 * @param   bool  $force  Should I forcibly reload the registry?
	 *
	 * @return  Registry
	 */
	private function &getCacheObject(bool $force = false): Registry
	{
		// Check if we have to load the cache file or we are forced to do that
		if (is_null($this->_cache) || $force)
		{
			// Try to get data from Joomla!'s cache
			$cache        = JoomlaFactory::getCache('fof', '');
			$this->_cache = $cache->get('cache', 'fof');

			$isRegistry = is_object($this->_cache);

			if ($isRegistry)
			{
				$isRegistry = $this->_cache instanceof Registry;
			}

			if (!$isRegistry)
			{
				// Create a new Registry object
				$this->_cache = new Registry();
			}
		}

		return $this->_cache;
	}

	/**
	 * Save the cache object back to disk
	 *
	 * @return  bool  True on success
	 */
	private function saveCache(): bool
	{
		// Get the Registry object of our cached data
		$registry = $this->getCacheObject();

		$cache = JoomlaFactory::getCache('fof', '');

		return $cache->store($registry, 'cache', 'fof');
	}

	/**
	 * Joomla! 3.7 has a broken System - Page Cache plugin. When this plugin is enabled it FORCES the caching of all
	 * pages as soon as Joomla! starts loading, before the plugin has a chance to request to not be cached. Event worse,
	 * in case of a redirection, it doesn't try to remove the cache lock. This means that the next request will be
	 * treated as though the result of the page should be cached. Since there is NO cache content for the page Joomla!
	 * returns an empty response with a 200 OK header. This will, of course, get in the way of every single attempt to
	 * perform a redirection in the frontend of the site.
	 *
	 * @return  void
	 */
	private function bugfixJoomlaCachePlugin(): void
	{
		// Only do something when the System - Cache plugin is activated
		if (!class_exists('PlgSystemCache'))
		{
			return;
		}

		// Forcibly uncache the current request
		$options = [
			'defaultgroup' => 'page',
			'browsercache' => false,
			'caching'      => false,
		];

		$cache_key = Uri::getInstance()->toString();
		Cache::getInstance('page', $options)->cache->remove($cache_key, 'page');
	}
}
