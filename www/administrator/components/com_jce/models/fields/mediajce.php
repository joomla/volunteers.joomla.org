<?php

/**
 * @package     JCE
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @copyright   Copyright (C) 2022 Ryan Demmer All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Form\Field\MediaField;
use Joomla\CMS\Helper\MediaHelper;

/**
 * Provides a modal media selector field for the JCE File Browser
 *
 * @since  2.6.17
 */
class JFormFieldMediaJce extends MediaField
{
    /**
     * The form field type.
     *
     * @var    string
     */
    protected $type = 'MediaJce';

    /**
     * Layout to render
     *
     * @var    string
     * @since  3.5
     */
    protected $layout = 'joomla.form.field.media';

    /**
     * Method to attach a JForm object to the field.
     *
     * @param   SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
     * @param   mixed             $value    The form field value to validate.
     * @param   string            $group    The field name group control value. This acts as an array container for the field.
     *                                      For example if the field has name="foo" and the group value is set to "bar" then the
     *                                      full field name would end up being "bar[foo]".
     *
     * @return  boolean  True on success.
     *
     * @see     JFormField::setup()
     */
    public function setup(SimpleXMLElement $element, $value, $group = null)
    {
        $result = parent::setup($element, $value, $group);

        if ($result === true) {
            $this->mediatype = isset($this->element['mediatype']) ? (string) $this->element['mediatype'] : 'images';

            // Joomla 4 custom layout
            if (isset($this->types)) {
                $this->layout = 'joomla.form.field.mediacustom';
        	}
        }

        return $result;
    }

    /**
     * Get the data that is going to be passed to the layout
     *
     * @return  array
     */
    public function getLayoutData()
    {
        require_once JPATH_ADMINISTRATOR . '/components/com_jce/helpers/browser.php';

        $config = array(
            'element' => $this->id,
            'mediatype' => strtolower($this->mediatype),
            'converted' => (int) $this->element['converted'] ? true : false
        );

        if (isset($this->element['plugin'])) {
            $config['plugin'] = (string) $this->element['plugin'];
        }

        $options = WFBrowserHelper::getMediaFieldOptions($config);

        $this->link = $options['url'];

        // Get the basic field data
        $data = parent::getLayoutData();

        // not a valid file browser link
        if (!$this->link) {
            return $data;
        }

        $extraData = array(
            'link'      => $this->link,
            'class'     => $this->element['class'] . ' input-medium wf-media-input wf-media-input-active'
        );

        if ($options['upload'] == 1) {
            $extraData['class'] .= ' wf-media-input-upload';
        }

        return array_merge($data, $extraData);
    }
}