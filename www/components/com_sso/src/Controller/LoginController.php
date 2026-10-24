<?php
/**
 * @package     SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

namespace Rolandd\Component\Sso\Site\Controller;

use Exception;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Rolandd\Component\Sso\Administrator\Sso\Sso;
use Rolandd\Component\Sso\Site\Model\LoginModel;
use SimpleSAML\Error\Error as SimpleSamlError;
use SimpleSAML\Logger;

defined('_JEXEC') or die;

/**
 * Login controller.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class LoginController extends BaseController
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
	public function login(): void
	{
		try
		{
			/** @var LoginModel $model */
			$model = $this->getModel('Login');
			$model->setState('authorizationSource', $this->input->getCmd('profile', 'default-sp'));

			$model->processLogin();

			// Check if we need to redirect the user anywhere
			$return = $this->input->getBase64('return');
			$link   = $return ? base64_decode($return) : '';

			if (empty($link))
			{
				$data = $this->app->getUserState('users.login.form.data', []);
				$link = $data['return'] ?? '';
			}

			$helper     = new Sso;
			$parameters = $helper->getParams($model->getState('authorizationSource', 'default-sp'));
			$redirect   = $parameters->get('joomla.redirect');

			if ($redirect !== 'active')
			{
				/** @var AbstractMenu $menu */
				$menu = $this->app->getMenu();
				$menu = $menu->getItem($redirect);
				$link = $menu->link . '&Itemid=' . $menu->id;

				if (Multilanguage::isEnabled())
				{
					$language = $this->app->getIdentity()->getParam('language');
					$link     .= '&lang=' . $language;
				}

				$link = Route::_($link);
			}

			if ($link)
			{
				$this->setRedirect($link);
			}
		}
		catch (SimpleSamlError $exception)
		{
			// SimpleSAML\Error\Error::getMessage() is a raw JSON-encoded errorCode/parameters
			// blob (see SimpleSAML\Error\Error::__construct()), not something an end user
			// should see - log the technical detail and show a friendly message instead.
			Logger::error(
				'SSO login failed: ' . $exception->getErrorCode() . ' ' . $exception->getMessage()
			);

			$this->app->enqueueMessage(Text::_('COM_SSO_LOGIN_FAILED'), 'error');
		}
		catch (Exception $exception)
		{
			$this->app->enqueueMessage($exception->getMessage(), 'error');
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
		/** @var LoginModel $model */
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
