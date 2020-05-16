<?php
/**
 * @package     SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2020 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Menu\AbstractMenu;

defined('_JEXEC') or die;

/**
 * RO SSO router.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class SsoRouter extends RouterView
{
	/**
	 * Class constructor.
	 *
	 * @param   CMSApplication  $app   Application-object that the router should use
	 * @param   AbstractMenu    $menu  Menu-object that the router should use
	 *
	 * @since   1.0.0
	 */
	public function __construct(CMSApplication $app = null, AbstractMenu $menu = null)
	{
		// Register the list view
		$sso = new JComponentRouterViewconfiguration('sso');
		$this->registerView($sso);

		// Register the login view
		$login = new JComponentRouterViewconfiguration('login');
		$this->registerView($login);

		// Register the logout view
		$logout = new JComponentRouterViewconfiguration('logout');
		$this->registerView($logout);

		// Invoke the constructor
		parent::__construct($app, $menu);
	}
}
