<?php
/**
 * @package     SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

namespace Rolandd\Component\Sso\Administrator\View\Profiles;

use Exception;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Toolbar\Button\DropdownButton;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Registry\Registry;
use Rolandd\Component\Sso\Administrator\Model\ProfilesModel;
use Rolandd\Component\Sso\Administrator\Sso\Config;

defined('_JEXEC') or die;

/**
 * Profiles view.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * Array with profiles
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	protected array $items;

	/**
	 * Pagination class
	 *
	 * @var    Pagination
	 * @since  1.0.0
	 */
	protected Pagination $pagination;

	/**
	 * The user state
	 *
	 * @var    Registry
	 * @since  1.0.0
	 */
	protected Registry $state;

	/**
	 * Access rights of a user
	 *
	 * @var    Registry
	 * @since  1.1.0
	 */
	protected Registry $canDo;

	/**
	 * Form with filters
	 *
	 * @var    Form
	 * @since  1.0.0
	 */
	public Form $filterForm;

	/**
	 * List of active filters
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	public array $activeFilters = [];

	/**
	 * The SimpleSAMLphp configuration
	 *
	 * @var    Config
	 * @since  1.0.0
	 */
	protected Config $config;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 *
	 * @since   1.0.0
	 */
	public function display($tpl = null)
	{
		/** @var ProfilesModel $model */
		$model               = $this->getModel();
		$this->items         = $model->getItems();
		$this->pagination    = $model->getPagination();
		$this->state         = $model->getState();
		$this->canDo         = ContentHelper::getActions('com_sso');
		$this->filterForm    = $model->getFilterForm();
		$this->activeFilters = $model->getActiveFilters();
		$this->config        = new Config;

		$this->addToolbar();

		parent::display($tpl);
	}

	/**
	 * Displays a toolbar for a specific page.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function addToolbar(): void
	{
		ToolbarHelper::title(Text::_('COM_SSO_PROFILES'), 'broadcast');
        $canDo = $this->canDo;
        $toolbar = Toolbar::getInstance();

        if ($canDo->get('core.create')) {
            $toolbar->addNew('profile.add');
        }

        if ($canDo->get('core.edit.state')) {
            /** @var DropdownButton $dropdown */
            $dropdown = $toolbar->dropdownButton('status-group')
                ->text('JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);

            $childBar = $dropdown->getChildToolbar();

            if ($canDo->get('core.edit.state')) {
                $childBar->publish('profiles.publish')->listCheck(true);
                $childBar->unpublish('profiles.unpublish')->listCheck(true);
            }

            $childBar->delete('profiles.delete', 'JTOOLBAR_DELETE')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
        }

		if ($this->canDo->get('core.create'))
		{
			ToolbarHelper::custom('profiles.refresh', 'refresh', 'refresh', Text::_('COM_SSO_REFRESH_CLIENTS'));
		}
	}
}
