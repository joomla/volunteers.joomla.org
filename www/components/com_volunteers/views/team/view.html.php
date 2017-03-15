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
class VolunteersViewTeam extends JViewLegacy
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
		$this->item           = $this->get('Item');
		$this->state          = $this->get('State');
		$this->form           = $this->get('Form');
		$this->user           = JFactory::getUser();
		$this->item->reports  = $this->get('TeamReports');
		$this->item->subteams = $this->get('TeamSubteams');
		$this->item->members  = $this->get('TeamMembers');
		$this->item->roles    = $this->get('TeamRoles');
		$this->acl            = VolunteersHelper::acl('team', $this->item->id);

		// Set team id in session
		JFactory::getSession()->set('team', $this->item->id);

		// Active / inactive
		$this->item->active = ($this->item->date_ended == '0000-00-00');

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
		$layout = JFactory::getApplication()->input->get('layout');

		if ($layout == 'edit')
		{
			// Prepare variables
			$title = JText::_('COM_VOLUNTEERS_TITLE_TEAMS_EDIT');

			// Set meta
			$this->document->setTitle($title);

			return;
		}

		// Prepare variables
		$title       = $this->item->title;
		$description = JHtml::_('string.truncate', $this->item->description, 160, true, false);
		$image       = 'https://cdn.joomla.org/images/joomla-org-og.jpg';
		$itemURL     = JRoute::_('index.php?option=com_volunteers&view=team&id=' . $this->item->id);
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
		$route = JRoute::_('index.php?option=com_volunteers&view=reports&filter_category=t.' . $this->item->id . '&format=feed&type=rss', false);
		$this->document->addHeadLink($route, 'alternate', 'rel', $props);

		// Add the ATOM link.
		$props = array('type' => 'application/atom+xml', 'title' => 'Atom 1.0');
		$route = JRoute::_('index.php?option=com_volunteers&view=reports&filter_category=t.' . $this->item->id . '&format=feed&type=atom', false);
		$this->document->addHeadLink($route, 'alternate', 'rel', $props);
	}

	/**
	 * Manipulates the form.
	 *
	 * @return  void.
	 */
	protected function _manipulateForm()
	{
		// Manipulate frontend edit form
		$app    = JFactory::getApplication();
		$teamId = $app->input->getInt('id');

		// Clear date ended field if not set
		if ($this->item->date_ended == '0000-00-00')
		{
			$this->form->setValue('date_ended', null, null);
		}

		// If editing existing team
		if ($teamId)
		{
			if (!$this->acl->edit_department)
			{
				$this->form->setFieldAttribute('department', 'readonly', 'true');
				// This should be activated again once we have department coordinators
				// $this->form->setFieldAttribute('status', 'readonly', 'true');
			}
		}
		else
		{
			$departmentId = (int) $app->getUserState('com_volunteers.edit.team.departmentid');
			$teamId       = (int) $app->getUserState('com_volunteers.edit.team.teamid');
			$this->form->setValue('department', null, $departmentId);
			$this->form->setValue('parent_id', null, $teamId);
			$this->form->setValue('date_started', null, JFactory::getDate());
			$this->form->setFieldAttribute('department', 'readonly', 'true');

			if ($teamId)
			{
				$this->form->setFieldAttribute('parent_id', 'readonly', 'true');
			}
		}
	}
}
