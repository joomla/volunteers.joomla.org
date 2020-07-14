<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Form\Field;

use FOF30\Model\DataModel;
use JHtml;
use JText;

defined('_JEXEC') or die;

/**
 * Form Field class for FOF
 * Relation list
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class Relation extends GenericList
{
	/**
	 * Get the rendering of this field type for static display, e.g. in a single
	 * item view (typically a "read" task).
	 *
	 * @since 2.0
	 *
	 * @return  string  The field HTML
	 */
	public function getStatic() {
		return $this->getRepeatable();
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
		$class         = $this->class ? $this->class : $this->id;
		$relationclass = $this->element['relationclass'] ? (string) $this->element['relationclass'] : '';
		$value_field   = $this->element['value_field'] ? (string) $this->element['value_field'] : 'title';
		$translate     = $this->element['translate'] ? (string) $this->element['translate'] : false;
		$link_url      = $this->element['url'] ? (string) $this->element['url'] : false;

		if (!($link_url && $this->item instanceof DataModel))
		{
			$link_url = false;
		}

		if ($this->element['empty_replacement'])
		{
			$empty_replacement = (string) $this->element['empty_replacement'];
		}

		$relationName = $this->form->getModel()->getContainer()->inflector->pluralize($this->name);
		$relations    = $this->item->getRelations()->getData($relationName);

		$rels = array();

		foreach ($relations as $relation) {

			$html = '<span class="' . $relationclass . '">';

			if ($link_url)
			{
				$keyfield = $relation->getKeyName();
				$this->_relationId =  $relation->$keyfield;

				$url = $this->parseFieldTags($link_url);
				$html .= '<a href="' . $url . '">';
			}

			if (!isset($this->valueField))
			{
				$this->valueField = $relation->getFieldAlias($value_field);
			}
			$value = $relation->{$this->valueField};

			// Get the (optionally formatted) value
			if (!empty($empty_replacement) && empty($value))
			{
				$value = JText::_($empty_replacement);
			}

			if ($translate == true)
			{
				$html .= JText::_($value);
			}
			else
			{
				$html .= $value;
			}

			if ($link_url)
			{
				$html .= '</a>';
			}

			$html .= '</span>';

			$rels[] = $html;
		}

		$html = '<span class="' . $class . '">';
		$html .= implode(', ', $rels);
		$html .= '</span>';

		return $html;
	}

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 */
	protected function getOptions()
	{
		$options     = array();
		$this->value = array();

		$value_field = $this->element['value_field'] ? (string) $this->element['value_field'] : 'title';
		$name = (string) $this->element['name'];
        	$class = $this->element['model'] ? (string) $this->element['model'] : $name;

		$view      = $this->form->getView()->getName();
		$relation  = $this->form->getModel()->getContainer()->inflector->pluralize($class);

		/** @var DataModel $model */
		$model = $this->form->getContainer()->factory->model($relation)->setIgnoreRequest(true)->savestate(false);

		$key   = $model->getIdFieldName();
		$value = $model->getFieldAlias($value_field);

		foreach ($model->get(true) as $option)
		{
			$options[] = JHtml::_('select.option', $option->$key, $option->$value);
		}

		if ($id = $this->form->getModel()->getId())
		{
			$model     = $this->form->getModel();
			$relations = $model->$name;

			foreach ($relations as $item)
			{
				$this->value[] = $item->getId();
			}
		}

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

        // Replace the [RELATION:ID] in the URL with the relation's key value
        $ret = str_replace('[RELATION:ID]', $this->_relationId, $ret);

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
