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
use JHtml;
use JText;

defined('_JEXEC') or die;

/**
 * Form Field class for FOF
 * Renders the row ordering interface checkbox in browse views
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class Ordering extends \JFormField implements FieldInterface
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
	 * Method to get the field input markup for this field type.
	 *
	 * @since 2.0
	 *
	 * @return  string  The field input markup.
	 */
	protected function getInput()
	{
		$html = array();
		$attr = '';

		// Initialize some field attributes.
		$attr .= !empty($this->class) ? ' class="' . $this->class . '"' : '';
		$attr .= $this->disabled ? ' disabled' : '';
		$attr .= !empty($this->size) ? ' size="' . $this->size . '"' : '';

		// Initialize JavaScript field attributes.
		$attr .= !empty($this->onchange) ? ' onchange="' . $this->onchange . '"' : '';

		$this->item = $this->form->getModel();

		$keyfield = $this->item->getKeyName();
		$itemId   = $this->item->$keyfield;

		$query = $this->getQuery();

		// Create a read-only list (no name) with a hidden input to store the value.
		if ($this->readonly)
		{
			$html[] = JHtml::_('list.ordering', '', $query, trim($attr), $this->value, $itemId ? 0 : 1);
			$html[] = '<input type="hidden" name="' . $this->name . '" value="' . $this->value . '"/>';
		}
		else
		{
			// Create a regular list.
			$html[] = JHtml::_('list.ordering', $this->name, $query, trim($attr), $this->value, $itemId ? 0 : 1);
		}

		return implode($html);
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

		$class = isset($this->class) ? $this->class : 'input-mini';
		$icon  = isset($this->element['icon']) ? $this->element['icon'] : 'icon-menu';
		$dnd = isset($this->element['dragndrop']) ? (string) $this->element['dragndrop'] : 'notbroken';

		if (strtolower($dnd) == 'notbroken')
		{
			$dnd = !version_compare(JVERSION, '3.5.0', 'ge');
		}
		else
		{
			$dnd = in_array(strtolower($dnd), array('1', 'true', 'yes', 'on', 'enabled'), true);
		}

		$html = '';

		$view = $this->form->getView();

		$ordering = $view->getLists()->order == $this->item->getFieldAlias('ordering');

		if ($view->hasAjaxOrderingSupport() === false)
		{
			// Ye olde Joomla! 2.5 method
			$disabled = $ordering ? '' : 'disabled="disabled"';
			$html .= '<span>';
			$html .= $view->getPagination()->orderUpIcon($this->rowid, true, 'orderup', 'Move Up', $ordering);
			$html .= '</span><span>';
			$html .= $view->getPagination()->orderDownIcon($this->rowid, $view->getPagination()->total, true, 'orderdown', 'Move Down', $ordering);
			$html .= '</span>';
			$html .= '<input type="text" name="order[]" size="5" value="' . $this->value . '" ' . $disabled;
			$html .= 'class="text-area-order" style="text-align: center" />';
		}
		else
		{
			// The modern drag'n'drop method
			if ($view->getPerms()->editstate)
			{
				$disableClassName = '';
				$disabledLabel = '';

				$hasAjaxOrderingSupport = $view->hasAjaxOrderingSupport();

				if (!is_array($hasAjaxOrderingSupport) || !$hasAjaxOrderingSupport['saveOrder'])
				{
					$disabledLabel = JText::_('JORDERINGDISABLED');
					$disableClassName = 'inactive tip-top';
				}

				$orderClass = $ordering ? 'order-enabled' : 'order-disabled';

				$html .= '<div class="' . $orderClass . '">';

				if ($dnd)
				{
					$html .= '<span class="sortable-handler ' . $disableClassName . '" title="' . $disabledLabel . '" rel="tooltip">';
					$html .= '<span class="' . $icon . '"></span>';
					$html .= '</span>';
				}

				if ($ordering)
				{
					/**
					 * Joomla! 3.5 and later: drag and drop reordering is broken when the ordering field is not hidden
					 * because some random bloke submitted that code and some supposedly responsible adult with commit
					 * rights committed it. I tried to file a PR to fix it and got the reply "can't test, won't test".
					 * OK, then. You blindly accepted code which did the EXACT OPPOSITE of what it promised and broke
					 * b/c. However, you won't accept the fix to your mess from someone who knows how Joomla! works and
					 * wasted 2 hours of his time to track down your mistake, fix it and explain why your actions
					 * resulted in a b/c break. You have to be kidding me!
					 */
					$joomla35IsBroken = version_compare(JVERSION, '3.5.0', 'ge') ? 'style="display: none"': '';

					// When the developer has disabled Drag and Drop we will show the field regardless
					$joomla35IsBroken = $dnd ? $joomla35IsBroken : '';

					$html .= '<input type="text" name="order[]" ' . $joomla35IsBroken . ' size="5" class="' . $class . ' text-area-order" value="' . $this->value . '" />';
				}

				$html .= '</div>';
			}
			else
			{
				$html .= '<span class="sortable-handler inactive" >';
				$html .= '<i class="' . $icon . '"></i>';
				$html .= '</span>';
			}
		}

		return $html;
	}

	/**
	 * Builds the query for the ordering list.
	 *
	 * @since 2.3.2
	 *
	 * @return \JDatabaseQuery  The query for the ordering form field
	 */
	protected function getQuery()
	{
		$ordering = $this->name;
		$title    = $this->element['ordertitle'] ? (string) $this->element['ordertitle'] : $this->item->getFieldAlias('title');

		$db = $this->form->getContainer()->platform->getDbo();
		$query = $db->getQuery(true);
		$query->select(array($db->quoteName($ordering, 'value'), $db->quoteName($title, 'text')))
				->from($db->quoteName($this->item->getTableName()))
				->order($ordering);

		return $query;
	}
}
