<?php

/**
 * @package    Com_Volunteers
 * @version    4.0.0
 * @author     The Joomla Project <secretary@opensourcematters.org>
 * @copyright  2023 The Joomla Project
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Site\View\My;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\Component\Volunteers\Administrator\Extension\VolunteersComponent;
use Joomla\Component\Volunteers\Administrator\Model\VolunteerModel;

/**
 * View class for a list of Volunteers.
 *
 * @since  4.0.0
 */
class HtmlView extends BaseHtmlView
{
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
     *
     */
    public function display($tpl = null)
    {
        /** @var VolunteersComponent $extension */
        $extension = $this->app->bootComponent('com_volunteers');
        /** @var VolunteerModel $model */
        $model = $extension->getMVCFactory()->createModel('Volunteer', 'Administrator', ['ignore_request' => true]);

        $user        = $this->getCurrentUser();
        $userId      = (int) $user->id;
        $volunteerId = (int) $model->getVolunteerId($userId);

        if ($volunteerId) {
            $this->app->redirect(Route::_('index.php?option=com_volunteers&view=volunteer&id=' . $volunteerId, false));
        }
        parent::display($tpl);
    }
}
