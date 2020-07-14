<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Form\Field;

use FOF30\Form\Exception\DataModelRequired;
use FOF30\Form\Exception\GetStaticNotAllowed;
use FOF30\Form\FieldInterface;
use FOF30\Form\Form;
use FOF30\Model\DataModel;
use FOF30\Utils\StringHelper;
use JHtml;

defined('_JEXEC') or die;

\JFormHelper::loadFieldClass('list');

/**
 * Form Field class for FOF
 * Supports a generic list of options.
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class Actions extends \JFormFieldList implements FieldInterface
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
	 * Get the field configuration
	 *
	 * @return  array
	 */
	protected function getConfig()
	{
		// If no custom options were defined let's figure out which ones of the
		// defaults we shall use...
		$config = array(
			'published'		 => 1,
			'unpublished'	 => 1,
			'archived'		 => 0,
			'trash'			 => 0,
			'all'			 => 0,
		);

		if (isset($this->element['show_published']))
		{
			$config['published'] = StringHelper::toBool($this->element['show_published']);
		}

		if (isset($this->element['show_unpublished']))
		{
			$config['unpublished'] = StringHelper::toBool($this->element['show_unpublished']);
		}

		if (isset($this->element['show_archived']))
		{
			$config['archived'] = StringHelper::toBool($this->element['show_archived']);
		}

		if (isset($this->element['show_trash']))
		{
			$config['trash'] = StringHelper::toBool($this->element['show_trash']);
		}

		if (isset($this->element['show_all']))
		{
			$config['all'] = StringHelper::toBool($this->element['show_all']);
		}

		return $config;
	}

	/**
	 * Method to get the field options.
	 *
	 * @since 2.0
	 *
	 * @return  array  The field option objects.
	 */
	protected function getOptions()
	{
		return null;
	}

	/**
	 * Method to get a
	 *
	 * @param   string  $enabledFieldName  Name of the enabled/published field
	 *
	 * @return  Published  Field
	 */
	protected function getPublishedField($enabledFieldName)
	{
		$attributes = array(
			'name' => $enabledFieldName,
			'type' => 'published',
		);

		if ($this->element['publish_up'])
		{
			$attributes['publish_up'] = (string) $this->element['publish_up'];
		}

		if ($this->element['publish_down'])
		{
			$attributes['publish_down'] = (string) $this->element['publish_down'];
		}

		$renderedAttributes = array();

		foreach ($attributes as $name => $value)
		{
			if (!is_null($value))
			{
				$renderedAttributes[] = $name . '="' . $value . '"';
			}
		}

		$publishedXml = new \SimpleXMLElement('<field ' . implode(' ', $renderedAttributes) . ' />');

		$publishedField = new Published($this->form);

		// Pass required objects to the field
		$publishedField->item = $this->item;
		$publishedField->rowid = $this->rowid;
		$publishedField->setup($publishedXml, $this->item->{$enabledFieldName});

		return $publishedField;
	}

	/**
	 * Get the rendering of this field type for static display, e.g. in a single
	 * item view (typically a "read" task).
	 *
	 * @since 2.0
	 *
	 * @return  string  The field HTML
	 *
	 * @throws  \LogicException
	 */
	public function getStatic()
	{
		throw new GetStaticNotAllowed(__CLASS__);
	}

	/**
	 * Get the rendering of this field type for a repeatable (grid) display,
	 * e.g. in a view listing many item (typically a "browse" task)
	 *
	 * @since 2.0
	 *
	 * @return  string  The field HTML
	 *
	 * @throws  DataModelRequired
	 */
	public function getRepeatable()
	{
		if (!($this->item instanceof DataModel))
		{
			throw new DataModelRequired(__CLASS__);
		}

		$config = $this->getConfig();

		$html = '<div class="btn-group">';

		// Render a published field
		if ($this->item->hasField('enabled'))
		{
            $publishedFieldName = $this->item->getFieldAlias('enabled');

			if ($config['published'] || $config['unpublished'])
			{
				// Generate a FieldInterfacePublished field
				$publishedField = $this->getPublishedField($publishedFieldName);

				// Render the publish button
				$html .= $publishedField->getRepeatable();
			}

			if ($config['archived'])
			{
				$archived	= $this->item->getFieldValue($publishedFieldName) == 2 ? true : false;

				// Create dropdown items
				$action = $archived ? 'unarchive' : 'archive';
				JHtml::_('actionsdropdown.' . $action, 'cb' . $this->rowid);
			}

			if ($config['trash'])
			{
				$trashed	= $this->item->getFieldValue($publishedFieldName) == -2 ? true : false;

				$action = $trashed ? 'untrash' : 'trash';
				JHtml::_('actionsdropdown.' . $action, 'cb' . $this->rowid);
			}

			// Render dropdown list
			if ($config['archived'] || $config['trash'])
			{
				$html .= JHtml::_('actionsdropdown.render', $this->item->title);
			}
		}

		$html .= '</div>';

		return $html;
	}
}
