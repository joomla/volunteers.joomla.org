<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * Ajax Report Spam Plugin class
 */
class PlgAjaxReportspam extends JPlugin
{
	protected $app;

	public function onAjaxReportspam()
	{
		// Variables
		$volunteer = $this->app->input->getInt('volunteer', 0);

		// Only track if volunteer ID is set
		if (!$volunteer)
		{
			return false;
		}
		// Don't track spam reports from guests
		if (JFactory::getUser()->guest)
		{
			return false;
		}

		// +1 on the spam column in database
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query
			->update('#__volunteers_volunteers')
			->set('spam = spam + 1')
			->where('id = ' . $volunteer);

		return $db->setQuery($query)->execute();
	}
}
