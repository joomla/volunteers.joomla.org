<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

class VolunteersControllerSubgroups extends FOFController
{
	/**
	 * Save the incoming data
	 */
	public function onAfterSave()
	{
		$subgroup = $this->getThisModel()->getItem();

		// Redirect
		$this->setRedirect(
			JRoute::_('index.php?option=com_volunteers&view=group&id=' . $subgroup->group_id, true),
			JText::_('COM_VOLUNTEERS_LBL_GROUP_SAVED'),'success'
		);

		return true;
	}

	public function onBeforeSave()
	{
		return true;
	}
}
