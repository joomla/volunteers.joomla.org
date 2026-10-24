<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License version 3, or later
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program. If not, see
 * <https://www.gnu.org/licenses/>.
 */

namespace Akeeba\Engine\Util;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Akeeba\Engine\Util\Log\LogInterface;
use Akeeba\Engine\Util\Log\WarningsLoggerAware;
use Akeeba\Engine\Util\Log\WarningsLoggerInterface;
use Akeeba\Engine\Psr\Log\InvalidArgumentException;
use Akeeba\Engine\Psr\Log\LoggerInterface;
use Akeeba\Engine\Psr\Log\LogLevel;

/**
 * Writes messages to the backup log file
 */
class Logger implements LoggerInterface, LogInterface, WarningsLoggerInterface
{
	use WarningsLoggerAware;

	/** @var  string  Log file name suffix for the default, web-inaccessible log file */
	private const SUFFIX_LOG_PHP = '.log.php';

	/** @var  string  Log file name suffix for the fallback, still web-inaccessible log file */
	private const SUFFIX_PHP = '.php';

	/** @var  string  Log file name suffix for the fallback, web-accessible log file */
	private const SUFFIX_LOG = '.log';

	/** @var  string[]  All log file name suffixes we may have ever used */
	private const ALL_SUFFIXES = [self::SUFFIX_LOG_PHP, self::SUFFIX_PHP, self::SUFFIX_LOG];

	/** @var  string  Full path to log file */
	protected $logName = null;

	/** @var  string  The current log tag */
	protected $currentTag = null;

	/** @var  resource  The file pointer to the current log file */
	protected $fp = null;

	/** @var  bool  Is the logging currently paused? */
	protected $paused = false;

	/** @var  int  The minimum log level */
	protected $configuredLoglevel;

	/** @var  string  The untranslated path to the site's root */
	protected $site_root_untranslated;

	/** @var  string  The translated path to the site's root */
	protected $site_root;

	/**
	 * Should I fall back to a web-accessible .log file if I cannot create a .log.php file?
	 *
	 * When disabled (the default) the fallback is a .php file instead, keeping the privileged information in the log
	 * file inaccessible over the web. If that fails as well logging is paused.
	 *
	 * @var  bool
	 */
	protected $allowPlainLogFiles = false;

	/**
	 * Public constructor. Initialises the properties with the parameters from the backup profile and platform.
	 *
	 * @param   bool  $allowPlainLogFiles  Allow falling back to a web-accessible .log file?
	 */
	public function __construct($allowPlainLogFiles = false)
	{
		$this->allowPlainLogFiles = (bool) $allowPlainLogFiles;

		$this->initialiseWithProfileParameters();
	}

	/**
	 * When shutting down this class always close any open log files.
	 */
	public function __destruct()
	{
		$this->close();
	}

	/**
	 * Clears the logfile
	 *
	 * @param   string  $tag  Backup origin
	 */
	public function reset($tag = null)
	{
		// Pause logging
		$this->pause();

		// Get the file names of all log file flavours for this tag
		$currentLogName = $this->logName;
		$taggedLogNames = $this->getAllLogFilenames($tag);

		$this->logName = $this->getLogFilename($tag);

		// Close the file if it's open
		if (in_array($currentLogName, $taggedLogNames, true))
		{
			$this->close();
		}

		// Remove all log file flavours for this tag
		foreach ($taggedLogNames as $taggedLogName)
		{
			@unlink($taggedLogName);
		}

		$hasWritten = false;

		// Try each candidate log file until one of them can be created and written to
		foreach ($this->getCandidateSuffixes() as $suffix)
		{
			$this->logName = $this->getLogFilenameWithSuffix($tag, $suffix);

			// Reset the log file
			$fp = @fopen($this->logName, 'w');

			if ($fp === false)
			{
				continue;
			}

			$contents   = substr($suffix, -4) === self::SUFFIX_PHP
				? '<' . '?' . 'php die(); ' . '?' . '>' . "\n"
				: "\n";
			$hasWritten = fwrite($fp, $contents) !== false;

			@fclose($fp);

			if ($hasWritten)
			{
				break;
			}

			// The file was created but I can't write to it. Do not leave it behind.
			@unlink($this->logName);
		}

		// Delete the default log file(s) if they exist
		if (!empty($tag))
		{
			foreach (self::ALL_SUFFIXES as $suffix)
			{
				$defaultLog = $this->getLogFilenameWithSuffix(null, $suffix);

				if (@file_exists($defaultLog))
				{
					@unlink($defaultLog);
				}
			}
		}

		// Set the current log tag
		$this->currentTag = $tag;

		// If there is nowhere to write the log to we have to keep the logging paused
		if (!$hasWritten)
		{
			return;
		}

		// Unpause logging
		$this->unpause();
	}

	/**
	 * Writes a line to the log, if the log level is high enough
	 *
	 * @param   string  $level    The log level
	 * @param   string  $message  The message to write to the log
	 * @param   array   $context  The logging context. For PSR-3 compatibility but not used in text file logs.
	 *
	 * @return  void
	 */
	public function log($level, $message = '', array $context = [])
	{
		// Warnings are enqueued no matter what is the minimum log level to report in the log file
		if (in_array($level, [LogLevel::WARNING, LogLevel::NOTICE]))
		{
			$this->enqueueWarning($message);
		}

		// If we are told to not log anything we can't continue
		if ($this->configuredLoglevel == 0)
		{
			return;
		}

		// If the logging is paused we can't continue
		if ($this->paused)
		{
			return;
		}

		// Open the log if it's closed
		if (is_null($this->fp))
		{
			$this->open($this->currentTag);
		}

		// If the log could not be opened we can't continue
		if (is_null($this->fp))
		{
			return;
		}

		// Get the log level as an integer (compatibility with our minimum log level configuration parameter)
		switch ($level)
		{
			case LogLevel::EMERGENCY:
			case LogLevel::ALERT:
			case LogLevel::CRITICAL:
			case LogLevel::ERROR:
				$intLevel = 1;
				break;

			case LogLevel::WARNING:
			case LogLevel::NOTICE:
				$intLevel = 2;
				break;

			case LogLevel::INFO:
				$intLevel = 3;
				break;

			case LogLevel::DEBUG:
				$intLevel = 4;
				break;

			default:
				throw new InvalidArgumentException("Unknown log level $level", 500);
				break;
		}

		// If the minimum log level is lower than what we're trying to log we cannot continue
		if ($this->configuredLoglevel < $intLevel)
		{
			return;
		}

		$translateRoot = boolval($context['root_translate'] ?? false);

		// Replace the site's root with <root> in the log file
		if ($translateRoot && !defined('AKEEBADEBUG'))
		{
			$message = str_replace($this->site_root_untranslated, "<root>", $message);
			$message = str_replace($this->site_root, "<root>", $message);
		}

		// Replace new lines
		$message = str_replace("\r\n", "\n", $message);
		$message = str_replace("\r", "\n", $message);
		$message = str_replace("\n", ' \n ', $message);

		switch ($level)
		{
			case LogLevel::EMERGENCY:
			case LogLevel::ALERT:
			case LogLevel::CRITICAL:
			case LogLevel::ERROR:
				$string = "ERROR   |";
				break;

			case LogLevel::WARNING:
			case LogLevel::NOTICE:
				$string = "WARNING |";
				break;

			case LogLevel::INFO:
				$string = "INFO    |";
				break;

			default:
				$string = "DEBUG   |";
				break;
		}

		$string .= gmdate('Ymd H:i:s') . "|$message\r\n";

		@fwrite($this->fp, $string);
	}

	/**
	 * Calculates the absolute path to the log file
	 *
	 * @param   string  $tag        The backup run's tag
	 * @param   string  $extension  The extension after the .log part of the file name ('' for a plain .log file)
	 *
	 * @return    string    The absolute path to the log file
	 */
	public function getLogFilename($tag = null, $extension = '.php')
	{
		return $this->getLogFilenameWithSuffix($tag, self::SUFFIX_LOG . $extension);
	}

	/**
	 * Calculates the absolute paths to all log file flavours we may have ever created for a backup run.
	 *
	 * The paths are returned in order of preference: the .log.php file first, then the .php file, and finally the
	 * .log file. The files are not guaranteed to exist.
	 *
	 * @param   string|null  $tag  The backup run's tag
	 *
	 * @return  string[]
	 */
	public function getAllLogFilenames($tag = null)
	{
		return array_map(
			function ($suffix) use ($tag) {
				return $this->getLogFilenameWithSuffix($tag, $suffix);
			},
			self::ALL_SUFFIXES
		);
	}

	/**
	 * Calculates the absolute path to the log file with a specific log file name suffix
	 *
	 * @param   string|null  $tag     The backup run's tag
	 * @param   string       $suffix  The log file name suffix, one of the SUFFIX_* constants
	 *
	 * @return  string  The absolute path to the log file
	 */
	protected function getLogFilenameWithSuffix($tag = null, $suffix = self::SUFFIX_LOG_PHP)
	{
		$fileName = (empty($tag) ? 'akeeba' : "akeeba.$tag") . $suffix;

		// Get output directory
		$registry        = Factory::getConfiguration();
		$outputDirectory = $registry->get('akeeba.basic.output_directory');

		// Get the log file name
		$absoluteLogFilename = Factory::getFilesystemTools()->TranslateWinPath($outputDirectory . DIRECTORY_SEPARATOR . $fileName);

		return $absoluteLogFilename;
	}

	/**
	 * Returns the log file name suffixes to try, in order, when creating a log file.
	 *
	 * The first choice is always a .log.php file. Some hosts, like WP Engine, do not let us write to files with a .php
	 * extension. In this case we fall back to a .php file — which some of these hosts do allow — and, only if this
	 * class is explicitly told to, to a .log file. The latter is a last ditch effort; it is readable over the web,
	 * exposing the privileged information in the log file.
	 *
	 * @param   string  $extension  The requested log file extension. Anything other than '.php' is honoured verbatim.
	 *
	 * @return  string[]
	 */
	protected function getCandidateSuffixes($extension = '.php')
	{
		// An explicit request for a specific extension is honoured verbatim, without any fallback
		if ($extension !== self::SUFFIX_PHP)
		{
			return [self::SUFFIX_LOG . $extension];
		}

		return [
			self::SUFFIX_LOG_PHP,
			$this->allowPlainLogFiles ? self::SUFFIX_LOG : self::SUFFIX_PHP,
		];
	}

	/**
	 * Close the currently active log and set the current tag to null.
	 *
	 * @return  void
	 */
	public function close()
	{
		// The log file changed. Close the old log.
		if (is_resource($this->fp))
		{
			@fclose($this->fp);
		}

		$this->fp         = null;
		$this->currentTag = null;
	}

	/**
	 * Open a new log instance with the specified tag. If another log is already open it is closed before switching to
	 * the new log tag. If the tag is null use the default log defined in the logging system.
	 *
	 * @param   string|null  $tag        The log to open
	 * @param   string       $extension  The log file extension (default: .php, use empty string for .log files)
	 *
	 * @return void
	 */
	public function open($tag = null, $extension = '.php')
	{
		// If the log is already open do nothing
		if (is_resource($this->fp) && ($tag == $this->currentTag))
		{
			return;
		}

		// If another log is open, close it
		if (is_resource($this->fp))
		{
			$this->close();
		}

		// Re-initialise site root and minimum log level since the active profile might have changed in the meantime
		$this->initialiseWithProfileParameters();

		// Set the current tag
		$this->currentTag = $tag;

		// Try each candidate log file in turn until one of them can be opened and written to
		foreach ($this->getCandidateSuffixes($extension) as $suffix)
		{
			if ($this->openWithSuffix($tag, $suffix))
			{
				return;
			}
		}

		// I have nowhere to write the log to. Pause the logging.
		$this->fp = null;

		$this->pause();
	}

	/**
	 * Try to open the log file with the given tag and log file name suffix.
	 *
	 * @param   string|null  $tag     The log to open
	 * @param   string       $suffix  The log file name suffix, one of the SUFFIX_* constants
	 *
	 * @return  bool  True if the log file was opened and is writeable
	 */
	protected function openWithSuffix($tag, $suffix)
	{
		// Get the log filename
		$this->logName = $this->getLogFilenameWithSuffix($tag, $suffix);

		// Touch the file
		@touch($this->logName);

		// Open the log file. DO NOT USE APPEND ('ab') MODE. I NEED TO SEEK INTO THE FILE. SEE FURTHER BELOW!
		$this->fp = @fopen($this->logName, 'c');

		// If we couldn't open the file set the file pointer to null
		if ($this->fp === false)
		{
			$this->fp = null;

			return false;
		}

		// Go to the end of the file, emulating append mode. DO NOT REPLACE THE fopen() FILE MODE!
		if (@fseek($this->fp, 0, SEEK_END) === -1)
		{
			$this->closeAndRemove();

			return false;
		}

		/**
		 * The following sounds pretty stupid but there is a reason for that convoluted code.
		 *
		 * Some hosts, like WP Engine, will now allow you to write to a log file with a .php extension. The code below
		 * tries to anticipate that when the log file name ends in .php. It will try to write to the file text which is
		 * actually resembling PHP code. Hosts like WP Engine will fail the fwrite() which will cause this method to
		 * report failure. Our caller will catch this case and try the next log file name in the fallback chain.
		 */
		if (substr($suffix, -4) !== self::SUFFIX_PHP)
		{
			return true;
		}

		// Try to write something into the file
		$written = @fwrite($this->fp, '<?php die("test"); ?>' . "\n");

		if ($written === false)
		{
			$this->closeAndRemove();

			return false;
		}

		// Store truncate offset, we will have to rewind the internal pointer to it
		$truncate_point = ftell($this->fp) - $written;

		if (ftruncate($this->fp, $truncate_point) === false)
		{
			$this->closeAndRemove();

			return false;
		}

		// Finally, move the file pointer at the truncation point. Otherwise PHP will append NULL bytes to the string
		// to "pad" the file length to the internal file pointer. No need to check if the operation was successful,
		// worst case scenario we will have some extra NULL bytes, there's no need to kill the log operation
		@fseek($this->fp, $truncate_point);

		return true;
	}

	/**
	 * Close the log file we just tried — and failed — to use, and remove it from the disk.
	 *
	 * @return  void
	 */
	protected function closeAndRemove()
	{
		@fclose($this->fp);
		@unlink($this->logName);

		$this->fp = null;
	}

	/**
	 * Temporarily pause log output. The log() method MUST respect this.
	 *
	 * @return  void
	 */
	public function pause()
	{
		$this->paused = true;
	}

	/**
	 * Resume the previously paused log output. The log() method MUST respect this.
	 *
	 * @return  void
	 */
	public function unpause()
	{
		$this->paused = false;
	}

	/**
	 * Returns the timestamp (in UNIX time long integer format) of the last log message written to the log with the
	 * specific tag. The timestamp MUST be read from the log itself, not from the logger object. It is used by the
	 * engine to find out the age of stalled backups which may have crashed.
	 *
	 * @param   string|null  $tag  The log tag for which the last timestamp is returned
	 *
	 * @return  int|null  The timestamp of the last log message, in UNIX time. NULL if we can't get the timestamp.
	 */
	public function getLastTimestamp($tag = null)
	{
		$timestamp = null;

		/**
		 * The log file akeeba.tag.log.php may not exist but the akeeba.tag.php or the akeeba.tag.log does. This would
		 * be the case in some bad hosts, like WPEngine, which do not allow us to create .php files EVEN THOUGH that's
		 * the only way to ensure the privileged information in the log file is not readable over the web. You can't fix
		 * bad hosts, you can only work around them.
		 */
		foreach (self::ALL_SUFFIXES as $suffix)
		{
			$fileName = $this->getLogFilenameWithSuffix($tag, $suffix);
			$fileTime = @file_exists($fileName) ? @filemtime($fileName) : false;

			if ($fileTime === false)
			{
				continue;
			}

			$timestamp = max($timestamp ?? 0, $fileTime);
		}

		return $timestamp;
	}

	/**
	 * System is unusable.
	 *
	 * @param   string  $message
	 * @param   array   $context
	 *
	 * @return void
	 */
	public function emergency($message, array $context = [])
	{
		$this->log(LogLevel::EMERGENCY, $message, $context);
	}

	/**
	 * Action must be taken immediately.
	 *
	 * Example: Entire website down, database unavailable, etc. This should
	 * trigger the SMS alerts and wake you up.
	 *
	 * @param   string  $message
	 * @param   array   $context
	 *
	 * @return void
	 */
	public function alert($message, array $context = [])
	{
		$this->log(LogLevel::ALERT, $message, $context);
	}

	/**
	 * Critical conditions.
	 *
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param   string  $message
	 * @param   array   $context
	 *
	 * @return void
	 */
	public function critical($message, array $context = [])
	{
		$this->log(LogLevel::CRITICAL, $message, $context);
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param   string  $message
	 * @param   array   $context
	 *
	 * @return void
	 */
	public function error($message, array $context = [])
	{
		$this->log(LogLevel::ERROR, $message, $context);
	}

	/**
	 * \Exceptional occurrences that are not errors.
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things
	 * that are not necessarily wrong.
	 *
	 * @param   string  $message
	 * @param   array   $context
	 *
	 * @return void
	 */
	public function warning($message, array $context = [])
	{
		$this->log(LogLevel::WARNING, $message, $context);
	}

	/**
	 * Normal but significant events.
	 *
	 * @param   string  $message
	 * @param   array   $context
	 *
	 * @return void
	 */
	public function notice($message, array $context = [])
	{
		$this->log(LogLevel::NOTICE, $message, $context);
	}

	/**
	 * Interesting events.
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param   string  $message
	 * @param   array   $context
	 *
	 * @return void
	 */
	public function info($message, array $context = [])
	{
		$this->log(LogLevel::INFO, $message, $context);
	}

	/**
	 * Detailed debug information.
	 *
	 * @param   string  $message
	 * @param   array   $context
	 *
	 * @return void
	 */
	public function debug($message, array $context = [])
	{
		$this->log(LogLevel::DEBUG, $message, $context);
	}

	/**
	 * Initialise the logger properties with parameters from the backup profile and the platform
	 *
	 * @return  void
	 */
	protected function initialiseWithProfileParameters()
	{
		// Get the site's translated and untranslated root
		$this->site_root_untranslated = Platform::getInstance()->get_site_root();
		$this->site_root              = Factory::getFilesystemTools()->TranslateWinPath($this->site_root_untranslated);

		// Load the registry and fetch log level
		$registry                 = Factory::getConfiguration();
		$this->configuredLoglevel = $registry->get('akeeba.basic.log_level');
		$this->configuredLoglevel = $this->configuredLoglevel * 1;
	}
}
