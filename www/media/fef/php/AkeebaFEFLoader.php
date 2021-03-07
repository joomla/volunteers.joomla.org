<?php
/**
 * Akeeba Frontend Framework (FEF)
 *
 * @package       fef
 * @copyright (c) 2017-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license       GNU General Public License version 3, or later
 */

/**
 * Loads Akeeba FEF CSS and JavaScript files. Cross-platform.
 */
class AkeebaFEFLoader
{
	/**
	 * Script dependencies, as loaded from dependencies.json
	 *
	 * @var   array
	 * @since 2.0.0
	 */
	private $dependenciesCache = [];

	/**
	 * Is the Akeeba FEF CSS Framework already loaded?
	 *
	 * @var   bool
	 * @since 2.0.0
	 */
	private $loadedCSSFramework = false;

	/**
	 * Is the Akeeba FEF JavaScript Framework already loaded?
	 *
	 * 0: no; 1: minimal; 2: full
	 *
	 * @var   int
	 * @since 2.0.0
	 */
	private $loadedJSFramework = 0;

	/**
	 * The platform-specific callback for loading an FEF CSS file given its basename.
	 *
	 * @var callable
	 * @since 2.0.0
	 */
	private $cssLoaderCallback;

	/**
	 * The platform-specific callback for loading an FEF JavaScript file given its basename.
	 *
	 * @var callable
	 * @since 2.0.0
	 */
	private $jsLoaderCallback;

	/**
	 * The platform we are currently running under. Auto-detected if not set.
	 *
	 * @var   null|string
	 * @since 2.0.0
	 */
	private $platform;

	/**
	 * Records the FEF JS files which have already been loaded.
	 *
	 * @var   array
	 * @since 2.0.0
	 */
	private $loadedScripts = [];

	public function __construct(callable $cssLoaderCallback, callable $jsLoaderCallback, ?string $platform)
	{
		$this->cssLoaderCallback = $cssLoaderCallback;
		$this->jsLoaderCallback  = $jsLoaderCallback;
		$this->platform          = $platform ?? $this->getPlatform();

		if (!in_array($this->platform, ['joomla', 'wordpress', 'standalone']))
		{
			$this->platform = $this->getPlatform();
		}
	}

	/**
	 * Loads the Akeeba Frontend Framework, both CSS and JS
	 *
	 * @param   bool  $withReset  Should I also load the CSS reset for the FEF container? (Only applies to Joomla)
	 * @param   bool  $dark       Include Dark Mode CSS?
	 *
	 * @return  void
	 * @since   1.0.3
	 */
	public function loadCSSFramework(bool $withReset = true, bool $dark = false)
	{
		if ($this->loadedCSSFramework)
		{
			return;
		}

		$this->loadedCSSFramework = true;

		if ($withReset && ($this->platform === 'joomla'))
		{
			call_user_func($this->cssLoaderCallback, 'fef-reset');
		}

		call_user_func($this->cssLoaderCallback, 'fef-' . $this->platform);

		if ($dark)
		{
			call_user_func($this->cssLoaderCallback, 'dark');
		}
	}

	/**
	 * Loads the Akeeba FEF JavaScript Framework
	 *
	 * @param   bool  $minimal  Should I load the minimal framework (without optional features linked to FEF CSS?)
	 *
	 * @return  void
	 * @since   2.0.0
	 */
	public function loadJSFramework(bool $minimal = false): void
	{
		$requested = $minimal ? 1 : 2;

		if ($this->loadedJSFramework >= $requested)
		{
			return;
		}

		//  Should I load Akeeba.Loader? Check performed before I change the loadedJSFramework value.
		$initLoader = $this->loadedJSFramework === 0;

		// Update $this->loadedJSFramework so that loadFEFScript actually loads the scripts we tell it!
		$this->loadedJSFramework = $requested;

		// Always load the Akeeba.Loader script first.
		if ($initLoader)
		{
			$this->loadFEFScript('Loader', false);
		}

		if ($requested === 1)
		{
			// Minimal framework: only load System
			$this->loadFEFScript('System');
		}
		else
		{
			// Full framework. Load the core FEF scripts. This pulls in System and its dependencies.
			if ($this->platform !== 'joomla')
			{
				$this->loadFEFScript('Menu');
			}

			$this->loadFEFScript('Tabs');
			$this->loadFEFScript('Dropdown');
		}
	}

	/**
	 * Has Akeeba FEF been already loaded?
	 *
	 * @return  bool
	 * @since   2.0.0
	 */
	public function isLoadedCSSFramework(): bool
	{
		return $this->isLoadedCSSFramework();
	}

	/**
	 * Load an Akeeba FEF JavaScript file and its dependencies.
	 *
	 * @param   string  $name   The basename of the file, e.g. "Tabs"
	 * @param   bool    $defer  Should I defer loading of the file?
	 *
	 * @since   2.0.0
	 */
	public function loadFEFScript(string $name, bool $defer = true): void
	{
		// Make sure FEF is loaded at all
		if (!$this->loadedJSFramework)
		{
			return;
		}

		// If the script is already loaded return early.
		if (in_array($name, $this->loadedScripts))
		{
			return;
		}

		$this->loadedScripts[] = $name;

		// Never defer loading for the Akeeba.Loader
		$defer    = $defer && ($name !== 'Loader');

		// Try to find the requested file
		$testFile = sprintf("%s/../js/%s.min.js", __DIR__, $name);

		if (!@is_file($testFile))
		{
			// Did someone use the legacy name of a JS file?
			$name     = ucfirst($name);
			$testFile = sprintf("%s/../js/%s.min.js", __DIR__, $name);
		}

		if (!@is_file($testFile))
		{
			return;
		}

		// Load any dependencies before the script itself
		$dependencies = $this->getDependencies($name);

		foreach ($dependencies as $dependency)
		{
			$this->loadFEFScript($dependency);
		}

		// Use the platform-specific callback to actually load the script
		call_user_func($this->jsLoaderCallback, $name, $defer);
	}


	/**
	 * Returns the auto-detected platform FEF is running under: joomla, wordpress or standalone.
	 *
	 * @return  string
	 * @since   2.0.0
	 * @see     self::setPlatform
	 */
	private function getPlatform(): string
	{
		if (defined('_JEXEC'))
		{
			return 'joomla';
		}

		if (defined('WPINC'))
		{
			return 'wordpress';
		}

		return 'standalone';
	}

	/**
	 * Get the dependencies of an FEF script
	 *
	 * @param   string  $script
	 *
	 * @return  array
	 * @since   2.0.0
	 */
	private function getDependencies(string $script): array
	{
		if (empty($this->dependenciesCache))
		{
			self::loadDependencies();
		}

		$dependencies = $this->dependenciesCache[$script] ?? [];

		return is_array($dependencies) ? $dependencies : [$dependencies];
	}

	/**
	 * Load the FEF script dependencies
	 *
	 * @return  void
	 * @since   2.0.0
	 */
	private function loadDependencies(): void
	{
		// Reset the dependencies cache
		$this->dependenciesCache = [];

		// Make sure dependencies.json exists
		$sourceFile = __DIR__ . '/../js/dependencies.json';

		if (!@file_exists($sourceFile) || !@is_file($sourceFile) || !@is_readable($sourceFile))
		{
			return;
		}

		// Try to load the JSON file contents
		$dependenciesJSON = @file_get_contents($sourceFile);

		if (($dependenciesJSON === false) || empty(trim($dependenciesJSON)))
		{
			return;
		}

		// Try to decode the JSON file
		try
		{
			$this->dependenciesCache = @json_decode($dependenciesJSON, true, 512);
		}
		catch (Exception $e)
		{
			$this->dependenciesCache = null;
		}

		// Make sure the dependencies cache is an array
		$this->dependenciesCache = $this->dependenciesCache ?? [];

		if (!is_array($this->dependenciesCache))
		{
			$this->dependenciesCache = [];
		}
	}
}