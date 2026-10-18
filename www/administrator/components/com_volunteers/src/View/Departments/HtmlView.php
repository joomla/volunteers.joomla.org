<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Administrator\View\Departments;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Pagination\Pagination;
use Joomla\Registry\Registry;
use Joomla\CMS\Form\Form;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Exception;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

use Joomla\CMS\Toolbar\Button\DropdownButton;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Volunteers\Administrator\Model\DepartmentsModel;

/**
 * View class for a list of departments.
 *
 * @since  4.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * An array of items
     *
     * @var  array
     */
    protected $items;

    /**
     * The pagination object
     *
     * @var Pagination
     *
     * @since  1.6
     */
    protected $pagination;

    /**
     * The model state
     *
     * @var Registry
     *
     * @since  1.6
     */
    protected $state;

    /**
     * Is this view an Empty State
     *
     * @var    boolean
     *
     * @since 4.0.0
     */
    private $isEmptyState = false;

    /**
     * Form object for search filters
     *
     * @var Form
     */
    public $filterForm;

    /**
     * The active search filters
     *
     * @var  array
     */
    public $activeFilters;

    /**
     * @var CMSApplicationInterface
     * @since  6.1.0
     */
    protected $app;

    /**
     * Constructor
     *
     * @param   array  $config  A named configuration array for object construction.
     *
     * @since   4.0.0
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->app = \Joomla\CMS\Factory::getApplication();
    }

    /**
     * Display the view
     *
     * @param   string  $tpl  Template name
     *
     * @return  void
     *
     * @throws Exception
     *
     * @since 4.0.0
     */
    public function display($tpl = null)
    {
        /** @var DepartmentsModel $model */
        $model               = $this->getModel();
        $model->setUseExceptions(true);
        
        $this->state         = $model->getState();
        $this->items         = $model->getItems();
        $this->pagination    = $model->getPagination();
        $this->filterForm    = $model->getFilterForm();
        $this->activeFilters = $model->getActiveFilters();
        $errors              = $model->getErrors();
        if ($errors && count($errors) > 0) {
            throw new GenericDataException(implode("\n", $errors));
        }

        $this->addToolbar();
        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     *
     * @since   4.0.0
     * @throws Exception
     */
    private function addToolbar()
    {
        $canDo = ContentHelper::getActions('com_volunteers');
        $user  = $this->getCurrentUser();
        ToolbarHelper::title(Text::_('COM_VOLUNTEERS') . ': ' . Text::_('COM_VOLUNTEERS_TITLE_DEPARTMENTS'), 'joomla');
        $toolbar = $this->getDocument()->getToolbar();
        if ($canDo->get('core.create')) {
            $toolbar->addNew('department.add');
        }

        if (!$this->isEmptyState && $canDo->get('core.edit.state')) {
            /** @var  DropdownButton $dropdown */
            $dropdown = $toolbar->dropdownButton('status-group', 'JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);

            $childBar = $dropdown->getChildToolbar();

            $childBar->publish('departments.publish')->listCheck(true);
            $childBar->unpublish('departments.unpublish')->listCheck(true);
            $childBar->archive('departments.archive')->listCheck(true);

            if ($user->authorise('core.admin')) {
                $childBar->checkin('departments.checkin');
            }

            if ($this->state->get('filter.published') != -2) {
                $childBar->trash('departments.trash')->listCheck(true);
            }
        }

        if (!$this->isEmptyState && $this->state->get('filter.published') == -2 && $canDo->get('core.delete')) {
            $toolbar->delete('departments.delete', 'JTOOLBAR_EMPTY_TRASH')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
        }

        if ($user->authorise('core.admin', 'com_volunteers') || $user->authorise('core.options', 'com_volunteers')) {
            $toolbar->preferences('com_volunteers');
        }
    }
}
