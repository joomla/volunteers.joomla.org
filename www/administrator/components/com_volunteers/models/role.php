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
 * Role model.
 */
class VolunteersModelRole extends JModelAdmin
{
	/**
	 * The type alias for this content type.
	 *
	 * @var    string
	 */
	public $typeAlias = 'com_volunteers.role';

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
	public function getTable($type = 'Role', $prefix = 'VolunteersTable', $config = array())
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
		$form = $this->loadForm('com_volunteers.role', 'role', array('control' => 'jform', 'load_data' => $loadData));

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
		$data = JFactory::getApplication()->getUserState('com_volunteers.edit.role.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		$this->preprocessData('com_volunteers.role', $data);

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
					->from($db->quoteName('#__volunteers_roles'));

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
	 * Method to delete the role from the database
	 *
	 * @return  boolean  True on success
	 */
	public function delete(&$pks)
	{
		// Reset role for members
		$db    = $this->getDbo();
		$query = $db->getQuery(true);
		$query
			->update('#__volunteers_members')
			->set('role = 0')
			->where('role = ' . $db->quote($pks));

		try
		{
			$db->setQuery($query)->execute();
		}
		catch (RuntimeException $e)
		{
			JError::raiseError(500, $e->getMessage());
		}

		parent::delete($pks);
	}
}
