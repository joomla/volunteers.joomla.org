<?php
/**
 * @package     SSO.Plugin
 * @subpackage  Authentication.sso
 *
 * @copyright   Copyright (C) 2017 - 2018 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;

/**
 * SSO Login Plugin
 *
 * @since  1.0.0
 */
class PlgSystemSso extends CMSPlugin
{
	/**
	 * Application instance
	 *
	 * @var    CMSApplication
	 * @since  1.0.0
	 */
	protected $app;

	/**
	 * Database driver
	 *
	 * @var    JDatabaseDriver
	 * @since  1.0.0
	 */
	protected $db;

	/**
	 * Check if a user needs to be logged-in via SSO.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function onAfterInitialise()
	{
		$input = $this->app->input;

		if ($this->app->isClient('site') && $input->get('option') !== 'com_sso' && (int) Factory::getUser()->id === 0)
		{
			// Automatically log users in
			if ($this->params->get('autoLogin', false))
			{
				// Get the referer URL
				$uri    = Uri::getInstance();
				$return = base64_encode($uri->toString());
				$this->app->redirect(
					Uri::root() .
					'index.php?option=com_sso&task=login.login&profile=' . $this->params->get('profile', 'default-sp') . '&return=' . $return
				);
			}

			// Prevent users from accessing the Joomla com_users login
			if ($this->params->get('preventLogin', false))
			{
				$redirect = false;
				$uri      = Uri::getInstance();

				if (($input->getCmd('option') === 'com_users' && $uri->getVar('view', '') === '')
					|| ($input->getCmd('option') === 'com_users' || $uri->getPath() === '/component/users/') && $uri->getVar('view') === 'login')
				{
					$redirect = true;
				}

				if (!$redirect)
				{
					$itemId = $input->getInt('Itemid');

					if ($itemId)
					{
						$menuLink = $this->getMenuLink($itemId);
						$menuUri  = Uri::getInstance($menuLink);

						if ($menuUri->getVar('option') === 'com_users' && $menuUri->getVar('view') === 'login')
						{
							$redirect = true;
						}
					}
				}

				if ($redirect)
				{
					// Get the menu link
					$menuLink = $this->getMenuLink($this->params->get('menuRedirect', 0));
					$url      = '/';

					if ($menuLink !== null)
					{
						$url = Route::_($menuLink);
					}

					$this->app->redirect($url);
				}
			}
		}
	}

	/**
	 * Find the item ID for a given view.
	 *
	 * @param   integer  $id  The id of the menu to load
	 *
	 * @return  string  The menu link.
	 *
	 * @since   1.0.0
	 */
	private function getMenuLink(int $id): string
	{
		$db    = $this->db;
		$query = $db->getQuery(true)
			->select($db->quoteName('link'))
			->from($db->quoteName('#__menu'))
			->where($db->quoteName('id') . ' = ' . (int) $id);
		$db->setQuery($query);

		return $db->loadResult();
	}
}
