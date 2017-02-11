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
class VolunteersViewReport extends JViewLegacy
{
	protected $item;

	protected $state;

	protected $form;

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
		$this->item  = $this->get('Item');
		$this->state = $this->get('State');
		$this->form  = $this->get('Form');
		$this->user  = JFactory::getUser();

		if ($this->item->department)
		{
			$this->acl            = VolunteersHelper::acl('department', $this->item->department);
		}
		elseif ($this->item->team)
		{
			$this->acl            = VolunteersHelper::acl('team', $this->item->team);
		}

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
			$title = JText::_('COM_VOLUNTEERS_TITLE_REPORTS_EDIT');

			// Set meta
			$this->document->setTitle($title);

			return;
		}

		// Prepare variables
		$typeTitle   = ($this->item->team) ? $this->item->team_title : $this->item->department_title;
		$title       = $this->item->title . ' - ' . $typeTitle;
		$description = JHtml::_('string.truncate', $this->item->description, 160, true, false);
		$image       = 'https://cdn.joomla.org/images/joomla-org-og.jpg';
		$itemURL     = JRoute::_('index.php?option=com_volunteers&view=report&id=' . $this->item->id);
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

		// Share Buttons
		$layout      = new JLayoutFile('joomlarrssb');
		$data        = (object) array(
			'title'            => $title,
			'image'            => $image,
			'url'              => $url,
			'displayEmail'     => true,
			'displayFacebook'  => true,
			'displayTwitter'   => true,
			'displayGoogle'    => true,
			'displayLinkedin'  => true,
			'displayPinterest' => true,
			'shorten'          => true,
			'shortenKey'       => JComponentHelper::getParams('com_volunteers')->get('yourlsapikey')
		);
		$this->share = $layout->render($data);

		// Add to pathway
		$pathway = JFactory::getApplication()->getPathway();
		if ($this->item->team)
		{
			$pathway->addItem($this->item->team_title, JRoute::_('index.php?option=com_volunteers&view=team&id=' . $this->item->team));
		}
		elseif ($this->item->department)
		{
			$pathway->addItem($this->item->department_title, JRoute::_('index.php?option=com_volunteers&view=department&id=' . $this->item->department));
		}
		$pathway->addItem($this->item->title, $itemURL);
	}

	/**
	 * Manipulates the form.
	 *
	 * @return  void.
	 */
	protected function _manipulateForm()
	{
		$app      = JFactory::getApplication();
		$jinput   = $app->input;
		$reportId = $jinput->getInt('id');

		// Disable fields
		$this->form->setFieldAttribute('department', 'readonly', 'true');
		$this->form->setFieldAttribute('team', 'readonly', 'true');

		// If editing existing report
		if ($reportId)
		{
			//$this->form->setFieldAttribute('volunteer', 'readonly', 'true');
		}
		else
		{
			$departmentId = (int) $app->getUserState('com_volunteers.edit.report.departmentid');
			$teamId       = (int) $app->getUserState('com_volunteers.edit.report.teamid');
			$this->form->setValue('department', $department = null, $departmentId);
			$this->form->setValue('team', $team = null, $teamId);
			$this->form->setValue('created', $team = null, JFactory::getDate());
			$this->item->department = $departmentId;
			$this->item->team       = $teamId;
		}
	}
}
