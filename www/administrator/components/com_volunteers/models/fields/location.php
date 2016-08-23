<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

JHtml::_('jquery.framework');
JHtml::script('//maps.googleapis.com/maps/api/js');
JHtml::script('com_volunteers/jquery-gmaps-latlon-picker.js', false, true);

/**
 * Volunteers Field class.
 */
class JFormFieldLocation extends JFormFieldText
{
	/**
	 * The form field type.
	 *
	 * @var        string
	 */
	protected $type = 'Location';

	/**
	 * Method to get the field options.
	 *
	 * @return  string  The field input markup.
	 */
	public function getInput()
	{
		$data = $this->form->getData();

		$html[] = '<div class="gllpLatlonPicker" id="location">';
		$html[] = '<div class="gllpMap" style="width:100%;height:200px;"></div>';
		$html[] = '<input type="hidden" class="gllpLatitude" name="jform[latitude]" id="latitude" value="' . $data->get('latitude') . '"/>';
		$html[] = '<input type="hidden" class="gllpLongitude" name="jform[longitude]" id="longitude" value="' . $data->get('longitude') . '"/>';
		$html[] = '<input type="hidden" class="gllpZoom" value="13"/>';
		$html[] = '<input type="hidden" class="gllpSearchField">';
		$html[] = '<input type="button" class="gllpSearchButton" style="display: none">';
		$html[] = '</div>';

		return implode($html);
	}
}
