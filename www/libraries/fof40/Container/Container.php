<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Container;

defined('_JEXEC') || die;

use FOF40\Autoloader\Autoloader;
use FOF40\Configuration\Configuration;
use FOF40\Dispatcher\Dispatcher;
use FOF40\Encrypt\EncryptService;
use FOF40\Factory\FactoryInterface;
use FOF40\Inflector\Inflector;
use FOF40\Input\Input as FOFInput;
use FOF40\Params\Params;
use FOF40\Platform\FilesystemInterface;
use FOF40\Platform\Joomla\Filesystem as JoomlaFilesystem;
use FOF40\Platform\PlatformInterface;
use FOF40\Render\RenderInterface;
use FOF40\Template\Template;
use FOF40\Toolbar\Toolbar;
use FOF40\TransparentAuthentication\TransparentAuthentication as TransparentAuth;
use FOF40\Utils\MediaVersion;
use FOF40\View\Compiler\Blade;
use JDatabaseDriver;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\CMS\Session\Session;
use Joomla\Input\Input as JoomlaInput;

/**
 * Dependency injection container for FOF-powered components.
 *
 * The properties below (except componentName, bareComponentName and the ones marked with property-read) can be
 * configured in the fof.xml component configuration file.
 *
 * Sample fof.xml:
 *
 * <fof>
 *   <common>
 *      <container>
 *         <option name="componentNamespace"><![CDATA[MyCompany\MyApplication]]></option>
 *         <option name="frontEndPath"><![CDATA[%PUBLIC%\components\com_application]]></option>
 *         <option name="factoryClass">magic</option>
 *      </container>
 *   </common>
 * </fof>
 *
 * The paths can use the variables %ROOT%, %PUBLIC%, %ADMIN%, %TMP%, %LOG% i.e. all the path keys returned by
 * Platform's
 * getPlatformBaseDirs() method in uppercase and surrounded by percent signs.
 *
 *
 * @property  string                   $componentName      The name of the component (com_something)
 * @property  string                   $bareComponentName  The name of the component without com_ (something)
 * @property  string                   $componentNamespace The namespace of the component's classes (\Foobar)
 * @property  string                   $frontEndPath       The absolute path to the front-end files
 * @property  string                   $backEndPath        The absolute path to the back-end files
 * @property  string                   $thisPath           The preferred path (e.g. backEndPath for Admin
 *            application)
 * @property  string                   $rendererClass      View renderer classname. Must implement
 *            RenderInterface
 * @property  string                   $factoryClass       MVC Factory classname, default
 *            FOF40\Factory\BasicFactory
 * @property  string                   $platformClass      Platform classname, default
 *            FOF40\Platform\Joomla\Platform
 * @property  MediaVersion             $mediaVersion       A version string for media files in forms.
 *
 * @property-read  Configuration       $appConfig          The application configuration registry
 * @property-read  Blade               $blade              The Blade view template compiler engine
 * @property-read  JDatabaseDriver     $db                 The database connection object
 * @property-read  Dispatcher          $dispatcher         The component's dispatcher
 * @property-read  FactoryInterface    $factory            The MVC object factory
 * @property-read  FilesystemInterface $filesystem         The filesystem abstraction layer object
 * @property-read  Inflector           $inflector          The English word inflector
 * @property-read  Params              $params             The component's params
 * @property-read  FOFInput            $input              The input object
 * @property-read  PlatformInterface   $platform           The platform abstraction layer object
 * @property-read  RenderInterface     $renderer           The view renderer
 * @property-read  Session             $session            Joomla! session storage
 * @property-read  Template            $template           The template helper
 * @property-read  TransparentAuth     $transparentAuth    Transparent authentication handler
 * @property-read  Toolbar             $toolbar            The component's toolbar
 * @property-read  EncryptService      $crypto             The component's data encryption service
 */
class Container extends ContainerBase
{

	/**
	 * Cache of created container instances
	 *
	 * @var   array
	 */
	protected static $instances = [];

	/**
	 * Public constructor. This does NOT go through the fof.xml file. You are advised to use getInstance() instead.
	 *
	 * @param   array  $values  Overrides for the container configuration and services
	 *
	 * @throws  \FOF40\Container\Exception\NoComponent  If no component name is specified
	 */
	public function __construct(array $values = [])
	{
		// Initialise
		$this->bareComponentName  = '';
		$this->componentName      = '';
		$this->componentNamespace = '';
		$this->frontEndPath       = '';
		$this->backEndPath        = '';
		$this->thisPath           = '';
		$this->factoryClass       = 'FOF40\\Factory\\BasicFactory';
		$this->platformClass      = 'FOF40\\Platform\\Joomla\\Platform';

		$initMediaVersion = null;

		if (isset($values['mediaVersion']) && !is_object($values['mediaVersion']))
		{
			$initMediaVersion = $values['mediaVersion'];

			unset($values['mediaVersion']);
		}

		// Try to construct this container object
		parent::__construct($values);

		// Make sure we have a component name
		if (empty($this['componentName']))
		{
			throw new Exception\NoComponent;
		}

		$bareComponent = substr($this->componentName, 4);

		$this['bareComponentName'] = $bareComponent;

		// Try to guess the component's namespace
		if (empty($this['componentNamespace']))
		{
			$this->componentNamespace = ucfirst($bareComponent);
		}
		else
		{
			$this->componentNamespace = trim($this->componentNamespace, '\\');
		}

		// Make sure we have front-end and back-end paths
		if (empty($this['frontEndPath']))
		{
			$this->frontEndPath = JPATH_SITE . '/components/' . $this->componentName;
		}

		if (empty($this['backEndPath']))
		{
			$this->backEndPath = JPATH_ADMINISTRATOR . '/components/' . $this->componentName;
		}

		// Get the namespaces for the front-end and back-end parts of the component
		$frontEndNamespace = '\\' . $this->componentNamespace . '\\Site\\';
		$backEndNamespace  = '\\' . $this->componentNamespace . '\\Admin\\';

		// Special case: if the frontend and backend paths are identical, we don't use the Site and Admin namespace
		// suffixes after $this->componentNamespace (so you may use FOF with WebApplication apps)
		if ($this->frontEndPath == $this->backEndPath)
		{
			$frontEndNamespace = '\\' . $this->componentNamespace . '\\';
			$backEndNamespace  = '\\' . $this->componentNamespace . '\\';
		}

		// Do we have to register the component's namespaces with the autoloader?
		$autoloader = Autoloader::getInstance();

		if (!$autoloader->hasMap($frontEndNamespace))
		{
			$autoloader->addMap($frontEndNamespace, $this->frontEndPath);
		}

		if (!$autoloader->hasMap($backEndNamespace))
		{
			$autoloader->addMap($backEndNamespace, $this->backEndPath);
		}

		// Inflector service
		if (!isset($this['inflector']))
		{
			$this['inflector'] = function (Container $c) {
				return new Inflector();
			};
		}

		// Filesystem abstraction service
		if (!isset($this['filesystem']))
		{
			$this['filesystem'] = function (Container $c) {
				return new JoomlaFilesystem($c);
			};
		}

		// Platform abstraction service
		if (!isset($this['platform']))
		{
			if (empty($c['platformClass']))
			{
				$c['platformClass'] = 'FOF40\\Platform\\Joomla\\Platform';
			}

			$this['platform'] = function (Container $c) {
				$className = $c['platformClass'];

				return new $className($c);
			};
		}

		if (empty($this['thisPath']))
		{
			$this['thisPath'] = $this['frontEndPath'];

			if ($this->platform->isBackend())
			{
				$this['thisPath'] = $this['backEndPath'];
			}
		}

		// MVC Factory service
		if (!isset($this['factory']))
		{
			$this['factory'] = function (Container $c) {
				if (empty($c['factoryClass']))
				{
					$c['factoryClass'] = 'FOF40\\Factory\\BasicFactory';
				}

				if (strpos($c['factoryClass'], '\\') === false)
				{
					$class = $c->getNamespacePrefix() . 'Factory\\' . $c['factoryClass'];

					$c['factoryClass'] = class_exists($class) ? $class : '\\FOF40\\Factory\\' . ucfirst($c['factoryClass']) . 'Factory';
				}

				if (!class_exists($c['factoryClass'], true))
				{
					$c['factoryClass'] = 'FOF40\\Factory\\BasicFactory';
				}

				$factoryClass = $c['factoryClass'];

				/** @var FactoryInterface $factory */
				$factory = new $factoryClass($c);

				if (isset($c['section']))
				{
					$factory->setSection($c['section']);
				}

				return $factory;
			};
		}

		// Component Configuration service
		if (!isset($this['appConfig']))
		{
			$this['appConfig'] = function (Container $c) {
				$class = $c->getNamespacePrefix() . 'Configuration\\Configuration';

				if (!class_exists($class, true))
				{
					$class = '\\FOF40\\Configuration\\Configuration';
				}

				return new $class($c);
			};
		}

		// Component Params service
		if (!isset($this['params']))
		{
			$this['params'] = function (Container $c) {
				return new Params($c);
			};
		}

		// Blade view template compiler service
		if (!isset($this['blade']))
		{
			$this['blade'] = function (Container $c) {
				return new Blade($c);
			};
		}

		// Database Driver service
		if (!isset($this['db']))
		{
			$this['db'] = function (Container $c) {
				return $c->platform->getDbo();
			};
		}

		// Request Dispatcher service
		if (!isset($this['dispatcher']))
		{
			$this['dispatcher'] = function (Container $c) {
				return $c->factory->dispatcher();
			};
		}

		// Component toolbar provider
		if (!isset($this['toolbar']))
		{
			$this['toolbar'] = function (Container $c) {
				return $c->factory->toolbar();
			};
		}

		// Component toolbar provider
		if (!isset($this['transparentAuth']))
		{
			$this['transparentAuth'] = function (Container $c) {
				return $c->factory->transparentAuthentication();
			};
		}

		// View renderer
		if (!isset($this['renderer']))
		{
			$this['renderer'] = function (Container $c) {
				if (isset($c['rendererClass']) && class_exists($c['rendererClass']))
				{
					$class    = $c['rendererClass'];
					$renderer = new $class($c);

					if ($renderer instanceof RenderInterface)
					{
						return $renderer;
					}
				}

				$filesystem = $c->filesystem;

				// Try loading the stock renderers shipped with FOF
				$path        = __DIR__ . '/../Render/';
				$renderFiles = $filesystem->folderFiles($path, '.php');
				$renderer    = null;
				$priority    = 0;

				foreach ($renderFiles as $filename)
				{
					if ($filename == 'RenderBase.php')
					{
						continue;
					}

					if ($filename == 'RenderInterface.php')
					{
						continue;
					}

					$className = 'FOF40\\Render\\' . basename($filename, '.php');

					if (!class_exists($className, true))
					{
						continue;
					}

					/** @var RenderInterface $o */
					$o = new $className($c);

					$info = $o->getInformation();

					if (($info->enabled ?? []) === [])
					{
						continue;
					}

					if ($info->priority > $priority)
					{
						$priority = $info->priority;
						$renderer = $o;
					}
				}

				return $renderer;
			};
		}

		// Input Access service
		if (isset($this['input']) &&
			(!(is_object($this['input'])) ||
				!($this['input'] instanceof FOFInput) ||
				!($this['input'] instanceof JoomlaInput))
		)
		{
			if (empty($this['input']))
			{
				$this['input'] = [];
			}

			// This swap is necessary to prevent infinite recursion
			$this['rawInputData'] = array_merge($this['input']);
			unset($this['input']);

			$this['input'] = function (Container $c) {
				$input = new FOFInput($c['rawInputData']);
				unset($c['rawInputData']);

				return $input;
			};
		}

		if (!isset($this['input']))
		{
			$this['input'] = function () {
				return new FOFInput();
			};
		}

		// Session service
		if (!isset($this['session']))
		{
			$this['session'] = function (Container $c) {
				return JoomlaFactory::getSession();
			};
		}

		// Template service
		if (!isset($this['template']))
		{
			$this['template'] = function (Container $c) {
				return new Template($c);
			};
		}

		// Media version string
		if (!isset($this['mediaVersion']))
		{
			$this['mediaVersion'] = function (Container $c) {
				return new MediaVersion($c);
			};

			if (!is_null($initMediaVersion))
			{
				$this['mediaVersion']->setMediaVersion($initMediaVersion);
			}
		}

		// Encryption / cryptography service
		if (!isset($this['crypto']))
		{
			$this['crypto'] = function (Container $c) {
				return new EncryptService($c);
			};
		}
	}

	/**
	 * Returns a container instance for a specific component. This method goes through fof.xml to read the default
	 * configuration values for the container. You are advised to use this unless you have a specific reason for
	 * instantiating a Container without going through the fof.xml file.
	 *
	 * Pass the value 'tempInstance' => true in the $values array to get a temporary instance. Otherwise you will get
	 * the cached instance of the previously created container.
	 *
	 * @param   string  $component  The component you want to get a container for, e.g. com_foobar.
	 * @param   array   $values     Container configuration overrides you want to apply. Optional.
	 * @param   string  $section    The application section (site, admin) you want to fetch. Any other value results in
	 *                              auto-detection.
	 *
	 * @return \FOF40\Container\Container
	 */
	public static function &getInstance($component, array $values = [], $section = 'auto')
	{
		$tempInstance = false;

		if (isset($values['tempInstance']))
		{
			$tempInstance = $values['tempInstance'];
			unset($values['tempInstance']);
		}

		if ($tempInstance)
		{
			return self::makeInstance($component, $values, $section);
		}

		$signature = md5($component . '@' . $section);

		if (!isset(self::$instances[$signature]))
		{
			self::$instances[$signature] = self::makeInstance($component, $values, $section);
		}

		return self::$instances[$signature];
	}

	/**
	 * Returns a temporary container instance for a specific component.
	 *
	 * @param   string  $component  The component you want to get a container for, e.g. com_foobar.
	 * @param   array   $values     Container configuration overrides you want to apply. Optional.
	 * @param   string  $section    The application section (site, admin) you want to fetch. Any other value results in
	 *                              auto-detection.
	 *
	 * @return \FOF40\Container\Container
	 *
	 * @throws Exception\NoComponent
	 */
	protected static function &makeInstance($component, array $values = [], $section = 'auto')
	{
		// Try to auto-detect some defaults
		$tmpConfig    = array_merge($values, ['componentName' => $component]);
		$tmpContainer = new Container($tmpConfig);

		if (!in_array($section, ['site', 'admin']))
		{
			$section = $tmpContainer->platform->isBackend() ? 'admin' : 'site';
		}

		$appConfig = $tmpContainer->appConfig;

		// Get the namespace from fof.xml
		$namespace = $appConfig->get('container.componentNamespace', null);

		// $values always overrides $namespace and fof.xml
		if (isset($values['componentNamespace']))
		{
			$namespace = $values['componentNamespace'];
		}

		// If there is no namespace set, try to guess it.
		if (empty($namespace))
		{
			$bareComponent = $component;

			if (substr($component, 0, 4) == 'com_')
			{
				$bareComponent = substr($component, 4);
			}

			$namespace = ucfirst($bareComponent);
		}

		// Get the default front-end/back-end paths
		$frontEndPath = $appConfig->get('container.frontEndPath', JPATH_SITE . '/components/' . $component);
		$backEndPath  = $appConfig->get('container.backEndPath', JPATH_ADMINISTRATOR . '/components/' . $component);

		// Parse path variables if necessary
		$frontEndPath = $tmpContainer->parsePathVariables($frontEndPath);
		$backEndPath  = $tmpContainer->parsePathVariables($backEndPath);

		// Apply path overrides
		if (isset($values['frontEndPath']))
		{
			$frontEndPath = $values['frontEndPath'];
		}

		if (isset($values['backEndPath']))
		{
			$backEndPath = $values['backEndPath'];
		}

		$thisPath = ($section == 'admin') ? $backEndPath : $frontEndPath;

		// Get the namespaces for the front-end and back-end parts of the component
		$frontEndNamespace = '\\' . $namespace . '\\Site\\';
		$backEndNamespace  = '\\' . $namespace . '\\Admin\\';

		// Special case: if the frontend and backend paths are identical, we don't use the Site and Admin namespace
		// suffixes after $this->componentNamespace (so you may use FOF with WebApplication apps)
		if ($frontEndPath == $backEndPath)
		{
			$frontEndNamespace = '\\' . $namespace . '\\';
			$backEndNamespace  = '\\' . $namespace . '\\';
		}

		// Do we have to register the component's namespaces with the autoloader?
		$autoloader = Autoloader::getInstance();

		if (!$autoloader->hasMap($frontEndNamespace))
		{
			$autoloader->addMap($frontEndNamespace, $frontEndPath);
		}

		if (!$autoloader->hasMap($backEndNamespace))
		{
			$autoloader->addMap($backEndNamespace, $backEndPath);
		}

		// Get the Container class name
		$classNamespace = ($section == 'admin') ? $backEndNamespace : $frontEndNamespace;
		$class          = $classNamespace . 'Container';

		// Get the values overrides from fof.xml
		$values = array_merge([
			'factoryClass'  => '\\FOF40\\Factory\\BasicFactory',
			'platformClass' => '\\FOF40\\Platform\\Joomla\\Platform',
			'section'       => $section,
		], $values);

		$values = array_merge($values, [
			'componentName'      => $component,
			'componentNamespace' => $namespace,
			'frontEndPath'       => $frontEndPath,
			'backEndPath'        => $backEndPath,
			'thisPath'           => $thisPath,
			'rendererClass'      => $appConfig->get('container.rendererClass', null),
			'factoryClass'       => $appConfig->get('container.factoryClass', $values['factoryClass']),
			'platformClass'      => $appConfig->get('container.platformClass', $values['platformClass']),
		]);

		if (empty($values['rendererClass']))
		{
			unset ($values['rendererClass']);
		}

		$mediaVersion = $appConfig->get('container.mediaVersion', null);

		unset($appConfig);
		unset($tmpConfig);
		unset($tmpContainer);

		$container = class_exists($class, true) ? new $class($values) : new Container($values);

		if (!is_null($mediaVersion))
		{
			$container->mediaVersion->setMediaVersion($mediaVersion);
		}

		return $container;
	}

	/**
	 * The container SHOULD NEVER be serialised. If this happens, it means that any of the installed version is doing
	 * something REALLY BAD, so let's die and inform the user of what it's going on.
	 */
	public function __sleep()
	{
		// If the site is in debug mode we die and let the user figure it out
		if (defined('JDEBUG') && JDEBUG)
		{
			$msg = <<< END
Something on your site is broken and tries to save the plugin state in the cache. This is a major security issue and
will cause your site to not work properly. Go to your site's backend, Global Configuration and set Caching to OFF as a
temporary solution. Possible causes: older versions of JoomlaShine templates, JomSocial, BetterPreview and other third
party Joomla! extensions. 
END;

			die($msg);
		}

		// Otherwise we serialise the Container
		return ['values', 'factories', 'protected', 'frozen', 'raw', 'keys'];
	}

	/**
	 * Get the applicable namespace prefix for a component section. Possible sections:
	 * auto            Auto-detect which is the current component section
	 * inverse      The inverse area than auto
	 * site            Frontend
	 * admin        Backend
	 *
	 * @param   string  $section  The section you want to get information for
	 *
	 * @return  string  The namespace prefix for the component's classes, e.g. \Foobar\Example\Site\
	 */
	public function getNamespacePrefix(string $section = 'auto'): string
	{
		// Get the namespaces for the front-end and back-end parts of the component
		$frontEndNamespace = '\\' . $this->componentNamespace . '\\Site\\';
		$backEndNamespace  = '\\' . $this->componentNamespace . '\\Admin\\';

		// Special case: if the frontend and backend paths are identical, we don't use the Site and Admin namespace
		// suffixes after $this->componentNamespace (so you may use FOF with WebApplication apps)
		if ($this->frontEndPath === $this->backEndPath)
		{
			$frontEndNamespace = '\\' . $this->componentNamespace . '\\';
			$backEndNamespace  = '\\' . $this->componentNamespace . '\\';
		}

		switch ($section)
		{
			default:
			case 'auto':
				if ($this->platform->isBackend())
				{
					return $backEndNamespace;
				}
				else
				{
					return $frontEndNamespace;
				}
				break;

			case 'inverse':
				if ($this->platform->isBackend())
				{
					return $frontEndNamespace;
				}

				return $backEndNamespace;

			case 'site':
				return $frontEndNamespace;

			case 'admin':
				return $backEndNamespace;
		}
	}

	/**
	 * Replace the path variables in the $path string.
	 *
	 * The recognized variables are:
	 * * %root%    Path to the site root
	 * * %public%  Path to the public area of the site
	 * * %admin%   Path to the administrative area of the site
	 * * %api%     Path to the API application area of the site
	 * * %tmp%     Path to the temp directory
	 * * %log%     Path to the log directory
	 *
	 * @param   string  $path
	 *
	 * @return mixed
	 */
	public function parsePathVariables(string $path)
	{
		$platformDirs = $this->platform->getPlatformBaseDirs();
		// root public admin tmp log

		$search  = array_map(function ($x) {
			return '%' . strtoupper($x) . '%';
		}, array_keys($platformDirs));
		$replace = array_values($platformDirs);

		return str_replace($search, $replace, $path);
	}
}
