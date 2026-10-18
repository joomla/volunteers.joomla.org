<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Site\Model;

use Joomla\Database\Exception\ExecutionFailureException;
use Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Methods supporting a list of teams records.
 * @since 4.0.0
 */
class VolunteersModel extends ListModel
{
    /**
     * Constructor.
     *
     * @param   array $config An optional associative array of configuration settings.
     * @param   MVCFactoryInterface|null  $factory  MVCFactoryInterface
     *
     * @see     JController
     * @since   4.0.0
     * @throws Exception
     */
    public function __construct($config = [], MVCFactoryInterface $factory = null)
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                    'id', 'a.id',
                    'alias', 'a.alias',
                    'checked_out', 'a.checked_out',
                    'checked_out_time', 'a.checked_out_time',
                    'state', 'a.state',
                    'created', 'a.created',
                    'created_by', 'a.created_by',
                    'ordering', 'a.ordering',
                    'featured', 'a.featured',
                    'username', 'user.username',
                    'modified', 'a.modified',
                    'num_teams', 'num_teams',
                    'spam', 'a.spam',
                    'birthday', 'a.birthday'
            ];
        }

        parent::__construct($config, $factory);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @return  QueryInterface
     * @since 4.0.0
     * @throws Exception
     */
    protected function getListQuery()
    {
        $frontend = Factory::getApplication()->isClient('site');

        // Create a new query object.
        $db    = $this->getDatabase();
        $query = $db->createQuery();

        // Select the required fields from the table.
        $query
            ->select($this->getState('list.select', ['a.*']))
            ->from($db->quoteName('#__volunteers_volunteers') . ' AS a');

        // Join over the users for the checked_out user.
        $query
            ->select('checked_out.name AS editor')
            ->join('LEFT', '#__users AS ' . $db->quoteName('checked_out') . ' ON checked_out.id = a.checked_out');

        // Join over the users for the related user.
        $query
            ->select('user.name AS name, user.username AS user_username, user.email AS user_email')
            ->join('LEFT', '#__users AS ' . $db->quoteName('user') . ' ON user.id = a.user_id');

        // Self-join to count teams involved.
        $query->select('COUNT(DISTINCT member.id) AS num_teams')
            ->join('LEFT', $db->quoteName('#__volunteers_members', 'member') . ' ON ' . $db->qn('member.volunteer') . ' = ' . $db->qn('a.id'));

        // Filter by published state
        $state = $this->getState('filter.state', 1);

        if (is_numeric($state)) {
            $query->where('a.state = ' . (int) $state);
        }

        // Filter by search in title
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            if (stripos((string) $search, 'id:') === 0) {
                $query->where('a.id = ' . (int) substr((string) $search, 3));
            } else {
                $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim((string) $search), true) . '%'));
                if ($frontend) {
                    $query->where('(user.name LIKE ' . $search . ' OR a.alias LIKE ' . $search . ')');
                } else {
                    $query->where('(user.name LIKE ' . $search . ' OR a.alias LIKE ' . $search . ' OR a.intro LIKE ' . $search . ' OR a.joomlastory LIKE ' . $search . ')');
                }
            }
        }

        // Filter private profiles on frontend
        $filterGuests = $this->getState('filter.private', ($frontend) ? 1 : null);

        if ($filterGuests) {
            $query->where($db->quoteName('user.email') . ' NOT LIKE ' . $db->quote('%identity.joomla.org%'));
        }

        // Filter by active state
        $active = $this->getState('filter.active', ($frontend) ? 1 : null);

        if (is_numeric($active)) {
            if ($active == 1) {
                $query->where($db->quoteName('member.date_ended') . ' = ' . $db->quote('0000-00-00'));
            }
        }

        // Filter by image
        $image = $this->getState('filter.image');

        if ($image) {
            $query->where('a.image <> \'\'');
        }

        // Filter by joomlastory
        $joomlastory = $this->getState('filter.joomlastory');

        if ($joomlastory) {
            $query->where('a.joomlastory <> \'\'');
        }

        // Filter by location
        $location = $this->getState('filter.location');

        if ($location) {
            $query->where('a.latitude <> \'\'');
            $query->where('a.longitude <> \'\'');
        }

        // Filter by coc
        $coc = $this->getState('filter.coc');

        if (is_numeric($coc) && $coc == 1) {
            $query->where('a.coc = 1');
        }

        // Group by ID
        $query->group('a.id');

        // Add the list ordering clause.
        $orderCol  = $this->state->get('list.ordering', 'user.name');
        $orderDirn = $this->state->get('list.direction', 'asc');

        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * This is necessary because the model is used by the component and
     * different modules that might need different sets of data or different
     * ordering requirements.
     *
     * @param   string  $id  A prefix for the store id.
     *
     * @return  string  A store id.
     * @since 4.0.0
     */
    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.state');
        $id .= ':' . $this->getState('filter.image');
        $id .= ':' . $this->getState('filter.joomlastory');
        $id .= ':' . $this->getState('filter.location');
        $id .= ':' . $this->getState('filter.coc');
        $id .= ':' . $this->getState('filter.active');

        return parent::getStoreId($id);
    }

    /**
     * Method to auto-populate the model state.
     *
     * @param   string  $ordering
     * @param   string  $direction
     *
     * @return  void
     *
     * @note    Calling getState in this method will result in recursion.
     * @since   4.0.0
     */
    protected function populateState($ordering = 'user.name', $direction = 'asc')
    {
        // Load the filter state.
        $this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search'));
        $this->setState('filter.state', $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state'));
        $this->setState('filter.image', $this->getUserStateFromRequest($this->context . '.filter.image', 'filter_image'));
        $this->setState('filter.joomlastory', $this->getUserStateFromRequest($this->context . '.filter.joomlastory', 'filter_joomlastory'));
        $this->setState('filter.location', $this->getUserStateFromRequest($this->context . '.filter.location', 'filter_location'));
        $this->setState('filter.coc', $this->getUserStateFromRequest($this->context . '.filter.coc', 'filter_coc'));
        $this->setState('filter.active', $this->getUserStateFromRequest($this->context . '.filter.active', 'filter_active'));

        // Load the parameters.
        $params = ComponentHelper::getParams('com_volunteers');
        $this->setState('params', $params);

        // List state information.
        parent::populateState($ordering, $direction);
    }

    /**
     * Method to reset Spam Counter
     *
     * @return bool
     *
     * @since version
     * @since 4.0.0
     * @throws Exception
     */
    public function resetSpam()
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__volunteers_volunteers'))
            ->set($db->quoteName('spam') . ' = 0')
            ->where($db->quoteName('spam') . ' <> 0');
        $db->setQuery($query);

        try {
            $db->execute();
        } catch (ExecutionFailureException $e) {
            throw new Exception(500, $e->getMessage());
        }

        return true;
    }
}
