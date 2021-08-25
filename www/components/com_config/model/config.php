<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_config
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Model for the global configuration
 *
 * @since  3.2
 */
class ConfigModelConfig extends ConfigModelForm
{
	/**
	 * Method to get a form object.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed	A JForm object on success, false on failure
	 *
	 * @since	3.2
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_config.config', 'config', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}
}
