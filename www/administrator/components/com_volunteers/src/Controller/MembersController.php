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
use Joomla\Utilities\ArrayHelper;

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
    public function getModel($name = 'Member', $prefix = 'Administrator', $config = ['ignore_request' => true])
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

        $items = $this->getSelectedItems();

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

        $items = $this->getSelectedItems();

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

    /**
     * Get the members selected in the list, in the order of the list
     *
     * @return  array  The selected members
     *
     * @since   4.0.0
     * @throws  Exception
     */
    private function getSelectedItems(): array
    {
        $ids = array_filter(ArrayHelper::toInteger((array) $this->input->get('cid', [], 'array')));

        if (!$ids) {
            return [];
        }

        /** @var MembersModel $model */
        $model = $this->getModel('Members', 'Administrator', ['ignore_request' => false]);

        // Load the list state first, so the selection is not limited by the filters or the page
        $model->getState();
        $model->setState('filter.ids', $ids);
        $model->setState('list.start', 0);
        $model->setState('list.limit', 0);

        foreach (['search', 'published', 'department', 'departmentTeam', 'team', 'volunteer', 'position', 'active', 'type', 'teamStatus', 'osmStatus'] as $filter) {
            $model->setState('filter.' . $filter, null);
        }

        return $model->getItems() ?: [];
    }
}
