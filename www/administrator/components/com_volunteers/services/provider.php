<?php

/**
 * @version    4.0.0
 * @package    Com_Volunteers
 * @author     The Joomla Project <secretary@opensourcematters.org>
 * @copyright  2023 The Joomla Project
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\CategoryFactory;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\HTML\Registry;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Component\Volunteers\Administrator\Extension\VolunteersComponent;
use Joomla\Component\Volunteers\Administrator\Service\AclService;
use Joomla\Component\Volunteers\Administrator\Service\VolunteersService;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

/**
 * The Volunteers service provider.
 *
 * @since  4.0.0
 */
return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since  4.0.0
     */
    public function register(Container $container)
    {

        $container->registerServiceProvider(new CategoryFactory('\\Joomla\\Component\\Volunteers'));
        $container->registerServiceProvider(new MVCFactory('\\Joomla\\Component\\Volunteers'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\Joomla\\Component\\Volunteers'));
        $container->registerServiceProvider(new RouterFactory('\\Joomla\\Component\\Volunteers'));

        $container->set(
            AclService::class,
            function (Container $container) {
                return new AclService(
                    Factory::getApplication(),
                    $container->get(MVCFactoryInterface::class)
                );
            }
        );

        $container->set(
            VolunteersService::class,
            function (Container $container) {
                return new VolunteersService(
                    $container->get(DatabaseInterface::class),
                    Factory::getApplication()
                );
            }
        );

        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new VolunteersComponent($container->get(ComponentDispatcherFactoryInterface::class));

                $component->setRegistry($container->get(Registry::class));
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                $component->setCategoryFactory($container->get(CategoryFactoryInterface::class));
                $component->setRouterFactory($container->get(RouterFactoryInterface::class));
                //$component->setDatabase($container->get(DatabaseInterface::class));
                //$component->setApplication($container->get(CMSApplicationInterface::class));

                return $component;
            }
        );
    }
};
