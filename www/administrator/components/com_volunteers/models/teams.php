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
class VolunteersModelTeams extends JModelList
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
	protected function populateState($ordering = 'a.title', $direction = 'asc')
	{
		// Load the filter state.
		$this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search'));
		$this->setState('filter.state', $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state'));
		$this->setState('filter.department', $this->getUserStateFromRequest($this->context . '.filter.department', 'filter_department'));
		$this->setState('filter.active', $this->getUserStateFromRequest($this->context . '.filter.active', 'filter_active'));
		$this->setState('filter.parent', $this->getUserStateFromRequest($this->context . '.filter.parent', 'filter_parent'));

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
		$id .= ':' . $this->getState('filter.active');

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
			->from($db->quoteName('#__volunteers_teams') . ' AS a');

		// Join over the users for the checked_out user.
		$query
			->select('checked_out.name AS editor')
			->join('LEFT', '#__users AS ' . $db->quoteName('checked_out') . ' ON checked_out.id = a.checked_out');

		// Join over the departments.
		$query
			->select('department.title AS department_title')
			->join('LEFT', '#__volunteers_departments AS ' . $db->quoteName('department') . ' ON department.id = a.department');

		// Self-join over the parent team.
		$query
			->select('parentteam.title AS parent_title')
			->join('LEFT', '#__volunteers_teams AS ' . $db->quoteName('parentteam') . ' ON parentteam.id = a.parent_id');

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

		// Filter by active state
		$active = $this->getState('filter.active', (JFactory::getApplication()->isSite()) ? 1 : null);

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

		// Filter by subteams
		$subteams = $this->getState('filter.subteams');

		if (!$subteams)
		{
			$query->where('a.parent_id = 0');
		}

		// Filter by parent
		$parent = $this->getState('filter.parent');

		if (is_array($parent))
		{
			$query->where('a.parent_id IN (' . implode($parent, ',') . ')');
		}

		if (is_numeric($parent) && ($parent > 0))
		{
			$query->where('a.parent_id = ' . (int) $parent);
		}

		// Group by ID
		$query->group('a.id');

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'a.title');
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

		if (JFactory::getApplication()->isSite())
		{
			$teams   = array();
			$teamIds = array();

			foreach ($items as $item)
			{
				$teamIds[]                  = $item->id;
				$teams[$item->id]           = $item;
				$teams[$item->id]->members  = array();
				$teams[$item->id]->subteams = array();
			}

			// Get Subteams
			$subteams = $this->getSubteams();

			// Add Subteams
			foreach ($subteams as $subteam)
			{
				if (isset($teams[$subteam->parent_id]))
				{
					$teams[$subteam->parent_id]->subteams[] = $subteam;
				}
			}

			// Get members
			$members = JModelLegacy::getInstance('Members', 'VolunteersModel', array('ignore_request' => true));
			$members->setState('filter.active', 1);
			$members->setState('filter.type', 'team');
			$members->setState('filter.team', $teamIds);
			$members = $members->getItems();

			if (!empty($members)) foreach ($members as $member)
			{
				if (isset($teams[$member->team]))
				{
					$teams[$member->team]->members[] = $member;
				}
			}

			$items = $teams;
		}

		return $items;
	}

	public function getSubteams($parent = null, $getmembers = false)
	{
		$db = $this->getDbo();

		$query = $db->getQuery(true);

		$query
			->select('*')
			->from('#__volunteers_teams');

		if (!$parent)
		{
			$query->where('parent_id > 0');
		}

		if (is_array($parent))
		{
			$query->where('parent_id IN (' . implode($parent, ',') . ')');
		}

		if (is_numeric($parent) && ($parent > 0))
		{
			$query->where('parent_id = ' . (int) $parent);
		}

		// Only active teams
		$nullDate = $db->quote($db->getNullDate());
		$query->where('date_ended = ' . $nullDate);

		$query->order('title ASC');

		$db->setQuery($query);

		$subteams = $db->loadObjectList();

		return $subteams;
	}
}
