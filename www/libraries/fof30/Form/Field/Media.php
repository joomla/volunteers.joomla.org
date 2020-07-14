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
use JHtml;
use JText;

defined('_JEXEC') or die;

\JFormHelper::loadFieldClass('media');

/**
 * Form Field class for the FOF framework
 * Media selection field.
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class Media extends \JFormFieldMedia implements FieldInterface
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
		if (isset($this->element['legacy']))
		{
			return $this->getInput();
		}

		$options = array(
			'id' => $this->id
		);

		return $this->getFieldContents($options);
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
		if (isset($this->element['legacy']))
		{
			return $this->getInput();
		}

		$options = array(
			'class' => $this->id
		);

		return $this->getFieldContents($options);
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @param   array   $fieldOptions  Options to be passed into the field
	 *
	 * @return  string  The field HTML
	 */
	public function getFieldContents(array $fieldOptions = array())
	{
		$imgattr = array();

		if (isset($fieldOptions['id']))
		{
			$imgattr['id'] = $fieldOptions['id'];
		}

		if ($this->class || (isset($fieldOptions['class']) && $fieldOptions['class']))
		{
			$imgattr['class'] = $this->class . (isset($fieldOptions['class']) ? ' ' . $fieldOptions['class'] : '');
		}

		if ($this->element['style'])
		{
			$imgattr['style'] = (string) $this->element['style'];
		}

		if ($this->element['width'])
		{
			$imgattr['width'] = (string) $this->element['width'];
		}

		if ($this->element['height'])
		{
			$imgattr['height'] = (string) $this->element['height'];
		}

		if ($this->element['align'])
		{
			$imgattr['align'] = (string) $this->element['align'];
		}

		if ($this->element['rel'])
		{
			$imgattr['rel'] = (string) $this->element['rel'];
		}

		if ($this->element['alt'])
		{
			$alt = JText::_((string) $this->element['alt']);
		}
		else
		{
			$alt = null;
		}

		if ($this->element['title'])
		{
			$imgattr['title'] = JText::_((string) $this->element['title']);
		}

		$directory = '';

		if ($this->element['directory'])
		{
			$directory = (string) $this->element['directory'];
			$directory = trim($directory, '/\\') . '/';
		}

		$imagePath = $directory . $this->value;

        $platform = $this->form->getContainer()->platform;
        $baseDirs = $platform->getPlatformBaseDirs();

		if ($this->value && file_exists($baseDirs['root'] . '/' . $imagePath))
		{
			$src = $platform->URIroot() . '/' . $imagePath;
            return JHtml::image($src, $alt, $imgattr);
		}

        // JHtml::image returns weird stuff when an empty path is provided, so let's be safe than sorry and return empty
        return '';
	}
}
