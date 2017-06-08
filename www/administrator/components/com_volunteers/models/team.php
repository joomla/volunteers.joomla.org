<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

/**
 * Team model.
 */
class VolunteersModelTeam extends JModelAdmin
{
	/**
	 * The type alias for this content type.
	 *
	 * @var    string
	 */
	public $typeAlias = 'com_volunteers.team';

	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 */
	protected $text_prefix = 'COM_VOLUNTEERS';

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string $type   The table name. Optional.
	 * @param   string $prefix The class prefix. Optional.
	 * @param   array  $config Configuration array for model. Optional.
	 *
	 * @return  JTable  A JTable object
	 */
	public function getTable($type = 'Team', $prefix = 'VolunteersTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Abstract method for getting the form from the model.
	 *
	 * @param   array   $data     Data for the form.
	 * @param   boolean $loadData True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A JForm object on success, false on failure
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_volunteers.team', 'team', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		// Modify the form based on access controls.
		if (!$this->canEditState((object) $data))
		{
			// Disable fields for display.
			$form->setFieldAttribute('ordering', 'disabled', 'true');
			$form->setFieldAttribute('state', 'disabled', 'true');

			// Disable fields while saving.
			$form->setFieldAttribute('ordering', 'filter', 'unset');
			$form->setFieldAttribute('state', 'filter', 'unset');
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  array  The default data is an empty array.
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_volunteers.edit.team.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		$this->preprocessData('com_volunteers.team', $data);

		return $data;
	}

	/**
	 * Prepare and sanitise the table data prior to saving.
	 *
	 * @param   JTable $table A reference to a JTable object.
	 *
	 * @return  void
	 */
	protected function prepareTable($table)
	{
		$date = JFactory::getDate();
		$user = JFactory::getUser();

		$table->title = htmlspecialchars_decode($table->title, ENT_QUOTES);
		$table->alias = JApplicationHelper::stringURLSafe($table->alias);

		if (empty($table->alias))
		{
			$table->alias = JApplicationHelper::stringURLSafe($table->title);
		}

		if (empty($table->id))
		{
			// Set the values

			// Set ordering to the last item if not set
			if (empty($table->ordering))
			{
				$db    = $this->getDbo();
				$query = $db->getQuery(true)
					->select('MAX(ordering)')
					->from($db->quoteName('#__volunteers_teams'));

				$db->setQuery($query);
				$max = $db->loadResult();

				$table->ordering = $max + 1;
			}
			else
			{
				// Set the values
				$table->modified    = $date->toSql();
				$table->modified_by = $user->id;
			}
		}

		// Increment the version number.
		$table->version++;
	}


	/**
	 * Method to save the form data.
	 *
	 * @param   array $data The form data.
	 *
	 * @return  boolean  True on success.
	 */
	public function save($data)
	{
		$app = JFactory::getApplication();

		// Alter the title for save as copy
		if ($app->input->get('task') == 'save2copy')
		{
			list($name, $alias) = $this->generateNewTitle(0, $data['alias'], $data['title']);
			$data['title'] = $name;
			$data['alias'] = $alias;
			$data['state'] = 0;
		}

		// Move team members to the honour roll if team end-date is set
		if ($data['date_ended'])
		{
			$members    = $this->getTeamMembers($data['id']);
			$membersIds = array_map(create_function('$member', 'return $member->id;'), $members->active);

			if (count($membersIds))
			{
				// Set date_ended for active members
				$db    = $this->getDbo();
				$query = $db->getQuery(true);
				$query
					->update('#__volunteers_members')
					->set('date_ended = ' . $db->quote($data['date_ended']))
					->where('id IN (' . implode($membersIds, ',') . ')');

				try
				{
					$db->setQuery($query)->execute();
				}
				catch (RuntimeException $e)
				{
					JError::raiseError(500, $e->getMessage());
				}
			}
		}

		return parent::save($data);
	}

	/**
	 * Method to change the title & alias.
	 *
	 * @param   integer $category_id The id of the parent.
	 * @param   string  $alias       The alias.
	 * @param   string  $name        The title.
	 *
	 * @return  array  Contains the modified title and alias.
	 */
	protected function generateNewTitle($category_id, $alias, $name)
	{
		// Alter the title & alias
		$table = $this->getTable();

		while ($table->load(array('alias' => $alias)))
		{
			if ($name == $table->title)
			{
				$name = String::increment($name);
			}

			$alias = String::increment($alias, 'dash');
		}

		return array($name, $alias);
	}

	/**
	 * Method to get team data.
	 *
	 * @param   integer $pk The id of the team.
	 *
	 * @return  mixed  Data object on success, false on failure.
	 */
	public function &getItem($pk = null)
	{
		$pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');

		$item = new JObject;

		if ($pk > 0)
		{
			try
			{
				$db    = $this->getDbo();
				$query = $db->getQuery(true)
					->select($this->getState('item.select', 'a.*'))
					->from('#__volunteers_teams AS a')
					->where('a.id = ' . (int) $pk);

				// Join on department table.
				$query->select('department.title AS department_title')
					->join('LEFT', '#__volunteers_departments AS ' . $db->quoteName('department') . ' on department.id = a.department');

				// Self-join over the parent team.
				$query
					->select('parentteam.title AS parent_title')
					->join('LEFT', '#__volunteers_teams AS ' . $db->quoteName('parentteam') . ' ON parentteam.id = a.parent_id');

				// Filter by published state.
				$published = $this->getState('filter.published');
				$archived  = $this->getState('filter.archived');

				if (is_numeric($published))
				{
					$query->where('(a.published = ' . (int) $published . ' OR a.published =' . (int) $archived . ')')
						->where('(c.published = ' . (int) $published . ' OR c.published =' . (int) $archived . ')');
				}

				$db->setQuery($query);

				$data = $db->loadObject();

				if (empty($data))
				{
					JError::raiseError(404, JText::_('COM_VOLUNTEERS_ERROR_TEAM_NOT_FOUND'));
				}

				// Check for published state if filter set.
				if (((is_numeric($published)) || (is_numeric($archived))) && (($data->published != $published) && ($data->published != $archived)))
				{
					JError::raiseError(404, JText::_('COM_VOLUNTEERS_ERROR_TEAM_NOT_FOUND'));
				}

				return $data;
			}
			catch (Exception $e)
			{
				$this->setError($e);

				return false;
			}
		}

		// Convert to the JObject before adding other data.
		$properties = $this->getTable()->getProperties(1);
		$item       = ArrayHelper::toObject($properties, 'JObject');

		return $item;
	}


	/**
	 * Method to get Team Members.
	 *
	 * @param   integer $pk The id of the team.
	 *
	 * @return  mixed  Data object on success, false on failure.
	 */
	public function getTeamMembers($pk = null)
	{
		$pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');

		// Get members
		$model = JModelLegacy::getInstance('Members', 'VolunteersModel', array('ignore_request' => true));
		$model->setState('filter.team', $pk);
		$items = $model->getItems();

		// Sorting the results
		$leaders    = array();
		$assistants = array();
		$volunteers = array();

		foreach ($items as $item)
		{
			switch ($item->position)
			{
				case 2:
					$leaders[$item->volunteer_name . $item->date_ended] = $item;
					break;

				case 7:
					$assistants[$item->volunteer_name . $item->date_ended] = $item;
					break;

				default:
					$volunteers[$item->volunteer_name . $item->date_ended] = $item;
					break;
			}
		}

		// Sort all members by name
		ksort($leaders);
		ksort($assistants);
		ksort($volunteers);

		// Group them again
		$groupmembers = $leaders + $assistants + $volunteers;

		$members            = new stdClass();
		$members->active    = array();
		$members->honorroll = array();

		// Check for active or inactive members
		foreach ($groupmembers as $item)
		{
			if ($item->date_ended == '0000-00-00')
			{
				$members->active[] = $item;
			}
			else
			{
				$members->honorroll[] = $item;
			}
		}

		return $members;
	}

	/**
	 * Method to get Team Roles.
	 *
	 * @param   integer $pk The id of the team.
	 *
	 * @return  mixed  Data object on success, false on failure.
	 */
	public function getTeamRoles($pk = null)
	{
		$pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');

		// Get roles
		$model = JModelLegacy::getInstance('Roles', 'VolunteersModel', array('ignore_request' => true));
		$model->setState('filter.team', $pk);
		$roles = $model->getItems();

		// Order by id
		$teamroles = array();
		foreach ($roles as $role)
		{
			$teamroles[$role->id] = $role;
		}

		$members = $this->getTeamMembers($pk);

		// Attach Joomlers to the roles
		foreach ($members->active as $member)
		{
			if ($member->role)
			{
				$teamroles[$member->role]->volunteers[] = $member;
			}
		}

		return $teamroles;
	}

	/**
	 * Method to get Team Reports.
	 *
	 * @param   integer $pk The id of the team.
	 *
	 * @return  mixed  Data object on success, false on failure.
	 */
	public function getTeamReports($pk = null)
	{
		$pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');

		// Get reports
		$model = JModelLegacy::getInstance('Reports', 'VolunteersModel', array('ignore_request' => true));
		$model->setState('filter.team', $pk);
		$model->setState('list.limit', 10);

		return $model->getItems();
	}

	/**
	 * Method to get Team Subteams.
	 *
	 * @param   integer $pk The id of the team.
	 *
	 * @return  mixed  Data object on success, false on failure.
	 */
	public function getTeamSubteams($pk = null)
	{
		$pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');

		// Get subteams
		$model = JModelLegacy::getInstance('Teams', 'VolunteersModel', array('ignore_request' => true));
		$model->setState('filter.subteams', true);
		$model->setState('filter.parent', $pk);
		$model->setState('list.limit', 0);

		return $model->getItems();
	}
}
