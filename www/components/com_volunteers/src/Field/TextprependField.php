<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Site\Field;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Form\Field\TextField;
use Joomla\CMS\Language\Text;

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
     * @var
     * @since version
     */
    protected $element;

    /**
     * Method to get the field options.
     *
     * @return  string  The field input markup.
     *
     * @since 4.0.0
     */
    public function getInput()
    {
        $html[] = '<div class="input-prepend"><span class="add-on">' . Text::_($this->element['prepend']) . '</span>';
        $html[] = parent::getInput();
        $html[] = '</div>';

        return implode('', $html);
    }
}
