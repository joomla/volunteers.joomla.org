<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Form\Field;

use FOF30\Form\FieldInterface;
use JHtml;

defined('_JEXEC') or die;

\JFormHelper::loadFieldClass('list');

/**
 * Form Field class for FOF
 * Supports a drop-down list of Yes/No (boolean) answers.
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class Boolean extends GenericList implements FieldInterface
{

	/**
	 * Method to get the field options.
	 *
	 * Ordering is disabled by default. You can enable ordering by setting the
	 * 'order' element in your form field. The other order values are optional.
	 *
	 * - order					What to order.			Possible values: 'name' or 'value' (default = false)
	 * - order_dir				Order direction.		Possible values: 'asc' = Ascending or 'desc' = Descending (default = 'asc')
	 * - order_case_sensitive	Order case sensitive.	Possible values: 'true' or 'false' (default = false)
	 *
	 * @return  array  The field option objects.
	 *
	 * @since	Ordering is available since FOF 2.1.b2.
	 */
	protected function getOptions()
	{
		$options = parent::getOptions();

		$defaultOptions = array(
			JHtml::_('select.option', 1, \JText::_('JYES')),
			JHtml::_('select.option', 0, \JText::_('JNO')),
		);

		$options = array_merge($defaultOptions, $options);

		return $options;
	}

}
