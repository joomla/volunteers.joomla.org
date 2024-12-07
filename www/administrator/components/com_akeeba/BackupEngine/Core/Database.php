<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Core;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Driver\Base as DriverBase;
use Akeeba\Engine\Driver\Mysqli;
use Akeeba\Engine\Platform;

/**
 * A utility class to return a database connection object
 */
class Database
{
	private static $instances = [];

	/**
	 * Returns a database connection object. It caches the created objects for future use.
	 *
	 * @param   array  $options  The database driver connection options
	 *
	 * @return  DriverBase|object  A DriverBase object or something with magic methods that's compatible with it
	 */
	public static function &getDatabase(array $options)
	{
		// Get the options signature.
		$signature = md5(serialize($options));

		// If there's a cached object return it.
		if (!empty(self::$instances[$signature]))
		{
			return self::$instances[$signature];
		}

		// Get the driver name / class
		$driver = preg_replace('/[^A-Z0-9_\\\.-]/i', '', $options['driver'] ?? '');

		// If there is no driver specified ask the Platform to guess it.
		if (empty($driver))
		{
			$default_signature = md5(serialize(Platform::getInstance()->get_platform_database_options()));
			$driver            = Platform::getInstance()->get_default_database_driver($signature === $default_signature);
		}

		// Ensure we have the FQN of the driver class
		if ((substr($driver, 0, 7) != '\\Akeeba') && substr($driver, 0, 7) != 'Akeeba\\')
		{
			$driver = '\\Akeeba\\Engine\\Driver\\' . ucfirst($driver);
		}

		// Map the legacy MySQL driver to the newer MySQLi
		if (($driver == '\\Akeeba\\Engine\\Driver\\Mysql') && !function_exists('mysql_connect'))
		{
			$driver = Mysqli::class;
		}

		// Translate MySQL SSL options
		if (!isset($options['ssl']) || !is_array($options['ssl']))
		{
			$options['ssl'] = [
				'enable'             => (bool) ($options['dbencryption'] ?? false),
				'cipher'             => ($options['dbsslcipher'] ?? '') ?: '',
				'ca'                 => ($options['dbsslca'] ?? '') ?: '',
				'capath'             => ($options['dbsslcapath'] ?? '') ?: '',
				'key'                => ($options['dbsslkey'] ?? '') ?: '',
				'cert'               => ($options['dbsslcert'] ?? '') ?: '',
				'verify_server_cert' => ($options['dbsslverifyservercert'] ?? false) ?: false,
			];
		}

		// Instantiate the database driver object and return it
		self::$instances[$signature] = new $driver($options);

		return self::$instances[$signature];
	}

	/**
	 * Un-cache a database driver object
	 *
	 * @param   array  $options  The database driver connection options
	 *
	 * @return  void
	 */
	public static function unsetDatabase(array $options): void
	{
		$signature = md5(serialize($options));

		if (!isset(self::$instances[$signature]))
		{
			return;
		}

		unset(self::$instances[$signature]);
	}
}
