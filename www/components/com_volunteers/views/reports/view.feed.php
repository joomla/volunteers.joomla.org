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
		$app            = JFactory::getApplication();
		$doc            = JFactory::getDocument();
		$siteEmail      = $app->get('mailfrom');
		$this->category = $this->get('Category');

		// Set document data
		$doc->title = ($this->category) ? JText::_('COM_VOLUNTEERS_TITLE_REPORTS') . ': ' . $this->category : JText::_('COM_VOLUNTEERS_TITLE_REPORTS');
		$doc->link  = JRoute::_('index.php?option=com_volunteers&view=reports');

		// Get some data from the model
		$app->input->set('limit', $app->get('feed_limit'));
		$rows = $this->get('Items');

		foreach ($rows as $row)
		{
			// Load individual item creator class
			$item              = new JFeedItem;
			$item->title       = $this->escape($row->title);
			$item->link        = JRoute::_('index.php?option=com_volunteers&view=report&id=' . $row->id);
			$item->description = JHTML::_('string.truncate', $row->description, 1000);
			$item->date        = $row->created;
			$item->category    = $row->department_title;
			$item->author      = $row->volunteer_name;
			$item->authorEmail = ($row->volunteer_email_feed) ? $row->volunteer_email : $siteEmail;

			// Loads item info into rss array
			$doc->addItem($item);
		}
	}
}
