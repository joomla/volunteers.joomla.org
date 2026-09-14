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
			JText::_('COM_VOLUNTEERS_TITLE_CONTACT'),
			'index.php?option=com_volunteers&view=contact',
			$vName == 'contact'
		);
	}
}
