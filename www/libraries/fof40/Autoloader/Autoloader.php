<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Autoloader;

defined('_JEXEC') || die;

// Do not put the JEXEC or die check on this file (necessary omission for testing)
use InvalidArgumentException;

/**
 * A PSR-4 class autoloader. This is a modified version of Composer's ClassLoader class
 *
 * @codeCoverageIgnore
 */
class Autoloader
{
	/**
	 * Class aliases. Maps an old, obsolete class name to the new one.
	 *
	 * @var array
	 */
	protected static $aliases = [
		'FOF40\Utils\CacheCleaner'                => 'FOF40\JoomlaAbstraction\CacheCleaner',
		'FOF40\Utils\ComponentVersion'            => 'FOF40\Utils\ComponentVersion',
		'FOF40\Utils\DynamicGroups'               => 'FOF40\JoomlaAbstraction\DynamicGroups',
		'FOF40\Utils\FEFHelper\BrowseView'        => 'FOF40\Html\FEFHelper\BrowseView',
		'FOF40\Utils\InstallScript\BaseInstaller' => 'FOF40\InstallScript\BaseInstaller',
		'FOF40\Utils\InstallScript\Component'     => 'FOF40\InstallScript\Component',
		'FOF40\Utils\InstallScript\Module'        => 'FOF40\InstallScript\Module',
		'FOF40\Utils\InstallScript\Plugin'        => 'FOF40\InstallScript\Plugin',
		'FOF40\Utils\InstallScript'               => 'FOF40\InstallScript\Component',
		'FOF40\Utils\Ip'                          => 'FOF40\IP\IPHelper',
		'FOF40\Utils\SelectOptions'               => 'FOF40\Html\SelectOptions',
		'FOF40\Utils\TimezoneWrangler'            => 'FOF40\Date\TimezoneWrangler',
	];

	/** @var   Autoloader  The static instance of this autoloader */
	private static $instance;

	/** @var   array  Lengths of PSR-4 prefixes */
	private $prefixLengths = [];

	/** @var   array  Prefix to directory map */
	private $prefixDirs = [];

	/** @var   array  Fall-back directories */
	private $fallbackDirs = [];

	/**
	 * @return Autoloader
	 */
	public static function getInstance(): self
	{
		if (!is_object(self::$instance))
		{
			self::$instance = new Autoloader();
		}

		return self::$instance;
	}

	/**
	 * Returns the prefix to directory map
	 *
	 * @return  array
	 *
	 * @noinspection PhpUnused
	 */
	public function getPrefixes(): array
	{
		return $this->prefixDirs;
	}

	/**
	 * Returns the list of fall=back directories
	 *
	 * @return  array
	 *
	 * @noinspection PhpUnused
	 */
	public function getFallbackDirs(): array
	{
		return $this->fallbackDirs;
	}

	/**
	 * Registers a set of PSR-4 directories for a given namespace, either
	 * appending or prefixing to the ones previously set for this namespace.
	 *
	 * @param   string        $prefix   The prefix/namespace, with trailing '\\'
	 * @param   array|string  $paths    The PSR-0 base directories
	 * @param   boolean       $prepend  Whether to prefix the directories
	 *
	 * @return  $this for chaining
	 *
	 * @throws  InvalidArgumentException  When the prefix is invalid
	 */
	public function addMap(string $prefix, $paths, bool $prepend = false): self
	{
		if (!is_string($paths) && !is_array($paths))
		{
			throw new InvalidArgumentException(sprintf('%s::%s -- $paths expects a string or array', __CLASS__, __METHOD__));
		}

		if ($prefix !== '')
		{
			$prefix = ltrim($prefix, '\\');
		}

		if ($prefix === '')
		{
			// Register directories for the root namespace.
			if ($prepend)
			{
				$this->fallbackDirs = array_merge(
					(array) $paths,
					$this->fallbackDirs
				);

				$this->fallbackDirs = array_unique($this->fallbackDirs);
			}
			else
			{
				$this->fallbackDirs = array_merge(
					$this->fallbackDirs,
					(array) $paths
				);

				$this->fallbackDirs = array_unique($this->fallbackDirs);
			}
		}
		elseif (!isset($this->prefixDirs[$prefix]))
		{
			// Register directories for a new namespace.
			$length = strlen($prefix);
			if ('\\' !== $prefix[$length - 1])
			{
				throw new InvalidArgumentException("A non-empty PSR-4 prefix must end with a namespace separator.");
			}
			$this->prefixLengths[$prefix[0]][$prefix] = $length;
			$this->prefixDirs[$prefix]                = (array) $paths;
		}
		elseif ($prepend)
		{
			// Prepend directories for an already registered namespace.
			$this->prefixDirs[$prefix] = array_merge(
				(array) $paths,
				$this->prefixDirs[$prefix]
			);

			$this->prefixDirs[$prefix] = array_unique($this->prefixDirs[$prefix]);
		}
		else
		{
			// Append directories for an already registered namespace.
			$this->prefixDirs[$prefix] = array_merge(
				$this->prefixDirs[$prefix],
				(array) $paths
			);

			$this->prefixDirs[$prefix] = array_unique($this->prefixDirs[$prefix]);
		}

		return $this;
	}

	/**
	 * Does the autoloader have a map for the specified prefix?
	 *
	 * @param   string  $prefix
	 *
	 * @return  bool
	 */
	public function hasMap($prefix)
	{
		return isset($this->prefixDirs[$prefix]);
	}

	/**
	 * Registers a set of PSR-4 directories for a given namespace,
	 * replacing any others previously set for this namespace.
	 *
	 * @param   string        $prefix  The prefix/namespace, with trailing '\\'
	 * @param   array|string  $paths   The PSR-4 base directories
	 *
	 * @return  void
	 *
	 * @throws InvalidArgumentException When the prefix is invalid
	 * @noinspection PhpUnused
	 */
	public function setMap(string $prefix, $paths)
	{
		if ($prefix !== '')
		{
			$prefix = ltrim($prefix, '\\');
		}

		if ($prefix === '')
		{
			$this->fallbackDirs = (array) $paths;
		}
		else
		{
			$length = strlen($prefix);
			if ('\\' !== $prefix[$length - 1])
			{
				throw new InvalidArgumentException("A non-empty PSR-4 prefix must end with a namespace separator.");
			}
			$this->prefixLengths[$prefix[0]][$prefix] = $length;
			$this->prefixDirs[$prefix]                = (array) $paths;
		}
	}

	/**
	 * Registers this instance as an autoloader.
	 *
	 * @param   boolean  $prepend  Whether to prepend the autoloader or not
	 *
	 * @return  void
	 */
	public function register($prepend = false)
	{
		spl_autoload_register([$this, 'loadClass'], true, $prepend);
	}

	/**
	 * Unregisters this instance as an autoloader.
	 *
	 * @return  void
	 */
	public function unregister()
	{
		spl_autoload_unregister([$this, 'loadClass']);
	}

	/**
	 * Loads the given class or interface.
	 *
	 * @param   string  $class  The name of the class
	 *
	 * @return  boolean|null True if loaded, null otherwise
	 */
	public function loadClass($class)
	{
		if (class_exists($class, false))
		{
			return null;
		}

		if ($file = $this->findFile($class))
		{
			/** @noinspection PhpIncludeInspection */
			include $file;

			return true;
		}

		if (array_key_exists($class, self::$aliases))
		{
			$newClass          = self::$aliases[$class];
			$foundAliasedClass = $this->loadClass($newClass);

			if ($foundAliasedClass === true)
			{
				class_alias($newClass, $class);

				return true;
			}
		}

		return null;
	}

	/**
	 * Finds the path to the file where the class is defined.
	 *
	 * @param   string  $class  The name of the class
	 *
	 * @return  string|false  The path if found, false otherwise
	 */
	public function findFile($class)
	{
		// work around for PHP 5.3.0 - 5.3.2 https://bugs.php.net/50731
		if ('\\' == $class[0])
		{
			$class = substr($class, 1);
		}

		// FEFHelper lookup
		if (substr($class, 0, 7) == 'FEFHelp' && file_exists($file = realpath(__DIR__ . '/..') . '/Html/FEFHelper/' . strtolower(substr($class, 7)) . '.php'))
		{
			return $file;
		}

		// PSR-4 lookup
		$logicalPath = strtr($class, '\\', DIRECTORY_SEPARATOR) . '.php';

		$first = $class[0];

		if (isset($this->prefixLengths[$first]))
		{
			foreach ($this->prefixLengths[$first] as $prefix => $length)
			{
				if (0 === strpos($class, $prefix))
				{
					foreach ($this->prefixDirs[$prefix] as $dir)
					{
						if (file_exists($file = $dir . DIRECTORY_SEPARATOR . substr($logicalPath, $length)))
						{
							return $file;
						}
					}
				}
			}
		}

		// PSR-4 fallback dirs
		foreach ($this->fallbackDirs as $dir)
		{
			if (file_exists($file = $dir . DIRECTORY_SEPARATOR . $logicalPath))
			{
				return $file;
			}
		}

		return false;
	}
}

// Register the current namespace with the autoloader
Autoloader::getInstance()->addMap('FOF40\\', [realpath(__DIR__ . '/..')]);
Autoloader::getInstance()->register();
