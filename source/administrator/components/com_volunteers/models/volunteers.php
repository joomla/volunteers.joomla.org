<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

include_once 'base.php';

/**
 * Class VolunteersModelVolunteers
 */
class VolunteersModelVolunteers extends VolunteersModelBase
{
	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 */
	protected function populateState($ordering = 'volunteer.firstname', $direction = 'asc')
	{
		parent::populateState($ordering, $direction);
	}

	/**
	 * Builds the SELECT query
	 *
	 * @param   boolean  $overrideLimits  Are we requested to override the set limits?
	 *
	 * @return  JDatabaseQuery
	 */
	public function buildQuery($overrideLimits = false)
	{
		$query = parent::buildQuery($overrideLimits);

		if (FOFPlatform::getInstance()->isFrontend())
		{
			$query->clear('order')
				->order('volunteer.firstname ASC');

			$this->setState('limit', 40);
		}

		return $query;
	}
	
	
	/**
	 * Allows data and form manipulation after preprocessing the form
	 *
	 * @param   FOFForm  $form    A FOFForm object.
	 * @param   array    &$data   The data expected for the form.
	 * @codeCoverageIgnore
	 *
	 * @return  void
	 */
	public function onAfterPreprocessForm(FOFForm &$form, &$data)
	{
		if (FOFPlatform::getInstance()->isFrontend())
		{
			$form->removeField('slug');
			$form->removeField('enabled');
			$form->removeField('notes');
		}
	}
	
	/**
	 * This method runs after an item has been gotten from the database in a read
	 * operation. You can modify it before it's returned to the MVC triad for
	 * further processing.
	 *
	 * @param   FOFTable  &$record  The table instance we fetched
	 *
	 * @return  void
	 */
	protected function onAfterGetItem(&$record)
	{
		parent::onAfterGetItem($record);
		
		if (FOFPlatform::getInstance()->isFrontend())
		{
			$record->groups    = $this->getVolunteerGroups($record->volunteers_volunteer_id);
			$record->honorroll = $this->getVolunteerHonourroll($record->volunteers_volunteer_id);
		}
	}
	
	/**
	 * This method runs before the $data is saved to the $table. Return false to
	 * stop saving.
	 *
	 * @param   array     &$data   The data to save
	 * @param   FOFTable  &$table  The table to save the data to
	 *
	 * @return  boolean  Return false to prevent saving, true to allow it
	 */
	protected function onBeforeSave(&$data, &$table)
	{
		if ($data['removeimage'] == 0)
		{
			// Grab the old value so that it doesn't get lost
			$data['image'] = $table->image;
		}

		$result = parent::onBeforeSave($data, $table);
		
		if (! $result)
		{
			return false;
		}
		
		$files = JFactory::getApplication()->input->files;
		
		$file = $files->get('image');
		
		if ($file['error'] == 0)
		{
			$rand 		= substr(md5(microtime()),rand(0,26),4);
			$firstname 	= $data['firstname'];
			$lastname 	= $data['lastname'];
			$name 		= JFilterOutput::stringURLSafe($firstname.'-'.$lastname);
			$name		= $rand.'_'.$name;
			
			// Filetype
			$filename = ($file['name']);
			$filetype = strstr($filename, '.');
			$filename = $name.$filetype;
			
			// Path
			$filepath = JPath::clean(JPATH_SITE.'/images/volunteers/' . $filename);
			
			// Do the upload
			jimport('joomla.filesystem.file');
			if (!JFile::upload($file['tmp_name'], $filepath))
			{
				$this->setError(JText::_('Upload file error'));
				return false;
			}
			
			// Instantiate our JImage object
			$image = new JImage($filepath);
			
			$sizes = array('250x250', '50x50');
			$image->createThumbs($sizes, JImage::CROP_RESIZE);
			
			// Set image url
			$data['image'] = $filename;
		}
		
		return true;
	}
	
	/**
	 * This method runs after the data is saved to the $table and syncs the user email address from the volunteers table
	 * to the users table
	 *
	 * @param   FOFTable  &$table  The table which was saved
	 *
	 * @return  boolean
	 */
	protected function onAfterSave(&$table)
	{
		$result = parent::onAfterSave($table);
		
		// Sync email with user table
		$user = JFactory::getUser($table->user_id);
		
		if ($user->email != $table->email)
		{
			$user->email = $table->email;
			
			$result &= $user->save();
		}
		
		return $result;
	}
	
}
