<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * View class for a list of departments.
 */
class VolunteersViewDepartments extends JViewLegacy
{
	protected $items;

	protected $pagination;

	protected $state;

	/**
	 * Display the view
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		$this->state         = $this->get('State');
		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		VolunteersHelper::addSubmenu('departments');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode("\n", $errors));

			return false;
		}

		$this->addToolbar();
		$this->sidebar = JHtmlSidebar::render();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 */
	protected function addToolbar()
	{
		require_once JPATH_COMPONENT . '/helpers/volunteers.php';

		$state = $this->get('State');
		$canDo = JHelperContent::getActions('com_volunteers');
		$user  = JFactory::getUser();

		// Set toolbar title
		JToolbarHelper::title(JText::_('COM_VOLUNTEERS') . ': ' . JText::_('COM_VOLUNTEERS_TITLE_DEPARTMENTS'), 'joomla');

		if ($canDo->get('core.create'))
		{
			JToolbarHelper::addNew('department.add');
		}

		if ($canDo->get('core.edit'))
		{
			JToolbarHelper::editList('department.edit');
		}

		if ($canDo->get('core.edit.state'))
		{
			JToolbarHelper::publish('volunteers.publish', 'JTOOLBAR_PUBLISH', true);
			JToolbarHelper::unpublish('volunteers.unpublish', 'JTOOLBAR_UNPUBLISH', true);
			JToolbarHelper::archiveList('volunteers.archive');
			JToolbarHelper::checkin('volunteers.checkin');
		}

		if ($state->get('filter.state') == -2 && $canDo->get('core.delete'))
		{
			JToolbarHelper::deleteList('', 'volunteers.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		elseif ($canDo->get('core.edit.state'))
		{
			JToolbarHelper::trash('volunteers.trash');
		}

		if ($user->authorise('core.admin', 'com_volunteers') || $user->authorise('core.options', 'com_volunteers'))
		{
			JToolbarHelper::preferences('com_volunteers');
		}
	}

	/**
	 * Returns an array of fields the table can be sorted by
	 *
	 * @return  array  Array containing the field name to sort by as the key and display text as value
	 */
	protected function getSortFields()
	{
		return array(
			'a.ordering' => JText::_('JGRID_HEADING_ORDERING'),
			'a.state'    => JText::_('JSTATUS'),
			'a.title'    => JText::_('JGLOBAL_TITLE'),
			'a.id'       => JText::_('JGRID_HEADING_ID')
		);
	}
}
