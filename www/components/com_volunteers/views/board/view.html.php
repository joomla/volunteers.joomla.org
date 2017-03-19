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
class VolunteersViewBoard extends JViewLegacy
{
	protected $item;

	protected $state;

	protected $form;

	protected $user;

	protected $acl;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string $tpl The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 */
	public function display($tpl = null)
	{
		$this->item          = $this->get('Item');
		$this->state         = $this->get('State');
		$this->form          = $this->get('Form');
		$this->user          = JFactory::getUser();
		$this->item->reports = $this->get('DepartmentReports');
		$this->item->members = $this->get('DepartmentMembers');
		$this->acl           = VolunteersHelper::acl('department', $this->item->id);

		// Set department id in session
		JFactory::getSession()->set('department', $this->item->id);

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
		$title       = $this->item->title;
		$description = JHtml::_('string.truncate', $this->item->description, 160, true, false);
		$image       = 'https://cdn.joomla.org/images/joomla-org-og.jpg';
		$itemURL     = JRoute::_('index.php?option=com_volunteers&view=board&id=' . $this->item->id);
		$url         = JUri::getInstance()->toString(['scheme', 'host', 'port']) . $itemURL;

		// Set meta
		$this->document->setTitle($title);
		$this->document->setDescription($description);

		// Twitter Card metadata
		$this->document->setMetaData('twitter:title', $title);
		$this->document->setMetaData('twitter:description', $description);
		$this->document->setMetaData('twitter:image', $image);

		// OpenGraph metadata
		$this->document->setMetaData('og:title', $title, 'property');
		$this->document->setMetaData('og:description', $description, 'property');
		$this->document->setMetaData('og:image', $image, 'property');
		$this->document->setMetaData('og:type', 'article', 'property');
		$this->document->setMetaData('og:url', $url, 'property');

		// Add to pathway
		$pathway = JFactory::getApplication()->getPathway();
		$pathway->addItem($this->item->title, $itemURL);

		// Add the RSS link.
		$props = array('type' => 'application/rss+xml', 'title' => 'RSS 2.0');
		$route = JRoute::_('index.php?option=com_volunteers&view=reports&filter_category=d.' . $this->item->id . '&format=feed&type=rss', false);
		$this->document->addHeadLink($route, 'alternate', 'rel', $props);

		// Add the ATOM link.
		$props = array('type' => 'application/atom+xml', 'title' => 'Atom 1.0');
		$route = JRoute::_('index.php?option=com_volunteers&view=reports&filter_category=d.' . $this->item->id . '&format=feed&type=atom', false);
		$this->document->addHeadLink($route, 'alternate', 'rel', $props);
	}
}
