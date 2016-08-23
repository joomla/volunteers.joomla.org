<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * Volunteers Component Controller
 */
class VolunteersController extends JControllerLegacy
{
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->addModelPath(JPATH_ADMINISTRATOR . '/components/com_volunteers/models', 'VolunteersModel');
		JFormHelper::addFormPath(JPATH_ADMINISTRATOR . '/components/com_volunteers/models/forms');
	}

	/**
	 * Method to display a view.
	 */
	public function display($cachable = true, $urlparams = false)
	{
		// Get the document object.
		$document = JFactory::getDocument();

		// Set the default view name and format from the Request.
		$id      = $this->input->getInt('id');
		$vName   = $this->input->getCmd('view');
		$vFormat = $document->getType();
		$lName   = $this->input->getCmd('layout', 'default');

		// Switch view
		if ($view = $this->getView($vName, $vFormat))
		{
			// Do any specific processing by view.
			switch ($vName)
			{
				case 'my':
					$model = $this->getModel('Volunteer', 'VolunteersModel');
					break;

				default:
					$model = $this->getModel($vName, 'VolunteersModel');
			}

			// Push the model into the view (as default).
			if (isset($model) && $model)
			{
				$view->setModel($model, true);
			}

			$view->setLayout($lName);

			// Push document object into the view.
			$view->document = $document;

			$view->display();
		}

		// Check for edit department form.
		if ($vName == 'department' && $lName == 'edit' && !$this->checkEditId('com_volunteers.edit.department', $id))
		{
			// Somehow the person just went to the form - we don't allow that.
			return JError::raiseError(403, JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
		}

		// Check for edit member form.
		if ($vName == 'member' && $lName == 'edit' && !$this->checkEditId('com_volunteers.edit.member', $id))
		{
			// Somehow the person just went to the form - we don't allow that.
			return JError::raiseError(403, JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
		}

		// Check for edit report form.
		if ($vName == 'report' && $lName == 'edit' && !$this->checkEditId('com_volunteers.edit.report', $id))
		{
			// Somehow the person just went to the form - we don't allow that.
			return JError::raiseError(403, JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
		}

		// Check for edit role form.
		if ($vName == 'role' && $lName == 'edit' && !$this->checkEditId('com_volunteers.edit.role', $id))
		{
			// Somehow the person just went to the form - we don't allow that.
			return JError::raiseError(403, JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
		}

		// Check for edit team form.
		if ($vName == 'team' && $lName == 'edit' && !$this->checkEditId('com_volunteers.edit.team', $id))
		{
			// Somehow the person just went to the form - we don't allow that.
			return JError::raiseError(403, JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
		}

		// Check for edit volunteer form.
		if ($vName == 'volunteer' && $lName == 'edit' && !$this->checkEditId('com_volunteers.edit.volunteer', $id))
		{
			// Somehow the person just went to the form - we don't allow that.
			return JError::raiseError(403, JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
		}
	}
}
