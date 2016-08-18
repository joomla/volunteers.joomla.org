<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * Volunteers Field class.
 */
class JFormFieldTextprepend extends JFormFieldText
{
	/**
	 * The form field type.
	 *
	 * @var        string
	 */
	protected $type = 'Textprepend';

	/**
	 * Method to get the field options.
	 *
	 * @return  string  The field input markup.
	 */
	public function getInput()
	{
		$html[] = '<div class="input-prepend"><span class="add-on">' . JText::_($this->element['prepend']) . '</span>';
		$html[] = parent::getInput();
		$html[] = '</div>';

		return implode($html);
	}
}
