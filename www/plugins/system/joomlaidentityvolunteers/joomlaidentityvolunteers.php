<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.joomlaidentityvolunteers
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;

defined('_JEXEC') or die;

/**
 * Joomla Identity Plugin class
 *
 * @since 1.0.0
 */
class PlgSystemJoomlaIdentityVolunteers extends CMSPlugin
{
	/**
	 * Application object.
	 *
	 * @var    CMSApplication
	 * @since  1.0
	 */
	protected $app;

	/**
	 * Application object.
	 *
	 * @var    JDatabaseDriver
	 * @since  1.0.0
	 */
	protected $db;

	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  1.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * The required volunteer data
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	private $requiredFields = array(
		'address',
		'city',
		'region',
		'zip',
		'country',
		'intro',
		'joomlastory',
		'image',
		'facebook',
		'twitter',
		'linkedin',
		'website',
		'github',
		'certification',
		'stackexchange',
		'joomlastackexchange',
		'latitude',
		'longitude',
		'joomlaforum',
		'joomladocs',
		'crowdin'
	);

	/**
	 * Method triggered in processing Joomla identity data
	 *
	 * @param   integer  $userId  Joomla User ID
	 * @param   string   $guid    GUID of user
	 * @param   string   $task    Task triggered
	 * @param   object   $data    Object containing user data
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function onProcessIdentity($userId, $guid, $task, $data)
	{
		try
		{
			$this->validateData($data);
			$this->updateVolunteer($userId, $guid, $data);
		}
		catch (Exception $exception)
		{
			echo $exception->getMessage();
		}
	}

	/**
	 * Validate the posted data containing al the required fields.
	 *
	 * @param   object  $data  The data to store
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 * @throws  InvalidArgumentException
	 *
	 */
	private function validateData($data)
	{
		foreach ($this->requiredFields as $field)
		{
			if (!isset($data->$field))
			{
				throw new InvalidArgumentException(Text::sprintf('PLG_SYSTEM_JOOMLAIDENTITYVOLUNTEERS_MISSING_FIELD', $field));
			}
		}
	}

	/**
	 * Method to update the volunteer data
	 *
	 * @param   integer  $userId  Joomla User ID
	 * @param   string   $guid    GUID of user
	 * @param   object   $data    Object containing user data
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function updateVolunteer($userId, $guid, $data)
	{
		// Consent date
		$volunteer = (object) array(
			'user_id'             => $userId,
			'alias'               => ApplicationHelper::stringURLSafe($data->name),
			'address'             => $data->address,
			'city'                => $data->city,
			'city-location'       => $data->city_location,
			'region'              => $data->region,
			'zip'                 => $data->zip,
			'country'             => $data->country,
			'intro'               => $data->intro,
			'joomlastory'         => $data->joomlastory,
			'image'               => ($data->image) ? 'https://identity.joomla.org/' . $data->image : '',
			'facebook'            => $data->facebook,
			'twitter'             => $data->twitter,
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
			'osmAddress'          => $data->osmAddress
		);

		JLog::add(json_encode($volunteer), JLog::INFO, 'idpjvp');

		try
		{
			$this->db->insertObject('#__volunteers_volunteers', $volunteer, 'user_id');
		}
		catch (Exception $e)
		{
			$this->db->updateObject('#__volunteers_volunteers', $volunteer, array('user_id'));
		}
	}
}
