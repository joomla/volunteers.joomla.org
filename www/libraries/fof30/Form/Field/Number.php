<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Form\Field;

use FOF30\Form\FieldInterface;
use FOF30\Form\Form;
use FOF30\Model\DataModel;

defined('_JEXEC') or die;

\JFormHelper::loadFieldClass('number');

/**
 * Form Field class for the FOF framework
 * Supports a numeric field and currency symbols.
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class Number extends \JFormFieldNumber implements FieldInterface
{
	/**
	 * @var  string  Static field output
	 */
	protected $static;

	/**
	 * @var  string  Repeatable field output
	 */
	protected $repeatable;

	/**
	 * The Form object of the form attached to the form field.
	 *
	 * @var    Form
	 */
	protected $form;

	/**
	 * A monotonically increasing number, denoting the row number in a repeatable view
	 *
	 * @var  int
	 */
	public $rowid;

	/**
	 * The item being rendered in a repeatable form field
	 *
	 * @var  DataModel
	 */
	public $item;

	/**
	 * Method to get certain otherwise inaccessible properties from the form field object.
	 *
	 * @param   string  $name  The property name for which to the the value.
	 *
	 * @return  mixed  The property value or null.
	 *
	 * @since   2.0
	 */
	public function __get($name)
	{
		switch ($name)
		{
			case 'static':
				if (empty($this->static))
				{
					$this->static = $this->getStatic();
				}

				return $this->static;
				break;

			case 'repeatable':
				if (empty($this->repeatable))
				{
					$this->repeatable = $this->getRepeatable();
				}

				return $this->repeatable;
				break;

			default:
				return parent::__get($name);
		}
	}

	/**
	 * Get the rendering of this field type for static display, e.g. in a single
	 * item view (typically a "read" task).
	 *
	 * @since 2.0
	 *
	 * @return  string  The field HTML
	 */
	public function getStatic()
	{
		return $this->getRepeatable();
	}

	/**
	 * Print out the number as requested by the attributes
	 */
	public function getRepeatable()
	{
		$currencyPos = $this->getAttribute('currency_position', false);
		$currencySymbol = $this->getAttribute('currency_symbol', false);

		// Initialise
		$class             = $this->id;

		// Get field parameters
		if ($this->element['class'])
		{
			$class = (string) $this->element['class'];
		}

		// Start the HTML output
		$html = '<span class="' . $class . '">';

		// Prepend currency?
		if ($currencyPos == 'before' && $currencySymbol)
		{
			$html .= $currencySymbol;
		}

		$number = $this->value;

		// Should we format the number too?
		$formatNumber = false;

		if (isset($this->element['format_number']))
		{
			$formatNumberValue = (string)$this->element['format_number'];
			$formatNumber = in_array(strtolower($formatNumberValue), array('yes', 'true', 'on', 1));
		}

		// Format the number correctly
		if ($formatNumber)
		{
			$numDecimals 	= $this->getAttribute('decimals', 2);
			$minNumDecimals = $this->getAttribute('min_decimals', 2);
			$decimalsSep 	= $this->getAttribute('decimals_separator', '.');
			$thousandSep 	= $this->getAttribute('thousand_separator', ',');

			// Format the number
			$number = number_format((float)$this->value, $numDecimals, $decimalsSep, $thousandSep);
		}

		// Put it all together
		$html .= $number;

		// Append currency?
		if ($currencyPos == 'after' && $currencySymbol)
		{
			$html .= $currencySymbol;
		}

		// End the HTML output
		$html .= '</span>';

		return $html;
	}
}
