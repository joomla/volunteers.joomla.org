<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * Registration view class for Users.
 */
class VolunteersViewRegistration extends JViewLegacy
{
	protected $data;

	protected $form;

	protected $params;

	protected $state;

	public $document;

	/**
	 * Method to display the view.
	 *
	 * @param   string $tpl The template file to include
	 *
	 * @return  mixed
	 *
	 * @since   1.6
	 */
	public function display($tpl = null)
	{
		// Get the view data.
		$this->data   = $this->get('Data');
		$this->form   = $this->get('Form');
		$this->state  = $this->get('State');
		$this->params = $this->state->get('params');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode('<br />', $errors));

			return false;
		}

		return parent::display($tpl);
	}
}
