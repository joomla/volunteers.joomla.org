<?php
/**
 * @package     SSO.Component
 *
 * @author      RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright   Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://rolandd.com
 */

namespace Rolandd\Component\Sso\Administrator\Sso;

use Joomla\Registry\Registry;

defined('_JEXEC') or die;

/**
 * SimpleSAMLphp cron module config helper.
 *
 * @package  SSO.Component
 * @since    2.4.0
 */
class ModuleCron
{
	/**
	 * The configuration
	 *
	 * @var    Registry
	 * @since  2.4.0
	 */
	private Registry $config;

	/**
	 * The name of the configuration file
	 *
	 * @var    string
	 * @since  2.4.0
	 */
	private string $filename = PATH_SIMPLESAMLPHP . '/config/module_cron.php';

	/**
	 * Constructor.
	 *
	 * @since   2.4.0
	 */
	public function __construct()
	{
		$config = [];

		// Load the configuration file
		if (file_exists($this->filename))
		{
			require $this->filename;
		}

		$this->config = new Registry($config, '~');
	}

	/**
	 * Get a value from the config.
	 *
	 * @param   string  $name     The name of the setting
	 * @param   mixed   $default  The default value to use
	 *
	 * @return  mixed  The value for the given entry or default.
	 *
	 * @since   2.4.0
	 */
	public function get(string $name, mixed $default = null): mixed
	{
		return $this->config->get($name, $default);
	}

	/**
	 * Set a value on the config.
	 *
	 * @param   string  $name   The name of the setting
	 * @param   mixed   $value  The value to set
	 *
	 * @return  mixed  The value for the given entry or default.
	 *
	 * @since   2.4.0
	 */
	public function set(string $name, mixed $value = null): mixed
	{
		return $this->config->set($name, $value);
	}

	/**
	 * Write out the configuration file.
	 *
	 * The cron module (used to trigger the metarefresh cron job) requires this file to be
	 * present, or running the cron hooks will throw an exception. The secret key is generated
	 * once and kept stable across writes; every other value is whatever the caller has set.
	 *
	 * @return  void
	 *
	 * @since   2.4.0
	 */
	public function write(): void
	{
		if ($this->config->get('key') === null)
		{
			$this->config->set('key', bin2hex(random_bytes(16)));
		}

		$config = var_export(json_decode($this->config->toString(), true), true);

		file_put_contents($this->filename, '<?php' . "\r\n" . '$config = ' . $config . ';');
	}
}
