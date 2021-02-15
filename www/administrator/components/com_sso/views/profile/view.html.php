<?php
/**
 * @package    SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2021 RolandD Cyber Produksi. All rights reserved.
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
 * Profile view.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class SsoViewProfile extends HtmlView
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
	 * @throws  Exception
	 *
	 * @since   1.0.0
	 */
	public function display($tpl = null)
	{
		/** @var SsoModelProfile $model */
		$model       = $this->getModel();
		$this->form  = $model->getForm();
		$this->item  = $model->getItem();
		$this->state = $model->getState();
		$this->canDo = ContentHelper::getActions('com_sso');

		// Load the PSP form
		$this->providerForm = $model->getProviderForm('serviceprovider');

		if ($this->providerForm)
		{
			$this->providerForm->bind($this->item->params);
		}

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
	 * @throws  Exception
	 *
	 * @since   1.0.0
	 */
	private function addToolbar()
	{
		Factory::getApplication()->input->set('hidemainmenu', true);

		ToolbarHelper::title(Text::_('COM_SSO_PROFILE'), 'broadcast');

		if ($this->canDo->get('core.edit') || $this->canDo->get('core.create'))
		{
			ToolbarHelper::apply('profile.apply');
			ToolbarHelper::save('profile.save');
		}

		if ($this->canDo->get('core.create') && $this->canDo->get('core.manage'))
		{
			ToolbarHelper::save2new('profile.save2new');
		}

		if ($this->canDo->get('core.create'))
		{
			ToolbarHelper::save2copy('profile.save2copy');
		}

		if (0 === $this->item->id)
		{
			ToolbarHelper::cancel('profile.cancel');
		}
		else
		{
			ToolbarHelper::cancel('profile.cancel', 'JTOOLBAR_CLOSE');
		}
	}
}
