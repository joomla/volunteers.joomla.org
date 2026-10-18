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
use Joomla\CMS\MVC\Model\AdminModel;

use Joomla\CMS\Table\Table;
use Joomla\String\StringHelper;
use stdClass;

/**
 * Board model.
 *
 * @since 4.0.0
 */
class BoardModel extends AdminModel
{
    /**
     * The type alias for this content type.
     *
     * @var    string
     *
     * @since 4.0.0
     */
    public $typeAlias = 'com_volunteers.board';

    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     *
     * @since 4.0.0
     */
    protected $text_prefix = 'COM_VOLUNTEERS';

    /**
     * @var null  Item data
     * @since  4.0.0
     */
    protected mixed $item = null;

    /**
     * Hard codes board of directors
     * @return bool|stdClass
     *
     * @since 4.0.0
     */
    public function getBoardItem()
    {
        return parent::getItem(58);
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
        // Get members
        /** @var MembersModel $model */
        $model = $this->getMVCFactory()->createModel('Members', 'Administrator', ['ignore_request' => true]);
        $model->setState('filter.position', [11, 13]);

        $items = $model->getItems();

        // Sorting the results
        $president     = [];
        $vicepresident = [];
        $secretary     = [];
        $treasurer     = [];
        $coordinator   = [];

        foreach ($items as $item) {
            switch ($item->role) {
                case 286:
                    $president['president-' . $item->volunteer_name . $item->date_ended] = $item;
                    break;

                case 287:
                    $vicepresident['vicepresident-' . $item->volunteer_name . $item->date_ended] = $item;
                    break;

                case 288:
                    $secretary['secretary-' . $item->volunteer_name . $item->date_ended] = $item;
                    break;

                case 289:
                    $treasurer['treasurer-' . $item->volunteer_name . $item->date_ended] = $item;
                    break;
            }

            switch ($item->position) {
                case 11:
                    $coordinator[$item->volunteer_name . $item->date_ended] = $item;
                    break;
            }
        }


        // Sort all members by name
        ksort($coordinator);

        // Group them again
        $groupmembers = $president + $vicepresident + $secretary + $treasurer + $coordinator;

        $members            = new stdClass();
        $members->active    = [];
        $members->honorroll = [];

        // Check for active or inactive members
        foreach ($groupmembers as $item) {
            if ($item->date_ended == '0000-00-00') {
                $members->active[] = $item;
            } else {
                $members->honorroll[] = $item;
            }
        }

        return $members;
    }

    /**
     * Method to get Department Reports.
     *
     * @param   int|null  $pk  The id of the team.
     *
     * @return  mixed  Data object on success, false on failure.
     *
     * @since 4.0.0
     * @since 4.0.0
     * @throws Exception
     */
    public function getDepartmentReports(int $pk = null)
    {
        $pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');

        // Get reports
        /** @var ReportsModel $model */
        $model = $this->getMVCFactory()->createModel('Reports', 'Administrator', ['ignore_request' => true]);
        $model->setState('filter.department', $pk);
        $model->setState('list.limit', 10);

        return $model->getItems();
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $name
     * @param   string  $prefix  The class prefix. Optional.
     * @param   array   $options
     *
     * @return  Table  A Table object
     *
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
     * @return  mixed  A Form object on success, false on failure
     *
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
     * @since 4.0.0
     * @throws Exception
     */
    protected function loadFormData()
    {
        // Check the session for previously entered form data.
        $data = Factory::getApplication()->getUserState('com_volunteers.edit.department.data', []);

        if (empty($data)) {
            if ($this->item === null) {
                $this->item = $this->getItem();
            }

            $data = $this->item;
        }
        $this->preprocessData('com_volunteers.department', $data);

        return $data;
    }

    /**
     * Prepare and sanitise the table data prior to saving.
     *
     * @param   Table  $table  A reference to a Table object.
     *
     * @return  void
     *
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
                    ->from($db->quoteName('#__volunteers_departments'));

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
        $table->version++;
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
     *
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
}
