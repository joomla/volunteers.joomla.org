<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Administrator\Extension;


use Exception;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Categories\CategoryServiceInterface;
use Joomla\CMS\Categories\CategoryServiceTrait;
use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Dispatcher\DispatcherInterface;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Tag\TagServiceInterface;
use Joomla\CMS\Tag\TagServiceTrait;
use Joomla\Component\Volunteers\Administrator\Service\Html\Volunteers;
use Psr\Container\ContainerInterface;
use stdClass;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Component class for com_volunteers
 *
 * @since  4.0.0
 */
class VolunteersComponent extends MVCComponent implements
    BootableExtensionInterface,
    CategoryServiceInterface,
    RouterServiceInterface,
    TagServiceInterface
{
    use HTMLRegistryAwareTrait;
    use RouterServiceTrait;
    use CategoryServiceTrait, TagServiceTrait {
        CategoryServiceTrait::getTableNameForSection insteadof TagServiceTrait;
        CategoryServiceTrait::getStateColumnForSection insteadof TagServiceTrait;
    }

    /**
     * @var ContainerInterface
     * @since  6.1.0
     */
    protected $container;

    /**
     * Booting the extension. This is the function to set up the environment of the extension like
     * registering new class loaders, etc.
     *
     * If required, some initial set up can be done from services of the container, e.g.
     * registering HTML services.
     *
     * @param   ContainerInterface  $container  The container
     *
     * @return  void
     *
     * @since  4.0.0
     */
    public function boot(ContainerInterface $container)
    {
        $this->container = $container;
        $this->getRegistry()->register('volunteers', new Volunteers());
    }

    /**
     * Returns the container.
     *
     * @return  ContainerInterface
     *
     * @since   6.1.0
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Returns the table for the count items functions for the given section.
     *
     * @param   string|null  $section  The section
     *
     * @return string
     *
     * @since  4.0.0
     */
    protected function getTableNameForSection(string $section = null)
    {
        return '';
    }


    /**
     * Adds Count Items for Category Manager.
     *
     * @param   stdClass[]  $items    The category objects
     * @param   string       $section  The section
     *
     * @return  void
     *
     * @since   4.0.0
     * @throws  Exception
     */
    public function countItems(array $items, string $section)
    {
        $config = (object) [
            'related_tbl'   => $this->getTableNameForSection($section),
            'state_col'     => $this->getStateColumnForSection($section),
            'group_col'     => 'primary_category_id',
            'relation_type' => 'category_or_group',
        ];

        ContentHelper::countRelations($items, $config);
    }

    /**
     * Returns the dispatcher for the given application.
     *
     * @param   CMSApplicationInterface  $application  The application
     *
     * @return  DispatcherInterface
     *
     * @since   4.0.0
     */
    public function getDispatcher(CMSApplicationInterface $application): DispatcherInterface
    {
        return parent::getDispatcher($application);
    }
}
