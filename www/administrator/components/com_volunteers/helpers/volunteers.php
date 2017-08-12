<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * Volunteers helper.
 */
class VolunteersHelper extends JHelperContent
{
	/**
	 * Configure the Linkbar.
	 *
	 * @param   string $vName The name of the active view.
	 *
	 * @return  void
	 */
	public static function addSubmenu($vName = 'volunteers')
	{
		JHtmlSidebar::addEntry(
			JText::_('COM_VOLUNTEERS_TITLE_VOLUNTEERS'),
			'index.php?option=com_volunteers&view=volunteers',
			$vName == 'volunteers'
		);

		JHtmlSidebar::addEntry(
			JText::_('COM_VOLUNTEERS_TITLE_TEAMS'),
			'index.php?option=com_volunteers&view=teams',
			$vName == 'teams'
		);

		JHtmlSidebar::addEntry(
			JText::_('COM_VOLUNTEERS_TITLE_ROLES'),
			'index.php?option=com_volunteers&view=roles',
			$vName == 'roles'
		);

		JHtmlSidebar::addEntry(
			JText::_('COM_VOLUNTEERS_TITLE_MEMBERS'),
			'index.php?option=com_volunteers&view=members',
			$vName == 'members'
		);

		JHtmlSidebar::addEntry(
			JText::_('COM_VOLUNTEERS_TITLE_REPORTS'),
			'index.php?option=com_volunteers&view=reports',
			$vName == 'reports'
		);

		JHtmlSidebar::addEntry('<hr>', '');

		JHtmlSidebar::addEntry(
			JText::_('COM_VOLUNTEERS_TITLE_DEPARTMENTS'),
			'index.php?option=com_volunteers&view=departments',
			$vName == 'departments'
		);

		JHtmlSidebar::addEntry(
			JText::_('COM_VOLUNTEERS_TITLE_POSITIONS'),
			'index.php?option=com_volunteers&view=positions',
			$vName == 'positions'
		);

		JHtmlSidebar::addEntry('<hr>', '');

		JHtmlSidebar::addEntry(
			JText::_('COM_VOLUNTEERS_TITLE_CONTACT'),
			'index.php?option=com_volunteers&view=contact',
			$vName == 'contact'
		);
	}
}
