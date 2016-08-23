<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * Registration controller class.
 */
class VolunteersControllerRegistration extends JControllerForm
{
	/**
	 * Constructor
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->addModelPath(JPATH_ADMINISTRATOR . '/components/com_volunteers/models', 'VolunteersModel');
	}

	/**
	 * Method to register a user.
	 *
	 * @return  boolean  True on success, false on failure.
	 *
	 * @since   1.6
	 */
	public function register()
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$app   = JFactory::getApplication();
		$model = $this->getModel('Registration', 'VolunteersModel');

		// Get the user data.
		$requestData = $this->input->post->get('jform', array(), 'array');

		// Validate the posted data.
		$form = $model->getForm();

		if (!$form)
		{
			JError::raiseError(500, $model->getError());

			return false;
		}

		$data = $model->validate($form, $requestData);

		// Check for field that should be empty to prevent spam
		if ($data['address'])
		{
			// Redirect to the profile screen, pretend success.
			$this->setMessage(JText::_('COM_USERS_REGISTRATION_SAVE_SUCCESS'));
			$this->setRedirect(JRoute::_('index.php?option=com_users&view=login', false));

			return true;
		}

		// Check for validation errors.
		if ($data === false)
		{
			// Get the validation messages.
			$errors = $this->getModel('Volunteer', 'VolunteersModel')->getErrors();

			// Push up to three validation messages out to the user.
			for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++)
			{
				if ($errors[$i] instanceof Exception)
				{
					$app->enqueueMessage($errors[$i]->getMessage(), 'warning');
				}
				else
				{
					$app->enqueueMessage($errors[$i], 'warning');
				}
			}

			// Save the data in the session.
			$app->setUserState('com_volunteers.registration.data', $requestData);

			// Redirect back to the registration screen.
			$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=registration', false));

			return false;
		}

		// Attempt to save the data.
		$return = $model->register($data);

		// Check for errors.
		if ($return === false)
		{
			// Get the validation messages.
			$errors = $model->getErrors();

			// Push up to three validation messages out to the user.
			for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++)
			{
				if ($errors[$i] instanceof Exception)
				{
					$app->enqueueMessage($errors[$i]->getMessage(), 'warning');
				}
				else
				{
					$app->enqueueMessage($errors[$i], 'warning');
				}
			}

			// Save the data in the session.
			$app->setUserState('com_volunteers.registration.data', $data);

			// Redirect back to the edit screen.
			$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=registration', false));

			return false;
		}

		// Flush the data from the session.
		$app->setUserState('com_volunteers.registration.data', null);

		// Get the log in credentials.
		$credentials = array(
			'username' => $data['email'],
			'password' => $data['password1']
		);

		// Perform the log in.
		$app->login($credentials, array('remember' => true));

		// Volunteer ID
		$volunteerId = $app->getUserState('com_volunteers.registration.id');

		// Redirect to the profile screen.
		$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $volunteerId . '&new=1', false));

		return true;
	}
}
