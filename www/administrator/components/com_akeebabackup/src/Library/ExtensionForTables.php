<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Library;

defined('_JEXEC') || die;

use Akeeba\Engine\Driver\Base as EngineDBDriver;
use Akeeba\Engine\Driver\Query\Base as AbstractEngineQuery;
use Akeeba\Engine\Driver\QueryException;
use Dflydev\DotAccessData\DataInterface;
use Joomla\Component\Installer\Administrator\Helper\InstallerHelper;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\QueryInterface;
use Joomla\Filesystem\Folder;
use PHPSQLParser\PHPSQLParser;
use SimpleXMLElement;

/**
 * An abstraction for a Joomla extension allowing us to get a list of its installed tables.
 *
 * This is based on our work in Akeeba Onthos.
 *
 * @since  10.1.0
 */
final class ExtensionForTables
{
	/**
	 * Extension type (component, plugin, module, template, package, ...)
	 *
	 * @var   string
	 * @since 10.1.0
	 */
	private string $type;

	/**
	 * Extension element
	 *
	 * @var   string
	 * @since 10.1.0
	 */
	private string $element;

	/**
	 * Extension folder (for plugins)
	 *
	 * @var   string|null
	 * @since 10.1.0
	 */
	private ?string $folder;

	/**
	 * Extension client application (for plugins, templates, modules)
	 *
	 * @var   int
	 * @since 10.1.0
	 */
	private int $client_id;

	/**
	 * Possible tables which need checking.
	 *
	 * @var   array
	 * @since 10.1.0
	 */
	private array $tables = [];

	/**
	 * SQL files already checked.
	 *
	 * @var   array
	 * @since 10.1.0
	 */
	private array $checkedFiles = [];

	/**
	 * Construct an extension object given an `#__extensions` row.
	 *
	 * @param   object  $extensionRow  The extensions table row in object format
	 *
	 * @since   10.1.0
	 */
	public function __construct(object $extensionRow)
	{
		$this->type      = $extensionRow->type ?? 'invalid';
		$this->element   = $extensionRow->element ?? '';
		$this->folder    = $extensionRow->folder ?? '';
		$this->client_id = $extensionRow->client_id ?? 0;

		$this->init();
	}

	/**
	 * Returns a Generator to iterate through all installed extensions.
	 *
	 * @param   EngineDBDriver|DatabaseDriver|DataInterface  $db  The site's database object.
	 *
	 * @since   10.1.0
	 */
	public static function allExtensions($db)
	{
		try
		{
			/** @var QueryInterface|AbstractEngineQuery $query */
			$query = method_exists($db, 'createQuery') ? $db->createQuery(true) : $db->getQuery(true);
			$query->select([
				$db->quoteName('type'),
				$db->quoteName('element'),
				$db->quoteName('folder'),
				$db->quoteName('client_id'),
			])
				->from($db->quoteName('#__extensions'));

			$extensions = $db->setQuery($query)->loadObjectList() ?: [];

			if (empty($extensions))
			{
				return;
			}
		}
		catch (QueryException $e)
		{
			return;
		}

		foreach ($extensions as $extension)
		{
			yield new self($extension);
		}
	}

	/**
	 * Returns the tables installed by any and all extensions on this site.
	 *
	 * The list is made unique and alpha-sorted to make troubleshooting easy :)
	 *
	 * @param   EngineDBDriver|DatabaseDriver|DataInterface  $db  The site's database object.
	 *
	 * @since   10.1.0
	 */
	public static function allTables($db): array
	{
		$allTables = [];

		foreach (self::allExtensions($db) as $extension)
		{
			$moreTables = $extension->getTables();

			if (empty($moreTables))
			{
				continue;
			}

			$allTables = array_merge($allTables, $moreTables);
		}

		$allTables = array_unique($allTables);
		asort($allTables);

		return $allTables;
	}

	/**
	 * Get possible database tables which may have been installed by the extension.
	 *
	 * @return  array
	 * @since   10.1.0
	 */
	public function getTables(): array
	{
		return $this->tables;
	}

	/**
	 * Initialise the internal variables. Called from __construct().
	 *
	 * @return  void
	 * @since   10.1.0
	 */
	private function init(): void
	{
		if (empty($this->element ?? null) || $this->element === 'com_admin')
		{
			return;
		}

		// Use the default SQL files to populate the tables
		$this->populateTablesFromDefaultDirectory();

		// Discover the manifest
		try
		{
			$xml = InstallerHelper::getInstallationXML($this->element, $this->type, $this->client_id, $this->folder);

			if (!$xml instanceof SimpleXMLElement)
			{
				return;
			}

			if (strtolower($this->getXMLAttribute($xml, 'type')) !== strtolower($this->type))
			{
				return;
			}
		}
		catch (\Throwable $e)
		{
			return;
		}

		// Populate the tables from the manifest
		$this->populateTablesFromManifest($xml);
	}

	/**
	 * Get the value of a named attribute, given the XML node it appears in.
	 *
	 * This is used when parsing the XML manifests.
	 *
	 * @param   SimpleXMLElement  $node     The XML node, as a SimpleXMLElement.
	 * @param   string            $name     The name of the attribute to retrieve the value of.
	 * @param   string|null       $default  The default value to return if the attribute is missing.
	 *
	 * @return  string|null  The attribute value.
	 * @since   10.1.0
	 */
	private function getXMLAttribute(SimpleXMLElement $node, string $name, ?string $default = null): ?string
	{
		$attributes = $node->attributes();

		if (isset($attributes[$name]))
		{
			return (string) $attributes[$name];
		}

		return $default;
	}

	/**
	 * Populates the extension tables using the Joomla! hardcoded default `sql` directory.
	 *
	 * This DOES NOT read the manifest. It assumes the extension has a directory named `sql` under its main extension
	 * directory (for components it's the extension's admin directory) which has .sql files inside it in some sort of
	 * directory structure. This default is hardcoded in Joomla's Database Fix code where it looks for SQL update files
	 * under the extension's `sql/updates` folder. We are being slightly more flexible here.
	 *
	 * This is meant to be a quick and dirty way to identify extension tables if the manifest is missing or corrupt. It
	 * is not meant as the only, or even preferred, method.
	 *
	 * @return  void
	 * @since   10.1.0
	 * @see     self::populateTablesFromManifest
	 */
	private function populateTablesFromDefaultDirectory(): void
	{
		if ($this->type === 'component')
		{
			$basePath = JPATH_ADMINISTRATOR . '/components/' . $this->element;
		}
		elseif ($this->type === 'plugin')
		{
			$basePath = JPATH_PLUGINS . '/' . $this->folder . '/' . $this->element;
		}
		elseif ($this->type === 'module')
		{
			if ($this->client_id == 1)
			{
				$basePath = JPATH_ADMINISTRATOR . '/modules/' . $this->element;
			}
			elseif ($this->client_id == 0)
			{
				$basePath = JPATH_SITE . '/modules/' . $this->element;
			}
			else
			{
				// Cannot process modules with an invalid client ID.
				return;
			}
		}
		elseif ($this->type === 'file' && $this->element === 'com_admin')
		{
			// Specific bodge for the Joomla CMS special database check which points to com_admin
			$basePath = JPATH_ADMINISTRATOR . '/components/' . $this->element;
		}
		else
		{
			// Unknown extension type, or other type (e.g. library, files etc) which don't have known SQL paths
			return;
		}

		if (!@is_dir($basePath . '/sql'))
		{
			return;
		}

		/**
		 * The /sql subdirectory as the default schema location is a hardcoded default in Joomla.
		 *
		 * @see \Joomla\Component\Installer\Administrator\Model\DatabaseModel::fetchSchemaCache
		 */
		$sqlFiles = Folder::files($basePath . '/sql', '\.sql$', true, true) ?: [];

		foreach ($sqlFiles as $sqlFile)
		{
			try
			{
				$this->populateTablesFromSQLFile($sqlFile);
			}
			catch (\Throwable $e)
			{
				// It's not the end of the world. Keep going.
			}
		}

		$this->tables = array_unique($this->tables);
	}

	/**
	 * Populates database tables from the SQL files specified in the extension's XML manifest file.
	 *
	 * This is the most accurate way to do this. Instead of using a hardcoded default, we examine the manifest to locate
	 * the installation SQL file, and the path to the update SQL files. We then read them, parse them, and identify the
	 * created tables.
	 *
	 * @param   SimpleXMLElement  $xml  The XML manifest.
	 *
	 * @return  void
	 * @since   10.1.0
	 */
	private function populateTablesFromManifest(SimpleXMLElement $xml): void
	{
		$sqlFiles = [];
		$basePath = JPATH_ADMINISTRATOR . '/components/' . $this->element . '/';

		foreach ($xml->xpath('/extension/install/sql/file') as $fileNode)
		{
			$driver  = $this->getXMLAttribute($fileNode, 'driver', 'mysql');
			$charset = $this->getXMLAttribute($fileNode, 'charset', 'utf8');
			$relPath = (string) $fileNode;

			if ($charset != 'utf8')
			{
				continue;
			}

			if (str_starts_with($driver, 'mysql') || str_starts_with($driver, 'postgres'))
			{
				$sqlFiles[] = $basePath . ltrim($relPath, '/');
			}
		}

		foreach ($xml->xpath('/extension/update/schemas/schemapath') as $folderNode)
		{
			$type = $this->getXMLAttribute($folderNode, 'type', 'mysql');

			if (!str_starts_with($type, 'mysql') && !str_starts_with($type, 'postgres'))
			{
				continue;
			}

			$relPath = (string) $folderNode;
			$absPath = $basePath . ltrim($relPath, '/');

			if (!is_dir($absPath))
			{
				continue;
			}

			$sqlFiles = array_merge(
				$sqlFiles, Folder::files($absPath, '\.sql$', false, true) ?: []
			);
		}

		foreach ($sqlFiles as $sqlFile)
		{
			$this->populateTablesFromSQLFile($sqlFile);
		}

		$this->tables = array_unique($this->tables);
	}

	/**
	 * Populates the list of table names from an SQL file by parsing CREATE TABLE statements.
	 *
	 * @param   mixed  $sqlFile  The file path to the SQL file to be read. Must be a readable file path.
	 *
	 * @return  void
	 * @since   10.1.0
	 */
	private function populateTablesFromSQLFile($sqlFile): void
	{
		if (in_array($sqlFile, $this->checkedFiles))
		{
			return;
		}

		$this->checkedFiles[] = $sqlFile;

		if (!@file_exists($sqlFile) || !@is_readable($sqlFile))
		{
			return;
		}

		$buffer = @file_get_contents($sqlFile);

		if ($buffer === false)
		{
			return;
		}

		foreach (DatabaseDriver::splitSql($buffer) as $statement)
		{
			if (!preg_match('/CREATE\s+TABLE/i', $statement))
			{
				continue;
			}

			try
			{
				$parser = new PHPSQLParser($statement, false);
			}
			catch (\Throwable $e)
			{
				continue;
			}

			if (!is_array($parser->parsed) || empty($parser->parsed) || !isset($parser->parsed['TABLE']))
			{
				continue;
			}

			$rawTableName = $parser->parsed['TABLE']['name'] ?? null;

			if (!is_string($rawTableName))
			{
				continue;
			}

			$tableName = trim($rawTableName, '`"[]');

			$this->tables[] = $tableName;
		}
	}
}