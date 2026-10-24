<?php
/**
 * PHPFinder – Locate the PHP CLI binary on the server.
 *
 * @package      PHPFinder
 * @copyright    (c) 2024-2025 Akeeba Ltd
 * @license      MIT
 *
 * @noinspection PhpUnusedPrivateFieldInspection
 */

namespace Akeeba\PHPFinder;

/**
 * PHPFinder Configuration
 *
 * See the individual property comments for a description on what each property controls.
 *
 * @property bool $useConstants
 * @property bool $osSpecific
 * @property bool $softwareSpecific
 * @property bool $extendedBinaryNameSearch
 * @property bool $useWhich
 * @property bool $useUpdateAlternatives
 * @property bool $useWhereIs
 * @property bool $useWhere
 * @property bool $usePHPCommonWinDirsSearch
 * @property bool $validateVersion
 * @property bool $validateCli
 *
 * @method self setUseConstants(bool $value)
 * @method self setOsSpecific(bool $value)
 * @method self setSoftwareSpecific(bool $value)
 * @method self setExtendedBinaryNameSearch(bool $value)
 * @method self setUseWhich(bool $value)
 * @method self setUseUpdateAlternatives(bool $value)
 * @method self setUseWhereIs(bool $value)
 * @method self setUseWhere(bool $value)
 * @method self setUsePHPCommonWinDirsSearch(bool $value)
 * @method self setValidateVersion(bool $value)
 * @method self setValidateCli(bool $value)
 */
final class Configuration
{
	/**
	 * Use PHP constants to find the PHP executable?
	 *
	 * This is superfast, but it only really works if your script is launched from the CLI, using the same PHP version
	 * you are looking for.
	 *
	 * Default: true
	 *
	 * @var    bool
	 * @since  1.0.0
	 */
	private $useConstants = true;

	/**
	 * Use OS-specific search methods.
	 *
	 * PHPFinder will use OS-specific commands to locate the possible PHP binary names. Some of these methods are fast,
	 * some are not. It is strongly recommended you leave this enabled, and disable any slow methods using the other
	 * properties.
	 *
	 * Default: true
	 *
	 * @var    bool
	 * @since  1.0.0
	 */
	private $osSpecific = true;

	/**
	 * Use methods specific to common prepackaged AMP environments, and common hosting control panels.
	 *
	 * This will look for the most common PHP paths in XAMPP, MAMP (Pro), WAMPServer, cPanel, and Plesk. These are
	 * fairly fast, but you may want to disable them if your software will never run on these environments, or you are
	 * not interested in supporting these environments.
	 *
	 * Default: true
	 *
	 * @var    bool
	 * @since  1.0.0
	 */
	private $softwareSpecific = true;

	/**
	 * Enable to search for uncommon PHP binary names.
	 *
	 * When disabled, PHPFinder will look for the standard php, phpXY, and phpX.Y binary names. It will also search for
	 * the cPanel-specific ea-phpXY and CloudLinux alt-phpXY binaries.
	 *
	 * When enabled, it will search for a permutation of various uncommon binary names such as php-cli, phpXY-cli etc.
	 *
	 * Default: true.
	 *
	 * @var    bool
	 * @since  1.0.0
	 */
	private $extendedBinaryNameSearch = true;

	/**
	 * Use `which` to locate the PHP binary.
	 *
	 * Available on Linux and macOS. Moderately slow (about 0.1 second).
	 *
	 * Default: true
	 *
	 * @var    bool
	 * @since  1.0.0
	 */
	private $useWhich = true;

	/**
	 * Use `update-alternatives` to locate the PHP binary.
	 *
	 * Available on Linux (Debian and derivatives). Very fast.
	 *
	 * Default: true
	 *
	 * @var    bool
	 * @since  1.0.0
	 */
	private $useUpdateAlternatives = true;

	/**
	 * Use `whereis` to locate the PHP binary.
	 *
	 * Available on Linux and macOS. Very slow (1+ second).
	 *
	 * Default: false
	 *
	 * @var    bool
	 * @since  1.0.0
	 */
	private $useWhereIs = false;

	/**
	 * Use `where` to locate the PHP binary.
	 *
	 * Available on Windows. Fast.
	 *
	 * Default: true
	 *
	 * @var    bool
	 * @since  1.0.0
	 */
	private $useWhere = true;

	/**
	 * Search for common PHP installation directories on Windows.
	 *
	 * This looks for PHP installations in d:\PHP, d:\PHPxy, d:\PHPx.y, d:\PHPx.y.z, as well as Program Files and
	 * Program Files (x86). In the above d is one of the available drive letters, and x.y.z is the PHP version you are
	 * looking for.
	 *
	 * This is moderately slow; it depends on how many drive letters you have, and what they are mapped to.
	 *
	 * Default: true
	 *
	 * @var    bool
	 * @since  1.0.0
	 */
	private $usePHPCommonWinDirsSearch = true;

	/**
	 * Validate the version of returned binary.
	 *
	 * When enabled, the binary will be executed with the -v option to allow us to validate that the version it returns
	 * is indeed to version we were told to look for.
	 *
	 * @var    bool
	 * @since  1.0.0
	 */
	private $validateVersion = true;

	/**
	 * Validate the returned binary is a CLI executable.
	 *
	 * Requires $validateVersion.
	 *
	 * When enabled, the result of php_binary -v will be inspected for the `(cli)` specifier which indicates it is a
	 * CLI executable. If disabled, it is possible to get the path to the PHP-CGI binary instead.
	 *
	 * @var    bool
	 * @since  1.0.0
	 */
	private $validateCli = true;

	/**
	 * Returns a new object instance.
	 *
	 * @param   array  $config  The configuration to apply.
	 *
	 * @return  self
	 * @since   1.0.0
	 */
	public static function make(array $config = []): self
	{
		$configuration = new self();

		foreach ($config as $key => $value)
		{
			$configuration->{$key} = $value;
		}

		return $configuration;
	}

	/**
	 * Magic property getter.
	 *
	 * @param   string  $name  The property name to get.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function __get($name)
	{
		if (property_exists($this, $name))
		{
			return $this->{$name};
		}

		throw new \InvalidArgumentException(
			sprintf('Undefined property: %s::$%s', get_class($this), $name)
		);
	}

	/**
	 * Magic property setter.
	 *
	 * @param   string  $name   The property name to set.
	 * @param   bool    $value  The value to set the property to.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function __set($name, $value)
	{
		if (property_exists($this, $name))
		{
			$this->{$name} = @boolval($value);
		}

		throw new \InvalidArgumentException(
			sprintf('Undefined property: %s::$%s', get_class($this), $name)
		);
	}

	/**
	 * Magic function call handler.
	 *
	 * Allows you to set configuration parameters using virtual setPropertyName methods which return $this for chaining.
	 *
	 * @param   string  $name       The method name
	 * @param   array   $arguments  Its parameters
	 *
	 * @return  $this  Self for chaining calls
	 * @since   1.0.0
	 */
	public function __call($name, $arguments)
	{
		if (substr($name, 0, 3) !== 'set')
		{
			throw new \BadMethodCallException(
				sprintf('Call to undefined method %s::%s()', get_class($this), $name)
			);
		}

		$name = substr($name, 3);
		$name = strtolower(substr($name, 0, 1)) . substr($name, 1);

		$this->{$name} = boolval($arguments[0]);

		return $this;
	}
}