<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Form\Field;

use FOF30\Form\Exception\DataModelRequired;
use FOF30\Form\FieldInterface;
use FOF30\Form\Form;
use FOF30\Model\DataModel;
use JHtml;
use JText;

defined('_JEXEC') or die;

\JFormHelper::loadFieldClass('list');

/**
 * Form Field class for FOF
 * Supports a generic list of options.
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class Published extends \JFormFieldList implements FieldInterface
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
	 * Method to get the field options.
	 *
	 * @since 2.0
	 *
	 * @return  array  The field option objects.
	 */
	protected function getOptions()
	{
		$options = parent::getOptions();

		if (!empty($options))
		{
			return $options;
		}

		// If no custom options were defined let's figure out which ones of the
		// defaults we shall use...

		$config = array(
			'published'		 => 1,
			'unpublished'	 => 1,
			'archived'		 => 0,
			'trash'			 => 0,
			'all'			 => 0,
		);

		$configMap = array(
			'show_published'	=> array('published', 1),
			'show_unpublished'	=> array('unpublished', 1),
			'show_archived'		=> array('archived', 0),
			'show_trash'		=> array('trash', 0),
			'show_all'			=> array('all', 0),
		);

		foreach ($configMap as $attribute => $preferences)
		{
			list($configKey, $default) = $preferences;

			switch (strtolower($this->element[$attribute]))
			{
				case 'true':
				case '1':
				case 'yes':
					$config[$configKey] = true;
				break;

				case 'false':
				case '0':
				case 'no':
					$config[$configKey] = false;
				break;

				default:
					$config[$configKey] = $default;
			}
		}

		$stack = array();

		if ($config['published'])
		{
			$stack[] = JHtml::_('select.option', '1', JText::_('JPUBLISHED'));
		}

		if ($config['unpublished'])
		{
			$stack[] = JHtml::_('select.option', '0', JText::_('JUNPUBLISHED'));
		}

		if ($config['archived'])
		{
			$stack[] = JHtml::_('select.option', '2', JText::_('JARCHIVED'));
		}

		if ($config['trash'])
		{
			$stack[] = JHtml::_('select.option', '-2', JText::_('JTRASHED'));
		}

		if ($config['all'])
		{
			$stack[] = JHtml::_('select.option', '*', JText::_('JALL'));
		}

		return $stack;
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
		$class = $this->class ? ' class="' . $this->class . '"' : '';

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
	 *
	 * @throws  DataModelRequired
	 */
	public function getRepeatable()
	{
		if (!($this->item instanceof DataModel))
		{
			throw new DataModelRequired(__CLASS__);
		}

		$prefix       = $this->element['prefix'] ? (string) $this->element['prefix'] : '';
		$checkbox     = $this->element['checkbox'] ? (string) $this->element['checkbox'] : 'cb';
		$publish_up   = $this->element['publish_up'] ? (string) $this->element['publish_up'] : null;
		$publish_down = $this->element['publish_down'] ? (string) $this->element['publish_down'] : null;
		$container    = $this->form->getContainer();
		$privilege    = $this->element['acl_privilege'] ? $this->element['acl_privilege'] : 'core.edit.state';
		$component    = $this->element['acl_component'] ? $this->element['acl_component'] : $container->componentName;
		$component    = empty($component) ? null : $component;
		$enabled      = $container->platform->getUser()->authorise($privilege, $component);

		// @todo Enforce ACL checks to determine if the field should be enabled or not
		// Get the HTML
		return JHTML::_('jgrid.published', $this->value, $this->rowid, $prefix, $enabled, $checkbox, $publish_up, $publish_down);
	}
}
