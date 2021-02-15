<?php
/**
 * @package     SSO.Component
 *
 * @author      RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright   Copyright (C) 2017 - 2021 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://rolandd.com
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use SimpleSAML\Logger;

defined('_JEXEC') or die;

/**
 * SimpleSAMLphp config helper.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class SsoConfig
{
	/**
	 * The configuration
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	private $config;

	/**
	 * The name of the configuration file
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	private $filename = JPATH_LIBRARIES . '/simplesamlphp/config/config.php';

	/**
	 * Constructor.
	 *
	 * @param   bool  $loadDefaults  Set if the default values should be loaded
	 *
	 * @since   1.0.0
	 */
	public function __construct(bool $loadDefaults = false)
	{
		// Load the configuration file
		$config = [];

		if (file_exists($this->filename))
		{
			require $this->filename;
		}

		/** @var array $config */
		$this->config = new Registry($config);

		if ($loadDefaults)
		{
			$this->loadDefaultSettings();
		}

		// Set a different separator because period is used in names
		$this->config->separator = '~';
	}

	/**
	 * Default values for a clean installation.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function loadDefaultSettings(): void
	{
		$config = Factory::getConfig();

		// Get the subfolder where the site lives
		$uri       = Uri::getInstance();
		$path      = $uri->getScheme() . '://' . $uri->getHost() . '/';
		$subFolder = str_replace($path, '', Uri::root());

		$default = [
			'baseurlpath'            => $subFolder . 'libraries/simplesamlphp/www/',
			'loggingdir'             => $config->get('log_path') . '/',
			'tempdir'                => $config->get('tmp_path') . '/',
			'timezone'               => $config->get('offset'),
			'secretsalt'             => uniqid('', true),
			'auth.adminpassword'     => uniqid('', true),
			'admin.protectindexpage' => true,
			'admin.checkforupdates'  => false,
			'usenewui'               => true,
			'debug'                  => ['saml' => false, 'backtraces' => false, 'validatexml' => false],
			'showerrors'             => false,
			'errorreporting'         => false,
			'logging.level'          => Logger::DEBUG,
			'logging.handler'        => 'file',
			'store.type'             => 'sql',
			'store.sql.dsn'          => str_replace('mysqli', 'mysql', $config->get('dbtype'))
				. ':dbname=' . $config->get('db') . ';host=' . $config->get('host'),
			'store.sql.username'     => $config->get('user'),
			'store.sql.password'     => $config->get('password'),
			'store.sql.prefix'       => substr($config->get('dbprefix'), 0, -1),
		];

		$this->config->loadArray($default);
	}

	/**
	 * Get a value from the config.
	 *
	 * @param   string  $name     The name of the setting
	 * @param   mixed   $default  The default value to use
	 *
	 * @return  mixed  The value for the given entry or default.
	 *
	 * @since   1.0.0
	 */
	public function get(string $name, $default = null)
	{
		return $this->config->get($name, $default);
	}

	/**
	 * Get a value from the config.
	 *
	 * @param   string  $name   The name of the setting
	 * @param   mixed   $value  The value to set
	 *
	 * @return  mixed  The value for the given entry or default.
	 *
	 * @since   1.0.0
	 */
	public function set(string $name, $value = null)
	{
		return $this->config->set($name, $value);
	}

	/**
	 * Remove a value from the config.
	 *
	 * @param   string  $name  The name of the setting
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function remove(string $name): void
	{
		$this->config->remove($name);
	}

	/**
	 * Write out the configuration file.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function write(): void
	{
		$config = var_export(json_decode($this->config->toString(), true), true);

		file_put_contents($this->filename, '<?php' . "\r\n" . '$config = ' . $config . ';');
	}
}
