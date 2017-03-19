<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\Registry\Registry;

/**
 * Department model.
 */
class VolunteersModelBoard extends JModelAdmin
{
	/**
	 * The type alias for this content type.
	 *
	 * @var    string
	 */
	public $typeAlias = 'com_volunteers.board';

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
	public function getTable($type = 'Department', $prefix = 'VolunteersTable', $config = array())
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
		$form = $this->loadForm('com_volunteers.department', 'department', array('control' => 'jform', 'load_data' => $loadData));

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
		$data = JFactory::getApplication()->getUserState('com_volunteers.edit.department.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		$this->preprocessData('com_volunteers.department', $data);

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
					->from($db->quoteName('#__volunteers_departments'));

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
	 * Method to get Department Members.
	 *
	 * @param   integer $pk The id of the team.
	 *
	 * @return  mixed  Data object on success, false on failure.
	 */
	public function getDepartmentMembers($pk = null)
	{
		// Get members
		$model = JModelLegacy::getInstance('Members', 'VolunteersModel', array('ignore_request' => true));
		$model->setState('filter.position', array(11, 13));
		$items = $model->getItems();

		// Sorting the results
		$president     = array();
		$vicepresident = array();
		$secretary     = array();
		$treasurer     = array();
		$coordinator   = array();

		foreach ($items as $item)
		{
			switch ($item->role)
			{
				case 286:
					$president['president-'.$item->volunteer_name . $item->date_ended] = $item;
					break;

				case 287:
					$vicepresident['vicepresident-'.$item->volunteer_name . $item->date_ended] = $item;
					break;

				case 288:
					$secretary['secretary-'.$item->volunteer_name . $item->date_ended] = $item;
					break;

				case 289:
					$treasurer['treasurer-'.$item->volunteer_name . $item->date_ended] = $item;
					break;
			}

			switch ($item->position)
			{
				case 11:
					$coordinator[$item->volunteer_name . $item->date_ended] = $item;
					break;
			}
		}



		// Sort all members by name
		ksort($coordinator);

		// Group them again
		$groupmembers = $president + $vicepresident + $secretary + $treasurer + $coordinator;

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
	 * Method to get Department Reports.
	 *
	 * @param   integer $pk The id of the team.
	 *
	 * @return  mixed  Data object on success, false on failure.
	 */
	public function getDepartmentReports($pk = null)
	{
		$pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');

		// Get reports
		$model = JModelLegacy::getInstance('Reports', 'VolunteersModel', array('ignore_request' => true));
		$model->setState('filter.department', $pk);
		$model->setState('list.limit', 10);

		return $model->getItems();
	}
}
