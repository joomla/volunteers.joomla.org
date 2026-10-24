<?php
/**
 * @package    SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

namespace Rolandd\Component\Sso\Administrator\View\Certificate;

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Registry\Registry;
use Rolandd\Component\Sso\Administrator\Model\CertificateModel;

defined('_JEXEC') or die;

/**
 * Certificate view.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * Form with settings
	 *
	 * @var    Form
	 * @since  1.0.0
	 */
	protected Form $form;

	/**
	 * Access rights of a user
	 *
	 * @var    Registry
	 * @since  1.0.0
	 */
	protected Registry $canDo;

	/**
	 * List of existing certificates
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	protected array $certificates = [];

    /**
     * Execute and display a template script.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  void
     *
     * @throws  Exception
     * @since   1.0.0
     */
	public function display($tpl = null)
	{
		/** @var CertificateModel $model */
		$model              = $this->getModel();
		$this->form         = $model->getForm();
		$this->certificates = $model->getCertificates();
		$this->canDo        = ContentHelper::getActions('com_sso');

		$this->addToolbar();

		parent::display($tpl);
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
	private function addToolbar(): void
	{
        Factory::getApplication()->input->set('hidemainmenu', true);
        $toolbar = Toolbar::getInstance();

		ToolbarHelper::title(Text::_('COM_SSO_CERTIFICATE'), 'file-2');

		if ($this->canDo->get('core.edit') || $this->canDo->get('core.create'))
		{
			$toolbar->apply('certificate.apply', 'COM_SSO_GENERATE_CERTIFICATE');
		}

        $toolbar->cancel('certificate.cancel');

        $toolbar->divider();
        $toolbar->inlinehelp();
	}
}
