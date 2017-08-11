<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * Volunteers Plugin class
 */
class PlgSystemVolunteers extends JPlugin
{
	/**
	 * JFactory::getApplication();
	 *
	 * @var    object  Joomla! application object
	 */
	protected $app;

	/**
	 * If the user is not logged in, redirect to the login component
	 *
	 * @return bool
	 */
	public function onAfterRoute()
	{
		// Run on frontend only
		if ($this->app->isAdmin())
		{
			return true;
		}

		// Get variables
		$option = $this->app->input->getString('option');
		$view   = $this->app->input->getString('view');
		$layout = $this->app->input->getString('layout');
		$id     = $this->app->input->getInt('id');

		// Check if volunteer url is correct
		if ($option == 'com_volunteers' && $view == 'volunteer' && $layout != 'edit')
		{
			$itemURL    = JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $id);
			$correctURL = JUri::getInstance()->toString(['scheme', 'host', 'port']) . $itemURL;
			$currentURL = JUri::getInstance();

			if ($correctURL != $currentURL)
			{
				$this->app->redirect(JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $id, false), 301);
			}
		}

		// Check if this is the volunteers own profile
		if ($option == 'com_volunteers' && $view == 'volunteer')
		{
			JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_volunteers/models', 'VolunteersModel');
			$model       = JModelLegacy::getInstance('Volunteer', 'VolunteersModel', array('ignore_request' => true));
			$userId      = JFactory::getUser()->get('id');
			$volunteerId = (int) $model->getVolunteerId($userId);

			// Change active menu for own profile
			if ($volunteerId == $id)
			{
				$menu     = $this->app->getMenu();
				$menuItem = $menu->getItems('link', 'index.php?option=com_volunteers&view=my', true);
				$menu->setActive($menuItem->id);
			}
		}

		return true;
	}

	/**
	 * Check if volunteer filled in all required fields
	 *
	 * @return  boolean  True on success
	 */
	public function onUserAfterLogin()
	{
		// Run on frontend only
		if ($this->app->isAdmin())
		{
			return true;
		}

		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_volunteers/models', 'VolunteersModel');
		$model       = JModelLegacy::getInstance('Volunteer', 'VolunteersModel', array('ignore_request' => true));
		$userId      = JFactory::getUser()->get('id');
		$volunteerId = (int) $model->getVolunteerId($userId);
		$teams       = $model->getVolunteerTeams($volunteerId);

		// If active team member, check fields
		if ($teams->activemember)
		{
			// Get volunteer data
			$volunteer = $model->getItem($volunteerId);

			if (empty($volunteer->address) || empty($volunteer->city) || empty($volunteer->zip))
			{
				// Set session variable
				JFactory::getSession()->set('updateprofile', 1);

				// Redirect to profile
				$this->loadLanguage('com_volunteers', JPATH_ADMINISTRATOR);
				$this->app->enqueueMessage(JText::_('COM_VOLUNTEERS_PROFILE_ACTIVEMEMBERFIELDS'), 'warning');
				$this->app->redirect('index.php?option=com_volunteers&task=volunteer.edit&id=' . $volunteerId);
			}
		}

		return true;
	}

	/**
	 * Check if volunteer filled in all required fields
	 *
	 * @return  boolean  True on success
	 */
	public function onAfterRender()
	{
		// Run on frontend only
		if ($this->app->isAdmin())
		{
			return true;
		}

		// Get variables
		$view = $this->app->input->getString('view');
		$task = $this->app->input->getString('task');

		// Check if volunteer needs to update profile
		$update = JFactory::getSession()->get('updateprofile');

		if ($update && $view != 'volunteer' && $task != 'volunteer.edit')
		{
			JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_volunteers/models', 'VolunteersModel');
			$model       = JModelLegacy::getInstance('Volunteer', 'VolunteersModel', array('ignore_request' => true));
			$userId      = JFactory::getUser()->get('id');
			$volunteerId = (int) $model->getVolunteerId($userId);

			// Redirect to profile
			$this->loadLanguage('com_volunteers', JPATH_ADMINISTRATOR);
			$this->app->enqueueMessage(JText::_('COM_VOLUNTEERS_PROFILE_ACTIVEMEMBERFIELDS'), 'warning');
			$this->app->redirect('index.php?option=com_volunteers&task=volunteer.edit&id=' . $volunteerId);
		}

		return true;
	}
}