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

/**
 * Position model.
 * @since 4.0.0
 */
class PositionModel extends AdminModel
{
    /**
     * The type alias for this content type.
     *
     * @var    string
     * @since 4.0.0
     */
    public $typeAlias = 'com_volunteers.position';

    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     * @since 4.0.0
     */
    protected $text_prefix = 'COM_VOLUNTEERS';

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
     * Abstract method for getting the form from the model.
     *
     * @param   array    $data      Data for the form.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  false|Form  A Form object on success, false on failure
     * @since 4.0.0
     * @throws Exception
     */
    public function getForm($data = [], $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm('com_volunteers.position', 'position', ['control' => 'jform', 'load_data' => $loadData]);

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
    public function getTable($name = 'Position', $prefix = 'VolunteersTable', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
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
        $data = Factory::getApplication()->getUserState('com_volunteers.edit.position.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_volunteers.position', $data);

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
                    ->from($db->quoteName('#__volunteers_positions'));

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
        $v =  $table->get('version');
        $v++;
        $table->set('version', $v);
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
}
