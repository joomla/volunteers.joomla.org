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

\JFormHelper::loadFieldClass('usergroup');

/**
 * Form Field class for FOF
 * Joomla! user groups
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class UserGroup extends \JFormFieldUsergroup implements FieldInterface
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

		$class = $this->class ? ' class="' . $this->class . '"' : '';

		$params = $this->getOptions();

		$db = $this->form->getContainer()->platform->getDbo();
		$query = $db->getQuery(true)
				->select('a.id AS value, a.title AS text')
				->from('#__usergroups AS a')
				->group('a.id, a.title')
				->order('a.id ASC')
				->order($db->qn('title') . ' ASC');

		// Get the options.
		$options = $db->setQuery($query)->loadObjectList();

		// If params is an array, push these options to the array
		if (is_array($params))
		{
			$options = array_merge($params, $options);
		}

		// If all levels is allowed, push it into the array.
		elseif ($params)
		{
			array_unshift($options, JHtml::_('select.option', '', JText::_('JOPTION_ACCESS_SHOW_ALL_LEVELS')));
		}

		return '<span id="' . $this->id . '" ' . $class . '>' .
			htmlspecialchars(GenericList::getOptionName($options, $this->value), ENT_COMPAT, 'UTF-8') .
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
		if (isset($this->element['legacy']))
		{
			return $this->getInput();
		}

		$class = $this->class ? $this->class : '';

		$db = $this->form->getContainer()->platform->getDbo();
		$query = $db->getQuery(true)
				->select('a.id AS value, a.title AS text')
				->from('#__usergroups AS a')
				->group('a.id, a.title')
				->order('a.id ASC')
				->order($db->qn('title') . ' ASC');

		// Get the options.
		$options = $db->setQuery($query)->loadObjectList();

		return '<span class="' . $this->id . ' ' . $class . '">' .
			htmlspecialchars(GenericList::getOptionName($options, $this->value), ENT_COMPAT, 'UTF-8') .
			'</span>';
	}

	/**
	 * Returns the options for this control
	 *
	 * Adapted from JHtmlAccess::usergroup
	 *
	 * @return  \stdClass[]
	 */
	protected function getOptions()
	{
		$db = $this->form->getContainer()->platform->getDbo();
		$query = $db->getQuery(true)
			->select('a.id AS value, a.title AS text, COUNT(DISTINCT b.id) AS level')
			->from($db->quoteName('#__usergroups') . ' AS a')
			->join('LEFT', $db->quoteName('#__usergroups') . ' AS b ON a.lft > b.lft AND a.rgt < b.rgt')
			->group('a.id, a.title, a.lft, a.rgt')
			->order('a.lft ASC');
		$db->setQuery($query);
		$options = $db->loadObjectList();

		for ($i = 0, $n = count($options); $i < $n; $i++)
		{
			$options[$i]->text = str_repeat('- ', $options[$i]->level) . $options[$i]->text;
		}
	}
}
