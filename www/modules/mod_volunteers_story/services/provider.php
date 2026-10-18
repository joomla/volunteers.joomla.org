<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2005 - 2024 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\Service\Provider\HelperFactory;
use Joomla\CMS\Extension\Service\Provider\Module;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new ModuleDispatcherFactory('\\Joomla\\Module\\VolunteersStory'));
        $container->registerServiceProvider(new HelperFactory('\\Joomla\\Module\\VolunteersStory\\Site\\Helper'));
        $container->registerServiceProvider(new Module());
    }
};
