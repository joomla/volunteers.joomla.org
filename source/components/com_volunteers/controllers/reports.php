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
	public function onBeforeRead() {
		$author = $this->getThisModel()->getItem()->created_by;

		// Get Volunteer Profile Manager
		$volunteer = FOFModel::getTmpInstance('Volunteers', 'VolunteersModel')
			->user_id($author)
			->getFirstItem();

		$this->getThisView()->assign('volunteer', $volunteer);

		$group_id = $this->getThisModel()->getItem()->volunteers_group_id;

		// Get Volunteer Profile Manager
		$group = FOFModel::getTmpInstance('Groups', 'VolunteersModel')
			->setID($group_id)
			->getItem();

		$this->getThisView()->assign('group', $group);

		return true;
	}

	/**
	 * This runs before the browse() method. Return false to prevent executing
	 * the method.
	 *
	 * @return bool
	 */
	public function onBeforeBrowse() {
		$result = parent::onBeforeBrowse();
		if($result) {
			// Get the current order by column
			$orderby = $this->getThisModel()->getState('filter_order','');
			// If it's not one of the allowed columns, force it to be the "ordering" column
			if(!in_array($orderby, array('title','created_on'))) {
				$orderby = 'created_on';
			}

			// Apply ordering and filter only the enabled items
			$this->getThisModel()
				->filter_order($orderby)
				->enabled(1)
				->filter_order_Dir('DESC');

			// If no reports are shown even though I do have reports, use a limitstart of 0
			if($this->input->getInt('limitstart') == '')
            {
				$this->getThisModel()->limitstart(0);
			}

			// Fetch page parameters
			$params = JFactory::getApplication()->getPageParameters('com_volunteers');

			// Push page parameters
			$this->getThisView()->assign('pageparams', $params);
		}
		return $result;
	}

	protected function onBeforeAdd()
	{
		$groupid = JFactory::getApplication()->input->get('group', 0);

		$group = FOFModel::getTmpInstance('Groups', 'VolunteersModel')
				->id($groupid)
				->getFirstItem();

		$this->getThisView()->assign('group', $group);

		return $this->checkACL('core.create');
	}

	protected function onBeforeEdit()
	{
		$groupid = JFactory::getApplication()->input->get('group', 0);

		$group = FOFModel::getTmpInstance('Groups', 'VolunteersModel')
				->id($groupid)
				->getFirstItem();

		$this->getThisView()->assign('group', $group);

		$report = $this->getThisModel()->getItem();

		if($report->created_by == JFactory::getUser()->id)
		{
			return true;
		}
	}

	public function onAfterSave()
	{
		// Redirect
		$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=reports'), JText::_('COM_VOLUNTEERS_LBL_REPORT_SAVED'),'success');

		return true;
	}
}