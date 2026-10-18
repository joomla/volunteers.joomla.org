<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Site\Model;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Component\Volunteers\Administrator\Model\MembersModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Methods supporting a list of teams records.
 * @since 4.0.0
 */
class TeamsModel extends ListModel
{
    /**
     * @var \Joomla\CMS\Application\CMSApplicationInterface
     * @since  6.1.0
     */
    protected $app;

    /**
     * Constructor.
     *
     * @param   array                     $config  An optional associative array of configuration settings.
     * @param   MVCFactoryInterface|null  $factory
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
                'title',
                'a.title',
                'alias',
                'a.alias',
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

        $this->app = Factory::getApplication();
    }

    /**
     * Method to auto-populate the model state.
     *
     * @param   string  $ordering
     * @param   string  $direction
     *
     * @return  void
     *
     * @since   4.0.0
     * @throws  Exception
     * @note    Calling getState in this method will result in recursion.
     */
    protected function populateState($ordering = 'a.title', $direction = 'asc')
    {
        // Load the filter state.
        $this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search'));
        $this->setState('filter.state', $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state'));
        $this->setState('filter.department', $this->getUserStateFromRequest($this->context . '.filter.department', 'filter_department'));
        $this->setState('filter.active', $this->getUserStateFromRequest($this->context . '.filter.active', 'filter_active'));
        $this->setState('filter.parent', $this->getUserStateFromRequest($this->context . '.filter.parent', 'filter_parent'));
        $deptid = $this->app->input->getInt('id');
        if ($deptid === 58) {
            $this->setState('filter.groups', $this->app->input->getInt('id'));
        }

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
        $id .= ':' . $this->getState('filter.department');
        $id .= ':' . $this->getState('filter.active');
        $id .= ':' . $this->getState('filter.groups');

        return parent::getStoreId($id);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @return  QueryInterface
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
            ->from($db->quoteName('#__volunteers_teams', 'a'));

        // Join over the users for the checked_out user.
        $query
            ->select($db->quoteName('checked_out.name', 'editor'))
            ->join('LEFT', $db->quoteName('#__users', 'checked_out') . ' ON ' . $db->quoteName('checked_out.id') . ' = ' . $db->quoteName('a.checked_out'));

        // Join over the departments.
        $query
            ->select($db->quoteName('department.title', 'department_title'))
            ->join('LEFT', $db->quoteName('#__volunteers_departments', 'department') . ' ON ' . $db->quoteName('department.id') . ' = ' . $db->quoteName('a.department'));

        // Self-join over the parent team.
        $query
            ->select($db->quoteName('parentteam.title', 'parent_title'))
            ->join('LEFT', $db->quoteName('#__volunteers_teams', 'parentteam') . ' ON ' . $db->quoteName('parentteam.id') . ' = ' . $db->quoteName('a.parent_id'));

        // Filter by published state
        $state = $this->getState('filter.state', 1);

        if (is_numeric($state)) {
            $query->where($db->quoteName('a.state') . ' = :state')
                ->bind(':state', $state, ParameterType::INTEGER);
        }

        // Filter by search in title
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            if (stripos((string) $search, 'id:') === 0) {
                $id = (int) substr((string) $search, 3);
                $query->where($db->quoteName('a.id') . ' = :id')
                    ->bind(':id', $id, ParameterType::INTEGER);
            } else {
                $search = '%' . str_replace(' ', '%', $db->escape(trim((string) $search), true) . '%');
                $query->where('(' . $db->quoteName('a.title') . ' LIKE :search OR ' . $db->quoteName('a.alias') . ' LIKE :search)')
                    ->bind(':search', $search);
            }
        }

        // Filter by groups
        $groups = $this->getState('filter.groups');

        if (is_numeric($groups) && ($groups > 0)) {
            $query->where($db->quoteName('a.department') . ' = :groups')
                ->bind(':groups', $groups, ParameterType::INTEGER);
        } else {
            $query->where($db->quoteName('a.department') . ' <> 58');
        }

        // Filter by department
        $department = $this->getState('filter.department');
        if (is_numeric($department) && ($department > 0)) {
            $query->where($db->quoteName('a.department') . ' = :department')
                ->bind(':department', $department, ParameterType::INTEGER);
        }

        // Filter by active state
        $frontend = $this->app->isClient('site');
        $active   = $this->getState('filter.active', ($frontend) ? 1 : null);

        if (is_numeric($active)) {
            if ($active == 1) {
                $query->where($db->quoteName('a.date_ended') . ' IS NULL');
            }

            if ($active == 0) {
                $query->where($db->quoteName('a.date_ended') . ' IS NOT NULL');
            }
        }

        // Filter by subteams
        $subteams = $this->getState('filter.subteams');

        if (!$subteams) {
            $query->where($db->quoteName('a.parent_id') . ' = 0');
        }

        // Filter by parent
        $parent = $this->getState('filter.parent');

        if (is_array($parent)) {
            $query->whereIn($db->quoteName('a.parent_id'), (array) $parent);
        }

        if (is_numeric($parent) && ($parent > 0)) {
            $query->where($db->quoteName('a.parent_id') . ' = :parent')
                ->bind(':parent', $parent, ParameterType::INTEGER);
        }

        // Group by ID
        $query->group($db->quoteName('a.id'));

        // Add the list ordering clause.
        $orderCol  = $this->state->get('list.ordering', 'a.title');
        $orderDirn = $this->state->get('list.direction', 'asc');

        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }

    /**
     * Method to get an array of data items.
     *
     * @return  array  An array of data items on success, false on failure.
     * @since 4.0.0
     * @throws Exception
     */
    public function getItems()
    {
        $items = parent::getItems();

        $teams   = [];
        $teamIds = [];

        foreach ($items as $item) {
            $teamIds[]                  = $item->id;
            $teams[$item->id]           = $item;
            $teams[$item->id]->members  = [];
            $teams[$item->id]->subteams = [];
        }

        // Get Subteams
        $subteams = $this->getSubteams();

        // Add Subteams
        foreach ($subteams as $subteam) {
            if (isset($teams[$subteam->parent_id])) {
                $teams[$subteam->parent_id]->subteams[] = $subteam;
            }
        }

        // Get members
        /** @var MembersModel $members */
        $members = $this->getMVCFactory()->createModel('Members', 'Administrator', ['ignore_request' => true]);

        $members->setState('filter.active', 1);
        $members->setState('filter.type', 'team');
        $members->setState('filter.team', $teamIds);
        $members->setState('filter.private', 1);
        $members = $members->getItems();

        if (!empty($members)) {
            foreach ($members as $member) {
                if (isset($teams[$member->team])) {
                    $teams[$member->team]->members[] = $member;
                }
            }
        }

        return $teams;
    }

    /**
     * @param   null  $parent
     * @param   bool  $getmembers
     *
     * @return mixed
     *
     * @since version
     */
    public function getSubteams($parent = null, bool $getmembers = false)
    {
        $db = $this->getDatabase();

        $query = $db->createQuery();

        $query
            ->select('*')
            ->from($db->quoteName('#__volunteers_teams'));

        if (!$parent) {
            $query->where($db->quoteName('parent_id') . ' > 0');
        }

        if (is_array($parent)) {
            $query->whereIn($db->quoteName('parent_id'), (array) $parent);
        }

        if (is_numeric($parent) && ($parent > 0)) {
            $query->where($db->quoteName('parent_id') . ' = :parent')
                ->bind(':parent', $parent, ParameterType::INTEGER);
        }

        // Only active teams
        $query->where($db->quoteName('date_ended') . ' IS NULL');

        $query->order($db->quoteName('title') . ' ASC');

        $db->setQuery($query);

        return $db->loadObjectList();
    }
}
