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
		$option = $this->app->input->get('option');
		$view   = $this->app->input->get('view');
		$id     = $this->app->input->get('id');

		// Check if volunteer url is correct
		if ($option == 'com_volunteers' && $view == 'volunteer')
		{
			$itemURL    = JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $id);
			$correctURL = JUri::getInstance()->toString(['scheme', 'host', 'port']) . $itemURL;
			$currentURL = JUri::getInstance();

			if ($correctURL != $currentURL)
			{
				$this->app->redirect(JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $id, 301));
			}
		}

		return true;
	}
}