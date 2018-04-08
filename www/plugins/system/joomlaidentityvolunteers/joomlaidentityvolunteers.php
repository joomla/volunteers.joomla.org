<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.joomlaidentityvolunteers
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;

// No direct access.
defined('_JEXEC') or die;

/**
 * Joomla Identity Plugin class
 *
 * @since 1.0
 */
class PlgSystemJoomlaIdentityVolunteers extends CMSPlugin
{
	/**
	 * Application object.
	 *
	 * @var    JApplicationCms
	 * @since  1.0
	 */
	protected $app;

	/**
	 * Method triggered in processing Joomla identity data
	 *
	 * @param   integer $userId Joomla User ID
	 * @param   string  $guid   GUID of user
	 * @param   object  $data   Object containing user data
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onProcessIdentity($userId, $guid, $data)
	{
		// Update volunteer
		$this->updateVolunteer($userId, $guid, $data);
	}

	/**
	 * Method to update the volunteer data
	 *
	 * @param   integer $userId Joomla User ID
	 * @param   string  $guid   GUID of user
	 * @param   object  $data   Object containing user data
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function updateVolunteer($userId, $guid, $data)
	{
		// Consent date
		$volunteer = (object) array(
			'user_id'             => $userId,
			'alias'               => JApplicationHelper::stringURLSafe($data->name),
			'address'             => $data->address,
			'city'                => $data->city,
			'city-location'       => $data->city,
			'region'              => $data->region,
			'zip'                 => $data->zip,
			'country'             => $data->country,
			'intro'               => $data->intro,
			'joomlastory'         => $data->joomlastory,
			'image'               => $data->image,
			'facebook'            => $data->facebook,
			'twitter'             => $data->twitter,
			'googleplus'          => $data->googleplus,
			'linkedin'            => $data->linkedin,
			'website'             => $data->website,
			'github'              => $data->github,
			'certification'       => $data->certification,
			'stackexchange'       => $data->stackexchange,
			'joomlastackexchange' => $data->joomlastackexchange,
			'latitude'            => $data->latitude,
			'longitude'           => $data->longitude,
			'joomlaforum'         => $data->joomlaforum,
			'joomladocs'          => $data->joomladocs,
			'crowdin'             => $data->crowdin,
			'guid'                => $guid
		);

		try
		{
			Factory::getDbo()->insertObject('#__volunteers_volunteers', $volunteer, 'user_id');
		}
		catch (Exception $e)
		{
			Factory::getDbo()->updateObject('#__volunteers_volunteers', $volunteer, array('user_id'));
		}
	}
}
