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
class VolunteersViewReports extends JViewLegacy
{
	protected $state;

	protected $items;

	protected $pagination;

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
		$this->state      = $this->get('State');
		$this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->user       = JFactory::getUser();
		$this->category   = $this->get('Category');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseWarning(500, implode("\n", $errors));

			return false;
		}

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
		$title   = JText::_('COM_VOLUNTEERS_TITLE_REPORTS');
		$image   = 'https://cdn.joomla.org/images/joomla-org-og.jpg';
		$itemURL = JRoute::_('index.php?option=com_volunteers&view=reports');
		$url     = JUri::getInstance()->toString(['scheme', 'host', 'port']) . $itemURL;

		// Set meta
		$this->document->setTitle($title);

		// Twitter Card metadata
		$this->document->setMetaData('twitter:title', $title);
		$this->document->setMetaData('twitter:image', $image);

		// OpenGraph metadata
		$this->document->setMetaData('og:title', $title, 'property');
		$this->document->setMetaData('og:image', $image, 'property');
		$this->document->setMetaData('og:type', 'article', 'property');
		$this->document->setMetaData('og:url', $url, 'property');

		// Add the RSS link.
		$props = array('type' => 'application/rss+xml', 'title' => 'RSS 2.0');
		$route = JRoute::_('index.php?option=com_volunteers&view=reports&filter_category=&format=feed&type=rss');
		$this->document->addHeadLink($route, 'alternate', 'rel', $props);

		// Add the ATOM link.
		$props = array('type' => 'application/atom+xml', 'title' => 'Atom 1.0');
		$route = JRoute::_('index.php?option=com_volunteers&view=reports&filter_category=&format=feed&type=atom');
		$this->document->addHeadLink($route, 'alternate', 'rel', $props);
	}
}
