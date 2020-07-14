<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Form\Header;

use JHtml;
use JText;

defined('_JEXEC') or die;

/**
 * Generic field header, with drop down filters
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class Selectable extends Field
{
	/**
	 * Create objects for the options
	 *
	 * @return  array  The array of option objects
	 */
	protected function getOptions()
	{
		$options = array();

		// Get the field $options
		/** @var \SimpleXMLElement $option */
		foreach ($this->element->children() as $option)
		{
			// Only add <option /> elements.
			if ($option->getName() != 'option')
			{
				continue;
			}

			// Create a new option object based on the <option /> element.
			$options[] = JHtml::_(
				'select.option',
				(string) $option['value'],
				JText::alt(
					trim((string) $option),
					preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname)
				),
				'value', 'text', ((string) $option['disabled'] == 'true')
			);
		}

		// Do we have a class and method source for our options?
		$source_file = empty($this->element['source_file']) ? '' : (string) $this->element['source_file'];
		$source_class = empty($this->element['source_class']) ? '' : (string) $this->element['source_class'];
		$source_method = empty($this->element['source_method']) ? '' : (string) $this->element['source_method'];
		$source_key = empty($this->element['source_key']) ? '*' : (string) $this->element['source_key'];
		$source_value = empty($this->element['source_value']) ? '*' : (string) $this->element['source_value'];
		$source_translate = empty($this->element['source_translate']) ? 'true' : (string) $this->element['source_translate'];
		$source_translate = in_array(strtolower($source_translate), array('true','yes','1','on')) ? true : false;
		$source_format = empty($this->element['source_format']) ? '' : (string) $this->element['source_format'];

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
}
