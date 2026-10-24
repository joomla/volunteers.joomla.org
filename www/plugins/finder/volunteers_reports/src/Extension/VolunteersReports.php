<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Finder\VolunteersReports\Extension;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\Component\Finder\Administrator\Indexer\Indexer;
use Joomla\Component\Finder\Administrator\Indexer\Result;
use Joomla\Component\Volunteers\Administrator\Finder\VolunteersAdapter;
use Joomla\Database\QueryInterface;

/**
 * Smart Search adapter for the reports of the Joomla Volunteer Portal.
 *
 * @since  6.1.0
 */
final class VolunteersReports extends VolunteersAdapter
{
    /**
     * The plugin identifier.
     *
     * @var    string
     * @since  6.1.0
     */
    protected $context = 'VolunteersReports';

    /**
     * The sublayout to use when rendering the results.
     *
     * @var    string
     * @since  6.1.0
     */
    protected $layout = 'report';

    /**
     * The type of content that the adapter indexes.
     *
     * @var    string
     * @since  6.1.0
     */
    protected $type_title = 'Report';

    /**
     * The table name.
     *
     * @var    string
     * @since  6.1.0
     */
    protected $table = '#__volunteers_reports';

    /**
     * Method to index an item.
     *
     * @param   Result  $item  The item to index as a Result object.
     *
     * @return  void
     *
     * @since   6.1.0
     */
    protected function index(Result $item)
    {
        $item->addInstruction(Indexer::META_CONTEXT, 'department');
        $item->addInstruction(Indexer::META_CONTEXT, 'team');

        parent::index($item);
    }

    /**
     * Method to get the SQL query used to retrieve the list of content items.
     *
     * @param   mixed  $query  A QueryInterface object or null.
     *
     * @return  QueryInterface  A database object.
     *
     * @since   6.1.0
     */
    protected function getListQuery($query = null)
    {
        $db = $this->getDatabase();

        $query = $query instanceof QueryInterface ? $query : $db->createQuery()
            ->select('a.id, a.title, a.alias, a.description AS summary, a.state, a.modified')
            ->select('a.created AS start_date, a.created AS publish_start_date')
            ->select($db->quoteName(['department.title', 'team.title'], ['department', 'team']))
            ->select($db->quote('*') . ' AS language')
            ->from($db->quoteName('#__volunteers_reports', 'a'))
            ->join('LEFT', $db->quoteName('#__volunteers_departments', 'department') . ' ON ' . $db->quoteName('department.id') . ' = ' . $db->quoteName('a.department'))
            ->join('LEFT', $db->quoteName('#__volunteers_teams', 'team') . ' ON ' . $db->quoteName('team.id') . ' = ' . $db->quoteName('a.team'));

        return $query;
    }
}
