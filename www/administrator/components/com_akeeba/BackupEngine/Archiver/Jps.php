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
use Akeeba\Engine\Base\Exceptions\WarningException;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Util\Encrypt;
use Akeeba\Engine\Util\RandomValue;

if (!defined('_JPS_MAJOR'))
{
	define('_JPS_MAJOR', 2);
	define('_JPS_MINOR', 0);
}

/**
 * JoomlaPack Archive Secure (JPS) creation class
 *
 * JPS Format 1.9 implemented, minus BZip2 compression support
 */
class Jps extends BaseArchiver
{
	/** @var integer How many files are contained in the archive */
	private $totalFilesCount = 0;

	/** @var integer The total size of files contained in the archive as they are stored */
	private $totalCompressedSize = 0;

	/** @var integer The total size of files contained in the archive when they are extracted to disk. */
	private $totalUncompressedSize = 0;

	/** @var string Standard Header signature */
	private $archiveSignature = "\x4A\x50\x53"; // JPS

	/** @var string Standard Header signature */
	private $endOfArchiveSignature = "\x4A\x50\x45"; // JPE

	/** @var string Entity Block signature */
	private $fileHeaderSignature = "\x4A\x50\x46"; // JPF

	/** @var int Current part file number */
	private $currentPartNumber = 1;

	/** @var int Total number of part files */
	private $totalParts = 1;

	/** @var string The password to use */
	private $password = null;

	/** @var Encrypt The encryption object used in this class */
	private $encryptionObject = null;

	/** @var array Static Salt for PBKDF2 */
	private $staticSalt = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";

	/**
	 * Also remove the encryption object reference
	 *
	 * @codeCoverageIgnore
	 *
	 * @return  void
	 */
	public function _onSerialize()
	{
		parent::_onSerialize();

		$this->encryptionObject = null;
	}

	/**
	 * Initialises the archiver class, creating the archive from an existent
	 * installer's JPA archive.
	 *
	 * @param   string  $targetArchivePath  Absolute path to the generated archive
	 * @param   array   $options            A named key array of options (optional)
	 *
	 * @return  void
	 */
	public function initialize($targetArchivePath, $options = [])
	{
		Factory::getLog()->debug(__CLASS__ . " :: new instance - archive $targetArchivePath");

		$this->_dataFileName = $targetArchivePath;

		// Make sure the encryption functions are all there
		$this->testEncryptionAvailability();

		// Get and memorise the password
		$config         = Factory::getConfiguration();
		$this->password = $config->get('engine.archiver.jps.key', '');

		if (empty($this->password))
		{
			Factory::getLog()->warning('You are using an empty password. This is not secure at all!');
		}

		// Set up the key expansion based on preferences
		$pbkdf2UseStaticSalt = $config->get('engine.archiver.jps.pbkdf2usestaticsalt', 1);
		$this->encryptionObject
			->setPbkdf2Algorithm('sha1')
			->setPbkdf2Iterations($pbkdf2UseStaticSalt ? 128000 : 2500)
			->setPbkdf2UseStaticSalt($pbkdf2UseStaticSalt);

		// If a static salt is to be used let's create one
		if ($pbkdf2UseStaticSalt)
		{
			$rand             = new RandomValue();
			$this->staticSalt = $rand->generate(64);
			$this->encryptionObject->setPbkdf2StaticSalt($this->staticSalt);
		}

		// Should we enable split archive feature?
		$this->enableSplitArchives();

		// Should I use Symlink Target Storage?
		$this->enableSymlinkTargetStorage();

		// Create the new backup archive
		$this->createNewBackupArchive();

		// Write the initial instance of the archive header
		$this->writeArchiveHeader();
	}

	/**
	 * Updates the Standard Header with current information
	 *
	 * @return  void
	 */
	public function finalize()
	{
		// Close any open file pointers
		if (is_resource($this->fp))
		{
			$this->fclose($this->fp);
		}

		if (is_resource($this->cdfp))
		{
			$this->fclose($this->cdfp);
		}

		$this->_closeAllFiles();

		// If spanned JPS and there is no .jps file, rename the last part to .jps
		$this->renameLastPartToJps();

		// Write the end of archive header
		$this->writeEndOfArchiveHeader();
	}

	/**
	 * Returns a string with the extension (including the dot) of the files produced
	 * by this class.
	 *
	 * @return string
	 */
	public function getExtension()
	{
		return '.jps';
	}

	/**
	 * Returns the length of a string in BYTES, not characters
	 *
	 * @param   string  $string  The string to get the length for
	 *
	 * @return int The size in BYTES
	 */
	public function stringLength($string)
	{
		return function_exists('mb_strlen') ? mb_strlen($string, '8bit') : strlen($string);
	}

	/**
	 * Attempt to use mbstring for getting parts of strings
	 *
	 * @param   string    $string
	 * @param   int       $start
	 * @param   int|null  $length
	 *
	 * @return  string
	 */
	public function subString($string, $start, $length = null)
	{
		return function_exists('mb_substr') ? mb_substr($string, $start, $length, '8bit') :
			substr($string, $start, $length);
	}

	/**
	 * Outputs a Standard Header at the top of the file
	 *
	 * @return  void
	 *
	 * @throws  ErrorException
	 */
	protected function writeArchiveHeader()
	{
		if (is_null($this->fp))
		{
			$this->fp = @$this->fopen($this->_dataFileName, 'r+');
		}

		if ($this->fp === false)
		{
			throw new ErrorException('Could not open ' . $this->_dataFileName . ' for writing. Check permissions and open_basedir restrictions.');
		}

		// === HEADER ===
		$this->fwrite($this->fp, $this->archiveSignature); // ID string (JPS)
		$this->fwrite($this->fp, pack('C', _JPS_MAJOR)); // Major version
		$this->fwrite($this->fp, pack('C', _JPS_MINOR)); // Minor version
		$this->fwrite($this->fp, pack('C', $this->useSplitArchive ? 1 : 0)); // Is it a split archive?

		// === EXTRA HEADERS (JPS v2.0) ===

		// Extra headers length (76 bytes for key expansion header)
		$this->fwrite($this->fp, pack('v', 76));

		// Password expansion header (28 bytes)
		$this->writeKeyExpansionArchiveExtraHeader();

		// Change the permissions of the file
		@chmod($this->_dataFileName, $this->getPermissions());
	}

	/**
	 * Outputs the end of the Standard Header at the file
	 *
	 * @return  void
	 */
	protected function writeEndOfArchiveHeader()
	{
		if (!is_null($this->fp))
		{
			$this->fclose($this->fp);
			$this->fp = null;
		}

		$this->openArchiveForOutput(true);

		$this->fwrite($this->fp, $this->endOfArchiveSignature); // ID string (JPE)
		$this->fwrite($this->fp, pack('v', $this->totalParts)); // Total number of parts
		$this->fwrite($this->fp, pack('V', $this->totalFilesCount)); // Total number of files
		$this->fwrite($this->fp, pack('V', $this->totalUncompressedSize)); // Uncompressed size
		$this->fwrite($this->fp, pack('V', $this->totalCompressedSize)); // Compressed size
	}

	/**
	 * Extend the bootstrap code to add some define's used by the JPS format engine
	 *
	 * @return void
	 */
	protected function __bootstrap_code()
	{
		if (!defined('_JPS_MAJOR'))
		{
			define('_JPS_MAJOR', 1); // JPS Format major version number
			define('_JPS_MINOR', 9); // JPS Format minor version number
		}

		// Set up the key expansion
		$this->encryptionObject = Factory::getEncryption();

		$config              = Factory::getConfiguration();
		$pbkdf2UseStaticSalt = $config->get('engine.archiver.jps.pbkdf2usestaticsalt', 1);
		$this->encryptionObject
			->setPbkdf2Algorithm('sha1')
			->setPbkdf2Iterations($pbkdf2UseStaticSalt ? 128000 : 2500)
			->setPbkdf2UseStaticSalt($pbkdf2UseStaticSalt);

		if ($pbkdf2UseStaticSalt)
		{
			$this->encryptionObject->setPbkdf2StaticSalt($this->staticSalt);
		}

		parent::__bootstrap_code();
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
	 * @return boolean True on success, false otherwise
	 */
	protected function _addFile($isVirtual, &$sourceNameOrData, $targetName)
	{
		// Get references to engine objects we're going to be using
		$configuration = Factory::getConfiguration();

		// Is this a virtual file?
		$isVirtual = (bool) $isVirtual;

		// Open data file for output
		$this->openArchiveForOutput();

		// Should I continue backing up a file from the previous step?
		$continueProcessingFile = $configuration->get('volatile.engine.archiver.processingfile', false);

		// Initialize with the default values. Why are *these* values default? If we are continuing file packing, by
		// definition we have an uncompressed, non-virtual file. Hence the default values.
		$isDir     = false;
		$isSymlink = false;

		if (!$continueProcessingFile)
		{
			// Log the file being added
			$messageSource = $isVirtual ? '(virtual data)' : "(source: $sourceNameOrData)";
			Factory::getLog()->debug("-- Adding $targetName to archive $messageSource");

			$this->writeFileHeader($sourceNameOrData, $targetName, $isVirtual, $isSymlink, $isDir, $compressionMethod, $fileSize);
		}
		else
		{
			$sourceNameOrData = $configuration->get('volatile.engine.archiver.sourceNameOrData', '');
			$resume           = $configuration->get('volatile.engine.archiver.resume', 0);
			$fileSize         = $configuration->get('volatile.engine.archiver.unc_len', 0);

			// Log the file we continue packing
			Factory::getLog()->debug("-- Resuming adding file $sourceNameOrData to archive from position $resume (total size $fileSize)");
		}

		if ($isSymlink)
		{
			// Symlink: Single step, one block, uncompressed
			$this->putSymlinkToArchive($sourceNameOrData);
		}
		elseif ($isVirtual)
		{
			// Virtual: Single step, multiple blocks, compressed
			$this->putVirtualFileToArchive($sourceNameOrData);
		}
		elseif (!$isDir)
		{
			// Regular file: multiple step, multiple blocks, compressed
			if ($this->putFileIntoArchive($sourceNameOrData, $fileSize) === true)
			{
				return true;
			}
		}

		return true;
	}

	/**
	 * Writes an encrypted block to the archive
	 *
	 * @param   string  $data  Raw binary data to encrypt and write
	 *
	 * @return  bool  True on success
	 */
	protected function _writeEncryptedBlock($data)
	{
		$decryptedSize = akstrlen($data);
		$data          = $this->encryptionObject->AESEncryptCBC($data, $this->password);
		$encryptedSize = akstrlen($data);

		// Initialize the value with something suitable for single part archives
		$free_space = $encryptedSize + 8;

		// Do we have enough space to store the 8 byte header?
		if ($this->useSplitArchive)
		{
			// Compare to free part space
			$free_space = $this->getPartFreeSize();

			if ($free_space <= 8)
			{
				$this->createAndOpenNewPart();
			}
		}

		// Write the header
		$this->fwrite($this->fp,
			pack('V', $encryptedSize) .
			pack('V', $decryptedSize)
		);

		$free_space -= 8;

		// Do we have enough space to write the data in one part?
		if ($free_space >= $encryptedSize)
		{
			$this->fwrite($this->fp, $data);

			return true;
		}

		while ($encryptedSize > 0)
		{
			// Split between parts - Write first part
			$dataMD5         = md5($data);
			$firstPart       = aksubstr($data, 0, $free_space);
			$data            = aksubstr($data, $free_space);
			$firstPartLength = akstrlen($firstPart);

			if (md5($firstPart . $data) != $dataMD5)
			{
				throw new ErrorException('Multibyte character problems detected');
			}

			// Try to write to the archive. We can only write as much bytes as the free space in the backup archive OR
			// the total data bytes left, whichever is lower.
			$bytesWritten = $this->fwrite($this->fp, $firstPart, $firstPartLength);

			// Since we may have written fewer bytes than anticipated we use the real bytes written for calculations
			$free_space    -= $bytesWritten;
			$encryptedSize -= $bytesWritten;

			// Not all bytes were written. The rest must be placed in front of the remaining data so we can write it
			// in the next archive part.
			if ($bytesWritten < $firstPartLength)
			{
				$data = aksubstr($firstPart, $bytesWritten) . $data;
			}

			// If the part file is full create a new one
			if ($free_space <= 0)
			{
				// Create new part
				$this->createAndOpenNewPart();

				// Get its free space
				$free_space = $this->getPartFreeSize();
			}
		}

		return true;
	}

	/**
	 * Creates a new archive part
	 *
	 * @param   bool  $finalPart  Set to true if it is the final part (therefore has the .jps extension)
	 *
	 * @return  bool  True on success
	 */
	protected function createNewPartFile($finalPart = false)
	{
		// Close any open file pointers
		if (is_resource($this->fp))
		{
			$this->fclose($this->fp);
		}

		if (is_resource($this->cdfp))
		{
			$this->fclose($this->cdfp);
		}

		// Remove the just finished part from the list of resumable offsets
		$this->removeFromOffsetsList($this->_dataFileName);

		// Set the file pointers to null
		$this->fp   = null;
		$this->cdfp = null;

		// Push the previous part if we have to post-process it immediately
		$configuration = Factory::getConfiguration();

		if ($configuration->get('engine.postproc.common.after_part', 0))
		{
			$this->finishedPart[] = $this->_dataFileName;
		}

		$this->totalParts++;
		$this->currentPartNumber = $this->totalParts;

		if ($finalPart)
		{
			$this->_dataFileName = $this->dataFileNameWithoutExtension . '.jps';
		}
		else
		{
			$this->_dataFileName =
				$this->dataFileNameWithoutExtension . '.j' . sprintf('%02d', $this->currentPartNumber);
		}

		Factory::getLog()->info('Creating new JPS part #' . $this->currentPartNumber . ', file ' . $this->_dataFileName);

		// Inform that we have chenged the multipart number
		$statistics = Factory::getStatistics();
		$statistics->updateMultipart($this->totalParts);

		// Try to remove any existing file
		@unlink($this->_dataFileName);

		// Touch the new file
		$result = @touch($this->_dataFileName);

		@chmod($this->_dataFileName, $this->getPermissions());

		return $result;
	}

	/**
	 * Write the header for key expansion into the archive
	 *
	 * @return  void
	 *
	 * @since   5.3.0
	 */
	protected function writeKeyExpansionArchiveExtraHeader()
	{
		$expansionParams = $this->encryptionObject->getKeyDerivationParameters();

		switch ($expansionParams['algorithm'])
		{
			default:
			case 'sha1':
				$algo = 0;
				break;

			case 'sha256':
				$algo = 1;
				break;

			case 'sha512':
				$algo = 2;
				break;
		}

		$hasStaticSalt = $expansionParams['useStaticSalt'];
		$staticSalt    = $expansionParams['staticSalt'];

		if (!$hasStaticSalt)
		{
			$staticSalt = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
			$staticSalt .= "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
			$staticSalt .= "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
			$staticSalt .= "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
		}

		// -- Header
		$this->fwrite($this->fp, "JH\x00\x01");
		// -- Field length (with header)
		$this->fwrite($this->fp, pack('v', 12 + $this->stringLength($staticSalt)));
		// -- Algorithm, iterations, has static salt
		$this->fwrite($this->fp, pack('CVC', $algo, $expansionParams['iterations'], $hasStaticSalt));
		// -- Static salt
		$this->fwrite($this->fp, $staticSalt);
	}

	/**
	 * Test whether encryption and compression is available and operational on this server.
	 *
	 * @return  void
	 *
	 * @throws  ErrorException
	 */
	private function testEncryptionAvailability()
	{
		$test = $this->encryptionObject->AESEncryptCBC('test', 'test');

		if ($test === false)
		{
			throw new ErrorException('Sorry, your server does not support AES-128 encryption. Please use a different archive format.');
		}

		// Make sure we can really compress stuff
		if (!function_exists('gzcompress'))
		{
			throw new ErrorException('Sorry, your server does not support GZip compression which is required for the JPS format. Please use a different archive format.');
		}
	}

	/**
	 * Rename the extension of the last part of a split archive to .jps
	 *
	 * @return  void
	 *
	 * @throws  ErrorException
	 */
	private function renameLastPartToJps()
	{
		if (!$this->useSplitArchive)
		{
			return;
		}

		$extension = substr($this->_dataFileName, -3);

		if ($extension == '.jps')
		{
			return;
		}

		Factory::getLog()->debug('Renaming last JPS part to .JPS extension');
		$newName = $this->dataFileNameWithoutExtension . '.jps';

		if (!@rename($this->_dataFileName, $newName))
		{
			throw new ErrorException('Could not rename last JPS part to .JPS extension.');
		}

		$this->_dataFileName = $newName;
	}

	/**
	 * Write the file header to the backup archive.
	 *
	 * Only the first three parameters are input. All other are ignored for input and are overwritten.
	 *
	 * @param   string  $sourceNameOrData   The path to the file being compressed, or the raw file data for virtual files
	 * @param   string  $targetName         The target path to be stored inside the archive
	 * @param   bool    $isVirtual          Is this a virtual file?
	 * @param   bool    $isSymlink          Is this a symlink?
	 * @param   bool    $isDir              Is this a directory?
	 * @param   int     $compressionMethod  The compression method chosen for this file
	 * @param   int     $fileSize           The uncompressed size of the file / source data
	 *
	 * @return  void
	 */
	private function writeFileHeader(&$sourceNameOrData, $targetName, &$isVirtual, &$isSymlink, &$isDir, &$compressionMethod, &$fileSize)
	{
		$configuration = Factory::getConfiguration();

		// Uncache data
		$configuration->set('volatile.engine.archiver.sourceNameOrData', null);
		$configuration->set('volatile.engine.archiver.unc_len', null);
		$configuration->set('volatile.engine.archiver.resume', null);
		$configuration->set('volatile.engine.archiver.processingfile', false);

		// See if it's a directory
		$isDir = $isVirtual ? false : is_dir($sourceNameOrData);

		// See if it's a symlink (w/out dereference)
		$isSymlink = false;

		if ($this->storeSymlinkTarget && !$isVirtual)
		{
			$isSymlink = is_link($sourceNameOrData);
		}

		// Get real size before compression
		[$fileSize, $fileModTime] =
			$this->getFileSizeAndModificationTime($sourceNameOrData, $isVirtual, $isSymlink, $isDir);

		// Decide if we will compress
		$compressionMethod = ($isDir || $isSymlink) ? 0 : 1;

		// Fix stored name for directories
		$storedName = $targetName;
		$storedName .= ($isDir) ? "/" : "";

		// Get file permissions
		$perms = $isVirtual ? 0644 : @fileperms($sourceNameOrData);

		// Get file type
		$fileType = 1;

		if ($isSymlink)
		{
			$fileType = 2;
		}
		elseif ($isDir)
		{
			$fileType = 0;
		}

		// Create the Entity Description Block Data
		$headerData =
			pack('v', akstrlen($storedName)) // Length of entity path
			. $storedName // Entity path
			. pack('c', $fileType) // Entity type
			. pack('c', $compressionMethod) // Compression type
			. pack('V', $fileSize) // Uncompressed size
			. pack('V', $perms) // Entity permissions
			. pack('V', $fileModTime) // File Modification Time
		;

		// Create and write the Entity Description Block Header
		$decryptedSize = akstrlen($headerData);
		$headerData    = $this->encryptionObject->AESEncryptCBC($headerData, $this->password);
		$encryptedSize = akstrlen($headerData);

		$headerData =
			$this->fileHeaderSignature . // JPF
			pack('v', $encryptedSize) . // Encrypted size
			pack('v', $decryptedSize) . // Decrypted size
			$headerData // Encrypted Entity Description Block Data
		;

		// Do we have enough space to store the header?
		if ($this->useSplitArchive)
		{
			// Compare to free part space
			$free_space = $this->getPartFreeSize();

			if ($free_space <= akstrlen($headerData))
			{
				// Not enough space on current part, create new part
				$this->createAndOpenNewPart();
			}
		}

		// Write the header data
		$this->fwrite($this->fp, $headerData);

		// Cache useful information about the file
		$configuration->set('volatile.engine.archiver.sourceNameOrData', $sourceNameOrData);
		$configuration->set('volatile.engine.archiver.unc_len', $fileSize);

		// Update global stats
		$this->totalFilesCount++;
		$this->totalUncompressedSize += $fileSize;
	}

	/**
	 * Put a symlink into the archive
	 *
	 * @param   string  $sourceNameOrData  The link to add to the archive
	 */
	private function putSymlinkToArchive(&$sourceNameOrData)
	{
		$data = @readlink($sourceNameOrData);
		$this->_writeEncryptedBlock($data);
		$this->totalCompressedSize += akstrlen($data);
	}

	/**
	 * Put a virtual file into the archive
	 *
	 * @param   string  $sourceNameOrData  The file contents to put into the archive
	 *
	 * @return  void
	 *
	 * @throws  ErrorException
	 */
	private function putVirtualFileToArchive(&$sourceNameOrData)
	{
		if (akstrlen($sourceNameOrData) <= 0)
		{
			return;
		}

		// Loop in 64Kb blocks
		while (akstrlen($sourceNameOrData) > 0)
		{
			// Get up to 64Kb
			$data = aksubstr($sourceNameOrData, 0, 65535);

			// Compress and encrypt data
			$data = gzcompress($data);
			$data = aksubstr(aksubstr($data, 0, -4), 2);
			$this->_writeEncryptedBlock($data);

			// Update the compressed size counter
			$this->totalCompressedSize += akstrlen($data);

			// Remove the portion of the data we just handled from the source
			if (akstrlen($data) < akstrlen($sourceNameOrData))
			{
				$sourceNameOrData = aksubstr($sourceNameOrData, 65535);
			}
			else
			{
				$sourceNameOrData = '';
			}
		}
	}

	/**
	 * Begin or resume adding a file to the archive
	 *
	 * @param   string  $sourceNameOrData  Path to the file being added to the archive
	 *
	 * @return  bool  True if we must resume file processing in the next step
	 */
	private function putFileIntoArchive(&$sourceNameOrData, $fileSize)
	{
		$configuration = Factory::getConfiguration();
		$timer         = Factory::getTimer();
		$resume        = null;

		// Get resume information of required
		$continueProcessingFile = $configuration->get('volatile.engine.archiver.processingfile', false);

		if ($continueProcessingFile)
		{
			$resume = $configuration->get('volatile.engine.archiver.resume', 0);

			Factory::getLog()->debug("(cont) Source: $sourceNameOrData - Size: $fileSize - Resume: $resume");
		}

		// Open the file
		$zdatafp = @fopen($sourceNameOrData, "rb");

		if ($zdatafp === false)
		{
			throw new WarningException('Unreadable file ' . $sourceNameOrData . '. Check permissions');
		}

		// Seek to the resume point if required
		if ($continueProcessingFile)
		{
			// Seek to new offset
			$seek_result = @fseek($zdatafp, $resume);

			if ($seek_result === -1)
			{
				// What?! We can't resume!
				$this->conditionalFileClose($zdatafp);

				throw new ErrorException(sprintf('Could not resume packing of file %s. Your archive is damaged!', $sourceNameOrData));
			}

			// Doctor the uncompressed size to match the remainder of the data
			$fileSize = $fileSize - $resume;
		}

		while (!@feof($zdatafp) && ($timer->getTimeLeft() > 0) && ($fileSize > 0))
		{
			$zdata    = @fread($zdatafp, AKEEBA_CHUNK);
			$fileSize -= min(akstrlen($zdata), AKEEBA_CHUNK);
			$zdata    = gzcompress($zdata);
			$zdata    = aksubstr(aksubstr($zdata, 0, -4), 2);

			try
			{
				$this->_writeEncryptedBlock($zdata);
			}
			catch (ErrorException $e)
			{
				$this->conditionalFileClose($zdatafp);

				throw $e;
			}

			$this->totalCompressedSize += akstrlen($zdata);
		}

		$mustResume = false;
		$resume     = null;

		// WARNING!!! The extra $fileSize != 0 check is necessary as PHP won't reach EOF for 0-byte files.
		if (!feof($zdatafp) && ($fileSize != 0))
		{
			// We have to break, or we'll time out!
			$mustResume = true;
			$resume     = @ftell($zdatafp);
		}

		$configuration->set('volatile.engine.archiver.resume', $resume);
		$configuration->set('volatile.engine.archiver.processingfile', $mustResume);

		$this->conditionalFileClose($zdatafp);

		return $mustResume;
	}
}
