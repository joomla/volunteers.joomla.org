<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

class VolunteersControllerMy extends FOFController
{
	public function __construct($config = array()) {
		parent::__construct($config);
		$this->modelName = 'VolunteersModelVolunteers';
		$this->cacheableTasks = array();
	}

	public function execute($task) {
		$item = FOFModel::getTmpInstance('Volunteers', 'VolunteersModel')
				->user_id(JFactory::getUser()->id)
				->getFirstItem();

		$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=volunteer&id='.$item->volunteers_volunteer_id));
	}
}