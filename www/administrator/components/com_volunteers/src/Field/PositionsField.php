<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Administrator\Field;

use Exception;
use Joomla\CMS\Form\Field\GroupedlistField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Component\Volunteers\Administrator\Helper\VolunteersHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects



/**
 * Positions Field class.
 * @since 4.0.0
 */
class PositionsField extends GroupedlistField
{
    /**
     * The form field type.
     *
     * @var        string
     * @since 4.0.0
     */
    protected $type = 'Positions';

    /**
     * Method to get the field options, grouped by the type of the position.
     *
     * @return  array  The field option objects, grouped by type.
     * @since 4.0.0
     * @throws Exception
     */
    protected function getGroups()
    {
        $types  = [0 => 'JNONE', 1 => 'COM_VOLUNTEERS_FIELD_DEPARTMENT', 2 => 'COM_VOLUNTEERS_FIELD_TEAM'];
        // A field with multiple values has no option to select nothing
        $groups = [];

        if (!$this->multiple) {
            $groups[] = [HTMLHelper::_('select.option', '', Text::_('COM_VOLUNTEERS_SELECT_POSITION'))];
        }

        foreach (VolunteersHelper::positions() ?? [] as $position) {
            $groups[Text::_($types[(int) $position->type] ?? 'JNONE')][] = HTMLHelper::_('select.option', $position->value, $position->text);
        }

        return $groups;
    }
}
