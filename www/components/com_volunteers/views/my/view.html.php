<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * HTML List view
 */
class VolunteersViewMy extends JViewLegacy
{
	/**
	 * Execute and display a template script.
	 *
	 * @param   string $tpl The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 */
	public function display($tpl = null)
	{
		$model       = JModelLegacy::getInstance('Volunteer', 'VolunteersModel', array('ignore_request' => true));
		$user        = JFactory::getUser();
		$userId      = (int) $user->get('id');
		$volunteerId = (int) $model->getVolunteerId($userId);

		JFactory::getApplication()->redirect(JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $volunteerId, false));
	}
}
