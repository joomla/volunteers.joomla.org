<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * Registration model class.
 */
class VolunteersModelHome extends JModelList
{
	/**
	 * Method to get Latest Reports.
	 *
	 * @param   integer $pk The id of the team.
	 *
	 * @return  mixed  Data object on success, false on failure.
	 */
	public function getLatestReports()
	{
		// Get reports
		$model = JModelLegacy::getInstance('Reports', 'VolunteersModel', array('ignore_request' => true));
		$model->setState('list.limit', 3);

		return $model->getItems();
	}

	/**
	 * Method to get Latest Volunteers.
	 *
	 * @param   integer $pk The id of the team.
	 *
	 * @return  mixed  Data object on success, false on failure.
	 */
	public function getLatestVolunteers()
	{
		// Get reports
		$model = JModelLegacy::getInstance('Volunteers', 'VolunteersModel', array('ignore_request' => true));
		$model->setState('list.limit', 5);
		$model->setState('list.ordering', 'a.created');
		$model->setState('list.direction', 'desc');
		$model->setState('filter.image', 1);

		return $model->getItems();
	}

	/**
	 * Method to get Volunteer Story.
	 *
	 * @param   integer $pk The id of the team.
	 *
	 * @return  mixed  Data object on success, false on failure.
	 */
	public function getVolunteerStory()
	{
		// Get reports
		$model = JModelLegacy::getInstance('Volunteers', 'VolunteersModel', array('ignore_request' => true));
		$model->setState('list.limit', 1);
		$model->setState('list.ordering', 'rand()');
		$model->setState('filter.image', 1);
		$model->setState('filter.joomlastory', 1);

		$items = $model->getItems();

		return $items[0];
	}

	/**
	 * Method to get Markers for Google Map.
	 *
	 * @param   integer $pk The id of the team.
	 *
	 * @return  mixed  Data object on success, false on failure.
	 */
	public function getMapMarkers()
	{
		// Get reports
		$model = JModelLegacy::getInstance('Volunteers', 'VolunteersModel', array('ignore_request' => true));
		$model->setState('filter.location', 1);
		$items = $model->getItems();

		// Map markers
		$markers = array();

		if($items) array_walk($items, function ($item) use (&$markers)
		{
			array_push($markers, json_encode(array(
				'title'   => $item->firstname . ' ' . $item->lastname,
				'lat'     => $item->latitude,
				'lng'     => $item->longitude,
				'url'     => JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $item->id),
				'address' => VolunteersHelper::location($item->city, $item->country),
				'logo'    => 'images/volunteers/' . $item->image
			)));
		});

		return $markers;
	}
}
