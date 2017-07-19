<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * Methods supporting a list of teams records.
 */
class VolunteersModelMembers extends JModelList
{
	/**
	 * Constructor.
	 *
	 * @param   array  An optional associative array of configuration settings.
	 *
	 * @see     JControllerLegacy
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.id',
				'team_id', 'a.team_id',
				'volunteer_id', 'a.volunteer_id',
				'role', 'a.role',
				'position', 'a.position',
				'checked_out', 'a.checked_out',
				'checked_out_time', 'a.checked_out_time',
				'state', 'a.state',
				'created', 'a.created',
				'created_by', 'a.created_by',
				'ordering', 'a.ordering',
				'featured', 'a.featured',
				'date_started', 'a.date_started',
				'date_ended', 'a.date_ended',
				'volunteer', 'a.volunteer',
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * @return  void
	 *
	 * @note    Calling getState in this method will result in recursion.
	 */
	protected function populateState($ordering = 'team.title', $direction = 'asc')
	{
		// Load the filter state.
		$this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search'));
		$this->setState('filter.state', $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state'));
		$this->setState('filter.department', $this->getUserStateFromRequest($this->context . '.filter.department', 'filter_department'));
		$this->setState('filter.team', $this->getUserStateFromRequest($this->context . '.filter.team', 'filter_team'));
		$this->setState('filter.volunteer', $this->getUserStateFromRequest($this->context . '.filter.volunteer', 'filter_volunteer'));
		$this->setState('filter.position', $this->getUserStateFromRequest($this->context . '.filter.position', 'filter_position'));
		$this->setState('filter.active', $this->getUserStateFromRequest($this->context . '.filter.active', 'filter_active'));
		$this->setState('filter.type', $this->getUserStateFromRequest($this->context . '.filter.type', 'filter_type'));

		// Load the parameters.
		$params = JComponentHelper::getParams('com_volunteers');
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
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.state');
		$id .= ':' . $this->getState('filter.department');
		$id .= ':' . $this->getState('filter.volunteer');
		$id .= ':' . $this->getState('filter.active');
		$id .= ':' . $this->getState('filter.type');

		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  JDatabaseQuery
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query
			->select($this->getState('list.select', array('a.*')))
			->from($db->quoteName('#__volunteers_members') . ' AS a');

		// Join over the volunteers.
		$query->select('volunteer.image AS volunteer_image, volunteer.address AS volunteer_address, volunteer.city AS volunteer_city, volunteer.zip AS volunteer_zip, volunteer.country AS volunteer_country')
			->join('LEFT', '#__volunteers_volunteers AS ' . $db->quoteName('volunteer') . ' ON volunteer.id = a.volunteer');

		// Join over the teams.
		$query->select('department.title AS department_title, department.parent_id AS department_parent_id')
			->join('LEFT', '#__volunteers_departments AS ' . $db->quoteName('department') . ' ON department.id = a.department');

		// Join over the teams.
		$query->select('team.title AS team_title')
			->join('LEFT', '#__volunteers_teams AS ' . $db->quoteName('team') . ' ON team.id = a.team');

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
		$state = $this->getState('filter.state', 1);

		if (is_numeric($state))
		{
			$query->where('a.state = ' . (int) $state);
		}

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
				$query->where('user.name LIKE ' . $search);
			}
		}

		// Filter by department
		$department = $this->getState('filter.department');

		if (is_numeric($department) && ($department > 0))
		{
			$query->where('a.department = ' . (int) $department);
		}

		// Filter by team
		$team = $this->getState('filter.team');

		if (is_array($team))
		{
			$query->where('a.team IN (' . implode($team, ',') . ')');
		}

		if (is_numeric($team) && ($team > 0))
		{
			$query->where('a.team = ' . (int) $team);
		}

		// Filter by volunteer
		$volunteer = $this->getState('filter.volunteer');

		if (is_numeric($volunteer) && ($volunteer > 0))
		{
			$query->where('a.volunteer = ' . (int) $volunteer);
		}

		// Filter by position
		$position = $this->getState('filter.position');

		if (is_array($position))
		{
			$query->where('a.position IN (' . implode($position, ',') . ')');
		}

		if (is_numeric($position) && ($position > 0))
		{
			$query->where('a.position = ' . (int) $position);
		}

		// Filter by active
		$active = $this->getState('filter.active');

		if (is_numeric($active))
		{
			$nullDate = $db->quote($db->getNullDate());

			if ($active == 1)
			{
				$query->where('a.date_ended = ' . $nullDate);
			}

			if ($active == 0)
			{
				$query->where('a.date_ended != ' . $nullDate);
			}
		}

		// Filter by type
		$type = $this->getState('filter.type');

		if ($type == 'department')
		{
			$query->where('a.department <> 0');
		}

		if ($type == 'team')
		{
			$query->where('a.team <> 0');
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'team.title');
		$orderDirn = $this->state->get('list.direction', 'asc');

		$query->order($db->escape($orderCol . ' ' . $orderDirn));

		return $query;
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 */
	public function getItems()
	{
		$items = parent::getItems();

		if ($items) foreach ($items as $item)
		{
			$item->address = false;

			// Check if address is filled in
			if ($item->volunteer_address && $item->volunteer_city && $item->volunteer_zip && $item->volunteer_country)
			{
				$item->address = true;
			}

			unset($item->volunteer_address);
			unset($item->volunteer_city);
			unset($item->volunteer_zip);
		}

		return $items;
	}
}
