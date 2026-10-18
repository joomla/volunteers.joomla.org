<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Administrator\Field;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Exception;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Component\Volunteers\Administrator\Helper\VolunteersHelper;

/**
 * Teams Field class.
 * @since 4.0.0
 */
class TeamsField extends ListField
{
    /**
     * The form field type.
     *
     * @var        string
     * @since 4.0.0
     */
    protected $type = 'Teams';

    /**
     * Method to get the field options.
     *
     * @return  array  The field option objects.
     * @since 4.0.0
     * @throws Exception
     */
    public function getOptions()
    {
        $parent    = $this->element['parent'];
        $teams     = VolunteersHelper::teams($parent);
        $default[] = HTMLHelper::_('select.option', '', Text::_('COM_VOLUNTEERS_SELECT_TEAM'));
        $options   = array_merge($default, $teams);

        return array_merge(parent::getOptions(), $options);
    }
}
