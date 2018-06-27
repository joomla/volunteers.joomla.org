<?php

defined('JPATH_PLATFORM') or die;

JFormHelper::loadFieldClass('checkboxes');

class JFormFieldButtons extends JFormFieldCheckboxes
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  11.1
     */
    protected $type = 'Buttons';

    /**
     * Method to get the field input markup for check boxes.
     *
     * @return  string  The field input markup.
     *
     * @since   11.1
     */
    protected function getInput()
    {
        $html = parent::getInput();

        // Get the field options.
        $options = $this->getOptions();

        foreach($options as $i => $option) {
            $html = preg_replace('#<label for="' . $this->id . $i . '"([^>]*)>#', '<label for="' . $this->id . $i . '"$1><div class="mce-toolbar"><i class="mce-ico mce-i-' . $option->value . '"></i> ', $html);
        }

        return $html;
    }
}