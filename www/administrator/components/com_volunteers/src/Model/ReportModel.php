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

/**
 * Report model.
 * @since 4.0.0
 */
class ReportModel extends AdminModel
{
    /**
     * The type alias for this content type.
     *
     * @var    string
     * @since 4.0.0
     */
    public $typeAlias = 'com_volunteers.report';

    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     * @since 4.0.0
     */
    protected $text_prefix = 'COM_VOLUNTEERS';

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $name    The table name. Optional.
     * @param   string  $prefix  The class prefix. Optional.
     * @param   array   $options  Configuration array for model. Optional.
     *
     * @return  Table  A Table object
     * @since 4.0.0
     * @throws Exception
     */
    public function getTable($name = 'Report', $prefix = 'VolunteersTable', $options = [])
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
     * @since 4.0.0
     * @throws Exception
     */
    public function getForm($data = [], $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm('com_volunteers.report', 'report', ['control' => 'jform', 'load_data' => $loadData]);

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
     * @since 4.0.0
     * @throws Exception
     */
    protected function loadFormData()
    {
        // Check the session for previously entered form data.
        $data = Factory::getApplication()->getUserState('com_volunteers.edit.report.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_volunteers.report', $data);

        return $data;
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

        $table->title = htmlspecialchars_decode((string) $table->title, ENT_QUOTES);
        $table->alias = ApplicationHelper::stringURLSafe($table->alias);

        if (empty($table->alias)) {
            $table->alias = ApplicationHelper::stringURLSafe($table->title);
        }

        if (empty($table->getId())) {
            // Set the values

            // Set ordering to the last item if not set
            if (empty($table->ordering)) {
                $db    = $this->getDatabase();
                $query = $db->getQuery(true)
                    ->select('MAX(ordering)')
                    ->from($db->quoteName('#__volunteers_reports'));

                $db->setQuery($query);
                $max = $db->loadResult();

                $table->ordering = $max + 1;
            } else {
                // Set the values
                $table->modified    = $date->toSql();
                $table->modified_by = $user->id;
            }
        }

        // Increment the version number.
        $v  = $table->version;
        $v++;
        $table->version = $v;
    }


    /**
     * Method to save the form data.
     *
     * @param   array  $data  The form data.
     *
     * @return  boolean  True on success.
     * @since 4.0.0
     * @throws Exception
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
            if ($title == $table->title) {
                $title = StringHelper::increment($title);
            }

            $alias = StringHelper::increment($alias, 'dash');
        }

        return [$title, $alias];
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
    public function &getItem($pk = null)
    {
        $pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');



        if ($pk > 0) {
            try {
                $db    = $this->getDatabase();
                $query = $db->getQuery(true)
                    ->select($this->getState('item.select', 'a.*'))
                    ->from('#__volunteers_reports AS a')
                    ->where('a.id = ' . (int) $pk);

                // Join on volunteer table.
                $query->select('volunteer.id AS volunteer_id, volunteer.image AS volunteer_image')
                    ->join('LEFT', '#__volunteers_volunteers AS ' . $db->quoteName('volunteer') . ' on volunteer.user_id = a.created_by');

                // Join over the users for the related user.
                $query
                    ->select('user.name AS volunteer_name')
                    ->join('LEFT', '#__users AS ' . $db->quoteName('user') . ' ON user.id = a.created_by');

                // Join on department table.
                $query->select('department.title AS department_title, department.parent_id AS department_parent_id')
                    ->join('LEFT', '#__volunteers_departments AS ' . $db->quoteName('department') . ' on department.id = a.department');

                // Join on team table.
                $query->select('team.title AS team_title')
                    ->join('LEFT', '#__volunteers_teams AS ' . $db->quoteName('team') . ' on team.id = a.team');

                // Filter by published state.
                $published = $this->getState('filter.published');
                $archived  = $this->getState('filter.archived');

                if (is_numeric($published)) {
                    $query->where('(a.state = ' . (int) $published . ' OR a.state =' . (int) $archived . ')');
                    //  ->where('(c.published = ' . (int) $published . ' OR c.published =' . (int) $archived . ')');
                }

                $db->setQuery($query);

                $data = $db->loadObject();

                if (empty($data)) {
                    throw new Exception(Text::_('COM_VOLUNTEERS_ERROR_REPORT_NOT_FOUND'), 404);
                }

                // Check for published state if filter set.
                if (((is_numeric($published)) || (is_numeric($archived))) && (($data->state != $published) && ($data->state != $archived))) {
                    throw new Exception(Text::_('COM_VOLUNTEERS_ERROR_REPORT_NOT_FOUND'), 404);
                }

                return $data;
            } catch (Exception $e) {
                throw new Exception($e);
            }
        }

        // Convert to the JObject before adding other data.
        $properties = $this->getTable()->getTableProperties(1);
        $item       = ArrayHelper::toObject($properties);

        return $item;
    }

    /**
     * Method to get volunteer info.
     *
     * @return  mixed  Data object on success, false on failure.
     * @since 4.0.0
     * @throws Exception
     */
    public function getVolunteer()
    {
        // Get user
        $user = $this->getCurrentUser();

        // Get subteams
        $model       = $this->getMVCFactory()->createModel('Volunteer', 'Administrator', ['ignore_request' => true]);
        $volunteerId = $model->getVolunteerId($user->id);

        return $model->getItem($volunteerId);
    }
}
