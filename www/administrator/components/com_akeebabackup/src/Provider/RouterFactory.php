<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Provider;

defined('_JEXEC') or die;

use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

class RouterFactory implements ServiceProviderInterface
{
	/**
	 * The module namespace
	 *
	 * @var     string
	 *
	 * @since   5.0.0
	 */
	private $namespace;

	/**
	 * Router factory constructor.
	 *
	 * @param   string  $namespace  The namespace
	 *
	 * @since   5.0.0
	 */
	public function __construct(string $namespace)
	{
		$this->namespace = $namespace;
	}

	/**
	 * @inheritDoc
	 */
	public function register(Container $container)
	{
		$container->set(
			RouterFactoryInterface::class,
			function (Container $container) {
				return new \Akeeba\Component\AkeebaBackup\Administrator\Router\RouterFactory(
					$this->namespace,
					$container->get(MVCFactoryInterface::class)
				);
			}
		);
	}

}