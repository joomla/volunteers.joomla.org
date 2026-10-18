<?php

/**
 * @package    Volunteer Portal
 *
 * @copyright  (C) 2023 Open Source Matters, Inc.  <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Administrator\View\Setupdemo;

use Joomla\Component\Volunteers\Administrator\Model\SetupdemoModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Exception;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Registry\Registry;
use SimpleXMLElement;

/**
 * View class for a single Copyvolunteer3data.
 *
 * @since  4.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The item object
     *
     * @var    object
     * @since  4.0.0
     */
    protected mixed $item;

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
         * The model state
         *
         * @var    Registry
     *
         * @since  4.0.0
         */
    protected mixed $state;
    /**
         * Component Parameters
         *
         * @var    Registry|null
     *
         * @since  4.0.0
         */
    protected ?Registry $params = null;
    /**
         * Migration SQL
         *
         * @var    SimpleXMLElement
         * @since  4.0.0
         */
    protected SimpleXMLElement $migrate_xml;
    /**
         * Action Task
         *
         * @var    string
         * @since  4.0.0
         */
    protected string $task;
    /**
         * Add the page title and toolbar.
         *
         * @since  4.0.0
         * @throws Exception
         */
    private function addToolbar()
    {
        ToolBarHelper::title('Setup Demo Menu');
        $user = $this->getCurrentUser();
        $toolbar = $this->getDocument()->getToolbar();

        if (
            $user->authorise('core.admin', 'com_volunteer')
            || $user->authorise(
                'core.options',
                'com_volunteer'
            )
        ) {
            $toolbar->preferences('com_volunteer');
        }
    }

    /**
     * Execute and display a template script.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  void
     *
     * @since  4.0.0
     * @throws  Exception
     */
    public function display($tpl = null)
    {
        /** @var SetupdemoModel $model */
        $model = $this->getModel();
        $model->setUseExceptions(true);
        
        $this->state       = $model->getState();
        $this->item        = $model->getItem();
        $this->params      = ComponentHelper::getParams('com_volunteer');
        $input             = $this->app->getInput()->getInputForRequestMethod();
        $this->task        = $input->get('task', '');
        $this->addToolbar();
        parent::display($tpl);
    }
}
