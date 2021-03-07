<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Factory\Magic;

defined('_JEXEC') || die;

use FOF40\Dispatcher\Dispatcher;

/**
 * Creates a Dispatcher object instance based on the information provided by the fof.xml configuration file
 */
class DispatcherFactory extends BaseFactory
{
	/**
	 * Create a new object instance
	 *
	 * @param   array  $config  The config parameters which override the fof.xml information
	 *
	 * @return  Dispatcher  A new Dispatcher object
	 */
	public function make(array $config = []): Dispatcher
	{
		$appConfig     = $this->container->appConfig;
		$defaultConfig = $appConfig->get('dispatcher.*');
		$config        = array_merge($defaultConfig, $config);

		$className = $this->container->getNamespacePrefix($this->getSection()) . 'Dispatcher\\DefaultDispatcher';

		if (!class_exists($className, true))
		{
			$className = '\\FOF40\\Dispatcher\\Dispatcher';
		}

		return new $className($this->container, $config);
	}
}
