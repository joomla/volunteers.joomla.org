<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Factory\Scaffolding\Layout;

use FOF30\Container\Container;
use SimpleXMLElement;

defined('_JEXEC') or die;

/**
 * Scaffolding Builder
 *
 * Creates an automatic XML form definition to render a view based on the database fields you've got in the model. This
 * is not designed for production; it's designed to give you a way to quickly add some test data to your component
 * and get started really fast with FOF development.
 *
 * @package FOF30\Factory\Scaffolding
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class Builder
{
	/** @var  \FOF30\Container\Container  The container we belong to */
	protected $container = null;

	/** @var  bool  Should I save the scaffolding results? */
	protected $saveScaffolding = false;

	/** @var  SimpleXMLElement  The form we will be returning to the caller */
	protected $xml;

	/** @var  array  Language string definitions we need to add to the component's language file */
	protected $strings = array();

	/**
	 * Create the scaffolding builder instance
	 *
	 * @param \FOF30\Container\Container $c
	 */
	public function __construct(Container $c)
	{
		$this->container = $c;

		$this->saveScaffolding = $this->container->factory->isSaveScaffolding();
	}

	/**
	 * Make a new scaffolding document
	 *
	 * @param   string  $requestedFilename  The requested filename, e.g. form.default.xml
	 * @param   string  $viewName           The name of the view this form will be used to render
	 *
	 * @return  string|null  The XML source or null if we can't make a scaffolding XML
	 */
	public function make($requestedFilename, $viewName)
	{
		// Initialise
		$this->xml = null;
		$this->strings = array();

		// The requested filename should be in the format "form.SOMETHING.xml"
		if (substr($requestedFilename, 0, 5) !== 'form.')
		{
			return null;
		}

		// Get the requested form type
		$formType = substr($requestedFilename, 5);

		// Make sure the requested form type is supported by this builder
		if (!in_array($formType, array('default', 'form', 'item')))
		{
			return null;
		}

		switch ($formType)
		{
			default:
			case 'default':
				$builderType = 'Browse';
				break;

			case 'form':
				$builderType = 'Form';
				break;

			case 'item':
				$builderType = 'Item';
				break;
		}

		// Get the model
		$model = $this->container->factory->model($viewName);

		// Create the scaffolding object and build the XML file
		$className = 'FOF30\\Factory\\Scaffolding\\Layout\\' . $builderType . 'Erector';

		/** @var ErectorInterface $erector */
		$erector = new $className($this, $model, $viewName);
		$erector->build();

		if ($this->saveScaffolding)
		{
			$this->saveXml($requestedFilename, $viewName);
			$this->saveStrings();
		}

		$this->applyStrings();

		return $this->xml->asXML();
	}

	/**
	 * Set the XML form document
	 *
	 * @param   SimpleXMLElement  $xml  The XML document to set
	 */
	public function setXml(SimpleXMLElement $xml)
	{
		$this->xml = $xml;
	}

	/**
	 * Set the additional strings array
	 *
	 * @param   array  $strings  The strings array to set
	 */
	public function setStrings(array $strings)
	{
		$this->strings = $strings;
	}

	/**
	 * Load the strings array in Joomla!'s JLanguage object
	 */
	protected function applyStrings()
	{
		// If we don't have language strings there's no point continuing
		if (empty($this->strings))
		{
			return;
		}

		// Get a temporary filename
		$baseDirs = $this->container->platform->getPlatformBaseDirs();
		$tempDir = $baseDirs['tmp'];
		$filename = tempnam($tempDir, 'fof');

		if ($filename === false)
		{
			return;
		}

		// Save the strings to a temporary file
		$this->saveStrings($filename);

		// Load the temporary file
		$lang = $this->container->platform->getLanguage();
		$langReflection = new \ReflectionObject($lang);
		$loadLangReflection = $langReflection->getMethod('loadLanguage');
		$loadLangReflection->setAccessible(true);
		$loadLangReflection->invoke($lang, $filename, $this->container->componentName);

		// Delete temporary filename
		@unlink($filename);
	}

	/**
	 * Gets the container this builder belongs to
	 *
	 * @return Container
	 */
	public function getContainer()
	{
		return $this->container;
	}

	/**
	 * Save the XML form as a file
	 *
	 * @param   string  $requestedFilename  The requested filename, e.g. form.default.xml
	 * @param   string  $viewName           The name of the view this form will be used to render
	 */
	protected function saveXml($requestedFilename, $viewName)
	{
		$path = $this->container->frontEndPath;

		if ($this->container->platform->isBackend())
		{
			$path = $this->container->backEndPath;
		}

		$targetFilename = $path . '/View/' . $viewName . '/tmpl/' . $requestedFilename;

		$directory = dirname($targetFilename);

		if (!is_dir($directory))
		{
			$createdDirectory = @mkdir($directory, 0755, true);

			if (!@$createdDirectory)
			{
				\JLoader::import('joomla.filesystem.folder');
				\JFolder::create($directory, 0755);
			}
		}

		$xml = $this->xml->asXML();

		$domDocument = new \DOMDocument('1.0');
		$domDocument->loadXML($xml);
		$domDocument->preserveWhiteSpace = false;
		$domDocument->formatOutput = true;
		$xml = $domDocument->saveXML();

		$saveResult = @file_put_contents($targetFilename . '.xml', $xml);

		if ($saveResult === false)
		{
			\JLoader::import('joomla.filesystem.file');
			\JFile::write($targetFilename, $xml);
		}
	}

	/**
	 * Saves the language strings, merged with any old ones, to a Joomla! INI language file
	 *
	 * @param   string  $targetFilename  The full path to the INI file, leave blank for auto-detection
	 */
	protected function saveStrings($targetFilename = null)
	{
		// If no filename is defined, get the component's language definition filename
		if (empty($targetFilename))
		{
			$jLang = $this->container->platform->getLanguage();
			$basePath = $this->container->platform->isBackend() ? JPATH_ADMINISTRATOR : JPATH_SITE;

			$lang = $jLang->setLanguage('en-GB');
			$jLang->setLanguage($lang);

			$path = $jLang->getLanguagePath($basePath, $lang);

			$targetFilename = $path . '/' . $lang . '.' . $this->container->componentName . '.ini';
		}

		// Try to load the existing language file
		$strings = array();

		if (@file_exists($targetFilename))
		{
			$contents = file_get_contents($targetFilename);
			$contents = str_replace('_QQ_', '"\""', $contents);
			$strings = @parse_ini_string($contents);
		}

		$strings = array_merge($strings, $this->strings);

		// Create the INI file
		$iniFile = '';

		foreach ($strings as $k => $v)
		{
			$iniFile .= strtoupper($k) . '="' . str_replace('"', '"_QQ_"', $v) . "\"\n";
		}

		// Save it
		$saveResult = @file_put_contents($targetFilename, $iniFile);

		if ($saveResult === false)
		{
			\JLoader::import('joomla.filesystem.file');
			\JFile::write($targetFilename, $iniFile);
		}
	}
}
