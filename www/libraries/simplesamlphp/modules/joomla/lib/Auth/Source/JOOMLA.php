<?php
/**
 * @package    SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - [year] RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserHelper;
use JoomlaCore\JoomlaCore;
use SimpleSAML\Logger;

/**
 * Joomla authentication source
 *
 * @since  1.0.0
 */
class sspmod_joomla_Auth_Source_JOOMLA extends sspmod_core_Auth_UserPassBase
{
	/**
	 * Attempt to log in using the given username and password.
	 *
	 * On a successful login, this function should return the users attributes. On failure,
	 * it should throw an exception. If the error was caused by the user entering the wrong
	 * username or password, a SimpleSAML_Error_Error('WRONGUSERPASS') should be thrown.
	 *
	 * Note that both the username and the password are UTF-8 encoded.
	 *
	 * @param   string  $username  The username the user wrote.
	 * @param   string  $password  The password the user wrote.
	 *
	 * @return  array  Associative array with the users attributes.
	 *
	 * @throws  Exception
	 *
	 * @since   1.0.0
	 */
	protected function login($username, $password)
	{
		// Load the Joomla helper
		require_once __DIR__ . '/../../../core/joomla.php';
		$joomlaCore = new JoomlaCore;
		$joomlaCore->loadDomain();

		// Get the user details
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select(
				$db->quoteName(
					array(
						'users.id',
						'users.password',
						'profiles.guid'
					)
				)
			)
			->from($db->quoteName('#__users', 'users'))
			->leftJoin(
				$db->quoteName('#__identity_profiles', 'profiles')
				. ' ON ' . $db->quoteName('profiles.user_id') . ' = ' . $db->quoteName('users.id')
			)
			->where($db->quoteName('users.block') . ' = 0')
			->where($db->quoteName('users.email') . ' = ' . $db->quote($username));

		$db->setQuery($query);
		$result = $db->loadObject();

		if (is_null($result) || empty($result->guid))
		{
			// No rows returned - invalid username/password.
			Logger::error('Joomla:' .
				': No rows in result set. Probably wrong username/password.'
			);

			throw new SimpleSAML_Error_Error('WRONGUSERPASS');
		}

		// Verify the users password
		$match = UserHelper::verifyPassword($password, $result->password, $result->id);

		if (!$match)
		{
			throw new SimpleSAML_Error_Error('WRONGUSERPASS');
		}

		$attributes = array();

		// Bring this in line with the rest of the system
		$user                         = User::getInstance($result->id);
		$attributes['emailaddress'][] = $user->email;
		$attributes['name'][]         = $user->name;
		$attributes['upn'][]          = $result->guid;

		// This field is required to handle the consents
		$attributes['guid'][]         = $result->guid;

		// Trigger the onAfterLogin
		$joomlaCore->onAfterLogin($result->guid);

		return $attributes;
	}
}
