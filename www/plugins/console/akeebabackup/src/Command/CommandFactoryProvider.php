<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\Console\AkeebaBackup\Command;

defined('_JEXEC') || die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

class CommandFactoryProvider implements ServiceProviderInterface
{
	public function register(Container $container)
	{
		$container->set(
			CommandFactoryInterface::class,
			function (Container $container) {
				$factory = new CommandFactory();

				$factory->setMVCFactory($container->get(MVCFactoryInterface::class));
				$factory->setDatabase($container->get(DatabaseInterface::class));
				$factory->setApplication(Factory::getApplication());

				return $factory;
			}
		);
	}
}