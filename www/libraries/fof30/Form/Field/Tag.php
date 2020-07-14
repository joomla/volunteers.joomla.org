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

defined('_JEXEC') or die;

\JFormHelper::loadFieldClass('tag');

/**
 * Form Field class for FOF
 * Tag Fields
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class Tag extends \JFormFieldTag implements FieldInterface
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
	 * @since 2.1
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
		$id    = isset($fieldOptions['id']) ? 'id="' . $fieldOptions['id'] . '" ' : '';
		$class = $this->class . (isset($fieldOptions['class']) ? ' ' . $fieldOptions['class'] : '');

		$front_link = $this->element['front_link'] ? (string) $this->element['front_link'] : false;
		$translate  = $this->element['translate'] ? (string) $this->element['translate'] : false;

		$tagIds = is_array($this->value) ? implode(',', $this->value) : $this->value;

		if (!$this->item instanceof DataModel)
		{
			$this->item = $this->form->getModel();
		}

		if ($tagIds && $this->item instanceof DataModel)
		{
			$db = $this->form->getContainer()->platform->getDbo();
			$query = $db->getQuery(true)
				->select(array($db->quoteName('id'), $db->quoteName('title')))
				->from($db->quoteName('#__tags'))
				->where($db->quoteName('id') . ' IN (' . $tagIds . ')');
			$query->order($db->quoteName('title'));

			$db->setQuery($query);
			$tags = $db->loadObjectList();

			$html = '';

			foreach ($tags as $tag)
			{
				$html .= '<span>';

				if ($front_link)
				{
					\JLoader::register('TagsHelperRoute', \JPATH_SITE . '/components/com_tags/helpers/route.php');

					$html .= '<a href="' . \JRoute::_(\TagsHelperRoute::getTagRoute($tag->id)) . '">';
				}

				if ($translate == true)
				{
					$html .= \JText::_($tag->title);
				}
				else
				{
					$html .= $tag->title;
				}

				if ($front_link)
				{
					$html .= '</a>';
				}

				$html .= '</span>';
			}
		}

		return '<span ' . ($id ? $id : '') . 'class="' . $class . '">' .
			$html .
			'</span>';
	}
}
