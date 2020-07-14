<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Form\Header;

use JText;

defined('_JEXEC') or die;

/**
 * Generic field header, with text input (search) filter
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class Filterable extends Searchable
{
	/**
	 * Get the filter field
	 *
	 * @return  string  The HTML
	 */
	protected function getFilter()
	{
		$valide = array('yes', 'true', '1');

		// Initialize some field(s) attributes.
		$size        = $this->element['size'] ? ' size="' . (int) $this->element['size'] . '"' : '';
		$maxLength   = $this->element['maxlength'] ? ' maxlength="' . (int) $this->element['maxlength'] . '"' : '';
		$filterclass = $this->element['filterclass'] ? ' class="' . (string) $this->element['filterclass'] . '"' : '';
		$placeholder = $this->element['placeholder'] ? $this->element['placeholder'] : $this->getLabel();
		$name        = $this->element['searchfieldname'] ? $this->element['searchfieldname'] : $this->name;
		$placeholder = ' placeholder="' . JText::_($placeholder) . '"';

		$single      = in_array($this->element['single'], $valide) ? true : false;
		$showMethod  = in_array($this->element['showmethod'], $valide) ? true : false;
		$method      = $this->element['method'] ? $this->element['method'] : 'between';
		$fromName    = $this->element['fromname'] ? $this->element['fromname'] : 'from';
		$toName      = $this->element['toname'] ? $this->element['toname'] : 'to';

		$values      = $this->form->getModel()->getState($name);
		$fromValue   = $values[$fromName];
		$toValue     = $values[$toName];

		// Initialize JavaScript field attributes.
		if ($this->element['onchange'])
		{
			$onchange = ' onchange="' . (string) $this->element['onchange'] . '"';
		}
		else
		{
			$onchange = ' onchange="document.adminForm.submit();"';
		}

		if ($showMethod)
		{
			$html  = '<input type="text" name="' . $name . '[method]" value="'. $method . '" />';
		} else
		{
			$html  = '<input type="hidden" name="' . $name . '[method]" value="'. $method . '" />';
		}

		$html .= '<input type="text" name="' . $name . '[from]" id="' . $this->id . '_' . $fromName . '"' . ' value="'
				. htmlspecialchars($fromValue, ENT_COMPAT, 'UTF-8') . '"' . $filterclass . $size . $placeholder . $onchange . $maxLength . '/>';

		if (!$single)
		{
			$html .= '<input type="text" name="' . $name . '[to]" id="' . $this->id . '_' . $toName . '"' . ' value="'
				. htmlspecialchars($toValue, ENT_COMPAT, 'UTF-8') . '"' . $filterclass . $size . $placeholder . $onchange . $maxLength . '/>';
		}

		return $html;
	}
}
