<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Akeeba\Plugin\System\AKWarn\Extension\AKWarn;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;

return new class implements ServiceProviderInterface {
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   10.2.0
	 */
	public function register(Container $container)
	{
		$container->set(
			PluginInterface::class,
			function (Container $container) {
				$config     = (array) PluginHelper::getPlugin('system', 'akwarn');
				$dispatcher = $container->get(DispatcherInterface::class);

				$plugin = version_compare(JVERSION, '5.4.0', 'ge')
					? new AKWarn($config)
					: new AKWarn(
						$dispatcher, $config
					);

				$plugin->setApplication(Factory::getApplication());

				return $plugin;
			}
		);
	}
};
