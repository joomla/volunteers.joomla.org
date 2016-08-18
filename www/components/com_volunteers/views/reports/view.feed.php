<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * Feed List view
 */
class VolunteersViewReports extends JViewLegacy
{
	/**
	 * Execute and display a template script.
	 *
	 * @param   string $tpl The name of the template file to parse; automatically searches through the template paths.
	 */
	public function display($tpl = null)
	{
		// Parameters
		$app       = JFactory::getApplication();
		$doc       = JFactory::getDocument();
		$siteEmail = $app->get('mailfrom');
		$doc->link = JRoute::_('index.php?option=com_volunteers&view=reports');

		// Get some data from the model
		$app->input->set('limit', $app->get('feed_limit'));
		$rows = $this->get('Items');

		foreach ($rows as $row)
		{
			// Load individual item creator class
			$item              = new JFeedItem;
			$item->title       = $this->escape($row->title);
			$item->link        = JRoute::_('index.php?option=com_volunteers&view=report&id=' . $row->id);
			$item->description = $row->description;
			$item->date        = $row->publish_up;
			$item->category    = $row->department_title;
			$item->author      = $row->author_name;
			$item->authorEmail = $siteEmail;

			// Loads item info into rss array
			$doc->addItem($item);
		}
	}
}
