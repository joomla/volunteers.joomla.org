<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * Roles list controller class.
 */
class VolunteersControllerRoles extends JControllerAdmin
{
	/**
	 * Proxy for getModel
	 *
	 * @param   string $name   The model name. Optional.
	 * @param   string $prefix The class prefix. Optional.
	 * @param   array  $config The array of possible config values. Optional.
	 *
	 * @return  object  The model.
	 */
	public function getModel($name = 'Role', $prefix = 'VolunteersModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}

	public function getTeamRoles()
	{
		// Get team ID from input
		$app         = JFactory::getApplication();
		$input       = $app->input;
		$team        = $input->getInt('team', 0);
		$currentrole = $input->getInt('role', 0);

		// Get the team roles
		$roles = JHtmlVolunteers::roles($team);

		// Generate option list
		$options   = array();
		$options[] = JHtml::_('select.option', '', JText::_('COM_VOLUNTEERS_SELECT_ROLE'));
		foreach ($roles as $role)
		{
			$options[] = JHtml::_('select.option', $role->value, $role->text);
		}

		// Echo the options
		echo JHtml::_('select.options', $options, 'value', 'text', $currentrole, true);

		// Bye
		$app->close();
	}
}
