<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use FOF40\Utils\ArrayHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/**
 * Custom JHtml (HTMLHelper) class. Offers selects compatible with Akeeba Frontend Framework (FEF)
 *
 * Call these methods as HTMLHelper::_('FEFHelp.select.methodName', $parameter1, $parameter2, ...)
 *
 * @noinspection PhpIllegalPsrClassPathInspection
 */
abstract class FEFHelpSelect
{
	/**
	 * Default values for options. Organized by option group.
	 *
	 * @var     array
	 */
	protected static $optionDefaults = [
		'option' => [
			'option.attr'         => null,
			'option.disable'      => 'disable',
			'option.id'           => null,
			'option.key'          => 'value',
			'option.key.toHtml'   => true,
			'option.label'        => null,
			'option.label.toHtml' => true,
			'option.text'         => 'text',
			'option.text.toHtml'  => true,
			'option.class'        => 'class',
			'option.onclick'      => 'onclick',
		],
	];

	/**
	 * Generates a yes/no radio list.
	 *
	 * @param   string  $name      The value of the HTML name attribute
	 * @param   array   $attribs   Additional HTML attributes for the `<select>` tag
	 * @param   mixed   $selected  The key that is selected
	 * @param   string  $yes       Language key for Yes
	 * @param   string  $no        Language key for no
	 * @param   mixed   $id        The id for the field or false for no id
	 *
	 * @return  string  HTML for the radio list
	 *
	 * @see     JFormFieldRadio
	 */
	public static function booleanlist($name, $attribs = [], $selected = null, $yes = 'JYES', $no = 'JNO', $id = false)
	{
		$options = [
			\Joomla\CMS\HTML\HTMLHelper::_('FEFHelp.select.option', '0', \Joomla\CMS\Language\Text::_($no)),
			\Joomla\CMS\HTML\HTMLHelper::_('FEFHelp.select.option', '1', \Joomla\CMS\Language\Text::_($yes)),
		];
		$attribs = array_merge(['forSelect' => 1], $attribs);

		return \Joomla\CMS\HTML\HTMLHelper::_('FEFHelp.select.radiolist', $options, $name, $attribs, 'value', 'text', (int) $selected, $id);
	}

	/**
	 * Generates a searchable HTML selection list (Chosen on J3, Choices.js on J4).
	 *
	 * @param   array    $data       An array of objects, arrays, or scalars.
	 * @param   string   $name       The value of the HTML name attribute.
	 * @param   mixed    $attribs    Additional HTML attributes for the `<select>` tag. This
	 *                               can be an array of attributes, or an array of options. Treated as options
	 *                               if it is the last argument passed. Valid options are:
	 *                               Format options, see {@see JHtml::$formatOptions}.
	 *                               Selection options, see {@see JHtmlSelect::options()}.
	 *                               list.attr, string|array: Additional attributes for the select
	 *                               element.
	 *                               id, string: Value to use as the select element id attribute.
	 *                               Defaults to the same as the name.
	 *                               list.select, string|array: Identifies one or more option elements
	 *                               to be selected, based on the option key values.
	 * @param   string   $optKey     The name of the object variable for the option value. If
	 *                               set to null, the index of the value array is used.
	 * @param   string   $optText    The name of the object variable for the option text.
	 * @param   mixed    $selected   The key that is selected (accepts an array or a string).
	 * @param   mixed    $idtag      Value of the field id or null by default
	 * @param   boolean  $translate  True to translate
	 *
	 * @return  string  HTML for the select list.
	 *
	 * @since   3.7.2
	 */
	public static function smartlist($data, $name, $attribs = null, $optKey = 'value', $optText = 'text', $selected = null, $idtag = false, $translate = false)
	{
		$innerList = self::genericlist($data, $name, $attribs, $optKey, $optText, $selected, $idtag, $translate);

		// Joomla 3: Use Chosen
		if (version_compare(JVERSION, '3.999.999', 'le'))
		{
			HTMLHelper::_('formbehavior.chosen');

			return $innerList;
		}

		// Joomla 4: Use the joomla-field-fancy-select using choices.js
		try
		{
			\Joomla\CMS\Factory::getApplication()->getDocument()->getWebAssetManager()
				->usePreset('choicesjs')
				->useScript('webcomponent.field-fancy-select');
		}
		catch (Exception $e)
		{
			return $innerList;
		}

		$j4Attr = array_filter([
			'class'       => $attribs['class'] ?? null,
			'placeholder' => $attribs['placeholder'] ?? null,
		], function ($x) {
			return !empty($x);
		});

		$dataAttribute = '';

		if (isset($attribs['dataAttribute']))
		{
			$dataAttribute = is_string($attribs['dataAttribute']) ? $attribs['dataAttribute'] : '';
		}

		if ((bool) ($attribs['allowCustom'] ?? false))
		{
			$dataAttribute .= ' allow-custom new-item-prefix="#new#"';
		}

		$remoteSearchUrl = $attribs['remoteSearchURL'] ?? null;
		$remoteSearch    = ((bool) ($attribs['remoteSearch'] ?? false)) && !empty($remoteSearchUrl);
		$termKey         = $attribs['termKey'] ?? 'like';
		$minTermLength   = $attribs['minTermLength'] ?? 3;

		if ($remoteSearch)
		{
			$dataAttribute             .= ' remote-search';
			$j4Attr['url']             = $remoteSearchUrl;
			$j4Attr['term-key']        = $termKey;
			$j4Attr['min-term-length'] = $minTermLength;
		}

		if (isset($attribs['required']))
		{
			$j4Attr['class'] = ($j4Attr['class'] ?? '') . ' required';
			$dataAttribute .= ' required';
		}

		if (isset($attribs['readonly']))
		{
			return $innerList;
		}

		return sprintf("<joomla-field-fancy-select %s %s>%s</joomla-field-fancy-select>", ArrayHelper::toString($j4Attr), $dataAttribute, $innerList);
	}

	/**
	 * Generates an HTML selection list.
	 *
	 * @param   array    $data       An array of objects, arrays, or scalars.
	 * @param   string   $name       The value of the HTML name attribute.
	 * @param   mixed    $attribs    Additional HTML attributes for the `<select>` tag. This
	 *                               can be an array of attributes, or an array of options. Treated as options
	 *                               if it is the last argument passed. Valid options are:
	 *                               Format options, see {@see HTMLHelper::$formatOptions}.
	 *                               Selection options, see {@see HTMLHelper::options()}.
	 *                               list.attr, string|array: Additional attributes for the select
	 *                               element.
	 *                               id, string: Value to use as the select element id attribute.
	 *                               Defaults to the same as the name.
	 *                               list.select, string|array: Identifies one or more option elements
	 *                               to be selected, based on the option key values.
	 * @param   string   $optKey     The name of the object variable for the option value. If
	 *                               set to null, the index of the value array is used.
	 * @param   string   $optText    The name of the object variable for the option text.
	 * @param   mixed    $selected   The key that is selected (accepts an array or a string).
	 * @param   mixed    $idtag      Value of the field id or null by default
	 * @param   boolean  $translate  True to translate
	 *
	 * @return  string  HTML for the select list.
	 *
	 */
	public static function genericlist(array $data, string $name, ?array $attribs = null, string $optKey = 'value',
	                                   string $optText = 'text', $selected = null, $idtag = false,
	                                   bool $translate = false): string
	{
		// Set default options
		$options = array_merge(HTMLHelper::$formatOptions, ['format.depth' => 0, 'id' => false]);

		if (is_array($attribs) && func_num_args() === 3)
		{
			// Assume we have an options array
			$options = array_merge($options, $attribs);
		}
		else
		{
			// Get options from the parameters
			$options['id']             = $idtag;
			$options['list.attr']      = $attribs;
			$options['list.translate'] = $translate;
			$options['option.key']     = $optKey;
			$options['option.text']    = $optText;
			$options['list.select']    = $selected;
		}

		$attribs = '';

		if (isset($options['list.attr']))
		{
			if (is_array($options['list.attr']))
			{
				$attribs = ArrayHelper::toString($options['list.attr']);
			}
			else
			{
				$attribs = $options['list.attr'];
			}

			if ($attribs !== '')
			{
				$attribs = ' ' . $attribs;
			}
		}

		$id = $options['id'] !== false ? $options['id'] : $name;
		$id = str_replace(['[', ']', ' '], '', $id);

		$baseIndent = str_repeat($options['format.indent'], $options['format.depth']++);

		return $baseIndent . '<select' . ($id !== '' ? ' id="' . $id . '"' : '') . ' name="' . $name . '"' . $attribs . '>' . $options['format.eol']
			. static::options($data, $options) . $baseIndent . '</select>' . $options['format.eol'];
	}

	/**
	 * Generates a grouped HTML selection list from nested arrays.
	 *
	 * @param   array   $data     An array of groups, each of which is an array of options.
	 * @param   string  $name     The value of the HTML name attribute
	 * @param   array   $options  Options, an array of key/value pairs. Valid options are:
	 *                            Format options, {@see HTMLHelper::$formatOptions}.
	 *                            Selection options. See {@see HTMLHelper::options()}.
	 *                            group.id: The property in each group to use as the group id
	 *                            attribute. Defaults to none.
	 *                            group.label: The property in each group to use as the group
	 *                            label. Defaults to "text". If set to null, the data array index key is
	 *                            used.
	 *                            group.items: The property in each group to use as the array of
	 *                            items in the group. Defaults to "items". If set to null, group.id and
	 *                            group. label are forced to null and the data element is assumed to be a
	 *                            list of selections.
	 *                            id: Value to use as the select element id attribute. Defaults to
	 *                            the same as the name.
	 *                            list.attr: Attributes for the select element. Can be a string or
	 *                            an array of key/value pairs. Defaults to none.
	 *                            list.select: either the value of one selected option or an array
	 *                            of selected options. Default: none.
	 *                            list.translate: Boolean. If set, text and labels are translated via
	 *                            Text::_().
	 *
	 * @return  string  HTML for the select list
	 *
	 * @throws  RuntimeException If a group has contents that cannot be processed.
	 */
	public static function groupedlist(array $data, string $name, array $options = []): string
	{
		// Set default options and overwrite with anything passed in
		$options = array_merge(
			HTMLHelper::$formatOptions,
			[
				'format.depth' => 0, 'group.items' => 'items', 'group.label' => 'text', 'group.label.toHtml' => true,
				'id'           => false,
			],
			$options
		);

		// Apply option rules
		if ($options['group.items'] === null)
		{
			$options['group.label'] = null;
		}

		$attribs = '';

		if (isset($options['list.attr']))
		{
			if (is_array($options['list.attr']))
			{
				$attribs = ArrayHelper::toString($options['list.attr']);
			}
			else
			{
				$attribs = $options['list.attr'];
			}

			if ($attribs !== '')
			{
				$attribs = ' ' . $attribs;
			}
		}

		$id = $options['id'] !== false ? $options['id'] : $name;
		$id = str_replace(['[', ']', ' '], '', $id);

		// Disable groups in the options.
		$options['groups'] = false;

		$baseIndent  = str_repeat($options['format.indent'], $options['format.depth']++);
		$html        = $baseIndent . '<select' . ($id !== '' ? ' id="' . $id . '"' : '') . ' name="' . $name . '"' . $attribs . '>' . $options['format.eol'];
		$groupIndent = str_repeat($options['format.indent'], $options['format.depth']++);

		foreach ($data as $dataKey => $group)
		{
			$label   = $dataKey;
			$id      = '';
			$noGroup = is_int($dataKey);

			if ($options['group.items'] == null)
			{
				// Sub-list is an associative array
				$subList = $group;
			}
			elseif (is_array($group))
			{
				// Sub-list is in an element of an array.
				$subList = $group[$options['group.items']];

				if (isset($group[$options['group.label']]))
				{
					$label   = $group[$options['group.label']];
					$noGroup = false;
				}

				if (isset($options['group.id']) && isset($group[$options['group.id']]))
				{
					$id      = $group[$options['group.id']];
					$noGroup = false;
				}
			}
			elseif (is_object($group))
			{
				// Sub-list is in a property of an object
				$subList = $group->{$options['group.items']};

				if (isset($group->{$options['group.label']}))
				{
					$label   = $group->{$options['group.label']};
					$noGroup = false;
				}

				if (isset($options['group.id']) && isset($group->{$options['group.id']}))
				{
					$id      = $group->{$options['group.id']};
					$noGroup = false;
				}
			}
			else
			{
				throw new RuntimeException('Invalid group contents.', 1);
			}

			if ($noGroup)
			{
				$html .= static::options($subList, $options);
			}
			else
			{
				$html .= $groupIndent . '<optgroup' . (empty($id) ? '' : ' id="' . $id . '"') . ' label="'
					. ($options['group.label.toHtml'] ? htmlspecialchars($label, ENT_COMPAT, 'UTF-8') : $label) . '">' . $options['format.eol']
					. static::options($subList, $options) . $groupIndent . '</optgroup>' . $options['format.eol'];
			}
		}

		return $html . ($baseIndent . '</select>' . $options['format.eol']);
	}

	/**
	 * Generates a selection list of integers.
	 *
	 * @param   integer  $start     The start integer
	 * @param   integer  $end       The end integer
	 * @param   integer  $inc       The increment
	 * @param   string   $name      The value of the HTML name attribute
	 * @param   mixed    $attribs   Additional HTML attributes for the `<select>` tag, an array of
	 *                              attributes, or an array of options. Treated as options if it is the last
	 *                              argument passed.
	 * @param   mixed    $selected  The key that is selected
	 * @param   string   $format    The printf format to be applied to the number
	 *
	 * @return  string   HTML for the select list
	 */
	public static function integerlist(int $start, int $end, int $inc, string $name, ?array $attribs = null,
	                                   $selected = null, string $format = ''): string
	{
		// Set default options
		$options = array_merge(HTMLHelper::$formatOptions, ['format.depth' => 0, 'option.format' => '', 'id' => null]);

		if (is_array($attribs) && func_num_args() === 5)
		{
			// Assume we have an options array
			$options = array_merge($options, $attribs);

			// Extract the format and remove it from downstream options
			$format = $options['option.format'];
			unset($options['option.format']);
		}
		else
		{
			// Get options from the parameters
			$options['list.attr']   = $attribs;
			$options['list.select'] = $selected;
		}

		$start = (int) $start;
		$end   = (int) $end;
		$inc   = (int) $inc;

		$data = [];

		for ($i = $start; $i <= $end; $i += $inc)
		{
			$data[$i] = $format ? sprintf($format, $i) : $i;
		}

		// Tell genericlist() to use array keys
		$options['option.key'] = null;

		return HTMLHelper::_('FEFHelp.select.genericlist', $data, $name, $options);
	}

	/**
	 * Create an object that represents an option in an option list.
	 *
	 * @param   string   $value    The value of the option
	 * @param   string   $text     The text for the option
	 * @param   mixed    $optKey   If a string, the returned object property name for
	 *                             the value. If an array, options. Valid options are:
	 *                             attr: String|array. Additional attributes for this option.
	 *                             Defaults to none.
	 *                             disable: Boolean. If set, this option is disabled.
	 *                             label: String. The value for the option label.
	 *                             option.attr: The property in each option array to use for
	 *                             additional selection attributes. Defaults to none.
	 *                             option.disable: The property that will hold the disabled state.
	 *                             Defaults to "disable".
	 *                             option.key: The property that will hold the selection value.
	 *                             Defaults to "value".
	 *                             option.label: The property in each option array to use as the
	 *                             selection label attribute. If a "label" option is provided, defaults to
	 *                             "label", if no label is given, defaults to null (none).
	 *                             option.text: The property that will hold the the displayed text.
	 *                             Defaults to "text". If set to null, the option array is assumed to be a
	 *                             list of displayable scalars.
	 * @param   string   $optText  The property that will hold the the displayed text. This
	 *                             parameter is ignored if an options array is passed.
	 * @param   boolean  $disable  Not used.
	 *
	 * @return  stdClass
	 */
	public static function option(?string $value, string $text = '', $optKey = 'value', string $optText = 'text',
	                              bool $disable = false)
	{
		$options = [
			'attr'           => null,
			'disable'        => false,
			'option.attr'    => null,
			'option.disable' => 'disable',
			'option.key'     => 'value',
			'option.label'   => null,
			'option.text'    => 'text',
		];

		if (is_array($optKey))
		{
			// Merge in caller's options
			$options = array_merge($options, $optKey);
		}
		else
		{
			// Get options from the parameters
			$options['option.key']  = $optKey;
			$options['option.text'] = $optText;
			$options['disable']     = $disable;
		}

		$obj                            = new stdClass;
		$obj->{$options['option.key']}  = $value;
		$obj->{$options['option.text']} = trim($text) ? $text : $value;

		/*
		 * If a label is provided, save it. If no label is provided and there is
		 * a label name, initialise to an empty string.
		 */
		$hasProperty = $options['option.label'] !== null;

		if (isset($options['label']))
		{
			$labelProperty       = $hasProperty ? $options['option.label'] : 'label';
			$obj->$labelProperty = $options['label'];
		}
		elseif ($hasProperty)
		{
			$obj->{$options['option.label']} = '';
		}

		// Set attributes only if there is a property and a value
		if ($options['attr'] !== null)
		{
			$obj->{$options['option.attr']} = $options['attr'];
		}

		// Set disable only if it has a property and a value
		if ($options['disable'] !== null)
		{
			$obj->{$options['option.disable']} = $options['disable'];
		}

		return $obj;
	}

	/**
	 * Generates the option tags for an HTML select list (with no select tag
	 * surrounding the options).
	 *
	 * @param   array    $arr         An array of objects, arrays, or values.
	 * @param   mixed    $optKey      If a string, this is the name of the object variable for
	 *                                the option value. If null, the index of the array of objects is used. If
	 *                                an array, this is a set of options, as key/value pairs. Valid options are:
	 *                                -Format options, {@see HTMLHelper::$formatOptions}.
	 *                                -groups: Boolean. If set, looks for keys with the value
	 *                                "&lt;optgroup>" and synthesizes groups from them. Deprecated. Defaults
	 *                                true for backwards compatibility.
	 *                                -list.select: either the value of one selected option or an array
	 *                                of selected options. Default: none.
	 *                                -list.translate: Boolean. If set, text and labels are translated via
	 *                                Text::_(). Default is false.
	 *                                -option.id: The property in each option array to use as the
	 *                                selection id attribute. Defaults to none.
	 *                                -option.key: The property in each option array to use as the
	 *                                selection value. Defaults to "value". If set to null, the index of the
	 *                                option array is used.
	 *                                -option.label: The property in each option array to use as the
	 *                                selection label attribute. Defaults to null (none).
	 *                                -option.text: The property in each option array to use as the
	 *                                displayed text. Defaults to "text". If set to null, the option array is
	 *                                assumed to be a list of displayable scalars.
	 *                                -option.attr: The property in each option array to use for
	 *                                additional selection attributes. Defaults to none.
	 *                                -option.disable: The property that will hold the disabled state.
	 *                                Defaults to "disable".
	 *                                -option.key: The property that will hold the selection value.
	 *                                Defaults to "value".
	 *                                -option.text: The property that will hold the the displayed text.
	 *                                Defaults to "text". If set to null, the option array is assumed to be a
	 *                                list of displayable scalars.
	 * @param   string   $optText     The name of the object variable for the option text.
	 * @param   mixed    $selected    The key that is selected (accepts an array or a string)
	 * @param   boolean  $translate   Translate the option values.
	 *
	 * @return  string  HTML for the select list
	 */
	public static function options(array $arr, $optKey = 'value', string $optText = 'text',
	                               ?string $selected = null, bool $translate = false): string
	{
		$options = array_merge(
			HTMLHelper::$formatOptions,
			static::$optionDefaults['option'],
			['format.depth' => 0, 'groups' => true, 'list.select' => null, 'list.translate' => false]
		);

		if (is_array($optKey))
		{
			// Set default options and overwrite with anything passed in
			$options = array_merge($options, $optKey);
		}
		else
		{
			// Get options from the parameters
			$options['option.key']     = $optKey;
			$options['option.text']    = $optText;
			$options['list.select']    = $selected;
			$options['list.translate'] = $translate;
		}

		$html       = '';
		$baseIndent = str_repeat($options['format.indent'], $options['format.depth']);

		foreach ($arr as $elementKey => &$element)
		{
			$attr  = '';
			$extra = '';
			$label = '';
			$id    = '';

			if (is_array($element))
			{
				$key  = $options['option.key'] === null ? $elementKey : $element[$options['option.key']];
				$text = $element[$options['option.text']];

				if (isset($element[$options['option.attr']]))
				{
					$attr = $element[$options['option.attr']];
				}

				if (isset($element[$options['option.id']]))
				{
					$id = $element[$options['option.id']];
				}

				if (isset($element[$options['option.label']]))
				{
					$label = $element[$options['option.label']];
				}

				if (isset($element[$options['option.disable']]) && $element[$options['option.disable']])
				{
					$extra .= ' disabled="disabled"';
				}
			}
			elseif (is_object($element))
			{
				$key  = $options['option.key'] === null ? $elementKey : $element->{$options['option.key']};
				$text = $element->{$options['option.text']};

				if (isset($element->{$options['option.attr']}))
				{
					$attr = $element->{$options['option.attr']};
				}

				if (isset($element->{$options['option.id']}))
				{
					$id = $element->{$options['option.id']};
				}

				if (isset($element->{$options['option.label']}))
				{
					$label = $element->{$options['option.label']};
				}

				if (isset($element->{$options['option.disable']}) && $element->{$options['option.disable']})
				{
					$extra .= ' disabled="disabled"';
				}

				if (isset($element->{$options['option.class']}) && $element->{$options['option.class']})
				{
					$extra .= ' class="' . $element->{$options['option.class']} . '"';
				}

				if (isset($element->{$options['option.onclick']}) && $element->{$options['option.onclick']})
				{
					$extra .= ' onclick="' . $element->{$options['option.onclick']} . '"';
				}
			}
			else
			{
				// This is a simple associative array
				$key  = $elementKey;
				$text = $element;
			}

			/*
			 * The use of options that contain optgroup HTML elements was
			 * somewhat hacked for J1.5. J1.6 introduces the grouplist() method
			 * to handle this better. The old solution is retained through the
			 * "groups" option, which defaults true in J1.6, but should be
			 * deprecated at some point in the future.
			 */

			$key = (string) $key;

			if ($key === '<OPTGROUP>' && $options['groups'])
			{
				$html       .= $baseIndent . '<optgroup label="' . ($options['list.translate'] ? Text::_($text) : $text) . '">' . $options['format.eol'];
				$baseIndent = str_repeat($options['format.indent'], ++$options['format.depth']);
			}
			elseif ($key === '</OPTGROUP>' && $options['groups'])
			{
				$baseIndent = str_repeat($options['format.indent'], --$options['format.depth']);
				$html       .= $baseIndent . '</optgroup>' . $options['format.eol'];
			}
			else
			{
				// If no string after hyphen - take hyphen out
				$splitText = explode(' - ', $text, 2);
				$text      = $splitText[0];

				if (isset($splitText[1]) && $splitText[1] !== '' && !preg_match('/^[\s]+$/', $splitText[1]))
				{
					$text .= ' - ' . $splitText[1];
				}

				if (!empty($label) && $options['list.translate'])
				{
					$label = Text::_($label);
				}

				if ($options['option.label.toHtml'])
				{
					$label = htmlentities($label);
				}

				if (is_array($attr))
				{
					$attr = ArrayHelper::toString($attr);
				}
				else
				{
					$attr = trim($attr);
				}

				$extra = ($id ? ' id="' . $id . '"' : '') . ($label ? ' label="' . $label . '"' : '') . ($attr ? ' ' . $attr : '') . $extra;

				if (is_array($options['list.select']))
				{
					foreach ($options['list.select'] as $val)
					{
						$key2 = is_object($val) ? $val->{$options['option.key']} : $val;

						if ($key == $key2)
						{
							$extra .= ' selected="selected"';
							break;
						}
					}
				}
				elseif ((string) $key === (string) $options['list.select'])
				{
					$extra .= ' selected="selected"';
				}

				if ($options['list.translate'])
				{
					$text = Text::_($text);
				}

				// Generate the option, encoding as required
				$html .= $baseIndent . '<option value="' . ($options['option.key.toHtml'] ? htmlspecialchars($key, ENT_COMPAT, 'UTF-8') : $key) . '"'
					. $extra . '>';
				$html .= $options['option.text.toHtml'] ? htmlentities(html_entity_decode($text, ENT_COMPAT, 'UTF-8'), ENT_COMPAT, 'UTF-8') : $text;
				$html .= '</option>' . $options['format.eol'];
			}
		}

		return $html;
	}

	/**
	 * Generates an HTML radio list.
	 *
	 * @param   array    $data       An array of objects
	 * @param   string   $name       The value of the HTML name attribute
	 * @param   string   $attribs    Additional HTML attributes for the `<select>` tag
	 * @param   mixed    $optKey     The key that is selected
	 * @param   string   $optText    The name of the object variable for the option value
	 * @param   mixed    $selected   The name of the object variable for the option text
	 * @param   boolean  $idtag      Value of the field id or null by default
	 * @param   boolean  $translate  True if options will be translated
	 *
	 * @return  string  HTML for the select list
	 */
	public static function radiolist($data, $name, $attribs = null, $optKey = 'value', $optText = 'text', $selected = null, $idtag = false,
	                                 $translate = false)
	{

		$forSelect = false;

		if (isset($attribs['forSelect']))
		{
			$forSelect = (bool) ($attribs['forSelect']);
			unset($attribs['forSelect']);
		}

		if (is_array($attribs))
		{
			$attribs = ArrayHelper::toString($attribs);
		}

		$id_text = empty($idtag) ? $name : $idtag;

		$html = '';

		foreach ($data as $optionObject)
		{
			$optionValue = $optionObject->$optKey;
			$labelText   = $translate ? \Joomla\CMS\Language\Text::_($optionObject->$optText) : $optionObject->$optText;
			$id          = ($optionObject->id ?? null);

			$extra = '';
			$id    = $id ? $optionObject->id : $id_text . $optionValue;

			if (is_array($selected))
			{
				foreach ($selected as $val)
				{
					$k2 = is_object($val) ? $val->$optKey : $val;

					if ($optionValue == $k2)
					{
						$extra .= ' selected="selected" ';
						break;
					}
				}
			}
			else
			{
				$extra .= ((string) $optionValue === (string) $selected ? ' checked="checked" ' : '');
			}

			if ($forSelect)
			{
				$html .= "\n\t" . '<input type="radio" name="' . $name . '" id="' . $id . '" value="' . $optionValue . '" ' . $extra
					. $attribs . ' />';
				$html .= "\n\t" . '<label for="' . $id . '" id="' . $id . '-lbl">' . $labelText . '</label>';
			}
			else
			{
				$html .= "\n\t" . '<label for="' . $id . '" id="' . $id . '-lbl">';
				$html .= "\n\t\n\t" . '<input type="radio" name="' . $name . '" id="' . $id . '" value="' . $optionValue . '" ' . $extra
					. $attribs . ' />' . $labelText;
				$html .= "\n\t" . '</label>';

			}
		}

		return $html . "\n";
	}

	/**
	 * Creates two radio buttons styled with FEF to appear as a YES/NO switch
	 *
	 * @param   string  $name      Name of the field
	 * @param   mixed   $selected  Selected field
	 * @param   array   $attribs   Additional attributes to add to the switch
	 *
	 * @return    string    The HTML for the switch
	 */
	public static function booleanswitch(string $name, $selected, array $attribs = []): string
	{
		if (empty($attribs))
		{
			$attribs = ['class' => 'akeeba-toggle'];
		}
		elseif (isset($attribs['class']))
		{
			$attribs['class'] .= ' akeeba-toggle';
		}
		else
		{
			$attribs['class'] = 'akeeba-toggle';
		}

		$temp = '';

		foreach ($attribs as $key => $value)
		{
			$temp .= $key . ' = "' . $value . '"';
		}

		$attribs = $temp;

		$checked_1 = $selected ? '' : 'checked ';
		$checked_2 = $selected ? 'checked ' : '';

		$html = '<div ' . $attribs . '>';
		$html .= '<input type="radio" class="radio-yes" name="' . $name . '" ' . $checked_2 . 'id="' . $name . '-2" value="1">';
		$html .= '<label for="' . $name . '-2" class="green">' . Text::_('JYES') . '</label>';
		$html .= '<input type="radio" class="radio-no" name="' . $name . '" ' . $checked_1 . 'id="' . $name . '-1" value="0">';
		$html .= '<label for="' . $name . '-1" class="red">' . Text::_('JNO') . '</label>';
		$html .= '</div>';

		return $html;
	}
}
