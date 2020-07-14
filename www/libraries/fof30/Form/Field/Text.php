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
use JText;

defined('_JEXEC') or die;

\JFormHelper::loadFieldClass('text');

/**
 * Form Field class for the FOF framework
 * Supports a one line text field.
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class Text extends \JFormFieldText implements FieldInterface
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
		if (is_array($this->value))
		{
			$this->value = empty($this->value) ? '' : print_r($this->value, true);
		}

		if (isset($this->element['legacy']))
		{
			return $this->getInput();
		}

		$class = $this->class ? ' class="' . $this->class . '"' : '';

		$empty_replacement = $this->element['empty_replacement'] ? (string) $this->element['empty_replacement'] : '';

		if (!empty($empty_replacement) && empty($this->value))
		{
			$this->value = JText::_($empty_replacement);
		}

		return '<span id="' . $this->id . '" ' . $class . '>' .
			htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') .
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
		if (is_array($this->value))
		{
			$this->value = print_r($this->value, true);
		}

		if (isset($this->element['legacy']))
		{
			return $this->getInput();
		}

		// Should I support checked-out elements?
		$checkoutSupport = false;

		if (isset($this->element['checkout']))
		{
			$checkoutSupportValue = (string)$this->element['checkout'];
			$checkoutSupport = in_array(strtolower($checkoutSupportValue), array('yes', 'true', 'on', 1));
		}

		// Initialise
		$class					= $this->class ? $this->class : $this->id;
		$format_string			= $this->element['format'] ? (string) $this->element['format'] : '';
		$format_if_not_empty	= in_array((string) $this->element['format_if_not_empty'], array('true', '1', 'on', 'yes'));
		$parse_value			= in_array((string) $this->element['parse_value'], array('true', '1', 'on', 'yes'));
		$link_url				= $this->element['url'] ? (string) $this->element['url'] : '';
		$empty_replacement		= $this->element['empty_replacement'] ? (string) $this->element['empty_replacement'] : '';
		$format_source_file     = empty($this->element['format_source_file']) ? '' : (string) $this->element['format_source_file'];
		$format_source_class    = empty($this->element['format_source_class']) ? '' : (string) $this->element['format_source_class'];
		$format_source_method   = empty($this->element['format_source_method']) ? '' : (string) $this->element['format_source_method'];

		if ($link_url && ($this->item instanceof DataModel))
		{
			$link_url = $this->parseFieldTags($link_url);
		}
		else
		{
			$link_url = false;
		}

		// Get the (optionally formatted) value
		$value = $this->value;

		if (!empty($empty_replacement) && empty($this->value))
		{
			$value = JText::_($empty_replacement);
		}

		if ($parse_value)
		{
			$value = $this->parseFieldTags($value);
		}

		if (!empty($format_string) && (!$format_if_not_empty || ($format_if_not_empty && !empty($this->value))))
		{
			$format_string = $this->parseFieldTags($format_string);
			$value = sprintf($format_string, $value);
		}
		elseif ($format_source_class && $format_source_method)
		{
			// Maybe we have to load a file?
			if (!empty($format_source_file))
			{
				$format_source_file = $this->form->getContainer()->template->parsePath($format_source_file, true);

				if ($this->form->getContainer()->filesystem->fileExists($format_source_file))
				{
					include_once $format_source_file;
				}
			}

			// Make sure the class and method exist
			if (class_exists($format_source_class, true) && in_array($format_source_method, get_class_methods($format_source_class)))
			{
				$value = $format_source_class::$format_source_method($value);
				$value = $this->parseFieldTags($value);
			}
			else
			{
				$value = htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
			}
		}
		else
		{
			$value = htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
		}

		// Create the HTML
		$html = '<span class="' . $class . '">';

		$userId = $this->form->getContainer()->platform->getUser()->id;

		if ($checkoutSupport && $this->item->isLocked($userId))
		{
			$key_field = $this->item->getKeyName();
			$key_id    = $this->item->$key_field;

			$lockedBy = '';
			$lockedOn = '';

			if ($this->item->hasField('locked_by'))
			{
				$lockedUser = $this->form->getContainer()->platform->getUser($this->item->getFieldValue('locked_by'));
				$lockedBy = $lockedUser->name . ' (' . $lockedUser->username . ')';
			}

			if ($this->item->hasField('locked_on'))
			{
				$lockedOn = $this->item->getFieldValue('locked_on');
			}

			$html .= \JHtml::_('jgrid.checkedout', $key_id, $lockedBy, $lockedOn, '', true);
		}

		if ($link_url)
		{
			$html .= '<a href="' . $link_url . '">';
		}

		$html .= $value;

		if ($link_url)
		{
			$html .= '</a>';
		}

		$html .= '</span>';

		return $html;
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
