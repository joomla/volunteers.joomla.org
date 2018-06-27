<?php

/**
 * @package     Joomla.Platform
 * @subpackage  Form
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
defined('JPATH_PLATFORM') or die;

JFormHelper::loadFieldClass('number');

/**
 * Form Field class for the Joomla Platform.
 * Supports a one line text field.
 *
 * @package     Joomla.Platform
 * @subpackage  Form
 * @link        http://www.w3.org/TR/html-markup/input.text.html#input.text
 * @since       11.1
 */
class JFormFieldUploadMaxSize extends JFormFieldNumber
{

    /**
     * The form field type.
     *
     * @var    string
     *
     * @since  11.1
     */
    protected $type = 'uploadmaxsize';

    /**
     * Method to get the field input markup.
     *
     * @return  string  The field input markup.
     *
     * @since   11.1
     */
    protected function getInput()
    {
        $this->max = (int) $this->getUploadValue();
        $this->class = trim($this->class . ' input-small');

        $html = '<div class="input-append">';

        $html .= parent::getInput();
        $html .= '<span class="add-on">Kb</span>';
        $html .= '</div>';
        $html .= '&nbsp;<span class="help-inline"><em>' . JText::_('COM_JCE_SERVER_UPLOAD_SIZE') . ' : ' . $this->getUploadValue() . '</em></span>';

        return $html;
    }

    public function getUploadValue()
    {
        $upload = trim(ini_get('upload_max_filesize'));
        $post = trim(ini_get('post_max_size'));

        $upload = $this->convertValue($upload);
        $post = $this->convertValue($post);

        if (intval($post) === 0) {
            return $upload;
        }

        if (intval($upload) < intval($post)) {
            return $upload;
        }

        return $post;
    }

    public function convertValue($value)
    {
        $unit = 'KB';
        $prefix = '';

        preg_match('#([0-9]+)\s?([a-z]*)#i', $value, $matches);

        // get unit
        if (isset($matches[2])) {
            $prefix = $matches[2];
        }
        // get value
        if (isset($matches[1])) {
            $value = (int) $matches[1];
        }

        // Convert to bytes
        switch (strtolower($prefix)) {
            case 'g':
                $value *= 1073741824;
                break;
            case 'm':
                $value *= 1048576;
                break;
            case 'k':
                $value *= 1024;
                break;
        }

        // Convert to unit value
        switch (strtolower($unit)) {
            case 'g':
            case 'gb':
                $value /= 1073741824;
                break;
            case 'm':
            case 'mb':
                $value /= 1048576;
                break;
            case 'k':
            case 'kb':
                $value /= 1024;
                break;
        }

        return (int) $value . ' KB';
    }

}
