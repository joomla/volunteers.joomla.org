<?php
/**
 * @package    SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2020 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Object\CMSObject;

defined('_JEXEC') or die;

/**
 * Certificate view.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class SsoViewCertificate extends HtmlView
{
	/**
	 * Form with settings
	 *
	 * @var    Form
	 * @since  1.0.0
	 */
	protected $form;

	/**
	 * Access rights of a user
	 *
	 * @var    CMSObject
	 * @since  1.0.0
	 */
	protected $canDo;

	/**
	 * List of existing certificates
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	protected $certificates = array();

	/**
	 * The sidebar to show
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $sidebar = '';

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 *
	 * @since   1.0.0
	 *
	 * @throws  Exception
	 */
	public function display($tpl = null)
	{
		/** @var SsoModelCertificate $model */
		$model              = $this->getModel();
		$this->form         = $model->getForm();
		$this->certificates = $model->getCertificates();
		$this->canDo        = ContentHelper::getActions('com_sso');

		// Render the sidebar
		$helper = new SsoHelper;
		$helper->addSubmenu('certificate');
		$this->sidebar = JHtmlSidebar::render();

		// Add the toolbar
		$this->addToolbar();

		// Display it all
		return parent::display($tpl);
	}

	/**
	 * Displays a toolbar for a specific page.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 *
	 * @throws  Exception
	 */
	private function addToolbar()
	{
		JToolbarHelper::title(Text::_('COM_SSO_CERTIFICATE'), 'file-2');

		if ($this->canDo->get('core.edit') || $this->canDo->get('core.create'))
		{
			JToolbarHelper::apply('certificate.apply', 'COM_SSO_GENERATE_CERTIFICATE');
		}
	}
}
