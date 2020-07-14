<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Form\Field;

use FOF30\Form\FieldInterface;
use JText;

defined('_JEXEC') or die;

\JFormHelper::loadFieldClass('text');

/**
 * Form Field class for the FOF framework
 * Supports a title field with an optional slug display below it.
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class Title extends Text implements FieldInterface
{
	/**
	 * Get the rendering of this field type for a repeatable (grid) display,
	 * e.g. in a view listing many item (typically a "browse" task)
	 *
	 * @since 2.0
	 *
	 * @return  string  The field HTML
	 */
	public function getRepeatable()
	{
		// Initialise
		$slug_field     = isset($this->element['slug_field']) ? (string)$this->element['slug_field'] :
			$this->item->getFieldAlias('slug');
		$slug_format    = $this->element['slug_format'] ? (string)$this->element['slug_format'] : '(%s)';
		$slug_class     = $this->element['slug_class'] ? (string)$this->element['slug_class'] : 'small';
		$slug_separator = isset($this->element['slug_separator']) ? (string)$this->element['slug_separator'] : '<br />';

		// Get the regular display
		$html = parent::getRepeatable();

		$slug = $this->item->$slug_field;

		$html .= $slug_separator . '<span class="' . $slug_class . '">';
		$html .= JText::sprintf($slug_format, $slug);
		$html .= '</span>';

		return $html;
	}
}
