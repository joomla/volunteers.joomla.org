<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * Class VolunteersControllerMembers
 */
class VolunteersControllerMembers extends FOFController
{
	protected function onBeforeAdd()
	{
		$type = JFactory::getApplication()->input->get('type');
		
		if ($type == 'group')
		{
			$groupId = JFactory::getApplication()->input->get('group', 0);
			
			if($groupId == 0)
			{
				$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=reports'), JText::_('COM_VOLUNTEERS_BAD_REQUEST'),'error');
				
				return true;
			}
			
			return $this->checkACL('core.create');
		}
		
		$this->setRedirect(JRoute::_('index.php?option=com_volunteers'), JText::_('COM_VOLUNTEERS_BAD_REQUEST'),'error');
		
		return true;
	}

	protected function onBeforeEdit()
	{
		return true;
	}

	public function onAfterSave()
	{
		$this->setRedirect(JRoute::_('index.php'), JText::_('COM_VOLUNTEERS_LBL_MEMBER_SAVED'),'success');

		if ($this->input->get('type') == 'group')
		{
			$reltable_id = $this->input->get('reltable_id');

			// Redirect
			$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=group&id=' . $reltable_id) . '#groups', JText::_('COM_VOLUNTEERS_LBL_MEMBER_SAVED'),'success');
		}

		return true;
	}
}