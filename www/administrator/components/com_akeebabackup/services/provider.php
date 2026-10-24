<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Extension\AkeebaBackupComponent;
use Akeeba\Component\AkeebaBackup\Administrator\Helper\JoomlaPublicFolder;
use Akeeba\Component\AkeebaBackup\Administrator\Provider\CacheCleaner;
use Akeeba\Component\AkeebaBackup\Administrator\Provider\ComponentParameters;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\HTML\Registry;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

/**
 * The banners service provider.
 *
 * @since  9.0.0
 */
return new class implements ServiceProviderInterface {
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   9.0.0
	 */
	public function register(Container $container)
	{
		$container->registerServiceProvider(new MVCFactory('Akeeba\\Component\\AkeebaBackup'));
		$container->registerServiceProvider(new ComponentDispatcherFactory('Akeeba\\Component\\AkeebaBackup'));
		$container->registerServiceProvider(new RouterFactory('\\Akeeba\\Component\\AkeebaBackup'));
		$container->registerServiceProvider(new CacheCleaner());
		$container->registerServiceProvider(new ComponentParameters('com_akeebabackup'));
		$container->registerServiceProvider(new \Akeeba\Component\AkeebaBackup\Administrator\Provider\RouterFactory('\\Akeeba\\Component\\AkeebaBackup'));

		$container->set(
			ComponentInterface::class,
			function (Container $container) {
				JoomlaPublicFolder::init();

				$component = new AkeebaBackupComponent($container->get(ComponentDispatcherFactoryInterface::class));

				$component->setRegistry($container->get(Registry::class));
				$component->setMVCFactory($container->get(MVCFactoryInterface::class));
				$component->setRouterFactory($container->get(RouterFactoryInterface::class));

				return $component;
			}
		);
	}
};
