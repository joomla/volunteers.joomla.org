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
use FOF30\Utils\StringHelper;
use JHtml;
use Joomla\Utilities\ArrayHelper;
use JText;

defined('_JEXEC') or die;

\JFormHelper::loadFieldClass('list');

/**
 * Form Field class for FOF
 * Supports a generic list of options.
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class GenericList extends \JFormFieldList implements FieldInterface
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
		if (isset($this->element['legacy']))
		{
			return $this->getInput();
		}

		$class = $this->class ? ' class="' . $this->class . '"' : '';

		return '<span id="' . $this->id . '" ' . $class . '>' .
			htmlspecialchars(self::getOptionName($this->getOptions(), $this->value), ENT_COMPAT, 'UTF-8') .
			'</span>';
	}

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
		if (isset($this->element['legacy']))
		{
			return $this->getInput();
		}

		$class = $this->class ? $this->class : '';

		$link_url = $this->element['url'];

		if ($link_url && ($this->item instanceof DataModel))
		{
			$link_url = $this->parseFieldTags($link_url);
		}
		else
		{
			$link_url = false;
		}

		$html = '<span class="' . $this->id . ' ' . $class . '">';

		if ($link_url)
		{
			$html .= '<a href="' . $link_url . '">';
		}

		$html .= htmlspecialchars(self::getOptionName($this->getOptions(), $this->value), ENT_COMPAT, 'UTF-8');

		if ($link_url)
		{
			$html .= '</a>';
		}

		$html .= '</span>';

		return $html;
	}

	/**
	 * Gets the active option's label given an array of JHtml options
	 *
	 * @param   array   $data           The JHtml options to parse
	 * @param   mixed   $selected       The currently selected value
	 * @param   string  $optKey         Key name
	 * @param   string  $optText        Value name
	 * @param   bool    $selectFirst    Should I automatically select the first option?
	 *
	 * @return  mixed   The label of the currently selected option
	 */
	public static function getOptionName($data, $selected = null, $optKey = 'value', $optText = 'text', $selectFirst = true)
	{
		$ret = null;

		foreach ($data as $elementKey => &$element)
		{
			if (is_array($element))
			{
				$key = $optKey === null ? $elementKey : $element[$optKey];
				$text = $element[$optText];
			}
			elseif (is_object($element))
			{
				$key = $optKey === null ? $elementKey : $element->$optKey;
				$text = $element->$optText;
			}
			else
			{
				// This is a simple associative array
				$key = $elementKey;
				$text = $element;
			}

			if (is_null($ret) && $selectFirst)
			{
				$ret = $text;
			}
			elseif ($selected == $key)
			{
				$ret = $text;
			}
		}

		return $ret;
	}

	/**
	 * Method to get the field options.
	 *
	 * Ordering is disabled by default. You can enable ordering by setting the
	 * 'order' element in your form field. The other order values are optional.
	 *
	 * - order					What to order.			Possible values: 'name' or 'value' (default = false)
	 * - order_dir				Order direction.		Possible values: 'asc' = Ascending or 'desc' = Descending (default = 'asc')
	 * - order_case_sensitive	Order case sensitive.	Possible values: 'true' or 'false' (default = false)
	 *
	 * @return  array  The field option objects.
	 *
	 * @since	Ordering is available since FOF 2.1.b2.
	 */
	protected function getOptions()
	{
		// Ordering is disabled by default for backward compatibility
		$order = false;

		// Set default order direction
		$order_dir = 'asc';

		// Set default value for case sensitive sorting
		$order_case_sensitive = false;

		if ($this->element['order'] && $this->element['order'] !== 'false')
		{
			$order = $this->element['order'];
		}

		if ($this->element['order_dir'])
		{
			$order_dir = $this->element['order_dir'];
		}

		if ($this->element['order_case_sensitive'])
		{
			// Override default setting when the form element value is 'true'
			if ($this->element['order_case_sensitive'] == 'true')
			{
				$order_case_sensitive = true;
			}
		}

		// Create a $sortOptions array in order to apply sorting
		$i = 0;
		$sortOptions = array();

		foreach ($this->element->children() as $option)
		{
			$name = JText::alt(trim((string) $option), preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname));

			$sortOptions[$i] = new \stdClass;
			$sortOptions[$i]->option = $option;
			$sortOptions[$i]->value = $option['value'];
			$sortOptions[$i]->name = $name;
			$i++;
		}

		// Only order if it's set
		if ($order)
		{
			jimport('joomla.utilities.arrayhelper');

			if (class_exists('JArrayHelper'))
			{
				\JArrayHelper::sortObjects($sortOptions, $order, $order_dir == 'asc' ? 1 : -1, $order_case_sensitive, false);
			}
			else
			{
				ArrayHelper::sortObjects($sortOptions, $order, $order_dir == 'asc' ? 1 : -1, $order_case_sensitive, false);
			}
		}

		// Initialise the options
		$options = array();

		// Get the field $options
		foreach ($sortOptions as $sortOption)
		{
			/** @var \SimpleXMLElement $option */
			$option = $sortOption->option;
			$name = $sortOption->name;

			// Only add <option /> elements.
			if ($option->getName() != 'option')
			{
				continue;
			}

			$tmp = JHtml::_('select.option', (string) $option['value'], $name, 'value', 'text', ((string) $option['disabled'] == 'true'));

			// Set some option attributes.
			$tmp->class = (string) $option['class'];

			// Set some JavaScript option attributes.
			$tmp->onclick = (string) $option['onclick'];

			// Add the option object to the result set.
			$options[] = $tmp;
		}

		// Do we have a class and method source for our options?
		$source_file      = empty($this->element['source_file']) ? '' : (string) $this->element['source_file'];
		$source_class     = empty($this->element['source_class']) ? '' : (string) $this->element['source_class'];
		$source_method    = empty($this->element['source_method']) ? '' : (string) $this->element['source_method'];
		$source_key       = empty($this->element['source_key']) ? '*' : (string) $this->element['source_key'];
		$source_value     = empty($this->element['source_value']) ? '*' : (string) $this->element['source_value'];
		$source_translate = is_null($this->element['source_translate']) ? 'true' : (string) $this->element['source_translate'];
		$source_translate = StringHelper::toBool($source_translate) ? true : false;
		$source_format    = empty($this->element['source_format']) ? '' : (string) $this->element['source_format'];

		if ($source_class && $source_method)
		{
			// Maybe we have to load a file?
			if (!empty($source_file))
			{
				$source_file = $this->form->getContainer()->template->parsePath($source_file, true);

				if ($this->form->getContainer()->filesystem->fileExists($source_file))
				{
					include_once $source_file;
				}
			}

			// Make sure the class exists
			if (class_exists($source_class, true))
			{
				// ...and so does the option
				if (in_array($source_method, get_class_methods($source_class)))
				{
					// Get the data from the class
					if ($source_format == 'optionsobject')
					{
						$options = array_merge($options, $source_class::$source_method());
					}
					else
					{
						// Get the data from the class
						$source_data = $source_class::$source_method();

						// Loop through the data and prime the $options array
						foreach ($source_data as $k => $v)
						{
							$key = (empty($source_key) || ($source_key == '*')) ? $k : @$v[$source_key];
							$value = (empty($source_value) || ($source_value == '*')) ? $v : @$v[$source_value];

							if ($source_translate)
							{
								$value = JText::_($value);
							}

							$options[] = JHtml::_('select.option', $key, $value, 'value', 'text');
						}
					}
				}
			}
		}

		reset($options);

		return $options;
	}

	/**
	 * Replace string with tags that reference fields
	 *
	 * @param   string  $text  Text to process
	 *
	 * @return  string         Text with tags replace
	 */
    protected function parseFieldTags($text)
    {
        $ret = $text;

        // Replace [ITEM:ID] in the URL with the item's key value (usually:
        // the auto-incrementing numeric ID)
        if (is_null($this->item))
        {
            $this->item = $this->form->getModel();
        }

        $replace  = $this->item->getId();
        $ret = str_replace('[ITEM:ID]', $replace, $ret);

        // Replace the [ITEMID] in the URL with the current Itemid parameter
        $ret = str_replace('[ITEMID]', $this->form->getContainer()->input->getInt('Itemid', 0), $ret);

        // Replace the [TOKEN] in the URL with the Joomla! form token
        $ret = str_replace('[TOKEN]', \JFactory::getSession()->getFormToken(), $ret);

        // Replace other field variables in the URL
        $data = $this->item->getData();

        foreach ($data as $field => $value)
        {
            // Skip non-processable values
            if(is_array($value) || is_object($value))
            {
                continue;
            }

            $search = '[ITEM:' . strtoupper($field) . ']';
            $ret    = str_replace($search, $value, $ret);
        }

        return $ret;
    }
}
