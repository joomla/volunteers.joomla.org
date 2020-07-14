<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Form\Field;

use FOF30\Container\Container;
use FOF30\Form\FieldInterface;
use FOF30\Form\Form;
use FOF30\Model\DataModel;
use FOF30\View\View;

defined('_JEXEC') or die;

\JFormHelper::loadFieldClass('text');

/**
 * Form Field class for the FOF framework
 * Displays a view template loaded from an outside source
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class ViewTemplate extends \JFormField implements FieldInterface
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
	 * @param   string $name The property name for which to the the value.
	 *
	 * @return  mixed  The property value or null.
	 *
	 * @since   2.0
	 */
	public function __get($name)
	{
		switch ($name)
		{
			case 'input':
				if (empty($this->input))
				{
					$this->input = $this->getInput();
				}

				return $this->input;
				break;

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
		return $this->getRenderedTemplate();
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
		return $this->getRenderedTemplate(true);
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   11.1
	 */
	protected function getInput()
	{
		return $this->getRenderedTemplate();
	}

	/**
	 * Returns the rendered view template
	 *
	 * @return string
	 */
	protected function getRenderedTemplate($isRepeatable = false)
	{
		$sourceTemplate = isset($this->element['source']) ? (string) $this->element['source'] : null;
		$sourceView = isset($this->element['source_view']) ? (string) $this->element['source_view'] : null;
		$sourceViewType = isset($this->element['source_view_type']) ? (string) $this->element['source_view_type'] : 'html';
		$sourceComponent = isset($this->element['source_component']) ? (string) $this->element['source_component'] : null;

		if (empty($sourceTemplate))
		{
			return '';
		}

		$sourceContainer = empty($sourceComponent) ? $this->form->getContainer() : Container::getInstance($sourceComponent);

		if (empty($sourceView))
		{
			$viewObject = new View($sourceContainer, array(
				'name' => 'FAKE_FORM_VIEW'
			));
		}
		else
		{
			$viewObject = $sourceContainer->factory->view($sourceView, $sourceViewType);
		}

		$viewObject->populateFromModel($this->form->getModel());

		return $viewObject->loadAnyTemplate($sourceTemplate, array(
			'model'        => $isRepeatable ? $this->item : $this->form->getModel(),
			'rowid'        => $isRepeatable ? $this->rowid : null,
			'form'         => $this->form,
			'formType'     => $this->form->getAttribute('type', 'edit'),
			'fieldValue'   => $this->value,
			'fieldElement' => $this->element,
		));
	}
}
