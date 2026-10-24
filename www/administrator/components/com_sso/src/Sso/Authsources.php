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
 * SimpleSAMLphp authsources helper.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class Authsources
{
	/**
	 * The configuration
	 *
	 * @var    Registry
	 * @since  1.0.0
	 */
	private Registry $config;

	/**
	 * The name of the configuration file
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	private string $filename = PATH_SIMPLESAMLPHP . '/config/authsources.php';

	/**
	 * Constructor.
	 *
	 * @since   1.0.0
	 */
	public function __construct()
	{
		$config = [];

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
	 * @since   1.0.0
	 */
	public function get(string $name, mixed $default = null): mixed
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
	public function set(string $name, mixed $value = null): mixed
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
		$this->validateSections();
		$config = var_export(json_decode($this->config->toString(), true), true);
		file_put_contents($this->filename, '<?php' . "\r\n" . '$config = ' . $config . ';');
	}

	/**
	 * Validate sections.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function validateSections(): void
	{
		// Check if we have an admin section
		if (!$this->get('admin'))
		{
			$this->set('admin', [0 => 'core:AdminPassword']);
		}

		// Remove the default-sp section
		if ($this->get('default-sp'))
		{
			$this->remove('default-sp');
		}
	}
}
