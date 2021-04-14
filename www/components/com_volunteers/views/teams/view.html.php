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
class VolunteersViewTeams extends JViewLegacy
{
	protected $state;

	protected $items;

	protected $pagination;

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
		$title   = ($this->state->get('filter.groups')) ? JText::_('COM_VOLUNTEERS_TITLE_GROUPS') : JText::_('COM_VOLUNTEERS_TITLE_TEAMS');
		$image   = 'https://cdn.joomla.org/images/joomla-org-og.jpg';
		$itemURL = JRoute::_('index.php?option=com_volunteers&view=teams');
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
	}
}
