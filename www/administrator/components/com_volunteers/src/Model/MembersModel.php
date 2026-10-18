<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Administrator\Model;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Methods supporting a list of members records.
 * @since 4.0.0
 */
class MembersModel extends ListModel
{
    /**
     * @var \Joomla\CMS\Application\CMSApplicationInterface
     * @since  6.1.0
     */
    protected $app;

    /**
     * Constructor.
     *
     * @param   array                $config   An optional associative array of configuration settings.
     * @param   MVCFactoryInterface  $factory  The factory.
     *
     * @since   4.0.0
     * @throws  Exception
     */
    public function __construct($config = [], MVCFactoryInterface $factory = null)
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id',
                'a.id',
                'team_id',
                'a.team_id',
                'volunteer_id',
                'a.volunteer_id',
                'role',
                'a.role',
                'position',
                'a.position',
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
                'date_started',
                'a.date_started',
                'date_ended',
                'a.date_ended',
                'volunteer',
                'a.volunteer',
            ];
        }

        parent::__construct($config, $factory);

        $this->app = Factory::getApplication();
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
        $items = parent::getItems();
        if ($items) {
            foreach ($items as $item) {
                $item->address = false;
                $item->guest   = false;

                // Check if address is filled in
                if ($item->osmAddress) {
                    $item->address = true;
                }

                if ((str_contains((string) $item->user_email, 'identity.joomla.org'))) {
                    $item->guest = true;
                }

                unset($item->volunteer_address);
                unset($item->volunteer_city);
                unset($item->volunteer_zip);
            }
        }

        return $items;
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
            ->from($db->quoteName('#__volunteers_members') . ' AS a');

        // Join over the volunteers.
        $query->select('volunteer.image AS volunteer_image, volunteer.osmAddress AS osmAddress, volunteer.nda AS nda, volunteer.city AS volunteer_city, volunteer.country AS volunteer_country')
            ->join('LEFT', '#__volunteers_volunteers AS ' . $db->quoteName('volunteer') . ' ON volunteer.id = a.volunteer');

        // Join over the departments.
        $query->select('department.title AS department_title, department.parent_id AS department_parent_id')
            ->join('LEFT', '#__volunteers_departments AS ' . $db->quoteName('department') . ' ON department.id = a.department');

        // Join over the teams.
        $query->select('team.title AS team_title, team.status AS team_status')
            ->join('LEFT', '#__volunteers_teams AS ' . $db->quoteName('team') . ' ON team.id = a.team');

        // Join over the teams.
        $query->select('teamdepartment.title AS teamdepartment_title')
            ->join('LEFT', '#__volunteers_departments AS ' . $db->quoteName('teamdepartment') . ' ON teamdepartment.id = team.department');

        // Join over the positions.
        $query->select('position.title AS position_title')
            ->join('LEFT', '#__volunteers_positions AS ' . $db->quoteName('position') . ' ON position.id = a.position');

        // Join over the roles.
        $query->select('role.title AS role_title')
            ->join('LEFT', '#__volunteers_roles AS ' . $db->quoteName('role') . ' ON role.id = a.role');

        // Join over the users.
        $query
            ->select('user.name AS volunteer_name, user.email AS user_email')
            ->join('LEFT', '#__users AS ' . $db->quoteName('user') . ' ON user.id = volunteer.user_id');

        // Join over the users for the checked_out user.
        $query
            ->select('checked_out.name AS editor')
            ->join('LEFT', '#__users AS ' . $db->quoteName('checked_out') . ' ON checked_out.id = a.checked_out');

        // Filter by published state
        $state = $this->getState('filter.state');

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
                $query->where($db->quoteName('user.name') . ' LIKE :search')
                    ->bind(':search', $search);
            }
        }

        // Filter by department
        $department = $this->getState('filter.department');

        if (is_numeric($department) && ($department > 0)) {
            $query->where($db->quoteName('a.department') . ' = :department')
                ->bind(':department', $department, ParameterType::INTEGER);
        }

        // Filter by department teams
        $departmentTeam = $this->getState('filter.departmentTeam');

        if (is_numeric($departmentTeam) && ($departmentTeam > 0)) {
            // Special handling for board of directors
            if ($departmentTeam == 58) {
                $query->where($db->quoteName('a.position') . ' IN (11,13)');
            } else {
                $query->where($db->quoteName('team.department') . ' = :departmentTeam')
                    ->bind(':departmentTeam', $departmentTeam, ParameterType::INTEGER);
            }
        }

        // Filter by team
        $team = $this->getState('filter.team');

        if (is_array($team)) {
            $query->whereIn($db->quoteName('a.team'), (array) $team);
        }

        if (is_numeric($team) && ($team > 0)) {
            $query->where($db->quoteName('a.team') . ' = :team')
                ->bind(':team', $team, ParameterType::INTEGER);
        }

        // Filter by volunteer
        $volunteer = $this->getState('filter.volunteer');

        if (is_numeric($volunteer) && ($volunteer > 0)) {
            $query->where($db->quoteName('a.volunteer') . ' = :volunteer')
                ->bind(':volunteer', $volunteer, ParameterType::INTEGER);
        }

        // Filter by position
        $position = $this->getState('filter.position');

        if (is_array($position)) {
            $query->whereIn($db->quoteName('a.position'), (array) $position);
        }

        if (is_numeric($position) && ($position > 0)) {
            $query->where($db->quoteName('a.position') . ' = :position')
                ->bind(':position', $position, ParameterType::INTEGER);
        }

        // Filter by team status
        $teamStatus = $this->getState('filter.teamStatus');

        if (is_numeric($teamStatus)) {
            $query->where($db->quoteName('team.status') . ' = :teamStatus')
                ->bind(':teamStatus', $teamStatus, ParameterType::INTEGER);
        }

        // Filter by OSM status
        $osmStatus = $this->getState('filter.osmStatus');
        $positions = [];
        if ($osmStatus) {
            switch ($osmStatus) {
                case 'contributor':
                    $positions = [8];
                    break;
                case 'member':
                    $positions = [1, 2, 7];
                    break;
                case 'teamleaders':
                    $positions = [2];
                    break;
                case 'directors':
                    $positions = [11];
                    break;
                case 'officers':
                    $positions = [13];
                    break;
            }

            $query
                ->whereIn($db->quoteName('a.position'), $positions)
                ->where($db->quoteName('team.parent_id') . ' = 0')
                ->where($db->quoteName('team.department') . ' != 58');
        }

        // Filter private profile on frontend
        $filterGuests = $this->getState('filter.private');

        if ($filterGuests) {
            $query->where($db->quoteName('user.email') . ' NOT LIKE ' . $db->quote('%identity.joomla.org%'));
        }

        // Filter by active
        $active = $this->getState('filter.active');

        if (is_numeric($active)) {
            if ($active == 1) {
                $query->where($db->quoteName('a.date_ended') . ' IS NULL');
            }

            if ($active == 0) {
                $query->where($db->quoteName('a.date_ended') . ' IS NOT NULL');
            }
        }

        // Filter by type
        $type = $this->getState('filter.type');

        if ($type === 'department') {
            $query->where('a.department <> 0');
        }

        if ($type === 'team') {
            $query->where('a.team <> 0');
        }

        // Add the list ordering clause.
        $orderCol  = $this->state->get('list.ordering', 'a.id');
        $orderDirn = $this->state->get('list.direction', 'desc');

        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        return $query;
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
        $id .= ':' . $this->getState('filter.departmentTeam');
        $id .= ':' . $this->getState('filter.volunteer');
        $id .= ':' . $this->getState('filter.active');
        $id .= ':' . $this->getState('filter.type');
        $id .= ':' . $this->getState('filter.teamStatus');
        $id .= ':' . $this->getState('filter.osmStatus');

        return parent::getStoreId($id);
    }

    /**
     * Method to auto-populate the model state.
     *
     * @return  void
     *
     * @note    Calling getState in this method will result in recursion.
     * @since   4.0.0
     */
    protected function populateState($ordering = 'a.id', $direction = 'desc')
    {
        // Load the filter state.
        $this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search'));
        $this->setState('filter.state', $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state'));
        $this->setState('filter.department', $this->getUserStateFromRequest($this->context . '.filter.department', 'filter_department'));
        $this->setState('filter.departmentTeam', $this->getUserStateFromRequest($this->context . '.filter.departmentTeam', 'filter_departmentTeam'));
        $this->setState('filter.team', $this->getUserStateFromRequest($this->context . '.filter.team', 'filter_team'));
        $this->setState('filter.volunteer', $this->getUserStateFromRequest($this->context . '.filter.volunteer', 'filter_volunteer'));
        $this->setState('filter.position', $this->getUserStateFromRequest($this->context . '.filter.position', 'filter_position'));
        $this->setState('filter.active', $this->getUserStateFromRequest($this->context . '.filter.active', 'filter_active'));
        $this->setState('filter.type', $this->getUserStateFromRequest($this->context . '.filter.type', 'filter_type'));
        $this->setState('filter.teamStatus', $this->getUserStateFromRequest($this->context . '.filter.teamStatus', 'filter_teamStatus'));
        $this->setState('filter.osmStatus', $this->getUserStateFromRequest($this->context . '.filter.osmStatus', 'filter_osmStatus'));

        // Load the parameters.
        $params = ComponentHelper::getParams('com_volunteers');
        $this->setState('params', $params);

        // List state information.
        parent::populateState($ordering, $direction);
    }

}
