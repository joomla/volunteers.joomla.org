<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * Routing class from com_volunteers
 */
class VolunteersRouter extends JComponentRouterBase
{
	/**
	 * Build the route for the com_volunteers component
	 *
	 * @param   array &$query An array of URL arguments
	 *
	 * @return  array  The URL arguments to use to assemble the subsequent URL.
	 */
	public function build(&$query)
	{
		// Initialize variables.
		$segments = [];

		// Handle the view
		$view   = isset($query['view']) ? $query['view'] : null;
		$layout = isset($query['layout']) ? $query['layout'] : null;
		unset($query['view']);

		// DB
		$db = JFactory::getDbo();

		switch ($view)
		{
			case 'department':
				$dbQuery = $db->getQuery(true)
					->select('alias')
					->from('#__volunteers_departments')
					->where('id=' . (int) $query['id']);
				$db->setQuery($dbQuery);
				$alias = $db->loadResult();

				$segments[] = $alias;

				if ($layout == 'edit')
				{
					$segments[] = 'edit';
					unset($query['layout']);
				}

				$query['Itemid'] = $this->getItemid('departments');
				unset($query['id']);

				break;

			case 'board':
				$query['Itemid'] = $this->getItemid('board');
				unset($query['id']);

				break;

			case 'team':
				if (isset($query['id']))
				{
					$dbQuery = $db->getQuery(true)
						->select('alias')
						->from('#__volunteers_teams')
						->where('id=' . (int) $query['id']);
					$db->setQuery($dbQuery);
					$alias = $db->loadResult();

					$segments[] = $alias;

					if ($layout == 'edit')
					{
						$segments[] = 'edit';
						unset($query['layout']);
					}
				}
				else
				{
					$departmentId = JFactory::getApplication()->getUserState('com_volunteers.edit.team.departmentid');
					$teamId       = JFactory::getApplication()->getUserState('com_volunteers.edit.team.teamid');

					if ($teamId)
					{
						$dbQuery = $db->getQuery(true)
							->select($db->qn('alias'))
							->from('#__volunteers_teams')
							->where('id=' . (int) $teamId);
						$db->setQuery($dbQuery);

						$segments[] = $db->loadResult();
						$segments[] = 'subteam';
					}
					elseif ($departmentId)
					{
						$dbQuery = $db->getQuery(true)
							->select($db->qn('alias'))
							->from('#__volunteers_departments')
							->where('id=' . (int) $departmentId);
						$db->setQuery($dbQuery);

						$segments[] = $db->loadResult();
						$segments[] = 'team';
					}

					$segments[] = 'new';
					unset($query['layout']);
				}

				$query['Itemid'] = $this->getItemid('teams');
				unset($query['id']);

				break;

			case 'volunteer':
				$dbQuery = $db->getQuery(true)
					->select('alias')
					->from('#__volunteers_volunteers')
					->where('id=' . (int) $query['id']);
				$db->setQuery($dbQuery);
				$alias = $db->loadResult();

				$segments[] = $query['id'] . '-' . $alias;

				if ($layout == 'edit')
				{
					$segments[] = 'edit';
					unset($query['layout']);
				}

				$query['Itemid'] = $this->getItemid('volunteers');
				unset($query['id']);

				break;

			case 'member':
				if (isset($query['id']))
				{
					$dbQuery = $db->getQuery(true)
						->select(array($db->qn('department'), $db->qn('team')))
						->from('#__volunteers_members')
						->where('id=' . (int) $query['id']);
					$db->setQuery($dbQuery);
					$member = $db->loadObject();

					if ($member->department)
					{
						$dbQuery = $db->getQuery(true)
							->select($db->qn('alias'))
							->from('#__volunteers_departments')
							->where('id=' . (int) $member->department);
						$db->setQuery($dbQuery);
						$alias = $db->loadResult();

						$query['Itemid'] = $this->getItemid('departments');
					}

					if ($member->team)
					{
						$dbQuery = $db->getQuery(true)
							->select($db->qn('alias'))
							->from('#__volunteers_teams')
							->where('id=' . (int) $member->team);
						$db->setQuery($dbQuery);
						$alias = $db->loadResult();

						$query['Itemid'] = $this->getItemid('teams');
					}

					$segments[] = $alias;
					$segments[] = 'member';

					if ($layout == 'edit')
					{
						$segments[] = 'edit';
						unset($query['layout']);
					}

					$segments[] = $query['id'];
				}
				else
				{
					$departmentId = JFactory::getApplication()->getUserState('com_volunteers.edit.member.departmentid');
					$teamId       = JFactory::getApplication()->getUserState('com_volunteers.edit.member.teamid');

					if ($departmentId)
					{
						$dbQuery = $db->getQuery(true)
							->select($db->qn('alias'))
							->from('#__volunteers_departments')
							->where('id=' . (int) $departmentId);
						$db->setQuery($dbQuery);
						$alias = $db->loadResult();

						$query['Itemid'] = $this->getItemid('departments');
					}

					if ($teamId)
					{
						$dbQuery = $db->getQuery(true)
							->select($db->qn('alias'))
							->from('#__volunteers_teams')
							->where('id=' . (int) $teamId);
						$db->setQuery($dbQuery);
						$alias = $db->loadResult();

						$query['Itemid'] = $this->getItemid('teams');
					}

					$segments[] = $alias;
					$segments[] = 'member';
					$segments[] = 'new';
					unset($query['layout']);
				}
				unset($query['id']);

				break;

			case 'role':
				if (isset($query['id']))
				{
					$dbQuery = $db->getQuery(true)
						->select($db->qn('team'))
						->from('#__volunteers_roles')
						->where('id=' . (int) $query['id']);
					$db->setQuery($dbQuery);
					$teamId = $db->loadResult();

					$dbQuery = $db->getQuery(true)
						->select($db->qn('alias'))
						->from('#__volunteers_teams')
						->where('id=' . (int) $teamId);
					$db->setQuery($dbQuery);
					$teamAlias = $db->loadResult();

					$segments[] = $teamAlias;
					$segments[] = 'role';

					if ($layout == 'edit')
					{
						$segments[] = 'edit';
						unset($query['layout']);
					}

					$segments[] = $query['id'];
				}
				else
				{
					$teamId = JFactory::getApplication()->getUserState('com_volunteers.edit.role.teamid');

					$dbQuery = $db->getQuery(true)
						->select($db->qn('alias'))
						->from('#__volunteers_teams')
						->where('id=' . (int) $teamId);
					$db->setQuery($dbQuery);
					$teamAlias = $db->loadResult();

					$segments[] = $teamAlias;
					$segments[] = 'role';
					$segments[] = 'new';
					unset($query['layout']);
				}

				$query['Itemid'] = $this->getItemid('teams');
				unset($query['id']);

				break;

			case 'report':
				if (isset($query['id']))
				{
					$dbQuery = $db->getQuery(true)
						->select(array($db->qn('department'), $db->qn('team'), $db->qn('alias')))
						->from('#__volunteers_reports')
						->where('id=' . (int) $query['id']);
					$db->setQuery($dbQuery);
					$report = $db->loadObject();

					if ($report->department)
					{
						$dbQuery = $db->getQuery(true)
							->select('alias, parent_id')
							->from('#__volunteers_departments')
							->where('id=' . (int) $report->department);
						$db->setQuery($dbQuery);
						$item = $db->loadObject();

						if ($item->parent_id == 0)
						{
							$query['Itemid'] = $this->getItemid('board');
							$segments[]      = 'reports';
						}
						else
						{
							$query['Itemid'] = $this->getItemid('departments');
							$segments[]      = $item->alias;
							$segments[]      = 'reports';
						}
					}

					if ($report->team)
					{
						$dbQuery = $db->getQuery(true)
							->select($db->qn('alias'))
							->from('#__volunteers_teams')
							->where('id=' . (int) $report->team);
						$db->setQuery($dbQuery);
						$alias = $db->loadResult();

						$query['Itemid'] = $this->getItemid('teams');

						$segments[] = $alias;
						$segments[] = 'reports';
					}

					if ($layout == 'edit')
					{
						$segments[] = 'edit';
						unset($query['layout']);
					}

					$segments[] = $query['id'] . '-' . $report->alias;
				}
				else
				{
					$departmentId = JFactory::getApplication()->getUserState('com_volunteers.edit.report.departmentid');
					$teamId       = JFactory::getApplication()->getUserState('com_volunteers.edit.report.teamid');

					if ($departmentId)
					{
						$dbQuery = $db->getQuery(true)
							->select($db->qn('alias'))
							->from('#__volunteers_departments')
							->where('id=' . (int) $departmentId);
						$db->setQuery($dbQuery);
						$alias = $db->loadResult();

						$query['Itemid'] = $this->getItemid('departments');
					}

					if ($teamId)
					{
						$dbQuery = $db->getQuery(true)
							->select($db->qn('alias'))
							->from('#__volunteers_teams')
							->where('id=' . (int) $teamId);
						$db->setQuery($dbQuery);
						$alias = $db->loadResult();

						$query['Itemid'] = $this->getItemid('teams');
					}

					$segments[] = $alias;
					$segments[] = 'reports';
					$segments[] = 'new';
					unset($query['layout']);
				}
				unset($query['id']);

				break;

			case 'reports':
			case 'volunteers':
			case 'teams':
			case 'registration':
			case 'home':
			case 'roles':
				$query['Itemid'] = $this->getItemid($view);
		}

		return $segments;
	}

	/**
	 * Parse the segments of a URL.
	 *
	 * @param   array &$segments The segments of the URL to parse.
	 *
	 * @return  array  The URL attributes to be used by the application.
	 */
	public function parse(&$segments)
	{
		$vars = array();

		// Get the active menu item.
		$item = $this->menu->getActive();

		// Count route segments
		$count = count($segments);

		if (!$count)
		{
			return $vars;
		}

		// Database
		$db = JFactory::getDbo();

		// View
		$view = $item->query['view'];

		// Handle the View
		switch ($view)
		{
			case 'teams':
			case 'departments':
				// Members
				if (isset($segments[1]) && ($segments[1] == 'member'))
				{
					$vars['view'] = 'member';

					if (isset($segments[2]) && ($segments[2] == 'new'))
					{
						$vars['layout'] = 'edit';
					}

					if (isset($segments[2]) && ($segments[2] == 'edit'))
					{
						$vars['layout'] = 'edit';
						$vars['id']     = $segments[3];
					}
				}
				// Roles
				elseif (isset($segments[1]) && ($segments[1] == 'role'))
				{
					$vars['view'] = 'role';

					if (isset($segments[2]) && ($segments[2] == 'new'))
					{
						$vars['layout'] = 'edit';
					}

					if (isset($segments[2]) && ($segments[2] == 'edit'))
					{
						$vars['layout'] = 'edit';
						$vars['id']     = $segments[3];
					}
				}
				// Reports
				elseif (isset($segments[1]) && ($segments[1] == 'reports'))
				{
					$vars['view'] = 'report';

					if (isset($segments[2]) && ($segments[2] == 'new'))
					{
						$vars['layout'] = 'edit';
					}
					elseif (isset($segments[2]) && ($segments[2] == 'edit'))
					{
						$vars['layout'] = 'edit';
						list($id) = explode('-', $segments[3], 2);
						$vars['id'] = $id;
					}
					else
					{
						list($id) = explode('-', $segments[2], 2);
						$vars['id'] = $id;
					}
				}
				// Subteam
				elseif (isset($segments[1]) && ($segments[1] == 'subteam' || $segments[1] == 'team'))
				{
					$vars['view'] = 'team';

					if (isset($segments[2]) && ($segments[2] == 'new'))
					{
						$vars['layout'] = 'edit';
					}
				}
				else
				{
					$dbQuery = $db->getQuery(true)
						->select('id')
						->from('#__volunteers_' . $view)
						->where('alias=' . $db->quote($segments[0]));
					$db->setQuery($dbQuery);
					$id = $db->loadResult();

					if (!$id)
					{
						JError::raiseError(404, JText::_('JERROR_PAGE_NOT_FOUND'));
					}

					$vars['view'] = substr($view, 0, -1);
					$vars['id']   = $id;

					if (isset($segments[1]) && ($segments[1] == 'edit'))
					{
						$vars['layout'] = 'edit';
					}
				}

				break;

			case 'board':
				// Reports
				if (isset($segments[0]) && ($segments[0] == 'reports'))
				{
					$vars['view'] = 'report';

					list($id) = explode('-', $segments[1], 2);
					$vars['id'] = $id;

					if (isset($segments[1]) && ($segments[1] == 'new'))
					{
						$vars['layout'] = 'edit';
					}
					elseif (isset($segments[1]) && ($segments[1] == 'edit'))
					{
						$vars['layout'] = 'edit';
						list($id) = explode('-', $segments[2], 2);
						$vars['id'] = $id;
					}
					else
					{
						list($id) = explode('-', $segments[1], 2);
						$vars['id'] = $id;
					}
				}

				break;

			case 'volunteers':
				list($id) = explode('-', $segments[0], 2);
				if (!is_numeric($id))
				{
					$dbQuery = $db->getQuery(true)
						->select('id')
						->from('#__volunteers_' . $view)
						->where('alias=' . $db->quote($segments[0]));
					$db->setQuery($dbQuery);
					$id = $db->loadResult();

					if (!$id)
					{
						JError::raiseError(404, JText::_('JERROR_PAGE_NOT_FOUND'));
					}

					$vars['view'] = 'volunteer';
					$vars['id']   = $id;
				}
				else
				{
					$vars['view'] = 'volunteer';
					$vars['id']   = $id;
				}

				if (isset($segments[1]) && ($segments[1] == 'edit'))
				{
					$vars['layout'] = 'edit';
				}

				break;

			case 'home':
				if (isset($segments[0]))
				{
					JError::raiseError(404, JText::_('JERROR_PAGE_NOT_FOUND'));
				}
				break;
		}

		return $vars;
	}

	private function getItemid($view, $id = null)
	{
		// Get all relevant menu items.
		$items = $this->menu->getItems('component', 'com_volunteers');

		// ItemId
		$itemid = null;

		if ($id) foreach ($items as $item)
		{
			if ($item->query['view'] == $view && $item->query['id'] == $id)
			{
				$itemid = $item->id;
				break;
			}
		}

		if (empty($itemid)) foreach ($items as $item)
		{
			if ($item->query['view'] == $view)
			{
				$itemid = $query['Itemid'] = $item->id;
				break;
			}
		}

		return $itemid;
	}
}