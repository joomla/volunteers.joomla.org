<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * Volunteers list controller class.
 */
class VolunteersControllerVolunteers extends JControllerAdmin
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
	public function getModel($name = 'Volunteer', $prefix = 'VolunteersModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}

	/**
	 * Reset spam counter
	 *
	 * @since 1.0
	 */
	public function resetspam()
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		/** @var $model VolunteersModelVolunteers */
		$model = $this->getModel('volunteers');
		$model->resetSpam();

		$this->setMessage(JText::_('COM_VOLUNTEERS_MESSAGE_RESET_SUCCESS'));
		$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=volunteers', false));
	}
}
