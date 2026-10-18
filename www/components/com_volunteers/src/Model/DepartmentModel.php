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
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\AdminModel;

use Joomla\CMS\Table\Table;
use Joomla\Database\ParameterType;
use Joomla\String\StringHelper;
use stdClass;

/**
 * Department model.
 * @since 4.0.0
 */
class DepartmentModel extends AdminModel
{
    /**
     * The type alias for this content type.
     *
     * @var    string
     * @since 4.0.0
     */
    public $typeAlias = 'com_volunteers.department';

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
     * @var \Joomla\CMS\Application\CMSApplicationInterface
     * @since  6.1.0
     */
    protected $app;

    /**
     * Constructor.
     *
     * @param   array                $config   An optional associative array of configuration settings.
     * @param   MVCFactoryInterface  $factory  The factory.
     *
     * @since   4.0.0
     * @throws  Exception
     */
    public function __construct($config = [], MVCFactoryInterface $factory = null)
    {
        parent::__construct($config, $factory);

        $this->app = Factory::getApplication();
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
    public function getDepartmentMembers(int $pk = null)
    {
        $pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');

        // Get members

        $model = $this->getMVCFactory()->createModel('Members', 'Administrator', ['ignore_request' => true]);
        $model->setState('filter.department', $pk);
        $items = $model->getItems();

        // Sorting the results
        $leaders    = [];
        $assistants = [];
        $volunteers = [];

        foreach ($items as $item) {
            switch ($item->position) {
                case 9:
                case 11:
                    $leaders[$item->volunteer_name . $item->date_ended] = $item;
                    break;

                case 10:
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
            if (empty($item->date_ended) || $item->date_ended === '0000-00-00') {
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
     * Method to get Department Reports.
     *
     * @param   int|null  $pk  The id of the team.
     *
     * @return  mixed  Data object on success, false on failure.
     * @since 4.0.0
     * @throws Exception
     */
    public function getDepartmentReports(int $pk = null)
    {
        $pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');

        // Get reports
        $model = $this->getMVCFactory()->createModel('Reports', 'Administrator', ['ignore_request' => true]);
        $model->setState('filter.department', $pk);
        $model->setState('list.limit', 25);

        return $model->getItems();
    }

    /**
     * Method to get Department Reports.
     *
     * @param   int|null  $pk  The id of the team.
     *
     * @return  mixed  Data object on success, false on failure.
     * @since 4.0.0
     * @throws Exception
     */
    public function getDepartmentReportsTeams(int $pk = null)
    {
        $pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');

        // Get reports
        $model = $this->getMVCFactory()->createModel('Reports', 'Administrator', ['ignore_request' => true]);
        $model->setState('filter.departmentTeams', $pk);
        $model->setState('list.limit', 25);

        return $model->getItems();
    }

    /**
     * Method to get total number of team reports.
     *
     * @param   int|null  $pk  The id of the team.
     *
     * @return  integer
     * @since 4.0.0
     * @throws Exception
     */
    public function getDepartmentReportsTotal(int $pk = null)
    {
        $pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');

        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('count(id)')
            ->from($db->quoteName('#__volunteers_reports'))
            ->where($db->quoteName('department') . ' = :pk')
            ->where($db->quoteName('state') . ' = 1')
            ->bind(':pk', $pk, ParameterType::INTEGER);

        return $db->setQuery($query)->loadResult();
    }

    /**
     * Method to get Department Teams.
     *
     * @param   int|null  $pk  The id of the team.
     *
     * @return  array  Data object on success, false on failure.
     * @since 4.0.0
     * @throws Exception
     */
    public function getDepartmentTeams(int $pk = null)
    {
        $pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');

        // Get teams
        $model = $this->getMVCFactory()->createModel('Teams', 'Administrator', ['ignore_request' => true]);
        $model->setState('filter.department', $pk);
        $model->setState('list.limit', 0);
        $teams = $model->getItems();

        $teamsById = [];
        foreach ($teams as $team) {
            $teamsById[$team->id] = $team;
        }

        // Get the department team leads
        $teamLeads = $this->getAllDepartmentTeamLeads(array_keys($teamsById));
        //teamleads is an array(teamid) of positions so
        foreach ($teamLeads as $teamentries) {
            foreach ($teamentries as $lead) {
                if (!str_contains((string) $lead->position_title, 'Assistant')) {
                    $teamsById[$lead->team]->leader[] = $lead;
                } else {
                    $teamsById[$lead->team]->assistantleader[] = $lead;
                }
            }
        }

        return $teamsById;
    }

    /**
     * Method to get All Department Teams.
     *
     * @param   array  $teams  The ids of the teams.
     *
     * @return  array
     * @since 4.0.0
     * @throws Exception
     */
    public function getAllDepartmentTeamLeads(array $teams)
    {
        $out = [];
        foreach ($teams as $team_id) {
            $out[] = $this->getDepartmentTeamLeads($team_id);
        }

        return $out;
    }

    /**
     * Method to get Department Teams.
     *
     * @param   int|null  $pk  The ids of the team.
     *
     * @return  mixed  Data object on success, false on failure.
     * @since 4.0.0
     * @throws Exception
     */
    public function getDepartmentTeamLeads(int $pk = null)
    {
        $pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');

        // Get team lead positions
        $model = $this->getMVCFactory()->createModel('Positions', 'Administrator', ['ignore_request' => true]);
        $model->setState('filter.type', 2);
        $model->setState('filter.acl', 'edit');
        $positions = $model->getItems();

        $positionIds = [];
        foreach ($positions as $position) {
            $positionIds[] = $position->id;
        }

        // Get team leads

        $model = $this->getMVCFactory()->createModel('Members', 'Administrator', ['ignore_request' => true]);
        $model->setState('filter.team', $pk);
        $model->setState('filter.position', $positionIds);
        $model->setState('filter.active', 1);

        return $model->getItems();
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $name     The table name. Optional.
     * @param   string  $prefix   The class prefix. Optional.
     * @param   array   $options  Configuration array for model. Optional.
     *
     * @return  Table  A Table object
     * @since 4.0.0
     * @throws Exception
     */
    public function getTable($name = 'Department', $prefix = 'VolunteersTable', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }

    /**
     * Abstract method for getting the form from the model.
     *
     * @param   array    $data      Data for the form.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  Form|bool  A Form object on success, false on failure
     * @since 4.0.0
     * @throws Exception
     */
    public function getForm($data = [], $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm('com_volunteers.department', 'department', ['control' => 'jform', 'load_data' => $loadData]);

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
     * @return  mixed  The default data is an empty array.
     * @since 4.0.0
     * @throws Exception
     */
    protected function loadFormData()
    {
        // Check the session for previously entered form data.
        $data = $this->app->getUserState('com_volunteers.edit.department.data', []);

        if (empty($data)) {
            if ($this->item === null) {
                $this->item = $this->getItem();
            }

            $data = $this->item;
        }
        $this->preprocessData('com_volunteers.department', $data);

        return (array) $data;
    }

    /**
     * Prepare and sanitise the table data prior to saving.
     *
     * @param   Table  $table  A reference to a Table object.
     *
     * @return  void
     * @since 4.0.0
     * @throws Exception
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
                    ->from($db->quoteName('#__volunteers_departments'));

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
     *
     * @since 4.0.0
     * @throws Exception
     */
    public function save($data)
    {
        // Alter the title for save as copy
        if ($this->app->getInput()->get('task') == 'save2copy') {
            [$name, $alias] = $this->generateNewTitle(0, $data['alias'], $data['title']);
            $data['title']      = $name;
            $data['alias']      = $alias;
            $data['state']      = 0;
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
     * @since 4.0.0
     * @throws Exception
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
     * Method to get a single record.
     *
     * @param   integer  $pk  The id of the primary key.
     *
     * @return  stdClass|boolean  Object on success, false on failure.
     *
     * @since  4.0.0
     * @throws Exception
     */
    public function getItem($pk = null)
    {
        $pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');

        return parent::getItem($pk);
    }
}
