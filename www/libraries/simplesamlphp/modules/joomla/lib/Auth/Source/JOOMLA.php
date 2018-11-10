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
		// Load Joomla so we can use its functions
		$this->loadJoomla();

		// Get the user details
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select(
				$db->quoteName(
					array(
						'id',
						'password'
					)
				)
			)
			->from($db->quoteName('#__users'))
			->where($db->quoteName('block') . ' = 0')
			->where($db->quoteName('username') . ' = ' . $db->quote($username));

		$db->setQuery($query);
		$result = $db->loadObject();

		if (is_null($result))
		{
			// No rows returned - invalid username/password.
			Logger::error('Joomla:' .
				': No rows in result set. Probably wrong username/password.'
			);

			throw new SimpleSAML_Error_Error('WRONGUSERPASS');
		}

		// Verify the users password
		$match      = UserHelper::verifyPassword($password, $result->password, $result->id);
		$attributes = array();

		if ($match === true)
		{
			// Bring this in line with the rest of the system
			$user                         = User::getInstance($result->id);
			$attributes['emailaddress'][] = $user->email;
			$attributes['name'][]         = $user->name;
			$attributes['upn'][]          = $user->username;
		}

		Logger::info('Joomla:' . $this->authId . ': Attributes: ' .
			implode(',', array_keys($attributes))
		);

		return $attributes;
	}

	/**
	 * Minimal configuration to load Joomla.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function loadJoomla()
	{
		// Tell Joomla we can be trusted
		if (!defined('_JEXEC'))
		{
			define('_JEXEC', 1);
		}

		// Set the base folder
		$base = dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))));

		// Load the Joomla Framework
		require_once $base . '/import.php';

		// Require the classmap file so Joomla can match the old names
		require $base . '/classmap.php';

		// Load system defines
		if (!defined('JPATH_BASE'))
		{
			define('JPATH_BASE', dirname($base));
		}

		require_once dirname($base) . '/includes/defines.php';

		// Register the library base path for CMS libraries.
		JLoader::registerPrefix('J', JPATH_PLATFORM . '/cms', false, true);

		JLoader::registerNamespace('Joomla\\CMS', JPATH_PLATFORM . '/src', false, false, 'psr4');
		$loader = require $base . '/vendor/autoload.php';
		$loader->unregister();

		// Decorate Composer autoloader
		spl_autoload_register(array(new JClassLoader($loader), 'loadClass'), true, true);
	}
}
