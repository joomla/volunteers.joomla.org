<?php
/**
 * @package     SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

namespace Rolandd\Component\Sso\Site\Service;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Menu\AbstractMenu;

defined('_JEXEC') or die;

/**
 * RO SSO router.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class Router extends RouterView
{
    /**
     * Class constructor.
     *
     * @param   SiteApplication|null  $app   Application-object that the router should use
     * @param   AbstractMenu|null     $menu  Menu-object that the router should use
     *
     * @since   1.0.0
     */
	public function __construct($app = null, $menu = null)
	{
		// Register the list view
		$sso = new RouterViewconfiguration('sso');
		$this->registerView($sso);

		// Register the login view
		$login = new RouterViewconfiguration('login');
		$this->registerView($login);

		// Register the logout view
		$logout = new RouterViewconfiguration('logout');
		$this->registerView($logout);

		// Invoke the constructor
		parent::__construct($app, $menu);

        $this->attachRule(new MenuRules($this));
        $this->attachRule(new StandardRules($this));
        $this->attachRule(new NomenuRules($this));
	}
}
