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
 * Report model.
 */
class VolunteersModelReport extends JModelAdmin
{
	/**
	 * The type alias for this content type.
	 *
	 * @var    string
	 */
	public $typeAlias = 'com_volunteers.report';

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
	public function getTable($type = 'Report', $prefix = 'VolunteersTable', $config = array())
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
		$form = $this->loadForm('com_volunteers.report', 'report', array('control' => 'jform', 'load_data' => $loadData));

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
		$data = JFactory::getApplication()->getUserState('com_volunteers.edit.report.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		$this->preprocessData('com_volunteers.report', $data);

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
					->from($db->quoteName('#__volunteers_reports'));

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
					->from('#__volunteers_reports AS a')
					->where('a.id = ' . (int) $pk);

				// Join on volunteer table.
				$query->select('volunteer.id AS volunteer_id, volunteer.image AS volunteer_image')
					->join('LEFT', '#__volunteers_volunteers AS ' . $db->quoteName('volunteer') . ' on volunteer.user_id = a.created_by');

				// Join over the users for the related user.
				$query
					->select('user.name AS volunteer_name')
					->join('LEFT', '#__users AS ' . $db->quoteName('user') . ' ON user.id = a.created_by');

				// Join on department table.
				$query->select('department.title AS department_title, department.parent_id AS department_parent_id')
					->join('LEFT', '#__volunteers_departments AS ' . $db->quoteName('department') . ' on department.id = a.department');

				// Join on team table.
				$query->select('team.title AS team_title')
					->join('LEFT', '#__volunteers_teams AS ' . $db->quoteName('team') . ' on team.id = a.team');

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
					JError::raiseError(404, JText::_('COM_VOLUNTEERS_ERROR_REPORT_NOT_FOUND'));
				}

				// Check for published state if filter set.
				if (((is_numeric($published)) || (is_numeric($archived))) && (($data->published != $published) && ($data->published != $archived)))
				{
					JError::raiseError(404, JText::_('COM_VOLUNTEERS_ERROR_REPORT_NOT_FOUND'));
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
	 * Method to get volunteer info.
	 *
	 * @param   integer $pk The id of the team.
	 *
	 * @return  mixed  Data object on success, false on failure.
	 */
	public function getVolunteer()
	{
		// Get user
		$user = JFactory::getUser();

		// Get subteams
		$model = JModelLegacy::getInstance('Volunteer', 'VolunteersModel', array('ignore_request' => true));
		$volunteerId = $model->getVolunteerId($user->id);

		return $model->getItem($volunteerId);
	}
}
