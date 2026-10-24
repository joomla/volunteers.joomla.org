<?php
/**
 * @package     SSO.Plugin
 * @subpackage  Authentication.sso
 *
 * @copyright   Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') || die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Rolandd\Plugin\Authentication\Sso\Extension\Sso;

return new class implements ServiceProviderInterface {
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function register(Container $container)
	{
		$container->set(
			PluginInterface::class,

			function (Container $container) {
                $dispatcher = $container->get(DispatcherInterface::class);
				$config  = (array) PluginHelper::getPlugin('authentication', 'sso');

				$plugin = new Sso($dispatcher, $config);
                $plugin->setApplication(Factory::getApplication());

                $language = Factory::getApplication()->getLanguage();
                $language->load('plg_authentication_sso', JPATH_SITE . '/plugins/authentication/sso');

                return $plugin;
			}
		);
	}
};
