<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Factory\Magic;

defined('_JEXEC') || die;

use FOF40\Factory\Exception\ViewNotFound;
use FOF40\View\View;

/**
 * Creates a DataModel/TreeModel object instance based on the information provided by the fof.xml configuration file
 */
class ViewFactory extends BaseFactory
{
	/**
	 * Create a new object instance
	 *
	 * @param   string  $name      The name of the class we're making
	 * @param   string  $viewType  The view type, default html, possible values html, form, raw, json, csv
	 * @param   array   $config    The config parameters which override the fof.xml information
	 *
	 * @return  View  A DataViewInterface view
	 */
	public function make(string $name = null, string $viewType = 'html', array $config = []): View
	{
		if (empty($name))
		{
			throw new ViewNotFound("[name : type] = [$name : $viewType]");
		}

		$appConfig = $this->container->appConfig;
		$name      = ucfirst($name);

		$defaultConfig = [
			'name'          => $name,
			'template_path' => $appConfig->get("views.$name.config.template_path"),
			'layout'        => $appConfig->get("views.$name.config.layout"),
			// You can pass something like .php => Class1, .foo.bar => Class 2
			'viewEngineMap' => $appConfig->get("views.$name.config.viewEngineMap"),
		];

		$config = array_merge($defaultConfig, $config);

		$className = $this->container->getNamespacePrefix($this->getSection()) . 'View\\DataView\\Default' . ucfirst($viewType);

		if (!class_exists($className, true))
		{
			$className = '\\FOF40\\View\\DataView\\' . ucfirst($viewType);
		}

		if (!class_exists($className, true))
		{
			$className = $this->container->getNamespacePrefix($this->getSection()) . 'View\\DataView\\DefaultHtml';
		}

		if (!class_exists($className))
		{
			$className = '\\FOF40\\View\\DataView\\Html';
		}

		return new $className($this->container, $config);
	}
}
