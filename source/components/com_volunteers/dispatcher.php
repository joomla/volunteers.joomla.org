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
	public function __construct($config = array()) {
		$this->defaultView = 'groups';

		parent::__construct($config);
	}

	public function onBeforeDispatch() {
		$result = parent::onBeforeDispatch();

		if($result) {
			// Load js
			JHtml::_('bootstrap.framework');
			JHTML::_('formbehavior.chosen', 'select');

			// Load css
			$doc = JFactory::getDocument();
			$doc->addStyleSheet(JURI::root(true).'/media/com_volunteers/css/frontend.css');
		}

		return $result;
	}
}