<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Render;

defined('_JEXEC') || die;

use FOF40\Container\Container;
use stdClass;

interface RenderInterface
{
	/**
	 * Public constructor
	 *
	 * @param   Container  $container  The container we are attached to
	 */
	function __construct(Container $container);

	/**
	 * Returns the information about this renderer
	 *
	 * @return object
	 */
	function getInformation(): stdClass;

	/**
	 * Performs initialisation.
	 *
	 * This is where you load your CSS and JavaScript frameworks. Only load the common code that the view's static
	 * assets will definitely depend on.
	 *
	 * This runs at the top of the View's display() method, before ony onBefore* handlers. Any files inserted to the
	 * Joomla document / WebAssetManager by this method CAN plausibly be removed by code in the view or any view event
	 * handlers in plugins.
	 *
	 * @param   string  $view  The current view
	 * @param   string  $task  The current task
	 *
	 * @return  void
	 * @since   4.0.0
	 */
	function initialise(string $view, string $task): void;

	/**
	 * Echoes any HTML to show before the view template
	 *
	 * @param   string  $view  The current view
	 * @param   string  $task  The current task
	 *
	 * @return  void
	 */
	function preRender(string $view, string $task): void;

	/**
	 * Echoes any HTML to show after the view template
	 *
	 * @param   string  $view  The current view
	 * @param   string  $task  The current task
	 *
	 * @return  void
	 */
	function postRender(string $view, string $task): void;

	/**
	 * Renders the submenu (link bar) for a category view when it is used in a
	 * extension
	 *
	 * Note: this function has to be called from the addSubmenu function in
	 *         the ExtensionNameHelper class located in
	 *         administrator/components/com_ExtensionName/helpers/Extensionname.php
	 *
	 * @return  void
	 */
	function renderCategoryLinkbar(): void;

	/**
	 * Set a renderer option (depends on the renderer)
	 *
	 * @param   string  $key    The name of the option to set
	 * @param   string  $value  The value of the option
	 *
	 * @return  void
	 */
	function setOption(string $key, string $value): void;

	/**
	 * Set multiple renderer options at once (depends on the renderer)
	 *
	 * @param   array  $options  The options to set as key => value pairs
	 *
	 * @return  void
	 */
	function setOptions(array $options): void;

	/**
	 * Get the value of a renderer option
	 *
	 * @param   string  $key      The name of the parameter
	 * @param   mixed   $default  The default value to return if the parameter is not set
	 *
	 * @return  mixed  The parameter value
	 */
	function getOption(string $key, $default = null);
}
