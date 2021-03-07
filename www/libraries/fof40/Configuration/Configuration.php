<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Configuration;

use FOF40\Container\Container;

defined('_JEXEC') || die;

/**
 * Reads and parses the fof.xml file in the back-end of a FOF-powered component,
 * provisioning the data to the rest of the FOF framework
 *
 * @since    2.1
 */
class Configuration
{
	/**
	 * Cache of FOF components' configuration variables
	 *
	 * @var array
	 */
	public static $configurations = [];

	/**
	 * The component's container
	 *
	 * @var  Container
	 */
	protected $container;

	function __construct(Container $c)
	{
		$this->container = $c;

		$this->parseComponent();
	}

	/**
	 * Returns the value of a variable. Variables use a dot notation, e.g.
	 * view.config.whatever where the first part is the domain, the rest of the
	 * parts specify the path to the variable.
	 *
	 * @param   string  $variable  The variable name
	 * @param   mixed   $default   The default value, or null if not specified
	 *
	 * @return  mixed  The value of the variable
	 */
	public function get(string $variable, $default = null)
	{
		static $domains = null;

		if (is_null($domains))
		{
			$domains = $this->getDomains();
		}

		[$domain, $var] = explode('.', $variable, 2);

		if (!in_array(ucfirst($domain), $domains))
		{
			return $default;
		}

		$class = '\\FOF40\\Configuration\\Domain\\' . ucfirst($domain);
		/** @var   \FOF40\Configuration\Domain\DomainInterface $o */
		$o = new $class;

		return $o->get(self::$configurations[$this->container->componentName], $var, $default);
	}

	/**
	 * Parses the configuration of the specified component
	 *
	 * @return  void
	 */
	protected function parseComponent(): void
	{
		if ($this->container->platform->isCli())
		{
			$order = ['cli', 'backend'];
		}
		elseif ($this->container->platform->isBackend())
		{
			$order = ['backend'];
		}
		else
		{
			$order = ['frontend'];
		}

		$order[] = 'common';

		$order                                                 = array_reverse($order);
		self::$configurations[$this->container->componentName] = [];

		foreach ([false, true] as $userConfig)
		{
			foreach ($order as $area)
			{
				$config                                                = $this->parseComponentArea($area, $userConfig);
				self::$configurations[$this->container->componentName] = array_replace_recursive(self::$configurations[$this->container->componentName], $config);
			}
		}
	}

	/**
	 * Parses the configuration options of a specific component area
	 *
	 * @param   string  $area        Which area to parse (frontend, backend, cli)
	 * @param   bool    $userConfig  When true the user configuration (fof.user.xml) file will be read
	 *
	 * @return  array  A hash array with the configuration data
	 */
	protected function parseComponentArea(string $area, bool $userConfig = false): array
	{
		$component = $this->container->componentName;

		// Initialise the return array
		$ret = [];

		// Get the folders of the component
		$componentPaths = $this->container->platform->getComponentBaseDirs($component);
		$filesystem     = $this->container->filesystem;
		$path           = $componentPaths['admin'];

		if (isset($this->container['backEndPath']))
		{
			$path = $this->container['backEndPath'];
		}

		// This line unfortunately doesn't work with Unit Tests because JPath depends on the JPATH_SITE constant :(
		// $path = $filesystem->pathCheck($path);

		// Check that the path exists
		if (!$filesystem->folderExists($path))
		{
			return $ret;
		}

		// Read the filename if it exists
		$filename = $path . '/fof.xml';

		if ($userConfig)
		{
			$filename = $path . '/fof.user.xml';
		}

		if (!$filesystem->fileExists($filename) && !file_exists($filename))
		{
			return $ret;
		}

		$data = file_get_contents($filename);

		// Load the XML data in a SimpleXMLElement object
		$xml = simplexml_load_string($data);

		if (!($xml instanceof \SimpleXMLElement))
		{
			return $ret;
		}

		// Get this area's data
		$areaData = $xml->xpath('//' . $area);

		if (empty($areaData))
		{
			return $ret;
		}

		$xml = array_shift($areaData);

		// Parse individual configuration domains
		$domains = $this->getDomains();

		foreach ($domains as $dom)
		{
			$class = '\\FOF40\\Configuration\\Domain\\' . ucfirst($dom);

			if (class_exists($class, true))
			{
				/** @var   \FOF40\Configuration\Domain\DomainInterface $o */
				$o = new $class;
				$o->parseDomain($xml, $ret);
			}
		}

		// Finally, return the result
		return $ret;
	}

	/**
	 * Gets a list of the available configuration domain adapters
	 *
	 * @return  array  A list of the available domains
	 */
	protected function getDomains(): array
	{
		static $domains = [];

		if (empty($domains))
		{
			$filesystem = $this->container->filesystem;

			$files = $filesystem->folderFiles(__DIR__ . '/Domain', '.php');

			if (!empty($files))
			{
				foreach ($files as $file)
				{
					$domain = basename($file, '.php');

					if ($domain == 'DomainInterface')
					{
						continue;
					}

					$domain    = preg_replace('/[^A-Za-z0-9]/', '', $domain);
					$domains[] = $domain;
				}

				$domains = array_unique($domains);
			}
		}

		return $domains;
	}

}
