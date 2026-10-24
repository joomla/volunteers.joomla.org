<?php
/**
 * PHPFinder – Locate the PHP CLI binary on the server.
 *
 * @package   PHPFinder
 * @copyright (c) 2024-2025 Akeeba Ltd
 * @license   MIT
 */

namespace Akeeba\PHPFinder;

/**
 * A class to locate the PHP binary path, optionally for a specific version.
 *
 * @license GPL-3.0-or-later
 * @author  Nicholas K. Dionysopoulos
 */
final class PHPFinder
{
	/**
	 * The Configuration object
	 *
	 * @var    Configuration
	 * @since  1.0.0
	 */
	protected $configuration;

	/**
	 * Returns a new object instance.
	 *
	 * @param   Configuration|null  $configuration  The configuration to use.
	 *
	 * @return  self
	 */
	final public static function make(?Configuration $configuration = null): self
	{
		return new self($configuration);
	}

	/**
	 * Public constructor.
	 *
	 * @param   Configuration|null  $configuration  The optional configuration object.
	 *
	 * @since   1.0.0
	 */
	final public function __construct(?Configuration $configuration = null)
	{
		$this->configuration = $configuration ?? new Configuration();
	}

	/**
	 * Get the best guess path for the requested PHP version's CLI executable.
	 *
	 * If the version is not found, it will return NULL.
	 *
	 * If no version is specified, any PHP CLI binary will be returned; not necessarily the one with the latest PHP
	 * version!
	 *
	 * If no suitable executable is found (there is no PHP CLI executable installed, or it uses an uncommon path) NULL
	 * will be returned as well.
	 *
	 * @param   string|null  $version  The version to look for.
	 *
	 * @return  string|null
	 * @since   1.0.0
	 */
	final public function getBestPath(?string $version = null): ?string
	{
		$info = $this->getBestPathMeta($version);

		return $info === null ? null : $info->path;
	}

	/**
	 * Get the best guess path for the requested PHP version's CLI executable along with its metadata.
	 *
	 * See notes on getBestPath.
	 *
	 * @param   string|null  $version  The version to look for.
	 *
	 * @return  null|object{version: string, cli: bool, path: string}
	 * @since   1.0.0
	 * @see     self::getBestPath
	 */
	final public function getBestPathMeta(?string $version = null): ?object
	{
		$possiblePaths = $this->getPossiblePaths($version);

		if (empty($possiblePaths))
		{
			return null;
		}

		foreach ($possiblePaths as $path)
		{
			$info = $this->analyzePHPVersion($path);

			if ($info->version === null)
			{
				continue;
			}

			if ($this->configuration->validateVersion && strpos($info->version, $version) === false)
			{
				continue;
			}

			if ($this->configuration->validateCli && !$info->cli)
			{
				continue;
			}

			$info->path = $path;

			return $info;
		}

		return null;
	}

	/**
	 * Gets all possible PHP CLI paths available on the server.
	 *
	 * The returned paths might NOT correspond to the correct version. Some paths are generic. Use getBestPath() with
	 * default configuration options to guarantee the accuracy of the result.
	 *
	 * @param   string|null  $version  The version to look for.
	 *
	 * @return  array
	 * @since   1.0.0
	 */
	public function getPossiblePaths(?string $version = null): array
	{
		$ret = [];

		// PHP constants
		$ret = array_merge($ret, $this->pathsFromConstants());

		// OS-specific search methods
		$ret = array_merge($ret, $this->pathsUNIX($version));
		$ret = array_merge($ret, $this->pathsUsingWhich($version));
		$ret = array_merge($ret, $this->pathsUsingUpdateAlternatives($version));
		$ret = array_merge($ret, $this->pathsUsingWhereIs($version));
		$ret = array_merge($ret, $this->pathsUsingWhere($version));
		$ret = array_merge($ret, $this->pathsFromCommonWindowsInstallPaths($version));

		// Software-specific
		$ret = array_merge($ret, $this->pathsCPanel($version));
		$ret = array_merge($ret, $this->pathsCloudLinux($version));
		$ret = array_merge($ret, $this->pathsPlesk($version));
		$ret = array_merge($ret, $this->pathsXAMPP($version));
		$ret = array_merge($ret, $this->pathsMAMP($version));
		$ret = array_merge($ret, $this->pathsHomeBrew($version));
		$ret = array_merge($ret, $this->pathsWAMPServer($version));

		return array_filter(
			array_unique($ret),
			function ($filePath) {
				return !empty($filePath) && @is_file($filePath);
			}
		);
	}

	/**
	 * Get possible paths from PHP constants.
	 *
	 * Available on all OS. Fast.
	 *
	 * @return  array
	 * @since   1.0.0
	 */
	private function pathsFromConstants(): array
	{
		if (!$this->configuration->useConstants)
		{
			return [];
		}

		$ret = [];

		defined('PHP_BINARY') && $ret[] = PHP_BINARY;
		/** @noinspection PhpUndefinedConstantInspection */
		defined('PHP_PATH') && $ret[] = PHP_PATH;
		/** @noinspection PhpUndefinedConstantInspection */
		defined('PHP_PEAR_PHP_BIN') && $ret[] = PHP_PEAR_PHP_BIN;

		return $ret;
	}

	/**
	 * Return the default PHP paths for UNIX compatible systems.
	 *
	 * Available on Linux, BSD, and macOS. Very fast.
	 *
	 * @param   string|null  $version  The optional version to look for
	 *
	 * @return  array
	 * @since   1.0.0
	 */
	private function pathsUNIX(?string $version = null): array
	{
		if (
			!$this->configuration->osSpecific
			|| !$this->configuration->useWhich
			|| !in_array(strtoupper(PHP_OS_FAMILY), ['LINUX', 'BSD', 'DARWIN'])
		)
		{
			return [];
		}

		$possibleNames = $this->getBinaryNamesForVersion($version);
		$ret           = [];

		foreach ($possibleNames as $binName)
		{
			$ret[] = '/usr/bin/' . $binName;
			$ret[] = '/usr/local/bin/' . $binName;
			$ret[] = '/opt/bin/' . $binName;
		}

		return $ret;
	}

	/**
	 * Get possible PHP paths using `which`.
	 *
	 * Available on Linux and macOS. Moderately slow (about 100 msec).
	 *
	 * @param   string|null  $version  Optional PHP version to look for
	 *
	 * @return  array
	 * @since   1.0.0
	 */
	private function pathsUsingWhich(?string $version = null): array
	{
		if (
			!$this->configuration->osSpecific
			|| !$this->configuration->useWhich
			|| !in_array(strtoupper(PHP_OS_FAMILY), ['LINUX', 'BSD', 'DARWIN'])
		)
		{
			return [];
		}

		if (!function_exists('exec') || !$this->executableExists('which'))
		{
			return [];
		}

		$possibleExecutables = $this->getBinaryNamesForVersion($version);
		$ret                 = [];

		foreach ($possibleExecutables as $executable)
		{
			$which = @exec('which ' . escapeshellarg($executable) . ' 2>/dev/null', $output, $resultCode);

			if ($which === false || $resultCode !== 0)
			{
				continue;
			}

			$paths = explode("\n", $which);
			$paths = array_filter($paths);

			$ret = array_merge($ret, $paths);
		}

		return $ret;
	}

	/**
	 * Find possible PHP paths using `update-alternatives`.
	 *
	 * Available on Linux (only Debian and derivatives). Fast.
	 *
	 * @param   string|null  $version  Optional PHP version to look for
	 *
	 * @return  array
	 * @since   1.0.0
	 */
	private function pathsUsingUpdateAlternatives(?string $version): array
	{
		if (
			!$this->configuration->osSpecific
			|| !$this->configuration->useUpdateAlternatives
			|| in_array(strtoupper(PHP_OS_FAMILY), ['LINUX', 'BSD'])
		)
		{
			return [];
		}

		if (!function_exists('exec') || !$this->executableExists('update-alternatives'))
		{
			return [];
		}

		$possibleExecutables = $this->getBinaryNamesForVersion($version);
		$ret                 = [];

		$result = @exec('update-alternatives --list php 2>/dev/null', $output);

		if ($result === false)
		{
			return $ret;
		}

		foreach ($output as $line)
		{
			$parts    = explode(DIRECTORY_SEPARATOR, $line);
			$lastPart = end($parts);

			if (in_array($lastPart, $possibleExecutables, true))
			{
				$ret[] = $line;

				continue;
			}

			if (strpos($lastPart, '/') === false && strpos($lastPart, '\\') === false)
			{
				continue;
			}

			$lastPart = str_replace('\\', '/', $lastPart);
			$parts    = explode(DIRECTORY_SEPARATOR, $lastPart);
			$lastPart = end($parts);

			if (in_array($lastPart, $possibleExecutables, true))
			{
				$ret[] = $line;
			}
		}

		return $ret;
	}

	/**
	 * Find possible PHP paths using `whereis`.
	 *
	 * Available on Linux, macOS. Slow (over 1 second).
	 *
	 * @param   string|null  $version  Optional PHP version to look for
	 *
	 * @return  array
	 * @since   1.0.0
	 */
	private function pathsUsingWhereIs(?string $version): array
	{
		if (
			!$this->configuration->osSpecific
			|| !$this->configuration->useWhereIs
			|| !in_array(strtoupper(PHP_OS_FAMILY), ['LINUX', 'BSD', 'DARWIN'])
		)
		{
			return [];
		}

		if (!function_exists('exec') || !$this->executableExists('whereis'))
		{
			return [];
		}

		$possibleExecutables = $this->getBinaryNamesForVersion($version);
		$ret                 = [];

		foreach ($possibleExecutables as $executable)
		{
			$result = @exec('whereis -b ' . escapeshellarg($executable) . ' 2>/dev/null');

			if ($result === false)
			{
				continue;
			}

			[, $rawList] = explode(':', $result);
			$list = explode(' ', $rawList);
			$list = array_filter($list);

			if (empty($list))
			{
				continue;
			}

			foreach ($list as $path)
			{
				if (!@is_file($path))
				{
					continue;
				}

				$ret[] = $path;
			}
		}

		return $ret;
	}

	/**
	 * Get possible PHP paths using `where`.
	 *
	 * Available on Windows. Fast.
	 *
	 * @param   string|null  $version  Optional PHP version to look for
	 *
	 * @return  array
	 * @since   1.0.0
	 */
	private function pathsUsingWhere(?string $version = null): array
	{
		if (
			!$this->configuration->osSpecific
			|| !$this->configuration->useWhere
			|| strtoupper(PHP_OS_FAMILY) !== 'WINDOWS'
		)
		{
			return [];
		}

		if (!function_exists('exec') || !$this->executableExists('where'))
		{
			return [];
		}

		$possibleExecutables = $this->getBinaryNamesForVersion($version);
		$ret                 = [];

		foreach ($possibleExecutables as $executable)
		{
			$where = @exec('where ' . escapeshellarg($executable), $output, $resultCode);

			if ($where === false || $resultCode !== 0)
			{
				continue;
			}

			$paths = explode("\n", $where);
			$paths = array_filter($paths);

			$ret = array_merge($ret, $paths);
		}

		return $ret;
	}

	/**
	 * Get possible paths from common windows PHP installation paths
	 *
	 * @param   string|null  $version  The version to look for
	 *
	 * @return  array
	 * @since   1.0.0
	 */
	private function pathsFromCommonWindowsInstallPaths(?string $version = null): array
	{
		if (
			!$this->configuration->osSpecific
			|| !$this->configuration->usePHPCommonWinDirsSearch
			|| strtoupper(PHP_OS_FAMILY) !== 'WINDOWS'
		)
		{
			return [];
		}

		$possiblePaths = [];

		foreach ($this->getWindowsDrives() as $drive)
		{
			$possiblePaths = array_merge(
				$possiblePaths,
				$this->getWindowsProgramFilesPHPPaths($drive, $version),
				$this->getWindowsProgramFilesPHPPaths($drive . '\\Program Files', $version),
				$this->getWindowsProgramFilesPHPPaths($drive . '\\Program Files (x86)', $version)
			);
		}

		$programFiles    = @getenv('PROGRAMFILES');
		$programFilesx86 = @getenv('PROGRAMFILES(X86)');

		$possiblePaths = array_merge($possiblePaths, $this->getWindowsProgramFilesPHPPaths($programFiles, $version));
		$possiblePaths = array_merge($possiblePaths, $this->getWindowsProgramFilesPHPPaths($programFilesx86, $version));

		$possiblePaths = array_unique($possiblePaths);

		asort($possiblePaths);

		$ret = [];

		$binaryNames = $this->getBinaryNamesForVersion($version);

		foreach ($possiblePaths as $path => $maxNesting)
		{
			$ret = array_merge(
				$ret,
				$this->recursivePathScanner($path, 0, $maxNesting, $binaryNames)
			);
		}

		return array_unique($ret);
	}

	/**
	 * Return cPanel-specific PHP paths. Only works given a version.
	 *
	 * Available on Linux. Very fast.
	 *
	 * @param   string|null  $version  The version to get paths for.
	 *
	 * @return  array
	 * @since   1.0.0
	 */
	private function pathsCPanel(?string $version): array
	{
		/** @noinspection DuplicatedCode */
		if (
			empty($version)
			|| !$this->configuration->softwareSpecific
			|| in_array(strtoupper(PHP_OS_FAMILY), ['LINUX', 'BSD'])
		)
		{
			return [];
		}

		[$major, $minor] = $this->getMajorMinor($version);

		if ($major === null || $minor === null)
		{
			return [];
		}

		$suffix = $major . $minor;

		return [
			'/opt/cpanel/ea-php' . $suffix . '/root/usr/bin/php',
			'/usr/bin/ea-php' . $suffix,
			'/usr/local/bin/ea-php' . $suffix,
		];
	}

	/**
	 * Return CloudLinux-specific PHP paths. Only works given a version.
	 *
	 * Available on Linux. Very fast.
	 *
	 * @param   string|null  $version  The version to get paths for.
	 *
	 * @return  array
	 * @since   1.0.0
	 */
	private function pathsCloudLinux(?string $version): array
	{
		/** @noinspection DuplicatedCode */
		if (
			empty($version)
			|| !$this->configuration->softwareSpecific
			|| in_array(strtoupper(PHP_OS_FAMILY), ['LINUX', 'BSD'])
		)
		{
			return [];
		}

		[$major, $minor] = $this->getMajorMinor($version);

		if ($major === null || $minor === null)
		{
			return [];
		}

		$suffix = $major . $minor;

		return [
			'/opt/cloudlinux/alt-php' . $suffix . '/root/usr/bin/php',
			'/usr/bin/alt-php' . $suffix,
			'/usr/local/bin/alt-php' . $suffix,
		];
	}

	/**
	 * Return Plesk-specific PHP paths. Only works given a version.
	 *
	 * Available on Linux. Very fast.
	 *
	 * @param   string|null  $version  The version to get paths for.
	 *
	 * @return  array
	 * @since   1.0.0
	 */
	private function pathsPlesk(?string $version): array
	{
		if (
			empty($version)
			|| !$this->configuration->softwareSpecific
		)
		{
			return [];
		}

		[$major, $minor] = $this->getMajorMinor($version);

		if ($major === null || $minor === null)
		{
			return [];
		}

		$suffix = $major . '.' . $minor;

		switch (strtoupper(PHP_OS_FAMILY))
		{
			case 'LINUX':
			case 'BSD':
				return [
					'/opt/plesk/php/' . $suffix . '/bin/php',
					'/usr/bin/php' . $suffix,
					'/usr/local/bin/php' . $suffix,
				];

			case 'WINDOWS':
				// Binary found in %plesk_dir%Additional\PHPXX\php.exe
				$pleskDir = @getenv('PLESK_DIR');

				if (empty($pleskDir))
				{
					return [];
				}

				return [
					rtrim($pleskDir, '\\') . '\\Additional\\PHP' . $suffix . '\\php.exe',
				];

			default:
				return [];
		}
	}

	/**
	 * Returns the PHP path for XAMPP.
	 *
	 * This assumes that you are using the default installation path which is one of the following.
	 *
	 * - Windows: C:\xampp on Windows (where C: could be any known drive letter)
	 * - Linux: /opt/lampp
	 * - macOS: /Applications/XAMPP
	 *
	 * @param   string|null  $version  Ignored; XAMPP only has one PHP version installed at a time.
	 *
	 * @return  array
	 * @since   1.0.0
	 */
	private function pathsXAMPP(?string $version): array
	{
		if (!$this->configuration->softwareSpecific)
		{
			return [];
		}

		switch (strtoupper(PHP_OS_FAMILY))
		{
			case 'WINDOWS':
				return array_map(
					function ($drive) {
						return rtrim($drive, '\\') . '\\xampp\\php\\php.exe';
					},
					$this->getWindowsDrives() ?: ['C:']
				);

			case 'LINUX':
			case 'BSD':
				return [
					'/opt/lampp/php/php',
				];

			case 'DARWIN':
				return [
					'/Applications/XAMPP/php/php',
				];

			default:
				return [];
		}
	}

	/**
	 * Get the MAMP / MAMP Pro paths for PHP CLI.
	 *
	 * Available on macOS, and Windows. Fast.
	 *
	 * @param   string|null  $version  The version to get paths for.
	 *
	 * @return  array
	 * @since   1.0.0
	 */
	private function pathsMAMP(?string $version)
	{
		if (!$this->configuration->softwareSpecific)
		{
			return [];
		}

		switch (strtoupper(PHP_OS_FAMILY))
		{
			case 'DARWIN':
				$rootPath = '/Applications/MAMP/bin/php/';
				break;

			case 'WINDOWS':
				$drives = $this->getWindowsDrives();

				foreach ($drives as $drive)
				{
					$rootPath = $drive . '\\MAMP\\bin\\php';

					if (!@is_dir($rootPath))
					{
						continue;
					}

					$rootPath .= '\\';
					break;
				}

				return [];

			default:
				return [];
		}

		if (!@is_dir($rootPath))
		{
			return [];
		}

		return $this->recursivePathScanner($rootPath, 0, 1, ['php']);
	}

	/**
	 * Get the HomeBrew paths for PHP CLI.
	 *
	 * Available on macOS, and Linux. Moderately fast.
	 *
	 * @param   string|null  $version  The version to get paths for.
	 *
	 * @return  array
	 * @since   1.0.0
	 */
	private function pathsHomeBrew(?string $version): array
	{
		if (!$this->configuration->softwareSpecific && !in_array(strtoupper(PHP_OS_FAMILY), ['LINUX', 'BSD', 'DARWIN']))
		{
			return [];
		}

		if (!function_exists('exec') || !$this->executableExists('brew'))
		{
			return [];
		}

		$checkFor = [];

		[$major, $minor] = $this->getMajorMinor($version);

		if ($minor !== null && $major === null)
		{
			$checkFor[] = 'php@' . $major . '.' . $minor;
		}

		$checkFor[] = 'php';

		foreach ($checkFor as $package)
		{
			$result = @exec('brew --prefix ' . escapeshellarg($package));

			if (!$result)
			{
				continue;
			}

			return [$result];
		}

		return [];
	}

	/**
	 * Get the WAMPServer paths for PHP CLI.
	 *
	 * IMPORTANT! This will return the paths to PHP CLI for all installed PHP versions. Unlike most other prepackaged
	 * xAMP servers, WAMPServer allows you to have multiple patch versions of the same minor PHP version installed at
	 * the same time. This makes it impossible to find a specific PHP version if you give only a minor version. This can
	 * be addressed by checking for the exact version during the verification stage.
	 *
	 * Available on Windows. Moderately fast.
	 *
	 * @param   string|null  $version  The version to get paths for.
	 *
	 * @return  array
	 * @since   1.0.0
	 */
	private function pathsWAMPServer(?string $version): array
	{
		if (!$this->configuration->softwareSpecific && strtoupper(PHP_OS_FAMILY) != 'WINDOWS')
		{
			return [];
		}

		$possiblePaths = [];

		foreach ($this->getWindowsDrives() as $drive)
		{
			$possiblePaths[$drive . '\\wamp\\bin\\php']                            = 1;
			$possiblePaths[$drive . '\\wamp64\\bin\\php']                          = 1;
			$possiblePaths[$drive . '\\Program Files (x86)\\WampServer\\bin\\php'] = 1;
			$possiblePaths[$drive . '\\Program Files\\WampServer\\bin\\php']       = 1;
		}

		$possiblePaths = array_filter(
			$possiblePaths,
			function ($path) {
				return @is_dir($path);
			}
		);

		if (empty($possiblePaths))
		{
			return [];
		}

		$ret = [];

		foreach ($possiblePaths as $path => $maxDepth)
		{
			$ret[] = array_merge($ret, $this->recursivePathScanner($path, 0, $maxDepth, ['php']));
		}

		return $ret;
	}

	/**
	 * Extract the major and minor version integers from a version string.
	 *
	 * @param   string|null  $version  The version to parse.
	 *
	 * @return  array  The major and minor versions. NULL elements if invalid version.
	 * @since   1.0.0
	 */
	private function getMajorMinor(?string $version = null): array
	{
		$major = null;
		$minor = null;

		if (empty ($version))
		{
			return [$major, $minor];
		}

		$parts = explode('.', $version);
		$major = @intval($parts[0]);
		$minor = $parts[1] ?? null;
		$minor = $minor === null ? null : @intval($minor);

		$major = $major ?: null;
		$minor = $major === null ? null : ($minor ?: null);

		return [$major, $minor];
	}

	/**
	 * Get a list of possible PHP binary names for a given PHP version.
	 *
	 * Note: this does NOT add the `.exe` suffix on Windows!
	 *
	 * @param   string|null  $version  The optional PHP version
	 *
	 * @return  string[]
	 * @since   1.0.0
	 */
	private function getBinaryNamesForVersion(?string $version = null): array
	{
		$possibleExecutables = ['php'];

		[$major, $minor] = $this->getMajorMinor($version);

		if ($major === null)
		{
			return $possibleExecutables;
		}

		$suffix    = $major . ($minor ?? '');
		$altSuffix = $major . ($minor === null ? '' : ':') . ($minor ?? '');

		$possibleExecutables[] = 'php' . $suffix;
		$possibleExecutables[] = 'php' . $altSuffix;
		$possibleExecutables[] = 'ea-php' . $suffix;
		$possibleExecutables[] = 'alt-php' . $suffix;

		if ($this->configuration->extendedBinaryNameSearch)
		{
			$possibleExecutables[] = 'php-cli';
			$possibleExecutables[] = 'php' . $suffix . '-cli';
			$possibleExecutables[] = 'php' . $altSuffix . '-cli';
			$possibleExecutables[] = 'php-' . $suffix;
			$possibleExecutables[] = 'php-' . $altSuffix;
			$possibleExecutables[] = 'php-' . $suffix . 'cli';
			$possibleExecutables[] = 'php-cli-' . $suffix;
			$possibleExecutables[] = 'php-cli' . $suffix;
		}

		return $possibleExecutables;
	}

	/**
	 * Gets the current list of known Windows drives.
	 *
	 * This uses the Windows Management Instrumentation (wmic), or fsutil. If both fail, it returns only  %SYSTEMDRIVE%.
	 *
	 * @return  string[]
	 * @since   1.0.0
	 */
	private function getWindowsDrives(): array
	{
		static $drives = null;

		if ($drives !== null)
		{
			return $drives;
		}

		$defaultFallback = [getenv('SYSTEMDRIVE') ?: 'C:'];

		if (!function_exists('exec'))
		{
			return $drives = $defaultFallback;
		}

		// First, let's try WMIC (deprecated since Windows 11)
		$result = $this->executableExists('wmic')
			? @exec('wmic logicaldisk get name', $output)
			: false;

		if ($result !== false)
		{
			return $drives = array_filter(
				$output,
				function ($item) {
					return strpos($item, ':') !== false;
				}
			);
		}

		// Use fsutil as a fallback
		$result = $this->executableExists('fsutil')
			? @exec('fsutil fsinfo drives')
			: false;

		if ($result !== false)
		{
			$parts = explode(':', $result, 2);

			return $drives = array_map(
				function ($x) {
					return rtrim(trim($x), '\\');
				},
				array_filter(explode(' ', $parts[1] ?? ''))
			) ?: $defaultFallback;
		}

		// If all else fails, return %SYSTEMDRIVE% or if that's not available just 'C:'.
		return $drives = $defaultFallback;
	}

	/**
	 * Recursively scan paths on Windows for PHP binaries
	 *
	 * @param   string  $path         The path to scan
	 * @param   int     $depth        Current depth
	 * @param   int     $maxNesting   Maximum depth to scan
	 * @param   array   $binaryNames  List of allowed binary names (without the .exe suffix)
	 *
	 * @return  array
	 * @since   1.0.0
	 */
	private function recursivePathScanner(string $path, int $depth, int $maxNesting, array $binaryNames): array
	{
		if (!@is_dir($path))
		{
			return [];
		}

		$ret       = [];
		$isWindows = strtoupper(PHP_OS_FAMILY) === 'WINDOWS';

		/** @var \DirectoryIterator $file */
		foreach (new \DirectoryIterator($path) as $file)
		{
			if ($file->isDot())
			{
				continue;
			}

			if ($file->isDir())
			{
				if ($depth >= $maxNesting)
				{
					continue;
				}

				$ret = array_merge(
					$ret,
					$this->recursivePathScanner($file->getPathName(), $depth + 1, $maxNesting, $binaryNames)
				);

				continue;
			}

			if (!$file->isFile())
			{
				continue;
			}

			if ($isWindows)
			{
				if ($file->getExtension() === 'exe' && in_array($file->getBasename('.exe'), $binaryNames))
				{
					$ret[] = $file->getBasename();
				}

			}
			elseif (in_array($file->getBasename(), $binaryNames))
			{
				$ret[] = $file->getBasename();
			}
		}

		return $ret;
	}

	/**
	 * Analyzes the version information output of the suspected PHP CLI binary.
	 *
	 * @param   string  $path
	 *
	 * @return  object{version: string, cli: bool}
	 * @since   1.0.0
	 */
	private function analyzePHPVersion(string $path): object
	{
		$ret = (object) [
			'version' => null,
			'cli'     => false,
		];

		if (!function_exists('exec'))
		{
			return $ret;
		}

		$result = @file_exists($path)
		          && $this->isExecutable($path)
		          && @exec(escapeshellcmd($path) . ' -v', $output);

		if (!$result)
		{
			return $ret;
		}

		foreach ($output as $line)
		{
			if (substr($line, 0, 4) !== 'PHP ')
			{
				continue;
			}

			// PHP x.y.x (cli) (built: blah blah)
			$parts = explode(' ', $line);

			if (count($parts) < 3)
			{
				return $ret;
			}

			$ret->version = $parts[1];
			$ret->cli     = $parts[2] === '(cli)';

			return $ret;
		}

		return $ret;
	}

	/**
	 * Get Windows PHP paths for a specific Program Files directory
	 *
	 * @param   string       $pathPrefix
	 * @param   string|null  $version
	 *
	 * @return  array
	 * @since   1.0.0
	 */
	private function getWindowsProgramFilesPHPPaths(string $pathPrefix, ?string $version): array
	{
		$possiblePaths = [];

		if (!$pathPrefix)
		{
			return $possiblePaths;
		}

		[$major, $minor] = $this->getMajorMinor();

		$possiblePaths[rtrim($pathPrefix, '\\') . '\\PHP'] = 1;

		if ($minor !== null)
		{
			$possiblePaths[rtrim($pathPrefix, '\\') . '\\PHP' . $major . $minor]       = 0;
			$possiblePaths[rtrim($pathPrefix, '\\') . '\\PHP' . $major . '.' . $minor] = 0;
			$possiblePaths[rtrim($pathPrefix, '\\') . '\\PHP' . $version]              = 0;
		}

		return $possiblePaths;
	}

	/**
	 * Checks whether a command exists in the user's PATH, and points to an executable file.
	 *
	 * @param   string  $command  The command to check for.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	private function executableExists(string $command): bool
	{
		$path = getenv('PATH');

		if (empty($path))
		{
			return false;
		}

		$directories = explode(PATH_SEPARATOR, $path);

		foreach ($directories as $directory)
		{
			$fullPath = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $command;

			if ($this->isExecutable($fullPath))
			{
				return true;
			}
		}

		return false;
	}

	private function isExecutable(string $fullPath): bool
	{
		if (@is_executable($fullPath))
		{
			return true;
		}

		if (
			(strtoupper(PHP_OS_FAMILY) === 'WINDOWS')
			&& (
				@is_executable($fullPath . '.exe')
				|| @is_executable($fullPath . '.com')
				|| @is_executable($fullPath . '.bat')
			)
		)
		{
			return true;
		}

		return false;
	}
}