<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Administrator\Model;

use Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Methods supporting a list of departments records.
 * @since 4.0.0
 */
class DepartmentsModel extends ListModel
{
    /**
     * Method to auto-populate the model state.
     *
     * @return  void
     *
     * @note    Calling getState in this method will result in recursion.
     * @since   4.0.0
     */
    protected function populateState($ordering = 'a.ordering', $direction = 'asc')
    {
        // Load the filter state.
        $this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search'));
        $this->setState('filter.state', $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state'));

        // Load the parameters.
        $params = ComponentHelper::getParams('com_volunteers');
        $this->setState('params', $params);

        // List state information.
        parent::populateState($ordering, $direction);
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * This is necessary because the model is used by the component and
     * different modules that might need different sets of data or different
     * ordering requirements.
     *
     * @param   string  $id  A prefix for the store id.
     *
     * @return  string  A store id.
     * @since 4.0.0
     */
    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.state');

        return parent::getStoreId($id);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @return QueryInterface
     * @since 4.0.0
     * @throws Exception
     */
    protected function getListQuery()
    {

        // Create a new query object.
        $db    = $this->getDatabase();
        $query = $db->createQuery();

        // Select the required fields from the table.
        $query
            ->select($this->getState('list.select', ['a.*']))
            ->from($db->quoteName('#__volunteers_departments') . ' AS a');

        // Join over the users for the checked_out user.
        $query
            ->select('checked_out.name AS editor')
            ->join('LEFT', '#__users AS ' . $db->quoteName('checked_out') . ' ON checked_out.id = a.checked_out');

        // Filter by published state
        $state = $this->getState('filter.state', 1);

        if (is_numeric($state)) {
            $query->where('a.state = ' . (int) $state);
        }

        // Filter by search in title
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            if (stripos((string) $search, 'id:') === 0) {
                $query->where('a.id = ' . (int) substr((string) $search, 3));
            } else {
                $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim((string) $search), true) . '%'));
                $query->where('(a.title LIKE ' . $search . ' OR a.alias LIKE ' . $search . ')');
            }
        }

        // Filter by active state
        $frontend = Factory::getApplication()->isClient('site');
        $board    = ($frontend ? 0 : 1);

        if (!$board) {
            $query->where('a.parent_id != 0');
        }

        // Add the list ordering clause.
        $orderCol  = $this->state->get('list.ordering', 'a.ordering');
        $orderDirn = $this->state->get('list.direction', 'asc');

        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }

    /**
     * Method to get an array of data items.
     *
     * @return  mixed  An array of data items on success, false on failure.
     * @since 4.0.0
     * @throws Exception
     */
    public function getItems()
    {

        $items    = parent::getItems();
        $frontend = Factory::getApplication()->isClient('site');
        if ($frontend) {
            $departments = [];
            foreach ($items as $item) {
                $departments[$item->id]          = $item;
                $departments[$item->id]->members = [];
            }

            // Get members

            $model = $this->getMVCFactory()->createModel('Members', 'Administrator', ['ignore_request' => true]);
            $model->setState('filter.position', [11, 13]);
            $model->setState('filter.active', 1);
            $model->setState('filter.type', 'department');
            $members = $model->getItems();

            foreach ($members as $member) {
                if (isset($departments[$member->department])) {
                    $departments[$member->department]->members[] = $member;
                }
            }

            $items = $departments;
        }

        return $items;
    }

    /**
     * Constructor.
     *
     * @param array $config  An optional associative array of configuration settings.
* @param $factory MVCFactoryInterface
     *
     * @see     JController
     * @since   4.0.0
     * @throws Exception
     */
    public function __construct($config = [], MVCFactoryInterface $factory = null)
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'title', 'a.title',
                'alias', 'a.alias',
                'checked_out', 'a.checked_out',
                'checked_out_time', 'a.checked_out_time',
                'state', 'a.state',
                'created', 'a.created',
                'created_by', 'a.created_by',
                'ordering', 'a.ordering',
                'featured', 'a.featured',
            ];
        }

        parent::__construct($config, $factory);
    }
}
