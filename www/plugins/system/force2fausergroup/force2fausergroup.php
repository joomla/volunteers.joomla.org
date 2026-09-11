<?php
/**
 * Force2faUsergroup Plugin
 *
 * @copyright  Copyright (C) 2020 Tobias Zulauf All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

/**
 * Plugin class for Fetch Metadata
 *
 * @since  1.0
 */
class PlgSystemForce2faUsergroup extends CMSPlugin
{
	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Listener for the `onAfterInitialise` event
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onAfterInitialise()
	{
		if ($this->isTwoFactorAuthenticationRequired())
		{
			$this->redirectIfTwoFactorAuthenticationRequired();
		}
	}

	/**
	 * Method to catch user login and check whether this use has to setup 2fa
	 *
	 * @param   array  $options  Array holding options (user, responseType)
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function onUserAfterLogin($options)
	{
		if ($this->isTwoFactorAuthenticationRequired())
		{
			$this->redirectIfTwoFactorAuthenticationRequired();
		}
	}

	/**
	 * Checks if 2fa needs to be enforced
	 * if so returns true, else returns false
	 *
	 * Based on: https://github.com/joomla/joomla-cms/blob/4.0.0-beta3/libraries/src/Application/CMSApplication.php#L1163-L1173
	 *
	 * @return  boolean
	 *
	 * @since   1.0.0
	 *
	 * @throws \Exception
	 */
	private function isTwoFactorAuthenticationRequired(): bool
	{
		$user = Factory::getUser();

		if ($user->guest)
		{
			return false;
		}

		// Check session if user has set up 2fa
		if (Factory::getApplication()->getUserState('has2fa', false))
		{
			return false;
		}

		// If the user is not allowed to view the output then end here.
		$forced2faGroups = (array) $this->params->get('force2fausergroups', []);

		if (!empty($forced2faGroups))
		{
			$userGroups = (array) $user->get('groups', []);

			if (!array_intersect($forced2faGroups, $userGroups))
			{
				return false;
			}
		}

		if (!PluginHelper::isEnabled('twofactorauth'))
		{
			return false;
		}

		return !$this->hasUserConfiguredTwoFactorAuthentication();
	}

	/**
	 * Redirects user to his Two Factor Authentication setup page
	 *
	 * Based on: https://github.com/joomla/joomla-cms/blob/4.0.0-beta3/libraries/src/Application/CMSApplication.php#L1246-L1253
	 *
	 * @return void
	 *
	 * @since  1.0.0
	 */
	private function redirectIfTwoFactorAuthenticationRequired(): void
	{
		$app = Factory::getApplication();

		$option = (string) $app->getInput()->get('option');
		$task   = (string) $app->getInput()->get('task');
		$view   = (string) $app->get('view', null, 'string');
		$layout = (string) $app->get('layout', null, 'string');

		if ($app->isClient('site'))
		{
			// If user is already on edit profile screen or press update/apply button, do nothing to avoid infinite redirect
			if (($option === 'com_users' && \in_array($task, ['profile.edit', 'profile.save', 'profile.apply', 'user.logout', 'user.menulogout'], true))
				|| $option === 'com_users' && $view === 'profile' && $layout === 'edit')
			{
				return;
			}

			// Redirect to com_users profile edit
			$app->enqueueMessage(Text::_('PLG_SYSTEM_FORCE2FAUSERGROUP_2FA_REDIRECT_MESSAGE'), 'notice');
			$app->redirect('index.php?option=com_users&view=profile&layout=edit');
		}

		if ($option === 'com_admin' && \in_array($task, ['profile.edit', 'profile.save', 'profile.apply'], true)
			|| ($option === 'com_admin' && $view === 'profile' && $layout === 'edit')
			|| ($option === 'com_users' && \in_array($task, ['user.save', 'user.edit', 'user.apply', 'user.logout', 'user.menulogout'], true))
			|| ($option === 'com_users' && $view === 'user' && $layout === 'edit')
			|| ($option === 'com_login' && \in_array($task, ['save', 'edit', 'apply', 'logout', 'menulogout'], true)))
		{
			return;
		}

		$user = Factory::getUser();

		// With 4.0.0 (https://github.com/joomla/joomla-cms/pull/32771) you are allowed to edit your user via com_users.
		if (version_compare(JVERSION, '4.0.0', 'ge'))
		{
			// Redirect to com_users user edit
			$app->enqueueMessage(Text::_('PLG_SYSTEM_FORCE2FAUSERGROUP_2FA_REDIRECT_MESSAGE'), 'notice');
			$app->redirect('index.php?option=com_users&task=user.edit&id=' . $user->id);
		}

		// With 3.9.22 (https://github.com/joomla/joomla-cms/pull/30751) and 4.0.0 you can configure the 2FA options from your com_admin profile
		if (version_compare(JVERSION, '3.9.22', 'ge'))
		{
			// Redirect to com_admin profile edit
			$app->enqueueMessage(Text::_('PLG_SYSTEM_FORCE2FAUSERGROUP_2FA_REDIRECT_MESSAGE'), 'notice');
			$app->redirect('index.php?option=com_admin&task=profile.edit&id=' . $user->id);
		}

		// Check whether the current user is allowed to edit his user via com_users as in 3.9 you can not edit 2fa in the backend.
		if (($user->authorise('core.edit', 'com_users') && $user->authorise('core.manage', 'com_users'))
			|| $user->authorise('core.admin')
		)
		{
			// Redirect to com_users user edit
			$app->enqueueMessage(Text::_('PLG_SYSTEM_FORCE2FAUSERGROUP_2FA_REDIRECT_MESSAGE'), 'notice');
			$app->redirect('index.php?option=com_users&task=user.edit&id=' . $user->id);
		}

		// We are a user that is allowed to login to the backend but not to access com_users
		// and we are not yet at a version that supports 2FA edit in com_admin or com_users. Redirect to the frontend.
		$app->enqueueMessage(Text::_('PLG_SYSTEM_FORCE2FAUSERGROUP_2FA_REDIRECT_MESSAGE'), 'notice');
		$app->redirect(Uri::root() . 'index.php?option=com_users&view=profile&layout=edit');
	}

	/**
	 * Checks if otpKey and otep for the user are not empty
	 * if any one is empty returns false, else returns true
	 *
	 * Based on: https://github.com/joomla/joomla-cms/blob/4.0.0-beta3/libraries/src/Application/CMSApplication.php#L1288-L1298
	 *
	 * @return  boolean
	 *
	 * @since   1.0.0
	 *
	 * @throws \Exception
	 */
	private function hasUserConfiguredTwoFactorAuthentication(): bool
	{
		$user = Factory::getUser();

		// Check whether there is a 2FA setup for that user
		if (empty($user->otpKey) || empty($user->otep))
		{
			return false;
		}

		// Set session to user has configured 2fa
		Factory::getApplication()->setUserState('has2fa', true);

		return true;
	}
}
