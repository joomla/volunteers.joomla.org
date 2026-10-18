<?php

/**
 * @package    Com_Volunteers
 * @version    4.0.0
 * @author     The Joomla Project <secretary@opensourcematters.org>
 * @copyright  2023 The Joomla Project
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Site\Model;

use Joomla\Component\Volunteers\Administrator\Model\ReportsModel;
use Joomla\Component\Volunteers\Administrator\Model\VolunteersModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Exception;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Router\Route;
use Joomla\Component\Volunteers\Site\Helper\VolunteersHelper;

/**
 * Home model.
 *
 * @since  4.0.0
 */
class HomeModel extends ListModel
{
    /**
     * Method to get Latest Reports.
     *
     * @return  mixed  Data object on success, false on failure.
     *
     * @since 4.0.0
     * @throws Exception
     */
    public function getLatestReports()
    {
        // Get reports
        /** @var ReportsModel $model */
        $model = $this->getMVCFactory()->createModel('Reports', 'Administrator', ['ignore_request' => true]);
        $model->setState('list.limit', 3);

        return $model->getItems();
    }

    /**
     * Method to get Latest Volunteers.
     *
     * @return  mixed  Data object on success, false on failure.
     * @since 4.0.0
     * @throws Exception
     */
    public function getLatestVolunteers()
    {
        // Get volunteers
        /** @var VolunteersModel $model */
        $model = $this->getMVCFactory()->createModel('Volunteers', 'Administrator', ['ignore_request' => true]);
        $model->setState('list.limit', 5);
        $model->setState('list.ordering', 'a.created');
        $model->setState('list.direction', 'desc');
        $model->setState('filter.image', 1);

        return $model->getItems();
    }

    /**
     * Method to get Markers for Google Map.
     *
     * @return  string[]  Data object on success, false on failure.
     * @since 4.0.0
     */
    public function getMapMarkers()
    {
        // Create a new query object.
        $db    = $this->getDatabase();
        $query = $db->createQuery();

        $query
            ->select($db->quoteName(['a.id', 'a.alias', 'user.name', 'a.latitude', 'a.longitude', 'a.image']))
            ->from($db->quoteName('#__volunteers_volunteers') . ' AS a')
            ->join('LEFT', '#__users AS ' . $db->quoteName('user') . ' ON user.id = a.user_id')
            ->where($db->quoteName('latitude') . ' not like \'\'')
            ->where($db->quoteName('longitude') . ' not like \'\'');

        $db->setQuery($query);

        $volunteers = $db->loadObjectList();

        // Map markers
        $markers = [];

        if ($volunteers) {
            // Base Joomlers url
            $joomlers = Route::_('index.php?option=com_volunteers&view=volunteers');

            foreach ($volunteers as $volunteer) {
                $markers[] = json_encode([
                    'title' => $volunteer->name,
                    'lat'   => $volunteer->latitude,
                    'lng'   => $volunteer->longitude,
                    'url'   => $joomlers . '/' . $volunteer->id . '-' . $volunteer->alias,
                    'image' => VolunteersHelper::image($volunteer->image, 'small', true),
                ]);
            }
        }

        return $markers;
    }

    /**
     * Method to get Volunteer Story.
     *
     * @return  mixed  Data object on success, false on failure.
     * @since 4.0.0
     * @throws Exception
     */
    public function getVolunteerStory()
    {
        // Get volunteers story
        /** @var VolunteersModel $model */
        $model = $this->getMVCFactory()->createModel('Volunteers', 'Administrator', ['ignore_request' => true]);
        $model->setState('list.limit', 1);
        $model->setState('list.ordering', 'rand()');
        $model->setState('filter.image', 1);
        $model->setState('filter.joomlastory', 1);

        $items = $model->getItems();

        return $items[0];
    }
}
