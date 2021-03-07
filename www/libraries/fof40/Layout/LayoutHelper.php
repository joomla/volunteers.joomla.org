<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Layout;

defined('_JEXEC') || die;

use FOF40\Container\Container;

class LayoutHelper
{
	/**
	 * A default base path that will be used if none is provided when calling the render method.
	 * Note that FileLayout itself will default to JPATH_ROOT . '/layouts' if no basePath is supplied at all
	 *
	 * @var    string
	 */
	public static $defaultBasePath = '';

	/**
	 * Method to render the layout.
	 *
	 * @param   Container  $container    The container of your component
	 * @param   string     $layoutFile   Dot separated path to the layout file, relative to base path
	 * @param   array      $displayData  Array with values to be used inside the layout file to build displayed output
	 * @param   string     $basePath     Base path to use when loading layout files
	 *
	 * @return  string
	 */
	public static function render(Container $container, string $layoutFile, array $displayData = [], string $basePath = ''): string
	{
		$basePath = empty($basePath) ? self::$defaultBasePath : $basePath;

		// Make sure we send null to LayoutFile if no path set
		$basePath          = empty($basePath) ? null : $basePath;
		$layout            = new LayoutFile($layoutFile, $basePath);
		$layout->container = $container;

		return $layout->render($displayData);
	}

}
