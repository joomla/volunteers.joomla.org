<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * Class VolunteersControllerVolunteers
 */
class VolunteersControllerVolunteers extends FOFController
{
	/**
	 * ACL check before editing a record; override to customise
	 *
	 * @return  boolean  True to allow the method to run
	 */
	protected function onBeforeEdit()
	{
		$volunteer = $this->getThisModel()->getItem();

		if($volunteer->user_id == JFactory::getUser()->id) 
		{
			return $this->checkACL('core.edit.own');
		}

		return false;
	}

	/**
	 * Save the incoming data
	 */
	public function onAfterSave()
	{
		// Redirect
		$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=my'), JText::_('COM_VOLUNTEERS_LBL_VOLUNTEER_SAVED'),'success');

		return true;
	}
}
