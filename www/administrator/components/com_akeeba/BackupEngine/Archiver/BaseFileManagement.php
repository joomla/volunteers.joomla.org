<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Archiver;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Base\Exceptions\ErrorException;
use Akeeba\Engine\Factory;

if (!function_exists('akstrlen'))
{
	/**
	 * Attempt to use mbstring for calculating the binary string length.
	 *
	 * @param $string
	 *
	 * @return int
	 */
	function akstrlen($string)
	{
		return function_exists('mb_strlen') ? mb_strlen($string, '8bit') : strlen($string);
	}
}

/**
 * Abstract class for an archiver using managed file pointers
 */
abstract class BaseFileManagement extends Base
{
	/** @var resource File pointer to the archive's central directory file (for ZIP) */
	protected $cdfp = null;

	/** @var resource File pointer to the archive being currently written to */
	protected $fp = null;

	/** @var   array  An array of the last open files for writing and their last written to offsets */
	private $fileOffsets = [];

	/** @var   array  An array of open file pointers */
	private $filePointers = [];

	/** @var   null|string  The last filename fwrite() wrote to */
	private $lastFileName = null;

	/** @var   null|resource  The last file pointer fwrite() wrote to */
	private $lastFilePointer = null;

	/**
	 * Release file pointers when the object is being destroyed
	 *
	 * @codeCoverageIgnore
	 *
	 * @return  void
	 */
	public function __destruct()
	{
		$this->_closeAllFiles();

		$this->fp   = null;
		$this->cdfp = null;
	}

	/**
	 * Release file pointers when the object is being serialized
	 *
	 * @codeCoverageIgnore
	 *
	 * @return  void
	 */
	public function _onSerialize()
	{
		$this->_closeAllFiles();

		$this->fp   = null;
		$this->cdfp = null;
	}

	/**
	 * Closes all open files known to this archiver object
	 *
	 * @return  void
	 */
	protected function _closeAllFiles()
	{
		if (!empty($this->filePointers))
		{
			foreach ($this->filePointers as $file => $fp)
			{
				$this->conditionalFileClose($fp);

				unset($this->filePointers[$file]);
			}
		}
	}

	/**
	 * Closes an already open file
	 *
	 * @param   resource  $fp  The file pointer to close
	 *
	 * @return  boolean
	 */
	protected function fclose(&$fp)
	{
		$result = true;

		$offset = array_search($fp, $this->filePointers, true);

		if (!is_null($fp) && is_resource($fp))
		{
			$result = $this->conditionalFileClose($fp);
		}

		if ($offset !== false)
		{
			unset($this->filePointers[$offset]);
		}

		$fp = null;

		return $result;
	}

	protected function fcloseByName($file)
	{
		if (!array_key_exists($file, $this->filePointers))
		{
			return true;
		}

		$ret = $this->fclose($this->filePointers[$file]);

		if (array_key_exists($file, $this->filePointers))
		{
			unset($this->filePointers[$file]);
		}

		return $ret;
	}

	/**
	 * Opens a file, if it's not already open, or returns its cached file pointer if it's already open
	 *
	 * @param   string  $file  The filename to open
	 * @param   string  $mode  File open mode, defaults to binary write
	 *
	 * @return  resource
	 */
	protected function fopen($file, $mode = 'w')
	{
		if (!array_key_exists($file, $this->filePointers))
		{
			//Factory::getLog()->debug("Opening backup archive $file with mode $mode");
			$this->filePointers[$file] = @fopen($file, $mode);

			// If we open a file for append we have to seek to the correct offset
			if (substr($mode, 0, 1) == 'a')
			{
				if (isset($this->fileOffsets[$file]))
				{
					Factory::getLog()->debug("Truncating backup archive file $file to " . $this->fileOffsets[$file] . " bytes");
					@ftruncate($this->filePointers[$file], $this->fileOffsets[$file]);
				}

				fseek($this->filePointers[$file], 0, SEEK_END);
			}
		}

		return $this->filePointers[$file];
	}

	/**
	 * Write to file, defeating magic_quotes_runtime settings (pure binary write)
	 *
	 * @param   resource  $fp     Handle to a file
	 * @param   string    $data   The data to write to the file
	 * @param   integer   $p_len  Maximum length of data to write
	 *
	 * @return  int  The number of bytes written
	 *
	 * @throws  ErrorException  When writing to the file is not possible
	 */
	protected function fwrite($fp, $data, $p_len = null)
	{
		if ($fp !== $this->lastFilePointer)
		{
			$this->lastFilePointer = $fp;
			$this->lastFileName    = array_search($fp, $this->filePointers, true);
		}

		$len = is_null($p_len) ? (akstrlen($data)) : $p_len;
		$ret = fwrite($fp, $data, $len);

		if (($ret === false) || (abs(($ret - $len)) >= 1))
		{
			// Log debug information about the archive file's existence and current size. This helps us figure out if
			// there is a server-imposed maximum file size limit.
			clearstatcache();
			$fileExists  = @file_exists($this->lastFileName) ? 'exists' : 'does NOT exist';
			$currentSize = @filesize($this->lastFileName);

			Factory::getLog()->debug(sprintf("%s::_fwrite() ERROR!! Cannot write to archive file %s. The file %s. File size %s bytes after writing %s of %d bytes. Please check the output directory permissions and make sure you have enough disk space available. If this does not help, please set up a Part Size for Split Archives LOWER than this size and retry backing up.", __CLASS__, $this->lastFileName, $fileExists, $currentSize, $ret, $len));

			throw new ErrorException(sprintf("Couldn\'t write to the archive file; check the output directory permissions and make sure you have enough disk space available. [len=%s / %s]", $ret, $len));
		}

		if ($this->lastFileName !== false)
		{
			$this->fileOffsets[$this->lastFileName] = @ftell($fp);
		}

		return $ret;
	}

	/**
	 * Removes a file path from the list of resumable offsets
	 *
	 * @param $filename
	 */
	protected function removeFromOffsetsList($filename)
	{
		if (isset($this->fileOffsets[$filename]))
		{
			unset($this->fileOffsets[$filename]);
		}
	}

}
