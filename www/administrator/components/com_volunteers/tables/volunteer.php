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
	}

	/**
	 * Method to bind the data.
	 *
	 * @param   array $array  The data to bind.
	 * @param   mixed $ignore An array or space separated list of fields to ignore.
	 *
	 * @return  boolean  True on success, false on failure.
	 */
	public function bind($array, $ignore = array())
	{
		// send_permission checkbox default
		if (!isset($array['send_permission']))
		{
			$array['send_permission'] = 0;
		}

		// coc checkbox default
		if (!isset($array['coc']))
		{
			$array['coc'] = 0;
		}

		return parent::bind($array, $ignore);
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
		if ($this->birthday && $this->birthday != '0000-00-00 00:00:00')
		{
			$this->birthday = JFactory::getDate('0000-' . $this->birthday)->format('Y-m-d');
		}

		return parent::store($updateNulls);
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
