<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * Member Table class
 */
class VolunteersTableMember extends JTable
{
	/**
	 * Indicates that columns fully support the NULL value in the database
	 *
	 * @var  boolean
	 */
	protected $_supportNullValue = true;

	/**
	 * Constructor
	 *
	 * @param   JDatabaseDriver &$db A database connector object
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__volunteers_members', 'id', $db);

		// Set the published column alias
		$this->setColumnAlias('published', 'state');
	}

	/**
	 * Overload the store method for the table.
	 *
	 * @param   boolean    Toggle whether null values should be updated.
	 *
	 * @return  boolean  True on success, false on failure.
	 */
	public function store($updateNulls = true)
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

		return parent::store($updateNulls);
	}

	/**
	 * Overloaded check method to ensure data integrity.
	 *
	 * @return  boolean  True on success.
	 */
	public function check()
	{
		// Empty selections are posted as '', which strict SQL mode rejects for integer columns
		$this->department = (int) $this->department;
		$this->team       = (int) $this->team;
		$this->volunteer  = (int) $this->volunteer;
		$this->position   = (int) $this->position;
		$this->role       = (int) $this->role;

		// Set date_started to null if not set
		if (!$this->date_started)
		{
			$this->date_started = null;
		}

		// Set date_ended to null if not set
		if (!$this->date_ended)
		{
			$this->date_ended = null;
		}

		return true;
	}
}
