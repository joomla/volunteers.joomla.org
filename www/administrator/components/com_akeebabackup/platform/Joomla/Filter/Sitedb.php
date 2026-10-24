<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Filter;

// Protection against direct access
defined('_JEXEC') || die();

use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;

/**
 * Add site's main database to the backup set.
 */
class Sitedb extends Base
{
	public function __construct()
	{
		// This is a directory inclusion filter.
		$this->object      = 'db';
		$this->subtype     = 'inclusion';
		$this->method      = 'direct';
		$this->filter_name = 'Sitedb';

		// Add a new record for the core Joomla! database
		$configuration = Factory::getConfiguration();

		if ($configuration->get('akeeba.platform.override_db', 0))
		{
			$options = [
				'port'     => $configuration->get('akeeba.platform.dbport', ''),
				'host'     => $configuration->get('akeeba.platform.dbhost', ''),
				'user'     => $configuration->get('akeeba.platform.dbusername', ''),
				'password' => $configuration->get('akeeba.platform.dbpassword', ''),
				'database' => $configuration->get('akeeba.platform.dbname', ''),
				'prefix'   => $configuration->get('akeeba.platform.dbprefix', ''),
				'ssl'      => [
					'enable'             => $configuration->get('akeeba.platform.dbencryption', '0') == 1,
					'cipher'             => $configuration->get('akeeba.platform.dbsslcipher', ''),
					'ca'                 => $configuration->get('akeeba.platform.dbsslca', ''),
					'capath'             => $configuration->get('akeeba.platform.dbsslcapath', ''),
					'key'                => $configuration->get('akeeba.platform.dbsslkey', ''),
					'cert'               => $configuration->get('akeeba.platform.dbsslcert', ''),
					'verify_server_cert' => $configuration->get('akeeba.platform.dbsslverifyservercert', 0) == 1,
				],
			];
			$driver  = '\\Akeeba\\Engine\\Driver\\' . ucfirst($configuration->get('akeeba.platform.dbdriver', 'mysqli'));
		}
		else
		{
			$options = Platform::getInstance()->get_platform_database_options();
			$driver  = Platform::getInstance()->get_default_database_driver(true);
		}

		// This is the format of the database inclusion filters
		$options['ssl'] = $options['ssl'] ?? [];
		$options['ssl'] = is_array($options['ssl']) ? $options['ssl'] : [];

		$entry = [
			'host'                  => ($options['host'] ?? null) ?: null,
			'port'                  => ($options['port'] ?? null) ?: null,
			'socket'                => ($options['socket'] ?? null) ?: null,
			'username'              => $options['user'] ?? null,
			'password'              => $options['password'] ?? null,
			'database'              => $options['database'] ?? null,
			'prefix'                => $options['prefix'] ?? '',
			'dumpFile'              => 'site.sql',
			'driver'                => $driver,
			'dbencryption'          => ($options['ssl']['enable'] ?? false) ? 1 : 0,
			'dbsslcipher'           => ($options['ssl']['cipher'] ?? '') ?: '',
			'dbsslca'               => ($options['ssl']['ca'] ?? '') ?: '',
			'dbsslcapath'           => ($options['ssl']['capath'] ?? '') ?: '',
			'dbsslkey'              => ($options['ssl']['key'] ?? '') ?: '',
			'dbsslcert'             => ($options['ssl']['cert'] ?? '') ?: '',
			'dbsslverifyservercert' => ($options['ssl']['verify_server_cert'] ?? false) ? 1 : 0,
		];

		// We take advantage of the filter class magic to inject our custom filters
		$this->filter_data['[SITEDB]'] = $entry;

		parent::__construct();
	}
}
