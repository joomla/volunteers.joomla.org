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
class VolunteersViewMember extends JViewLegacy
{
	protected $item;

	protected $state;

	protected $form;

	protected $user;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string $tpl The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 */
	public function display($tpl = null)
	{
		$this->item  = $this->get('Item');
		$this->state = $this->get('State');
		$this->form  = $this->get('Form');
		$this->user  = JFactory::getUser();

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseWarning(500, implode("\n", $errors));

			return false;
		}

		// Manipulate form
		$this->_manipulateForm();

		// Prepare document
		$this->_prepareDocument();

		parent::display($tpl);
	}

	/**
	 * Prepares the document.
	 *
	 * @return  void.
	 */
	protected function _prepareDocument()
	{
		// Prepare variables
		$title = JText::_('COM_VOLUNTEERS_TITLE_MEMBERS_EDIT');

		// Set meta
		$this->document->setTitle($title);
	}


	/**
	 * Manipulates the form.
	 *
	 * @return  void.
	 */
	protected function _manipulateForm()
	{
		$app          = JFactory::getApplication();
		$jinput       = $app->input;
		$memberId     = $jinput->getInt('id');
		$departmentId = (int) $app->getUserState('com_volunteers.edit.member.departmentid');
		$teamId       = (int) $app->getUserState('com_volunteers.edit.member.teamid');

		// Disable fields
		$this->form->setFieldAttribute('department', 'readonly', 'true');
		$this->form->setFieldAttribute('team', 'readonly', 'true');

		// Clear date ended field if not set
		if ($this->item->date_ended == '0000-00-00')
		{
			$this->form->setValue('date_ended', null, null);
		}

		// If editing existing member
		if ($memberId)
		{
			$this->form->setFieldAttribute('volunteer', 'readonly', 'true');
			$this->form->setFieldAttribute('position', 'readonly', 'true');

			if ($departmentId)
			{
				$this->form->removeField('role');
			}
		}
		else
		{
			$this->form->setValue('department', $department = null, $departmentId);
			$this->form->setValue('team', $team = null, $teamId);
			$this->form->setValue('date_started', $team = null, JFactory::getDate());
			$this->item->department = $departmentId;
			$this->item->team       = $teamId;
		}
	}
}
