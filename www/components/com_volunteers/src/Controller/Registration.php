<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Site\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Exception;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;

/**
 * Registration controller class.
 *
 * @since 4.0.0
 */
class RegistrationController extends FormController
{
    protected $view_list = 'volunteers';

    /**
     * Method to register a user.
     *
     * @return  boolean  True on success, false on failure.
     *
     * @since   1.6
     * @throws Exception
     */
    public function register()
    {
        // Check for request forgeries.
        $this->checkToken();

        $model = $this->getModel('Registration', 'Site');

        // Get the user data.
        $requestData = $this->input->post->get('jform', [], 'array');

        // Validate the posted data.
        $form = $model->getForm();

        if (!$form) {
            throw new Exception('', 500);
            //          JError::raiseError(500, $model->getError()); HOW TO FIX THIS?

            //return false;
        }

        $data = $model->validate($form, $requestData);

        // Check for field that should be empty to prevent spam
        if ($data['address']) {
            // Redirect to the profile screen, pretend success.
            $this->setMessage(Text::_('COM_USERS_REGISTRATION_SAVE_SUCCESS'));
            $this->setRedirect(Route::_('index.php?option=com_users&view=login', false));

            return true;
        }

        // Check for validation errors.
        if ($data === false) {
            // Get the validation messages.
            $errors = $this->getModel('Volunteer', 'VolunteersModel')->getErrors();

            // Push up to three validation messages out to the user.
            for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++) {
                if ($errors[$i] instanceof Exception) {
                    $this->app->enqueueMessage($errors[$i]->getMessage(), 'warning');
                } else {
                    $this->app->enqueueMessage($errors[$i], 'warning');
                }
            }

            // Save the data in the session.
            $this->app->setUserState('com_volunteers.registration.data', $requestData);

            // Redirect back to the registration screen.
            $this->setRedirect(Route::_('index.php?option=com_volunteers&view=registration', false));

            return false;
        }

        // Attempt to save the data.
        $return = $model->register($data);

        // Check for errors.
        if ($return === false) {
            // Get the validation messages.
            $errors = $model->getErrors();

            // Push up to three validation messages out to the user.
            for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++) {
                if ($errors[$i] instanceof Exception) {
                    $this->app->enqueueMessage($errors[$i]->getMessage(), 'warning');
                } else {
                    $this->app->enqueueMessage($errors[$i], 'warning');
                }
            }

            // Save the data in the session.
            $this->app->setUserState('com_volunteers.registration.data', $data);

            // Redirect back to the edit screen.
            $this->setRedirect(Route::_('index.php?option=com_volunteers&view=registration', false));

            return false;
        }

        // Flush the data from the session.
        $this->app->setUserState('com_volunteers.registration.data', null);

        // Get the log in credentials.
        $credentials = [
            'username' => $data['email'],
            'password' => $data['password1'],
        ];

        // Perform the log in.
        $this->app->login($credentials, ['remember' => true]);

        // Volunteer ID
        $volunteerId = $this->app->getUserState('com_volunteers.registration.id');

        // Redirect to the profile screen.
        $this->setRedirect(Route::_('index.php?option=com_volunteers&view=volunteer&id=' . $volunteerId . '&new=1', false));

        return true;
    }
}
