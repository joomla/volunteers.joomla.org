<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Html\FEFHelper;

defined('_JEXEC') || die;

use FOF40\Container\Container;
use FOF40\Html\SelectOptions;
use FOF40\Model\DataModel;
use FOF40\Utils\ArrayHelper;
use FOF40\View\DataView\DataViewInterface;
use FOF40\View\View;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/**
 * An HTML helper for Browse views.
 *
 * It reintroduces a FEF-friendly of some of the functionality found in FOF 3's Header and Field classes. These
 * helpers are also accessible through Blade, making the transition from XML forms to Blade templates easier.
 *
 * @since 3.3.0
 */
abstract class BrowseView
{
	/**
	 * Caches the results of getOptionsFromModel keyed by a hash. The hash is computed by the model
	 * name, the model state and the options passed to getOptionsFromModel.
	 *
	 * @var array
	 */
	private static $cacheModelOptions = [];

	/**
	 * Get the translation key for a field's label
	 *
	 * @param   string  $fieldName  The field name
	 *
	 * @return string
	 *
	 * @since 3.3.0
	 */
	public static function fieldLabelKey(string $fieldName): string
	{
		$view = self::getViewFromBacktrace();

		try
		{
			$inflector     = $view->getContainer()->inflector;
			$viewName      = $inflector->singularize($view->getName());
			$altViewName   = $inflector->pluralize($view->getName());
			$componentName = $view->getContainer()->componentName;

			$keys = [
				strtoupper($componentName . '_' . $viewName . '_FIELD_' . $fieldName),
				strtoupper($componentName . '_' . $altViewName . '_FIELD_' . $fieldName),
				strtoupper($componentName . '_' . $viewName . '_' . $fieldName),
				strtoupper($componentName . '_' . $altViewName . '_' . $fieldName),
			];

			foreach ($keys as $key)
			{
				if (Text::_($key) !== $key)
				{
					return $key;
				}
			}

			return $keys[0];
		}
		catch (\Exception $e)
		{
			return ucfirst($fieldName);
		}
	}

	/**
	 * Returns the label for a field (translated)
	 *
	 * @param   string  $fieldName  The field name
	 *
	 * @return string
	 */
	public static function fieldLabel(string $fieldName): string
	{
		return Text::_(self::fieldLabelKey($fieldName));
	}

	/**
	 * Return a table field header which sorts the table by that field upon clicking
	 *
	 * @param   string       $field    The name of the field
	 * @param   string|null  $langKey  (optional) The language key for the header to be displayed
	 *
	 * @return  string
	 */
	public static function sortgrid(string $field, ?string $langKey = null): string
	{
		/** @var DataViewInterface $view */
		$view = self::getViewFromBacktrace();

		if (is_null($langKey))
		{
			$langKey = self::fieldLabelKey($field);
		}

		return HTMLHelper::_('FEFHelp.browse.sort', $langKey, $field, $view->getLists()->order_Dir, $view->getLists()->order, $view->getTask());
	}

	/**
	 * Create a browse view filter from values returned by a model
	 *
	 * @param   string  $localField       Field name
	 * @param   string  $modelTitleField  Foreign model field for drop-down display values
	 * @param   null    $modelName        Foreign model name
	 * @param   string  $placeholder      Placeholder for no selection
	 * @param   array   $params           Generic select display parameters
	 *
	 * @return string
	 *
	 * @since 3.3.0
	 */
	public static function modelFilter(string $localField, string $modelTitleField = 'title', ?string $modelName = null,
	                                   ?string $placeholder = null, array $params = []): string
	{
		/** @var DataModel $model */
		$model = self::getViewFromBacktrace()->getModel();

		if (empty($modelName))
		{
			$modelName = $model->getForeignModelNameFor($localField);
		}

		if (is_null($placeholder))
		{
			$placeholder = self::fieldLabelKey($localField);
		}

		$params = array_merge([
			'list.none'      => '&mdash; ' . Text::_($placeholder) . ' &mdash;',
			'value_field'    => $modelTitleField,
			'fof.autosubmit' => true,
		], $params);

		return self::modelSelect($localField, $modelName, $model->getState($localField), $params);
	}

	/**
	 * Display a text filter (search box)
	 *
	 * @param   string  $localField   The name of the model field. Used when getting the filter state.
	 * @param   string  $searchField  The INPUT element's name. Default: "filter_$localField".
	 * @param   string  $placeholder  The Text language key for the placeholder. Default: extrapolate from $localField.
	 * @param   array   $attributes   HTML attributes for the INPUT element.
	 *
	 * @return  string
	 *
	 * @since   3.3.0
	 */
	public static function searchFilter(string $localField, ?string $searchField = null, ?string $placeholder = null,
	                                    array $attributes = []): string
	{
		/** @var DataModel $model */
		$view                      = self::getViewFromBacktrace();
		$model                     = $view->getModel();
		$searchField               = empty($searchField) ? $localField : $searchField;
		$placeholder               = empty($placeholder) ? self::fieldLabelKey($localField) : $placeholder;
		$attributes['type']        = $attributes['type'] ?? 'text';
		$attributes['name']        = $searchField;
		$attributes['id']          = !isset($attributes['id']) ? "filter_$localField" : $attributes['id'];
		$attributes['placeholder'] = !isset($attributes['placeholder']) ? $view->escape(Text::_($placeholder)) : $attributes['placeholder'];
		$attributes['title']       = $attributes['title'] ?? $attributes['placeholder'];
		$attributes['value']       = $view->escape($model->getState($localField));

		if (!isset($attributes['onchange']))
		{
			$attributes['class'] = trim(($attributes['class'] ?? '') . ' akeebaCommonEventsOnChangeSubmit');
			$attributes['data-akeebasubmittarget'] = $attributes['data-akeebasubmittarget'] ?? 'adminForm';
		}

		// Remove null attributes and collapse into a string
		$attributes = array_filter($attributes, function ($v) {
			return !is_null($v);
		});
		$attributes = ArrayHelper::toString($attributes);

		return "<input $attributes />";
	}

	/**
	 * Create a browse view filter with dropdown values
	 *
	 * @param   string  $localField   Field name
	 * @param   array   $options      The HTMLHelper options list to use
	 * @param   string  $placeholder  Placeholder for no selection
	 * @param   array   $params       Generic select display parameters
	 *
	 * @return string
	 *
	 * @since 3.3.0
	 */
	public static function selectFilter(string $localField, array $options, ?string $placeholder = null,
	                                    array $params = []): string
	{
		/** @var DataModel $model */
		$model = self::getViewFromBacktrace()->getModel();

		if (is_null($placeholder))
		{
			$placeholder = self::fieldLabelKey($localField);
		}

		$params = array_merge([
			'list.none'      => '&mdash; ' . Text::_($placeholder) . ' &mdash;',
			'fof.autosubmit' => true,
		], $params);

		return self::genericSelect($localField, $options, $model->getState($localField), $params);
	}

	/**
	 * View access dropdown filter
	 *
	 * @param   string  $localField   Field name
	 * @param   string  $placeholder  Placeholder for no selection
	 * @param   array   $params       Generic select display parameters
	 *
	 * @return string
	 *
	 * @since 3.3.0
	 */
	public static function accessFilter(string $localField, ?string $placeholder = null, array $params = []): string
	{
		return self::selectFilter($localField, SelectOptions::getOptions('access', $params), $placeholder, $params);
	}

	/**
	 * Published state dropdown filter
	 *
	 * @param   string  $localField   Field name
	 * @param   string  $placeholder  Placeholder for no selection
	 * @param   array   $params       Generic select display parameters
	 *
	 * @return string
	 *
	 * @since 3.3.0
	 */
	public static function publishedFilter(string $localField, ?string $placeholder = null, array $params = []): string
	{
		return self::selectFilter($localField, SelectOptions::getOptions('published', $params), $placeholder, $params);
	}

	/**
	 * Create a select box from the values returned by a model
	 *
	 * @param   string  $name          Field name
	 * @param   string  $modelName     The name of the model, e.g. "items" or "com_foobar.items"
	 * @param   mixed   $currentValue  The currently selected value
	 * @param   array   $params        Passed to optionsFromModel and genericSelect
	 * @param   array   $modelState    Optional state variables to pass to the model
	 * @param   array   $options       Any HTMLHelper select options you want to add in front of the model's returned
	 *                                 values
	 *
	 * @return string
	 *
	 * @see   self::getOptionsFromModel
	 * @see   self::getOptionsFromSource
	 * @see   self::genericSelect
	 *
	 * @since 3.3.0
	 */
	public static function modelSelect(string $name, string $modelName, $currentValue, array $params = [],
	                                   array $modelState = [], array $options = []): string
	{
		$params = array_merge([
			'fof.autosubmit' => true,
		], $params);

		$options = self::getOptionsFromModel($modelName, $params, $modelState, $options);

		return self::genericSelect($name, $options, $currentValue, $params);
	}

	/**
	 * Get a (human readable) title from a (typically numeric, foreign key) key value using the data
	 * returned by a DataModel.
	 *
	 * @param   string  $value       The key value
	 * @param   string  $modelName   The name of the model, e.g. "items" or "com_foobar.items"
	 * @param   array   $params      Passed to getOptionsFromModel
	 * @param   array   $modelState  Optional state variables to pass to the model
	 * @param   array   $options     Any HTMLHelper select options you want to add in front of the model's returned
	 *                               values
	 *
	 * @return string
	 *
	 * @see   self::getOptionsFromModel
	 * @see   self::getOptionsFromSource
	 * @see   self::genericSelect
	 *
	 * @since 3.3.0
	 */
	public static function modelOptionName(string $value, ?string $modelName = null, array $params = [],
	                                       array $modelState = [], array $options = []): ?string
	{
		if (!isset($params['cache']))
		{
			$params['cache'] = true;
		}

		if (!isset($params['none_as_zero']))
		{
			$params['none_as_zero'] = true;
		}

		$options = self::getOptionsFromModel($modelName, $params, $modelState, $options);

		return self::getOptionName($value, $options);
	}

	/**
	 * Gets the active option's label given an array of HTMLHelper options
	 *
	 * @param   mixed   $selected     The currently selected value
	 * @param   array   $data         The HTMLHelper options to parse
	 * @param   string  $optKey       Key name, default: value
	 * @param   string  $optText      Value name, default: text
	 * @param   bool    $selectFirst  Should I automatically select the first option? Default: true
	 *
	 * @return  mixed   The label of the currently selected option
	 */
	public static function getOptionName($selected, array $data, string $optKey = 'value', string $optText = 'text', bool $selectFirst = true): ?string
	{
		$ret = null;

		foreach ($data as $elementKey => &$element)
		{
			if (is_array($element))
			{
				$key  = $optKey === null ? $elementKey : $element[$optKey];
				$text = $element[$optText];
			}
			elseif (is_object($element))
			{
				$key  = $optKey === null ? $elementKey : $element->$optKey;
				$text = $element->$optText;
			}
			else
			{
				// This is a simple associative array
				$key  = $elementKey;
				$text = $element;
			}

			if (is_null($ret) && $selectFirst && ($selected == $key))
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
	 * Create a generic select list based on a bunch of options. Option sources will be merged into the provided
	 * options automatically.
	 *
	 * Parameters:
	 * - format.depth The current indent depth.
	 * - format.eol The end of line string, default is linefeed.
	 * - format.indent The string to use for indentation, default is tab.
	 * - groups If set, looks for keys with the value "<optgroup>" and synthesizes groups from them. Deprecated.
	 * Default: true.
	 * - list.select Either the value of one selected option or an array of selected options. Default: $currentValue.
	 * - list.translate If true, text and labels are translated via Text::_(). Default is false.
	 * - list.attr HTML element attributes (key/value array or string)
	 * - list.none Placeholder for no selection (creates an option with an empty string key)
	 * - option.id The property in each option array to use as the selection id attribute. Defaults: null.
	 * - option.key The property in each option array to use as the Default: "value". If set to null, the index of the
	 * option array is used.
	 * - option.label The property in each option array to use as the selection label attribute. Default: null
	 * - option.text The property in each option array to use as the displayed text. Default: "text". If set to null,
	 * the option array is assumed to be a list of displayable scalars.
	 * - option.attr The property in each option array to use for additional selection attributes. Defaults: null.
	 * - option.disable: The property that will hold the disabled state. Defaults to "disable".
	 * - fof.autosubmit Should I auto-submit the form on change? Default: true
	 * - fof.formname Form to auto-submit. Default: adminForm
	 * - class CSS class to apply
	 * - size Size attribute for the input
	 * - multiple Is this a multiple select? Default: false.
	 * - required Is this a required field? Default: false.
	 * - autofocus Should I focus this field automatically? Default: false
	 * - disabled Is this a disabled field? Default: false
	 * - readonly Render as a readonly field with hidden inputs? Overrides 'disabled'. Default: false
	 * - onchange Custom onchange handler. Overrides fof.autosubmit. Default: NULL (use fof.autosubmit).
	 *
	 * @param   string  $name
	 * @param   array   $options
	 * @param   mixed   $currentValue
	 * @param   array   $params
	 *
	 * @return string
	 *
	 * @since 3.3.0
	 */
	public static function genericSelect(string $name, array $options, $currentValue, array $params = []): string
	{
		$params = array_merge([
			'format.depth'   => 0,
			'format.eol'     => "\n",
			'format.indent'  => "\t",
			'groups'         => true,
			'list.select'    => $currentValue,
			'list.translate' => false,
			'option.id'      => null,
			'option.key'     => 'value',
			'option.label'   => null,
			'option.text'    => 'text',
			'option.attr'    => null,
			'option.disable' => 'disable',
			'list.attr'      => '',
			'list.none'      => '',
			'id'             => null,
			'fof.autosubmit' => true,
			'fof.formname'   => 'adminForm',
			'class'          => '',
			'size'           => '',
			'multiple'       => false,
			'required'       => false,
			'autofocus'      => false,
			'disabled'       => false,
			'onchange'       => null,
			'readonly'       => false,
		], $params);

		$currentValue = $params['list.select'];

		$classes = $params['class'] ?? '';
		$classes = is_array($classes) ? implode(' ', $classes) : $classes;

		// If fof.autosubmit is enabled and onchange is not set we will add our own handler
		if ($params['fof.autosubmit'] && is_null($params['onchange']))
		{
			$formName                          = $params['fof.formname'] ?: 'adminForm';
			$classes                           .= ' akeebaCommonEventsOnChangeSubmit';
			$params['data-akeebasubmittarget'] = $formName;
		}

		// Construct SELECT element's attributes
		$attr = [
			'class'         => trim($classes) ?: null,
			'size'          => ($params['size'] ?? null) ?: null,
			'multiple'      => ($params['multiple'] ?? null) ?: null,
			'required'      => ($params['required'] ?? false) ?: null,
			'aria-required' => ($params['required'] ?? false) ? 'true' : null,
			'autofocus'     => ($params['autofocus'] ?? false) ?: null,
			'disabled'      => (($params['disabled'] ?? false) || ($params['readonly'] ?? false)) ?: null,
			'onchange'      => $params['onchange'] ?? null,
		];

		$attr = array_filter($attr, function ($x) {
			return !is_null($x);
		});

		// We merge the constructed SELECT element's attributes with the 'list.attr' array, if it was provided
		$params['list.attr'] = array_merge($attr, (($params['list.attr'] ?? []) ?: []));

		// Merge the options with those fetched from a source (e.g. another Helper object)
		$options = array_merge($options, self::getOptionsFromSource($params));

		if (!empty($params['list.none']))
		{
			array_unshift($options, HTMLHelper::_('FEFHelp.select.option', '', Text::_($params['list.none'])));
		}

		$html = [];

		// Create a read-only list (no name) with hidden input(s) to store the value(s).
		if ($params['readonly'])
		{
			$html[] = HTMLHelper::_('FEFHelp.select.genericlist', $options, $name, $params);

			// E.g. form field type tag sends $this->value as array
			if ($params['multiple'] && is_array($currentValue))
			{
				if (count($currentValue) === 0)
				{
					$currentValue[] = '';
				}

				foreach ($currentValue as $value)
				{
					$html[] = '<input type="hidden" name="' . $name . '" value="' . htmlspecialchars($value, ENT_COMPAT, 'UTF-8') . '"/>';
				}
			}
			else
			{
				$html[] = '<input type="hidden" name="' . $name . '" value="' . htmlspecialchars($value, ENT_COMPAT, 'UTF-8') . '"/>';
			}
		}
		else
			// Create a regular list.
		{
			$html[] = HTMLHelper::_('FEFHelp.select.genericlist', $options, $name, $params);
		}

		return implode($html);
	}

	/**
	 * Replace tags that reference fields with their values
	 *
	 * @param   string     $text  Text to process
	 * @param   DataModel  $item  The DataModel instance to get values from
	 *
	 * @return  string         Text with tags replace
	 *
	 * @since 3.3.0
	 */
	public static function parseFieldTags(string $text, DataModel $item): string
	{
		$ret = $text;

		if (empty($item))
		{
			return $ret;
		}

		/**
		 * Replace [ITEM:ID] in the URL with the item's key value (usually: the auto-incrementing numeric ID)
		 */
		$replace = $item->getId();
		$ret     = str_replace('[ITEM:ID]', $replace, $ret);

		// Replace the [ITEMID] in the URL with the current Itemid parameter
		$ret = str_replace('[ITEMID]', $item->getContainer()->input->getInt('Itemid', 0), $ret);

		// Replace the [TOKEN] in the URL with the Joomla! form token
		$ret = str_replace('[TOKEN]', $item->getContainer()->platform->getToken(true), $ret);

		// Replace other field variables in the URL
		$data = $item->getData();

		foreach ($data as $field => $value)
		{
			// Skip non-processable values
			if (is_array($value) || is_object($value))
			{
				continue;
			}

			$search = '[ITEM:' . strtoupper($field) . ']';
			$ret    = str_replace($search, $value, $ret);
		}

		return $ret;
	}

	/**
	 * Get the FOF View from the backtrace of the static call. MAGIC!
	 *
	 * @return  View
	 *
	 * @since 3.3.0
	 */
	public static function getViewFromBacktrace(): View
	{
		// In case we are on a brain-dead host
		if (!function_exists('debug_backtrace'))
		{
			throw new \RuntimeException("Your host has disabled the <code>debug_backtrace</code> PHP function. Please ask them to re-enable it. It's required for running this software.");
		}

		/**
		 * For performance reasons I look into the last 4 call stack entries. If I don't find a container I
		 * will expand my search by another 2 entries and so on until I either find a container or I stop
		 * finding new call stack entries.
		 */
		$lastNumberOfEntries = 0;
		$limit               = 4;
		$skip                = 0;
		$container           = null;

		while (true)
		{
			$backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, $limit);

			if (count($backtrace) === $lastNumberOfEntries)
			{
				throw new \RuntimeException(__METHOD__ . ": Cannot retrieve FOF View from call stack. You are either calling me from a non-FEF extension or your PHP is broken.");
			}

			$lastNumberOfEntries = count($backtrace);

			if ($skip)
			{
				$backtrace = array_slice($backtrace, $skip);
			}

			foreach ($backtrace as $bt)
			{
				if (!isset($bt['object']))
				{
					continue;
				}

				if ($bt['object'] instanceof View)
				{
					return $bt['object'];
				}
			}

			$skip  = $limit;
			$limit += 2;
		}
	}

	/**
	 * Get HTMLHelper options from an alternate source, e.g. a helper. This is useful for adding arbitrary options
	 * which are either dynamic or you do not want to inline to your view, e.g. reusable options across
	 * different views.
	 *
	 * The attribs can be:
	 * source_file          The file to load. You can use FOF's URIs such as 'admin:com_foobar/foo/bar'
	 * source_class         The class to use
	 * source_method        The static method to use on source_class
	 * source_key           Use * if you're returning a key/value array. Otherwise the array key for the key (ID)
	 * value.
	 * source_value         Use * if you're returning a key/value array. Otherwise the array key for the displayed
	 * value. source_translate     Should I pass the value field through Text? Default: true source_format        Set
	 * to "optionsobject" if you're returning an array of HTMLHelper options. Ignored otherwise.
	 *
	 * @param   array  $attribs
	 *
	 * @return array
	 *
	 * @since 3.3.0
	 */
	private static function getOptionsFromSource(array $attribs = []): array
	{
		$options = [];

		$container = self::getContainerFromBacktrace();

		$attribs = array_merge([
			'source_file'      => '',
			'source_class'     => '',
			'source_method'    => '',
			'source_key'       => '*',
			'source_value'     => '*',
			'source_translate' => true,
			'source_format'    => '',
		], $attribs);

		$source_file      = $attribs['source_file'];
		$source_class     = $attribs['source_class'];
		$source_method    = $attribs['source_method'];
		$source_key       = $attribs['source_key'];
		$source_value     = $attribs['source_value'];
		$source_translate = $attribs['source_translate'];
		$source_format    = $attribs['source_format'];

		if ($source_class && $source_method)
		{
			// Maybe we have to load a file?
			if (!empty($source_file))
			{
				$source_file = $container->template->parsePath($source_file, true);

				if ($container->filesystem->fileExists($source_file))
				{
					include $source_file;
				}
			}

			// Make sure the class exists
			// ...and so does the option
			if (class_exists($source_class, true) && in_array($source_method, get_class_methods($source_class)))
			{
				// Get the data from the class
				if ($source_format == 'optionsobject')
				{
					$options = array_merge($options, $source_class::$source_method());
				}
				else
				{
					$source_data = $source_class::$source_method();

					// Loop through the data and prime the $options array
					foreach ($source_data as $k => $v)
					{
						$key   = (empty($source_key) || ($source_key == '*')) ? $k : @$v[$source_key];
						$value = (empty($source_value) || ($source_value == '*')) ? $v : @$v[$source_value];

						if ($source_translate)
						{
							$value = Text::_($value);
						}

						$options[] = HTMLHelper::_('FEFHelp.select.option', $key, $value, 'value', 'text');
					}
				}
			}
		}

		reset($options);

		return $options;
	}

	/**
	 * Get HTMLHelper options from the values returned by a model.
	 *
	 * The params can be:
	 * key_field        The model field used for the OPTION's key. Default: the model's ID field.
	 * value_field      The model field used for the OPTION's displayed value. You must provide it.
	 * apply_access     Should I apply Joomla ACLs to the model? Default: FALSE.
	 * none             Placeholder for no selection. Default: NULL (no placeholder).
	 * none_as_zero     When true, the 'none' placeholder applies to values '' **AND** '0' (empty string and zero)
	 * translate        Should I pass the values through Text? Default: TRUE.
	 * with             Array of relation names for eager loading.
	 * cache            Cache the results for faster reuse
	 *
	 * @param   string  $modelName   The name of the model, e.g. "items" or "com_foobar.items"
	 * @param   array   $params      Parameters which define which options to get from the model
	 * @param   array   $modelState  Optional state variables to pass to the model
	 * @param   array   $options     Any HTMLHelper select options you want to add in front of the model's returned
	 *                               values
	 *
	 * @return mixed
	 *
	 * @since 3.3.0
	 */
	private static function getOptionsFromModel(string $modelName, array $params = [], array $modelState = [],
	                                            array $options = []): array
	{
		// Let's find the FOF DI container from the call stack
		$container = self::getContainerFromBacktrace();

		// Explode model name into component name and prefix
		$componentName = $container->componentName;
		$mName         = $modelName;

		if (strpos($modelName, '.') !== false)
		{
			[$componentName, $mName] = explode('.', $mName, 2);
		}

		if ($componentName !== $container->componentName)
		{
			$container = Container::getInstance($componentName);
		}

		/** @var DataModel $model */
		$model = $container->factory->model($mName)->setIgnoreRequest(true)->savestate(false);

		$defaultParams = [
			'key_field'    => $model->getKeyName(),
			'value_field'  => 'title',
			'apply_access' => false,
			'none'         => null,
			'none_as_zero' => false,
			'translate'    => true,
			'with'         => [],
		];

		$params = array_merge($defaultParams, $params);

		$cache    = isset($params['cache']) && $params['cache'];
		$cacheKey = null;

		if ($cache)
		{
			$cacheKey = sha1(print_r([
				$model->getContainer()->componentName,
				$model->getName(),
				$params['key_field'],
				$params['value_field'],
				$params['apply_access'],
				$params['none'],
				$params['translate'],
				$params['with'],
				$modelState,
			], true));
		}

		if ($cache && isset(self::$cacheModelOptions[$cacheKey]))
		{
			return self::$cacheModelOptions[$cacheKey];
		}

		if (empty($params['none']) && !is_null($params['none']))
		{
			$langKey     = strtoupper($model->getContainer()->componentName . '_TITLE_' . $model->getName());
			$placeholder = Text::_($langKey);

			if ($langKey !== $placeholder)
			{
				$params['none'] = '&mdash; ' . $placeholder . ' &mdash;';
			}
		}

		if (!empty($params['none']))
		{
			$options[] = HTMLHelper::_('FEFHelp.select.option', null, Text::_($params['none']));

			if ($params['none_as_zero'])
			{
				$options[] = HTMLHelper::_('FEFHelp.select.option', 0, Text::_($params['none']));
			}
		}


		if ($params['apply_access'])
		{
			$model->applyAccessFiltering();
		}

		if (!is_null($params['with']))
		{
			$model->with($params['with']);
		}

		// Set the model's state, if applicable
		foreach ($modelState as $stateKey => $stateValue)
		{
			$model->setState($stateKey, $stateValue);
		}

		// Set the query and get the result list.
		$items = $model->get(true);

		foreach ($items as $item)
		{
			$value = $item->{$params['value_field']};

			if ($params['translate'])
			{
				$value = Text::_($value);
			}

			$options[] = HTMLHelper::_('FEFHelp.select.option', $item->{$params['key_field']}, $value);
		}

		if ($cache)
		{
			self::$cacheModelOptions[$cacheKey] = $options;
		}

		return $options;
	}

	/**
	 * Get the FOF DI container from the backtrace of the static call. MAGIC!
	 *
	 * @return  Container
	 *
	 * @since 3.3.0
	 */
	private static function getContainerFromBacktrace(): Container
	{
		// In case we are on a brain-dead host
		if (!function_exists('debug_backtrace'))
		{
			throw new \RuntimeException("Your host has disabled the <code>debug_backtrace</code> PHP function. Please ask them to re-enable it. It's required for running this software.");
		}

		/**
		 * For performance reasons I look into the last 4 call stack entries. If I don't find a container I
		 * will expand my search by another 2 entries and so on until I either find a container or I stop
		 * finding new call stack entries.
		 */
		$lastNumberOfEntries = 0;
		$limit               = 4;
		$skip                = 0;
		$container           = null;

		while (true)
		{
			$backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, $limit);

			if (count($backtrace) === $lastNumberOfEntries)
			{
				throw new \RuntimeException(__METHOD__ . ": Cannot retrieve FOF container from call stack. You are either calling me from a non-FEF extension or your PHP is broken.");
			}

			$lastNumberOfEntries = count($backtrace);

			if ($skip !== 0)
			{
				$backtrace = array_slice($backtrace, $skip);
			}

			foreach ($backtrace as $bt)
			{
				if (!isset($bt['object']))
				{
					continue;
				}

				if (!method_exists($bt['object'], 'getContainer'))
				{
					continue;
				}

				return $bt['object']->getContainer();
			}

			$skip  = $limit;
			$limit += 2;
		}
	}

}
