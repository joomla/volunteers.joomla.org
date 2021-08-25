<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_installer
 *
 * @copyright   (C) 2010 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JLoader::register('InstallerHelper', JPATH_ADMINISTRATOR . '/components/com_installer/helpers/installer.php');

JFormHelper::loadFieldClass('list');

/**
 * Form field for a list of extension types.
 *
 * @since  3.5
 */
class JFormFieldType extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var	   string
	 * @since  3.5
	 */
	protected $type = 'Type';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   3.5
	 */
	public function getOptions()
	{
		$options = InstallerHelper::getExtensionTypes();

		return array_merge(parent::getOptions(), $options);
	}
}
