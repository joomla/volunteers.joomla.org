<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * Contact model.
 */
class VolunteersModelContact extends JModelAdmin
{
	/**
	 * Abstract method for getting the form from the model.
	 *
	 * @param   array   $data     Data for the form.
	 * @param   boolean $loadData True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A JForm object on success, false on failure
	 */
	public function getForm($data = array(), $loadData = false)
	{
		// Get the form.
		$form = $this->loadForm('com_volunteers.contact', 'contact', array('control' => 'jform'));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Get active volunteers
	 *
	 * @return  mixed
	 */
	public function getActiveVolunteers()
	{
		$query = $this->_db->getQuery(true);

		$query
			->select('DISTINCT u.id, u.name, u.email')
			->from('#__users AS u')
			->leftJoin('#__volunteers_volunteers AS v ON u.id = v.user_id')
			->leftJoin('#__volunteers_members AS m ON v.id = m.volunteer')
			->leftJoin('#__volunteers_teams AS t ON t.id = m.team')
			->where('m.team IS NOT NULL')
			->where('t.title IS NOT NULL')
			->where('m.date_ended IS NULL')
			->where('t.date_ended IS NULL');

		$this->_db->setQuery($query);

		return $this->_db->loadObjectList();
	}
}
