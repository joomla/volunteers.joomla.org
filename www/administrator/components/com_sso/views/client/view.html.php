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
use Joomla\CMS\Toolbar\ToolbarHelper;

defined('_JEXEC') or die;

/**
 * Client view.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class SsoViewClient extends HtmlView
{
	/**
	 * Form with settings
	 *
	 * @var    Form
	 * @since  1.0.0
	 */
	protected $form;

	/**
	 * Provider form with settings
	 *
	 * @var    Form
	 * @since  1.0.0
	 */
	protected $providerForm;

	/**
	 * The item object
	 *
	 * @var    object
	 * @since  1.0.0
	 */
	protected $item;

	/**
	 * Get the state
	 *
	 * @var    object
	 * @since  1.0.0
	 */
	protected $state;

	/**
	 * Access rights of a user
	 *
	 * @var    CMSObject
	 * @since  1.0.0
	 */
	protected $canDo;

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
		/** @var SsoModelClient $model */
		$model       = $this->getModel();
		$this->form  = $model->getForm();
		$this->item  = $model->getItem();
		$this->canDo = ContentHelper::getActions('com_sso');

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
	private function addToolbar(): void
	{
		Factory::getApplication()->input->set('hidemainmenu', true);

		ToolbarHelper::title(Text::_('COM_SSO_client'), 'file-add');

		if ($this->canDo->get('core.edit') || $this->canDo->get('core.create'))
		{
			ToolbarHelper::apply('client.apply');
			ToolbarHelper::save('client.save');
		}

		if ($this->canDo->get('core.create') && $this->canDo->get('core.manage'))
		{
			ToolbarHelper::save2new('client.save2new');
		}

		if ($this->canDo->get('core.create'))
		{
			ToolbarHelper::save2copy('client.save2copy');
		}

		if (0 === $this->item->id)
		{
			ToolbarHelper::cancel('client.cancel');
		}
		else
		{
			ToolbarHelper::cancel('client.cancel', 'JTOOLBAR_CLOSE');
		}
	}
}
