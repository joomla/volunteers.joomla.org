<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Administrator\Field;

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\TextField;
use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Volunteers Field class.
 * @since 4.0.0
 */
class LocationField extends TextField
{
    /**
     * The form field type.
     *
     * @var        string
     * @since 4.0.0
     */
    protected $type = 'Location';

    /**
     * Method to get the field options.
     *
     * @return  string  The field input markup.
     * @since 4.0.0
     * @throws Exception
     */
    public function getInput()
    {
        Factory::getApplication()->getDocument()->getWebAssetManager()->usePreset('com_volunteers.location-picker');
        Text::script('COM_VOLUNTEERS_LOCATION_NOT_FOUND');

        $data = $this->form->getData();
        $zoom = ($data->get('latitude') && $data->get('longitude')) ? 13 : 1;

        $html[] = '<div class="gllpLatlonPicker" id="location">';
        $html[] = '<div class="gllpMap" style="width:100%;height:200px;"></div>';
        $html[] = '<input type="hidden" class="gllpLatitude" name="jform[latitude]" id="latitude" value="' . $data->get('latitude') . '"/>';
        $html[] = '<input type="hidden" class="gllpLongitude" name="jform[longitude]" id="longitude" value="' . $data->get('longitude') . '"/>';
        $html[] = '<input type="hidden" class="gllpZoom" value="' . $zoom . '"/>';
        $html[] = '<input type="hidden" class="gllpSearchField">';
        $html[] = '<input type="button" class="gllpSearchButton" style="display: none">';
        $html[] = '</div>';

        return implode('', $html);
    }
}
