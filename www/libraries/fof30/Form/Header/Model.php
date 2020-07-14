<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Form\Header;

use FOF30\Container\Container;
use FOF30\Model\DataModel;
use JHtml;
use JText;

defined('_JEXEC') or die;

if (!class_exists('JFormFieldSql'))
{
	require_once JPATH_LIBRARIES . '/joomla/form/fields/sql.php';
}

/**
 * Form Field class for FOF
 * Generic list from a model's results
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class Model extends Selectable
{
	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 */
	protected function getOptions()
	{
		$options = array();

		// Initialize some field attributes.
		$key = $this->element['key_field'] ? (string) $this->element['key_field'] : 'value';
		$value = $this->element['value_field'] ? (string) $this->element['value_field'] : (string) $this->element['name'];
		$applyAccess = $this->element['apply_access'] ? (string) $this->element['apply_access'] : 'false';
		$modelName = (string) $this->element['model'];
		$nonePlaceholder = (string) $this->element['none'];
		$translate = empty($this->element['translate']) ? 'true' : (string) $this->element['translate'];
		$translate = in_array(strtolower($translate), array('true','yes','1','on')) ? true : false;
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

		// Process field atrtibutes
		$applyAccess = strtolower($applyAccess);
		$applyAccess = in_array($applyAccess, array('yes', 'on', 'true', '1'));

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
			// Only add <option /> elements.
			if ($stateoption->getName() != 'state')
			{
				continue;
			}

			$stateKey = (string) $stateoption['key'];
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
					$options[] = JHtml::_('select.option', $item->$key, $item->$value);
				}
			}
		}

		// Merge any additional options in the XML definition.
		$options = array_merge(parent::getOptions(), $options);

		return $options;
	}
}
