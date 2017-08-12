<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * View to contact active volunteers.
 *
 * @since  __DEPLOY_VERSION__
 */
class VolunteersViewContact extends JViewLegacy
{
	protected $form;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function display($tpl = null)
	{
		/** @var JForm form */
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
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function addToolbar()
	{
		JFactory::getApplication()->input->set('hidemainmenu', true);

		$canDo = JHelperContent::getActions('com_volunteers');

		// Set toolbar title
		JToolbarHelper::title(JText::_('COM_VOLUNTEERS') . ': ' . JText::_('COM_VOLUNTEERS_CONTACT_ACTIVE_VOLUNTEERS'), 'joomla');

		if ($canDo->get('core.manage'))
		{
			JToolbarHelper::custom('contact.send', 'mail', 'mail', 'COM_VOLUNTEERS_CONTACT', false);
		}

		JToolbarHelper::cancel('contact.cancel');
	}
}
