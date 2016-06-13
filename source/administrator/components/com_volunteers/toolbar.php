<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

class VolunteersToolbar extends FOFToolbar
{
	protected function getMyViews()
	{
		$views = array(
			'groups',
			'groupmembers',
			'volunteers',
			'reports',
		);
		return $views;
	}

	public function onBrowse()
	{
		parent::onBrowse();

		JToolBarHelper::preferences('com_volunteers');
	}
}