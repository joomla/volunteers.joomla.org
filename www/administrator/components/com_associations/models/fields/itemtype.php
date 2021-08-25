<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_associations
 *
 * @copyright   (C) 2017 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

JLoader::register('AssociationsHelper', JPATH_ADMINISTRATOR . '/components/com_associations/helpers/associations.php');
JFormHelper::loadFieldClass('groupedlist');

/**
 * A drop down containing all component item types that implement associations.
 *
 * @since  3.7.0
 */
class JFormFieldItemType extends JFormFieldGroupedList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 *
	 * @since  3.7.0
	 */
	protected $type = 'ItemType';

	/**
	 * Method to get the field input markup.
	 *
	 * @return  array  The field option objects as a nested array in groups.
	 *
	 * @since   3.7.0
	 *
	 * @throws  UnexpectedValueException
	 */
	protected function getGroups()
	{
		$options    = array();
		$extensions = AssociationsHelper::getSupportedExtensions();

		foreach ($extensions as $extension)
		{
			if ($extension->get('associationssupport') === true)
			{
				foreach ($extension->get('types') as $type)
				{
					$context = $extension->get('component') . '.' . $type->get('name');
					$options[$extension->get('title')][] = JHtml::_('select.option', $context, $type->get('title'));
				}
			}
		}

		// Sort by alpha order.
		uksort($options, 'strnatcmp');

		// Add options to parent array.
		return array_merge(parent::getGroups(), $options);
	}
}
