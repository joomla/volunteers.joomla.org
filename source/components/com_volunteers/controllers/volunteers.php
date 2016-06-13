<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

class VolunteersControllerVolunteers extends FOFController
{
	public function onBeforeRead() {
		$groups = FOFModel::getTmpInstance('Groupmembers', 'VolunteersModel')
			->limit(0)
			->limitstart(0)
			->enabled(1)
			->active(1)
			->volunteer($this->getThisModel()->getItem()->volunteers_volunteer_id)
			->filter_order('volunteer_title')
			->filter_order_Dir('ASC')
			->getList();

		$this->getThisView()->assign('groups', $groups);

		$honorroll = FOFModel::getTmpInstance('Groupmembers', 'VolunteersModel')
			->limit(0)
			->limitstart(0)
			->enabled(1)
			->active(0)
			->volunteer($this->getThisModel()->getItem()->volunteers_volunteer_id)
			->filter_order('volunteer_title')
			->filter_order_Dir('ASC')
			->getList();

		$this->getThisView()->assign('honorroll', $honorroll);

		return true;
	}

	/**
	 * This runs before the browse() method. Return false to prevent executing
	 * the method.
	 *
	 * @return bool
	 */
	public function onBeforeBrowse() {
		$result = parent::onBeforeBrowse();
		if($result) {
			// Get the current order by column
			$orderby = $this->getThisModel()->getState('filter_order','');
			// If it's not one of the allowed columns, force it to be the "ordering" column
			if(!in_array($orderby, array('ordering','firstname','random'))) {
				$orderby = 'firstname';
			}

			// Get the event ID
			$params = JFactory::getApplication()->getPageParameters('com_volunteers');

			// Apply ordering and filter only the enabled items
			$this->getThisModel()
				->limit(40)
				->filter_order($orderby)
				->enabled(1)
				->filter_order_Dir('ASC');

			// If no volunteers are shown even though I do have volunteers, use a limitstart of 0
			if($this->input->getInt('limitstart') == '')
            {
				$this->getThisModel()->limitstart(0);
			}

			// Fetch page parameters
			$params = JFactory::getApplication()->getPageParameters('com_volunteers');

			// Push page parameters
			$this->getThisView()->assign('pageparams', $params);
		}
		return $result;
	}

	protected function onBeforeEdit()
	{
		$volunteer = $this->getThisModel()->getItem();

		if($volunteer->user_id == JFactory::getUser()->id) {
			return $this->checkACL('core.edit.own');
		}

	}

	/**
	 * Save the incoming data
	 */
	public function onAfterSave()
	{
		// Get volunteer data
		$volunteer = $this->getThisModel()->getItem();

		// Update email
		if($volunteer->email)
		{
			$db 		= JFactory::getDbo();
			$query 		= $db->getQuery(true);
			$fields 	= array($db->quoteName('email') . ' = ' . $db->quote($volunteer->email));
			$conditions = array($db->quoteName('id') . ' = ' . $db->quote($volunteer->user_id));
			$query->update($db->quoteName('#__users'))->set($fields)->where($conditions);
			$db->setQuery($query);
			$result = $db->execute();
		}

		// Redirect
		$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=my'), JText::_('COM_VOLUNTEERS_LBL_VOLUNTEER_SAVED'),'success');

		return true;
	}


	public function onBeforeSave()
	{
		// Check if volunteer is editing own profile
		$item = FOFModel::getTmpInstance('Volunteers', 'VolunteersModel')
			->user_id(JFactory::getUser()->id)
			->getFirstItem();

		$volunteer_id 		= $item->volunteers_volunteer_id;
		$volunteer_edit_id 	= FOFInput::getVar('volunteers_volunteer_id', null, $this->input);

		if($volunteer_id !== $volunteer_edit_id) {
			$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=my'), JText::_('You try to edit someone else profile'),'error');
			return false;
		}

		$userid = FOFInput::getInt('user_id', null, $this->input);

		if((!$userid) || ($userid == 0)) {
			$this->input->set('user_id',JFactory::getUser()->id);
		}

		$file = JRequest::getVar('image', '', 'files', 'array');

		if(!$file['error']) {
			$rand 		= substr(md5(microtime()),rand(0,26),4);
			$firstname 	= FOFInput::getVar('firstname', null, $this->input);
			$lastname 	= FOFInput::getVar('lastname', null, $this->input);
			$name 		= JFilterOutput::stringURLSafe($firstname.'-'.$lastname);
			$name		= $rand.'_'.$name;

			// Filetype
			$filename = ($file['name']);
			$filetype = strstr($filename, '.');
			$filename = $name.$filetype;

			// Path
			$filepath = JPath::clean(JPATH_SITE.'/images/volunteers/'.$filename);

			// Do the upload
			jimport('joomla.filesystem.file');
			if (!JFile::upload($file['tmp_name'], $filepath)) {
				$this->setError(JText::_('Upload file error'));
				return false;
			}

			// Instantiate our JImage object
			$image = new JImage($filepath);

			$sizes = array('250x250', '50x50');
			$image->createThumbs($sizes, JImage::CROP_RESIZE);

			// Set image url
			$this->input->set('image', $filename);
		}

		return true;
	}
}