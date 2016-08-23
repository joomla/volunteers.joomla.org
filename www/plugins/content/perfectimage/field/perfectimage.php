<?php
/*
 * @package		Perfect Image Form Field
 * @copyright	Copyright (c) 2016 Perfect Web Team / perfectwebteam.nl
 * @license		GNU General Public License version 3 or later
 */

// No direct access.
defined('_JEXEC') or die;

JHtml::_('jquery.framework');
JHtml::script('plg_content_perfectimage/cropper.min.js', false, true);
JHtml::script('plg_content_perfectimage/perfectimage.js', false, true);
JHtml::stylesheet('plg_content_perfectimage/cropper.min.css', false, true, false);
JHtml::stylesheet('plg_content_perfectimage/perfectimage.css', false, true, false);

/**
 * Sample list form field
 */
class JFormFieldPerfectimage extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var  string
	 */
	protected $type = 'Perfectimage';

	protected function getInput()
	{
		// Setup variables for display.
		$html = array();

		// Container
		$html[] = '<div class="perfect-image" id="' . $this->id . '">';

		// Image
		$html[] = '<div class="perfect-image-preview">';

		if ($this->value)
		{
			$html[] = '<img src="' . JURI::Root() . '/images/volunteers/' . $this->value . '"/>';
		}

		$html[] = '</div>';

		// Select button
		$html[] = '<a href="#' . $this->id . '_modal" role="button" class="btn btn-primary perfect-image-select" data-toggle="modal" title="' . JText::_('COM_VOLUNTEERS_IMAGECROPPER_SELECT') . '">'
			. '<span class="icon-list icon-white"></span> '
			. JText::_('COM_VOLUNTEERS_IMAGECROPPER_SELECT') . '</a>';

		// Reset button
		$html[] = '<button class="btn perfect-image-clear' . ($this->value ? '' : ' hidden') . '"><span class="icon-remove"></span> ' . JText::_('COM_VOLUNTEERS_IMAGECROPPER_CLEAR') . '</button>';

		// The class='required' for client side validation
		$class = '';

		if ($this->required)
		{
			$class = ' class="required modal-value"';
		}

		$html[] = '<input type="hidden" id="' . $this->id . '"' . $class . ' name="' . $this->name . '" value="' . $this->value . '" />';

		// Modal window
		$data = array(
			'id'               => (string) $this->id,
			'ratio'            => (string) $this->element['ratio'],
			'width'            => (string) $this->element['width'],
			'max_size'         => (string) $this->file_upload_max_size(),
			'max_size_message' => JText::_('COM_VOLUNTEERS_IMAGECROPPER_TOO_BIG'),
			'max_dimension'    => 10000
		);

		$layout = new JLayoutFile('cropper', JPATH_ROOT . '/plugins/content/perfectimage/layouts');

		$html[] = JHtml::_('bootstrap.renderModal',
			$this->id . '_modal',
			array(
				'title'  => JText::_('COM_VOLUNTEERS_IMAGECROPPER_TITLE'),
				'footer' =>
					'<button class="btn" data-dismiss="modal" aria-hidden="true">' . JText::_("JLIB_HTML_BEHAVIOR_CLOSE") . '</button>' .
					'<button class="btn btn-success perfect-image-save" data-dismiss="modal" aria-hidden="true">' . JText::_("COM_VOLUNTEERS_IMAGECROPPER_INSERT") . '</button>'
			),
			$layout->render($data)
		);

		// Container end
		$html[] = '</div>';

		return implode("\n", $html);
	}

	// thnx to Drupal
	function file_upload_max_size()
	{
		static $max_size = -1;

		if ($max_size < 0)
		{
			// Start with post_max_size.
			$max_size = $this->parse_size(ini_get('post_max_size'));

			// If upload_max_size is less, then reduce. Except if upload_max_size is
			// zero, which indicates no limit.
			$upload_max = $this->parse_size(ini_get('upload_max_filesize'));
			if ($upload_max > 0 && $upload_max < $max_size)
			{
				$max_size = $upload_max;
			}
		}

		return $max_size;
	}

	function parse_size($size)
	{
		$unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
		$size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
		if ($unit)
		{
			// Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
			return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
		}
		else
		{
			return round($size);
		}
	}

}