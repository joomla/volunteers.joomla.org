<?php
/*
 * @package		Perfect Image Form Field
 * @copyright	Copyright (c) 2016 Perfect Web Team / perfectwebteam.nl
 * @license		GNU General Public License version 3 or later
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/**
 * Sample list form field
 */
class JFormFieldVolunteerimage extends JFormField
{
    /**
     * The form field type.
     *
     * @var  string
     */
    protected $type = 'Volunteerimage';

    protected function getInput()
    {
        // Setup variables for display.
        $html = array();

        // Container
        $html[] = '<div id="' . $this->id . '">';

        // Image
        $html[] = '<div class="image-preview">';

        if ($this->value)
        {
            $html[] = '<img src="' . $this->value . '"/>';
        }
        else
        {
            $html[] = '<p>' . Text::_('COM_VOLUNTEERS_IMAGE_NOT_PROVIDED') .'</p>';
        }

        $html[] = '</div>';

        $html[] = '<input type="hidden" id="' . $this->id . '" name="' . $this->name . '" value="' . $this->value . '" />';

        // Container end
        $html[] = '</div>';

        return implode("\n", $html);
    }
}
