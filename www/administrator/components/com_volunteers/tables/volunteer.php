<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * Volunteer Table class
 */
class VolunteersTableVolunteer extends JTable
{
	/**
	 * Constructor
	 *
	 * @param   JDatabaseDriver &$db A database connector object
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__volunteers_volunteers', 'id', $db);

		// Set the published column alias
		$this->setColumnAlias('published', 'state');

		JTableObserverTags::createObserver($this, array('typeAlias' => 'com_volunteers.team'));
		JTableObserverContenthistory::createObserver($this, array('typeAlias' => 'com_volunteers.team'));
	}

	/**
	 * Overload the store method for the table.
	 *
	 * @param   boolean    Toggle whether null values should be updated.
	 *
	 * @return  boolean  True on success, false on failure.
	 */
	public function store($updateNulls = false)
	{
		$date = JFactory::getDate();
		$user = JFactory::getUser();

		$this->modified = $date->toSql();

		if ($this->id)
		{
			// Existing item
			$this->modified_by = $user->id;
		}
		else
		{
			// New item. An item created and created_by field can be set by the user,
			// so we don't touch either of these if they are set.
			if (!(int) $this->created)
			{
				$this->created = $date->toSql();
			}

			if (empty($this->created_by))
			{
				$this->created_by = $user->id;
			}
		}

		// Birthday format
		if ($this->birthday)
		{
			$this->birthday = JFactory::getDate('0000-' . $this->birthday)->format("Y-m-d");
		}

		// Verify that the alias is unique
		$table = JTable::getInstance('Volunteer', 'VolunteersTable');

		if ($table->load(array('alias' => $this->alias)) && ($table->id != $this->id || $this->id == 0))
		{
			$this->setError(JText::_('COM_VOLUNTEERS_ERROR_UNIQUE_ALIAS'));

			return false;
		}

		return parent::store($updateNulls);
	}

	/**
	 * Overloaded check method to ensure data integrity.
	 *
	 * @return  boolean  True on success.
	 */
	public function check()
	{
		// check for valid firstname
		if (trim($this->firstname) == '')
		{
			$this->setError(JText::_('COM_VOLUNTEERS_ERR_TABLES_NAME'));

			return false;
		}

		// check for valid lastname
		if (trim($this->lastname) == '')
		{
			$this->setError(JText::_('COM_VOLUNTEERS_ERR_TABLES_NAME'));

			return false;
		}

		if (empty($this->alias))
		{
			$this->alias = $this->title;
		}

		$this->alias = JApplicationHelper::stringURLSafe($this->alias);

		if (trim(str_replace('-', '', $this->alias)) == '')
		{
			$this->alias = JFactory::getDate()->format("Y-m-d-H-i-s");
		}

		return true;
	}

	/**
	 * Overloaded delete method
	 *
	 * @param   mixed $pk An optional primary key value to delete.  If not set the instance property value is used.
	 *
	 * @return  boolean  True on success.
	 */
	public function delete($pk = null)
	{
		$return = parent::delete($pk);

		// Delete the Joomla User
		$user = JUser::getInstance($this->user_id);

		if (!$user->delete())
		{
			return false;
		}

		return $return;
	}
}
