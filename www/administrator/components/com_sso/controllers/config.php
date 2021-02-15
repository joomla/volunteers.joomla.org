<?php
/**
 * @package     SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2021 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

/**
 * Config Controller.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class SsoControllerConfig extends FormController
{
	/**
	 * Method to save a record.
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since   1.0.0
	 *
	 * @throws  Exception
	 */
	public function save($key = null, $urlVar = null): bool
	{
		// Check for request forgeries.
		Session::checkToken() or exit(Text::_('JINVALID_TOKEN'));

		$app   = Factory::getApplication();
		/** @var SsoModelConfig $model */
		$model = $this->getModel();
		$data  = $this->input->post->get('jform', array(), 'array');
		$context = "$this->option.edit.$this->context";

		// Access check.
		if (!$this->allowSave($data, $key))
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), 'error');

			$this->setRedirect(
				Route::_(
					'index.php?option=' . $this->option . '&view=' . $this->view_list
					. $this->getRedirectToListAppend(), false
				)
			);

			return false;
		}

		// Validate the posted data.
		// Sometimes the form needs some posted data, such as for plugins and modules.
		$form = $model->getForm($data, false);

		if (!$form)
		{
			$app->enqueueMessage($model->getError(), 'error');

			return false;
		}

		// Test whether the data is valid.
		$validData = $model->validate($form, $data);

		// Check for validation errors.
		if ($validData === false)
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
			$app->setUserState($context . '.data', $data);

			// Redirect back to the edit screen.
			$this->setRedirect(
				Route::_(
					'index.php?option=' . $this->option . '&view=' . $this->view_item,
					false
				)
			);

			return false;
		}

		if (!isset($validData['tags']))
		{
			$validData['tags'] = null;
		}

		// Attempt to save the data.
		if (!$model->save($validData))
		{
			// Save the data in the session.
			$app->setUserState($context . '.data', $validData);

			// Redirect back to the edit screen.
			$this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_SAVE_FAILED', $model->getError()), 'error');

			$this->setRedirect(
				Route::_(
					'index.php?option=' . $this->option . '&view=' . $this->view_item,
					false
				)
			);

			return false;
		}

		$langKey = $this->text_prefix . '_SAVE_SUCCESS';
		$prefix  = Factory::getLanguage()->hasKey($langKey) ? $this->text_prefix : 'JLIB_APPLICATION';

		$this->setMessage(Text::_($prefix . '_SAVE_SUCCESS'));

		// Clear the record id and data from the session.
		$app->setUserState($context . '.data', null);

		$url = 'index.php?option=' . $this->option . '&view=config';

		// Check if there is a return value
		$return = $this->input->get('return', null, 'base64');

		if (!is_null($return) && Uri::isInternal(base64_decode($return)))
		{
			$url = base64_decode($return);
		}

		// Redirect to the list screen.
		$this->setRedirect(Route::_($url, false));

		// Invoke the postSave method to allow for the child class to access the model.
		$this->postSaveHook($model, $validData);

		return true;
	}
}
