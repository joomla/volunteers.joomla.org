<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

class PlgUserVolunteer extends JPlugin
{

	/**
	 * Utility method to act on a user after it has been saved.
	 */
	public function onUserAfterSave($user, $isnew, $success, $msg)
	{
		// If the user wasn't stored we don't resync
		if (!$success)
		{
			return false;
		}

		// If the user isn't new we don't sync
		if (!$isnew)
		{
			return false;
		}

		// Ensure the user id is really an int
		$user_id = (int) $user['id'];

		// If the user id appears invalid then bail out just in case
		if (empty($user_id))
		{
			return false;
		}

		$db 	= JFactory::getDbo();
		$query 	= $db->getQuery(true);
		$query->select('volunteers_volunteer_id');
		$query->from($db->quoteName('#__volunteers_volunteers'));
		$query->where($db->quoteName('email')." = ".$db->quote($user['email']));
		$db->setQuery($query);
		$id = $db->loadResult();

		if($id)
		{
			$query 		= $db->getQuery(true);
			$fields 	= array($db->quoteName('user_id') . ' = ' . $db->quote($user_id));
			$conditions = array($db->quoteName('email') . ' = ' . $db->quote($user['email']));
			$query->update($db->quoteName('#__volunteers_volunteers'))->set($fields)->where($conditions);
			$db->setQuery($query);
			$result = $db->execute();
		}
		else
		{
			// Split name
			$nameparts 	= explode(' ', $user['name']);
			$firstname 	= array_shift($nameparts);
			$lastname 	= join(' ', $nameparts);

			// Create new volunteer
			$volunteer = new stdClass();
			$volunteer->user_id 	= $user['id'];
			$volunteer->firstname 	= $firstname;
			$volunteer->lastname 	= $lastname;
			$volunteer->email		= $user['email'];
			$volunteer->slug		= JFilterOutput::stringURLSafe($firstname.'-'.$lastname);

			// Insert the object into the user profile table.
			$result = JFactory::getDbo()->insertObject('#__volunteers_volunteers', $volunteer);
		}

		return true;
	}

	public function onUserAfterDelete($user, $success, $msg)
	{
		if (!$success)
		{
			return false;
		}

		$db 		= JFactory::getDbo();
		$query 		= $db->getQuery(true);
		$fields 	= array($db->quoteName('user_id') . ' = ' . $db->quote(0));
		$conditions = array($db->quoteName('email') . ' = ' . $db->quote($user['email']));
		$query->update($db->quoteName('#__volunteers_volunteers'))->set($fields)->where($conditions);
		$db->setQuery($query);
		$result = $db->execute();

		return true;
	}
}
