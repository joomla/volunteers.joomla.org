<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace FOF40\Factory\Magic;

defined('_JEXEC') || die;

use FOF40\TransparentAuthentication\TransparentAuthentication;

/**
 * Creates a TransparentAuthentication object instance based on the information provided by the fof.xml configuration
 * file
 */
class TransparentAuthenticationFactory extends BaseFactory
{
	/**
	 * Create a new object instance
	 *
	 * @param   array  $config  The config parameters which override the fof.xml information
	 *
	 * @return  TransparentAuthentication  A new TransparentAuthentication object
	 */
	public function make(array $config = []): TransparentAuthentication
	{
		$appConfig     = $this->container->appConfig;
		$defaultConfig = $appConfig->get('authentication.*');
		$config        = array_merge($defaultConfig, $config);

		$className = $this->container->getNamespacePrefix($this->getSection()) . 'TransparentAuthentication\\DefaultTransparentAuthentication';

		if (!class_exists($className, true))
		{
			$className = '\\FOF40\\TransparentAuthentication\\TransparentAuthentication';
		}

		return new $className($this->container, $config);
	}
}
