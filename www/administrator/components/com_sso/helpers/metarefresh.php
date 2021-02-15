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
 * SimpleSAMLphp metarefresh helper.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class SsoMetarefresh
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
	private $filename = JPATH_LIBRARIES . '/simplesamlphp/config/config-metarefresh.php';

	/**
	 * Constructor.
	 *
	 * @param   bool  $loadDefaults  Set if the default values should be loaded
	 *
	 * @since   1.0.0
	 */
	public function __construct(bool $loadDefaults = false)
	{
		$config = [];

		// Load the configuration file
		if (file_exists($this->filename))
		{
			require $this->filename;
		}

		/** @var array $config */
		$this->config = new Registry($config);

		// Set a different separator because period is used in names
		$this->config->separator = '~';
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
