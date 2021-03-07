<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Factory;

defined('_JEXEC') || die;

use FOF40\Container\Container;
use FOF40\Controller\Controller;
use FOF40\Dispatcher\Dispatcher;
use FOF40\Model\Model;
use FOF40\Toolbar\Toolbar;
use FOF40\TransparentAuthentication\TransparentAuthentication;
use FOF40\View\View;
use FOF40\View\ViewTemplateFinder;

/**
 * Interface for the MVC object factory
 */
interface FactoryInterface
{
	/**
	 * Public constructor for the factory object
	 *
	 * @param Container $container The container we belong to
	 */
	public function __construct(Container $container);

	/**
	 * Create a new Controller object
	 *
	 * @param string $viewName The name of the view we're getting a Controller for.
	 * @param array  $config   Optional MVC configuration values for the Controller object.
	 *
	 * @return  Controller
	 */
	public function controller(string $viewName, array $config = []): Controller;

	/**
	 * Create a new Model object
	 *
	 * @param string $viewName The name of the view we're getting a Model for.
	 * @param array  $config   Optional MVC configuration values for the Model object.
	 *
	 * @return  Model
	 */
	public function model(string $viewName, array $config = []): Model;

	/**
	 * Create a new View object
	 *
	 * @param string $viewName The name of the view we're getting a View object for.
	 * @param string $viewType The type of the View object. By default it's "html".
	 * @param array  $config   Optional MVC configuration values for the View object.
	 *
	 * @return  View
	 */
	public function view(string $viewName, $viewType = 'html', array $config = []): View;

	/**
	 * Creates a new Toolbar
	 *
	 * @param array $config The configuration values for the Toolbar object
	 *
	 * @return  Toolbar
	 */
	public function toolbar(array $config = []): Toolbar;

	/**
	 * Creates a new Dispatcher
	 *
	 * @param array $config The configuration values for the Dispatcher object
	 *
	 * @return  Dispatcher
	 */
	public function dispatcher(array $config = []): Dispatcher;

	/**
	 * Creates a new TransparentAuthentication handler
	 *
	 * @param array $config The configuration values for the TransparentAuthentication object
	 *
	 * @return  TransparentAuthentication
	 */
	public function transparentAuthentication(array $config = []): TransparentAuthentication;

	/**
	 * Creates a view template finder object for a specific View
	 *
	 * @param View  $view   The view this view template finder will be attached to
	 * @param array $config Configuration variables for the object
	 *
	 * @return  ViewTemplateFinder
	 */
	public function viewFinder(View $view, array $config = []): ViewTemplateFinder;

	/**
	 * @return string
	 */
	public function getSection(): string;

	/**
	 * @param string $section
	 */
	public function setSection(string $section): void;
}
