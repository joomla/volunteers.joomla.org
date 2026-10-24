<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Finder\VolunteersDepartments\Extension;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\Component\Volunteers\Administrator\Finder\VolunteersAdapter;
use Joomla\Database\QueryInterface;

/**
 * Smart Search adapter for the departments of the Joomla Volunteer Portal.
 *
 * @since  6.1.0
 */
final class VolunteersDepartments extends VolunteersAdapter
{
    /**
     * The plugin identifier.
     *
     * @var    string
     * @since  6.1.0
     */
    protected $context = 'VolunteersDepartments';

    /**
     * The sublayout to use when rendering the results.
     *
     * @var    string
     * @since  6.1.0
     */
    protected $layout = 'department';

    /**
     * The type of content that the adapter indexes.
     *
     * @var    string
     * @since  6.1.0
     */
    protected $type_title = 'Department';

    /**
     * The table name.
     *
     * @var    string
     * @since  6.1.0
     */
    protected $table = '#__volunteers_departments';

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
            ->select($db->quote('*') . ' AS language')
            ->from($db->quoteName('#__volunteers_departments', 'a'));

        return $query;
    }
}
