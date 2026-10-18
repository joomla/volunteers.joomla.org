<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Site\View\Role;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Exception;

use Joomla\CMS\User\User;
use Joomla\Component\Volunteers\Site\Model\RoleModel;

/**
 * View to edit a role.
 *
 * @since 4.0.0
 */
class HtmlView extends BaseHtmlView
{
    protected mixed $state;
    protected mixed $item;
    protected mixed $form;
    protected User|null $user = null;

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
     * Execute and display a template script.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  void
     *
     * @since 4.0.0
     * @throws Exception
     */
    public function display($tpl = null)
    {
        /** @var RoleModel $model */

        $model      = $this->getModel();
        $model->setUseExceptions(true);
        $this->item = $model->getItem();

        $this->state = $model->getState();
        $this->form  = $model->getForm();
        $this->user  = $this->getCurrentUser();

        $errors = $model->getErrors();
        if ($errors && count($errors) > 0) {
            throw new GenericDataException(implode("\n", $errors));
        }

        // Manipulate form
        $this->manipulateForm();

        // Prepare document
        $this->prepareDocument();

        parent::display($tpl);
    }

    /**
     * Manipulates the form.
     *
     * @return  void.
     *
     * @since 4.0.0
     * @throws Exception
     */
    protected function manipulateForm()
    {
        $jinput   = $this->app->getInput();
        $memberId = $jinput->getInt('id');
        $this->form->setFieldAttribute('team', 'readonly', 'true');

        // If editing existing member
        if (!$memberId) {
            $teamId = (int) $this->app->getUserState('com_volunteers.edit.role.teamid');
            $this->form->setValue('team', null, $teamId);
            $this->item->team = $teamId;
        }
    }

    /**
     * Prepares the document.
     *
     * @return  void.
     * @since 4.0.0
     */
    protected function prepareDocument()
    {
        // Prepare variables
        $title = Text::_('COM_VOLUNTEERS_TITLE_ROLES_EDIT');

        // Set meta
        $this->getDocument()->
        setTitle($title);
    }
}
