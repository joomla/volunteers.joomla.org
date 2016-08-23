<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

class PlgUserVolunteer extends JPlugin
{
	public function onUserAfterDelete($user, $success, $msg)
	{
		if (!$success)
		{
			return false;
		}

		$db         = JFactory::getDbo();
		$query      = $db->getQuery(true);
		$conditions = array($db->quoteName('user_id') . ' = ' . $db->quote($user['id']));
		$query->delete($db->quoteName('#__volunteers_volunteers'));
		$query->where($conditions);
		$db->setQuery($query);
		$result = $db->execute();

		return $result;
	}
}
