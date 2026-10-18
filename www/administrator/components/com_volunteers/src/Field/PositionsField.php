<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Administrator\Field;

use Exception;
use Joomla\CMS\Form\Field\ListField;
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
class PositionsField extends ListField
{
    /**
     * The form field type.
     *
     * @var        string
     * @since 4.0.0
     */
    protected $type = 'Positions';

    /**
     * Method to get the field options.
     *
     * @return  array  The field option objects.
     * @since 4.0.0
     * @throws Exception
     */
    public function getOptions()
    {
        $options   = VolunteersHelper::positions();
        $default[] = HTMLHelper::_('select.option', '', Text::_('COM_VOLUNTEERS_SELECT_POSITION'));

        return array_merge($default, $options);
    }
}
