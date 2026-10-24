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

use Joomla\CMS\Form\Field\TextField;

/**
 * Volunteers Field class.
 * @since 4.0.0
 */
class TextprependField extends TextField
{
    /**
     * The form field type.
     *
     * @var        string
     * @since 4.0.0
     */
    protected $type = 'Textprepend';

    /**
     * Method to get the data to be passed to the layout for rendering.
     *
     * @return  array
     *
     * @since   6.1.0
     */
    protected function getLayoutData()
    {
        $data = parent::getLayoutData();

        // The start of the link shows in front of the field as a core input group
        $data['addonBefore'] = $data['addonBefore'] ?: (string) $this->element['prepend'];

        return $data;
    }
}
