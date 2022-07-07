<?php

defined('JPATH_PLATFORM') or die;

JFormHelper::loadFieldClass('list');

class JFormFieldTagList extends JFormFieldList
{
    /**
     * The form field type.
     *
     * @var string
     *
     * @since  11.1
     */
    public $type = 'TagList';

    /**
	 * Name of the layout being used to render the field
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	//protected $layout = 'joomla.form.field.tag';

    /**
	 * Method to get the field input for a tag field.
	 *
	 * @return  string  The field input.
	 *
	 * @since   3.1
	 */
	/*protected function getInput()
	{
		$data = $this->getLayoutData();

        // Get the field id
		$id    = isset($this->element['id']) ? $this->element['id'] : null;
		$cssId = '#' . $this->getId($id, $this->element['name']);

        \JHtml::_('tag.ajaxfield', $cssId, true);

		if (!\is_array($this->value) && !empty($this->value))
		{
			// String in format 2,5,4
			if (\is_string($this->value))
			{
				$this->value = explode(',', $this->value);
			}

			// Integer is given
			if (\is_int($this->value))
			{
				$this->value = array($this->value);
			}

			$data['value'] = $this->value;
		}

		$data['remoteSearch']  = false;
		$data['options']       = $this->getOptions();
		$data['isNested']      = false;
		$data['allowCustom']   = true;
		$data['minTermLength'] = (int) 3;

		return $this->getRenderer($this->layout)->render($data);
	}*/
}