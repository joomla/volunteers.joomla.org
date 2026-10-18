<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Administrator\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Exception;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;
use RuntimeException;
use stdClass;

/**
 * Team model.
 * @since 4.0.0
 */
class TeamModel extends AdminModel
{
    /**
     * The type alias for this content type.
     *
     * @var    string
     * @since 4.0.0
     */
    public $typeAlias = 'com_volunteers.team';

    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     * @since 4.0.0
     */
    protected $text_prefix = 'COM_VOLUNTEERS';

    /**
     * @var null  Item data
     * @since  4.0.0
     */
    protected mixed $item = null;

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $name     The table name. Optional.
     * @param   string  $prefix   The class prefix. Optional.
     * @param   array   $options  Configuration array for model. Optional.
     *
     * @return  Table  A Table object
     * @throws Exception
     * @since 4.0.0
     */
    public function getTable($name = 'Team', $prefix = 'VolunteersTable', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }

    /**
     * Abstract method for getting the form from the model.
     *
     * @param   array    $data      Data for the form.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  mixed  A Form object on success, false on failure
     * @throws Exception
     * @since 4.0.0
     */
    public function getForm($data = [], $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm('com_volunteers.team', 'team', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        // Modify the form based on access controls.
        if (!$this->canEditState((object) $data)) {
            // Disable fields for display.
            $form->setFieldAttribute('ordering', 'disabled', 'true');
            $form->setFieldAttribute('state', 'disabled', 'true');

            // Disable fields while saving.
            $form->setFieldAttribute('ordering', 'filter', 'unset');
            $form->setFieldAttribute('state', 'filter', 'unset');
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  array  The default data is an empty array.
     *
     * @throws Exception
     * @since 4.0.0
     */
    protected function loadFormData()
    {
        // Check the session for previously entered form data.
        $data = Factory::getApplication()->getUserState('com_volunteers.edit.team.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_volunteers.team', $data);

        return $data;
    }

    /**
     * Prepare and sanitise the table data prior to saving.
     *
     * @param   Table  $table  A reference to a Table object.
     *
     * @return  void
     * @throws Exception
     * @since 4.0.0
     */
    protected function prepareTable($table)
    {
        $date = Factory::getDate();
        $user = $this->getCurrentUser();

        $table->set('title', htmlspecialchars_decode((string) $table->get('title'), ENT_QUOTES));
        $table->set('alias', ApplicationHelper::stringURLSafe($table->get('alias')));

        if (empty($table->get('alias'))) {
            $table->set('alias', ApplicationHelper::stringURLSafe($table->get('title')));
        }

        if (empty($table->getId())) {
            // Set the values

            // Set ordering to the last item if not set
            if (empty($table->get('ordering'))) {
                $db    = $this->getDatabase();
                $query = $db->getQuery(true)
                    ->select('MAX(ordering)')
                    ->from($db->quoteName('#__volunteers_teams'));

                $db->setQuery($query);
                $max = $db->loadResult();

                $table->set('ordering', $max + 1);
            } else {
                // Set the values
                $table->set('modified', $date->toSql());
                $table->set('modified_by', $user->id);
            }
        }

        // Increment the version number.
        $v = $table->get('version');
        $v++;
        $table->set('version', $v);
    }

    /**
     * Method to save the form data.
     *
     * @param   array  $data  The form data.
     *
     * @return  boolean  True on success.
     * @throws Exception
     * @since 4.0.0
     */
    public function save($data)
    {
        $app = Factory::getApplication();

        // Alter the title for save as copy
        if ($app->getInput()->get('task') == 'save2copy') {
            [$name, $alias] = $this->generateNewTitle(0, $data['alias'], $data['title']);
            $data['title']      = $name;
            $data['alias']      = $alias;
            $data['state']      = 0;
        }

        // Move team members to the honour roll if team end-date is set
        if ($data['date_ended']) {
            $members    = $this->getTeamMembers($data['id']);
            $membersIds = array_map(
                fn($member) => $member->id,
                $members->active
            );

            if (count($membersIds)) {
                // Set date_ended for active members
                $db    = $this->getDatabase();
                $query = $db->createQuery();
                $query
                    ->update('#__volunteers_members')
                    ->set('date_ended = ' . $db->quote($data['date_ended']))
                    ->where('id IN (' . implode(',', $membersIds) . ')');

                try {
                    $db->setQuery($query)->execute();
                } catch (RuntimeException $e) {
                    throw new Exception($e->getMessage(), 500);
                }
            }

            // Close all open positions for team
            $db    = $this->getDatabase();
            $query = $db->createQuery();
            $query
                ->update('#__volunteers_roles')
                ->set('open = 0')
                ->where('team = ' . $db->quote($data['id']));

            try {
                $db->setQuery($query)->execute();
            } catch (RuntimeException $e) {
                throw new Exception($e->getMessage(), 500);
            }
        }

        return parent::save($data);
    }

    /**
     * Method to change the title & alias.
     *
     * @param   integer  $categoryId  The id of the parent.
     * @param   string   $alias       The alias.
     * @param   string   $title       The title.
     *
     * @return  array  Contains the modified title and alias.
     * @throws Exception
     * @since 4.0.0
     */
    protected function generateNewTitle($categoryId, $alias, $title)
    {
        // Alter the title & alias
        $table = $this->getTable();

        while ($table->load(['alias' => $alias])) {
            if ($title == $table->get('title')) {
                $title = StringHelper::increment($title);
            }

            $alias = StringHelper::increment($alias, 'dash');
        }

        return [$title, $alias];
    }

    /**
     * Method to get Team Members.
     *
     * @param   int|null  $pk  The id of the team.
     *
     * @return  stdClass  Data object on success, false on failure.
     * @since 4.0.0
     *@throws Exception
     */
    public function getTeamMembers($pk = null)
    {
        $pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');

        // Get members
        $model = $this->getMVCFactory()->createModel('Members', 'Administrator', ['ignore_request' => true]);
        $model->setState('filter.team', $pk);
        $items = $model->getItems();

        // Sorting the results
        $leaders    = [];
        $assistants = [];
        $volunteers = [];

        foreach ($items as $item) {
            switch ($item->position) {
                case 2:
                    $leaders[$item->volunteer_name . $item->date_ended] = $item;
                    break;

                case 7:
                    $assistants[$item->volunteer_name . $item->date_ended] = $item;
                    break;

                default:
                    $volunteers[$item->volunteer_name . $item->date_ended] = $item;
                    break;
            }
        }

        // Sort all members by name
        ksort($leaders);
        ksort($assistants);
        ksort($volunteers);

        // Group them again
        $groupmembers = $leaders + $assistants + $volunteers;

        $members            = new stdClass();
        $members->active    = [];
        $members->honorroll = [];

        // Check for active or inactive members
        foreach ($groupmembers as $item) {
            if ($item->date_ended == '0000-00-00') {
                $members->active[] = $item;
            } else {
                $members->honorroll[$item->date_ended . $item->volunteer_name] = $item;
            }
        }

        // Sort honor roll
        krsort($members->honorroll);

        return $members;
    }

    /**
     * Method to get Team Roles.
     *
     * @param   int|null  $pk  The id of the team.
     *
     * @return  mixed  Data object on success, false on failure.
     * @throws Exception
     * @since 4.0.0
     */
    public function getTeamRoles(int $pk = null)
    {
        $pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');

        // Get roles
        $model = $this->getMVCFactory()->createModel('Roles', 'Administrator', ['ignore_request' => true]);
        $model->setState('filter.team', $pk);
        $roles = $model->getItems();

        // Order by id
        $teamroles = [];
        foreach ($roles as $role) {
            $teamroles[$role->id] = $role;
        }

        $members = $this->getTeamMembers($pk);

        // Attach Joomlers to the roles
        foreach ($members->active as $member) {
            if ($member->role) {
                $teamroles[$member->role]->volunteers[] = $member;
            }
        }

        return $teamroles;
    }

    /**
     * Method to get Team Reports.
     *
     * @param   int|null  $pk  The id of the team.
     *
     * @return  mixed  Data object on success, false on failure.
     * @throws Exception
     * @since 4.0.0
     */
    public function getTeamReports(int $pk = null)
    {
        $pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');

        // Get reports
        $model = $this->getMVCFactory()->createModel('Reports', 'Administrator', ['ignore_request' => true]);
        $model->setState('filter.team', $pk);
        $model->setState('list.limit', 25);

        return $model->getItems();
    }

    /**
     * Method to get total number of team reports.
     *
     * @param   integer|null  $pk  The id of the team.
     *
     * @return  integer
     * @throws Exception
     * @since 4.0.0
     */
    public function getTeamReportsTotal(int $pk = null)
    {
        $pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');

        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('count(id)')
            ->from($db->quoteName('#__volunteers_reports'))
            ->where($db->quoteName('team') . ' = ' . $db->quote($pk))
            ->where($db->quoteName('state') . ' = 1');

        return $db->setQuery($query)->loadResult();
    }

    /**
     * Method to get Team Subteams.
     *
     * @param   integer|null  $pk  The id of the team.
     *
     * @return  mixed  Data object on success, false on failure.
     * @throws Exception
     * @since 4.0.0
     */
    public function getTeamSubteams(int $pk = null)
    {
        $pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');

        // Get subteams
        $model = $this->getMVCFactory()->createModel('Teams', 'Administrator', ['ignore_request' => true]);
        $model->setState('filter.subteams', true);
        $model->setState('filter.parent', $pk);
        $model->setState('list.limit', 0);

        return $model->getItems();
    }



    /**
     * Method to get team data.
     *
     * @param   integer  $pk  The id of the team.
     *
     * @return  mixed  Data object on success, false on failure.
     * @throws Exception
     * @since 4.0.0
     */
    public function getItem($pk = null)
    {
        $pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');

        $item = new stdClass();

        if ($pk > 0) {
            try {
                $db    = $this->getDatabase();
                $query = $db->getQuery(true)
                    ->select($this->getState('item.select', 'a.*'))
                    ->from('#__volunteers_teams AS a')
                    ->where('a.id = ' . (int) $pk);

                // Join on department table.
                $query->select('department.title AS department_title')
                    ->join('LEFT', '#__volunteers_departments AS ' . $db->quoteName('department') . ' on department.id = a.department');

                // Self-join over the parent team.
                $query
                    ->select('parentteam.title AS parent_title')
                    ->join('LEFT', '#__volunteers_teams AS ' . $db->quoteName('parentteam') . ' ON parentteam.id = a.parent_id');

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
                    throw new Exception(Text::_('COM_VOLUNTEERS_ERROR_TEAM_NOT_FOUND'), 404);
                }

                // Check for published state if filter set.
                if (((is_numeric($published)) || (is_numeric($archived))) && (($data->published != $published) && ($data->published != $archived))) {
                    throw new Exception(Text::_('COM_VOLUNTEERS_ERROR_TEAM_NOT_FOUND'), 404);
                }

                return $data;
            } catch (Exception $e) {
                throw new \Exception($e);
            }
        }

        // Convert to the stdClass before adding other data.
        $properties = $this->getTable()->getProperties(1);
        $item       = ArrayHelper::toObject($properties);

        return $item;
    }
}
