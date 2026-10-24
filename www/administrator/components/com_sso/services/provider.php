<?php
/**
 * @package    SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Rolandd\Component\Sso\Administrator\Extension\SsoComponent;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

/**
 * @since  1.0.0
 */
return new class implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function register(Container $container)
    {
        $container->registerServiceProvider(new MVCFactory('\\Rolandd\\Component\\Sso'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\Rolandd\\Component\\Sso'));
        $container->registerServiceProvider(new RouterFactory('\\Rolandd\\Component\\Sso'));

        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new SsoComponent($container->get(ComponentDispatcherFactoryInterface::class));

                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                $component->setRouterFactory($container->get(RouterFactoryInterface::class));

                $language = Factory::getApplication()->getLanguage();
                $language->load('com_sso', JPATH_ADMINISTRATOR . '/components/com_sso');

                return $component;
            }
        );
    }
};
