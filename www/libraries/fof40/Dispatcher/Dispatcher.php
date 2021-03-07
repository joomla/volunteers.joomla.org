<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Dispatcher;

defined('_JEXEC') || die;

use Exception;
use FOF40\Container\Container;
use FOF40\Controller\Controller;
use FOF40\Dispatcher\Exception\AccessForbidden;
use FOF40\TransparentAuthentication\TransparentAuthentication;

/**
 * A generic MVC dispatcher
 *
 * @property-read  \FOF40\Input\Input $input  The input object (magic __get returns the Input from the Container)
 */
class Dispatcher
{
	/** @var   string  The name of the default view, in case none is specified */
	public $defaultView;

	/** @var  array  Local cache of the dispatcher configuration */
	protected $config = [];

	/** @var  Container  The container we belong to */
	protected $container;

	/** @var  string  The view which will be rendered by the dispatcher */
	protected $view;

	/** @var  string  The layout for rendering the view */
	protected $layout;

	/** @var  Controller  The controller which will be used */
	protected $controller;

	/** @var  bool  Is this user transparently logged in? */
	protected $isTransparentlyLoggedIn = false;

	/**
	 * Public constructor
	 *
	 * The $config array can contain the following optional values:
	 * defaultView  string  The view to render if none is specified in $input
	 *
	 * Do note that $config is passed to the Controller and through it to the Model and View. Please see these classes
	 * for more information on the configuration variables they accept.
	 *
	 * @param   Container  $container
	 * @param   array      $config
	 */
	public function __construct(Container $container, array $config = [])
	{
		$this->container = $container;

		$this->config = $config;

		$this->defaultView = $container->appConfig->get('dispatcher.defaultView', $this->defaultView);

		if (isset($config['defaultView']))
		{
			$this->defaultView = $config['defaultView'];
		}

		$this->supportCustomViewAndTaskParameters();

		// Get the default values for the view and layout names
		$this->view   = $this->input->getCmd('view', null);
		$this->layout = $this->input->getCmd('layout', null);

		// Not redundant; you may pass an empty but non-null view which is invalid, so we need the fallback
		if (empty($this->view))
		{
			$this->view = $this->defaultView;
			$this->container->input->set('view', $this->view);
		}
	}

	/**
	 * Magic get method. Handles magic properties:
	 * $this->input  mapped to $this->container->input
	 *
	 * @param   string  $name  The property to fetch
	 *
	 * @return  mixed|null
	 */
	public function __get(string $name)
	{
		// Handle $this->input
		if ($name == 'input')
		{
			return $this->container->input;
		}

		// Property not found; raise error
		$trace = debug_backtrace();
		trigger_error(
			'Undefined property via __get(): ' . $name .
			' in ' . $trace[0]['file'] .
			' on line ' . $trace[0]['line'],
			E_USER_NOTICE);

		return null;
	}

	/**
	 * The main code of the Dispatcher. It spawns the necessary controller and
	 * runs it.
	 *
	 * @return  void
	 *
	 * @throws AccessForbidden  When the access is forbidden
	 * @throws Exception For displaying an error page
	 */
	public function dispatch(): void
	{
		// Load the translations for this component;
		$this->container->platform->loadTranslations($this->container->componentName);

		// Perform transparent authentication
		if ($this->container->platform->getUser()->guest)
		{
			$this->transparentAuthenticationLogin();
		}

		// Get the event names (different for CLI)
		$onBeforeEventName = 'onBeforeDispatch';
		$onAfterEventName  = 'onAfterDispatch';

		if ($this->container->platform->isCli())
		{
			$onBeforeEventName = 'onBeforeDispatchCLI';
			$onAfterEventName  = 'onAfterDispatchCLI';
		}

		try
		{
			$result = $this->triggerEvent($onBeforeEventName);
			$error  = '';
		}
		catch (\Exception $e)
		{
			$result = false;
			$error  = $e->getMessage();
		}

		if ($result === false)
		{
			if ($this->container->platform->isCli())
			{
				$this->container->platform->setHeader('Status', '403 Forbidden', true);
			}

			$this->transparentAuthenticationLogout();

			$this->container->platform->showErrorPage(new AccessForbidden);
		}

		// Get and execute the controller
		$view = $this->input->getCmd('view', $this->defaultView);
		$task = $this->input->getCmd('task', 'default');

		if (empty($task))
		{
			$task = 'default';
			$this->input->set('task', $task);
		}

		try
		{
			$this->controller = $this->container->factory->controller($view, $this->config);
			$status           = $this->controller->execute($task);
		}
		catch (Exception $e)
		{
			$this->container->platform->showErrorPage($e);

			// Redundant; just to make code sniffers happy
			return;
		}

		if ($status !== false)
		{
			try
			{
				$this->triggerEvent($onAfterEventName);
			}
			catch (\Exception $e)
			{
				$status = false;
			}
		}

		if ($status === false)
		{
			if ($this->container->platform->isCli())
			{
				$this->container->platform->setHeader('Status', '403 Forbidden', true);
			}

			$this->transparentAuthenticationLogout();

			$this->container->platform->showErrorPage(new AccessForbidden);
		}

		$this->transparentAuthenticationLogout();

		$this->controller->redirect();
	}

	/**
	 * Returns a reference to the Controller object currently in use by the dispatcher
	 *
	 * @return Controller|null
	 */
	public function &getController(): ?Controller
	{
		return $this->controller;
	}

	/**
	 * Triggers an object-specific event. The event runs both locally –if a suitable method exists– and through the
	 * Joomla! plugin system. A true/false return value is expected. The first false return cancels the event.
	 *
	 * EXAMPLE
	 * Component: com_foobar, Object name: item, Event: onBeforeDispatch, Arguments: array(123, 456)
	 * The event calls:
	 * 1. $this->onBeforeDispatch(123, 456)
	 * 2. Joomla! plugin event onComFoobarDispatcherBeforeDispatch($this, 123, 456)
	 *
	 * @param   string  $event      The name of the event, typically named onPredicateVerb e.g. onBeforeKick
	 * @param   array   $arguments  The arguments to pass to the event handlers
	 *
	 * @return  bool
	 */
	protected function triggerEvent(string $event, array $arguments = []): bool
	{
		$result = true;

		// If there is an object method for this event, call it
		if (method_exists($this, $event))
		{
			switch (count($arguments))
			{
				case 0:
					$result = $this->{$event}();
					break;
				case 1:
					$result = $this->{$event}($arguments[0]);
					break;
				case 2:
					$result = $this->{$event}($arguments[0], $arguments[1]);
					break;
				case 3:
					$result = $this->{$event}($arguments[0], $arguments[1], $arguments[2]);
					break;
				case 4:
					$result = $this->{$event}($arguments[0], $arguments[1], $arguments[2], $arguments[3]);
					break;
				case 5:
					$result = $this->{$event}($arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4]);
					break;
				default:
					$result = call_user_func_array([$this, $event], $arguments);
					break;
			}
		}

		if ($result === false)
		{
			return false;
		}

		// All other event handlers live outside this object, therefore they need to be passed a reference to this
		// objects as the first argument.
		array_unshift($arguments, $this);

		// If we have an "on" prefix for the event (e.g. onFooBar) remove it and stash it for later.
		$prefix = '';

		if (substr($event, 0, 2) == 'on')
		{
			$prefix = 'on';
			$event  = substr($event, 2);
		}

		// Get the component/model prefix for the event
		$prefix .= 'Com' . ucfirst($this->container->bareComponentName) . 'Dispatcher';

		// The event name will be something like onComFoobarItemsBeforeSomething
		$event = $prefix . $event;

		// Call the Joomla! plugins
		$results = $this->container->platform->runPlugins($event, $arguments);

		return !in_array(false, $results, true);
	}

	/**
	 * Handles the transparent authentication log in
	 */
	protected function transparentAuthenticationLogin(): void
	{
		/** @var TransparentAuthentication $transparentAuth */
		$transparentAuth = $this->container->transparentAuth;
		$authInfo        = $transparentAuth->getTransparentAuthenticationCredentials();

		if (empty($authInfo))
		{
			return;
		}

		$this->isTransparentlyLoggedIn = $this->container->platform->loginUser($authInfo);
	}

	/**
	 * Handles the transparent authentication log out
	 */
	protected function transparentAuthenticationLogout(): void
	{
		if (!$this->isTransparentlyLoggedIn)
		{
			return;
		}

		/** @var TransparentAuthentication $transparentAuth */
		$transparentAuth = $this->container->transparentAuth;

		if (!$transparentAuth->getLogoutOnExit())
		{
			return;
		}

		$this->container->platform->logoutUser();
	}

	/**
	 * Adds support for akview/aktask in lieu of view and task.
	 *
	 * This is for future-proofing FOF in case Joomla assigns special meaning to view and task, e.g. by trying to find a
	 * specific controller / task class instead of letting the component's front-end router handle it. If that happens
	 * FOF components can have a single Joomla-compatible view/task which launches the Dispatcher and perform internal
	 * routing using akview/aktask.
	 *
	 * @return  void
	 * @since   3.6.3
	 */
	private function supportCustomViewAndTaskParameters()
	{
		$view = $this->input->getCmd('akview', null);
		$task = $this->input->getCmd('aktask', null);

		if (!is_null($view))
		{
			$this->input->remove('akview');
			$this->input->set('view', $view);
		}

		if (!is_null($task))
		{
			$this->input->remove('aktask');
			$this->input->set('task', $task);
		}
	}
}
