<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Archiver;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Factory;
use RuntimeException;
use ZipArchive;

/**
 * Class Zipnative
 *
 * This file uses the ZipArchive class to create and add files to existing ZIP
 * archives. For more information on the use of this class, please see:
 * 1. http://devzone.zend.com/article/2105 (tutorial)
 * 2. http://www.php.net/manual/en/class.ziparchive.php (reference)
 *
 * That said, the ZipArchive class is terribly inflexible when it comes down to
 * features already implemented in Akeeba Engine's Zip such as archive
 * splitting, chunked processing of very large files and processing of symlinks.
 * We deem it only suitable for small sites, without large files, running on a
 * decent hosting facility.
 */
class Zipnative extends Base
{
	/** @var string The name of the file holding the ZIP's data, which becomes the final archive */
	private $_dataFileName;

	/** @var ZipArchive An instance of the PHP ZIPArchive class */
	private $zip = null;

	/** @var int Running sum of bytes added to the archive */
	private $runningSum = 0;

	/** @var int Permissions for the backup archive part files */
	protected $permissions = null;

	/**
	 * Class constructor - initializes internal operating parameters
	 *
	 * @throws RuntimeException
	 */
	public function __construct()
	{
		Factory::getLog()->debug(__CLASS__ . " :: New instance");

		if (!class_exists('\ZipArchive'))
		{
			throw new RuntimeException('Your server does not support the ZipArchive extension. Please use a different Archiver Engine and retry backing up your site');
		}

		parent::__construct();
	}

	/**
	 * Common code which gets called on instance creation or wake-up (unserialization)
	 *
	 * @codeCoverageIgnore
	 *
	 * @return  void
	 */
	public function __bootstrap_code()
	{
		parent::__bootstrap_code();

		// So that the first run doesn't crash!
		if (empty($this->_dataFileName))
		{
			return;
		}

		// Try to reopen the ZIP
		$this->zip = new ZipArchive;

		if (!file_exists($this->_dataFileName))
		{
			$res = $this->zip->open($this->_dataFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE);
		}
		else
		{
			$res = $this->zip->open($this->_dataFileName);
		}

		if ($res !== true)
		{
			switch ($res)
			{
				case ZipArchive::ER_EXISTS:
					throw new RuntimeException("The archive {$this->_dataFileName} already exists");
					break;

				case ZipArchive::ER_INCONS:
					throw new RuntimeException("Inconsistent archive {$this->_dataFileName} detected");
					break;

				case ZipArchive::ER_INVAL:
					throw new RuntimeException("Invalid archive {$this->_dataFileName} detected");
					break;

				case ZipArchive::ER_MEMORY:
					throw new RuntimeException("Not enough memory to process archive {$this->_dataFileName}");
					break;

				case ZipArchive::ER_NOENT:
					throw new RuntimeException("Unexpected ZipArchive::ER_NOENT error processing archive {$this->_dataFileName}");
					break;

				case ZipArchive::ER_NOZIP:
					throw new RuntimeException("File {$this->_dataFileName} is not a ZIP archive!");
					break;

				case ZipArchive::ER_OPEN:
					throw new RuntimeException("Could not open archive file {$this->_dataFileName} for writing");
					break;

				case ZipArchive::ER_READ:
					throw new RuntimeException("Could not read from archive file {$this->_dataFileName}");
					break;

				case ZipArchive::ER_SEEK:
					throw new RuntimeException("Could not seek into position while processing archive file {$this->_dataFileName}");
					break;
			}
		}
	}

	/**
	 * Initialises the archiver class, creating the archive from an existent
	 * installer's JPA archive.
	 *
	 * @param   string  $targetArchivePath  Absolute path to the generated archive
	 * @param   array   $options            A named key array of options (optional). This is currently not supported
	 *
	 * @return  void
	 */
	public function initialize($targetArchivePath, $options = [])
	{
		Factory::getLog()->debug(__CLASS__ . " :: initialize - archive $targetArchivePath");

		// Get names of temporary files
		$this->_dataFileName = $targetArchivePath;

		// Try to kill the archive if it exists
		Factory::getLog()->debug(__CLASS__ . " :: Killing old archive");

		$fp = fopen($this->_dataFileName, "w");

		if (!($fp === false))
		{
			ftruncate($fp, 0);
			$this->conditionalFileClose($fp);
		}
		else
		{
			@unlink($this->_dataFileName);
		}

		$this->runningSum = 0;

		// Make sure we open the file
		$this->__bootstrap_code();
	}

	/**
	 * In this engine, we have no finalization, really
	 *
	 * @return  void
	 */
	public function finalize()
	{
		$this->zip->close();

		@chmod($this->_dataFileName, $this->getPermissions());
	}

	/**
	 * Returns a string with the extension (including the dot) of the files produced
	 * by this class.
	 *
	 * @return string
	 */
	public function getExtension()
	{
		return '.zip';
	}

	/**
	 * The most basic file transaction: add a single entry (file or directory) to
	 * the archive.
	 *
	 * @param   bool    $isVirtual         If true, the next parameter contains file data instead of a file name
	 * @param   string  $sourceNameOrData  Absolute file name to read data from or the file data itself is $isVirtual is
	 *                                     true
	 * @param   string  $targetName        The (relative) file name under which to store the file in the archive
	 *
	 * @return bool True on success, false otherwise
	 */
	protected function _addFile($isVirtual, &$sourceNameOrData, $targetName)
	{
		if (!is_object($this->zip))
		{
			return false;
		}

		if (!$isVirtual)
		{
			Factory::getLog()->debug(__CLASS__ . " :: Adding $sourceNameOrData");

			if (is_dir($sourceNameOrData))
			{
				$result = $this->zip->addEmptyDir($targetName);
			}
			else
			{
				$this->runningSum += filesize($sourceNameOrData);
				$result           = $this->zip->addFile($sourceNameOrData, $targetName);
			}
		}
		else
		{
			Factory::getLog()->debug('  Virtual add:' . $targetName . ' (' . strlen($sourceNameOrData) . ')');
			$this->runningSum += strlen($sourceNameOrData);

			if (empty($sourceNameOrData))
			{
				$result = $this->zip->addEmptyDir($targetName);
			}
			else
			{
				$result = $this->zip->addFromString($targetName, $sourceNameOrData);
			}
		}

		$this->zip->close();
		$this->__bootstrap_code();

		return true;
	}

	/**
	 * Return the requested permissions for the backup archive file.
	 *
	 * @return  int
	 * @since   8.0.0
	 */
	protected function getPermissions(): int
	{
		if (!is_null($this->permissions))
		{
			return $this->permissions;
		}

		$configuration     = Factory::getConfiguration();
		$permissions       = $configuration->get('engine.archiver.common.permissions', '0666') ?: '0666';
		$this->permissions = octdec($permissions);

		return $this->permissions;
	}
}
