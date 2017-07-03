<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * Registration model class.
 */
class VolunteersModelRegistration extends JModelForm
{
	/**
	 * @var    object  The user registration data.
	 */
	protected $data;

	/**
	 * Constructor
	 *
	 * @param   array $config An array of configuration options (name, state, dbo, table_path, ignore_request).
	 *
	 * @throws  Exception
	 */
	public function __construct($config = array())
	{
		$config = array_merge(
			array(
				'events_map' => array('validate' => 'user')
			), $config
		);

		parent::__construct($config);
	}

	/**
	 * Method to get the registration form data.
	 *
	 * The base form data is loaded and then an event is fired
	 * for users plugins to extend the data.
	 *
	 * @return  mixed  Data object on success, false on failure.
	 */
	public function getData()
	{
		if ($this->data === null)
		{
			$this->data = new stdClass;
			$app        = JFactory::getApplication();
			$params     = JComponentHelper::getParams('com_volunteers');

			// Override the base user data with any data in the session.
			$temp = (array) $app->getUserState('com_volunteers.registration.data', array());

			foreach ($temp as $k => $v)
			{
				$this->data->$k = $v;
			}

			// Get the groups the user should be added to after registration.
			$this->data->groups = array();

			// Get the default new user group, Registered if not specified.
			$system = $params->get('new_usertype', 2);

			$this->data->groups[] = $system;

			// Unset the passwords.
			unset($this->data->password1);
			unset($this->data->password2);

			// Get the dispatcher and load the users plugins.
			$dispatcher = JEventDispatcher::getInstance();
			JPluginHelper::importPlugin('user');

			// Trigger the data preparation event.
			$results = $dispatcher->trigger('onContentPrepareData', array('com_volunteers.registration', $this->data));

			// Check for errors encountered while preparing the data.
			if (count($results) && in_array(false, $results, true))
			{
				$this->setError($dispatcher->getError());
				$this->data = false;
			}
		}

		return $this->data;
	}

	/**
	 * Method to get the registration form.
	 *
	 * The base form is loaded from XML and then an event is fired
	 * for users plugins to extend the form with extra fields.
	 *
	 * @param   array   $data     An optional array of data for the form to interogate.
	 * @param   boolean $loadData True if the form is to load its own data (default case), false if not.
	 *
	 * @return  JForm  A JForm object on success, false on failure
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_volunteers.registration', 'registration', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 */
	protected function loadFormData()
	{
		$data = $this->getData();

		$this->preprocessData('com_volunteers.registration', $data);

		return $data;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array $temp The form data.
	 *
	 * @return  mixed  The user id on success, false on failure.
	 */
	public function register($temp)
	{
		$data = (array) $this->getData();

		// Merge in the registration data.
		foreach ($temp as $k => $v)
		{
			$data[$k] = $v;
		}

		// Volunteer Model
		$volunteer = JModelLegacy::getInstance('Volunteer', 'VolunteersModel', array('ignore_request' => true));

		if (!$volunteer->save($data))
		{
			$this->setError(JText::sprintf('COM_VOLUNTEERS_REGISTRATION_SAVE_FAILED', $volunteer->getError()));

			return false;
		}

		// Global config
		$config = JFactory::getConfig();

		// Compile the notification mail values.
		$data['fromname'] = $config->get('fromname');
		$data['mailfrom'] = $config->get('mailfrom');
		$data['sitename'] = $config->get('sitename');
		$data['siteurl']  = JUri::root();

		$emailSubject = JText::sprintf(
			'COM_USERS_EMAIL_ACCOUNT_DETAILS',
			$data['name'],
			$data['sitename']
		);

		$emailBody = JText::sprintf(
			'COM_USERS_EMAIL_REGISTERED_BODY_NOPW',
			$data['name'],
			$data['sitename'],
			$data['siteurl']
		);

		// Send the registration email.
		JFactory::getMailer()->sendMail($data['mailfrom'], $data['fromname'], $data['email'], $emailSubject, $emailBody);

		return $volunteer;
	}
}
