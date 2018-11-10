<?php
/**
 * @package     SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2018 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Pagination\Pagination;

defined('_JEXEC') or die;

/**
 * Profiles view.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class SsoViewProfiles extends HtmlView
{
	/**
	 * Array with profiles
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	protected $items;

	/**
	 * Pagination class
	 *
	 * @var    Pagination
	 * @since  1.0.0
	 */
	protected $pagination;

	/**
	 * The user state
	 *
	 * @var    CMSObject
	 * @since  1.0.0
	 */
	protected $state;

	/**
	 * Access rights of a user
	 *
	 * @var    CMSObject
	 * @since  1.1.0
	 */
	protected $canDo;

	/**
	 * The sidebar to show
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $sidebar = '';

	/**
	 * Form with filters
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	public $filterForm = array();

	/**
	 * List of active filters
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	public $activeFilters = array();

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 *
	 * @see     fetch()
	 * @since   1.0.0
	 *
	 * @throws  Exception
	 */
	public function display($tpl = null)
	{
		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->state         = $this->get('State');
		$this->canDo         = ContentHelper::getActions('com_sso');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		// Render the sidebar
		$helper = new SsoHelper;
		$helper->addSubmenu('profiles');
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
		JToolbarHelper::title(Text::_('COM_SSO_PROFILES'), 'page');

		if ($this->canDo->get('core.create'))
		{
			JToolbarHelper::addNew('profile.add');
		}

		if ($this->canDo->get('core.edit') || $this->canDo->get('core.edit.own'))
		{
			JToolbarHelper::editList('profile.edit');
		}

		if ($this->canDo->get('core.edit.state'))
		{
			JToolbarHelper::publish('profiles.publish', 'JTOOLBAR_PUBLISH', true);
			JToolbarHelper::unpublish('profiles.unpublish', 'JTOOLBAR_UNPUBLISH', true);
		}

		if ($this->canDo->get('core.delete'))
		{
			JToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'profiles.delete', 'JTOOLBAR_DELETE');
		}
	}
}
