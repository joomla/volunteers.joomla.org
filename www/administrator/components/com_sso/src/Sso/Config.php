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

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use SimpleSAML\Logger;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

defined('_JEXEC') or die;

/**
 * SimpleSAMLphp config helper.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class Config
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
	private string $filename = PATH_SIMPLESAMLPHP . '/config/config.php';

    /**
     * Load the configuration file
     *
     * @param   bool  $loadDefaults  Set if the default values should be loaded
     *
     * @throws Exception
     * @since   1.0.0
     */
	public function __construct(bool $loadDefaults = false)
	{
		$config = [];

		if (file_exists($this->filename))
		{
			require $this->filename;
		}

		$this->config = new Registry($config, '~');

		if ($loadDefaults)
		{
			$this->loadDefaultSettings();
		}
	}

    /**
     * Default values for a clean installation.
     *
     * @return  void
     *
     * @throws  Exception
     * @since   1.0.0
     */
	private function loadDefaultSettings(): void
	{
		$config = Factory::getApplication()->getConfig();

		$uri       = Uri::getInstance();
		$path      = $uri->getScheme() . '://' . $uri->getHost() . '/';
		$subFolder = str_replace($path, '', Uri::root());

        $dsn = function () use ($config) {
            $host = $config->get('host');
            $port = 3306;
            if (str_contains($host, ':')) {
                [$host, $port] = explode(':', $host, 2);
            }
            return str_replace('mysqli', 'mysql', $config->get('dbtype'))
                . ':dbname=' . $config->get('db') . ';host=' . $host . ';port=' . $port;
        };

		$default = [
			'baseurlpath'            => $subFolder . 'libraries/simplesamlphp/public/',
			'loggingdir'             => $config->get('log_path') . '/',
			'cachedir'               => JPATH_ADMINISTRATOR . '/cache/simplesamlphp/',
			'tempdir'                => $config->get('tmp_path') . '/',
			'timezone'               => $config->get('offset'),
			'secretsalt'             => uniqid('', true),
			'auth.adminpassword'     => uniqid('', true),
			'admin.checkforupdates'  => false,
			'debug'                  => ['saml' => false, 'backtraces' => false, 'validatexml' => false],
			'showerrors'             => false,
			'errorreporting'         => false,
			'logging.level'          => Logger::NOTICE,
			'logging.handler'        => 'file',
			'store.type'             => 'sql',
			'store.sql.dsn'          => $dsn(),
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
	public function set(string $name, mixed $value = null):  mixed
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

        file_put_contents($this->filename, '<?php' . "\r\n" . '$httpUtils = new \SimpleSAML\Utils\HTTP();' . "\r\n" . '$config = ' . $config . ';');
	}

    /**
     * Hash a password using Argon2ID.
     *
     * Taken from libraries/simplesamlphp/bin/pwgen.php
     *
     * @return  string  The hashed password.
     *
     * @since   2.0.1
     */
    public function hashPassword(string $password): string
    {
        $hasher = new NativePasswordHasher(
            4, // time cost
            65536, // memory cost
            null, // cost
            PASSWORD_ARGON2ID,
        );

        return $hasher->hash($password);
    }
}
