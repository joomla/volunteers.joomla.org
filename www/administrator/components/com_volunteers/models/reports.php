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
class VolunteersModelReports extends JModelList
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
				'title', 'a.title',
				'alias', 'a.alias',
				'checked_out', 'a.checked_out',
				'checked_out_time', 'a.checked_out_time',
				'state', 'a.state',
				'created', 'a.created',
				'created_by', 'a.created_by',
				'ordering', 'a.ordering',
				'featured', 'a.featured',
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
	protected function populateState($ordering = 'a.created', $direction = 'desc')
	{
		// Load the filter state.
		$this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search'));
		$this->setState('filter.state', $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state'));
		$this->setState('filter.team', $this->getUserStateFromRequest($this->context . '.filter.team', 'filter_team'));
		$this->setState('filter.department', $this->getUserStateFromRequest($this->context . '.filter.department', 'filter_department'));
		$this->setState('filter.category', $this->getUserStateFromRequest($this->context . '.filter.category', 'filter_category'));

		// Load the parameters.
		$params = JComponentHelper::getParams('com_volunteers');
		$this->setState('params', $params);

		// List state information.
		parent::populateState($ordering, $direction);

		// List Limit
		$this->setState('list.limit', $this->getUserStateFromRequest($this->context . '.list.limit', 'limit', $this->get('list_limit', 15), 'uint'));
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
		$id .= ':' . $this->getState('filter.team');
		$id .= ':' . $this->getState('filter.department');
		$id .= ':' . $this->getState('filter.category');

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
			->from($db->quoteName('#__volunteers_reports') . ' AS a');

		// Join over the users for the checked_out user.
		$query
			->select('checked_out.name AS editor')
			->join('LEFT', '#__users AS ' . $db->quoteName('checked_out') . ' ON checked_out.id = a.checked_out');

		// Join over the users for the author name.
		$query
			->select('author.name AS author_name')
			->join('LEFT', '#__users AS ' . $db->quoteName('author') . ' ON author.id = a.created_by');

		// Join over the volunteers
		$query
			->select('volunteer.image AS volunteer_image, volunteer.id AS volunteer_id')
			->join('LEFT', '#__volunteers_volunteers AS ' . $db->quoteName('volunteer') . ' ON volunteer.user_id = a.created_by');

		// Join over the teams.
		$query->select('department.title AS department_title')
			->join('LEFT', '#__volunteers_departments AS ' . $db->quoteName('department') . ' ON department.id = a.department');

		// Join over the teams.
		$query
			->select('team.title AS team_title')
			->join('LEFT', '#__volunteers_teams AS ' . $db->quoteName('team') . ' ON team.id = a.team');

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
				$query->where('(a.title LIKE ' . $search . ' OR a.alias LIKE ' . $search . ')');
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

		if (is_numeric($team) && ($team > 0))
		{
			$query->where('a.team = ' . (int) $team);
		}

		// Filter by category
		$category = $this->getState('filter.category');

		if ($category)
		{
			$selection = explode('.', $category);

			if ($selection[0] == 'd')
			{
				$query->where('a.department = ' . (int) $selection[1]);
			}

			if ($selection[0] == 't')
			{
				$query->where('a.team = ' . (int) $selection[1]);
			}
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'a.created');
		$orderDirn = $this->state->get('list.direction', 'desc');

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

		if (JFactory::getApplication()->isSite())
		{
			foreach ($items as $item)
			{
				if ($item->department)
				{
					$item->acl = VolunteersHelper::acl('department', $item->department);
				}
				elseif ($item->team)
				{
					$item->acl = VolunteersHelper::acl('team', $item->team);
				}
			}
		}

		return $items;
	}
}
