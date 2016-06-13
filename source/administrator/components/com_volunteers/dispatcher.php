<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

class VolunteersDispatcher extends FOFDispatcher
{
	public $defaultView = 'groups';

	public function onBeforeDispatch() {
		$result = parent::onBeforeDispatch();

		if($result) {
			if (!JFactory::getUser()->authorise('core.manage', 'com_volunteers'))
			{
				JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
				return false;
			}

			// Load Akeeba Strapper
			include_once JPATH_ROOT . '/media/akeeba_strapper/strapper.php';
			AkeebaStrapper::bootstrap();

			JHTML::_('formbehavior.chosen', 'select');
		}

		return $result;
	}
}