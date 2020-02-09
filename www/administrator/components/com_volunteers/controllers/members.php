<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;

// No direct access.
defined('_JEXEC') or die;

/**
 * Members list controller class.
 */
class VolunteersControllerMembers extends JControllerAdmin
{
	/**
	 * The headers in the csv export
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	private $headerFields
		= array(
			"\xEF\xBB\xBF" . 'Name',
			'E-mail',
			'Position',
			'Team'
		);

	/**
	 * Proxy for getModel
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  The array of possible config values. Optional.
	 *
	 * @return  object  The model.
	 * @since 1.0.0
	 */
	public function getModel($name = 'Member', $prefix = 'VolunteersModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}

	/**
	 * Export members to csv
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function export()
	{
		// Check for request forgeries.
		$this->checkToken();

		/** @var VolunteersModelMembers $model */
		$model = JModelLegacy::getInstance('Members', 'VolunteersModel', array('ignore_request' => false));
		$items = $model->getItems();

		// Output the data in csv format
		$date     = new Date('now', new DateTimeZone('UTC'));
		$filename = 'members_' . $date->format('Y-m-d_His');

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=' . $filename . '.csv');

		$outstream = fopen("php://output", 'w');

		// Insert headers
		fputcsv($outstream, $this->headerFields);

		foreach ($items as $item)
		{
			fputcsv(
				$outstream, array(
					$item->volunteer_name,
					$item->user_email,
					$item->position_title,
					$item->team_title,
				)
			);
		}

		// Push to the browser so it starts a download
		Factory::$application->close();
	}

	/**
	 * Export members to csv
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function mail()
	{
		// Check for request forgeries.
		$this->checkToken();

		/** @var VolunteersModelMembers $model */
		$model = JModelLegacy::getInstance('Members', 'VolunteersModel', array('ignore_request' => false));
		$items = $model->getItems();

		$members = [];

		foreach ($items as $item)
		{
			$members[] = [
				'name'     => $item->volunteer_name,
				'email'    => $item->user_email,
				'position' => $item->position_title,
				'team'     => $item->team_title
			];
		}

		Factory::getSession()->set('volunteers.recipients', $members);

		$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=contact', false));
	}
}
