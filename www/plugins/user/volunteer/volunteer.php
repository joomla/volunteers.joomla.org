<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

class PlgUserVolunteer extends JPlugin
{
	/**
	 * Application object
	 *
	 * @var    JApplicationCms
	 * @since  3.2
	 */
	protected $app;

	public function onUserBeforeDelete($user)
	{
		$db = Factory::getDbo();

		$query = $db->getQuery(true)
			->select($db->quoteName('members.id'))
			->from($db->quoteName('#__volunteers_members', 'members'))
			->leftJoin($db->quoteName('#__volunteers_volunteers') . ' AS volunteers ON ' . $db->quoteName('volunteers.id') . ' = ' . $db->quoteName('members.volunteer'))
			->where($db->quoteName('volunteers.user_id') . ' = ' . $db->quote($user['id']));

		$volunteer = $db->setQuery($query)->loadResult();

		if ($volunteer)
		{
			$this->app->enqueueMessage(Text::_('This user is/was a volunteer. You can\'t delete ' . $user['name']), 'error');
			$this->app->redirect('index.php?option=com_users&view=users');
			jexit();
		}

		return true;
	}

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
