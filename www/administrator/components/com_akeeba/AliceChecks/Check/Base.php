<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Alice\Check;

use Akeeba\Alice\Exception\CannotOpenLogfile;
use Akeeba\Alice\Exception\StopScanningEarly;
use Exception;
use InvalidArgumentException;

/**
 * Abstract class for ALICE checks
 *
 * @since  7.0.0
 */
abstract class Base
{
	/**
	 * Check priority
	 *
	 * @var   int
	 * @since 7.0.0
	 */
	protected $priority = 0;

	/**
	 * The full path to the log file we are analyzing
	 *
	 * @var   string
	 * @since 7.0.0
	 */
	protected $logFile = null;

	/**
	 * Language key with the description of the check implemented by this class.
	 *
	 * @var   string
	 * @since 7.0.0
	 */
	protected $checkLanguageKey = '';

	/**
	 * Language key and sprintf() parameters for the detected error.
	 *
	 * Position 0 of the array is the language string. Positions 1 onwards (optional) are the sprintf() parameters.
	 *
	 * @var   array
	 * @since 7.0.0
	 */
	protected $errorLanguageKey = [];

	/**
	 * Status of the current check.
	 *
	 * 1 = success; 0 = warning; -1 = failure
	 *
	 * @var   int
	 * @since 7.0.0
	 */
	protected $result = 1;

	/**
	 * Check constructor
	 *
	 * @param   string  $logFile  The log file we will be analyzing
	 *
	 * @return  void
	 * @since   7.0.0
	 */
	public function __construct($logFile)
	{
		$this->logFile = $logFile;
	}

	/**
	 * Run a check
	 *
	 * @return  void
	 * @throws  CannotOpenLogfile  If the log file cannot be opened
	 * @throws  Exception  If an unhandled error occurs
	 * @since   7.0.0
	 */
	abstract public function check();

	/**
	 * Returns the solution that should be applied to fix the issue
	 *
	 * @return  string  Steps required to fixing the issue
	 * @since   7.0.0
	 */
	abstract public function getSolution();

	/**
	 * Returns the status of this check.
	 *
	 * @return  int  1 = success; 0 = warning; -1 = failure
	 * @since   7.0.0
	 */
	public function getResult()
	{
		return $this->result;
	}

	/**
	 * Set the result for current check.
	 *
	 * @param   int  $result  1 = success; 0 = warning; -1 = failure
	 *
	 * @return  void
	 * @since   7.0.0
	 */
	public function setResult($result)
	{
		// Allow only a set of results
		if (!in_array($result, [1, 0, -1], true))
		{
			$result = -1;
		}

		$this->result = $result;
	}

	/**
	 * Gets the priority of this check
	 *
	 * @return  int
	 * @since   7.0.0
	 */
	public function getPriority()
	{
		return $this->priority;
	}

	/**
	 * Returns the language key and any sprintf() parameters for the error detected by this check
	 *
	 * @return  array  Position 0 is the lang key, everything else is the sprintf parameters
	 * @since   7.0.0
	 */
	public function getErrorLanguageKey()
	{
		return $this->errorLanguageKey;
	}

	/**
	 * @param   array  $errorLanguageKey
	 *
	 * @since   7.0.0
	 */
	public function setErrorLanguageKey($errorLanguageKey)
	{
		if (!is_array($errorLanguageKey))
		{
			throw new InvalidArgumentException(sprintf(
				"Method %s now only accepts an array as its parameter", __METHOD__
			));
		}

		$this->errorLanguageKey = $errorLanguageKey;
	}

	/**
	 * Returns the language key with this check's description
	 *
	 * @return  string
	 * @since   7.0.0
	 */
	public function getCheckLanguageKey()
	{
		return $this->checkLanguageKey;
	}

	/**
	 * Runs a scanner callback against all lines of the log file
	 *
	 * @param   callable  $callback  The scanner callback to execute on each line of the log file.
	 *
	 * @throws Exception  If the scanner callback detects an error
	 */
	protected function scanLines(callable $callback)
	{
		// Open the log file for reading
		$handle = @fopen($this->logFile, 'r');

		// Did we fail to open the log file?
		if ($handle === false)
		{
			throw new CannotOpenLogfile($this->logFile);
		}

		$prev_data = '';
		$buffer    = 65536;

		while (!feof($handle))
		{
			$line = fgets($handle);

			// Apply the callback on the current line.
			try
			{
				call_user_func($callback, $line);
			}
			catch (StopScanningEarly $e)
			{
				/**
				 * This exception is used to stop scanning the log file, e.g. if the checker has found the information
				 * it was looking for. We just need to terminate the loop WITHOUT rethrowing the exception.
				 */
				break;
			}
			catch (Exception $e)
			{
				// The check detected an error condition. Close the log file and rethrow the exception.
				fclose($handle);

				throw $e;
			}
		}

		// All right. We finished processing the log file. Close the handle.
		fclose($handle);
	}
}
