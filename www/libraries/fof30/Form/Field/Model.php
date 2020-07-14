<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Form\Field;

use FOF30\Container\Container;
use FOF30\Form\FieldInterface;
use FOF30\Form\Form;
use FOF30\Model\DataModel;
use FOF30\Utils\StringHelper;
use JHtml;
use JText;

defined('_JEXEC') or die;

\JFormHelper::loadFieldClass('list');

/**
 * Form Field class for FOF
 * Generic list from a model's results
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class Model extends GenericList implements FieldInterface
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
	 * Options loaded from the model, cached for efficiency
	 *
	 * @var null|array
	 */
	protected static $loadedOptions = null;

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
		$class = $this->class ? 'class="' . $this->class . '"' : '';

		return '<span id="' . $this->id . '" ' . $class . '>' .
			htmlspecialchars(GenericList::getOptionName($this->getOptions(), $this->value), ENT_COMPAT, 'UTF-8') .
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
		// Get field parameters
		$class					= $this->class ? $this->class : $this->id;
		$format_string			= $this->element['format'] ? (string) $this->element['format'] : '';
		$link_url				= $this->element['url'] ? (string) $this->element['url'] : '';
		$empty_replacement		= $this->element['empty_replacement'] ? (string) $this->element['empty_replacement'] : '';

		if ($link_url && ($this->item instanceof DataModel))
		{
			$link_url = $this->parseFieldTags($link_url);
		}
		else
		{
			$link_url = false;
		}

		if ($this->element['empty_replacement'])
		{
			$empty_replacement = (string) $this->element['empty_replacement'];
		}

        // Ask GenericList::getOptionName to not automatically select the first value
		$value = GenericList::getOptionName($this->getOptions(), $this->value, 'value', 'text', false);

		// Get the (optionally formatted) value
		if (!empty($empty_replacement) && empty($value))
		{
			$value = JText::_($empty_replacement);
		}

		if (empty($format_string))
		{
			$value = htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
		}
		else
		{
			$value = sprintf($format_string, $value);
		}

		// Create the HTML
		$html = '<span class="' . $class . '">';

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
     * Method to get the field options.
     *
     * @param   bool $forceReset
     *
     * @return  array The field option objects.
     */
	protected function getOptions($forceReset = false)
	{
		$myFormKey = $this->form->getName() . '#$#' . (string) $this->element['model'];

		if ($forceReset && isset(static::$loadedOptions[$myFormKey]))
		{
			unset(static::$loadedOptions[$myFormKey]);
		}

		if (!isset(static::$loadedOptions[$myFormKey]))
		{
			$options = array();

			// Initialize some field attributes.
			$key             = $this->element['key_field'] ? (string) $this->element['key_field'] : 'value';
			$value           = $this->element['value_field'] ? (string) $this->element['value_field'] : (string) $this->element['name'];
			$valueReplace    = StringHelper::toBool($this->element['parse_value']);
			$translate       = StringHelper::toBool($this->element['translate']);
			$applyAccess     = StringHelper::toBool($this->element['apply_access']);
			$modelName       = (string) $this->element['model'];
			$nonePlaceholder = (string) $this->element['none'];

			$with = $this->element['with'] ? (string) $this->element['with'] : null;

			if (!is_null($with))
			{
				$with = trim($with);
				$with = explode(',', $with);
				$with = array_map('trim', $with);
			}

			if (!empty($nonePlaceholder))
			{
				$options[] = JHtml::_('select.option', null, JText::_($nonePlaceholder));
			}

			// Explode model name into component name and prefix
			$componentName = $this->form->getContainer()->componentName;
			$mName = $modelName;

			if (strpos($modelName, '.') !== false)
			{
				list ($componentName, $mName) = explode('.', $mName, 2);
			}

			// Get the applicable container
			$container = $this->form->getContainer();

			if ($componentName != $container->componentName)
			{
				$container = Container::getInstance($componentName);
			}

			/** @var DataModel $model */
			$model = $container->factory->model($mName)->setIgnoreRequest(true)->savestate(false);

			// Get the model object
			if ($applyAccess)
			{
				$model->applyAccessFiltering();
			}

			if (!is_null($with))
			{
				$model->with($with);
			}

			// Process state variables
			/** @var \SimpleXMLElement $stateoption */
			foreach ($this->element->children() as $stateoption)
			{
				// Only add <state /> elements.
				if ($stateoption->getName() != 'state')
				{
					continue;
				}

				$stateKey   = (string) $stateoption['key'];
				$stateValue = (string) $stateoption;

				$model->setState($stateKey, $stateValue);
			}

			// Set the query and get the result list.
			$items = $model->get(true);

			// Build the field options.
			if (!empty($items))
			{
				foreach ($items as $item)
				{
					if ($translate == true)
					{
						$options[] = JHtml::_('select.option', $item->$key, JText::_($item->$value));
					}
					else
					{
						if ($valueReplace)
						{
							$text = $this->parseFieldTags($value, $item);
						}
						else
						{
							$text = $item->$value;
						}

						$options[] = JHtml::_('select.option', $item->$key, $text);
					}
				}
			}

			// Merge any additional options in the XML definition.
			$options = array_merge(parent::getOptions(), $options);

            static::$loadedOptions[$myFormKey] = $options;
		}

		return static::$loadedOptions[$myFormKey];
	}

	/**
	 * Replace string with tags that reference fields
	 *
	 * @param   string  $text  Text to process
	 *
	 * @return  string         Text with tags replace
	 */
	protected function parseFieldTags($text, $item = null)
	{
		$ret = $text;

		if ($item)
		{
			$this->item = $item;
		}

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
