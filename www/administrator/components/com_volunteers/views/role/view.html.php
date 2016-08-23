<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * View to edit a role.
 */
class VolunteersViewRole extends JViewLegacy
{
	protected $state;

	protected $item;

	protected $form;

	/**
	 * Display the view
	 */
	public function display($tpl = null)
	{
		$this->state = $this->get('State');
		$this->item  = $this->get('Item');
		$this->form  = $this->get('Form');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode("\n", $errors));

			return false;
		}

		$this->addToolbar();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 */
	protected function addToolbar()
	{
		JFactory::getApplication()->input->set('hidemainmenu', true);

		$user       = JFactory::getUser();
		$isNew      = ($this->item->id == 0);
		$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		$canDo      = JHelperContent::getActions('com_volunteers');

		// Set toolbar title
		JToolbarHelper::title($isNew ? JText::_('COM_VOLUNTEERS') . ': ' . JText::_('COM_VOLUNTEERS_TITLE_ROLES_NEW') : JText::_('COM_VOLUNTEERS') . ': ' . JText::_('COM_VOLUNTEERS_TITLE_ROLES_EDIT'), 'joomla');

		if (!$checkedOut && ($canDo->get('core.edit') || $canDo->get('core.create')))
		{
			JToolbarHelper::apply('role.apply');
			JToolbarHelper::save('role.save');
		}

		if (!$checkedOut && $canDo->get('core.create'))
		{
			JToolbarHelper::save2new('role.save2new');
		}

		if (!$isNew && $canDo->get('core.create'))
		{
			JToolbarHelper::save2copy('role.save2copy');
		}

		if (empty($this->item->id))
		{
			JToolbarHelper::cancel('role.cancel');
		}
		else
		{
			if ($this->state->params->get('save_history', 0) && $user->authorise('core.edit'))
			{
				JToolbarHelper::versions('com_volunteers.role', $this->item->id);
			}

			JToolbarHelper::cancel('role.cancel', 'JTOOLBAR_CLOSE');
		}
	}
}
