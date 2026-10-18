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
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;

use Joomla\CMS\Table\Table;
use Joomla\Component\Volunteers\Administrator\Helper\VolunteersHelper;

/**
 * Member model.
 * @since 4.0.0
 */
class MemberModel extends AdminModel
{
    /**
     * The type alias for this content type.
     *
     * @var    string
     * @since 4.0.0
     */
    public $typeAlias = 'com_volunteers.member';

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
     * @param $volunteerId
     * @param $department
     * @param $team
     *
     * @return false
     *
     * @since 4.0.0
     */
    public function getPosition($volunteerId, $department = null, $team = null)
    {
        if (empty($volunteerId)) {
            return false;
        }

        $db = $this->getDatabase();

        $nullDate = $db->quote($db->getNullDate());

        $query = $db->getQuery(true)
            ->select('position')
            ->from('#__volunteers_members')
            ->where($db->quoteName('volunteer') . ' = ' . (int) $volunteerId)
            ->where('date_ended = ' . $nullDate);

        if ($department) {
            $query->where($db->quoteName('department') . ' = ' . (int) $department);
        } elseif ($team) {
            $query->where($db->quoteName('team') . ' = ' . (int) $team);
        }

        $db->setQuery($query);

        return $db->loadResult();
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
    public function getTable($name = 'Member', $prefix = 'VolunteersTable', $options = [])
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
        $form = $this->loadForm('com_volunteers.member', 'member', ['control' => 'jform', 'load_data' => $loadData]);

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
        $data = Factory::getApplication()->getUserState('com_volunteers.edit.member.data', []);

        if (empty($data)) {
            if ($this->item === null) {
                $this->item = $this->getItem();
            }
            $data = $this->item;
        }

        $this->preprocessData('com_volunteers.member', $data);

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

        if (empty($table->getId())) {
            // Set the values

            // Set ordering to the last item if not set
            if (empty($table->ordering)) {
                $db    = $this->getDatabase();
                $query = $db->getQuery(true)
                    ->select('MAX(ordering)')
                    ->from($db->quoteName('#__volunteers_members'));

                $db->setQuery($query);
                $max = $db->loadResult();

                $table->ordering = $max + 1;
            } else {
                // Set the values
                $table->modified    = $date->toSql();
                $table->modified_by = $user->id;
            }
        }
    }

    /**
     * Method to get a single record.
     *
     * @param   null  $pk  The id of the primary key.
     *
     * @return stdClass|bool Object on success
     *
     * @since  4.0.0
     * @throws Exception
     */
    public function getItem($pk = null)
    {
        $pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');

        $item =  parent::getItem($pk);

        if ($item->department == 0) {
            $db    = $this->getDatabase();
            $query = $db->createQuery();
            $query->select('a.department')->from('#__volunteers_teams AS a')->where('a.id = ' . (int) $item->team);

            $db->setQuery($query);
            $item->department = $db->loadResult();
        }
        return $item;
    }
}
