<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Factory;

defined('_JEXEC') || die;

use Exception;
use FOF40\Container\Container;
use FOF40\Controller\Controller;
use FOF40\Dispatcher\Dispatcher;
use FOF40\Factory\Exception\ControllerNotFound;
use FOF40\Factory\Exception\DispatcherNotFound;
use FOF40\Factory\Exception\ModelNotFound;
use FOF40\Factory\Exception\ToolbarNotFound;
use FOF40\Factory\Exception\TransparentAuthenticationNotFound;
use FOF40\Factory\Exception\ViewNotFound;
use FOF40\Model\Model;
use FOF40\Toolbar\Toolbar;
use FOF40\TransparentAuthentication\TransparentAuthentication;
use FOF40\View\View;
use FOF40\View\ViewTemplateFinder;
use RuntimeException;

/**
 * MVC object factory. This implements the basic functionality, i.e. creating MVC objects only if the classes exist in
 * the same component section (front-end, back-end) you are currently running in. The Dispatcher and Toolbar will be
 * created from default objects if specialised classes are not found in your application.
 */
class BasicFactory implements FactoryInterface
{
	/** @var  Container  The container we belong to */
	protected $container;

	/**
	 * Section used to build the namespace prefix. We have to pass it since in CLI we need
	 * to force the section we're in (ie Site or Admin). {@see \FOF40\Container\Container::getNamespacePrefix() } for
	 * valid values
	 *
	 * @var   string
	 */
	protected $section = 'auto';

	/**
	 * Public constructor for the factory object
	 *
	 * @param   Container  $container  The container we belong to
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * Create a new Controller object
	 *
	 * @param   string  $viewName  The name of the view we're getting a Controller for.
	 * @param   array   $config    Optional MVC configuration values for the Controller object.
	 *
	 * @return  Controller
	 */
	public function controller(string $viewName, array $config = []): Controller
	{
		$controllerClass = $this->container->getNamespacePrefix($this->getSection()) . 'Controller\\' . ucfirst($viewName);

		try
		{
			return $this->createController($controllerClass, $config);
		}
		catch (ControllerNotFound $e)
		{
		}

		$controllerClass = $this->container->getNamespacePrefix($this->getSection()) . 'Controller\\' . ucfirst($this->container->inflector->singularize($viewName));

		return $this->createController($controllerClass, $config);
	}

	/**
	 * Create a new Model object
	 *
	 * @param   string  $viewName  The name of the view we're getting a Model for.
	 * @param   array   $config    Optional MVC configuration values for the Model object.
	 *
	 * @return  Model
	 */
	public function model(string $viewName, array $config = []): Model
	{
		$modelClass = $this->container->getNamespacePrefix($this->getSection()) . 'Model\\' . ucfirst($viewName);

		try
		{
			return $this->createModel($modelClass, $config);
		}
		catch (ModelNotFound $e)
		{
		}

		$modelClass = $this->container->getNamespacePrefix($this->getSection()) . 'Model\\' . ucfirst($this->container->inflector->singularize($viewName));

		return $this->createModel($modelClass, $config);
	}

	/**
	 * Create a new View object
	 *
	 * @param   string  $viewName  The name of the view we're getting a View object for.
	 * @param   string  $viewType  The type of the View object. By default it's "html".
	 * @param   array   $config    Optional MVC configuration values for the View object.
	 *
	 * @return  View
	 */
	public function view(string $viewName, $viewType = 'html', array $config = []): View
	{
		$container = $this->container;
		$prefix    = $this->container->getNamespacePrefix($this->getSection());

		$viewClass = $prefix . 'View\\' . ucfirst($viewName) . '\\' . ucfirst($viewType);

		try
		{
			return $this->createView($viewClass, $config);
		}
		catch (ViewNotFound $e)
		{
		}

		$viewClass = $prefix . 'View\\' . ucfirst($container->inflector->singularize($viewName)) . '\\' . ucfirst($viewType);

		return $this->createView($viewClass, $config);
	}

	/**
	 * Creates a new Dispatcher
	 *
	 * @param   array  $config  The configuration values for the Dispatcher object
	 *
	 * @return  Dispatcher
	 */
	public function dispatcher(array $config = []): Dispatcher
	{
		$dispatcherClass = $this->container->getNamespacePrefix($this->getSection()) . 'Dispatcher\\Dispatcher';

		try
		{
			return $this->createDispatcher($dispatcherClass, $config);
		}
		catch (DispatcherNotFound $e)
		{
			// Not found. Return the default Dispatcher
			return new Dispatcher($this->container, $config);
		}
	}

	/**
	 * Creates a new Toolbar
	 *
	 * @param   array  $config  The configuration values for the Toolbar object
	 *
	 * @return  Toolbar
	 */
	public function toolbar(array $config = []): Toolbar
	{
		$toolbarClass = $this->container->getNamespacePrefix($this->getSection()) . 'Toolbar\\Toolbar';

		try
		{
			return $this->createToolbar($toolbarClass, $config);
		}
		catch (ToolbarNotFound $e)
		{
			// Not found. Return the default Toolbar
			return new Toolbar($this->container, $config);
		}
	}

	/**
	 * Creates a new TransparentAuthentication handler
	 *
	 * @param   array  $config  The configuration values for the TransparentAuthentication object
	 *
	 * @return  TransparentAuthentication
	 */
	public function transparentAuthentication(array $config = []): TransparentAuthentication
	{
		$authClass = $this->container->getNamespacePrefix($this->getSection()) . 'TransparentAuthentication\\TransparentAuthentication';

		try
		{
			return $this->createTransparentAuthentication($authClass, $config);
		}
		catch (TransparentAuthenticationNotFound $e)
		{
			// Not found. Return the default TA
			return new TransparentAuthentication($this->container, $config);
		}
	}

	/**
	 * Creates a view template finder object for a specific View
	 *
	 * The default configuration is:
	 * Look for .php, .blade.php files; default layout "default"; no default sub-template;
	 * look only for the specified view; do NOT fall back to the default layout or sub-template;
	 * look for templates ONLY in site or admin, depending on where we're running from
	 *
	 * @param   View   $view    The view this view template finder will be attached to
	 * @param   array  $config  Configuration variables for the object
	 *
	 * @return  ViewTemplateFinder
	 *
	 * @throws Exception
	 */
	public function viewFinder(View $view, array $config = []): ViewTemplateFinder
	{
		// Initialise the configuration with the default values
		$defaultConfig = [
			'extensions'    => ['.php', '.blade.php'],
			'defaultLayout' => 'default',
			'defaultTpl'    => '',
			'strictView'    => true,
			'strictTpl'     => true,
			'strictLayout'  => true,
			'sidePrefix'    => 'auto',
		];

		$config = array_merge($defaultConfig, $config);

		// Apply fof.xml overrides
		$appConfig = $this->container->appConfig;
		$key       = "views." . ucfirst($view->getName()) . ".config";

		$fofXmlConfig = [
			'extensions'   => $appConfig->get("$key.templateExtensions", $config['extensions']),
			'strictView'   => $appConfig->get("$key.templateStrictView", $config['strictView']),
			'strictTpl'    => $appConfig->get("$key.templateStrictTpl", $config['strictTpl']),
			'strictLayout' => $appConfig->get("$key.templateStrictLayout", $config['strictLayout']),
			'sidePrefix'   => $appConfig->get("$key.templateLocation", $config['sidePrefix']),
		];

		$config = array_merge($config, $fofXmlConfig);

		// Create the new view template finder object
		return new ViewTemplateFinder($view, $config);
	}

	/**
	 * @return string
	 */
	public function getSection(): string
	{
		return $this->section;
	}

	/**
	 * @param   string  $section
	 */
	public function setSection(string $section): void
	{
		$this->section = $section;
	}

	/**
	 * Creates a Controller object
	 *
	 * @param   string  $controllerClass  The fully qualified class name for the Controller
	 * @param   array   $config           Optional MVC configuration values for the Controller object.
	 *
	 * @return  Controller
	 *
	 * @throws  RuntimeException  If the $controllerClass does not exist
	 */
	protected function createController(string $controllerClass, array $config = []): Controller
	{
		if (!class_exists($controllerClass))
		{
			throw new ControllerNotFound($controllerClass);
		}

		return new $controllerClass($this->container, $config);
	}

	/**
	 * Creates a Model object
	 *
	 * @param   string  $modelClass  The fully qualified class name for the Model
	 * @param   array   $config      Optional MVC configuration values for the Model object.
	 *
	 * @return  Model
	 *
	 * @throws  RuntimeException  If the $modelClass does not exist
	 */
	protected function createModel(string $modelClass, array $config = []): Model
	{
		if (!class_exists($modelClass))
		{
			throw new ModelNotFound($modelClass);
		}

		return new $modelClass($this->container, $config);
	}

	/**
	 * Creates a View object
	 *
	 * @param   string  $viewClass  The fully qualified class name for the View
	 * @param   array   $config     Optional MVC configuration values for the View object.
	 *
	 * @return  View
	 *
	 * @throws  RuntimeException  If the $viewClass does not exist
	 */
	protected function createView(string $viewClass, array $config = []): View
	{
		if (!class_exists($viewClass))
		{
			throw new ViewNotFound($viewClass);
		}

		return new $viewClass($this->container, $config);
	}

	/**
	 * Creates a Toolbar object
	 *
	 * @param   string  $toolbarClass  The fully qualified class name for the Toolbar
	 * @param   array   $config        The configuration values for the Toolbar object
	 *
	 * @return  Toolbar
	 *
	 * @throws  RuntimeException  If the $toolbarClass does not exist
	 */
	protected function createToolbar(string $toolbarClass, array $config = []): Toolbar
	{
		if (!class_exists($toolbarClass))
		{
			throw new ToolbarNotFound($toolbarClass);
		}

		return new $toolbarClass($this->container, $config);
	}

	/**
	 * Creates a Dispatcher object
	 *
	 * @param   string  $dispatcherClass  The fully qualified class name for the Dispatcher
	 * @param   array   $config           The configuration values for the Dispatcher object
	 *
	 * @return  Dispatcher
	 *
	 * @throws  RuntimeException  If the $dispatcherClass does not exist
	 */
	protected function createDispatcher(string $dispatcherClass, array $config = []): Dispatcher
	{
		if (!class_exists($dispatcherClass))
		{
			throw new DispatcherNotFound($dispatcherClass);
		}

		return new $dispatcherClass($this->container, $config);
	}

	/**
	 * Creates a TransparentAuthentication object
	 *
	 * @param   string  $authClass  The fully qualified class name for the TransparentAuthentication
	 * @param   array   $config     The configuration values for the TransparentAuthentication object
	 *
	 * @return  TransparentAuthentication
	 *
	 * @throws  RuntimeException  If the $authClass does not exist
	 */
	protected function createTransparentAuthentication(string $authClass, array $config): TransparentAuthentication
	{
		if (!class_exists($authClass))
		{
			throw new TransparentAuthenticationNotFound($authClass);
		}

		return new $authClass($this->container, $config);
	}
}
