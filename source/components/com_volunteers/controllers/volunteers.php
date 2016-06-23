<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * Class VolunteersControllerVolunteers
 */
class VolunteersControllerVolunteers extends FOFController
{

	protected function onBeforeEdit()
	{
		$volunteer = $this->getThisModel()->getItem();

		if($volunteer->user_id == JFactory::getUser()->id) 
		{
			return $this->checkACL('core.edit.own');
		}

		return false;
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