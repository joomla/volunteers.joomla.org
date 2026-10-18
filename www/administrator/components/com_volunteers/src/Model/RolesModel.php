<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Administrator\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;

/**
 * Methods supporting a list of roles records.
 * @since 4.0.0
 */
class RolesModel extends ListModel
{
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
                'id',
                'a.id',
                'team',
                'a.team',
                'checked_out',
                'a.checked_out',
                'checked_out_time',
                'a.checked_out_time',
                'state',
                'a.state',
                'created',
                'a.created',
                'created_by',
                'a.created_by',
                'ordering',
                'a.ordering',
                'featured',
                'a.featured',
            ];
        }

        parent::__construct($config, $factory);
    }

    /**
     * Method to auto-populate the model state.
     *
     * @return  void
     *
     * @note    Calling getState in this method will result in recursion.
     * @since 4.0.0
     */
    protected function populateState($ordering = 'a.title', $direction = 'asc')
    {
        // Load the filter state.
        $this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search'));
        $this->setState('filter.state', $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state'));
        $this->setState('filter.team', $this->getUserStateFromRequest($this->context . '.filter.team', 'filter_team'));

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
     * @param   string $id A prefix for the store id.
     *
     * @return  string  A store id.
     * @since 4.0.0
     */
    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.state');
        $id .= ':' . $this->getState('filter.team');

        return parent::getStoreId($id);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @return  QueryInterface
     * @since 4.0.0
     */
    protected function getListQuery()
    {
        // Create a new query object.
        $db    = $this->getDatabase();
        $query = $db->createQuery();

        // Select the required fields from the table.
        $query
            ->select($this->getState('list.select', ['a.*']))
            ->from($db->quoteName('#__volunteers_roles') . ' AS a');

        // Join over the teams.
        $query->select('team.title AS team_title')
            ->join('LEFT', '#__volunteers_teams AS ' . $db->quoteName('team') . ' ON team.id = a.team');

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
                $query->where('(a.title LIKE ' . $search . ')');
            }
        }

        // Filter by team
        $team = $this->getState('filter.team');

        if (is_numeric($team) && ($team > 0)) {
            $query->where('a.team = ' . (int) $team);
        }

        // Add the list ordering clause.
        $orderCol  = $this->state->get('list.ordering', 'a.title');
        $orderDirn = $this->state->get('list.direction', 'asc');

        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }

    /**
     * Method to get an array of open roles.
     *
     * @return  mixed  An array of data items on success, false on failure.
     * @since 4.0.0
     */
    public function getOpenRoles()
    {
        // Create a new query object.
        $db = $this->getDatabase();

        // Get the list query and add the extra WHERE clause.
        $query = $this->getListQuery();
        $query->where('a.open = 1');

        // Get the item to index.
        $db->setQuery($query);
        $items = $db->loadObjectList();

        $roles = [];

        foreach ($items as $item) {
            $roles[$item->team_title][] = $item;
        }

        // Sort by team name
        ksort($roles);

        return $roles;
    }
}
