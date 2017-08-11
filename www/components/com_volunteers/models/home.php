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
		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		$query
			->select($db->quoteName(array('a.id', 'a.alias', 'user.name', 'a.latitude', 'a.longitude', 'a.image')))
			->from($db->quoteName('#__volunteers_volunteers') . ' AS a')
			->join('LEFT', '#__users AS ' . $db->quoteName('user') . ' ON user.id = a.user_id')
			->where($db->quoteName('latitude') . ' not like \'\'')
			->where($db->quoteName('longitude') . ' not like \'\'');

		$db->setQuery($query);

		$volunteers = $db->loadObjectList();

		// Map markers
		$markers = array();

		if ($volunteers)
		{
			// Base Joomlers url
			$joomlers = JRoute::_('index.php?option=com_volunteers&view=volunteers');

			foreach ($volunteers as $volunteer)
			{
				$markers[] = json_encode(array(
					'title'   => $volunteer->name,
					'lat'     => $volunteer->latitude,
					'lng'     => $volunteer->longitude,
					'url'     => $joomlers . '/' . $volunteer->id . '-' . $volunteer->alias,
					'image'   => VolunteersHelper::image($volunteer->image, 'small', true)
				));
			}
		}

		return $markers;
	}
}
