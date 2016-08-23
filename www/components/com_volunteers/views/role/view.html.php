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
class VolunteersViewRole extends JViewLegacy
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
		$title = JText::_('COM_VOLUNTEERS_TITLE_ROLES_EDIT');

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
		$app      = JFactory::getApplication();
		$jinput   = $app->input;
		$memberId = $jinput->getInt('id');
		$this->form->setFieldAttribute('team', 'readonly', 'true');

		// If editing existing member
		if (!$memberId)
		{
			$teamId = (int) $app->getUserState('com_volunteers.edit.role.teamid');
			$this->form->setValue('team', $team = null, $teamId);
			$this->item->team = $teamId;
		}
	}
}
