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
class VolunteersModelVolunteers extends JModelList
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
				'alias', 'a.alias',
				'checked_out', 'a.checked_out',
				'checked_out_time', 'a.checked_out_time',
				'state', 'a.state',
				'created', 'a.created',
				'created_by', 'a.created_by',
				'ordering', 'a.ordering',
				'featured', 'a.featured',
				'username', 'user.username',
				'modified', 'a.modified',
				'num_teams', 'num_teams',
				'spam', 'a.spam',
				'birthday', 'a.birthday'
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
	protected function populateState($ordering = 'user.name', $direction = 'asc')
	{
		// Load the filter state.
		$this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search'));
		$this->setState('filter.state', $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state'));
		$this->setState('filter.image', $this->getUserStateFromRequest($this->context . '.filter.image', 'filter_image'));
		$this->setState('filter.joomlastory', $this->getUserStateFromRequest($this->context . '.filter.joomlastory', 'filter_joomlastory'));
		$this->setState('filter.location', $this->getUserStateFromRequest($this->context . '.filter.location', 'filter_location'));
		$this->setState('filter.coc', $this->getUserStateFromRequest($this->context . '.filter.coc', 'filter_coc'));

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
		$id .= ':' . $this->getState('filter.image');
		$id .= ':' . $this->getState('filter.joomlastory');
		$id .= ':' . $this->getState('filter.location');
		$id .= ':' . $this->getState('filter.coc');

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
			->from($db->quoteName('#__volunteers_volunteers') . ' AS a');

		// Join over the users for the checked_out user.
		$query
			->select('checked_out.name AS editor')
			->join('LEFT', '#__users AS ' . $db->quoteName('checked_out') . ' ON checked_out.id = a.checked_out');

		// Join over the users for the related user.
		$query
			->select('user.name AS name, user.username AS user_username, user.email AS user_email')
			->join('LEFT', '#__users AS ' . $db->quoteName('user') . ' ON user.id = a.user_id');

		// Self-join to count teams involved.
		$query->select('COUNT(DISTINCT member.id) AS num_teams')
			->join('LEFT', $db->quoteName('#__volunteers_members', 'member') . ' ON ' . $db->qn('member.volunteer') . ' = ' . $db->qn('a.id'));

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
				$query->where('(user.name LIKE ' . $search . ' OR a.alias LIKE ' . $search . ')');
			}
		}

		// Filter by image
		$image = $this->getState('filter.image');

		if ($image)
		{
			$query->where('a.image <> \'\'');
		}

		// Filter by joomlastory
		$joomlastory = $this->getState('filter.joomlastory');

		if ($joomlastory)
		{
			$query->where('a.joomlastory <> \'\'');
		}

		// Filter by location
		$location = $this->getState('filter.location');

		if ($location)
		{
			$query->where('a.latitude <> \'\'');
			$query->where('a.longitude <> \'\'');
		}

		// Filter by coc
		$coc = $this->getState('filter.coc');

		if (is_numeric($coc) && $coc == 1)
		{
			$query->where('a.coc = 1');
		}

		// Group by ID
		$query->group('a.id');

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'user.name');
		$orderDirn = $this->state->get('list.direction', 'asc');

		$query->order($db->escape($orderCol . ' ' . $orderDirn));

		return $query;
	}
}
