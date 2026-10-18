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
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;

use Joomla\CMS\Table\Table;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;

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
            ->select($db->quoteName('position'))
            ->from($db->quoteName('#__volunteers_members'))
            ->where($db->quoteName('volunteer') . ' = :volunteer')
            ->where($db->quoteName('date_ended') . ' = ' . $nullDate)
            ->bind(':volunteer', $volunteerId, ParameterType::INTEGER);

        if ($department) {
            $query->where($db->quoteName('department') . ' = :department')
                ->bind(':department', $department, ParameterType::INTEGER);
        } elseif ($team) {
            $query->where($db->quoteName('team') . ' = :team')
                ->bind(':team', $team, ParameterType::INTEGER);
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
    public function getTable($name = 'Member', $prefix = 'Administrator', $options = [])
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

        if (empty($table->getId())) {
            // Set the values

            // Set ordering to the last item if not set
            if (empty($table->get('ordering'))) {
                $db    = $this->getDatabase();
                $query = $db->getQuery(true)
                    ->select('MAX(ordering)')
                    ->from($db->quoteName('#__volunteers_members'));

                $db->setQuery($query);
                $max = $db->loadResult();

                $table->set('ordering', $max + 1);
            } else {
                // Set the values
                $table->set('modified', $date->toSql());
                $table->set('modified_by', $user->id);
            }
        }
    }

    /**
     * Method to get an object.
     *
     * @param   int|null  $id  The id of the object to get.
     *
     * @return Object|boolean Object on success, false on failure.
     *
     * @since 4.0.0
     * @throws Exception
     */
    public function getItem($pk = null)
    {

        return parent::getItem($pk);
    }
}
