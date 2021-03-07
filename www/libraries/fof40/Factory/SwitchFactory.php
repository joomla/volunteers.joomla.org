<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Factory;

defined('_JEXEC') || die;

use Exception;
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

/**
 * MVC object factory. This implements the advanced functionality, i.e. creating MVC objects only if the classes exist
 * in any component section (front-end, back-end). For example, if you're in the front-end and a Model class doesn't
 * exist there but does exist in the back-end then the back-end class will be returned.
 *
 * The Dispatcher and Toolbar will be created from default objects if specialised classes are not found in your application.
 */
class SwitchFactory extends BasicFactory implements FactoryInterface
{
	/**
	 * Create a new Controller object
	 *
	 * @param string $viewName The name of the view we're getting a Controller for.
	 * @param array  $config   Optional MVC configuration values for the Controller object.
	 *
	 * @return  Controller
	 */
	public function controller(string $viewName, array $config = []): Controller
	{
		try
		{
			return parent::controller($viewName, $config);
		}
		catch (ControllerNotFound $e)
		{
		}

		$controllerClass = $this->container->getNamespacePrefix('inverse') . 'Controller\\' . ucfirst($viewName);

		try
		{
			return $this->createController($controllerClass, $config);
		}
		catch (ControllerNotFound $e)
		{
		}

		$controllerClass = $this->container->getNamespacePrefix('inverse') . 'Controller\\' . ucfirst($this->container->inflector->singularize($viewName));

		return $this->createController($controllerClass, $config);
	}

	/**
	 * Create a new Model object
	 *
	 * @param string $viewName The name of the view we're getting a Model for.
	 * @param array  $config   Optional MVC configuration values for the Model object.
	 *
	 * @return  Model
	 */
	public function model(string $viewName, array $config = []): Model
	{
		try
		{
			return parent::model($viewName, $config);
		}
		catch (ModelNotFound $e)
		{
		}

		$modelClass = $this->container->getNamespacePrefix('inverse') . 'Model\\' . ucfirst($viewName);

		try
		{
			return $this->createModel($modelClass, $config);
		}
		catch (ModelNotFound $e)
		{
			$modelClass = $this->container->getNamespacePrefix('inverse') . 'Model\\' . ucfirst($this->container->inflector->singularize($viewName));

			return $this->createModel($modelClass, $config);
		}
	}

	/**
	 * Create a new View object
	 *
	 * @param string $viewName The name of the view we're getting a View object for.
	 * @param string $viewType The type of the View object. By default it's "html".
	 * @param array  $config   Optional MVC configuration values for the View object.
	 *
	 * @return  View
	 */
	public function view(string $viewName, $viewType = 'html', array $config = []): View
	{
		try
		{
			return parent::view($viewName, $viewType, $config);
		}
		catch (ViewNotFound $e)
		{
		}

		$viewClass = $this->container->getNamespacePrefix('inverse') . 'View\\' . ucfirst($viewName) . '\\' . ucfirst($viewType);

		try
		{
			return $this->createView($viewClass, $config);
		}
		catch (ViewNotFound $e)
		{
			$viewClass = $this->container->getNamespacePrefix('inverse') . 'View\\' . ucfirst($this->container->inflector->singularize($viewName)) . '\\' . ucfirst($viewType);

			return $this->createView($viewClass, $config);
		}
	}

	/**
	 * Creates a new Dispatcher
	 *
	 * @param array $config The configuration values for the Dispatcher object
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
			// Not found. Let's go on.
		}

		$dispatcherClass = $this->container->getNamespacePrefix('inverse') . 'Dispatcher\\Dispatcher';

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
	 * @param array $config The configuration values for the Toolbar object
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
			// Not found. Let's go on.
		}

		$toolbarClass = $this->container->getNamespacePrefix('inverse') . 'Toolbar\\Toolbar';

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
	 * Creates a new TransparentAuthentication
	 *
	 * @param array $config The configuration values for the TransparentAuthentication object
	 *
	 * @return  TransparentAuthentication
	 */
	public function transparentAuthentication(array $config = []): TransparentAuthentication
	{
		$toolbarClass = $this->container->getNamespacePrefix($this->getSection()) . 'TransparentAuthentication\\TransparentAuthentication';

		try
		{
			return $this->createTransparentAuthentication($toolbarClass, $config);
		}
		catch (TransparentAuthenticationNotFound $e)
		{
			// Not found. Let's go on.
		}

		$toolbarClass = $this->container->getNamespacePrefix('inverse') . 'TransparentAuthentication\\TransparentAuthentication';

		try
		{
			return $this->createTransparentAuthentication($toolbarClass, $config);
		}
		catch (TransparentAuthenticationNotFound $e)
		{
			// Not found. Return the default TransparentAuthentication
			return new TransparentAuthentication($this->container, $config);
		}
	}

	/**
	 * Creates a view template finder object for a specific View.
	 *
	 * The default configuration is:
	 * Look for .php, .blade.php files; default layout "default"; no default sub-template;
	 * look for both pluralised and singular views; fall back to the default layout without sub-template;
	 * look for templates in both site and admin
	 *
	 * @param View  $view   The view this view template finder will be attached to
	 * @param array $config Configuration variables for the object
	 *
	 * @return  mixed
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
			'strictView'    => false,
			'strictTpl'     => false,
			'strictLayout'  => false,
			'sidePrefix'    => 'any',
		];

		$config = array_merge($defaultConfig, $config);

		return parent::viewFinder($view, $config);
	}
}
