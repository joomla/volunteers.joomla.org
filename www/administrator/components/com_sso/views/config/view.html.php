<?php
/**
 * @package     SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2018 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;

defined('_JEXEC') or die;

/**
 * SSO view.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class SsoViewConfig extends HtmlView
{
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
	 * THe configuration form
	 *
	 * @var    Form
	 * @since  1.0.0
	 */
	protected $form;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 *
	 * @see     fetch()
	 * @since   1.0.0
	 */
	public function display($tpl = null)
	{
		$this->form  = $this->get('Form');

		// Show the toolbar
		$this->toolbar();

		// Show the sidebar
		$this->helper = new SsoHelper;
		$this->helper->addSubmenu('config');
		$this->sidebar = JHtmlSidebar::render();

		// Display it all
		return parent::display($tpl);
	}

	/**
	 * Displays a toolbar for a specific page.
	 *
	 * @return  void.
	 *
	 * @since   1.0.0
	 */
	private function toolbar()
	{
		JToolBarHelper::title(Text::_('COM_SSO_CONFIG'), 'equalizer');
		JToolbarHelper::apply('config.save');
	}
}
