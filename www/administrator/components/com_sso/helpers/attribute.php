<?php
/**
 * @package     SSO.Component
 *
 * @author      RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright   Copyright (C) 2017 - 2021 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://rolandd.com
 */

use Joomla\Registry\Registry;

defined('_JEXEC') or die;

/**
 * SimpleSAMLphp attribute helper.
 *
 * @package  SSO.Component
 * @since    1.3.0
 */
class SsoAttribute
{
	/**
	 * The configuration
	 *
	 * @var    Registry
	 * @since  1.3.0
	 */
	private $config;

	/**
	 * The name of the configuration file
	 *
	 * @var    string
	 * @since  1.3.0
	 */
	private $filename;

	/**
	 * Constructor.
	 *
	 * @since   1.3.0
	 */
	public function __construct()
	{
		$this->config = new Registry;

		// Set a different separator because period is used in names
		$this->config->separator = '~';
	}

	/**
	 * Write an attribute map file.
	 *
	 * @return  void
	 *
	 * @since   1.3.0
	 */
	public function write(): void
	{
		$config = var_export(json_decode($this->config->toString(), true), true);
		file_put_contents($this->filename, '<?php' . "\r\n" . '$attributemap = ' . $config . ';');
	}

	/**
	 * @param   string  $filename  The name of the file to write to
	 *
	 * @return  SsoAttribute
	 *
	 * @since   1.3.0
	 */
	public function setFilename(string $filename): SsoAttribute
	{
		$this->filename = $filename;

		return $this;
	}

	/**
	 * Get a value from the config.
	 *
	 * @param   string  $name     The name of the setting
	 * @param   mixed   $default  The default value to use
	 *
	 * @return  mixed  The value for the given entry or default.
	 *
	 * @since   1.3.0
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
	 * @since   1.3.0
	 */
	public function set(string $name, $value = null)
	{
		return $this->config->set($name, $value);
	}
}
