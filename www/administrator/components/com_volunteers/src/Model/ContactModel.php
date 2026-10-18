<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Administrator\Model;

use Exception;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Contact model.
 * @since 4.0.0
 */
class ContactModel extends AdminModel
{
    /**
     * Get active volunteers
     *
     * @return  object[]
     * @since 4.0.0
     */
    public function getActiveVolunteers()
    {
        $query = $this->getDatabase()->getQuery(true);

        $query
            ->select('DISTINCT u.id, u.name, u.email')
            ->from('#__users AS u')
            ->leftJoin('#__volunteers_volunteers AS v ON u.id = v.user_id')
            ->leftJoin('#__volunteers_members AS m ON v.id = m.volunteer')
            ->leftJoin('#__volunteers_teams AS t ON t.id = m.team')
            ->where('m.team IS NOT NULL')
            ->where('t.title IS NOT NULL')
            ->where('m.date_ended IS NULL')
            ->where('t.date_ended IS NULL');

        $this->getDatabase()->setQuery($query);

        return $this->getDatabase()->loadObjectList();
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
    public function getForm($data = [], $loadData = false)
    {
        // Get the form.
        $form = $this->loadForm('com_volunteers.contact', 'contact', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        return $form;
    }
}
