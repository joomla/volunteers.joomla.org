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
	}
}
