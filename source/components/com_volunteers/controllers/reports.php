<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

class VolunteersControllerReports extends FOFController
{
	protected function onBeforeAdd()
	{
		$groupId = JFactory::getApplication()->input->get('group', 0);

		if($groupId == 0)
		{
			$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=reports'), JText::_('COM_VOLUNTEERS_BAD_REQUEST'),'error');

			return true;
		}

		return $this->checkACL('core.create');
	}

	protected function onBeforeEdit()
	{
		$report = $this->getThisModel()->getItem();

		if($report->created_by != JFactory::getUser()->id)
		{
			$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=reports'), JText::_('COM_VOLUNTEERS_ARE_NOT_ALLOWED_TO_EDIT'),'error');
		}

		return true;
	}

	public function onAfterSave()
	{
		// Redirect
		$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=reports'), JText::_('COM_VOLUNTEERS_LBL_REPORT_SAVED'),'success');

		return true;
	}
}