<?php

/**
 * @version    4.0.0
 * @package    Com_Volunteers
 * @author     The Joomla Project <secretary@opensourcematters.org>
 * @copyright  2023 The Joomla Project
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Administrator\Controller;

use DateTimeZone;
use Exception;
use Joomla\CMS\Date\Date;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;
use Joomla\Component\Volunteers\Administrator\Model\MembersModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Members list controller class.
 *
 * @since 4.0.0
 */
class MembersController extends AdminController
{
    /**
     * The headers in the csv export
     *
     * @var    array
     * @since  1.0.0
     */
    private array $headerFields
        = [
            "\xEF\xBB\xBF" . 'Name',
            'E-mail',
            'Position',
            'Team',
        ];

    /**
     * Proxy for getModel
     *
     * @param   string  $name    The model name. Optional.
     * @param   string  $prefix  The class prefix. Optional.
     * @param   array   $config  The array of possible config values. Optional.
     *
     * @return  object  The model.
     * @since 1.0.0
     */
    public function getModel($name = 'Member', $prefix = 'VolunteersModel', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    /**
     * Export members to csv
     *
     * @return void
     *
     * @since 1.0.0
     *
     * @throws Exception
     */
    public function export()
    {
        // Check for request forgeries.
        $this->checkToken();

        /** @var MembersModel $model */
        $model = $this->getModel('Members', 'VolunteersModel', ['ignore_request' => false]);
        //$model = JModelLegacy::getInstance('Members', 'VolunteersModel', array('ignore_request' => false));
        $items = $model->getItems();

        // Output the data in csv format
        $date     = new Date('now', new DateTimeZone('UTC'));
        $filename = 'members_' . $date->format('Y-m-d_His');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename . '.csv');

        $outstream = fopen("php://output", 'w');

        // Insert headers
        fputcsv($outstream, $this->headerFields);

        foreach ($items as $item) {
            fputcsv(
                $outstream,
                [
                    $item->volunteer_name,
                    $item->user_email,
                    $item->position_title,
                    $item->team_title,
                ]
            );
        }

        // Push to the browser so it starts a download
        $this->app->close();
    }

    /**
     * Export members to csv
     *
     * @return void
     * @since 1.0.0
     * @throws Exception
     */
    public function mail()
    {
        // Check for request forgeries.
        $this->checkToken();

        /** @var MembersModel $model */
        $model = $this->getModel('Members', 'VolunteersModel', ['ignore_request' => false]);
        $items = $model->getItems();

        $members = [];

        foreach ($items as $item) {
            $members[] = [
                'name'     => $item->volunteer_name,
                'email'    => $item->user_email,
                'position' => $item->position_title,
                'team'     => $item->team_title,
            ];
        }

        $this->app->getSession()->set('volunteers.recipients', $members);

        $this->setRedirect(Route::_('index.php?option=com_volunteers&view=contact', false));
    }
}
