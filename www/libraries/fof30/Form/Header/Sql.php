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
 * Generic field header, with drop down filters based on a SQL query
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class Sql extends Selectable
{
	/**
	 * Create objects for the options
	 *
	 * @return  array  The array of option objects
	 */
	protected function getOptions()
	{
		$options = array();

		// Initialize some field attributes.
		$key       = $this->element['key_field'] ? (string) $this->element['key_field'] : 'value';
		$value     = $this->element['value_field'] ? (string) $this->element['value_field'] : (string) $this->element['name'];
		$translate = $this->element['translate'] ? (string) $this->element['translate'] : false;
		$query     = (string) $this->element['query'];

		// Get the database object.
		$db = $this->form->getContainer()->platform->getDbo();

		// Set the query and get the result list.
		$db->setQuery($query);
		$items = $db->loadObjectlist();

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
					$options[] = JHtml::_('select.option', $item->$key, $item->$value);
				}
			}
		}

		// Merge any additional options in the XML definition.
		$options = array_merge(parent::getOptions(), $options);

		return $options;
	}
}
