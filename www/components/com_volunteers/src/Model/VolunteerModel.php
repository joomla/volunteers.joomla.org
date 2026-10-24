<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Site\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;
use stdClass;

/**
 * Volunteer model.
 *
 * Joomlers edit their profile on the Joomla! Identity portal, which sends the changes to this site, so this model only reads a Joomler.
 *
 * @since 4.0.0
 */
class VolunteerModel extends ItemModel
{
    /**
     * Model context string.
     *
     * @var    string
     * @since  6.1.0
     */
    protected $_context = 'com_volunteers.volunteer';

    /**
     * Method to auto-populate the model state.
     *
     * @return  void
     *
     * @since   6.1.0
     * @throws  Exception
     */
    protected function populateState()
    {
        $this->setState($this->getName() . '.id', Factory::getApplication()->getInput()->getInt('id'));
        $this->setState('params', ComponentHelper::getParams('com_volunteers'));
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $name     The table name. Optional.
     * @param   string  $prefix   The class prefix. Optional.
     * @param   array   $options  Configuration array for model. Optional.
     *
     * @return  Table  A Table object
     *
     * @since   4.0.0
     * @throws  Exception
     */
    public function getTable($name = 'Volunteer', $prefix = 'Administrator', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }

    /**
     * Method to get team data.
     *
     * @param   integer  $pk  The id of the team.
     *
     * @return  mixed  Data object on success, false on failure.
     * @since 4.0.0
     * @throws Exception
     */
    public function getItem($pk = null)
    {

        $pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');

        if ($pk > 0) {
            try {
                $db    = $this->getDatabase();
                $query = $db->getQuery(true)
                    ->select($this->getState('item.select', 'a.*, user.name AS name'))
                    ->from('#__volunteers_volunteers AS a')
                    ->where('a.id = ' . (int) $pk);

                // Join on user table.
                $query->select('user.email AS email')
                    ->join('LEFT', '#__users AS ' . $db->quoteName('user') . ' on user.id = a.user_id');

                // Filter by published state.
                $published = $this->getState('filter.published');
                $archived  = $this->getState('filter.archived');

                if (is_numeric($published)) {
                    $query->where('(a.published = ' . (int) $published . ' OR a.published =' . (int) $archived . ')')
                        ->where('(c.published = ' . (int) $published . ' OR c.published =' . (int) $archived . ')');
                }

                $db->setQuery($query);

                $data = $db->loadObject();

                if (empty($data)) {
                    throw new Exception(Text::_('COM_VOLUNTEERS_ERROR_VOLUNTEER_NOT_FOUND'), 404);
                }

                // Check for published state if filter set.
                if (((is_numeric($published)) || (is_numeric($archived))) && (($data->published != $published) && ($data->published != $archived))) {
                    throw new Exception(Text::_('COM_VOLUNTEERS_ERROR_VOLUNTEER_NOT_FOUND'), 404);
                }

                // Make sure we have http:// or https://
                if ($data->website) {
                    $data->website = parse_url((string) $data->website, PHP_URL_SCHEME) == '' ? 'http://' . $data->website : $data->website;
                }

                return $data;
            } catch (Exception $e) {
                // Keep the 404 of a missing Joomler, anything else is a server error
                throw new Exception($e->getMessage(), $e->getCode() === 404 ? 404 : 500, $e);
            }
        }

        $properties = $this->getTable()->getTableProperties(1);

        return ArrayHelper::toObject($properties);
    }

    /**
     * getVolunteerId
     *
     * @param $userId
     *
     * @return mixed
     *
     * @since 4.0.0
     */
    public function getVolunteerId($userId = null)
    {
        if (empty($userId)) {
            return -1;
        }

        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->select('id')
            ->from($db->quoteName('#__volunteers_volunteers'))
            ->where($db->quoteName('user_id') . ' = :userId')
            ->bind(':userId', $userId, ParameterType::INTEGER);

        $db->setQuery($query);
        $id = $db->loadResult();

        if (!is_null($id)) {
            return $id;
        }
        return -1;
    }

    /**
     * Method to get Department Members.
     *
     * @param   int|null  $pk  The id of the team.
     *
     * @return  stdClass  Data object on success, false on failure.
     * @since 4.0.0
     * @throws Exception
     */
    public function getVolunteerTeams(?int $pk = null)
    {
        $pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');

        // Get members
        $model = $this->getMVCFactory()->createModel('Members', 'Administrator', ['ignore_request' => true]);
        $model->setState('filter.volunteer', $pk);
        $items = $model->getItems();

        $teams               = new stdClass();
        $teams->active       = [];
        $teams->honorroll    = [];
        $teams->activemember = false;

        // Check for active or inactive members
        foreach ($items as $item) {
            if ($item->department && ($item->department_parent_id == 0)) {
                $item->link = Route::_('index.php?option=com_volunteers&view=board&id=' . $item->department);
                $item->name = $item->department_title;
            } elseif ($item->department) {
                $item->link = Route::_('index.php?option=com_volunteers&view=department&id=' . $item->department);
                $item->name = $item->department_title;
            } elseif ($item->team) {
                $item->link = Route::_('index.php?option=com_volunteers&view=team&id=' . $item->team);
                $item->name = $item->team_title;
            }

            if (empty($item->date_ended) || $item->date_ended === '0000-00-00') {
                $teams->active[] = $item;
            } else {
                $teams->honorroll[] = $item;
            }

            if ((empty($item->date_ended) || $item->date_ended === '0000-00-00') && $item->position != 8) {
                $teams->activemember = true;
            }
        }

        return $teams;
    }
}
