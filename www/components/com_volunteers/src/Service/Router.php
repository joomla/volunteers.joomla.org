<?php

/**
 * @package    Com_Volunteers
 * @author     The Joomla Project <secretary@opensourcematters.org>
 * @copyright  2023 The Joomla Project
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Site\Service;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\Router\RouterBase;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\CMS\Router\Exception\RouteNotFoundException;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Routing class for com_volunteers.
 *
 * The URL scheme is inherited from the Joomla 3 version of the component. Every route is relative
 * to one of four "base" menu items - `departments`, `teams`, `board` or `volunteers` - and the
 * segments below it identify the item being shown:
 *
 *     <departments>/<department-alias>[/edit]
 *     <departments>/<department-alias>/team/new
 *     <departments>/<department-alias>/member/new
 *     <departments>/<department-alias>/member[/edit]/<member-id>
 *     <departments>/<department-alias>/reports/new
 *     <departments>/<department-alias>/reports[/edit]/<report-id>-<report-alias>
 *     <teams>/<team-alias>[/edit]
 *     <teams>/<team-alias>/subteam/new
 *     <teams>/<team-alias>/member/...          (as for departments)
 *     <teams>/<team-alias>/role/new
 *     <teams>/<team-alias>/role[/edit]/<role-id>
 *     <teams>/<team-alias>/reports/...         (as for departments)
 *     <board>/reports[/edit]/<report-id>-<report-alias>
 *     <volunteers>/<volunteer-id>-<volunteer-alias>[/edit]
 *
 * This shape cannot be expressed with RouterView: `member`, `role` and `report` each hang off both
 * `department` and `team` (a RouterViewConfiguration has a single parent), StandardRules emits only
 * one segment per view in the path, and there is no way to interleave the `edit` marker. The
 * component therefore routes on top of RouterBase.
 *
 * Note that, unlike Joomla 3, the Itemid is resolved in preprocess(). SiteRouter::buildSefRoute()
 * looks up the menu item before it calls build(), so assigning Itemid during build() would no
 * longer influence the route prefix.
 *
 * @since  6.1.0
 */
class Router extends RouterBase
{
    /**
     * Columns to read per component table, keyed by table suffix.
     *
     * @var    array<string, string[]>
     * @since  6.1.0
     */
    private const ROW_COLUMNS = [
        'departments' => ['id', 'alias', 'parent_id'],
        'teams'       => ['id', 'alias', 'department'],
        'volunteers'  => ['id', 'alias'],
        'members'     => ['id', 'department', 'team'],
        'reports'     => ['id', 'alias', 'department', 'team'],
        'roles'       => ['id', 'team'],
    ];

    /**
     * Tables that can be looked up by alias.
     *
     * @var    string[]
     * @since  6.1.0
     */
    private const ALIAS_TABLES = ['departments', 'teams', 'volunteers'];

    /**
     * The database driver.
     *
     * @var    DatabaseInterface
     * @since  6.1.0
     */
    private DatabaseInterface $db;

    /**
     * Rows already read in this request, keyed by "<table>.<id>".
     *
     * @var    array<string, ?object>
     * @since  6.1.0
     */
    private array $rows = [];

    /**
     * Ids already resolved from an alias in this request, keyed by "<table>.<alias>".
     *
     * @var    array<string, int>
     * @since  6.1.0
     */
    private array $ids = [];

    /**
     * Cached list of com_volunteers menu items.
     *
     * @var    ?object[]
     * @since  6.1.0
     */
    private ?array $menuItems = null;

    /**
     * Constructor.
     *
     * @param   CMSApplicationInterface    $app              The application object
     * @param   AbstractMenu               $menu             The menu object to work with
     * @param   ?CategoryFactoryInterface  $categoryFactory  The category factory (unused)
     * @param   ?DatabaseInterface         $db               The database driver
     *
     * @since   6.1.0
     */
    public function __construct(
        $app,
        $menu,
        ?CategoryFactoryInterface $categoryFactory = null,
        ?DatabaseInterface $db = null
    ) {
        parent::__construct($app, $menu);

        $this->db = $db ?: Factory::getContainer()->get(DatabaseInterface::class);
    }

    /**
     * Complete the URL parameters before the route is built.
     *
     * This attaches the Itemid of the menu item the route has to be built relative to. An Itemid
     * that is already present is kept when it points at a menu item of the expected type, so that
     * explicit links (menu modules, for instance) are not rewritten to a different menu item.
     *
     * @param   array  $query  An associative array of URL arguments
     *
     * @return  array  The URL arguments to use to assemble the subsequent URL
     *
     * @since   6.1.0
     */
    public function preprocess($query)
    {
        if (!isset($query['view'])) {
            return $query;
        }

        $target = $this->resolveTarget($query);

        if ($target['base'] === null) {
            return $query;
        }

        // Keep an explicit Itemid that already addresses the right kind of page.
        if ($this->getMenuItemView($query['Itemid'] ?? null) === $target['base']) {
            return $query;
        }

        if ($target['itemid']) {
            $query['Itemid'] = $target['itemid'];
        }

        return $query;
    }

    /**
     * Build the route for the com_volunteers component.
     *
     * When a route cannot be expressed as segments - because the Itemid does not belong to the
     * expected base view, or because a record is missing - the query is left untouched so that the
     * CMS falls back to a non-SEF query string instead of emitting a URL that cannot be parsed.
     *
     * @param   array  &$query  An array of URL arguments
     *
     * @return  array  The URL arguments to use to assemble the subsequent URL
     *
     * @since   6.1.0
     */
    public function build(&$query)
    {
        if (!isset($query['view'])) {
            return [];
        }

        $view     = $query['view'];
        $layout   = $query['layout'] ?? null;
        $id       = (int) ($query['id'] ?? 0);
        $target   = $this->resolveTarget($query);
        $menuView = $this->getMenuItemView($query['Itemid'] ?? null);

        // Everything below is relative to the base menu item. Bail out if we did not get it.
        if ($target['base'] === null || $menuView !== $target['base']) {
            return [];
        }

        $segments = [];

        switch ($view) {
            case 'department':
            case 'team':
                if ($id) {
                    if ($target['alias'] === null) {
                        return [];
                    }

                    $segments[] = $target['alias'];

                    if ($layout === 'edit') {
                        $segments[] = 'edit';
                    }

                    break;
                }

                // A new team is nested below the team or department it is being created in.
                if ($view !== 'team' || $target['alias'] === null || $target['marker'] === null) {
                    return [];
                }

                $segments[] = $target['alias'];
                $segments[] = $target['marker'];
                $segments[] = 'new';

                break;

            case 'volunteer':
                if (!$id || $target['alias'] === null) {
                    return [];
                }

                $segments[] = $id . '-' . $target['alias'];

                if ($layout === 'edit') {
                    $segments[] = 'edit';
                }

                break;

            case 'member':
            case 'role':
                if ($target['alias'] === null) {
                    return [];
                }

                $segments[] = $target['alias'];
                $segments[] = $view;

                if (!$id) {
                    $segments[] = 'new';

                    break;
                }

                if ($layout === 'edit') {
                    $segments[] = 'edit';
                }

                $segments[] = (string) $id;

                break;

            case 'report':
                // Reports of a root department are addressed below the board menu item, which has
                // no alias segment of its own.
                if ($target['alias'] !== null) {
                    $segments[] = $target['alias'];
                }

                $segments[] = 'reports';

                if (!$id) {
                    $segments[] = 'new';

                    break;
                }

                $report = $this->getRow('reports', $id);

                if ($report === null || $report->alias === '') {
                    return [];
                }

                if ($layout === 'edit') {
                    $segments[] = 'edit';
                }

                $segments[] = $id . '-' . $report->alias;

                break;

            default:
                // A view that is rendered by the menu item itself. The board always shows the root
                // department, so its id never needs to travel in the URL.
                if ($view === 'board') {
                    unset($query['id']);
                }

                $item = $this->menu->getItem($query['Itemid']);

                if (isset($query['id']) && (int) ($item->query['id'] ?? 0) === $id) {
                    unset($query['id']);
                }

                if (isset($query['layout']) && ($item->query['layout'] ?? null) === $layout) {
                    unset($query['layout']);
                }

                unset($query['view']);

                return [];
        }

        unset($query['view'], $query['id'], $query['layout']);

        return $segments;
    }

    /**
     * Parse the segments of a URL.
     *
     * The active menu item decides how the segments are read. Segments are only consumed when they
     * produced request variables - anything left over makes the CMS raise a 404.
     *
     * @param   array  &$segments  The segments of the URL to parse
     *
     * @return  array  The URL attributes to be used by the application
     *
     * @since   6.1.0
     * @throws  RouteNotFoundException
     */
    public function parse(&$segments)
    {
        if (!$segments) {
            return [];
        }

        $active = $this->menu->getActive();

        if ($active === null || $active->component !== 'com_volunteers' || !isset($active->query['view'])) {
            return [];
        }

        switch ($active->query['view']) {
            case 'departments':
            case 'teams':
                $vars = $this->parseContainer($active->query['view'], $segments);
                break;

            case 'board':
                if (($segments[0] ?? null) !== 'reports') {
                    throw new RouteNotFoundException(Text::_('JERROR_PAGE_NOT_FOUND'));
                }

                $vars = $this->parseReport(\array_slice($segments, 1));
                break;

            case 'volunteers':
                $vars = $this->parseVolunteer($segments);
                break;

            case 'home':
                throw new RouteNotFoundException(Text::_('JERROR_PAGE_NOT_FOUND'));

            default:
                return [];
        }

        if ($vars) {
            $segments = [];
        }

        return $vars;
    }

    /**
     * Parse the segments below a `departments` or `teams` menu item.
     *
     * @param   string    $view      The view of the active menu item
     * @param   string[]  $segments  The segments of the URL to parse
     *
     * @return  array  The URL attributes to be used by the application
     *
     * @since   6.1.0
     * @throws  RouteNotFoundException
     */
    private function parseContainer(string $view, array $segments): array
    {
        $marker = $segments[1] ?? null;

        switch ($marker) {
            case 'member':
            case 'role':
                return $this->parseChild($marker, \array_slice($segments, 2));

            case 'reports':
                return $this->parseReport(\array_slice($segments, 2));

            case 'team':
            case 'subteam':
                // The only route below these markers is the form for a new (sub)team.
                if (($segments[2] ?? null) !== 'new') {
                    throw new RouteNotFoundException(Text::_('JERROR_PAGE_NOT_FOUND'));
                }

                return ['view' => 'team', 'id' => 0, 'layout' => 'edit'];
        }

        // The department or team itself.
        $id = $this->getIdByAlias($view, $segments[0]);

        if (!$id) {
            throw new RouteNotFoundException(Text::_('JERROR_PAGE_NOT_FOUND'));
        }

        // departments -> department, teams -> team
        $vars = ['view' => substr($view, 0, -1), 'id' => $id];

        if ($marker === 'edit') {
            $vars['layout'] = 'edit';
        } elseif ($marker !== null) {
            throw new RouteNotFoundException(Text::_('JERROR_PAGE_NOT_FOUND'));
        }

        return $vars;
    }

    /**
     * Parse the segments below a `member` or `role` marker.
     *
     * @param   string    $view  Either `member` or `role`
     * @param   string[]  $tail  The segments following the marker
     *
     * @return  array  The URL attributes to be used by the application
     *
     * @since   6.1.0
     * @throws  RouteNotFoundException
     */
    private function parseChild(string $view, array $tail): array
    {
        $first = $tail[0] ?? null;

        if ($first === 'new') {
            return ['view' => $view, 'id' => 0, 'layout' => 'edit'];
        }

        $vars = ['view' => $view];

        if ($first === 'edit') {
            $vars['layout'] = 'edit';
            $vars['id']     = (int) ($tail[1] ?? 0);
        } else {
            $vars['id'] = (int) $first;
        }

        if (!$vars['id']) {
            throw new RouteNotFoundException(Text::_('JERROR_PAGE_NOT_FOUND'));
        }

        return $vars;
    }

    /**
     * Parse the segments below a `reports` marker.
     *
     * @param   string[]  $tail  The segments following the marker
     *
     * @return  array  The URL attributes to be used by the application
     *
     * @since   6.1.0
     * @throws  RouteNotFoundException
     */
    private function parseReport(array $tail): array
    {
        $first = $tail[0] ?? null;

        if ($first === 'new') {
            return ['view' => 'report', 'id' => 0, 'layout' => 'edit'];
        }

        $vars = ['view' => 'report'];

        if ($first === 'edit') {
            $vars['layout'] = 'edit';
            $vars['id']     = $this->parseLeadingId($tail[1] ?? '');
        } else {
            $vars['id'] = $this->parseLeadingId((string) $first);
        }

        if (!$vars['id']) {
            throw new RouteNotFoundException(Text::_('JERROR_PAGE_NOT_FOUND'));
        }

        return $vars;
    }

    /**
     * Parse the segments below a `volunteers` menu item.
     *
     * @param   string[]  $segments  The segments of the URL to parse
     *
     * @return  array  The URL attributes to be used by the application
     *
     * @since   6.1.0
     * @throws  RouteNotFoundException
     */
    private function parseVolunteer(array $segments): array
    {
        // Volunteers are addressed as `<id>-<alias>`, but a bare alias is still honoured.
        $id = $this->parseLeadingId($segments[0]) ?: $this->getIdByAlias('volunteers', $segments[0]);

        if (!$id) {
            throw new RouteNotFoundException(Text::_('JERROR_PAGE_NOT_FOUND'));
        }

        $vars = ['view' => 'volunteer', 'id' => $id];

        if (($segments[1] ?? null) === 'edit') {
            $vars['layout'] = 'edit';
        } elseif (isset($segments[1])) {
            throw new RouteNotFoundException(Text::_('JERROR_PAGE_NOT_FOUND'));
        }

        return $vars;
    }

    /**
     * Work out which menu item a query has to be routed through, and which record supplies the
     * leading alias segment.
     *
     * @param   array  $query  The request that is being built
     *
     * @return  array{base: ?string, itemid: ?int, alias: ?string, marker: ?string}
     *
     * @since   6.1.0
     */
    private function resolveTarget(array $query): array
    {
        $view = $query['view'];
        $id   = (int) ($query['id'] ?? 0);

        switch ($view) {
            case 'department':
                $department = $this->getRow('departments', $id);

                return $this->target('departments', $this->getItemid('departments'), $department->alias ?? null);

            case 'departments':
                return $this->target('departments', $this->getItemid('departments'));

            case 'board':
                return $this->target('board', $this->getItemid('board'));

            case 'team':
                if ($id) {
                    $team = $this->getRow('teams', $id);

                    return $this->target(
                        'teams',
                        $this->getItemid('teams', $team->department ?? null),
                        $team->alias ?? null
                    );
                }

                // A new team is created below an existing team or department, which the controller
                // recorded in the user state.
                $parentTeamId = $this->getUserStateId('com_volunteers.edit.team.teamid');

                if ($parentTeamId && $team = $this->getRow('teams', $parentTeamId)) {
                    return $this->target(
                        'teams',
                        $this->getItemid('teams', $team->department),
                        $team->alias,
                        'subteam'
                    );
                }

                $parentDepartmentId = $this->getUserStateId('com_volunteers.edit.team.departmentid');

                if ($parentDepartmentId && $department = $this->getRow('departments', $parentDepartmentId)) {
                    return $this->target('teams', $this->getItemid('teams'), $department->alias, 'team');
                }

                return $this->target('teams', $this->getItemid('teams'));

            case 'teams':
                return $this->target('teams', $this->getItemid('teams', $id ?: null));

            case 'volunteer':
                $volunteer = $this->getRow('volunteers', $id);

                return $this->target('volunteers', $this->getItemid('volunteers'), $volunteer->alias ?? null);

            case 'volunteers':
                return $this->target('volunteers', $this->getItemid('volunteers'));

            case 'member':
            case 'role':
            case 'report':
                return $this->resolveOwner($view, $id);

            default:
                return $this->target($view, $this->getItemid($view));
        }
    }

    /**
     * Work out the department or team a member, role or report belongs to.
     *
     * For an existing record the owner is read from the database. For a record that is still being
     * created the owner is taken from the user state the edit controllers maintain.
     *
     * @param   string  $view  One of `member`, `role` or `report`
     * @param   int     $id    The id of the record, or 0 for a new record
     *
     * @return  array{base: ?string, itemid: ?int, alias: ?string, marker: ?string}
     *
     * @since   6.1.0
     */
    private function resolveOwner(string $view, int $id): array
    {
        $departmentId = 0;
        $teamId       = 0;

        if ($id) {
            $row = $this->getRow($view === 'member' ? 'members' : $view . 's', $id);

            if ($row === null) {
                return $this->target(null, null);
            }

            $departmentId = (int) ($row->department ?? 0);
            $teamId       = (int) ($row->team ?? 0);
        } else {
            $departmentId = $this->getUserStateId('com_volunteers.edit.' . $view . '.departmentid');
            $teamId       = $this->getUserStateId('com_volunteers.edit.' . $view . '.teamid');
        }

        // A team wins over a department: a record that carries both is shown below its team.
        if ($teamId && $team = $this->getRow('teams', $teamId)) {
            return $this->target('teams', $this->getItemid('teams', $team->department), $team->alias);
        }

        if ($departmentId && $department = $this->getRow('departments', $departmentId)) {
            // Reports of a root department belong to the board.
            if ($view === 'report' && (int) $department->parent_id === 0) {
                return $this->target('board', $this->getItemid('board'));
            }

            return $this->target('departments', $this->getItemid('departments'), $department->alias);
        }

        return $this->target(null, null);
    }

    /**
     * Assemble a target description.
     *
     * @param   ?string  $base    The view of the menu item the route is relative to
     * @param   ?int     $itemid  The id of that menu item
     * @param   ?string  $alias   The leading alias segment, if the route has one
     * @param   ?string  $marker  The marker segment following the alias, if the route has one
     *
     * @return  array{base: ?string, itemid: ?int, alias: ?string, marker: ?string}
     *
     * @since   6.1.0
     */
    private function target(?string $base, ?int $itemid, ?string $alias = null, ?string $marker = null): array
    {
        return [
            'base'   => $base,
            'itemid' => $itemid,
            'alias'  => ($alias === null || $alias === '') ? null : $alias,
            'marker' => $marker,
        ];
    }

    /**
     * Find the best menu item for a view.
     *
     * A menu item whose own id matches `$id` is preferred, then one without an id at all, then any
     * remaining item for the view. Menu items in the active language always win over the rest.
     *
     * @param   string        $view  The view to find a menu item for
     * @param   int|string|null  $id  An id the menu item should be filtered on, if any
     *
     * @return  ?int  The id of the menu item, or null when the site has none
     *
     * @since   6.1.0
     */
    private function getItemid(string $view, $id = null): ?int
    {
        if ($this->menuItems === null) {
            $this->menuItems = $this->menu->getItems('component', 'com_volunteers') ?: [];
        }

        if (!$this->menuItems) {
            return null;
        }

        $language = \is_callable([$this->app, 'getLanguage']) ? $this->app->getLanguage()->getTag() : '*';
        $id       = $id === null ? null : (int) $id;

        // First pass over the items for the active language, second pass over everything else.
        foreach ([true, false] as $localised) {
            $exact   = null;
            $generic = null;
            $any     = null;

            foreach ($this->menuItems as $item) {
                if (($item->query['view'] ?? null) !== $view) {
                    continue;
                }

                if ($localised !== ($item->language === '*' || $item->language === $language)) {
                    continue;
                }

                if ($id !== null && isset($item->query['id']) && (int) $item->query['id'] === $id) {
                    $exact = $exact ?? (int) $item->id;
                } elseif (!isset($item->query['id'])) {
                    $generic = $generic ?? (int) $item->id;
                }

                $any = $any ?? (int) $item->id;
            }

            if ($itemid = $exact ?? $generic ?? $any) {
                return $itemid;
            }
        }

        return null;
    }

    /**
     * Get the view of a com_volunteers menu item.
     *
     * @param   int|string|null  $itemid  The id of the menu item
     *
     * @return  ?string  The view of the menu item, or null when it is not a com_volunteers item
     *
     * @since   6.1.0
     */
    private function getMenuItemView($itemid): ?string
    {
        if (!$itemid) {
            return null;
        }

        $item = $this->menu->getItem($itemid);

        if ($item === null || $item->component !== 'com_volunteers') {
            return null;
        }

        return $item->query['view'] ?? null;
    }

    /**
     * Read the leading numeric id of an `<id>-<alias>` segment.
     *
     * @param   string  $segment  The segment to read
     *
     * @return  int  The id, or 0 when the segment does not start with one
     *
     * @since   6.1.0
     */
    private function parseLeadingId(string $segment): int
    {
        [$id] = explode('-', $segment, 2);

        return ctype_digit($id) ? (int) $id : 0;
    }

    /**
     * Read an integer from the user state.
     *
     * @param   string  $key  The user state key
     *
     * @return  int  The value, or 0 when it is not set
     *
     * @since   6.1.0
     */
    private function getUserStateId(string $key): int
    {
        if (!\is_callable([$this->app, 'getUserState'])) {
            return 0;
        }

        return (int) $this->app->getUserState($key);
    }

    /**
     * Load the routing columns of a single component record.
     *
     * @param   string  $table  The table suffix, e.g. `departments`
     * @param   int     $id     The id of the record
     *
     * @return  ?object  The record, or null when it does not exist
     *
     * @since   6.1.0
     */
    private function getRow(string $table, int $id): ?object
    {
        if ($id <= 0 || !isset(self::ROW_COLUMNS[$table])) {
            return null;
        }

        $key = $table . '.' . $id;

        if (!\array_key_exists($key, $this->rows)) {
            $query = $this->db->getQuery(true)
                ->select($this->db->quoteName(self::ROW_COLUMNS[$table]))
                ->from($this->db->quoteName('#__volunteers_' . $table))
                ->where($this->db->quoteName('id') . ' = :id')
                ->bind(':id', $id, ParameterType::INTEGER);

            $this->rows[$key] = $this->db->setQuery($query)->loadObject() ?: null;
        }

        return $this->rows[$key];
    }

    /**
     * Resolve the id of a component record from its alias.
     *
     * @param   string  $table  The table suffix, e.g. `departments`
     * @param   string  $alias  The alias to look up
     *
     * @return  int  The id, or 0 when no record matches
     *
     * @since   6.1.0
     */
    private function getIdByAlias(string $table, string $alias): int
    {
        if ($alias === '' || !\in_array($table, self::ALIAS_TABLES, true)) {
            return 0;
        }

        $key = $table . '.' . $alias;

        if (!isset($this->ids[$key])) {
            $query = $this->db->getQuery(true)
                ->select($this->db->quoteName('id'))
                ->from($this->db->quoteName('#__volunteers_' . $table))
                ->where($this->db->quoteName('alias') . ' = :alias')
                ->bind(':alias', $alias)
                ->setLimit(1);

            $this->ids[$key] = (int) $this->db->setQuery($query)->loadResult();
        }

        return $this->ids[$key];
    }
}
