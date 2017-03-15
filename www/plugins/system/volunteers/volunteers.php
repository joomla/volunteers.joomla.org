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
				$this->app->redirect(JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $id, 301));
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
}