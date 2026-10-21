<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Routing class for com_volunteers
 *
 * @since 4.0.0
 */
class VolunteersRouter extends RouterView
{
    /**
     * The database driver object
     *
     * @var    DatabaseInterface
     * @since  4.0.0
     */
    private $db;

    /**
     * @param   SiteApplication           $app
     * @param   AbstractMenu              $menu
     *
     * @since 4.0.0
     * @throws Exception
     */
    public function __construct($app, $menu)
    {
        $departments = new RouterViewConfiguration('departments');
        $this->registerView($departments);
        $ccDepartment = new RouterViewConfiguration('department');
        $ccDepartment->setKey('id')->setParent($departments);
        $this->registerView($ccDepartment);
        $reports = new RouterViewConfiguration('reports');
        $this->registerView($reports);
        $ccReport = new RouterViewConfiguration('report');
        $ccReport->setKey('id')->setParent($reports);
        $this->registerView($ccReport);
        $roles = new RouterViewConfiguration('roles');
        $this->registerView($roles);
        $ccRole = new RouterViewConfiguration('role');
        $ccRole->setKey('id')->setParent($roles);
        $this->registerView($ccRole);
        $teams = new RouterViewConfiguration('teams');
        $this->registerView($teams);
        $ccTeam = new RouterViewConfiguration('team');
        $ccTeam->setKey('id')->setParent($teams);
        $this->registerView($ccTeam);
        // Members have no menu item of their own, so hang them off teams. Registered after team so team aliases are matched first.
        $ccMember = new RouterViewConfiguration('member');
        $ccMember->setKey('id')->setParent($teams);
        $this->registerView($ccMember);
        $volunteers = new RouterViewConfiguration('volunteers');
        $this->registerView($volunteers);
        $ccVolunteer = new RouterViewConfiguration('volunteer');
        $ccVolunteer->setKey('id')->setParent($volunteers);
        $this->registerView($ccVolunteer);
        $board = new RouterViewConfiguration('board');
        $board->setKey('id');
        $this->registerView($board);
        $home = new RouterViewConfiguration('home');
        $this->registerView($home);
        $my = new RouterViewConfiguration('my');
        $this->registerView($my);

        $this->db = \Joomla\CMS\Factory::getDbo();

        parent::__construct($app, $menu);

        $this->attachRule(new MenuRules($this));
        $this->attachRule(new StandardRules($this));
        $this->attachRule(new NomenuRules($this));
    }

    /**
     * Preprocess method for URLs. The Itemid must be chosen here, the site router has already
     * resolved the menu item by the time build() is called.
     *
     * @param   array  $query  An associative array of URL arguments
     *
     * @return  array  The URL arguments to use to assemble the subsequent URL.
     *
     * @since   4.0.0
     */
    public function preprocess($query)
    {
        $query = parent::preprocess($query);

        // Teams of the board live under the board's teams menu item
        if (isset($query['view'], $query['id']) && $query['view'] === 'team') {
            $dbQuery = $this->db->getQuery(true)
                ->select($this->db->quoteName('department'))
                ->from($this->db->quoteName('#__volunteers_teams'))
                ->where($this->db->quoteName('id') . ' = :id')
                ->bind(':id', $query['id'], ParameterType::INTEGER);
            $this->db->setQuery($dbQuery);
            $department = (int) $this->db->loadResult();

            if ($department === 58) {
                foreach ($this->menu->getItems('component', 'com_volunteers') as $item) {
                    if (isset($item->query['view'], $item->query['id']) && $item->query['view'] === 'teams' && (int) $item->query['id'] === $department) {
                        $query['Itemid'] = $item->id;
                        break;
                    }
                }
            }
        }

        return $query;
    }

    /**
     * Build method for URLs
     *
     * @param   array  &$query  Array of query elements
     *
     * @return  array  Array of URL segments
     *
     * @since   4.0.0
     */
    public function build(&$query)
    {
        $views = $this->getViews();

        // A new item form (e.g. view=report&layout=edit without an id) has no segment of its own. Keep the view in the
        // query string, otherwise the standard rules drop it and the URL resolves to the parent list view instead.
        if (isset($query['view'], $views[$query['view']]) && $views[$query['view']]->key && empty($query[$views[$query['view']]->key])) {
            return [];
        }

        return parent::build($query);
    }

    /**
     * Method to get the segment(s) for an department
     *
     * @param   string  $id     ID of the department to retrieve the segments for
     * @param   array   $query  The request that is built right now
     *
     * @return  array  The segments of this item
     *
     * @since 4.0.0
     */
    public function getDepartmentSegment(string $id, array $query)
    {
        if (!strpos($id, ':')) {
            $id      = (int) $id;
            $dbquery = $this->db->getQuery(true);
            $dbquery->select($this->db->quoteName('alias'))
                ->from($this->db->quoteName('#__volunteers_departments'))
                ->where($this->db->quoteName('id') . ' = :id')
                ->bind(':id', $id, ParameterType::INTEGER);
            $this->db->setQuery($dbquery);

            $id .= ':' . $this->db->loadResult();
        }

        list($void, $segment) = explode(':', $id, 2);

        return [$void => $segment];
    }

    /**
     * Method to get the segment(s) for the board
     *
     * @param   string  $id     ID of the board department to retrieve the segments for
     * @param   array   $query  The request that is built right now
     *
     * @return  array  The segments of this item
     *
     * @since 4.0.0
     */
    public function getBoardSegment(string $id, array $query)
    {
        return [(int) $id => $id];
    }

    /**
     * Method to get the segment(s) for an member
     *
     * @param   string  $id     ID of the member to retrieve the segments for
     * @param   array   $query  The request that is built right now
     *
     * @return  array  The segments of this item
     *
     * @since 4.0.0
     */
    public function getMemberSegment(string $id, array $query)
    {
        return [(int) $id => $id];
    }

    /**
     * Method to get the segment(s) for an report
     *
     * @param   string  $id     ID of the report to retrieve the segments for
     * @param   array   $query  The request that is built right now
     *
     * @return  array  The segments of this item
     *
     * @since 4.0.0
     */
    public function getReportSegment(string $id, array $query)
    {
        if (!strpos($id, ':')) {
            $id      = (int) $id;
            $dbquery = $this->db->getQuery(true);
            $dbquery->select($this->db->quoteName('alias'))
                ->from($this->db->quoteName('#__volunteers_reports'))
                ->where($this->db->quoteName('id') . ' = :id')
                ->bind(':id', $id, ParameterType::INTEGER);
            $this->db->setQuery($dbquery);

            $id .= ':' . $this->db->loadResult();
        }

        return [(int) $id => $id];
    }

    /**
     * Method to get the segment(s) for an role
     *
     * @param   string  $id     ID of the role to retrieve the segments for
     * @param   array   $query  The request that is built right now
     *
     * @return  array  The segments of this item
     *
     * @since 4.0.0
     */
    public function getRoleSegment(string $id, array $query)
    {
        return [(int) $id => $id];
    }

    /**
     * Method to get the segment(s) for an team
     *
     * @param   string  $id     ID of the team to retrieve the segments for
     * @param   array   $query  The request that is built right now
     *
     * @return  array  The segments of this item
     *
     * @since 4.0.0
     */
    public function getTeamSegment(string $id, array $query)
    {
        if (!strpos($id, ':')) {
            $id      = (int) $id;
            $dbquery = $this->db->getQuery(true);
            $dbquery->select($this->db->quoteName('alias'))
                ->from($this->db->quoteName('#__volunteers_teams'))
                ->where($this->db->quoteName('id') . ' = :id')
                ->bind(':id', $id, ParameterType::INTEGER);
            $this->db->setQuery($dbquery);

            $id .= ':' . $this->db->loadResult();
        }

        list($void, $segment) = explode(':', $id, 2);

        return [$void => $segment];
    }

    /**
     * Method to get the segment(s) for an volunteer
     *
     * @param   string  $id     ID of the volunteer to retrieve the segments for
     * @param   array   $query  The request that is built right now
     *
     * @return  array  The segments of this item
     *
     * @since 4.0.0
     */
    public function getVolunteerSegment(string $id, array $query)
    {
        if (!strpos($id, ':')) {
            $id      = (int) $id;
            $dbquery = $this->db->getQuery(true);
            $dbquery->select($this->db->quoteName('alias'))
                ->from($this->db->quoteName('#__volunteers_volunteers'))
                ->where($this->db->quoteName('id') . ' = :id')
                ->bind(':id', $id, ParameterType::INTEGER);
            $this->db->setQuery($dbquery);

            $id .= ':' . $this->db->loadResult();
        }

        return [(int) $id => $id];
    }

    /**
     * Method to get the segment(s) for an department
     *
     * @param   string  $segment  Segment of the department to retrieve the ID for
     * @param   array   $query    The request that is parsed right now
     *
     * @return  int   The id of this item or false
     *
     * @since 4.0.0
     */
    public function getDepartmentId(string $segment, array $query)
    {
        $dbquery = $this->db->getQuery(true);
        $dbquery->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__volunteers_departments'))
            ->where(
                [
                    $this->db->quoteName('alias') . ' = :alias',
                ]
            )
            ->bind(':alias', $segment);
        $this->db->setQuery($dbquery);

        return (int) $this->db->loadResult();
    }

    /**
     * Method to get the segment(s) for the board
     *
     * @param   string  $segment  Segment of the board department to retrieve the ID for
     * @param   array   $query    The request that is parsed right now
     *
     * @return  int   The id of this item or false
     *
     * @since 4.0.0
     */
    public function getBoardId(string $segment, array $query)
    {
        return (int) $segment;
    }

    /**
     * Method to get the segment(s) for an member
     *
     * @param   string  $segment  Segment of the member to retrieve the ID for
     * @param   array   $query    The request that is parsed right now
     *
     * @return  int   The id of this item or false
     *
     * @since 4.0.0
     */
    public function getMemberId(string $segment, array $query)
    {
        return ctype_digit($segment) ? (int) $segment : false;
    }

    /**
     * Method to get the segment(s) for an report
     *
     * @param   string  $segment  Segment of the report to retrieve the ID for
     * @param   array   $query    The request that is parsed right now
     *
     * @return  int   The id of this item or false
     * @since 4.0.0
     */
    public function getReportId(string $segment, array $query)
    {
        return (int) $segment;
    }

    /**
     * Method to get the segment(s) for an role
     *
     * @param   string  $segment  Segment of the role to retrieve the ID for
     * @param   array   $query    The request that is parsed right now
     *
     * @return  int   The id of this item or false
     *
     * @since 4.0.0
     */
    public function getRoleId(string $segment, array $query)
    {
        return (int) $segment;
    }

    /**
     * Method to get the segment(s) for an team
     *
     * @param   string  $segment  Segment of the team to retrieve the ID for
     * @param   array   $query    The request that is parsed right now
     *
     * @return  int   The id of this item or false
     *
     * @since 4.0.0
     */
    public function getTeamId(string $segment, array $query)
    {
        $dbquery = $this->db->getQuery(true);
        $dbquery->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__volunteers_teams'))
            ->where(
                [
                    $this->db->quoteName('alias') . ' = :alias',
                ]
            )
            ->bind(':alias', $segment);
        $this->db->setQuery($dbquery);

        return (int) $this->db->loadResult();
    }

    /**
     * Method to get the segment(s) for an volunteer
     *
     * @param   string  $segment  Segment of the volunteer to retrieve the ID for
     * @param   array   $query    The request that is parsed right now
     *
     * @return  int   The id of this item or false
     *
     * @since 4.0.0
     */
    public function getVolunteerId(string $segment, array $query)
    {
        return (int) $segment;
    }
}