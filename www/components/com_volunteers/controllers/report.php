<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * Report controller class.
 */
class VolunteersControllerReport extends JControllerForm
{
	/**
	 * Constructor
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->addModelPath(JPATH_ADMINISTRATOR . '/components/com_volunteers/models', 'VolunteersModel');
		JFormHelper::addFormPath(JPATH_ADMINISTRATOR . '/components/com_volunteers/models/forms');
	}

	/**
	 * Method to add report
	 *
	 * @return  boolean
	 */
	public function add($key = null, $urlVar = null)
	{
		// Get variables
		$departmentId = $this->input->getInt('department');
		$teamId       = $this->input->getInt('team');

		// Department or team?
		if ($departmentId)
		{
			$acl = VolunteersHelper::acl('department', $departmentId);
			JFactory::getApplication()->setUserState('com_volunteers.edit.report.departmentid', $departmentId);
		}
		elseif ($teamId)
		{
			$acl = VolunteersHelper::acl('team', $teamId);
			JFactory::getApplication()->setUserState('com_volunteers.edit.report.teamid', $teamId);
		}

		// Check if the user is authorized to edit this team
		if (!$acl->create_report)
		{
			return JError::raiseError(403, JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $memberId));
		}

		// Use parent add method
		return parent::add();
	}

	/**
	 * Method to edit report data
	 *
	 * @return  boolean
	 */
	public function edit($key = null, $urlVar = null)
	{
		// Get variables
		$reportId = $this->input->getInt('id');
		$report   = $this->getModel()->getItem($reportId);
		$userId   = (int) JFactory::getUser()->get('id');

		// Department or team?
		if ($report->department)
		{
			$acl = VolunteersHelper::acl('department', $report->department);
			JFactory::getApplication()->setUserState('com_volunteers.edit.report.departmentid', $report->department);
		}
		elseif ($report->team)
		{
			$acl = VolunteersHelper::acl('team', $report->team);
			JFactory::getApplication()->setUserState('com_volunteers.edit.report.teamid', $report->team);
		}

		// Check if the user is authorized to edit this team
		if (!$acl->edit && ($userId != $report->created_by))
		{
			return JError::raiseError(403, JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $reportId));
		}

		// Use parent edit method
		return parent::edit($key, $urlVar);
	}

	/**
	 * Method to save report data.
	 *
	 * @return  boolean
	 */
	public function save($key = null, $urlVar = null)
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Get variables
		$app          = JFactory::getApplication();
		$reportId     = ($this->input->getInt('id')) ? $this->input->getInt('id') : null;
		$report       = $this->getModel()->getItem($reportId);
		$departmentId = (int) ($reportId) ? $report->department : $app->getUserState('com_volunteers.edit.report.departmentid');
		$teamId       = (int) ($reportId) ? $report->team : $app->getUserState('com_volunteers.edit.report.teamid');
		$userId       = (int) JFactory::getUser()->get('id');

		// Department or team?
		if ($departmentId)
		{
			JFactory::getApplication()->setUserState('com_volunteers.edit.report.departmentid', null);
			$acl = VolunteersHelper::acl('department', $departmentId);
		}
		elseif ($teamId)
		{
			JFactory::getApplication()->setUserState('com_volunteers.edit.report.teamid', null);
			$acl = VolunteersHelper::acl('team', $teamId);
		}

		// Check if the user is authorized to edit this team
		if (!$acl->edit && !$acl->create_report && ($userId != $report->created_by))
		{
			return JError::raiseError(403, JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $memberId));
		}

		// Use parent save method
		$return = parent::save($key, $urlVar);

		// Redirect to the team
		$this->setMessage(JText::_('COM_VOLUNTEERS_LBL_REPORT_SAVED'));

		// Department or team?
		if ($departmentId)
		{
			$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=department&id=' . $departmentId . '#reports', false));
		}
		elseif ($teamId)
		{
			$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=team&id=' . $teamId . '#reports', false));
		}

		return $return;
	}

	/**
	 * Method to cancel member data.
	 *
	 * @return  boolean
	 */
	public function cancel($key = null)
	{
		// Get variables
		$app          = JFactory::getApplication();
		$departmentId = $app->getUserState('com_volunteers.edit.report.departmentid');
		$teamId       = $app->getUserState('com_volunteers.edit.report.teamid');

		// Use parent save method
		$return = parent::cancel($key);

		// Department or team?
		if ($departmentId)
		{
			JFactory::getApplication()->setUserState('com_volunteers.edit.report.departmentid', null);
			$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=department&id=' . $departmentId . '#reports', false));
		}
		elseif ($teamId)
		{
			JFactory::getApplication()->setUserState('com_volunteers.edit.report.teamid', null);
			$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=team&id=' . $teamId . '#reports', false));
		}

		return $return;
	}
}