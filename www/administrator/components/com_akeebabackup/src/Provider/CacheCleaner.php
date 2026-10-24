<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * @package     Akeeba\Component\AkeebaBackup\Administrator\Provider
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Provider;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Service\CacheCleaner as CacheCleanerService;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

class CacheCleaner implements ServiceProviderInterface
{
	public function register(Container $container)
	{
		$container->set(
			CacheCleanerService::class,
			function (Container $container) {
				$app = Factory::getApplication();

				return new CacheCleanerService(
					$app,
					$container->get(CacheControllerFactoryInterface::class)
				);
			}
		);
	}
}