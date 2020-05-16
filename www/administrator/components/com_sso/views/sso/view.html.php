<?php
/**
 * @package     SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2020 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Registry\Registry;

/**
 * SSO view.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class SsoViewSso extends HtmlView
{
	/**
	 * List of Identity Providers
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	protected $identityProviders = array();

	/**
	 * SSO helper
	 *
	 * @var    SsoHelper
	 * @since  1.0.0
	 */
	protected $helper;

	/**
	 * The sidebar to show
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $sidebar = '';

	/**
	 * The SimpleSAMLphp version
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $samlVersion = '';

	/**
	 * The SimpleSAMLphp configuration
	 *
	 * @var    Registry
	 * @since  1.0.0
	 */
	protected $config;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 *
	 * @see     fetch()
	 *
	 * @since   1.0.0
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		/** @var SsoModelSso $model */
		$model                   = $this->getModel();
		$this->identityProviders = $model->getIdentityProviderAliases();
		$this->samlVersion       = $model->getSamlVersion();
		$this->config            = new SsoConfig;

		// Show the toolbar
		$this->toolbar();

		// Show the sidebar
		$this->helper = new SsoHelper;
		$this->helper->addSubmenu('sso');
		$this->sidebar = JHtmlSidebar::render();

		// Display it all
		return parent::display($tpl);
	}

	/**
	 * Displays a toolbar for a specific page.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function toolbar(): void
	{
		ToolBarHelper::title(Text::_('COM_SSO'), 'signup');

		// Options button.
		if (Factory::getUser()->authorise('core.admin', 'com_sso'))
		{
			ToolBarHelper::preferences('com_sso');
		}
	}
}
