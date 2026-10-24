<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Administrator\Finder;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\Finder as FinderEvent;
use Joomla\Component\Finder\Administrator\Indexer\Adapter;
use Joomla\Component\Finder\Administrator\Indexer\Result;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\QueryInterface;
use Joomla\Event\SubscriberInterface;

/**
 * Shared Smart Search adapter for the Volunteers Portal content types.
 *
 * The volunteers tables have no categories or access levels, every item is
 * public and only its state decides if it shows up in the search results.
 *
 * @since  6.1.0
 */
abstract class VolunteersAdapter extends Adapter implements SubscriberInterface
{
    use DatabaseAwareTrait;

    /**
     * The extension name.
     *
     * @var    string
     * @since  6.1.0
     */
    protected $extension = 'com_volunteers';

    /**
     * Load the language file on instantiation.
     *
     * @var    boolean
     * @since  6.1.0
     */
    protected $autoloadLanguage = true;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   6.1.0
     */
    public static function getSubscribedEvents(): array
    {
        return array_merge(parent::getSubscribedEvents(), [
            'onFinderAfterDelete' => 'onFinderAfterDelete',
            'onFinderAfterSave'   => 'onFinderAfterSave',
            'onFinderChangeState' => 'onFinderChangeState',
        ]);
    }

    /**
     * Remove an item from the index after it is deleted.
     *
     * @param   FinderEvent\AfterDeleteEvent  $event  The event instance.
     *
     * @return  void
     *
     * @since   6.1.0
     */
    public function onFinderAfterDelete(FinderEvent\AfterDeleteEvent $event): void
    {
        $context = $event->getContext();
        $table   = $event->getItem();

        if ($context === $this->extension . '.' . $this->layout) {
            $id = $table->id;
        } elseif ($context === 'com_finder.index') {
            $id = $table->link_id;
        } else {
            return;
        }

        $this->remove($id);
    }

    /**
     * Reindex an item after it is saved.
     *
     * @param   FinderEvent\AfterSaveEvent  $event  The event instance.
     *
     * @return  void
     *
     * @since   6.1.0
     */
    public function onFinderAfterSave(FinderEvent\AfterSaveEvent $event): void
    {
        if ($event->getContext() === $this->extension . '.' . $this->layout) {
            $this->reindex($event->getItem()->id);
        }
    }

    /**
     * Update the index when the state of an item changes or the plugin is disabled.
     *
     * @param   FinderEvent\AfterChangeStateEvent  $event  The event instance.
     *
     * @return  void
     *
     * @since   6.1.0
     */
    public function onFinderChangeState(FinderEvent\AfterChangeStateEvent $event): void
    {
        $context = $event->getContext();

        if ($context === $this->extension . '.' . $this->layout) {
            $this->itemStateChange($event->getPks(), $event->getValue());
        }

        if ($context === 'com_plugins.plugin' && $event->getValue() === 0) {
            $this->pluginDisable($event->getPks());
        }
    }

    /**
     * Method to index an item.
     *
     * @param   Result  $item  The item to index as a Result object.
     *
     * @return  void
     *
     * @since   6.1.0
     */
    protected function index(Result $item)
    {
        if (ComponentHelper::isEnabled($this->extension) === false) {
            return;
        }

        $item->setLanguage();

        $item->url    = $this->getUrl($item->id, $this->extension, $this->layout);
        $item->route  = $item->url;
        $item->state  = $this->translateState($item->state);
        $item->access = 1;

        $item->addTaxonomy('Type', $this->type_title);

        $this->indexer->index($item);
    }

    /**
     * Method to set up the indexer to be run.
     *
     * @return  boolean  True on success.
     *
     * @since   6.1.0
     */
    protected function setup()
    {
        return true;
    }

    /**
     * Method to get a SQL query to load the published state of an item.
     *
     * @return  QueryInterface
     *
     * @since   6.1.0
     */
    protected function getStateQuery()
    {
        return $this->getDatabase()->createQuery()
            ->select('a.id, a.' . $this->state_field . ' AS state, 1 AS cat_state, 1 AS access, 1 AS cat_access')
            ->from($this->table . ' AS a');
    }
}
