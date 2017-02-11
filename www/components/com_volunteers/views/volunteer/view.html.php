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
class VolunteersViewVolunteer extends JViewLegacy
{
	protected $item;

	protected $state;

	protected $form;

	protected $user;

	protected $share;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string $tpl The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 */
	public function display($tpl = null)
	{
		$this->item        = $this->get('Item');
		$this->state       = $this->get('State');
		$this->form        = $this->get('Form');
		$this->user        = JFactory::getUser();
		$this->item->teams = $this->get('VolunteerTeams');
		$this->item->new   = JFactory::getApplication()->input->getInt('new', '0');

		// Set volunteer id in session
		JFactory::getSession()->set('volunteer', $this->item->id);

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseWarning(500, implode("\n", $errors));

			return false;
		}

		// Manipulate form
		$this->_manipulateForm();

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
		$title       = JText::_('COM_VOLUNTEERS_TITLE_VOLUNTEER') . ': ' . $this->item->name;
		$description = JHtml::_('string.truncate', $this->item->intro, 160, true, false);
		$image       = VolunteersHelper::image($this->item->image, 'large', true);
		$itemURL     = JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $this->item->id);
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
		$pathway->addItem($this->item->name, $itemURL);
	}

	/**
	 * Manipulates the form.
	 *
	 * @return  void.
	 */
	protected function _manipulateForm()
	{
		// Clear birthday field if not set
		if ($this->item->birthday == '0000-00-00')
		{
			$this->form->setValue('birthday', null, null);
		}
	}
}
