<?php
/**
 * @package     SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2018 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

/**
 * Login controller.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class SsoControllerLogin extends BaseController
{
	/**
	 * Logs a user in using SAML.
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 *
	 * @since   1.0.0
	 */
	public function login()
	{
		try
		{
			/** @var SsoModelLogin $model */
			$model = $this->getModel('Login');
			$model->setState('authorizationSource', $this->input->getCmd('profile', 'default-sp'));

			$model->processLogin();

			// Check if we need to redirect the user anywhere
			$helper     = new SsoHelper;
			$parameters = $helper->getParams($model->setState('authorizationSource', 'default-sp'));
			$menu       = Factory::getApplication()->getMenu();
			$menu       = $menu->getItem($parameters->get('joomla.redirect'));

			if ($menu)
			{
				$this->setRedirect(Route::_($menu->link));
			}
		}
		catch (Exception $exception)
		{
			Factory::getApplication()->enqueueMessage($exception->getMessage(), 'error');
		}
	}

	/**
	 * Log a user out of the system.
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 *
	 * @since   1.0.0
	 */
	public function logout()
	{
		/** @var SsoModelLogin $model */
		$model = $this->getModel('Login');
		$model->setState('authorizationSource', $this->input->getCmd('profile', 'default-sp'));

		$redirectUrl = $model->processLogout();

		// Redirect the user to IDP for logout
		if ($redirectUrl)
		{
			$this->setRedirect($redirectUrl);
		}
	}
}
