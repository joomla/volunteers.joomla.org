<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Administrator\View\Contact;

use Joomla\Component\Volunteers\Administrator\Model\ContactModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Exception;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View to contact active volunteers.
 *
 * @since  4.0.0
 */
class HtmlView extends BaseHtmlView
{
    protected mixed $form;

    protected array $recipients;

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
     * @param   string  $tpl  Template
     *
     * @return  void
     *
     * @since 4.0.0
     * @throws Exception
     */
    public function display($tpl = null)
    {
        /** @var ContactModel $model */
        $model = $this->getModel();
        $model->setUseExceptions(true);
        
        /** @var Form form */
        $this->form       = $model->getForm();
        $this->recipients = $this->app->getSession()->get('volunteers.recipients');

        $this->addToolbar();
        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     *
     * @since 4.0.0
     * @throws Exception
     */
    protected function addToolbar()
    {
        $this->app->input->set('hidemainmenu', true);

        $canDo = ContentHelper::getActions('com_volunteers');

        // Set toolbar title
        ToolbarHelper::title(Text::_('COM_VOLUNTEERS') . ': ' . Text::_('COM_VOLUNTEERS_TITLE_CONTACT'), 'joomla');
        $toolbar = $this->getDocument()->getToolbar();

        if ($canDo->get('core.manage')) {
            $toolbar->custom('contact.send', 'mail', 'mail', 'COM_VOLUNTEERS_CONTACT_SEND', false);
        }

        $toolbar->cancel('contact.cancel');
    }
}
