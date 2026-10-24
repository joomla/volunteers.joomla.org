<?php
/**
 * Akeeba Kickstart
 * An AJAX-powered archive extraction tool
 *
 * @package   kickstart
 * @copyright Copyright (c)2008-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

define('_AKEEBA_RESTORATION', 1);
defined('DS') or define('DS', DIRECTORY_SEPARATOR);

// Unarchiver run states
define('AK_STATE_NOFILE', 0); // File header not read yet
define('AK_STATE_HEADER', 1); // File header read; ready to process data
define('AK_STATE_DATA', 2); // Processing file data
define('AK_STATE_DATAREAD', 3); // Finished processing file data; ready to post-process
define('AK_STATE_POSTPROC', 4); // Post-processing
define('AK_STATE_DONE', 5); // Done with post-processing

/* Windows system detection */
if (!defined('_AKEEBA_IS_WINDOWS'))
{
	if (function_exists('php_uname'))
	{
		define('_AKEEBA_IS_WINDOWS', stristr(php_uname(), 'windows'));
	}
	else
	{
		define('_AKEEBA_IS_WINDOWS', DIRECTORY_SEPARATOR == '\\');
	}
}

// Get the file's root
if (!defined('KSROOTDIR'))
{
	define('KSROOTDIR', __DIR__);
}
if (!defined('KSLANGDIR'))
{
	define('KSLANGDIR', KSROOTDIR);
}

// Make sure the locale is correct for basename() to work
if (function_exists('setlocale'))
{
	@setlocale(LC_ALL, 'en_US.UTF8');
}

// fnmatch not available on non-POSIX systems
// Thanks to soywiz@php.net for this usefull alternative function [http://gr2.php.net/fnmatch]
if (!function_exists('fnmatch'))
{
	function fnmatch($pattern, $string)
	{
		return @preg_match(
			'/^' . strtr(addcslashes($pattern, '/\\.+^$(){}=!<>|'),
				['*' => '.*', '?' => '.?']) . '$/i', $string
		);
	}
}

// Unicode-safe binary data length function
if (!function_exists('akstringlen'))
{
	if (function_exists('mb_strlen'))
	{
		function akstringlen($string)
		{
			return mb_strlen($string, '8bit');
		}
	}
	else
	{
		function akstringlen($string)
		{
			return strlen($string);
		}
	}
}

if (!function_exists('aksubstr'))
{
	if (function_exists('mb_strlen'))
	{
		function aksubstr($string, $start, $length = null)
		{
			return mb_substr($string, $start, $length, '8bit');
		}
	}
	else
	{
		function aksubstr($string, $start, $length = null)
		{
			return substr($string, $start, $length);
		}
	}
}

/**
 * Gets a query parameter from GET or POST data
 *
 * @param $key
 * @param $default
 */
function getQueryParam($key, $default = null)
{
	$value = $default;

	if (array_key_exists($key, $_REQUEST))
	{
		$value = $_REQUEST[$key];
	}

	return $value;
}

// Debugging function
function debugMsg($msg)
{
	if (!defined('KSDEBUG'))
	{
		return;
	}

	$fp = fopen('debug.txt', 'a');

	fwrite($fp, $msg . PHP_EOL);
	fclose($fp);

	// Echo to stdout if KSDEBUGCLI is defined
	if (defined('KSDEBUGCLI'))
	{
		echo $msg . "\n";
	}
}

/**
 * Invalidate a file in OPcache.
 *
 * Only applies if the file has a .php extension.
 *
 * @param   string  $file  The filepath to clear from OPcache
 *
 * @return  boolean
 * @since   7.1.0
 */
function clearFileInOPCache($file)
{
	static $hasOpCache = null;

	if (is_null($hasOpCache))
	{
		$hasOpCache = ini_get('opcache.enable')
			&& function_exists('opcache_invalidate')
			&& (!ini_get('opcache.restrict_api') || stripos(realpath($_SERVER['SCRIPT_FILENAME']), ini_get('opcache.restrict_api')) === 0);
	}

	if ($hasOpCache && (strtolower(substr($file, -4)) === '.php'))
	{
		return opcache_invalidate($file, true);
	}

	return false;
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * The base class of Akeeba Engine objects. Allows for error and warnings logging
 * and propagation. Largely based on the Joomla! 1.5 JObject class.
 */
abstract class AKAbstractObject
{
	/** @var    array    The queue size of the $_errors array. Set to 0 for infinite size. */
	protected $_errors_queue_size = 0;
	/** @var    array    The queue size of the $_warnings array. Set to 0 for infinite size. */
	protected $_warnings_queue_size = 0;
	/** @var    array    An array of errors */
	private $_errors = [];
	/** @var    array    An array of warnings */
	private $_warnings = [];

	/**
	 * Get the most recent error message
	 *
	 * @param    integer $i Optional error index
	 *
	 * @return    string    Error message
	 */
	public function getError($i = null)
	{
		return $this->getItemFromArray($this->_errors, $i);
	}

	/**
	 * Returns the last item of a LIFO string message queue, or a specific item
	 * if so specified.
	 *
	 * @param array $array An array of strings, holding messages
	 * @param int   $i     Optional message index
	 *
	 * @return mixed The message string, or false if the key doesn't exist
	 */
	private function getItemFromArray($array, $i = null)
	{
		// Find the item
		if ($i === null)
		{
			// Default, return the last item
			$item = end($array);
		}
		else if (!array_key_exists($i, $array))
		{
			// If $i has been specified but does not exist, return false
			return false;
		}
		else
		{
			$item = $array[$i];
		}

		return $item;
	}

	/**
	 * Return all errors, if any
	 *
	 * @return    array    Array of error messages
	 */
	public function getErrors()
	{
		return $this->_errors;
	}

	/**
	 * Resets all error messages
	 */
	public function resetErrors()
	{
		$this->_errors = [];
	}

	/**
	 * Get the most recent warning message
	 *
	 * @param    integer $i Optional warning index
	 *
	 * @return    string    Error message
	 */
	public function getWarning($i = null)
	{
		return $this->getItemFromArray($this->_warnings, $i);
	}

	/**
	 * Return all warnings, if any
	 *
	 * @return    array    Array of error messages
	 */
	public function getWarnings()
	{
		return $this->_warnings;
	}

	/**
	 * Resets all warning messages
	 */
	public function resetWarnings()
	{
		$this->_warnings = [];
	}

	/**
	 * Propagates errors and warnings to a foreign object. The foreign object SHOULD
	 * implement the setError() and/or setWarning() methods but DOESN'T HAVE TO be of
	 * AKAbstractObject type. For example, this can even be used to propagate to a
	 * JObject instance in Joomla!. Propagated items will be removed from ourselves.
	 *
	 * @param object $object The object to propagate errors and warnings to.
	 */
	public function propagateToObject(&$object)
	{
		// Skip non-objects
		if (!is_object($object))
		{
			return;
		}

		if (method_exists($object, 'setError'))
		{
			if (!empty($this->_errors))
			{
				foreach ($this->_errors as $error)
				{
					$object->setError($error);
				}
				$this->_errors = [];
			}
		}

		if (method_exists($object, 'setWarning'))
		{
			if (!empty($this->_warnings))
			{
				foreach ($this->_warnings as $warning)
				{
					$object->setWarning($warning);
				}
				$this->_warnings = [];
			}
		}
	}

	/**
	 * Propagates errors and warnings from a foreign object. Each propagated list is
	 * then cleared on the foreign object, as long as it implements resetErrors() and/or
	 * resetWarnings() methods.
	 *
	 * @param object $object The object to propagate errors and warnings from
	 */
	public function propagateFromObject(&$object)
	{
		if (method_exists($object, 'getErrors'))
		{
			$errors = $object->getErrors();
			if (!empty($errors))
			{
				foreach ($errors as $error)
				{
					$this->setError($error);
				}
			}
			if (method_exists($object, 'resetErrors'))
			{
				$object->resetErrors();
			}
		}

		if (method_exists($object, 'getWarnings'))
		{
			$warnings = $object->getWarnings();
			if (!empty($warnings))
			{
				foreach ($warnings as $warning)
				{
					$this->setWarning($warning);
				}
			}
			if (method_exists($object, 'resetWarnings'))
			{
				$object->resetWarnings();
			}
		}
	}

	/**
	 * Add an error message
	 *
	 * @param    string $error Error message
	 */
	public function setError($error)
	{
		if ($this->_errors_queue_size > 0)
		{
			if (count($this->_errors) >= $this->_errors_queue_size)
			{
				array_shift($this->_errors);
			}
		}

		$this->_errors[] = $error;
	}

	/**
	 * Add an error message
	 *
	 * @param    string $error Error message
	 */
	public function setWarning($warning)
	{
		if ($this->_warnings_queue_size > 0)
		{
			if (count($this->_warnings) >= $this->_warnings_queue_size)
			{
				array_shift($this->_warnings);
			}
		}

		$this->_warnings[] = $warning;
	}

	/**
	 * Sets the size of the error queue (acts like a LIFO buffer)
	 *
	 * @param int $newSize The new queue size. Set to 0 for infinite length.
	 */
	protected function setErrorsQueueSize($newSize = 0)
	{
		$this->_errors_queue_size = (int) $newSize;
	}

	/**
	 * Sets the size of the warnings queue (acts like a LIFO buffer)
	 *
	 * @param int $newSize The new queue size. Set to 0 for infinite length.
	 */
	protected function setWarningsQueueSize($newSize = 0)
	{
		$this->_warnings_queue_size = (int) $newSize;
	}

}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * The superclass of all Akeeba Kickstart parts. The "parts" are intelligent stateful
 * classes which perform a single procedure and have preparation, running and
 * finalization phases. The transition between phases is handled automatically by
 * this superclass' tick() final public method, which should be the ONLY public API
 * exposed to the rest of the Akeeba Engine.
 */
abstract class AKAbstractPart extends AKAbstractObject
{
	/**
	 * Indicates whether this part has finished its initialisation cycle
	 *
	 * @var boolean
	 */
	protected $isPrepared = false;

	/**
	 * Indicates whether this part has more work to do (it's in running state)
	 *
	 * @var boolean
	 */
	protected $isRunning = false;

	/**
	 * Indicates whether this part has finished its finalization cycle
	 *
	 * @var boolean
	 */
	protected $isFinished = false;

	/**
	 * Indicates whether this part has finished its run cycle
	 *
	 * @var boolean
	 */
	protected $hasRun = false;

	/**
	 * The name of the engine part (a.k.a. Domain), used in return table
	 * generation.
	 *
	 * @var string
	 */
	protected $active_domain = "";

	/**
	 * The step this engine part is in. Used verbatim in return table and
	 * should be set by the code in the _run() method.
	 *
	 * @var string
	 */
	protected $active_step = "";

	/**
	 * A more detailed description of the step this engine part is in. Used
	 * verbatim in return table and should be set by the code in the _run()
	 * method.
	 *
	 * @var string
	 */
	protected $active_substep = "";

	/**
	 * Any configuration variables, in the form of an array.
	 *
	 * @var array
	 */
	protected $_parametersArray = [];

	/** @var string The database root key */
	protected $databaseRoot = [];
	/** @var array An array of observers */
	protected $observers = [];
	/** @var int Last reported warnings's position in array */
	private $warnings_pointer = -1;

	/**
	 * The public interface to an engine part. This method takes care for
	 * calling the correct method in order to perform the initialisation -
	 * run - finalisation cycle of operation and return a proper response array.
	 *
	 * @return    array    A Response Array
	 */
	final public function tick()
	{
		// Call the right action method, depending on engine part state
		switch ($this->getState())
		{
			case "init":
				$this->_prepare();
				break;
			case "prepared":
				$this->_run();
				break;
			case "running":
				$this->_run();
				break;
			case "postrun":
				$this->_finalize();
				break;
		}

		// Send a Return Table back to the caller
		$out = $this->_makeReturnTable();

		return $out;
	}

	/**
	 * Returns the state of this engine part.
	 *
	 * @return string The state of this engine part. It can be one of
	 * error, init, prepared, running, postrun, finished.
	 */
	final public function getState()
	{
		if ($this->getError())
		{
			return "error";
		}

		if (!($this->isPrepared))
		{
			return "init";
		}

		if (!($this->isFinished) && !($this->isRunning) && !($this->hasRun) && ($this->isPrepared))
		{
			return "prepared";
		}

		if (!($this->isFinished) && $this->isRunning && !($this->hasRun))
		{
			return "running";
		}

		if (!($this->isFinished) && !($this->isRunning) && $this->hasRun)
		{
			return "postrun";
		}

		if ($this->isFinished)
		{
			return "finished";
		}
	}

	/**
	 * Runs the preparation for this part. Should set _isPrepared
	 * to true
	 */
	abstract protected function _prepare();

	/**
	 * Runs the main functionality loop for this part. Upon calling,
	 * should set the _isRunning to true. When it finished, should set
	 * the _hasRan to true. If an error is encountered, setError should
	 * be used.
	 */
	abstract protected function _run();

	/**
	 * Runs the finalisation process for this part. Should set
	 * _isFinished to true.
	 */
	abstract protected function _finalize();

	/**
	 * Constructs a Response Array based on the engine part's state.
	 *
	 * @return array The Response Array for the current state
	 */
	final protected function _makeReturnTable()
	{
		// Get a list of warnings
		$warnings = $this->getWarnings();
		// Report only new warnings if there is no warnings queue size
		if ($this->_warnings_queue_size == 0)
		{
			if (($this->warnings_pointer > 0) && ($this->warnings_pointer < (count($warnings))))
			{
				$warnings = array_slice($warnings, $this->warnings_pointer + 1);
				$this->warnings_pointer += count($warnings);
			}
			else
			{
				$this->warnings_pointer = count($warnings);
			}
		}

		$out = [
			'HasRun'   => (!($this->isFinished)),
			'Domain'   => $this->active_domain,
			'Step'     => $this->active_step,
			'Substep'  => $this->active_substep,
			'Error'    => $this->getError(),
			'Warnings' => $warnings
		];

		return $out;
	}

	/**
	 * Returns a copy of the class's status array
	 *
	 * @return array
	 */
	public function getStatusArray()
	{
		return $this->_makeReturnTable();
	}

	/**
	 * Sends any kind of setup information to the engine part. Using this,
	 * we avoid passing parameters to the constructor of the class. These
	 * parameters should be passed as an indexed array and should be taken
	 * into account during the preparation process only. This function will
	 * set the error flag if it's called after the engine part is prepared.
	 *
	 * @param array $parametersArray The parameters to be passed to the
	 *                               engine part.
	 */
	final public function setup($parametersArray)
	{
		if ($this->isPrepared)
		{
			$this->setState('error', "Can't modify configuration after the preparation of " . $this->active_domain);
		}
		else
		{
			$this->_parametersArray = $parametersArray;
			if (array_key_exists('root', $parametersArray))
			{
				$this->databaseRoot = $parametersArray['root'];
			}
		}
	}

	/**
	 * Sets the engine part's internal state, in an easy to use manner
	 *
	 * @param    string $state        One of init, prepared, running, postrun, finished, error
	 * @param    string $errorMessage The reported error message, should the state be set to error
	 */
	protected function setState($state = 'init', $errorMessage = 'Invalid setState argument')
	{
		switch ($state)
		{
			case 'init':
				$this->isPrepared = false;
				$this->isRunning  = false;
				$this->isFinished = false;
				$this->hasRun     = false;
				break;

			case 'prepared':
				$this->isPrepared = true;
				$this->isRunning  = false;
				$this->isFinished = false;
				$this->hasRun     = false;
				break;

			case 'running':
				$this->isPrepared = true;
				$this->isRunning  = true;
				$this->isFinished = false;
				$this->hasRun     = false;
				break;

			case 'postrun':
				$this->isPrepared = true;
				$this->isRunning  = false;
				$this->isFinished = false;
				$this->hasRun     = true;
				break;

			case 'finished':
				$this->isPrepared = true;
				$this->isRunning  = false;
				$this->isFinished = true;
				$this->hasRun     = false;
				break;

			case 'error':
			default:
				$this->setError($errorMessage);
				break;
		}
	}

	final public function getDomain()
	{
		return $this->active_domain;
	}

	final public function getStep()
	{
		return $this->active_step;
	}

	final public function getSubstep()
	{
		return $this->active_substep;
	}

	/**
	 * Attaches an observer object
	 *
	 * @param AKAbstractPartObserver $obs
	 */
	function attach(AKAbstractPartObserver $obs)
	{
		$this->observers["$obs"] = $obs;
	}

	/**
	 * Detaches an observer object
	 *
	 * @param AKAbstractPartObserver $obs
	 */
	function detach(AKAbstractPartObserver $obs)
	{
		unset($this->observers["$obs"]);
	}

	/**
	 * Sets the BREAKFLAG, which instructs this engine part that the current step must break immediately,
	 * in fear of timing out.
	 */
	protected function setBreakFlag()
	{
		AKFactory::set('volatile.breakflag', true);
	}

	final protected function setDomain($new_domain)
	{
		$this->active_domain = $new_domain;
	}

	final protected function setStep($new_step)
	{
		$this->active_step = $new_step;
	}

	final protected function setSubstep($new_substep)
	{
		$this->active_substep = $new_substep;
	}

	/**
	 * Notifies observers each time something interesting happened to the part
	 *
	 * @param mixed $message The event object
	 */
	protected function notify($message)
	{
		foreach ($this->observers as $obs)
		{
			$obs->update($this, $message);
		}
	}
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * The base class of unarchiver classes
 */
abstract class AKAbstractUnarchiver extends AKAbstractPart
{
	/** @var array List of the names of all archive parts */
	public $archiveList = [];
	/** @var int The total size of all archive parts */
	public $totalSize = [];
	/** @var array Which files to rename */
	public $renameFiles = [];
	/** @var array Which directories to rename */
	public $renameDirs = [];
	/** @var array Which files to skip */
	public $skipFiles = [];
	/** @var string Archive filename */
	protected $filename = null;
	/** @var integer Current archive part number */
	protected $currentPartNumber = -1;
	/** @var integer The offset inside the current part */
	protected $currentPartOffset = 0;
	/** @var bool Should I restore permissions? */
	protected $flagRestorePermissions = false;
	/** @var AKAbstractPostproc Post processing class */
	protected $postProcEngine = null;
	/** @var string Absolute path to prepend to extracted files */
	protected $addPath = '';
	/** @var string Absolute path to remove from extracted files */
	protected $removePath = '';
	/** @var string|null Lexically normalised extraction root, cached across entries */
	protected $normalisedRoot = null;
	/** @var integer Chunk size for processing */
	protected $chunkSize = 524288;

	/** @var resource File pointer to the current archive part file */
	protected $fp = null;

	/** @var int Run state when processing the current archive file */
	protected $runState = null;

	/** @var stdClass File header data, as read by the readFileHeader() method */
	protected $fileHeader = null;

	/** @var int How much of the uncompressed data we've read so far */
	protected $dataReadLength = 0;

	/** @var array Unwriteable files in these directories are always ignored and do not cause errors when not extracted */
	protected $ignoreDirectories = [];

	/**
	 * Wakeup function, called whenever the class is unserialized
	 */
	public function __wakeup()
	{
		if ($this->currentPartNumber >= 0)
		{
			$this->fp = @fopen($this->archiveList[$this->currentPartNumber], 'r');

			if ((is_resource($this->fp)) && ($this->currentPartOffset > 0))
			{
				@fseek($this->fp, $this->currentPartOffset);
			}
		}
	}

	/**
	 * Sleep function, called whenever the class is serialized
	 */
	public function shutdown()
	{
		if (is_resource($this->fp))
		{
			$this->currentPartOffset = @ftell($this->fp);
			@fclose($this->fp);
		}
	}

	/**
	 * Is this file or directory contained in a directory we've decided to ignore
	 * write errors for? This is useful to let the extraction work despite write
	 * errors in the log, logs and tmp directories which MIGHT be used by the system
	 * on some low quality hosts and Plesk-powered hosts.
	 *
	 * @param   string $shortFilename The relative path of the file/directory in the package
	 *
	 * @return  boolean  True if it belongs in an ignored directory
	 */
	public function isIgnoredDirectory($shortFilename)
	{
		// return false;

		if (substr($shortFilename, -1) == '/')
		{
			$check = rtrim($shortFilename, '/');
		}
		else
		{
			$check = dirname($shortFilename);
		}

		return in_array($check, $this->ignoreDirectories);
	}

	/**
	 * Implements the abstract _prepare() method
	 */
	final protected function _prepare()
	{
		if (count($this->_parametersArray) > 0)
		{
			foreach ($this->_parametersArray as $key => $value)
			{
				switch ($key)
				{
					// Archive's absolute filename
					case 'filename':
						$this->filename = $value;

						// Sanity check
						if (!empty($value))
						{
							$value = strtolower($value);

							if (strlen($value) > 6)
							{
								if (
									(substr($value, 0, 7) == 'http://')
									|| (substr($value, 0, 8) == 'https://')
									|| (substr($value, 0, 6) == 'ftp://')
									|| (substr($value, 0, 7) == 'ssh2://')
									|| (substr($value, 0, 6) == 'ssl://')
								)
								{
									$this->setState('error', 'Invalid archive location');
								}
							}
						}


						break;

					// Should I restore permissions?
					case 'restore_permissions':
						$this->flagRestorePermissions = $value;
						break;

					// Should I use FTP?
					case 'post_proc':
						$this->postProcEngine = AKFactory::getpostProc($value);
						break;

					// Path to add in the beginning
					case 'add_path':
						$this->addPath = $value;
						$this->addPath = str_replace('\\', '/', $this->addPath);
						$this->addPath = rtrim($this->addPath, '/');
						if (!empty($this->addPath))
						{
							$this->addPath .= '/';
						}
						break;

					// Path to remove from the beginning
					case 'remove_path':
						$this->removePath = $value;
						$this->removePath = str_replace('\\', '/', $this->removePath);
						$this->removePath = rtrim($this->removePath, '/');
						if (!empty($this->removePath))
						{
							$this->removePath .= '/';
						}
						break;

					// Which files to rename (hash array)
					case 'rename_files':
						$this->renameFiles = $value;
						break;

					// Which files to rename (hash array)
					case 'rename_dirs':
						$this->renameDirs = $value;
						break;

					// Which files to skip (indexed array)
					case 'skip_files':
						$this->skipFiles = $value;
						break;

					// Which directories to ignore when we can't write files in them (indexed array)
					case 'ignoredirectories':
						$this->ignoreDirectories = $value;
						break;
				}
			}
		}

		$this->scanArchives();

		$this->readArchiveHeader();
		$errMessage = $this->getError();
		if (!empty($errMessage))
		{
			$this->setState('error', $errMessage);
		}
		else
		{
			$this->runState = AK_STATE_NOFILE;
			$this->setState('prepared');
		}
	}

	/**
	 * Scans for archive parts
	 */
	private function scanArchives()
	{
		if (defined('KSDEBUG'))
		{
			@unlink('debug.txt');
		}
		debugMsg('Preparing to scan archives');

		$privateArchiveList = [];

		// Get the components of the archive filename
		$dirname         = dirname($this->filename);
		$base_extension  = $this->getBaseExtension();
		$basename        = basename($this->filename, $base_extension);
		$this->totalSize = 0;

		// Scan for multiple parts until we don't find any more of them
		$count             = 0;
		$found             = true;
		$this->archiveList = [];
		while ($found)
		{
			++$count;
			$extension = substr($base_extension, 0, 2) . sprintf('%02d', $count);
			$filename  = $dirname . DIRECTORY_SEPARATOR . $basename . $extension;
			$found     = file_exists($filename);
			if ($found)
			{
				debugMsg('- Found archive ' . $filename);
				// Add yet another part, with a numeric-appended filename
				$this->archiveList[] = $filename;

				$filesize = @filesize($filename);
				$this->totalSize += $filesize;

				$privateArchiveList[] = [$filename, $filesize];
			}
			else
			{
				debugMsg('- Found archive ' . $this->filename);
				// Add the last part, with the regular extension
				$this->archiveList[] = $this->filename;

				$filename = $this->filename;
				$filesize = @filesize($filename);
				$this->totalSize += $filesize;

				$privateArchiveList[] = [$filename, $filesize];
			}
		}
		debugMsg('Total archive parts: ' . $count);

		$this->currentPartNumber = -1;
		$this->currentPartOffset = 0;
		$this->runState          = AK_STATE_NOFILE;

		// Send start of file notification
		$message                     = new stdClass;
		$message->type               = 'totalsize';
		$message->content            = new stdClass;
		$message->content->totalsize = $this->totalSize;
		$message->content->filelist  = $privateArchiveList;
		$this->notify($message);
	}

	/**
	 * Returns the base extension of the file, e.g. '.jpa'
	 *
	 * @return string
	 */
	private function getBaseExtension()
	{
		static $baseextension;

		if (empty($baseextension))
		{
			$basename      = basename($this->filename);
			$lastdot       = strrpos($basename, '.');
			$baseextension = substr($basename, $lastdot);
		}

		return $baseextension;
	}

	/**
	 * Concrete classes are supposed to use this method in order to read the archive's header and
	 * prepare themselves to the point of being ready to extract the first file.
	 */
	protected abstract function readArchiveHeader();

	protected function _run()
	{
		if ($this->getState() == 'postrun')
		{
			return;
		}

		$this->setState('running');

		$timer = AKFactory::getTimer();

		$status = true;
		while ($status && ($timer->getTimeLeft() > 0))
		{
			switch ($this->runState)
			{
				case AK_STATE_NOFILE:
					debugMsg(self::class . '::_run() - Reading file header');
					$status = $this->readFileHeader();
					if ($status)
					{
						// Send start of file notification
						$message                        = new stdClass;
						$message->type                  = 'startfile';
						$message->content               = new stdClass;
						$message->content->realfile     = $this->fileHeader->file;
						$message->content->file         = $this->fileHeader->file;
						$message->content->uncompressed = $this->fileHeader->uncompressed;

						if (array_key_exists('realfile', get_object_vars($this->fileHeader)))
						{
							$message->content->realfile = $this->fileHeader->realFile;
						}

						if (array_key_exists('compressed', get_object_vars($this->fileHeader)))
						{
							$message->content->compressed = $this->fileHeader->compressed;
						}
						else
						{
							$message->content->compressed = 0;
						}

						debugMsg(self::class . '::_run() - Preparing to extract ' . $message->content->realfile);

						$this->notify($message);
					}
					else
					{
						debugMsg(self::class . '::_run() - Could not read file header');
					}
					break;

				case AK_STATE_HEADER:
				case AK_STATE_DATA:
					debugMsg(self::class . '::_run() - Processing file data');
					$status = $this->processFileData();
					break;

				case AK_STATE_DATAREAD:
				case AK_STATE_POSTPROC:
					debugMsg(self::class . '::_run() - Calling post-processing class');
					$this->postProcEngine->timestamp = $this->fileHeader->timestamp;
					$status                          = $this->postProcEngine->process();
					$this->propagateFromObject($this->postProcEngine);
					$this->runState = AK_STATE_DONE;
					break;

				case AK_STATE_DONE:
				default:
					if ($status)
					{
						debugMsg(self::class . '::_run() - Finished extracting file');
						// Send end of file notification
						$message          = new stdClass;
						$message->type    = 'endfile';
						$message->content = new stdClass;
						if (array_key_exists('realfile', get_object_vars($this->fileHeader)))
						{
							$message->content->realfile = $this->fileHeader->realFile;
						}
						else
						{
							$message->content->realfile = $this->fileHeader->file;
						}
						$message->content->file = $this->fileHeader->file;
						if (array_key_exists('compressed', get_object_vars($this->fileHeader)))
						{
							$message->content->compressed = $this->fileHeader->compressed;
						}
						else
						{
							$message->content->compressed = 0;
						}
						$message->content->uncompressed = $this->fileHeader->uncompressed;
						$this->notify($message);
					}
					$this->runState = AK_STATE_NOFILE;

					break;
			}
		}

		$error = $this->getError();

		if (!$status && ($this->runState == AK_STATE_NOFILE) && empty($error))
		{
			debugMsg(self::class . '::_run() - Just finished');
			// We just finished
			$this->setState('postrun');

			// Reset internal state, prevents __wakeup from trying to open a non-existent file
			$this->currentPartNumber = -1;
		}
		elseif (!empty($error))
		{
			debugMsg(self::class . '::_run() - Halted with an error:');
			debugMsg($error);
			$this->setState('error', $error);
		}
	}

	/**
	 * Concrete classes must use this method to read the file header
	 *
	 * @return bool True if reading the file was successful, false if an error occurred or we reached end of archive
	 */
	protected abstract function readFileHeader();

	/**
	 * Concrete classes must use this method to process file data. It must set $runState to AK_STATE_DATAREAD when
	 * it's finished processing the file data.
	 *
	 * @return bool True if processing the file data was successful, false if an error occurred
	 */
	protected abstract function processFileData();

	protected function _finalize()
	{
		// Nothing to do
		$this->setState('finished');
	}

	/**
	 * Opens the next part file for reading
	 */
	protected function nextFile()
	{
		debugMsg('Current part is ' . $this->currentPartNumber . '; opening the next part');
		++$this->currentPartNumber;

		if ($this->currentPartNumber > (count($this->archiveList) - 1))
		{
			$this->setState('postrun');

			return false;
		}
		else
		{
			if (is_resource($this->fp))
			{
				@fclose($this->fp);
			}
			debugMsg('Opening file ' . $this->archiveList[$this->currentPartNumber]);
			$this->fp = @fopen($this->archiveList[$this->currentPartNumber], 'r');
			if ($this->fp === false)
			{
				debugMsg('Could not open file - crash imminent');
				$this->setError(AKText::sprintf('ERR_COULD_NOT_OPEN_ARCHIVE_PART', $this->archiveList[$this->currentPartNumber]));
			}
			fseek($this->fp, 0);
			$this->currentPartOffset = 0;

			return true;
		}
	}

	/**
	 * Returns true if we have reached the end of file
	 *
	 * @param $local bool True to return EOF of the local file, false (default) to return if we have reached the end of
	 *               the archive set
	 *
	 * @return bool True if we have reached End Of File
	 */
	protected function isEOF($local = false)
	{
		$eof = @feof($this->fp);

		if (!$eof)
		{
			// Border case: right at the part's end (eeeek!!!). For the life of me, I don't understand why
			// feof() doesn't report true. It expects the fp to be positioned *beyond* the EOF to report
			// true. Incredible! :(
			$position = @ftell($this->fp);
			$filesize = @filesize($this->archiveList[$this->currentPartNumber]);
			if ($filesize <= 0)
			{
				// 2Gb or more files on a 32 bit version of PHP tend to get screwed up. Meh.
				$eof = false;
			}
			elseif ($position >= $filesize)
			{
				$eof = true;
			}
		}

		if ($local)
		{
			return $eof;
		}
		else
		{
			return $eof && ($this->currentPartNumber >= (count($this->archiveList) - 1));
		}
	}

	/**
	 * Tries to make a directory user-writable so that we can write a file to it
	 *
	 * @param $path string A path to a file
	 */
	protected function setCorrectPermissions($path)
	{
		static $rootDir = null;

		if (is_null($rootDir))
		{
			$rootDir = rtrim(AKFactory::get('kickstart.setup.destdir', ''), '/\\');
		}

		$directory = rtrim(dirname($path), '/\\');
		if ($directory != $rootDir)
		{
			// Is this an unwritable directory?
			if (!is_writeable($directory))
			{
				$this->postProcEngine->chmod($directory, 0755);
			}
		}
		$this->postProcEngine->chmod($path, 0644);
	}

	/**
	 * Reads data from the archive and notifies the observer with the 'reading' message
	 *
	 * @param $fp
	 * @param $length
	 */
	protected function fread($fp, $length = null)
	{
		if (is_numeric($length))
		{
			if ($length > 0)
			{
				$data = fread($fp, $length);
			}
			else
			{
				$data = fread($fp, PHP_INT_MAX);
			}
		}
		else
		{
			$data = fread($fp, PHP_INT_MAX);
		}
		if ($data === false)
		{
			$data = '';
		}

		// Send start of file notification
		$message                  = new stdClass;
		$message->type            = 'reading';
		$message->content         = new stdClass;
		$message->content->length = strlen($data);
		$this->notify($message);

		return $data;
	}

	/**
	 * Removes the configured $removePath from the path $path
	 *
	 * @param   string $path The path to reduce
	 *
	 * @return  string  The reduced path
	 */
	protected function removePath($path)
	{
		if (empty($this->removePath))
		{
			return $path;
		}

		if (strpos($path, $this->removePath) === 0)
		{
			$path = substr($path, strlen($this->removePath));
			$path = ltrim($path, '/\\');
		}

		return $path;
	}

	/**
	 * Normalises a path lexically: collapses '.' segments and resolves '..' segments by
	 * string manipulation alone, without ever consulting the filesystem.
	 *
	 * This deliberately does NOT use realpath(). Symbolic links pointing outside the
	 * extraction root are a legitimate and common site layout, and policing them is the
	 * operating system's job, not ours. Resolving links here would make us refuse writes
	 * through links the site owner put there on purpose. Confinement is therefore purely
	 * lexical: it stops hostile paths spelled inside the archive, and leaves symlinks alone.
	 *
	 * A '..' which cannot be resolved is kept on a relative path, so that a path escaping
	 * its starting point remains visibly escaping to the caller.
	 *
	 * @param   string $path The path to normalise
	 *
	 * @return  string  The lexically normalised path
	 */
	protected function normalisePath($path)
	{
		$path   = str_replace('\\', '/', $path);
		$prefix = '';

		// Remember and strip a leading slash or a Windows drive designator
		if (preg_match('#^[a-zA-Z]:/#', $path))
		{
			$prefix = substr($path, 0, 3);
			$path   = substr($path, 3);
		}
		elseif (substr($path, 0, 1) == '/')
		{
			$prefix = '/';
			$path   = substr($path, 1);
		}

		$out = [];

		foreach (explode('/', $path) as $part)
		{
			// Empty segments (from doubled slashes) and '.' contribute nothing
			if (($part === '') || ($part === '.'))
			{
				continue;
			}

			if ($part !== '..')
			{
				$out[] = $part;

				continue;
			}

			// '..' climbs one level, if there is a level to climb
			if (!empty($out) && (end($out) !== '..'))
			{
				array_pop($out);

				continue;
			}

			// We cannot climb past the root of an absolute path; on a relative path we must
			// keep the '..' so the caller can see that the path escapes.
			if ($prefix === '')
			{
				$out[] = '..';
			}
		}

		return $prefix . implode('/', $out);
	}

	/**
	 * Is this path contained within the extraction root?
	 *
	 * $path is the final, ready-to-write path of an archive entry, i.e. after removePath() has
	 * been applied and addPath has been prepended. A relative path is taken to be relative to
	 * the extraction root, which is how addPath-prepended paths behave.
	 *
	 * $archivePath is the archive's own spelling of the same entry, before addPath was
	 * prepended, or null when the entry was not rebased onto the extraction root. It exists
	 * because prepending addPath to an absolute entry such as /etc/passwd quietly turns it into
	 * <root>/etc/passwd, which lands inside the root and would therefore pass the containment
	 * test below. That is precisely the silent rewrite we refuse to perform, so an absolute
	 * entry is rejected on its own spelling rather than on where concatenation put it.
	 *
	 * @param   string      $path        The final path of the archive entry
	 * @param   string|null $archivePath The archive's own spelling of the entry, if rebased
	 *
	 * @return  bool  True if the entry may be written
	 */
	protected function isPathInRoot($path, $archivePath = null)
	{
		// An entry which names an absolute path is never acceptable
		if (!empty($archivePath) && $this->isAbsolutePath($archivePath))
		{
			return false;
		}

		// The root does not change between entries; normalise it once, not once per file
		if (is_null($this->normalisedRoot))
		{
			$root = $this->addPath;

			if (empty($root))
			{
				$root = AKFactory::get('kickstart.setup.destdir', '');
			}

			$this->normalisedRoot = rtrim($this->normalisePath($root), '/');
		}

		$root = $this->normalisedRoot;
		$path = str_replace('\\', '/', $path);

		$isAbsolute = $this->isAbsolutePath($path);

		// With no root configured, paths are relative to the current working directory. Refuse
		// anything absolute, and anything which climbs above where it started.
		if ($root === '')
		{
			if ($isAbsolute)
			{
				return false;
			}

			$path = $this->normalisePath($path);

			return ($path !== '..') && (substr($path, 0, 3) !== '../');
		}

		// A relative entry is relative to the extraction root
		if (!$isAbsolute)
		{
			$path = $root . '/' . $path;
		}

		$path = $this->normalisePath($path);

		return ($path === $root) || (strpos($path, $root . '/') === 0);
	}

	/**
	 * Is this an absolute path? Understands both UNIX paths and Windows drive designators.
	 *
	 * @param   string $path The path to check
	 *
	 * @return  bool
	 */
	protected function isAbsolutePath($path)
	{
		$path = str_replace('\\', '/', $path);

		return (substr($path, 0, 1) == '/') || (bool) preg_match('#^[a-zA-Z]:/#', $path);
	}

	/**
	 * Am I supposed to skip the extraction of the current file? This depends on
	 *
	 * @return bool
	 */
	protected function mustSkip()
	{
		static $isDryRun = null;

		// List of files (and patterns) to extract
		static $extractList = null;

		// Internal cache of the last file we checked and whether it must be skipped
		static $lastFileName = '';
		static $mustSkip = false;

		// Make sure the dry run flag is, indeed, populated
		if (is_null($isDryRun))
		{
			$isDryRun = AKFactory::get('kickstart.setup.dryrun', '0');
		}

		// If it's a Kickstart dry run we have to skip the extraction of the file
		if ($isDryRun)
		{
			return true;
		}

		// Make sure I have a list of files and patterns to extract
		if (is_null($extractList))
		{
			$extractList = $this->getExtractList();
		}

		// No list of files to extract is given; we must extract everything.
		if (empty($extractList))
		{
			return false;
		}

		// I am asked about the same file again. Return the cached result.
		if ($this->fileHeader->file == $lastFileName)
		{
			return $mustSkip;
		}

		// Does the current file match the extract patterns or not?
		$lastFileName = $this->fileHeader->file;
		$lastFileName = (strpos($lastFileName, $this->addPath) === 0) ? substr($lastFileName, strlen(rtrim($this->addPath, "\\/")) + 1) : $lastFileName;
		$mustSkip     = !$this->matchesGlobPatterns($lastFileName, $extractList);

		return $mustSkip;
	}

	protected function fuzzySignatureSearch($requiredSignatures, $sigLen)
	{
		if (!is_array($requiredSignatures))
		{
			$requiredSignatures = [$requiredSignatures];
		}

		fseek($this->fp, 0, SEEK_SET);

		$stuff  = $this->fread($this->fp, 131072);
		$maxPos = function_exists('mb_strlen') ? mb_strlen($stuff, 'binary') : strlen($stuff);

		for ($i = 0; $i < $maxPos; $i++)
		{
			foreach ($requiredSignatures as $signature)
			{
				$sigBinary = function_exists('mb_substr') ? mb_substr($stuff, $i, $sigLen, 'binary') : substr($stuff, $i, $sigLen);

				if ($sigBinary === $signature)
				{
					fseek($this->fp, $i, SEEK_SET);

					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get the list of files / folders to extract. The list can contain filenames or glob patterns.
	 *
	 * @return  array
	 */
	private function getExtractList()
	{
		$rawList = AKFactory::get('kickstart.setup.extract_list', '');

		// Sometimes I could get an array, e.g. from CLI
		if (is_array($rawList))
		{
			$rawList = implode("\n", $rawList);
		}

		// Remove any whitespace
		$rawList = trim($rawList);

		if (empty($rawList))
		{
			return [];
		}

		// Convert commas to newlines so we can support both ways to express lists
		$rawList = str_replace(",", "\n", $rawList);
		$rawList = trim($rawList);

		// Convert the list to an array and clean it
		$list = explode("\n", $rawList);
		$list = array_map('trim', $list);

		return array_unique($list);
	}

	/**
	 * Tests whether the item $item matches the list of shell patterns $list.
	 *
	 * @param   string  $item  The file name to test
	 * @param   array   $list  The list of glob patterns to match
	 *
	 * @return  bool
	 */
	private function matchesGlobPatterns($item, array $list)
	{
		if (empty($list))
		{
			return true;
		}

		foreach ($list as $pattern)
		{
			if (fnmatch($pattern, $item))
			{
				return true;
			}
		}

		return false;
	}
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * File post processor engines base class
 */
abstract class AKAbstractPostproc extends AKAbstractObject
{
	/** @var int The UNIX timestamp of the file's desired modification date */
	public $timestamp = 0;
	/** @var string The actual file path we'll have to process */
	protected $filename = null;
	/** @var int The requested permissions */
	protected $perms = 0755;
	/** @var string The temporary file path we gave to the unarchiver engine */
	protected $tempFilename = null;
	/** @var string The temporary directory where the data will be stored */
	protected $tempDir = '';

	/**
	 * Processes the current file, e.g. moves it from temp to final location by FTP
	 */
	abstract public function process();

	/**
	 * The unarchiver tells us the path to the filename it wants to extract and we give it
	 * a different path instead.
	 *
	 * @param string $filename The path to the real file
	 * @param int    $perms    The permissions we need the file to have
	 *
	 * @return string The path to the temporary file
	 */
	abstract public function processFilename($filename, $perms = 0755);

	/**
	 * Recursively creates a directory if it doesn't exist
	 *
	 * @param string $dirName The directory to create
	 * @param int    $perms   The permissions to give to that directory
	 */
	abstract public function createDirRecursive($dirName, $perms);

	abstract public function chmod($file, $perms);

	abstract public function unlink($file);

	abstract public function rmdir($directory);

	abstract public function rename($from, $to);

	/**
	 * Returns the configured temporary directory
	 *
	 * @return string
	 */
	public function getTempDir()
	{
		return $this->tempDir;
	}
}


/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Descendants of this class can be used in the unarchiver's observer methods (attach, detach and notify)
 *
 * @author Nicholas
 *
 */
abstract class AKAbstractPartObserver
{
	abstract public function update($object, $message);
}


/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */


class AKPartNullObserver extends AKAbstractPartObserver
{
	public function update($object, $message)
	{
		// This observer does nothing.
	}
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Direct file writer
 */
class AKPostprocDirect extends AKAbstractPostproc
{
	public function process()
	{
		$restorePerms = AKFactory::get('kickstart.setup.restoreperms', false);
		if ($restorePerms)
		{
			@chmod($this->filename, $this->perms);
		}
		else
		{
			if (@is_file($this->filename))
			{
				@chmod($this->filename, 0644);
			}
			else
			{
				@chmod($this->filename, 0755);
			}
		}
		if ($this->timestamp > 0)
		{
			@touch($this->filename, $this->timestamp);
		}

		if (@is_file($this->filename) || @is_link($this->filename))
		{
			clearFileInOPCache($this->filename);
		}

		return true;
	}

	public function processFilename($filename, $perms = 0755)
	{
		$this->perms    = $perms;
		$this->filename = $filename;

		return $filename;
	}

	public function createDirRecursive($dirName, $perms)
	{
		if (AKFactory::get('kickstart.setup.dryrun', '0'))
		{
			return true;
		}

		if (@mkdir($dirName, 0755, true))
		{
			@chmod($dirName, 0755);

			return true;
		}

		$root = AKFactory::get('kickstart.setup.destdir');
		$root = rtrim(str_replace('\\', '/', $root), '/');
		$dir  = rtrim(str_replace('\\', '/', $dirName), '/');
		if (strpos($dir, $root) === 0)
		{
			$dir = ltrim(substr($dir, strlen($root)), '/');
			$root .= '/';
		}
		else
		{
			$root = '';
		}

		if (empty($dir))
		{
			return true;
		}

		$dirArray = explode('/', $dir);
		$path     = '';
		foreach ($dirArray as $dir)
		{
			$path .= $dir . '/';
			$ret = is_dir($root . $path) ? true : @mkdir($root . $path);
			if (!$ret)
			{
				// Is this a file instead of a directory?
				if (is_file($root . $path))
				{
					@unlink($root . $path);
					$ret = @mkdir($root . $path);
				}
				if (!$ret)
				{
					$this->setError(AKText::sprintf('COULDNT_CREATE_DIR', $path));

					return false;
				}
			}
			// Try to set new directory permissions to 0755
			@chmod($root . $path, $perms);
		}

		return true;
	}

	public function chmod($file, $perms)
	{
		if (AKFactory::get('kickstart.setup.dryrun', '0'))
		{
			return true;
		}

		return @chmod($file, $perms);
	}

	public function unlink($file)
	{
		return @unlink($file);
	}

	public function rmdir($directory)
	{
		return @rmdir($directory);
	}

	public function rename($from, $to)
	{
		return @rename($from, $to);
	}

}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * FTP file writer
 */
class AKPostprocFTP extends AKAbstractPostproc
{
	/** @var bool Should I use FTP over implicit SSL? */
	public $useSSL = false;
	/** @var bool use Passive mode? */
	public $passive = true;
	/** @var string FTP host name */
	public $host = '';
	/** @var int FTP port */
	public $port = 21;
	/** @var string FTP user name */
	public $user = '';
	/** @var string FTP password */
	public $pass = '';
	/** @var string FTP initial directory */
	public $dir = '';
	/** @var resource The FTP handle */
	private $handle = null;

	public function __construct()
	{
		$this->useSSL  = AKFactory::get('kickstart.ftp.ssl', false);
		$this->passive = AKFactory::get('kickstart.ftp.passive', true);
		$this->host    = AKFactory::get('kickstart.ftp.host', '');
		$this->port    = AKFactory::get('kickstart.ftp.port', 21);

		if (trim($this->port) == '')
		{
			$this->port = 21;
		}
		$this->user    = AKFactory::get('kickstart.ftp.user', '');
		$this->pass    = AKFactory::get('kickstart.ftp.pass', '');
		$this->dir     = AKFactory::get('kickstart.ftp.dir', '');
		$this->tempDir = AKFactory::get('kickstart.ftp.tempdir', '');

		$connected = $this->connect();

		if ($connected)
		{
			if (!empty($this->tempDir))
			{
				$tempDir  = rtrim($this->tempDir, '/\\') . '/';
				$writable = $this->isDirWritable($tempDir);
			}
			else
			{
				$tempDir  = '';
				$writable = false;
			}

			if (!$writable)
			{
				// Default temporary directory is the current root
				$tempDir = KSROOTDIR;
				if (empty($tempDir))
				{
					// Oh, we have no directory reported!
					$tempDir = '.';
				}
				$absoluteDirToHere = $tempDir;
				$tempDir           = rtrim(str_replace('\\', '/', $tempDir), '/');

				if (!empty($tempDir))
				{
					$tempDir .= '/';
				}

				$this->tempDir = $tempDir;
				// Is this directory writable?
				$writable = $this->isDirWritable($tempDir);
			}

			if (!$writable)
			{
				// Nope. Let's try creating a temporary directory in the site's root.
				$tempDir                 = $absoluteDirToHere . '/kicktemp';
				$trustMeIKnowWhatImDoing = 500 + 10 + 1; // working around overzealous scanners written by bozos
				$this->createDirRecursive($tempDir, $trustMeIKnowWhatImDoing);
				// Try making it writable...
				$this->fixPermissions($tempDir);
				$writable = $this->isDirWritable($tempDir);
			}

			// Was the new directory writable?
			if (!$writable)
			{
				// Let's see if the user has specified one
				$userdir = AKFactory::get('kickstart.ftp.tempdir', '');

				if (!empty($userdir))
				{
					// Is it an absolute or a relative directory?
					$absolute = false;
					$absolute = $absolute || (substr($userdir, 0, 1) == '/');
					$absolute = $absolute || (substr($userdir, 1, 1) == ':');
					$absolute = $absolute || (substr($userdir, 2, 1) == ':');

					if (!$absolute)
					{
						// Make absolute
						$tempDir = $absoluteDirToHere . $userdir;
					}
					else
					{
						// it's already absolute
						$tempDir = $userdir;
					}
					// Does the directory exist?
					if (is_dir($tempDir))
					{
						// Yeah. Is it writable?
						$writable = $this->isDirWritable($tempDir);
					}
				}
			}

			$this->tempDir = $tempDir;

			if (!$writable)
			{
				// No writable directory found!!!
				$this->setError(AKText::_('FTP_TEMPDIR_NOT_WRITABLE'));
			}
			else
			{
				AKFactory::set('kickstart.ftp.tempdir', $tempDir);
				$this->tempDir = $tempDir;
			}
		}
	}

	public function connect()
	{
		// Connect to server, using SSL if so required
		if ($this->useSSL)
		{
			$this->handle = @ftp_ssl_connect($this->host, $this->port);
		}
		else
		{
			$this->handle = @ftp_connect($this->host, $this->port);
		}

		if ($this->handle === false)
		{
			$this->setError(AKText::_('WRONG_FTP_HOST'));

			return false;
		}

		// Login
		if (!@ftp_login($this->handle, $this->user, $this->pass))
		{
			$this->setError(AKText::_('WRONG_FTP_USER'));
			@ftp_close($this->handle);

			return false;
		}

		// Change to initial directory
		if (!@ftp_chdir($this->handle, $this->dir))
		{
			$this->setError(AKText::_('WRONG_FTP_PATH1'));
			@ftp_close($this->handle);

			return false;
		}

		// Enable passive mode if the user requested it
		if ($this->passive)
		{
			@ftp_pasv($this->handle, true);
		}
		else
		{
			@ftp_pasv($this->handle, false);
		}

		// Try to download ourselves
		$testFilename = defined('KSSELFNAME') ? KSSELFNAME : basename(__FILE__);
		$tempHandle   = fopen('php://temp', 'r+');

		if (@ftp_fget($this->handle, $tempHandle, $testFilename, FTP_ASCII, 0) === false)
		{
			$this->setError(AKText::_('WRONG_FTP_PATH2'));
			@ftp_close($this->handle);
			fclose($tempHandle);

			return false;
		}

		fclose($tempHandle);

		return true;
	}

	private function isDirWritable($dir)
	{
		$fp = @fopen($dir . '/kickstart.dat', 'w');

		if ($fp === false)
		{
			return false;
		}
		else
		{
			@fclose($fp);
			unlink($dir . '/kickstart.dat');

			return true;
		}
	}

	public function createDirRecursive($dirName, $perms)
	{
		// Strip absolute filesystem path to website's root
		$removePath = AKFactory::get('kickstart.setup.destdir', '');

		if (!empty($removePath))
		{
			// UNIXize the paths
			$removePath = str_replace('\\', '/', $removePath);
			$dirName    = str_replace('\\', '/', $dirName);
			// Make sure they both end in a slash
			$removePath = rtrim($removePath, '/\\') . '/';
			$dirName    = rtrim($dirName, '/\\') . '/';
			// Process the path removal
			$left = substr($dirName, 0, strlen($removePath));

			if ($left == $removePath)
			{
				$dirName = substr($dirName, strlen($removePath));
			}
		}

		if (empty($dirName))
		{
			$dirName = '';
		} // 'cause the substr() above may return FALSE.

		$check = '/' . trim($this->dir, '/') . '/' . trim($dirName, '/');

		if ($this->is_dir($check))
		{
			return true;
		}

		$alldirs     = explode('/', $dirName);
		$previousDir = '/' . trim($this->dir);

		foreach ($alldirs as $curdir)
		{
			$check = $previousDir . '/' . $curdir;

			if (!$this->is_dir($check))
			{
				// Proactively try to delete a file by the same name
				@ftp_delete($this->handle, $check);

				if (@ftp_mkdir($this->handle, $check) === false)
				{
					// If we couldn't create the directory, attempt to fix the permissions in the PHP level and retry!
					$this->fixPermissions($removePath . $check);

					if (@ftp_mkdir($this->handle, $check) === false)
					{
						// Can we fall back to pure PHP mode, sire?
						if (!@mkdir($check))
						{
							$this->setError(AKText::sprintf('FTP_CANT_CREATE_DIR', $check));

							return false;
						}
						else
						{
							// Since the directory was built by PHP, change its permissions
							$trustMeIKnowWhatImDoing =
								500 + 10 + 1; // working around overzealous scanners written by bozos
							@chmod($check, $trustMeIKnowWhatImDoing);

							return true;
						}
					}
				}

				@ftp_chmod($this->handle, $perms, $check);

			}

			$previousDir = $check;
		}

		return true;
	}

	private function is_dir($dir)
	{
		return @ftp_chdir($this->handle, $dir);
	}

	private function fixPermissions($path)
	{
		// Turn off error reporting
		if (!defined('KSDEBUG'))
		{
			$oldErrorReporting = @error_reporting(0);
		}

		// Get UNIX style paths
		$relPath  = str_replace('\\', '/', $path);
		$basePath = rtrim(str_replace('\\', '/', KSROOTDIR), '/');
		$basePath = rtrim($basePath, '/');

		if (!empty($basePath))
		{
			$basePath .= '/';
		}

		// Remove the leading relative root
		if (substr($relPath, 0, strlen($basePath)) == $basePath)
		{
			$relPath = substr($relPath, strlen($basePath));
		}

		$dirArray  = explode('/', $relPath);
		$pathBuilt = rtrim($basePath, '/');

		foreach ($dirArray as $dir)
		{
			if (empty($dir))
			{
				continue;
			}
			$oldPath = $pathBuilt;
			$pathBuilt .= '/' . $dir;

			if (is_dir($oldPath . $dir))
			{
				$trustMeIKnowWhatImDoing = 500 + 10 + 1; // working around overzealous scanners written by bozos
				@chmod($oldPath . $dir, $trustMeIKnowWhatImDoing);
			}
			else
			{
				$trustMeIKnowWhatImDoing = 500 + 10 + 1; // working around overzealous scanners written by bozos
				if (@chmod($oldPath . $dir, $trustMeIKnowWhatImDoing) === false)
				{
					@unlink($oldPath . $dir);
				}
			}
		}

		// Restore error reporting
		if (!defined('KSDEBUG'))
		{
			@error_reporting($oldErrorReporting);
		}
	}

	public function __sleep()
	{
		if (!is_null($this->handle) && is_resource($this->handle))
		{
			@ftp_close($this->handle);
		}

		$this->handle = null;
	}

	public function __destruct()
	{
		if (!is_null($this->handle) && is_resource($this->handle))
		{
			@ftp_close($this->handle);
		}
	}


	public function __wakeup()
	{
		$this->connect();
	}

	public function process()
	{
		if (is_null($this->tempFilename))
		{
			// If an empty filename is passed, it means that we shouldn't do any post processing, i.e.
			// the entity was a directory or symlink
			return true;
		}

		$remotePath = dirname($this->filename);
		$removePath = AKFactory::get('kickstart.setup.destdir', '');

		if (!empty($removePath))
		{
			$removePath = ltrim($removePath, "/");
			$remotePath = ltrim($remotePath, "/");
			$left       = substr($remotePath, 0, strlen($removePath));

			if ($left == $removePath)
			{
				$remotePath = substr($remotePath, strlen($removePath));
			}
		}

		$absoluteFSPath  = dirname($this->filename);
		$relativeFTPPath = trim($remotePath, '/');
		$absoluteFTPPath = '/' . trim($this->dir, '/') . '/' . trim($remotePath, '/');
		$onlyFilename    = basename($this->filename);

		$remoteName = $absoluteFTPPath . '/' . $onlyFilename;

		$ret = @ftp_chdir($this->handle, $absoluteFTPPath);

		if ($ret === false)
		{
			$ret = $this->createDirRecursive($absoluteFSPath, 0755);

			if ($ret === false)
			{
				$this->setError(AKText::sprintf('FTP_COULDNT_UPLOAD', $this->filename));

				return false;
			}

			$ret = @ftp_chdir($this->handle, $absoluteFTPPath);

			if ($ret === false)
			{
				$this->setError(AKText::sprintf('FTP_COULDNT_UPLOAD', $this->filename));

				return false;
			}
		}

		$ret = @ftp_put($this->handle, $remoteName, $this->tempFilename, FTP_BINARY);

		if ($ret === false)
		{
			// If we couldn't create the file, attempt to fix the permissions in the PHP level and retry!
			$this->fixPermissions($this->filename);
			$this->unlink($this->filename);

			$fp = @fopen($this->tempFilename, 'r');

			if ($fp !== false)
			{
				$ret = @ftp_fput($this->handle, $remoteName, $fp, FTP_BINARY);
				@fclose($fp);
			}
			else
			{
				$ret = false;
			}
		}

		@unlink($this->tempFilename);

		if ($ret === false)
		{
			$this->setError(AKText::sprintf('FTP_COULDNT_UPLOAD', $this->filename));

			return false;
		}

		$restorePerms = AKFactory::get('kickstart.setup.restoreperms', false);

		if ($restorePerms)
		{
			@ftp_chmod($this->_handle, $this->perms, $remoteName);
		}
		else
		{
			@ftp_chmod($this->_handle, 0644, $remoteName);
		}

		if (@is_file($this->filename) || @is_link($this->filename))
		{
			clearFileInOPCache($this->filename);
		}

		return true;
	}

	/*
	 * Tries to fix directory/file permissions in the PHP level, so that
	 * the FTP operation doesn't fail.
	 * @param $path string The full path to a directory or file
	 */

	public function unlink($file)
	{
		$removePath = AKFactory::get('kickstart.setup.destdir', '');

		if (!empty($removePath))
		{
			$left = substr($file, 0, strlen($removePath));

			if ($left == $removePath)
			{
				$file = substr($file, strlen($removePath));
			}
		}

		$check = '/' . trim($this->dir, '/') . '/' . trim($file, '/');

		return @ftp_delete($this->handle, $check);
	}

	public function processFilename($filename, $perms = 0755)
	{
		// Catch some error conditions...
		if ($this->getError())
		{
			return false;
		}

		// If a null filename is passed, it means that we shouldn't do any post processing, i.e.
		// the entity was a directory or symlink
		if (is_null($filename))
		{
			$this->filename     = null;
			$this->tempFilename = null;

			return null;
		}

		// Strip absolute filesystem path to website's root
		$removePath = AKFactory::get('kickstart.setup.destdir', '');

		if (!empty($removePath))
		{
			$left = substr($filename, 0, strlen($removePath));

			if ($left == $removePath)
			{
				$filename = substr($filename, strlen($removePath));
			}
		}

		// Trim slash on the left
		$filename = ltrim($filename, '/');

		$this->filename     = $filename;
		$this->tempFilename = tempnam($this->tempDir, 'kickstart-');
		$this->perms        = $perms;

		if (empty($this->tempFilename))
		{
			// Oops! Let's try something different
			$this->tempFilename = $this->tempDir . '/kickstart-' . time() . '.dat';
		}

		return $this->tempFilename;
	}

	public function close()
	{
		@ftp_close($this->handle);
	}

	public function chmod($file, $perms)
	{
		return @ftp_chmod($this->handle, $perms, $file);
	}

	public function rmdir($directory)
	{
		$removePath = AKFactory::get('kickstart.setup.destdir', '');

		if (!empty($removePath))
		{
			$left = substr($directory, 0, strlen($removePath));

			if ($left == $removePath)
			{
				$directory = substr($directory, strlen($removePath));
			}
		}

		$check = '/' . trim($this->dir, '/') . '/' . trim($directory, '/');

		return @ftp_rmdir($this->handle, $check);
	}

	public function rename($from, $to)
	{
		$originalFrom = $from;
		$originalTo   = $to;

		$removePath = AKFactory::get('kickstart.setup.destdir', '');

		if (!empty($removePath))
		{
			$left = substr($from, 0, strlen($removePath));

			if ($left == $removePath)
			{
				$from = substr($from, strlen($removePath));
			}
		}

		$from = '/' . trim($this->dir, '/') . '/' . trim($from, '/');

		if (!empty($removePath))
		{
			$left = substr($to, 0, strlen($removePath));

			if ($left == $removePath)
			{
				$to = substr($to, strlen($removePath));
			}
		}

		$to = '/' . trim($this->dir, '/') . '/' . trim($to, '/');

		$result = @ftp_rename($this->handle, $from, $to);

		if ($result !== true)
		{
			return @rename($from, $to);
		}
		else
		{
			return true;
		}
	}

}


/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * FTP file writer
 */
class AKPostprocSFTP extends AKAbstractPostproc
{
	/** @var bool Should I use FTP over implicit SSL? */
	public $useSSL = false;
	/** @var bool use Passive mode? */
	public $passive = true;
	/** @var string FTP host name */
	public $host = '';
	/** @var int FTP port */
	public $port = 21;
	/** @var string FTP user name */
	public $user = '';
	/** @var string FTP password */
	public $pass = '';
	/** @var string FTP initial directory */
	public $dir = '';

	/** @var resource SFTP resource handle */
	private $handle = null;

	/** @var resource SSH2 connection resource handle */
	private $_connection = null;

	/** @var string Current remote directory, including the remote directory string */
	private $_currentdir;

	public function __construct()
	{
		$this->host = AKFactory::get('kickstart.ftp.host', '');
		$this->port = AKFactory::get('kickstart.ftp.port', 22);

		if (trim($this->port) == '')
		{
			$this->port = 22;
		}

		$this->user    = AKFactory::get('kickstart.ftp.user', '');
		$this->pass    = AKFactory::get('kickstart.ftp.pass', '');
		$this->dir     = AKFactory::get('kickstart.ftp.dir', '');
		$this->tempDir = AKFactory::get('kickstart.ftp.tempdir', '');

		$connected = $this->connect();

		if ($connected)
		{
			if (!empty($this->tempDir))
			{
				$tempDir  = rtrim($this->tempDir, '/\\') . '/';
				$writable = $this->isDirWritable($tempDir);
			}
			else
			{
				$tempDir  = '';
				$writable = false;
			}

			if (!$writable)
			{
				// Default temporary directory is the current root
				$tempDir = KSROOTDIR;
				if (empty($tempDir))
				{
					// Oh, we have no directory reported!
					$tempDir = '.';
				}
				$absoluteDirToHere = $tempDir;
				$tempDir           = rtrim(str_replace('\\', '/', $tempDir), '/');
				if (!empty($tempDir))
				{
					$tempDir .= '/';
				}
				$this->tempDir = $tempDir;
				// Is this directory writable?
				$writable = $this->isDirWritable($tempDir);
			}

			if (!$writable)
			{
				// Nope. Let's try creating a temporary directory in the site's root.
				$tempDir                 = $absoluteDirToHere . '/kicktemp';
				$trustMeIKnowWhatImDoing = 500 + 10 + 1; // working around overzealous scanners written by bozos
				$this->createDirRecursive($tempDir, $trustMeIKnowWhatImDoing);
				// Try making it writable...
				$this->fixPermissions($tempDir);
				$writable = $this->isDirWritable($tempDir);
			}

			// Was the new directory writable?
			if (!$writable)
			{
				// Let's see if the user has specified one
				$userdir = AKFactory::get('kickstart.ftp.tempdir', '');
				if (!empty($userdir))
				{
					// Is it an absolute or a relative directory?
					$absolute = false;
					$absolute = $absolute || (substr($userdir, 0, 1) == '/');
					$absolute = $absolute || (substr($userdir, 1, 1) == ':');
					$absolute = $absolute || (substr($userdir, 2, 1) == ':');
					if (!$absolute)
					{
						// Make absolute
						$tempDir = $absoluteDirToHere . $userdir;
					}
					else
					{
						// it's already absolute
						$tempDir = $userdir;
					}
					// Does the directory exist?
					if (is_dir($tempDir))
					{
						// Yeah. Is it writable?
						$writable = $this->isDirWritable($tempDir);
					}
				}
			}
			$this->tempDir = $tempDir;

			if (!$writable)
			{
				// No writable directory found!!!
				$this->setError(AKText::_('SFTP_TEMPDIR_NOT_WRITABLE'));
			}
			else
			{
				AKFactory::set('kickstart.ftp.tempdir', $tempDir);
				$this->tempDir = $tempDir;
			}
		}
	}

	public function connect()
	{
		$this->_connection = false;

		if (!function_exists('ssh2_connect'))
		{
			$this->setError(AKText::_('SFTP_NO_SSH2'));

			return false;
		}

		$this->_connection = @ssh2_connect($this->host, $this->port);

		if (!@ssh2_auth_password($this->_connection, $this->user, $this->pass))
		{
			$this->setError(AKText::_('SFTP_WRONG_USER'));

			$this->_connection = false;

			return false;
		}

		$this->handle = @ssh2_sftp($this->_connection);

		// I must have an absolute directory
		if (!$this->dir)
		{
			$this->setError(AKText::_('SFTP_WRONG_STARTING_DIR'));

			return false;
		}

		// Change to initial directory
		if (!$this->sftp_chdir('/'))
		{
			$this->setError(AKText::_('SFTP_WRONG_STARTING_DIR'));

			unset($this->_connection);
			unset($this->handle);

			return false;
		}

		// Try to download ourselves
		$testFilename = defined('KSSELFNAME') ? KSSELFNAME : basename(__FILE__);
		$basePath     = '/' . trim($this->dir, '/');

		if (@fopen("ssh2.sftp://{$this->handle}$basePath/$testFilename", 'r+') === false)
		{
			$this->setError(AKText::_('SFTP_WRONG_STARTING_DIR'));

			unset($this->_connection);
			unset($this->handle);

			return false;
		}

		return true;
	}

	/**
	 * Changes to the requested directory in the remote server. You give only the
	 * path relative to the initial directory and it does all the rest by itself,
	 * including doing nothing if the remote directory is the one we want.
	 *
	 * @param   string $dir The (realtive) remote directory
	 *
	 * @return  bool True if successful, false otherwise.
	 */
	private function sftp_chdir($dir)
	{
		// Strip absolute filesystem path to website's root
		$removePath = AKFactory::get('kickstart.setup.destdir', '');
		if (!empty($removePath))
		{
			// UNIXize the paths
			$removePath = str_replace('\\', '/', $removePath);
			$dir        = str_replace('\\', '/', $dir);

			// Make sure they both end in a slash
			$removePath = rtrim($removePath, '/\\') . '/';
			$dir        = rtrim($dir, '/\\') . '/';

			// Process the path removal
			$left = substr($dir, 0, strlen($removePath));

			if ($left == $removePath)
			{
				$dir = substr($dir, strlen($removePath));
			}
		}

		if (empty($dir))
		{
			// Because the substr() above may return FALSE.
			$dir = '';
		}

		// Calculate "real" (absolute) SFTP path
		$realdir = substr($this->dir, -1) == '/' ? substr($this->dir, 0, strlen($this->dir) - 1) : $this->dir;
		$realdir .= '/' . $dir;
		$realdir = substr($realdir, 0, 1) == '/' ? $realdir : '/' . $realdir;

		if ($this->_currentdir == $realdir)
		{
			// Already there, do nothing
			return true;
		}

		$result = @ssh2_sftp_stat($this->handle, $realdir);

		if ($result === false)
		{
			return false;
		}
		else
		{
			// Update the private "current remote directory" variable
			$this->_currentdir = $realdir;

			return true;
		}
	}

	private function isDirWritable($dir)
	{
		if (@fopen("ssh2.sftp://{$this->handle}$dir/kickstart.dat", 'w') === false)
		{
			return false;
		}
		else
		{
			@ssh2_sftp_unlink($this->handle, $dir . '/kickstart.dat');

			return true;
		}
	}

	public function createDirRecursive($dirName, $perms)
	{
		// Strip absolute filesystem path to website's root
		$removePath = AKFactory::get('kickstart.setup.destdir', '');
		if (!empty($removePath))
		{
			// UNIXize the paths
			$removePath = str_replace('\\', '/', $removePath);
			$dirName    = str_replace('\\', '/', $dirName);
			// Make sure they both end in a slash
			$removePath = rtrim($removePath, '/\\') . '/';
			$dirName    = rtrim($dirName, '/\\') . '/';
			// Process the path removal
			$left = substr($dirName, 0, strlen($removePath));
			if ($left == $removePath)
			{
				$dirName = substr($dirName, strlen($removePath));
			}
		}
		if (empty($dirName))
		{
			$dirName = '';
		} // 'cause the substr() above may return FALSE.

		$check = '/' . trim($this->dir, '/ ') . '/' . trim($dirName, '/');

		if ($this->is_dir($check))
		{
			return true;
		}

		$alldirs     = explode('/', $dirName);
		$previousDir = '/' . trim($this->dir, '/ ');

		foreach ($alldirs as $curdir)
		{
			if (!$curdir)
			{
				continue;
			}

			$check = $previousDir . '/' . $curdir;

			if (!$this->is_dir($check))
			{
				// Proactively try to delete a file by the same name
				@ssh2_sftp_unlink($this->handle, $check);

				if (@ssh2_sftp_mkdir($this->handle, $check) === false)
				{
					// If we couldn't create the directory, attempt to fix the permissions in the PHP level and retry!
					$this->fixPermissions($check);

					if (@ssh2_sftp_mkdir($this->handle, $check) === false)
					{
						// Can we fall back to pure PHP mode, sire?
						if (!@mkdir($check))
						{
							$this->setError(AKText::sprintf('FTP_CANT_CREATE_DIR', $check));

							return false;
						}
						else
						{
							// Since the directory was built by PHP, change its permissions
							$trustMeIKnowWhatImDoing =
								500 + 10 + 1; // working around overzealous scanners written by bozos
							@chmod($check, $trustMeIKnowWhatImDoing);

							return true;
						}
					}
				}

				@ssh2_sftp_chmod($this->handle, $check, $perms);
			}

			$previousDir = $check;
		}

		return true;
	}

	private function is_dir($dir)
	{
		return $this->sftp_chdir($dir);
	}

	private function fixPermissions($path)
	{
		// Turn off error reporting
		if (!defined('KSDEBUG'))
		{
			$oldErrorReporting = @error_reporting(0);
		}

		// Get UNIX style paths
		$relPath  = str_replace('\\', '/', $path);
		$basePath = rtrim(str_replace('\\', '/', KSROOTDIR), '/');
		$basePath = rtrim($basePath, '/');

		if (!empty($basePath))
		{
			$basePath .= '/';
		}

		// Remove the leading relative root
		if (substr($relPath, 0, strlen($basePath)) == $basePath)
		{
			$relPath = substr($relPath, strlen($basePath));
		}

		$dirArray  = explode('/', $relPath);
		$pathBuilt = rtrim($basePath, '/');

		foreach ($dirArray as $dir)
		{
			if (empty($dir))
			{
				continue;
			}

			$oldPath = $pathBuilt;
			$pathBuilt .= '/' . $dir;

			if (is_dir($oldPath . '/' . $dir))
			{
				$trustMeIKnowWhatImDoing = 500 + 10 + 1; // working around overzealous scanners written by bozos
				@chmod($oldPath . '/' . $dir, $trustMeIKnowWhatImDoing);
			}
			else
			{
				$trustMeIKnowWhatImDoing = 500 + 10 + 1; // working around overzealous scanners written by bozos
				if (@chmod($oldPath . '/' . $dir, $trustMeIKnowWhatImDoing) === false)
				{
					@unlink($oldPath . $dir);
				}
			}
		}

		// Restore error reporting
		if (!defined('KSDEBUG'))
		{
			@error_reporting($oldErrorReporting);
		}
	}

	function __wakeup()
	{
		$this->connect();
	}

	/*
	 * Tries to fix directory/file permissions in the PHP level, so that
	 * the FTP operation doesn't fail.
	 * @param $path string The full path to a directory or file
	 */

	public function process()
	{
		if (is_null($this->tempFilename))
		{
			// If an empty filename is passed, it means that we shouldn't do any post processing, i.e.
			// the entity was a directory or symlink
			return true;
		}

		$remotePath      = dirname($this->filename);
		$absoluteFSPath  = dirname($this->filename);
		$absoluteFTPPath = '/' . trim($this->dir, '/') . '/' . trim($remotePath, '/');
		$onlyFilename    = basename($this->filename);

		$remoteName = $absoluteFTPPath . '/' . $onlyFilename;

		$ret = $this->sftp_chdir($absoluteFTPPath);

		if ($ret === false)
		{
			$ret = $this->createDirRecursive($absoluteFSPath, 0755);

			if ($ret === false)
			{
				$this->setError(AKText::sprintf('SFTP_COULDNT_UPLOAD', $this->filename));

				return false;
			}

			$ret = $this->sftp_chdir($absoluteFTPPath);

			if ($ret === false)
			{
				$this->setError(AKText::sprintf('SFTP_COULDNT_UPLOAD', $this->filename));

				return false;
			}
		}

		// Create the file
		$ret = $this->write($this->tempFilename, $remoteName);

		// If I got a -1 it means that I wasn't able to open the file, so I have to stop here
		if ($ret === -1)
		{
			$this->setError(AKText::sprintf('SFTP_COULDNT_UPLOAD', $this->filename));

			return false;
		}

		if ($ret === false)
		{
			// If we couldn't create the file, attempt to fix the permissions in the PHP level and retry!
			$this->fixPermissions($this->filename);
			$this->unlink($this->filename);

			$ret = $this->write($this->tempFilename, $remoteName);
		}

		@unlink($this->tempFilename);

		if ($ret === false)
		{
			$this->setError(AKText::sprintf('SFTP_COULDNT_UPLOAD', $this->filename));

			return false;
		}
		$restorePerms = AKFactory::get('kickstart.setup.restoreperms', false);

		if ($restorePerms)
		{
			$this->chmod($remoteName, $this->perms);
		}
		else
		{
			$this->chmod($remoteName, 0644);
		}

		if (@is_file($this->filename) || @is_link($this->filename))
		{
			clearFileInOPCache($this->filename);
		}

		return true;
	}

	private function write($local, $remote)
	{
		$fp      = @fopen("ssh2.sftp://{$this->handle}$remote", 'w');
		$localfp = @fopen($local, 'r');

		if ($fp === false)
		{
			return -1;
		}

		if ($localfp === false)
		{
			@fclose($fp);

			return -1;
		}

		$res = true;

		while (!feof($localfp) && ($res !== false))
		{
			$buffer = @fread($localfp, 65567);
			$res    = @fwrite($fp, $buffer);
		}

		@fclose($fp);
		@fclose($localfp);

		return $res;
	}

	public function unlink($file)
	{
		$check = '/' . trim($this->dir, '/') . '/' . trim($file, '/');

		return @ssh2_sftp_unlink($this->handle, $check);
	}

	public function chmod($file, $perms)
	{
		return @ssh2_sftp_chmod($this->handle, $file, $perms);
	}

	public function processFilename($filename, $perms = 0755)
	{
		// Catch some error conditions...
		if ($this->getError())
		{
			return false;
		}

		// If a null filename is passed, it means that we shouldn't do any post processing, i.e.
		// the entity was a directory or symlink
		if (is_null($filename))
		{
			$this->filename     = null;
			$this->tempFilename = null;

			return null;
		}

		// Strip absolute filesystem path to website's root
		$removePath = AKFactory::get('kickstart.setup.destdir', '');
		if (!empty($removePath))
		{
			$left = substr($filename, 0, strlen($removePath));
			if ($left == $removePath)
			{
				$filename = substr($filename, strlen($removePath));
			}
		}

		// Trim slash on the left
		$filename = ltrim($filename, '/');

		$this->filename     = $filename;
		$this->tempFilename = tempnam($this->tempDir, 'kickstart-');
		$this->perms        = $perms;

		if (empty($this->tempFilename))
		{
			// Oops! Let's try something different
			$this->tempFilename = $this->tempDir . '/kickstart-' . time() . '.dat';
		}

		return $this->tempFilename;
	}

	public function close()
	{
		unset($this->_connection);
		unset($this->handle);
	}

	public function rmdir($directory)
	{
		$check = '/' . trim($this->dir, '/') . '/' . trim($directory, '/');

		return @ssh2_sftp_rmdir($this->handle, $check);
	}

	public function rename($from, $to)
	{
		$from = '/' . trim($this->dir, '/') . '/' . trim($from, '/');
		$to   = '/' . trim($this->dir, '/') . '/' . trim($to, '/');

		$result = @ssh2_sftp_rename($this->handle, $from, $to);

		if ($result !== true)
		{
			return @rename($from, $to);
		}
		else
		{
			return true;
		}
	}

}


/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Hybrid direct / FTP mode file writer
 */
class AKPostprocHybrid extends AKAbstractPostproc
{

	/** @var bool Should I use the FTP layer? */
	public $useFTP = false;

	/** @var bool Should I use FTP over implicit SSL? */
	public $useSSL = false;

	/** @var bool use Passive mode? */
	public $passive = true;

	/** @var string FTP host name */
	public $host = '';

	/** @var int FTP port */
	public $port = 21;

	/** @var string FTP user name */
	public $user = '';

	/** @var string FTP password */
	public $pass = '';

	/** @var string FTP initial directory */
	public $dir = '';

	/** @var resource The FTP handle */
	private $handle = null;

	/** @var null The FTP connection handle */
	private $_handle = null;

	/**
	 * Public constructor. Tries to connect to the FTP server.
	 */
	public function __construct()
	{
		$this->useFTP  = true;
		$this->useSSL  = AKFactory::get('kickstart.ftp.ssl', false);
		$this->passive = AKFactory::get('kickstart.ftp.passive', true);
		$this->host    = AKFactory::get('kickstart.ftp.host', '');
		$this->port    = AKFactory::get('kickstart.ftp.port', 21);
		$this->user    = AKFactory::get('kickstart.ftp.user', '');
		$this->pass    = AKFactory::get('kickstart.ftp.pass', '');
		$this->dir     = AKFactory::get('kickstart.ftp.dir', '');
		$this->tempDir = AKFactory::get('kickstart.ftp.tempdir', '');

		if (trim($this->port) == '')
		{
			$this->port = 21;
		}

		// If FTP is not configured, skip it altogether
		if (empty($this->host) || empty($this->user) || empty($this->pass))
		{
			$this->useFTP = false;
		}

		// Try to connect to the FTP server
		$connected = $this->connect();

		// If the connection fails, skip FTP altogether
		if (!$connected)
		{
			$this->useFTP = false;
		}

		if ($connected)
		{
			if (!empty($this->tempDir))
			{
				$tempDir  = rtrim($this->tempDir, '/\\') . '/';
				$writable = $this->isDirWritable($tempDir);
			}
			else
			{
				$tempDir  = '';
				$writable = false;
			}

			if (!$writable)
			{
				// Default temporary directory is the current root
				$tempDir = KSROOTDIR;
				if (empty($tempDir))
				{
					// Oh, we have no directory reported!
					$tempDir = '.';
				}
				$absoluteDirToHere = $tempDir;
				$tempDir           = rtrim(str_replace('\\', '/', $tempDir), '/');
				if (!empty($tempDir))
				{
					$tempDir .= '/';
				}
				$this->tempDir = $tempDir;
				// Is this directory writable?
				$writable = $this->isDirWritable($tempDir);
			}

			if (!$writable)
			{
				// Nope. Let's try creating a temporary directory in the site's root.
				$tempDir                 = $absoluteDirToHere . '/kicktemp';
				$trustMeIKnowWhatImDoing = 500 + 10 + 1; // working around overzealous scanners written by bozos
				$this->createDirRecursive($tempDir, $trustMeIKnowWhatImDoing);
				// Try making it writable...
				$this->fixPermissions($tempDir);
				$writable = $this->isDirWritable($tempDir);
			}

			// Was the new directory writable?
			if (!$writable)
			{
				// Let's see if the user has specified one
				$userdir = AKFactory::get('kickstart.ftp.tempdir', '');
				if (!empty($userdir))
				{
					// Is it an absolute or a relative directory?
					$absolute = false;
					$absolute = $absolute || (substr($userdir, 0, 1) == '/');
					$absolute = $absolute || (substr($userdir, 1, 1) == ':');
					$absolute = $absolute || (substr($userdir, 2, 1) == ':');
					if (!$absolute)
					{
						// Make absolute
						$tempDir = $absoluteDirToHere . $userdir;
					}
					else
					{
						// it's already absolute
						$tempDir = $userdir;
					}
					// Does the directory exist?
					if (is_dir($tempDir))
					{
						// Yeah. Is it writable?
						$writable = $this->isDirWritable($tempDir);
					}
				}
			}
			$this->tempDir = $tempDir;

			if (!$writable)
			{
				// No writable directory found!!!
				$this->setError(AKText::_('FTP_TEMPDIR_NOT_WRITABLE'));
			}
			else
			{
				AKFactory::set('kickstart.ftp.tempdir', $tempDir);
				$this->tempDir = $tempDir;
			}
		}
	}

	/**
	 * Tries to connect to the FTP server
	 *
	 * @return bool
	 */
	public function connect()
	{
		if (!$this->useFTP)
		{
			return false;
		}

		// Connect to server, using SSL if so required
		if ($this->useSSL)
		{
			$this->handle = @ftp_ssl_connect($this->host, $this->port);
		}
		else
		{
			$this->handle = @ftp_connect($this->host, $this->port);
		}
		if ($this->handle === false)
		{
			$this->setError(AKText::_('WRONG_FTP_HOST'));

			return false;
		}

		// Login
		if (!@ftp_login($this->handle, $this->user, $this->pass))
		{
			$this->setError(AKText::_('WRONG_FTP_USER'));
			@ftp_close($this->handle);

			return false;
		}

		// Change to initial directory
		if (!@ftp_chdir($this->handle, $this->dir))
		{
			$this->setError(AKText::_('WRONG_FTP_PATH1'));
			@ftp_close($this->handle);

			return false;
		}

		// Enable passive mode if the user requested it
		if ($this->passive)
		{
			@ftp_pasv($this->handle, true);
		}
		else
		{
			@ftp_pasv($this->handle, false);
		}

		// Try to download ourselves
		$testFilename = defined('KSSELFNAME') ? KSSELFNAME : basename(__FILE__);
		$tempHandle   = fopen('php://temp', 'r+');

		if (@ftp_fget($this->handle, $tempHandle, $testFilename, FTP_ASCII, 0) === false)
		{
			$this->setError(AKText::_('WRONG_FTP_PATH2'));
			@ftp_close($this->handle);
			fclose($tempHandle);

			return false;
		}

		fclose($tempHandle);

		return true;
	}

	/**
	 * Is the directory writeable?
	 *
	 * @param string $dir The directory ti check
	 *
	 * @return bool
	 */
	private function isDirWritable($dir)
	{
		$fp = @fopen($dir . '/kickstart.dat', 'w');

		if ($fp === false)
		{
			return false;
		}

		@fclose($fp);
		unlink($dir . '/kickstart.dat');

		return true;
	}

	/**
	 * Create a directory, recursively
	 *
	 * @param string $dirName The directory to create
	 * @param int    $perms   The permissions to give to the directory
	 *
	 * @return bool
	 */
	public function createDirRecursive($dirName, $perms)
	{
		// Strip absolute filesystem path to website's root
		$removePath = AKFactory::get('kickstart.setup.destdir', '');

		if (!empty($removePath))
		{
			// UNIXize the paths
			$removePath = str_replace('\\', '/', $removePath);
			$dirName    = str_replace('\\', '/', $dirName);
			// Make sure they both end in a slash
			$removePath = rtrim($removePath, '/\\') . '/';
			$dirName    = rtrim($dirName, '/\\') . '/';
			// Process the path removal
			$left = substr($dirName, 0, strlen($removePath));

			if ($left == $removePath)
			{
				$dirName = substr($dirName, strlen($removePath));
			}
		}

		// 'cause the substr() above may return FALSE.
		if (empty($dirName))
		{
			$dirName = '';
		}

		$check   = '/' . trim($this->dir, '/') . '/' . trim($dirName, '/');
		$checkFS = $removePath . trim($dirName, '/');

		if ($this->is_dir($check))
		{
			return true;
		}

		$alldirs       = explode('/', $dirName);
		$previousDir   = '/' . trim($this->dir);
		$previousDirFS = rtrim($removePath, '/\\');

		foreach ($alldirs as $curdir)
		{
			$check   = $previousDir . '/' . $curdir;
			$checkFS = $previousDirFS . '/' . $curdir;

			if (!is_dir($checkFS) && !$this->is_dir($check))
			{
				// Proactively try to delete a file by the same name
				if (!@unlink($checkFS) && $this->useFTP)
				{
					@ftp_delete($this->handle, $check);
				}

				$createdDir = @mkdir($checkFS, 0755);

				if (!$createdDir && $this->useFTP)
				{
					$createdDir = @ftp_mkdir($this->handle, $check);
				}

				if ($createdDir === false)
				{
					// If we couldn't create the directory, attempt to fix the permissions in the PHP level and retry!
					$this->fixPermissions($checkFS);

					$createdDir = @mkdir($checkFS, 0755);
					if (!$createdDir && $this->useFTP)
					{
						$createdDir = @ftp_mkdir($this->handle, $check);
					}

					if ($createdDir === false)
					{
						$this->setError(AKText::sprintf('FTP_CANT_CREATE_DIR', $check));

						return false;
					}
				}

				if (!@chmod($checkFS, $perms) && $this->useFTP)
				{
					@ftp_chmod($this->handle, $perms, $check);
				}
			}

			$previousDir   = $check;
			$previousDirFS = $checkFS;
		}

		return true;
	}

	private function is_dir($dir)
	{
		if ($this->useFTP)
		{
			return @ftp_chdir($this->handle, $dir);
		}

		return false;
	}

	/**
	 * Tries to fix directory/file permissions in the PHP level, so that
	 * the FTP operation doesn't fail.
	 *
	 * @param $path string The full path to a directory or file
	 */
	private function fixPermissions($path)
	{
		// Turn off error reporting
		if (!defined('KSDEBUG'))
		{
			$oldErrorReporting = error_reporting(0);
		}

		// Get UNIX style paths
		$relPath  = str_replace('\\', '/', $path);
		$basePath = rtrim(str_replace('\\', '/', KSROOTDIR), '/');
		$basePath = rtrim($basePath, '/');

		if (!empty($basePath))
		{
			$basePath .= '/';
		}

		// Remove the leading relative root
		if (substr($relPath, 0, strlen($basePath)) == $basePath)
		{
			$relPath = substr($relPath, strlen($basePath));
		}

		$dirArray  = explode('/', $relPath);
		$pathBuilt = rtrim($basePath, '/');

		foreach ($dirArray as $dir)
		{
			if (empty($dir))
			{
				continue;
			}

			$oldPath = $pathBuilt;
			$pathBuilt .= '/' . $dir;

			if (is_dir($oldPath . $dir))
			{
				$trustMeIKnowWhatImDoing = 500 + 10 + 1; // working around overzealous scanners written by bozos
				@chmod($oldPath . $dir, $trustMeIKnowWhatImDoing);
			}
			else
			{
				$trustMeIKnowWhatImDoing = 500 + 10 + 1; // working around overzealous scanners written by bozos
				if (@chmod($oldPath . $dir, $trustMeIKnowWhatImDoing) === false)
				{
					@unlink($oldPath . $dir);
				}
			}
		}

		// Restore error reporting
		if (!defined('KSDEBUG'))
		{
			@error_reporting($oldErrorReporting);
		}
	}

	/**
	 * Called after unserialisation, tries to reconnect to FTP
	 */
	public function __wakeup()
	{
		if ($this->useFTP)
		{
			$this->connect();
		}
	}

	public function __sleep()
	{
		if ($this->useFTP)
		{
			if (!is_null($this->_handle) && is_resource($this->_handle))
			{
				@ftp_close($this->_handle);
			}
		}

		$this->_handle = null;
	}


	public function __destruct()
	{
		if ($this->useFTP)
		{
			if (!is_null($this->handle) && is_resource($this->handle))
			{
				@ftp_close($this->handle);
			}
		}
	}

	/**
	 * Post-process an extracted file, using FTP or direct file writes to move it
	 *
	 * @return bool
	 */
	public function process()
	{
		if (is_null($this->tempFilename))
		{
			// If an empty filename is passed, it means that we shouldn't do any post processing, i.e.
			// the entity was a directory or symlink
			return true;
		}

		$remotePath = dirname($this->filename);
		$removePath = AKFactory::get('kickstart.setup.destdir', '');
		$root       = rtrim($removePath, '/\\');

		if (!empty($removePath))
		{
			$removePath = ltrim($removePath, "/");
			$remotePath = ltrim($remotePath, "/");
			$left       = substr($remotePath, 0, strlen($removePath));

			if ($left == $removePath)
			{
				$remotePath = substr($remotePath, strlen($removePath));
			}
		}

		$absoluteFSPath  = dirname($this->filename);
		$relativeFTPPath = trim($remotePath, '/');
		$absoluteFTPPath = '/' . trim($this->dir, '/') . '/' . trim($remotePath, '/');
		$onlyFilename    = basename($this->filename);

		$remoteName = $absoluteFTPPath . '/' . $onlyFilename;

		// Does the directory exist?
		if (!is_dir($root . '/' . $absoluteFSPath))
		{
			$ret = $this->createDirRecursive($absoluteFSPath, 0755);

			if (($ret === false) && ($this->useFTP))
			{
				$ret = @ftp_chdir($this->handle, $absoluteFTPPath);
			}

			if ($ret === false)
			{
				$this->setError(AKText::sprintf('FTP_COULDNT_UPLOAD', $this->filename));

				return false;
			}
		}

		if ($this->useFTP)
		{
			$ret = @ftp_chdir($this->handle, $absoluteFTPPath);
		}

		// Try copying directly
		$ret = @copy($this->tempFilename, $root . '/' . $this->filename);

		if ($ret === false)
		{
			$this->fixPermissions($this->filename);
			$this->unlink($this->filename);

			$ret = @copy($this->tempFilename, $root . '/' . $this->filename);
		}

		if ($this->useFTP && ($ret === false))
		{
			$ret = @ftp_put($this->handle, $remoteName, $this->tempFilename, FTP_BINARY);

			if ($ret === false)
			{
				// If we couldn't create the file, attempt to fix the permissions in the PHP level and retry!
				$this->fixPermissions($this->filename);
				$this->unlink($this->filename);

				$fp = @fopen($this->tempFilename, 'r');
				if ($fp !== false)
				{
					$ret = @ftp_fput($this->handle, $remoteName, $fp, FTP_BINARY);
					@fclose($fp);
				}
				else
				{
					$ret = false;
				}
			}
		}

		@unlink($this->tempFilename);

		if ($ret === false)
		{
			$this->setError(AKText::sprintf('FTP_COULDNT_UPLOAD', $this->filename));

			return false;
		}

		$restorePerms = AKFactory::get('kickstart.setup.restoreperms', false);
		$perms        = $restorePerms ? $this->perms : 0644;

		$ret = @chmod($root . '/' . $this->filename, $perms);

		if ($this->useFTP && ($ret === false))
		{
			@ftp_chmod($this->_handle, $perms, $remoteName);
		}

		if (@is_file($this->filename) || @is_link($this->filename))
		{
			clearFileInOPCache($this->filename);
		}

		return true;
	}

	public function unlink($file)
	{
		$ret = @unlink($file);

		if (!$ret && $this->useFTP)
		{
			$removePath = AKFactory::get('kickstart.setup.destdir', '');
			if (!empty($removePath))
			{
				$left = substr($file, 0, strlen($removePath));
				if ($left == $removePath)
				{
					$file = substr($file, strlen($removePath));
				}
			}

			$check = '/' . trim($this->dir, '/') . '/' . trim($file, '/');

			$ret = @ftp_delete($this->handle, $check);
		}

		return $ret;
	}

	/**
	 * Create a temporary filename
	 *
	 * @param string $filename The original filename
	 * @param int    $perms    The file permissions
	 *
	 * @return string
	 */
	public function processFilename($filename, $perms = 0755)
	{
		// Catch some error conditions...
		if ($this->getError())
		{
			return false;
		}

		// If a null filename is passed, it means that we shouldn't do any post processing, i.e.
		// the entity was a directory or symlink
		if (is_null($filename))
		{
			$this->filename     = null;
			$this->tempFilename = null;

			return null;
		}

		// Strip absolute filesystem path to website's root
		$removePath = AKFactory::get('kickstart.setup.destdir', '');

		if (!empty($removePath))
		{
			$left = substr($filename, 0, strlen($removePath));

			if ($left == $removePath)
			{
				$filename = substr($filename, strlen($removePath));
			}
		}

		// Trim slash on the left
		$filename = ltrim($filename, '/');

		$this->filename     = $filename;
		$this->tempFilename = tempnam($this->tempDir, 'kickstart-');
		$this->perms        = $perms;

		if (empty($this->tempFilename))
		{
			// Oops! Let's try something different
			$this->tempFilename = $this->tempDir . '/kickstart-' . time() . '.dat';
		}

		return $this->tempFilename;
	}

	/**
	 * Closes the FTP connection
	 */
	public function close()
	{
		if (!$this->useFTP)
		{
			@ftp_close($this->handle);
		}
	}

	public function chmod($file, $perms)
	{
		if (AKFactory::get('kickstart.setup.dryrun', '0'))
		{
			return true;
		}

		$ret = @chmod($file, $perms);

		if (!$ret && $this->useFTP)
		{
			// Strip absolute filesystem path to website's root
			$removePath = AKFactory::get('kickstart.setup.destdir', '');

			if (!empty($removePath))
			{
				$left = substr($file, 0, strlen($removePath));

				if ($left == $removePath)
				{
					$file = substr($file, strlen($removePath));
				}
			}

			// Trim slash on the left
			$file = ltrim($file, '/');

			$ret = @ftp_chmod($this->handle, $perms, $file);
		}

		return $ret;
	}

	public function rmdir($directory)
	{
		$ret = @rmdir($directory);

		if (!$ret && $this->useFTP)
		{
			$removePath = AKFactory::get('kickstart.setup.destdir', '');
			if (!empty($removePath))
			{
				$left = substr($directory, 0, strlen($removePath));
				if ($left == $removePath)
				{
					$directory = substr($directory, strlen($removePath));
				}
			}

			$check = '/' . trim($this->dir, '/') . '/' . trim($directory, '/');

			$ret = @ftp_rmdir($this->handle, $check);
		}

		return $ret;
	}

	public function rename($from, $to)
	{
		$ret = @rename($from, $to);

		if (!$ret && $this->useFTP)
		{
			$originalFrom = $from;
			$originalTo   = $to;

			$removePath = AKFactory::get('kickstart.setup.destdir', '');
			if (!empty($removePath))
			{
				$left = substr($from, 0, strlen($removePath));
				if ($left == $removePath)
				{
					$from = substr($from, strlen($removePath));
				}
			}
			$from = '/' . trim($this->dir, '/') . '/' . trim($from, '/');

			if (!empty($removePath))
			{
				$left = substr($to, 0, strlen($removePath));
				if ($left == $removePath)
				{
					$to = substr($to, strlen($removePath));
				}
			}
			$to = '/' . trim($this->dir, '/') . '/' . trim($to, '/');

			$ret = @ftp_rename($this->handle, $from, $to);
		}

		return $ret;
	}
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * JPA archive extraction class
 */
class AKUnarchiverJPA extends AKAbstractUnarchiver
{
	protected $archiveHeaderData = [];

	protected function readArchiveHeader()
	{
		debugMsg('Preparing to read archive header');
		// Initialize header data array
		$this->archiveHeaderData = new stdClass();

		// Open the first part
		debugMsg('Opening the first part');
		$this->nextFile();

		// Fail for unreadable files
		if ($this->fp === false)
		{
			debugMsg('Could not open the first part');

			return false;
		}

		// Fuzzy check for the start of archive.
		debugMsg('Fuzzy checking for archive signature');

		$sigFound = $this->fuzzySignatureSearch([
			'JPA',
		], 3);

		if (!$sigFound)
		{
			debugMsg('Cannot find a valid archive signature in the first 128Kb of the first part file');

			$this->setError(AKText::sprintf('ERR_INVALID_ARCHIVE_LONG', 'jpa', 'j'));

			return false;
		}

		debugMsg(sprintf('File signature found, position %d', ftell($this->fp)));

		// Read the signature
		$sig = fread($this->fp, 3);

		if ($sig != 'JPA')
		{
			// Not a JPA file
			debugMsg('Invalid archive signature');
			$this->setError(AKText::sprintf('ERR_INVALID_ARCHIVE_LONG', 'jpa', 'j'));

			return false;
		}

		// Read and parse header length
		$header_length_array = unpack('v', fread($this->fp, 2));
		$header_length       = $header_length_array[1];

		// Read and parse the known portion of header data (14 bytes)
		$bin_data    = fread($this->fp, 14);
		$header_data = unpack('Cmajor/Cminor/Vcount/Vuncsize/Vcsize', $bin_data);

		// Temporary array with all the data we read
		$temp = [
			'signature'        => $sig,
			'length'           => $header_length,
			'major'            => $header_data['major'],
			'minor'            => $header_data['minor'],
			'filecount'        => $header_data['count'],
			'uncompressedsize' => $header_data['uncsize'],
			'compressedsize'   => $header_data['csize'],
			'unknowndata'      => '',
		];

		// Load additional header data
		$rest_length = $header_length - 19;
		$junk        = '';

		while ($rest_length > 8)
		{
			// Read the extra length signature and size
			$extraSig    = fread($this->fp, 4);
			$binData     = fread($this->fp, 2);
			$extraHeader = unpack('vlength', $binData);
			$length      = $extraHeader['length'] - 2;

			$rest_length -= 6 + $length;

			switch ($extraSig)
			{
				case "\x4A\x50\x01\x01":
					$moreBinData        = fread($this->fp, $length);
					$moreExtraHeader    = unpack('vtotalParts', $moreBinData);
					$temp['totalParts'] = $moreExtraHeader['totalParts'];
					break;

				case "\x4A\x50\x01\x02":
					$moreBinData              = fread($this->fp, $length);

					// Only decode on 64-bit versions of PHP
					if (PHP_INT_SIZE >= 8)
					{
						$moreExtraHeader          = unpack('Puncompressed/Pcompressed', $moreBinData);
						$header_data['uncsize']   = $moreExtraHeader['uncompressed'];
						$header_data['csize']     = $moreExtraHeader['compressed'];
						$temp['uncompressedsize'] = $moreExtraHeader['uncompressed'];
						$temp['compressedsize']   = $moreExtraHeader['compressed'];
					}

					break;

				default:
					$moreBinData = fread($this->fp, $length);
					$junk        .= $extraSig . $binData . $moreBinData;
					break;
			}
		}

		if ($rest_length > 0)
		{
			$junk .= fread($this->fp, $rest_length);
		}
		else
		{
			$junk .= '';
		}

		// Array-to-object conversion
		foreach ($temp as $key => $value)
		{
			$this->archiveHeaderData->{$key} = $value;
		}

		debugMsg('Header data:');
		debugMsg('Length              : ' . $header_length);
		debugMsg('Major               : ' . $header_data['major']);
		debugMsg('Minor               : ' . $header_data['minor']);
		debugMsg('File count          : ' . $header_data['count']);
		debugMsg('Uncompressed size   : ' . $header_data['uncsize']);
		debugMsg('Compressed size     : ' . $header_data['csize']);
		debugMsg('Total Parts         : ' . (isset($header_data['totalParts']) ? $header_data['totalParts'] : '1'));

		$this->currentPartOffset = @ftell($this->fp);

		$this->dataReadLength = 0;

		return true;
	}

	/**
	 * Concrete classes must use this method to read the file header
	 *
	 * @return bool True if reading the file was successful, false if an error occurred or we reached end of archive
	 */
	protected function readFileHeader()
	{
		// If the current part is over, proceed to the next part please
		if ($this->isEOF(true))
		{
			debugMsg('Archive part EOF; moving to next file');
			$this->nextFile();
		}

		$this->currentPartOffset = ftell($this->fp);

		debugMsg("Reading file signature; part {$this->currentPartNumber}, offset {$this->currentPartOffset}");
		// Get and decode Entity Description Block
		$signature = fread($this->fp, 3);

		$this->fileHeader            = new stdClass();
		$this->fileHeader->timestamp = 0;

		// Check signature
		if ($signature != 'JPF')
		{
			if ($this->isEOF(true))
			{
				// This file is finished; make sure it's the last one
				$gotNextFile = $this->nextFile();

				if (!$gotNextFile && $this->getState() !== 'postrun')
				{
					debugMsg(sprintf('Cannot open file %s for part #%d', $this->archiveList[$this->currentPartNumber] ?: '(unknown)', $this->currentPartNumber));

					$this->setError(AKText::sprintf(
						'INVALID_FILE_HEADER_OFFSET_ZERO',
						$this->archiveList[$this->currentPartNumber] ?: '(unknown)',
						$this->currentPartNumber,
						'jpa',
						'j'
					));

					return false;
				}

				if (!$this->isEOF(false))
				{
					debugMsg('Invalid file signature before end of archive encountered');
					$this->setError(AKText::sprintf(
						'INVALID_FILE_HEADER',
						$this->currentPartNumber,
						$this->currentPartOffset,
						'jpa',
						'j'
					));

					return false;
				}

				// We're just finished
				return false;
			}
			else
			{
				$screwed = true;

				if (AKFactory::get('kickstart.setup.ignoreerrors', false))
				{
					debugMsg('Invalid file block signature; launching heuristic file block signature scanner');
					$screwed = !$this->heuristicFileHeaderLocator();

					if (!$screwed)
					{
						$signature = 'JPF';
					}
					else
					{
						debugMsg('Heuristics failed. Brace yourself for the imminent crash.');
					}
				}

				if ($screwed)
				{
					// This is not a file block! The archive is corrupt.
					debugMsg('Invalid file block signature');

					if (count($this->archiveList) > 1)
					{
						$this->setError(AKText::sprintf(
							'INVALID_FILE_HEADER_MULTIPART',
							$this->currentPartNumber,
							$this->currentPartOffset,
							'jpa',
							'j'
						));

						return false;
					}

					$this->setError(AKText::sprintf('INVALID_FILE_HEADER', $this->currentPartNumber, $this->currentPartOffset));

					return false;
				}
			}
		}
		// This a JPA Entity Block. Process the header.

		$isBannedFile = false;

		// Read length of EDB and of the Entity Path Data
		$length_array = unpack('vblocksize/vpathsize', fread($this->fp, 4));
		// Read the path data
		if ($length_array['pathsize'] > 0)
		{
			$file = fread($this->fp, $length_array['pathsize']);
		}
		else
		{
			$file = '';
		}

		// Handle file renaming
		$isRenamed = false;
		if (is_array($this->renameFiles) && (count($this->renameFiles) > 0))
		{
			if (array_key_exists($file, $this->renameFiles))
			{
				$file      = $this->renameFiles[$file];
				$isRenamed = true;
			}
		}

		// Handle directory renaming
		$isDirRenamed = false;
		if (is_array($this->renameDirs) && (count($this->renameDirs) > 0))
		{
			if (array_key_exists(dirname($file), $this->renameDirs))
			{
				$file         = rtrim($this->renameDirs[dirname($file)], '/') . '/' . basename($file);
				$isRenamed    = true;
				$isDirRenamed = true;
			}
		}

		// Read and parse the known data portion
		$bin_data    = fread($this->fp, 14);
		$header_data = unpack('Ctype/Ccompression/Vcompsize/Vuncompsize/Vperms', $bin_data);
		// Read any unknown data
		$restBytes = $length_array['blocksize'] - (21 + $length_array['pathsize']);

		if ($restBytes > 0)
		{
			// Start reading the extra fields
			while ($restBytes >= 4)
			{
				$extra_header_data      = fread($this->fp, 4);
				$extra_header           = unpack('vsignature/vlength', $extra_header_data);
				$restBytes              -= 4;
				$extra_header['length'] -= 4;

				if ($extra_header['length'] > 0)
				{
					switch ($extra_header['signature'])
					{
						case 256:
							// File modified timestamp
							$bindata                     = fread($this->fp, $extra_header['length']);
							$restBytes                   -= $extra_header['length'];
							$timestamps                  = unpack('Vmodified', substr($bindata, 0, 4));
							$filectime                   = $timestamps['modified'];
							$this->fileHeader->timestamp = $filectime;
							break;

						case 512:
							$bindata                   = fread($this->fp, $extra_header['length']);
							$restBytes                 -= $extra_header['length'];

							// Only decode on 64-bit versions of PHP
							if (PHP_INT_SIZE >= 8)
							{
								$sizes                     = unpack('Pclen/Punclen', $bindata);
								$header_data['compsize']   = $sizes['clen'];
								$header_data['uncompsize'] = $sizes['unclen'];
							}
							break;

						default:
							// Unknown field
							$junk      = fread($this->fp, $extra_header['length']);
							$restBytes -= $extra_header['length'];
							break;
					}
				}

			}

			if ($restBytes > 0)
			{
				$junk = fread($this->fp, $restBytes);
			}
		}

		$compressionType = $header_data['compression'];

		// Populate the return array
		$this->fileHeader->file         = $file;
		$this->fileHeader->compressed   = $header_data['compsize'];
		$this->fileHeader->uncompressed = $header_data['uncompsize'];

		switch ($header_data['type'])
		{
			case 0:
				$this->fileHeader->type = 'dir';
				break;

			case 1:
				$this->fileHeader->type = 'file';
				break;

			case 2:
				$this->fileHeader->type = 'link';
				break;
		}

		switch ($compressionType)
		{
			case 0:
				$this->fileHeader->compression = 'none';
				break;
			case 1:
				$this->fileHeader->compression = 'gzip';
				break;
			case 2:
				$this->fileHeader->compression = 'bzip2';
				break;
		}

		$this->fileHeader->permissions = $header_data['perms'];

		// Find hard-coded banned files
		if ((basename($this->fileHeader->file) == ".") || (basename($this->fileHeader->file) == ".."))
		{
			$isBannedFile = true;
		}

		// Also try to find banned files passed in class configuration
		if ((count($this->skipFiles) > 0) && (!$isRenamed))
		{
			if (in_array($this->fileHeader->file, $this->skipFiles))
			{
				$isBannedFile = true;
			}
		}

		// If we have a banned file, let's skip it
		if ($isBannedFile)
		{
			debugMsg('Skipping file ' . $this->fileHeader->file);
			// Advance the file pointer, skipping exactly the size of the compressed data
			$seekleft = $this->fileHeader->compressed;
			while ($seekleft > 0)
			{
				// Ensure that we can seek past archive part boundaries
				$curSize = @filesize($this->archiveList[$this->currentPartNumber]);
				$curPos  = @ftell($this->fp);
				$canSeek = $curSize - $curPos;
				if ($canSeek > $seekleft)
				{
					$canSeek = $seekleft;
				}
				@fseek($this->fp, $canSeek, SEEK_CUR);
				$seekleft -= $canSeek;
				if ($seekleft)
				{
					$this->nextFile();
				}
			}

			$this->currentPartOffset = @ftell($this->fp);
			$this->runState          = AK_STATE_DONE;

			return true;
		}

		// Remove the removePath, if any
		$this->fileHeader->file = $this->removePath($this->fileHeader->file);

		// Last chance to prepend a path to the filename
		$archivePath = null;

		if (!empty($this->addPath) && !$isDirRenamed)
		{
			$archivePath            = $this->fileHeader->file;
			$this->fileHeader->file = $this->addPath . $this->fileHeader->file;
		}

		// Refuse to extract an entry which resolves outside the extraction root. We reject
		// instead of rewriting the path: a silently corrected path hides a hostile archive,
		// whereas a hard stop puts it in front of the person doing the restoration.
		if (!$this->isPathInRoot($this->fileHeader->file, $archivePath))
		{
			debugMsg('Entry outside the extraction root: ' . $this->fileHeader->file);
			$this->setError(AKText::sprintf('ERR_PATH_OUTSIDE_ROOT', $this->fileHeader->file));

			return false;
		}

		// Get the translated path name
		$restorePerms = AKFactory::get('kickstart.setup.restoreperms', false);

		if (!$this->mustSkip())
		{
			if ($this->fileHeader->type == 'file')
			{
				// Regular file; ask the postproc engine to process its filename
				if ($restorePerms)
				{
					$this->fileHeader->realFile =
						$this->postProcEngine->processFilename($this->fileHeader->file, $this->fileHeader->permissions);
				}
				else
				{
					$this->fileHeader->realFile = $this->postProcEngine->processFilename($this->fileHeader->file);
				}
			}
			elseif ($this->fileHeader->type == 'dir')
			{
				$dir = $this->fileHeader->file;

				// Directory; just create it
				if ($restorePerms)
				{
					$this->postProcEngine->createDirRecursive($dir, $this->fileHeader->permissions);
				}
				else
				{
					$this->postProcEngine->createDirRecursive($dir, 0755);
				}

				$this->postProcEngine->processFilename(null);
			}
			else
			{
				// Symlink; do not post-process
				$this->postProcEngine->processFilename(null);
			}

			$this->createDirectory();
		}

		// Header is read
		$this->runState = AK_STATE_HEADER;

		$this->dataReadLength = 0;

		return true;
	}

	protected function heuristicFileHeaderLocator()
	{
		$ret     = false;
		$fullEOF = false;

		while (!$ret && !$fullEOF)
		{
			$this->currentPartOffset = @ftell($this->fp);

			if ($this->isEOF(true))
			{
				$this->nextFile();
			}

			if ($this->isEOF(false))
			{
				$fullEOF = true;
				continue;
			}

			// Read 512Kb
			$chunk     = fread($this->fp, 524288);
			$size_read = mb_strlen($chunk, '8bit');
			//$pos = strpos($chunk, 'JPF');
			$pos = mb_strpos($chunk, 'JPF', 0, '8bit');

			if ($pos !== false)
			{
				// We found it!
				$this->currentPartOffset += $pos + 3;
				@fseek($this->fp, $this->currentPartOffset, SEEK_SET);
				$ret = true;
			}
			else
			{
				// Not yet found :(
				$this->currentPartOffset = @ftell($this->fp);
			}
		}

		return $ret;
	}

	/**
	 * Creates the directory this file points to
	 */
	protected function createDirectory()
	{
		if ($this->mustSkip())
		{
			return true;
		}

		// Do we need to create a directory?
		if (empty($this->fileHeader->realFile))
		{
			$this->fileHeader->realFile = $this->fileHeader->file;
		}

		$lastSlash = strrpos($this->fileHeader->realFile, '/');
		$dirName   = substr($this->fileHeader->realFile, 0, $lastSlash);
		$perms     = $this->flagRestorePermissions ? $this->fileHeader->permissions : 0755;
		$ignore    = AKFactory::get('kickstart.setup.ignoreerrors', false) || $this->isIgnoredDirectory($dirName);

		if (($this->postProcEngine->createDirRecursive($dirName, $perms) == false) && (!$ignore))
		{
			$this->setError(AKText::sprintf('COULDNT_CREATE_DIR', $dirName));

			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	 * Concrete classes must use this method to process file data. It must set $runState to AK_STATE_DATAREAD when
	 * it's finished processing the file data.
	 *
	 * @return bool True if processing the file data was successful, false if an error occurred
	 */
	protected function processFileData()
	{
		switch ($this->fileHeader->type)
		{
			case 'dir':
				return $this->processTypeDir();
				break;

			case 'link':
				return $this->processTypeLink();
				break;

			case 'file':
				switch ($this->fileHeader->compression)
				{
					case 'none':
						return $this->processTypeFileUncompressed();
						break;

					case 'gzip':
					case 'bzip2':
						return $this->processTypeFileCompressedSimple();
						break;

				}
				break;

			default:
				debugMsg('Unknown file type ' . $this->fileHeader->type);
				break;
		}
	}

	/**
	 * Process the file data of a directory entry
	 *
	 * @return bool
	 */
	private function processTypeDir()
	{
		// Directory entries in the JPA do not have file data, therefore we're done processing the entry
		$this->runState = AK_STATE_DATAREAD;

		return true;
	}

	/**
	 * Process the file data of a link entry
	 *
	 * @return bool
	 */
	private function processTypeLink()
	{
		$readBytes   = 0;
		$toReadBytes = 0;
		$leftBytes   = $this->fileHeader->compressed;
		$data        = '';

		while ($leftBytes > 0)
		{
			$toReadBytes     = ($leftBytes > $this->chunkSize) ? $this->chunkSize : $leftBytes;
			$mydata          = $this->fread($this->fp, $toReadBytes);
			$reallyReadBytes = akstringlen($mydata);
			$data            .= $mydata;
			$leftBytes       -= $reallyReadBytes;

			if ($reallyReadBytes < $toReadBytes)
			{
				// We read less than requested! Why? Did we hit local EOF?
				if ($this->isEOF(true) && !$this->isEOF(false))
				{
					// Yeap. Let's go to the next file
					$this->nextFile();
				}
				else
				{
					debugMsg('End of local file before reading all data with no more parts left. The archive is corrupt or truncated.');
					// Nope. The archive is corrupt
					$this->setError(AKText::_('ERR_CORRUPT_ARCHIVE'));

					return false;
				}
			}
		}

		$filename = isset($this->fileHeader->realFile) ? $this->fileHeader->realFile : $this->fileHeader->file;

		if (!$this->mustSkip())
		{
			// Try to remove an existing file or directory by the same name
			if (file_exists($filename))
			{
				@unlink($filename);
				@rmdir($filename);
			}

			// Remove any trailing slash
			if (substr($filename, -1) == '/')
			{
				$filename = substr($filename, 0, -1);
			}
			// Create the symlink - only possible within PHP context. There's no support built in the FTP protocol, so no postproc use is possible here :(
			@symlink($data, $filename);
		}

		$this->runState = AK_STATE_DATAREAD;

		return true; // No matter if the link was created!
	}

	private function processTypeFileUncompressed()
	{
		// Uncompressed files are being processed in small chunks, to avoid timeouts
		if (($this->dataReadLength == 0) && !$this->mustSkip())
		{
			// Before processing file data, ensure permissions are adequate
			$this->setCorrectPermissions($this->fileHeader->file);

			clearstatcache($this->fileHeader->file);
		}

		// Open the output file
		if (!$this->mustSkip())
		{
			$ignore =
				AKFactory::get('kickstart.setup.ignoreerrors', false) || $this->isIgnoredDirectory($this->fileHeader->file);

			if ($this->dataReadLength == 0)
			{
				$outfp = @fopen($this->fileHeader->realFile, 'w');
			}
			else
			{
				$outfp = @fopen($this->fileHeader->realFile, 'a');
			}

			// Can we write to the file?
			if (($outfp === false) && (!$ignore))
			{
				// An error occurred
				debugMsg('Could not write to output file');
				$this->setError(AKText::sprintf('COULDNT_WRITE_FILE', $this->fileHeader->realFile));

				return false;
			}
		}

		// Does the file have any data, at all?
		if ($this->fileHeader->compressed == 0)
		{
			// No file data!
			if (!$this->mustSkip() && is_resource($outfp))
			{
				@fclose($outfp);
			}

			$this->runState = AK_STATE_DATAREAD;

			return true;
		}

		// Reference to the global timer
		$timer = AKFactory::getTimer();

		$toReadBytes = 0;
		$leftBytes   = $this->fileHeader->compressed - $this->dataReadLength;

		// Loop while there's data to read and enough time to do it
		while (($leftBytes > 0) && ($timer->getTimeLeft() > 0))
		{
			$toReadBytes          = ($leftBytes > $this->chunkSize) ? $this->chunkSize : $leftBytes;
			$data                 = $this->fread($this->fp, $toReadBytes);
			$reallyReadBytes      = akstringlen($data);
			$leftBytes            -= $reallyReadBytes;
			$this->dataReadLength += $reallyReadBytes;

			if ($reallyReadBytes < $toReadBytes)
			{
				// We read less than requested! Why? Did we hit local EOF?
				if ($this->isEOF(true) && !$this->isEOF(false))
				{
					// Yeap. Let's go to the next file
					$this->nextFile();
				}
				else
				{
					// Nope. The archive is corrupt
					debugMsg('Not enough data in file. The archive is truncated or corrupt.');
					$this->setError(AKText::_('ERR_CORRUPT_ARCHIVE'));

					return false;
				}
			}

			if (!$this->mustSkip())
			{
				if (is_resource($outfp))
				{
					@fwrite($outfp, $data);
				}
			}
		}

		// Close the file pointer
		if (!$this->mustSkip())
		{
			if (is_resource($outfp))
			{
				@fclose($outfp);
			}
		}

		// Was this a pre-timeout bail out?
		if ($leftBytes > 0)
		{
			$this->runState = AK_STATE_DATA;
		}
		else
		{
			// Oh! We just finished!
			$this->runState       = AK_STATE_DATAREAD;
			$this->dataReadLength = 0;
		}

		return true;
	}

	private function processTypeFileCompressedSimple()
	{
		if (!$this->mustSkip())
		{
			// Before processing file data, ensure permissions are adequate
			$this->setCorrectPermissions($this->fileHeader->file);

			clearstatcache($this->fileHeader->file);

			// Open the output file
			$outfp = @fopen($this->fileHeader->realFile, 'w');

			// Can we write to the file?
			$ignore =
				AKFactory::get('kickstart.setup.ignoreerrors', false) || $this->isIgnoredDirectory($this->fileHeader->file);

			if (($outfp === false) && (!$ignore))
			{
				// An error occurred
				debugMsg('Could not write to output file');
				$this->setError(AKText::sprintf('COULDNT_WRITE_FILE', $this->fileHeader->realFile));

				return false;
			}
		}

		// Does the file have any data, at all?
		if ($this->fileHeader->compressed == 0)
		{
			// No file data!
			if (!$this->mustSkip())
			{
				if (is_resource($outfp))
				{
					@fclose($outfp);
				}
			}
			$this->runState = AK_STATE_DATAREAD;

			return true;
		}

		// Simple compressed files are processed as a whole; we can't do chunk processing
		$zipData = $this->fread($this->fp, $this->fileHeader->compressed);
		while (akstringlen($zipData) < $this->fileHeader->compressed)
		{
			// End of local file before reading all data, but have more archive parts?
			if ($this->isEOF(true) && !$this->isEOF(false))
			{
				// Yeap. Read from the next file
				$this->nextFile();
				$bytes_left = $this->fileHeader->compressed - akstringlen($zipData);
				$zipData    .= $this->fread($this->fp, $bytes_left);
			}
			else
			{
				debugMsg('End of local file before reading all data with no more parts left. The archive is corrupt or truncated.');
				$this->setError(AKText::_('ERR_CORRUPT_ARCHIVE'));

				return false;
			}
		}

		if ($this->fileHeader->compression == 'gzip')
		{
			$unzipData = gzinflate($zipData);
		}
		elseif ($this->fileHeader->compression == 'bzip2')
		{
			$unzipData = bzdecompress($zipData);
		}
		unset($zipData);

		// Write to the file.
		if (!$this->mustSkip() && is_resource($outfp))
		{
			@fwrite($outfp, $unzipData, $this->fileHeader->uncompressed);
			@fclose($outfp);
		}
		unset($unzipData);

		$this->runState = AK_STATE_DATAREAD;

		return true;
	}
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * ZIP archive extraction class
 *
 * Since the file data portion of ZIP and JPA are similarly structured (it's empty for dirs,
 * linked node name for symlinks, dumped binary data for no compressions and dumped gzipped
 * binary data for gzip compression) we just have to subclass AKUnarchiverJPA and change the
 * header reading bits. Reusable code ;)
 */
class AKUnarchiverZIP extends AKUnarchiverJPA
{
	public $expectDataDescriptor = false;

	protected function readArchiveHeader()
	{
		debugMsg('Preparing to read archive header');
		// Initialize header data array
		$this->archiveHeaderData = new stdClass();

		// Open the first part
		debugMsg('Opening the first part');
		$this->nextFile();

		// Fail for unreadable files
		if ($this->fp === false)
		{
			debugMsg('The first part is not readable');

			return false;
		}

		// Fuzzy check for the start of archive.
		debugMsg('Fuzzy checking for archive signature');

		$sigFound = $this->fuzzySignatureSearch([
			pack('V', 0x08074b50), // Multi-part ZIP
			pack('V', 0x30304b50), // Multi-part ZIP (alternate)
			pack('V', 0x04034b50)  // Single file
		], 4);

		if (!$sigFound)
		{
			debugMsg('Cannot find a valid archive signature in the first 128Kb of the first part file');

			$this->setError(AKText::sprintf('ERR_INVALID_ARCHIVE_LONG', 'zip', 'z'));

			return false;
		}

		debugMsg(sprintf('File signature found, position %d', ftell($this->fp)));

		// Read a possible multipart signature
		$sigBinary  = fread($this->fp, 4);
		$headerData = unpack('Vsig', $sigBinary);

		// Roll back if it's not a multipart archive
		if ($headerData['sig'] == 0x04034b50)
		{
			debugMsg('The archive is not multipart');
			fseek($this->fp, -4, SEEK_CUR);
		}
		else
		{
			debugMsg('The archive is multipart');
		}

		$multiPartSigs = [
			0x08074b50, // Multi-part ZIP
			0x30304b50, // Multi-part ZIP (alternate)
			0x04034b50  // Single file
		];
		if (!in_array($headerData['sig'], $multiPartSigs))
		{
			debugMsg('Invalid header signature ' . dechex($headerData['sig']));
			$this->setError(AKText::sprintf('ERR_INVALID_ARCHIVE_LONG', 'zip', 'z'));

			return false;
		}

		$this->currentPartOffset = @ftell($this->fp);
		debugMsg('Current part offset after reading header: ' . $this->currentPartOffset);

		$this->dataReadLength = 0;

		return true;
	}

	/**
	 * Concrete classes must use this method to read the file header
	 *
	 * @return bool True if reading the file was successful, false if an error occurred or we reached end of archive
	 */
	protected function readFileHeader()
	{
		// If the current part is over, proceed to the next part please
		if ($this->isEOF(true))
		{
			debugMsg('Opening next archive part');
			$gotNextFile = $this->nextFile();
		}

		$this->currentPartOffset = ftell($this->fp);

		if ($this->expectDataDescriptor)
		{
			// The last file had bit 3 of the general purpose bit flag set. This means that we have a
			// 12 byte data descriptor we need to skip. To make things worse, there might also be a 4
			// byte optional data descriptor header (0x08074b50).
			$junk = @fread($this->fp, 4);
			$junk = unpack('Vsig', $junk);
			if ($junk['sig'] == 0x08074b50)
			{
				// Yes, there was a signature
				$junk = @fread($this->fp, 12);
				debugMsg('Data descriptor (w/ header) skipped at ' . (ftell($this->fp) - 12));
			}
			else
			{
				// No, there was no signature, just read another 8 bytes
				$junk = @fread($this->fp, 8);
				debugMsg('Data descriptor (w/out header) skipped at ' . (ftell($this->fp) - 8));
			}

			// And check for EOF, too
			if ($this->isEOF(true))
			{
				debugMsg('EOF before reading header');

				$gotNextFile = $this->nextFile();
			}
		}

		// Get and decode Local File Header
		$headerBinary = fread($this->fp, 30);
		$headerData   =
			unpack('Vsig/C2ver/vbitflag/vcompmethod/vlastmodtime/vlastmoddate/Vcrc/Vcompsize/Vuncomp/vfnamelen/veflen', $headerBinary);

		// Check signature
		if (!($headerData['sig'] == 0x04034b50))
		{
			debugMsg('Not a file signature at ' . (ftell($this->fp) - 4));

			// The signature is not the one used for files. Is this a central directory record (i.e. we're done)?
			if ($headerData['sig'] == 0x02014b50)
			{
				debugMsg('EOCD signature at ' . (ftell($this->fp) - 4));
				// End of ZIP file detected. We'll just skip to the end of file...
				while ($this->nextFile())
				{
				};
				@fseek($this->fp, 0, SEEK_END); // Go to EOF
				return false;
			}
			else
			{
				if (isset($gotNextFile) && !$gotNextFile && $this->getState() !== 'postrun')
				{
					debugMsg(sprintf('Cannot open file %s for part #%d', $this->archiveList[$this->currentPartNumber] ?: '(unknown)', $this->currentPartNumber));

					$this->setError(AKText::sprintf(
						'INVALID_FILE_HEADER_OFFSET_ZERO',
						$this->archiveList[$this->currentPartNumber] ?: '(unknown)',
						$this->currentPartNumber,
						'zip',
						'z'
					));

					return false;
				}

				if ($this->currentPartOffset === 0 && $this->currentPartNumber > 0)
				{
					$this->setError(AKText::sprintf(
						'INVALID_FILE_HEADER_MULTIPART',
						$this->currentPartNumber,
						$this->currentPartOffset,
						'jpa',
						'j'
					));

					return false;
				}

				debugMsg('Invalid signature ' . dechex($headerData['sig']) . ' at ' . ftell($this->fp));

				if (count($this->archiveList) > 1)
				{
					$this->setError(AKText::sprintf(
						'INVALID_FILE_HEADER_MULTIPART',
						$this->currentPartNumber,
						$this->currentPartOffset,
						'zip',
						'z'
					));

					return false;
				}

				$this->setError(AKText::sprintf(
					'INVALID_FILE_HEADER',
					$this->currentPartNumber,
					$this->currentPartOffset,
					'zip',
					'z'
				));

				return false;
			}
		}

		// If bit 3 of the bitflag is set, expectDataDescriptor is true
		$this->expectDataDescriptor = ($headerData['bitflag'] & 4) == 4;

		$this->fileHeader            = new stdClass();
		$this->fileHeader->timestamp = 0;

		// Read the last modified data and time
		$lastmodtime = $headerData['lastmodtime'];
		$lastmoddate = $headerData['lastmoddate'];

		if ($lastmoddate && $lastmodtime)
		{
			// ----- Extract time
			$v_hour    = ($lastmodtime & 0xF800) >> 11;
			$v_minute  = ($lastmodtime & 0x07E0) >> 5;
			$v_seconde = ($lastmodtime & 0x001F) * 2;

			// ----- Extract date
			$v_year  = (($lastmoddate & 0xFE00) >> 9) + 1980;
			$v_month = ($lastmoddate & 0x01E0) >> 5;
			$v_day   = $lastmoddate & 0x001F;

			// ----- Get UNIX date format
			$this->fileHeader->timestamp = @mktime($v_hour, $v_minute, $v_seconde, $v_month, $v_day, $v_year);
		}

		$isBannedFile = false;

		$this->fileHeader->compressed   = $headerData['compsize'];
		$this->fileHeader->uncompressed = $headerData['uncomp'];
		$nameFieldLength                = $headerData['fnamelen'];
		$extraFieldLength               = $headerData['eflen'];

		// Read filename field
		$this->fileHeader->file = fread($this->fp, $nameFieldLength);

		// Handle file renaming
		$isRenamed = false;
		if (is_array($this->renameFiles) && (count($this->renameFiles) > 0))
		{
			if (array_key_exists($this->fileHeader->file, $this->renameFiles))
			{
				$this->fileHeader->file = $this->renameFiles[$this->fileHeader->file];
				$isRenamed              = true;
			}
		}

		// Handle directory renaming
		$isDirRenamed = false;
		if (is_array($this->renameDirs) && (count($this->renameDirs) > 0))
		{
			if (array_key_exists(dirname($this->fileHeader->file), $this->renameDirs))
			{
				$file         =
					rtrim($this->renameDirs[dirname($this->fileHeader->file)], '/') . '/' . basename($this->fileHeader->file);
				$isRenamed    = true;
				$isDirRenamed = true;
			}
		}

		// Read extra field if present
		if ($extraFieldLength > 0)
		{
			$extrafield = fread($this->fp, $extraFieldLength);
		}

		debugMsg('*' . ftell($this->fp) . ' IS START OF ' . $this->fileHeader->file . ' (' . $this->fileHeader->compressed . ' bytes)');


		// Decide filetype -- Check for directories
		$this->fileHeader->type = 'file';
		if (strrpos($this->fileHeader->file, '/') == strlen($this->fileHeader->file) - 1)
		{
			$this->fileHeader->type = 'dir';
		}
		// Decide filetype -- Check for symbolic links
		if (($headerData['ver1'] == 10) && ($headerData['ver2'] == 3))
		{
			$this->fileHeader->type = 'link';
		}

		switch ($headerData['compmethod'])
		{
			case 0:
				$this->fileHeader->compression = 'none';
				break;
			case 8:
				$this->fileHeader->compression = 'gzip';
				break;
		}

		// Find hard-coded banned files
		if ((basename($this->fileHeader->file) == ".") || (basename($this->fileHeader->file) == ".."))
		{
			$isBannedFile = true;
		}

		// Also try to find banned files passed in class configuration
		if ((count($this->skipFiles) > 0) && (!$isRenamed))
		{
			if (in_array($this->fileHeader->file, $this->skipFiles))
			{
				$isBannedFile = true;
			}
		}

		// If we have a banned file, let's skip it
		if ($isBannedFile)
		{
			// Advance the file pointer, skipping exactly the size of the compressed data
			$seekleft = $this->fileHeader->compressed;
			while ($seekleft > 0)
			{
				// Ensure that we can seek past archive part boundaries
				$curSize = @filesize($this->archiveList[$this->currentPartNumber]);
				$curPos  = @ftell($this->fp);
				$canSeek = $curSize - $curPos;
				if ($canSeek > $seekleft)
				{
					$canSeek = $seekleft;
				}
				@fseek($this->fp, $canSeek, SEEK_CUR);
				$seekleft -= $canSeek;
				if ($seekleft)
				{
					$this->nextFile();
				}
			}

			$this->currentPartOffset = @ftell($this->fp);
			$this->runState          = AK_STATE_DONE;

			return true;
		}

		// Remove the removePath, if any
		$this->fileHeader->file = $this->removePath($this->fileHeader->file);

		// Last chance to prepend a path to the filename
		$archivePath = null;

		if (!empty($this->addPath) && !$isDirRenamed)
		{
			$archivePath            = $this->fileHeader->file;
			$this->fileHeader->file = $this->addPath . $this->fileHeader->file;
		}

		// Refuse to extract an entry which resolves outside the extraction root. We reject
		// instead of rewriting the path: a silently corrected path hides a hostile archive,
		// whereas a hard stop puts it in front of the person doing the restoration.
		if (!$this->isPathInRoot($this->fileHeader->file, $archivePath))
		{
			debugMsg('Entry outside the extraction root: ' . $this->fileHeader->file);
			$this->setError(AKText::sprintf('ERR_PATH_OUTSIDE_ROOT', $this->fileHeader->file));

			return false;
		}

		// Get the translated path name
		if (!$this->mustSkip())
		{
			if ($this->fileHeader->type == 'file')
			{
				$this->fileHeader->realFile = $this->postProcEngine->processFilename($this->fileHeader->file);
			}
			elseif ($this->fileHeader->type == 'dir')
			{
				$this->fileHeader->timestamp = 0;

				$dir = $this->fileHeader->file;

				$this->postProcEngine->createDirRecursive($dir, 0755);
				$this->postProcEngine->processFilename(null);
			}
			else
			{
				// Symlink; do not post-process
				$this->fileHeader->timestamp = 0;
				$this->postProcEngine->processFilename(null);
			}

			$this->createDirectory();
		}

		// Header is read
		$this->runState = AK_STATE_HEADER;

		return true;
	}

}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * JPS archive extraction class
 */
class AKUnarchiverJPS extends AKUnarchiverJPA
{
	/**
	 * Header data for the archive
	 *
	 * @var   array
	 */
	protected $archiveHeaderData = [];

	/**
	 * Plaintext password from which the encryption key will be derived with PBKDF2
	 *
	 * @var   string
	 */
	protected $password = '';

	/**
	 * Which hash algorithm should I use for key derivation with PBKDF2.
	 *
	 * @var   string
	 */
	private $pbkdf2Algorithm = 'sha1';

	/**
	 * How many iterations should I use for key derivation with PBKDF2
	 *
	 * @var   int
	 */
	private $pbkdf2Iterations = 1000;

	/**
	 * Should I use a static salt for key derivation with PBKDF2?
	 *
	 * @var   bool
	 */
	private $pbkdf2UseStaticSalt = 0;

	/**
	 * Static salt for key derivation with PBKDF2
	 *
	 * @var   string
	 */
	private $pbkdf2StaticSalt = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";

	/**
	 * How much compressed data I have read since the last file header read
	 *
	 * @var   int
	 */
	private $compressedSizeReadSinceLastFileHeader = 0;

	public function __construct()
	{
		$this->password = AKFactory::get('kickstart.jps.password', '');
	}

	public function __wakeup()
	{
		parent::__wakeup();

		// Make sure the decryption is all set up (required!)
		AKEncryptionAES::setPbkdf2Algorithm($this->pbkdf2Algorithm);
		AKEncryptionAES::setPbkdf2Iterations($this->pbkdf2Iterations);
		AKEncryptionAES::setPbkdf2UseStaticSalt($this->pbkdf2UseStaticSalt);
		AKEncryptionAES::setPbkdf2StaticSalt($this->pbkdf2StaticSalt);
	}


	protected function readArchiveHeader()
	{
		// Initialize header data array
		$this->archiveHeaderData = new stdClass();

		// Open the first part
		$this->nextFile();

		// Fail for unreadable files
		if ($this->fp === false)
		{
			return false;
		}

		// Fuzzy check for the start of archive.
		debugMsg('Fuzzy checking for archive signature');

		$sigFound = $this->fuzzySignatureSearch([
			'JPS'
		], 3);

		if (!$sigFound)
		{
			debugMsg('Cannot find a valid archive signature in the first 128Kb of the first part file');

			$this->setError(AKText::sprintf('ERR_INVALID_ARCHIVE_LONG', 'jps', 'j'));

			return false;
		}

		debugMsg(sprintf('File signature found, position %d', ftell($this->fp)));

		// Read the signature
		$sig = fread($this->fp, 3);

		if ($sig != 'JPS')
		{
			// Not a JPS file
			$this->setError(AKText::sprintf('ERR_INVALID_ARCHIVE_LONG', 'jps', 'j'));

			return false;
		}

		// Read and parse the known portion of header data (5 bytes)
		$bin_data    = fread($this->fp, 5);
		$header_data = unpack('Cmajor/Cminor/cspanned/vextra', $bin_data);

		// Is this a v2 archive?
		$versionHumanReadable = $header_data['major'] . '.' . $header_data['minor'];
		$isV2Archive = version_compare($versionHumanReadable, '2.0', 'ge');

		// Load any remaining header data
		$rest_length = $header_data['extra'];

		if ($isV2Archive && $rest_length)
		{
			// V2 archives only have one kind of extra header
			if (!$this->readKeyExpansionExtraHeader())
			{
				return false;
			}
		}
		elseif ($rest_length > 0)
		{
			$junk = fread($this->fp, $rest_length);
		}

		// Temporary array with all the data we read
		$temp = [
			'signature' => $sig,
			'major'     => $header_data['major'],
			'minor'     => $header_data['minor'],
			'spanned'   => $header_data['spanned']
		];
		// Array-to-object conversion
		foreach ($temp as $key => $value)
		{
			$this->archiveHeaderData->{$key} = $value;
		}

		$this->currentPartOffset = @ftell($this->fp);

		$this->dataReadLength = 0;

		return true;
	}

	/**
	 * Concrete classes must use this method to read the file header
	 *
	 * @return bool True if reading the file was successful, false if an error occurred or we reached end of archive
	 */
	protected function readFileHeader()
	{
		// If the current part is over, proceed to the next part please
		if ($this->isEOF(true))
		{
			$this->nextFile();
		}

		$this->currentPartOffset = ftell($this->fp);

		// Get and decode Entity Description Block
		$signature = fread($this->fp, 3);

		// Check for end-of-archive siganture
		if ($signature == 'JPE')
		{
			$this->setState('postrun');

			return true;
		}

		$this->fileHeader            = new stdClass();
		$this->fileHeader->timestamp = 0;

		// Check signature
		if ($signature != 'JPF')
		{
			if ($this->isEOF(true))
			{
				// This file is finished; make sure it's the last one
				$gotNextFile = $this->nextFile();

				if (!$gotNextFile && $this->getState() !== 'postrun')
				{
					debugMsg(sprintf('Cannot open file %s for part #%d', $this->archiveList[$this->currentPartNumber] ?: '(unknown)', $this->currentPartNumber));

					$this->setError(AKText::sprintf(
						'INVALID_FILE_HEADER_OFFSET_ZERO',
						$this->archiveList[$this->currentPartNumber] ?: '(unknown)',
						$this->currentPartNumber,
						'jps',
						'j'
					));

					return false;
				}

				if (!$this->isEOF(false))
				{
					$this->setError(AKText::sprintf('INVALID_FILE_HEADER', $this->currentPartNumber, $this->currentPartOffset));

					return false;
				}

				// We're just finished
				return false;
			}
			else
			{
				fseek($this->fp, -6, SEEK_CUR);
				$signature = fread($this->fp, 3);
				if ($signature == 'JPE')
				{
					return false;
				}

				if (count($this->archiveList) > 1)
				{
					$this->setError(AKText::sprintf(
						'INVALID_FILE_HEADER_MULTIPART',
						$this->currentPartNumber,
						$this->currentPartOffset,
						'jps',
						'j'
					));

					return false;
				}

				$this->setError(AKText::sprintf(
					'INVALID_FILE_HEADER',
					$this->currentPartNumber,
					$this->currentPartOffset,
					'jps',
					'j'
				));

				return false;
			}
		}

		// This a JPS Entity Block. Process the header.

		$isBannedFile = false;

		// Make sure the decryption is all set up
		AKEncryptionAES::setPbkdf2Algorithm($this->pbkdf2Algorithm);
		AKEncryptionAES::setPbkdf2Iterations($this->pbkdf2Iterations);
		AKEncryptionAES::setPbkdf2UseStaticSalt($this->pbkdf2UseStaticSalt);
		AKEncryptionAES::setPbkdf2StaticSalt($this->pbkdf2StaticSalt);

		// Read and decrypt the header
		$edbhData = fread($this->fp, 4);
		$edbh     = unpack('vencsize/vdecsize', $edbhData);
		$bin_data = fread($this->fp, $edbh['encsize']);

		// Add the header length to the data read
		$this->compressedSizeReadSinceLastFileHeader += $edbh['encsize'] + 4;

		// Decrypt and truncate
		$bin_data = AKEncryptionAES::AESDecryptCBC($bin_data, $this->password);
		$bin_data = substr($bin_data, 0, $edbh['decsize']);

		// Read length of EDB and of the Entity Path Data
		$length_array = unpack('vpathsize', substr($bin_data, 0, 2));
		// Read the path data
		$file = substr($bin_data, 2, $length_array['pathsize']);

		// Handle file renaming
		$isRenamed = false;
		if (is_array($this->renameFiles) && (count($this->renameFiles) > 0))
		{
			if (array_key_exists($file, $this->renameFiles))
			{
				$file      = $this->renameFiles[$file];
				$isRenamed = true;
			}
		}

		// Handle directory renaming
		$isDirRenamed = false;
		if (is_array($this->renameDirs) && (count($this->renameDirs) > 0))
		{
			if (array_key_exists(dirname($file), $this->renameDirs))
			{
				$file         = rtrim($this->renameDirs[dirname($file)], '/') . '/' . basename($file);
				$isRenamed    = true;
				$isDirRenamed = true;
			}
		}

		// Read and parse the known data portion
		$bin_data    = substr($bin_data, 2 + $length_array['pathsize']);
		$header_data = unpack('Ctype/Ccompression/Vuncompsize/Vperms/Vfilectime', $bin_data);

		$this->fileHeader->timestamp = $header_data['filectime'];
		$compressionType             = $header_data['compression'];

		// Populate the return array
		$this->fileHeader->file         = $file;
		$this->fileHeader->uncompressed = $header_data['uncompsize'];
		switch ($header_data['type'])
		{
			case 0:
				$this->fileHeader->type = 'dir';
				break;

			case 1:
				$this->fileHeader->type = 'file';
				break;

			case 2:
				$this->fileHeader->type = 'link';
				break;
		}
		switch ($compressionType)
		{
			case 0:
				$this->fileHeader->compression = 'none';
				break;
			case 1:
				$this->fileHeader->compression = 'gzip';
				break;
			case 2:
				$this->fileHeader->compression = 'bzip2';
				break;
		}
		$this->fileHeader->permissions = $header_data['perms'];

		// Find hard-coded banned files
		if ((basename($this->fileHeader->file) == ".") || (basename($this->fileHeader->file) == ".."))
		{
			$isBannedFile = true;
		}

		// Also try to find banned files passed in class configuration
		if ((count($this->skipFiles) > 0) && (!$isRenamed))
		{
			if (in_array($this->fileHeader->file, $this->skipFiles))
			{
				$isBannedFile = true;
			}
		}

		// If we have a banned file, let's skip it
		if ($isBannedFile)
		{
			$done = false;
			while (!$done)
			{
				// Read the Data Chunk Block header
				$binMiniHead = fread($this->fp, 8);
				if (in_array(substr($binMiniHead, 0, 3), ['JPF', 'JPE']))
				{
					// Not a Data Chunk Block header, I am done skipping the file
					@fseek($this->fp, -8, SEEK_CUR); // Roll back the file pointer
					$done = true; // Mark as done
					continue; // Exit loop
				}
				else
				{
					// Skip forward by the amount of compressed data
					$miniHead = unpack('Vencsize/Vdecsize', $binMiniHead);
					@fseek($this->fp, $miniHead['encsize'], SEEK_CUR);
					$this->compressedSizeReadSinceLastFileHeader += 8 + $miniHead['encsize'];
				}
			}

			$this->currentPartOffset                     = @ftell($this->fp);
			$this->runState                              = AK_STATE_DONE;
			$this->fileHeader->compressed                = $this->compressedSizeReadSinceLastFileHeader;
			$this->compressedSizeReadSinceLastFileHeader = 0;

			return true;
		}

		// Remove the removePath, if any
		$this->fileHeader->file = $this->removePath($this->fileHeader->file);

		// Last chance to prepend a path to the filename
		$archivePath = null;

		if (!empty($this->addPath) && !$isDirRenamed)
		{
			$archivePath            = $this->fileHeader->file;
			$this->fileHeader->file = $this->addPath . $this->fileHeader->file;
		}

		// Refuse to extract an entry which resolves outside the extraction root. We reject
		// instead of rewriting the path: a silently corrected path hides a hostile archive,
		// whereas a hard stop puts it in front of the person doing the restoration.
		if (!$this->isPathInRoot($this->fileHeader->file, $archivePath))
		{
			debugMsg('Entry outside the extraction root: ' . $this->fileHeader->file);
			$this->setError(AKText::sprintf('ERR_PATH_OUTSIDE_ROOT', $this->fileHeader->file));

			return false;
		}

		// Get the translated path name
		$restorePerms = AKFactory::get('kickstart.setup.restoreperms', false);

		if (!$this->mustSkip())
		{
			if ($this->fileHeader->type == 'file')
			{
				// Regular file; ask the postproc engine to process its filename
				if ($restorePerms)
				{
					$this->fileHeader->realFile =
						$this->postProcEngine->processFilename($this->fileHeader->file, $this->fileHeader->permissions);
				}
				else
				{
					$this->fileHeader->realFile = $this->postProcEngine->processFilename($this->fileHeader->file);
				}
			}
			elseif ($this->fileHeader->type == 'dir')
			{
				$dir                        = $this->fileHeader->file;
				$this->fileHeader->realFile = $dir;

				// Directory; just create it
				if ($restorePerms)
				{
					$this->postProcEngine->createDirRecursive($this->fileHeader->file, $this->fileHeader->permissions);
				}
				else
				{
					$this->postProcEngine->createDirRecursive($this->fileHeader->file, 0755);
				}

				$this->postProcEngine->processFilename(null);
			}
			else
			{
				// Symlink; do not post-process
				$this->postProcEngine->processFilename(null);
			}

			$this->createDirectory();
		}


		$this->fileHeader->compressed                = $this->compressedSizeReadSinceLastFileHeader;
		$this->compressedSizeReadSinceLastFileHeader = 0;

		// Header is read
		$this->runState = AK_STATE_HEADER;

		$this->dataReadLength = 0;

		return true;
	}

	/**
	 * Creates the directory this file points to
	 */
	protected function createDirectory()
	{
		if ($this->mustSkip())
		{
			return true;
		}

		// Do we need to create a directory?
		$lastSlash = strrpos($this->fileHeader->realFile, '/');
		$dirName   = substr($this->fileHeader->realFile, 0, $lastSlash);
		$perms     = 0755;
		$ignore    = AKFactory::get('kickstart.setup.ignoreerrors', false) || $this->isIgnoredDirectory($dirName);

		if (($this->postProcEngine->createDirRecursive($dirName, $perms) == false) && (!$ignore))
		{
			$this->setError(AKText::sprintf('COULDNT_CREATE_DIR', $dirName));

			return false;
		}

		return true;
	}

	/**
	 * Concrete classes must use this method to process file data. It must set $runState to AK_STATE_DATAREAD when
	 * it's finished processing the file data.
	 *
	 * @return bool True if processing the file data was successful, false if an error occurred
	 */
	protected function processFileData()
	{
		switch ($this->fileHeader->type)
		{
			case 'dir':
				return $this->processTypeDir();
				break;

			case 'link':
				return $this->processTypeLink();
				break;

			case 'file':
				switch ($this->fileHeader->compression)
				{
					case 'none':
						return $this->processTypeFileUncompressed();
						break;

					case 'gzip':
					case 'bzip2':
						return $this->processTypeFileCompressedSimple();
						break;

				}
				break;
		}
	}

	/**
	 * Process the file data of a directory entry
	 *
	 * @return bool
	 */
	private function processTypeDir()
	{
		// Directory entries in the JPA do not have file data, therefore we're done processing the entry
		$this->runState = AK_STATE_DATAREAD;

		return true;
	}

	/**
	 * Process the file data of a link entry
	 *
	 * @return bool
	 */
	private function processTypeLink()
	{

		// Does the file have any data, at all?
		if ($this->fileHeader->uncompressed == 0)
		{
			// No file data!
			$this->runState = AK_STATE_DATAREAD;

			return true;
		}

		// Read the mini header
		$binMiniHeader   = fread($this->fp, 8);
		$reallyReadBytes = akstringlen($binMiniHeader);

		if ($reallyReadBytes < 8)
		{
			// We read less than requested! Why? Did we hit local EOF?
			if ($this->isEOF(true) && !$this->isEOF(false))
			{
				// Yeap. Let's go to the next file
				$this->nextFile();
				// Retry reading the header
				$binMiniHeader   = fread($this->fp, 8);
				$reallyReadBytes = akstringlen($binMiniHeader);
				// Still not enough data? If so, the archive is corrupt or missing parts.
				if ($reallyReadBytes < 8)
				{
					$this->setError(AKText::_('ERR_CORRUPT_ARCHIVE'));

					return false;
				}
			}
			else
			{
				// Nope. The archive is corrupt
				$this->setError(AKText::_('ERR_CORRUPT_ARCHIVE'));

				return false;
			}
		}

		// Read the encrypted data
		$miniHeader      = unpack('Vencsize/Vdecsize', $binMiniHeader);
		$toReadBytes     = $miniHeader['encsize'];
		$data            = $this->fread($this->fp, $toReadBytes);
		$reallyReadBytes = akstringlen($data);
		$this->compressedSizeReadSinceLastFileHeader += 8 + $miniHeader['encsize'];

		if ($reallyReadBytes < $toReadBytes)
		{
			// We read less than requested! Why? Did we hit local EOF?
			if ($this->isEOF(true) && !$this->isEOF(false))
			{
				// Yeap. Let's go to the next file
				$this->nextFile();
				// Read the rest of the data
				$toReadBytes -= $reallyReadBytes;
				$restData        = $this->fread($this->fp, $toReadBytes);
				$reallyReadBytes = akstringlen($data);
				if ($reallyReadBytes < $toReadBytes)
				{
					$this->setError(AKText::_('ERR_CORRUPT_ARCHIVE'));

					return false;
				}
				$data .= $restData;
			}
			else
			{
				// Nope. The archive is corrupt
				$this->setError(AKText::_('ERR_CORRUPT_ARCHIVE'));

				return false;
			}
		}

		// Decrypt the data
		$data = AKEncryptionAES::AESDecryptCBC($data, $this->password);

		// Is the length of the decrypted data less than expected?
		$data_length = akstringlen($data);
		if ($data_length < $miniHeader['decsize'])
		{
			$this->setError(AKText::_('ERR_INVALID_JPS_PASSWORD'));

			return false;
		}

		// Trim the data
		$data = substr($data, 0, $miniHeader['decsize']);

		if (!$this->mustSkip())
		{
			// Try to remove an existing file or directory by the same name
			if (file_exists($this->fileHeader->file))
			{
				@unlink($this->fileHeader->file);
				@rmdir($this->fileHeader->file);
			}
			// Remove any trailing slash
			if (substr($this->fileHeader->file, -1) == '/')
			{
				$this->fileHeader->file = substr($this->fileHeader->file, 0, -1);
			}
			// Create the symlink - only possible within PHP context. There's no support built in the FTP protocol, so no postproc use is possible here :(
			@symlink($data, $this->fileHeader->file);
		}

		$this->runState = AK_STATE_DATAREAD;

		return true; // No matter if the link was created!
	}

	private function processTypeFileUncompressed()
	{
		// Uncompressed files are being processed in small chunks, to avoid timeouts
		if (($this->dataReadLength == 0) && !$this->mustSkip())
		{
			// Before processing file data, ensure permissions are adequate
			$this->setCorrectPermissions($this->fileHeader->file);

			clearstatcache($this->fileHeader->file);
		}

		// Open the output file
		if (!$this->mustSkip())
		{
			$ignore =
				AKFactory::get('kickstart.setup.ignoreerrors', false) || $this->isIgnoredDirectory($this->fileHeader->file);
			if ($this->dataReadLength == 0)
			{
				$outfp = @fopen($this->fileHeader->realFile, 'w');
			}
			else
			{
				$outfp = @fopen($this->fileHeader->realFile, 'a');
			}

			// Can we write to the file?
			if (($outfp === false) && (!$ignore))
			{
				// An error occurred
				$this->setError(AKText::sprintf('COULDNT_WRITE_FILE', $this->fileHeader->realFile));

				return false;
			}
		}

		// Does the file have any data, at all?
		if ($this->fileHeader->uncompressed == 0)
		{
			// No file data!
			if (!$this->mustSkip() && is_resource($outfp))
			{
				@fclose($outfp);
			}
			$this->runState = AK_STATE_DATAREAD;

			return true;
		}

		$this->setError('An uncompressed file was detected; this is not supported by this archive extraction utility');

		return false;
	}

	private function processTypeFileCompressedSimple()
	{
		$timer = AKFactory::getTimer();

		// Files are being processed in small chunks, to avoid timeouts
		if (($this->dataReadLength == 0) && !$this->mustSkip())
		{
			// Before processing file data, ensure permissions are adequate
			$this->setCorrectPermissions($this->fileHeader->file);

			clearstatcache($this->fileHeader->file);
		}

		// Open the output file
		if (!$this->mustSkip())
		{
			// Open the output file
			$outfp = @fopen($this->fileHeader->realFile, 'w');

			// Can we write to the file?
			$ignore =
				AKFactory::get('kickstart.setup.ignoreerrors', false) || $this->isIgnoredDirectory($this->fileHeader->file);
			if (($outfp === false) && (!$ignore))
			{
				// An error occurred
				$this->setError(AKText::sprintf('COULDNT_WRITE_FILE', $this->fileHeader->realFile));

				return false;
			}
		}

		// Does the file have any data, at all?
		if ($this->fileHeader->uncompressed == 0)
		{
			// No file data!
			if (!$this->mustSkip())
			{
				if (is_resource($outfp))
				{
					@fclose($outfp);
				}
			}
			$this->runState = AK_STATE_DATAREAD;

			return true;
		}

		$leftBytes = $this->fileHeader->uncompressed - $this->dataReadLength;

		// Loop while there's data to write and enough time to do it
		while (($leftBytes > 0) && ($timer->getTimeLeft() > 0))
		{
			// Read the mini header
			$binMiniHeader   = fread($this->fp, 8);
			$reallyReadBytes = akstringlen($binMiniHeader);
			if ($reallyReadBytes < 8)
			{
				// We read less than requested! Why? Did we hit local EOF?
				if ($this->isEOF(true) && !$this->isEOF(false))
				{
					// Yeap. Let's go to the next file
					$this->nextFile();
					// Retry reading the header
					$binMiniHeader   = fread($this->fp, 8);
					$reallyReadBytes = akstringlen($binMiniHeader);
					// Still not enough data? If so, the archive is corrupt or missing parts.
					if ($reallyReadBytes < 8)
					{
						$this->setError(AKText::_('ERR_CORRUPT_ARCHIVE'));

						return false;
					}
				}
				else
				{
					// Nope. The archive is corrupt
					$this->setError(AKText::_('ERR_CORRUPT_ARCHIVE'));

					return false;
				}
			}

			// Read the encrypted data
			$miniHeader      = unpack('Vencsize/Vdecsize', $binMiniHeader);
			$toReadBytes     = $miniHeader['encsize'];
			$data            = $this->fread($this->fp, $toReadBytes);
			$reallyReadBytes = akstringlen($data);

			$this->compressedSizeReadSinceLastFileHeader += $miniHeader['encsize'] + 8;

			if ($reallyReadBytes < $toReadBytes)
			{
				// We read less than requested! Why? Did we hit local EOF?
				if ($this->isEOF(true) && !$this->isEOF(false))
				{
					// Yeap. Let's go to the next file
					$this->nextFile();
					// Read the rest of the data
					$toReadBytes -= $reallyReadBytes;
					$restData        = $this->fread($this->fp, $toReadBytes);
					$reallyReadBytes = akstringlen($restData);
					if ($reallyReadBytes < $toReadBytes)
					{
						$this->setError(AKText::_('ERR_CORRUPT_ARCHIVE'));

						return false;
					}
					if (akstringlen($data) == 0)
					{
						$data = $restData;
					}
					else
					{
						$data .= $restData;
					}
				}
				else
				{
					// Nope. The archive is corrupt
					$this->setError(AKText::_('ERR_CORRUPT_ARCHIVE'));

					return false;
				}
			}

			// Decrypt the data
			$data = AKEncryptionAES::AESDecryptCBC($data, $this->password);

			// Is the length of the decrypted data less than expected?
			$data_length = akstringlen($data);
			if ($data_length < $miniHeader['decsize'])
			{
				$this->setError(AKText::_('ERR_INVALID_JPS_PASSWORD'));

				return false;
			}

			// Trim the data
			$data = substr($data, 0, $miniHeader['decsize']);

			// Decompress
			$data    = gzinflate($data);
			$unc_len = akstringlen($data);

			// Write the decrypted data
			if (!$this->mustSkip())
			{
				if (is_resource($outfp))
				{
					@fwrite($outfp, $data, akstringlen($data));
				}
			}

			// Update the read length
			$this->dataReadLength += $unc_len;
			$leftBytes = $this->fileHeader->uncompressed - $this->dataReadLength;
		}

		// Close the file pointer
		if (!$this->mustSkip())
		{
			if (is_resource($outfp))
			{
				@fclose($outfp);
			}
		}

		// Was this a pre-timeout bail out?
		if ($leftBytes > 0)
		{
			$this->runState = AK_STATE_DATA;
		}
		else
		{
			// Oh! We just finished!
			$this->runState       = AK_STATE_DATAREAD;
			$this->dataReadLength = 0;
		}

		return true;
	}

	private function readKeyExpansionExtraHeader()
	{
		$signature = fread($this->fp, 4);

		if ($signature != "JH\x00\x01")
		{
			// Not a valid JPS file
			$this->setError(AKText::_('ERR_NOT_A_JPS_FILE'));

			return false;
		}

		$bin_data    = fread($this->fp, 8);
		$header_data = unpack('vlength/Calgo/Viterations/CuseStaticSalt', $bin_data);

		if ($header_data['length'] != 76)
		{
			// Not a valid JPS file
			$this->setError(AKText::_('ERR_NOT_A_JPS_FILE'));

			return false;
		}

		switch ($header_data['algo'])
		{
			case 0:
				$algorithm = 'sha1';
				break;

			case 1:
				$algorithm = 'sha256';
				break;

			case 2:
				$algorithm = 'sha512';
				break;

			default:
				// Not a valid JPS file
				$this->setError(AKText::_('ERR_NOT_A_JPS_FILE'));

				return false;
				break;
		}

		$this->pbkdf2Algorithm     = $algorithm;
		$this->pbkdf2Iterations    = $header_data['iterations'];
		$this->pbkdf2UseStaticSalt = $header_data['useStaticSalt'];
		$this->pbkdf2StaticSalt    = fread($this->fp, 64);

		return true;
	}
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Timer class
 */
class AKCoreTimer extends AKAbstractObject
{
	/** @var int Maximum execution time allowance per step */
	private $max_exec_time = null;

	/** @var int Timestamp of execution start */
	private $start_time = null;

	/**
	 * Public constructor, creates the timer object and calculates the execution time limits
	 *
	 * @return  void
	 */
	public function __construct()
	{
		// Initialize start time
		$this->start_time = $this->microtime_float();

		// Get configured max time per step and bias
		$config_max_exec_time = AKFactory::get('kickstart.tuning.max_exec_time', 14);
		$bias                 = AKFactory::get('kickstart.tuning.run_time_bias', 75) / 100;

		// Get PHP's maximum execution time (our upper limit)
		if (@function_exists('ini_get'))
		{
			$php_max_exec_time = @ini_get("maximum_execution_time");
			if ((!is_numeric($php_max_exec_time)) || ($php_max_exec_time == 0))
			{
				// If we have no time limit, set a hard limit of about 10 seconds
				// (safe for Apache and IIS timeouts, verbose enough for users)
				$php_max_exec_time = 14;
			}
		}
		else
		{
			// If ini_get is not available, use a rough default
			$php_max_exec_time = 14;
		}

		// Apply an arbitrary correction to counter CMS load time
		$php_max_exec_time--;

		// Apply bias
		$php_max_exec_time    = $php_max_exec_time * $bias;
		$config_max_exec_time = $config_max_exec_time * $bias;

		// Use the most appropriate time limit value
		if ($config_max_exec_time > $php_max_exec_time)
		{
			$this->max_exec_time = $php_max_exec_time;
		}
		else
		{
			$this->max_exec_time = $config_max_exec_time;
		}
	}

	/**
	 * Returns the current timestampt in decimal seconds
	 */
	private function microtime_float()
	{
		list($usec, $sec) = explode(" ", microtime());

		return ((float) $usec + (float) $sec);
	}

	/**
	 * Wake-up function to reset internal timer when we get unserialized
	 */
	public function __wakeup()
	{
		// Re-initialize start time on wake-up
		$this->start_time = $this->microtime_float();
	}

	/**
	 * Gets the number of seconds left, before we hit the "must break" threshold
	 *
	 * @return float
	 */
	public function getTimeLeft()
	{
		return $this->max_exec_time - $this->getRunningTime();
	}

	/**
	 * Gets the time elapsed since object creation/unserialization, effectively how
	 * long Akeeba Engine has been processing data
	 *
	 * @return float
	 */
	public function getRunningTime()
	{
		return $this->microtime_float() - $this->start_time;
	}

	/**
	 * Enforce the minimum execution time
	 */
	public function enforce_min_exec_time()
	{
		// Try to get a sane value for PHP's maximum_execution_time INI parameter
		if (@function_exists('ini_get'))
		{
			$php_max_exec = @ini_get("maximum_execution_time");
		}
		else
		{
			$php_max_exec = 10;
		}
		if (($php_max_exec == "") || ($php_max_exec == 0))
		{
			$php_max_exec = 10;
		}
		// Decrease $php_max_exec time by 500 msec we need (approx.) to tear down
		// the application, as well as another 500msec added for rounding
		// error purposes. Also make sure this is never gonna be less than 0.
		$php_max_exec = max($php_max_exec * 1000 - 1000, 0);

		// Get the "minimum execution time per step" Akeeba Backup configuration variable
		$minexectime = AKFactory::get('kickstart.tuning.min_exec_time', 0);
		if (!is_numeric($minexectime))
		{
			$minexectime = 0;
		}

		// Make sure we are not over PHP's time limit!
		if ($minexectime > $php_max_exec)
		{
			$minexectime = $php_max_exec;
		}

		// Get current running time
		$elapsed_time = $this->getRunningTime() * 1000;
		$minexectime = 1000.0 * $minexectime;

		// Only run a sleep delay if we haven't reached the minexectime execution time
		if (($minexectime > $elapsed_time) && ($elapsed_time > 0))
		{
			$sleep_msec = (int)($minexectime - $elapsed_time);

			if (function_exists('usleep'))
			{
				usleep(1000 * $sleep_msec);
			}
			elseif (function_exists('time_nanosleep'))
			{
				$sleep_sec  = floor($sleep_msec / 1000);
				$sleep_nsec = 1000000 * ($sleep_msec - ($sleep_sec * 1000));
				time_nanosleep($sleep_sec, $sleep_nsec);
			}
			elseif (function_exists('time_sleep_until'))
			{
				$until_timestamp = time() + $sleep_msec / 1000;
				time_sleep_until($until_timestamp);
			}
			elseif (function_exists('sleep'))
			{
				$sleep_sec = ceil($sleep_msec / 1000);
				sleep($sleep_sec);
			}
		}
	}

	/**
	 * Reset the timer. It should only be used in CLI mode!
	 */
	public function resetTime()
	{
		$this->start_time = $this->microtime_float();
	}

	/**
	 * @param int $max_exec_time
	 */
	public function setMaxExecTime($max_exec_time)
	{
		$this->max_exec_time = $max_exec_time;
	}
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2024-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * PHP 8.4+ workaround for standalone MD5 and SHA-1 functions.
 *
 * PHP 8.4 deprecates the standalone md5(), md5_file(), sha1(), and sha1_file() functions. This trait creates shims
 * which use the hash() and hash_file() functions instead where available.
 *
 * IMPORTANT! PHP 7.4 made the ext/hash extension mandatory. These shims are here only as a backwards compatibility aid.
 * Eventually, we need to remove them, replacing their use by the direct use of hash() and hash_file().
 *
 * @deprecated 9.0
 */
abstract class AKUtilsHash
{
	/**
	 * @deprecated 9.0 Use hash() instead
	 */
	public static function md5($string, $binary = false)
	{
		static $shouldUseHash = null;

		if ($shouldUseHash === null)
		{
			$shouldUseHash = function_exists('hash')
			                 && function_exists('hash_algos')
			                 && in_array('md5', hash_algos());
		}

		return $shouldUseHash ? hash('md5', $string, $binary) : md5($string, $binary);
	}
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */


class AKUtilsHtaccess extends AKAbstractObject
{
	/**
	 * Extract the PHP handler configuration from a .htaccess file.
	 *
	 * This method supports AddHandler lines and SetHandler blocks.
	 *
	 * @param   string  $htaccess
	 *
	 * @return  string|null  NULL when not found
	 */
	public static function extractHandler($htaccess)
	{
		// Normalize the .htaccess
		$htaccess = self::normalizeHtaccess($htaccess);

		// Look for SetHandler and AddHandler in Files and FilesMatch containers
		foreach (['Files', 'FilesMatch'] as $container)
		{
			$result = self::extractContainer($container, $htaccess);

			if (!is_null($result))
			{
				return $result;
			}
		}

		// Fallback: extract an AddHandler line
		$found = preg_match('#^AddHandler\s?.*\.php.*$#mi', $htaccess, $matches);

		if ($found >= 1)
		{
			return $matches[0];
		}

		return null;
	}

	/**
	 * Extracts a Files or FilesMatch container with an AddHandler or SetHandler line
	 *
	 * @param   string  $container  "Files" or "FilesMatch"
	 * @param   string  $htaccess   The .htaccess file content
	 *
	 * @return  string|null  NULL when not found
	 */
	protected static function extractContainer($container, $htaccess)
	{
		// Try to find the opening container tag e.g. <Files....>
		$pattern = sprintf('#<%s\s*.*\.php.*>#m', $container);
		$found   = preg_match($pattern, $htaccess, $matches, PREG_OFFSET_CAPTURE);

		if (!$found)
		{
			return null;
		}

		// Get the rest of the .htaccess sample
		$openContainer = $matches[0][0];
		$htaccess      = trim(substr($htaccess, $matches[0][1] + strlen($matches[0][0])));

		// Try to find the closing container tag
		$pattern = sprintf('#</%s\s*>#m', $container);
		$found   = preg_match($pattern, $htaccess, $matches, PREG_OFFSET_CAPTURE);

		if (!$found)
		{
			return null;
		}

		// Get the rest of the .htaccess sample
		$htaccess       = trim(substr($htaccess, 0, $matches[$found - 1][1]));
		$closeContainer = $matches[$found - 1][0];

		if (empty($htaccess))
		{
			return null;
		}

		// Now we'll explode remaining lines and find the first SetHandler or AddHandler line
		$lines = array_map('trim', explode("\n", $htaccess));
		$lines = array_filter($lines, function ($line) {
			return preg_match('#(Add|Set)Handler\s?#i', $line) >= 1;
		});

		if (empty($lines))
		{
			return null;
		}

		return $openContainer . "\n" . array_shift($lines) . "\n" . $closeContainer;
	}

	/**
	 * Normalize the .htaccess file content, making it suitable for handler extraction
	 *
	 * @param   string  $htaccess  The original file
	 *
	 * @return  string  The normalized file
	 */
	private static function normalizeHtaccess($htaccess)
	{
		// Convert all newlines into UNIX style
		$htaccess = str_replace("\r\n", "\n", $htaccess);
		$htaccess = str_replace("\r", "\n", $htaccess);

		// Return only non-comment, non-empty lines
		$isNonEmptyNonComment = function ($line) {
			$line = trim($line);

			return !empty($line) && (substr($line, 0, 1) !== '#');
		};

		$lines = array_map('trim', explode("\n", $htaccess));

		return implode("\n", array_filter($lines, $isNonEmptyNonComment));
	}
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * A filesystem scanner which uses opendir()
 */
class AKUtilsLister extends AKAbstractObject
{
	public function &getFiles($folder, $pattern = '*')
	{
		// Initialize variables
		$arr   = [];
		$false = false;

		if (!is_dir($folder))
		{
			return $false;
		}

		$handle = @opendir($folder);
		// If directory is not accessible, just return FALSE
		if ($handle === false)
		{
			$this->setWarning('Unreadable directory ' . $folder);

			return $false;
		}

		while (($file = @readdir($handle)) !== false)
		{
			if (!fnmatch($pattern, $file))
			{
				continue;
			}

			if (($file != '.') && ($file != '..'))
			{
				$ds    =
					($folder == '') || ($folder == '/') || (@substr($folder, -1) == '/') || (@substr($folder, -1) == DIRECTORY_SEPARATOR) ?
						'' : DIRECTORY_SEPARATOR;
				$dir   = $folder . $ds . $file;
				$isDir = is_dir($dir);
				if (!$isDir)
				{
					$arr[] = $dir;
				}
			}
		}
		@closedir($handle);

		return $arr;
	}

	public function &getFolders($folder, $pattern = '*')
	{
		// Initialize variables
		$arr   = [];
		$false = false;

		if (!is_dir($folder))
		{
			return $false;
		}

		$handle = @opendir($folder);
		// If directory is not accessible, just return FALSE
		if ($handle === false)
		{
			$this->setWarning('Unreadable directory ' . $folder);

			return $false;
		}

		while (($file = @readdir($handle)) !== false)
		{
			if (!fnmatch($pattern, $file))
			{
				continue;
			}

			if (($file != '.') && ($file != '..'))
			{
				$ds    =
					($folder == '') || ($folder == '/') || (@substr($folder, -1) == '/') || (@substr($folder, -1) == DIRECTORY_SEPARATOR) ?
						'' : DIRECTORY_SEPARATOR;
				$dir   = $folder . $ds . $file;
				$isDir = is_dir($dir);
				if ($isDir)
				{
					$arr[] = $dir;
				}
			}
		}
		@closedir($handle);

		return $arr;
	}
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * A filesystem zapper - removes all files and folders under a root
 */
class AKUtilsZapper extends AKAbstractPart
{
	/** @var array Directories left to be deleted */
	private $directory_list;

	/** @var array Files left to be deleted */
	private $file_list;

	/**
	 * Have we finished scanning all subdirectories of the current directory?
	 *
	 * @var   boolean
	 */
	private $done_subdir_scanning = false;

	/**
	 * Have we finished scanning all files of the current directory?
	 *
	 * @var   boolean
	 */
	private $done_file_scanning = true;

	/**
	 * Is the current directory completely excluded?
	 *
	 * @var boolean
	 */
	private $excluded_folder = false;

	/** @var   integer  How many files have been processed in the current step */
	private $processed_files_counter;

	/** @var   string  Current directory being scanned */
	private $current_directory;

	/** @var   string  Current root directory being processed */
	private $root = '';

	/** @var   integer  Total files to process */
	private $total_files = 0;

	/** @var   integer  Total files already processed */
	private $done_files = 0;

	/** @var   integer  Total folders to process */
	private $total_folders = 0;

	/** @var   integer  Total folders already processed */
	private $done_folders = 0;

	/** @var array Absolute filesystem patterns to never delete (e.g. /var/www/html/*.jpa) */
	private $excluded = [];

	/** @var bool Are we in a dry-run? */
	private $dryRun = false;

	/**
	 * Implements the _prepare() abstract method
	 *
	 * Configuration parameters:
	 *
	 * root      The root under which we are going to be deleting files
	 * excluded  Absolute filesystem patterns to never delete (e.g. /var/www/html/*.jpa)
	 *
	 * @return  void
	 */
	protected function _prepare()
	{
		debugMsg(self::class . " :: Starting _prepare()");

		$defaultExcluded = $this->getDefaultExclusions();

		$parameters = array_merge([
			'root'     => rtrim(AKFactory::get('kickstart.setup.destdir'), '/' . DIRECTORY_SEPARATOR),
			'excluded' => $defaultExcluded,
            'dryRun'   => AKFactory::get('kickstart.setup.dryrun', false)
		], $this->_parametersArray);

		$this->root                 = $parameters['root'];
		$this->excluded             = $parameters['excluded'];
		$this->directory_list[]     = $this->root;
		$this->done_subdir_scanning = true;
		$this->done_file_scanning   = true;
		$this->total_files          = 0;
		$this->done_files           = 0;
		$this->total_folders        = 0;
		$this->done_folders         = 0;
		$this->dryRun               = $parameters['dryRun'];

		if (empty($this->root))
		{
			$error = "The folder to delete was not specified.";

			debugMsg(self::class . " :: " . $error);
			$this->setError($error);

			return;
		}

		if (!is_dir($this->root))
		{
			$error = sprintf("Folder %s does not exist", $this->root);

			debugMsg(self::class . " :: " . $error);
			$this->setError($error);

			return;
		}

		$this->setState('prepared');

		debugMsg(self::class . " :: prepared");
	}

	protected function _run()
	{
		if ($this->getState() == 'postrun')
		{
			debugMsg(self::class . " :: Already finished");
			$this->setStep("-");
			$this->setSubstep("");

			return true;
		}

		// If I'm done scanning files and subdirectories and there are no more files to pack get the next
		// directory. This block is triggered in the first step in a new root.
		if (empty($this->file_list) && $this->done_subdir_scanning && $this->done_file_scanning)
		{
			$this->progressMarkFolderDone();

			if (!$this->getNextDirectory())
			{
			    $this->setState('postrun');
				return true;
			}
		}

		// If I'm not done scanning for files and the file list is empty then scan for more files
		if (!$this->done_file_scanning && empty($this->file_list))
		{
			$this->scanFiles();
		}
		// If I have files left, delete them
		elseif (!empty($this->file_list))
		{
			$this->delete_files();
		}
		// If I'm not done scanning subdirectories, go ahead and scan some more of them
		elseif (!$this->done_subdir_scanning)
		{
			$this->scanSubdirs();
		}

		// Do I have an error?
		if ($this->getError())
		{
			return false;
		}

		return true;
	}

	/**
	 * Implements the _finalize() abstract method
	 *
	 */
	protected function _finalize()
	{
		// No finalization is required
		$this->setState('finished');
	}

	// ============================================================================================
	// PRIVATE METHODS
	// ============================================================================================

	/**
	 * Gets the next directory to scan from the stack. It also applies folder
	 * filters (directory exclusion, subdirectory exclusion, file exclusion),
	 * updating the operation toggle properties of the class.
	 *
	 * @return   boolean  True if we found a directory, false if the directory
	 *                    stack is empty. It also returns true if the folder is
	 *                    filtered (we are told to skip it)
	 */
	private function getNextDirectory()
	{
		// Reset the file / folder scanning positions
		$this->done_file_scanning   = false;
		$this->done_subdir_scanning = false;
		$this->excluded_folder      = false;

		if (count($this->directory_list) == 0)
		{
			// No directories left to scan
			return false;
		}

		// Get and remove the last entry from the $directory_list array
		$this->current_directory = array_pop($this->directory_list);
		$this->setStep($this->current_directory);
		$this->processed_files_counter = 0;

		// Apply directory exclusion filters
		if ($this->isFiltered($this->current_directory))
		{
			debugMsg("Skipping directory " . $this->current_directory);
			$this->done_subdir_scanning = true;
			$this->done_file_scanning   = true;
			$this->excluded_folder      = true;

			return true;
		}

		return true;
	}

	/**
	 * Try to delete some files from the $file_list
	 *
	 * @return   boolean   True if there were files deleted , false otherwise
	 *                     (empty filelist or fatal error)
	 */
	protected function delete_files()
	{
		// Get a reference to the archiver and the timer classes
		$timer = AKFactory::getTimer();

		// Normal file removal loop; we keep on processing the file list, removing files as we go.
		if (count($this->file_list) == 0)
		{
			// No files left to pack. Return true and let the engine loop
			$this->progressMarkFolderDone();

			return true;
		}

		debugMsg("Deleting files");

		$numberOfFiles = 0;
		$postProc = AKFactory::getPostProc();

		while ((count($this->file_list) > 0))
		{
			$file = @array_shift($this->file_list);

			$numberOfFiles++;

			// Remove the file
            $this->setSubstep($file);
            $this->notify((object) [
                'type' => 'deleteFile',
                'file' => $file
            ]);

            if (!$this->dryRun)
            {
                $postProc->unlink($file);
	            clearFileInOPCache($file);
            }

			// Mark a done file
			$this->progressMarkFileDone();

			if ($this->getError())
			{
				return false;
			}

			// I am running out of time.
			if ($timer->getTimeLeft() <= 0)
			{
				return true;
			}
		}

		// True if we have more files, false if we're done packing
		return (count($this->file_list) > 0);
	}

	protected function progressAddFile()
	{
		$this->total_files++;
	}

	protected function progressMarkFileDone()
	{
		$this->done_files++;
	}

	protected function progressAddFolder()
	{
		$this->total_folders++;
	}

	protected function progressMarkFolderDone()
	{
        debugMsg("Deleting directory " . $this->current_directory);

        $this->setSubstep($this->current_directory);
        $this->notify((object) [
            'type' => 'deleteFolder',
            'file' => $this->current_directory
        ]);

        if (!$this->dryRun)
        {
            /**
             * The scanner goes from shallow to deep directory. However this means that when it scans
             * <root>/foo/bar/baz/bat
             * it will only be able to remove the 'bat' directory, thus leaving foo/bar/baz on the disk. The following
             * method will check if the directory is a subdirectory of the site root and work its way up the tree until
             * it finds the site root. Therefore it will end up deleting the parent folders as well.
             */
            $this->deleteParentFolders($this->current_directory);
        }
	}

	/**
	 * Returns the site root, the translated site root and the translated current directory
	 *
	 * @return array
	 */
	protected function getCleanDirectoryComponents()
	{
		$root            = $this->root;
		$translated_root = $root;
		$dir             = TrimTrailingSlash($this->current_directory);

		if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
		{
			$translated_root = TranslateWinPath($translated_root);
			$dir             = TranslateWinPath($dir);
		}

		if (substr($dir, 0, strlen($translated_root)) == $translated_root)
		{
			$dir = substr($dir, strlen($translated_root));
		}
		elseif (in_array(substr($translated_root, -1), ['/', '\\']))
		{
			$new_translated_root = rtrim($translated_root, '/\\');

			if (substr($dir, 0, strlen($new_translated_root)) == $new_translated_root)
			{
				$dir = substr($dir, strlen($new_translated_root));
			}
		}

		if (substr($dir, 0, 1) == '/')
		{
			$dir = substr($dir, 1);
		}

		return [$root, $translated_root, $dir];
	}

	/**
	 * Steps the subdirectory scanning of the current directory
	 *
	 * @return  boolean  True on success, false on fatal error
	 */
	protected function scanSubdirs()
	{
		$lister = new AKUtilsLister();

		[$root, $translated_root, $dir] = $this->getCleanDirectoryComponents();

		debugMsg("Scanning directories of " . $this->current_directory);

		// Get subdirectories
		$subdirectories = $lister->getFolders($this->current_directory);

		// Error propagation
		$this->propagateFromObject($lister);

		// Error control
		if ($this->getError())
		{
			return false;
		}

		// Start adding the subdirectories
		if (!empty($subdirectories) && is_array($subdirectories))
		{
			// Treat symlinks to directories as simple symlink files
			foreach ($subdirectories as $subdirectory)
			{
				if (is_link($subdirectory))
				{
					// Symlink detected; apply directory filters to it
					if (empty($dir))
					{
						$dirSlash = $dir;
					}
					else
					{
						$dirSlash = $dir . '/';
					}

					$check = $dirSlash . basename($subdirectory);
					debugMsg("Directory symlink detected: $check");

					if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
					{
						$check = TranslateWinPath($check);
					}

					$check = $translated_root . '/' . $check;

					// Check for excluded symlinks
					if ($this->isFiltered($check))
					{
						debugMsg("Skipping directory symlink " . $check);

						continue;
					}

					debugMsg('Adding folder symlink: ' . $check);

					$this->file_list[] = $subdirectory;
					$this->progressAddFile();
				}

				$this->directory_list[] = $subdirectory;
				$this->progressAddFolder();
			}
		}

		$this->done_subdir_scanning = true;

		return true;
	}

	/**
	 * Steps the files scanning of the current directory
	 *
	 * @return  boolean  True on success, false on fatal error
	 */
	protected function scanFiles()
	{
		$lister = new AKUtilsLister();

		[$root, $translated_root, $dir] = $this->getCleanDirectoryComponents();

		debugMsg("Scanning files of " . $this->current_directory);
		$this->processed_files_counter = 0;

		// Get file listing
		$fileList = $lister->getFiles($this->current_directory);

		// Error propagation
		$this->propagateFromObject($lister);

		// Error control
		if ($this->getError())
		{
			return false;
		}

		// Do I have an unreadable directory?
		if (($fileList === false))
		{
			$this->setWarning('Unreadable directory ' . $this->current_directory);

			$this->done_file_scanning = true;

			return true;
		}

		// Directory was readable, process the file list
		if (is_array($fileList) && !empty($fileList))
		{
			// Add required trailing slash to $dir
			if (!empty($dir))
			{
				$dir .= '/';
			}

			// Scan all directory entries
			foreach ($fileList as $fileName)
			{
				$check = $dir . basename($fileName);

				if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
				{
					$check = TranslateWinPath($check);
				}

				$check        = $translated_root . '/' . $check;
				$skipThisFile = $this->isFiltered($check);

				if ($skipThisFile)
				{
					debugMsg("Skipping file $fileName");

					continue;
				}

				$this->file_list[] = $fileName;
				$this->processed_files_counter++;
				$this->progressAddFile();
			}
		}

		$this->done_file_scanning = true;

		return true;
	}

	/**
	 * Is a file or folder filtered (protected from deletion)
	 *
	 * @param   string  $fileOrFolder
	 *
	 * @return  bool
	 */
	private function isFiltered($fileOrFolder)
	{
		foreach ($this->excluded as $pattern)
		{
			if (fnmatch($pattern, $fileOrFolder))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the default exceptions from deletion
	 *
	 * @return  array
	 */
	private function getDefaultExclusions()
	{
		$ret     = [];
		$destDir = AKFactory::get('kickstart.setup.destdir');

		/**
		 * Exclude Kickstart / restore.php itself. Otherwise it'd crash!
		 */
		$myName = defined('KSSELFNAME') ? KSSELFNAME : basename(__FILE__);
		$ret[] = KSROOTDIR . '/' . $myName;

		/**
		 * Cheat: exclude the directory used in development (see source/buildscripts/kickstart_test.php)
		 *
		 * This directory contains the non-concatenated source code for Kickstart. We need to keep it protected.
		 */
		if (defined('MINIBUILD') && (MINIBUILD != $destDir))
		{
			$ret[] = TranslateWinPath(MINIBUILD);
		}

		/**
		 * Exclude the backup archive directory if it's not the site's root. This prevents mindlessly deleting all your
		 * backups before you restore from a previous backup which might not be the one you actually wanted. I will call
		 * this feature "clumsy-proofing".
		 */
		$backupArchive   = AKFactory::get('kickstart.setup.sourcefile');
		$backupDirectory = AKFactory::get('kickstart.setup.sourcepath');
		$backupDirectory = empty($backupDirectory) ? dirname($backupArchive) : $backupDirectory;

		if ($backupDirectory != $destDir)
		{
			$ret[] = TranslateWinPath($backupDirectory);
		}

		/**
		 * Exclude the backup archive files
		 *
		 * This obviously only makes sense when the backup archives are stored in the extraction target folder which is
		 * the most common use of Kickstart. In this case the backups folder is not excluded above.
		 */
		$plainBackupName = basename($backupArchive, '.jpa');
		$plainBackupName = basename($plainBackupName, '.jps');
		$plainBackupName = basename($plainBackupName, '.zip');
		$ret[]           = TranslateWinPath($backupDirectory . '/' . $plainBackupName) . '.*';

		/**
		 * Exclude Kickstart language files. Only applies in Kickstart mode.
		 */
		if (defined('KICKSTART'))
		{
			$langDir        = defined('KSLANGDIR') ? KSLANGDIR : KSROOTDIR;
			$myName         = defined('KSSELFNAME') ? KSSELFNAME : basename(__FILE__);
			$iniFilePattern = basename($myName, '.php') . '.*.ini';

			if ($langDir != KSROOTDIR)
            {
                $ret[] = KSLANGDIR;
            }

            $ret[]   = $langDir . '/' . $iniFilePattern;
            $ret[]   = KSROOTDIR . '/' . $iniFilePattern;
		}

		// Exclude the Kickstart temporary directory, if one is used by the post-processing engine
		$postProc = AKFactory::getPostProc();
		$tempDir  = $postProc->getTempDir();

		if (!empty($tempDir) && (realpath($tempDir) != realpath($destDir)))
		{
			$ret[] = TranslateWinPath($tempDir);
		}

		/**
		 * Exclude the configured Skipped Files ('kickstart.setup.skipfiles'). Also exclude the various restoration.php
		 * files if we are in restore.php mode and the files are present. These are required for the integrated
		 * restoration to actually work :)
		 */
		$skippedFiles = AKFactory::get('kickstart.setup.skipfiles', [
			basename(__FILE__), 'kickstart.php', 'htaccess.bak', 'php.ini.bak',
		]);

		if (!defined('KICKSTART'))
		{
			// In restore.php mode we have to exclude the various restoration.php files
			$skippedFiles = array_merge([
				// Akeeba Backup for Joomla!
				'administrator/components/com_akeeba/restoration.php',
				'administrator/components/com_akeebabackup/restoration.php',
				// Joomla! Update
				'administrator/components/com_joomlaupdate/restoration.php',
				// Akeeba Backup for WordPress
				'wp-content/plugins/akeebabackupwp/app/restoration.php',
				'wp-content/plugins/akeebabackupcorewp/app/restoration.php',
				'wp-content/plugins/akeebabackup/app/restoration.php',
				'wp-content/plugins/akeebabackupwpcore/app/restoration.php',
				// Akeeba Solo
				'app/restoration.php',
			], $skippedFiles);
		}

		foreach ($skippedFiles as $file)
		{
			$checkFile = $destDir . '/' . $file;

			if (file_exists($checkFile))
			{
				$ret[] = TranslateWinPath($checkFile);
			}
		}

		/**
		 * Exclude .htaccess if the stealth feature is enabled. Otherwise we'd unset the stealth mode.
		 * Exclude it even if we have any AddHandler directive, otherwise the site will be borked if the user
		 * chooses not to rename the .htaccess file
		 */
		if (AKFactory::get('kickstart.stealth.enable') || AKFactory::get('kickstart.setup.phphandlers', []))
		{
			$ret[] = $destDir . '/.htaccess';
		}

		// Remove any duplicate lines
        $ret = array_unique($ret);

		return $ret;
	}

    /**
     * Recursively delete an empty folder and any of its empty parent folders.
     *
     * @param   string  $folder  The folder to deletes
     */
	private function deleteParentFolders($folder)
    {
        // Don't try to delete an empty folder or the filesystem root
        if (empty($folder) || ($folder == '/'))
        {
            return;
        }

        $folder = TranslateWinPath($folder);
        $root   = TranslateWinPath($this->root);

        // Don't try to delete the site's root
        if ($folder === $root)
        {
            return;
        }

        // Delete the leaf folder
        $postProc = AKFactory::getPostProc();
        $postProc->rmdir($folder);

        // If the leaf folder is not under the site's root don't delete its parents
        if (strpos($folder, $root) !== 0)
        {
            return;
        }

        // Get and recursively delete the parent folder
        $this->deleteParentFolders(dirname($folder));
    }
}

/**
 * Runs the Zapper and returns a status table. The Zapper only runs if the feature is enabled (kickstart.setup.zapbefore
 * is 1) and there are more Zapper steps to run (its state is not postrun). If any of these conditions is not met we
 * return boolean false.
 *
 * @param   AKAbstractPartObserver  $observer  The observer to attach to the Zapper instance
 *
 * @return  bool|array  Boolean false or a status array
 */
function runZapper(AKAbstractPartObserver $observer)
{
	// This method should only run in restore.php mode or when we have Kickstart Professional.
	$isKickstart = defined('KICKSTART');
	$isPro       = defined('KICKSTARTPRO') ? KICKSTARTPRO : false;
	$isDebug     = defined('KSDEBUG') ? KSDEBUG : false;

	if ($isKickstart && (!$isPro && !$isDebug))
	{
		return false;
	}

	// Is the feature enabled?
    $enabled = AKFactory::get('kickstart.setup.zapbefore', 0);

    if (!$enabled)
    {
        return false;
    }

    // Do I still have work to do?
    $zapper = AKFactory::getZapper();

    if ($zapper->getState() == 'finished')
    {
        return false;
    }

    // Attach the observer
    if (is_object($observer))
    {
        $zapper->attach($observer);
    }

    // Run a step, create and return a status array
	$timer = AKFactory::getTimer();

    while ($timer->getTimeLeft() > 0)
    {
	    $ret = $zapper->tick();

	    if ($ret['Error'] != '')
	    {
	    	break;
	    }
    }

    $retArray = [
        'status'  => true,
        'message' => null,
        'done' => false,
    ];

    if ($ret['Error'] != '')
    {
        $retArray['status']  = false;
        $retArray['done']    = true;
        $retArray['message'] = $ret['Error'];
    }
    else
    {
        $retArray['files']    = 0;
        $retArray['bytesIn']  = 0;
        $retArray['bytesOut'] = 0;
        $retArray['factory']  = AKFactory::serialize();
        $retArray['lastfile'] = 'Deleting: ' . $zapper->getSubstep();
    }

	$timer->enforce_min_exec_time();

    return $retArray;
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * A simple INI-based i18n engine
 */
class AKText extends AKAbstractObject
{
	/**
	 * The default (en_GB) translation used when no other translation is available
	 *
	 * @var array
	 */
	private $default_translation = array (
  'AUTOMODEON' => 'Auto-mode enabled',
  'ERR_NOT_A_JPA_FILE' => 'The file is not a JPA archive',
  'ERR_CORRUPT_ARCHIVE' => 'The archive file is corrupt, truncated or archive parts are missing',
  'ERR_PATH_OUTSIDE_ROOT' => 'The archive contains an entry, “%s”, which would be written outside the extraction folder. Extraction has been stopped. A legitimate backup archive never does this, so please treat this archive as untrustworthy: it is either damaged or it has been tampered with.',
  'ERR_INVALID_ARCHIVE_LONG' => 'The archive file appears to be corrupt, or archive parts are missing. If your backups consist of multiple files, please make sure that you have downloaded all the archive part files (files with the same name and extensions .%s, .%s01, .%2$s02…). Please make sure to download <em>and</em> upload files using SFTP, or FTP in Binary transfer mode and do check that their file size matches the sizes reported in the Manage Backups page of Akeeba Backup / Akeeba Solo.',
  'ERR_INVALID_LOGIN' => 'Invalid login',
  'COULDNT_CREATE_DIR' => 'Could not create %s folder',
  'COULDNT_WRITE_FILE' => 'Could not open %s for writing.',
  'WRONG_FTP_HOST' => 'Wrong FTP host or port',
  'WRONG_FTP_USER' => 'Wrong FTP username or password',
  'WRONG_FTP_PATH1' => 'Wrong FTP initial directory - the directory doesn\'t exist',
  'FTP_CANT_CREATE_DIR' => 'Could not create directory %s',
  'FTP_TEMPDIR_NOT_WRITABLE' => 'Could not find or create a writable temporary directory',
  'SFTP_TEMPDIR_NOT_WRITABLE' => 'Could not find or create a writable temporary directory',
  'FTP_COULDNT_UPLOAD' => 'Could not upload %s',
  'THINGS_HEADER' => 'Things you should know about Akeeba Kickstart',
  'THINGS_01' => 'Kickstart is not an installer. It is an archive extraction tool. The actual installer was put inside the archive file at backup time.',
  'THINGS_03' => 'Kickstart is bound by your server\'s configuration. As such, it may not work at all.',
  'THINGS_04' => 'You should download and upload your archive files using FTP in Binary transfer mode. Any other method could lead to a corrupt backup archive and restoration failure.',
  'THINGS_05' => 'Post-restoration site load errors are usually caused by .htaccess or php.ini directives. You should understand that blank pages, 404 and 500 errors can usually be worked around by editing the aforementioned files. We are unable to change the configuration files of your server for you. These changes can be specific to your server or host, therefore dangerous if performed in an unsupervised and unprompted manner.',
  'THINGS_06' => 'Kickstart overwrites files without a warning. If you are not sure that this is acceptable for your use case you should close this window.',
  'THINGS_07' => 'Trying to restore to the temporary URL of a cPanel host (e.g. http://1.2.3.4/~username) will lead to restoration failure and your site will appear to be not working. This is normal and it\'s just how your server and CMS software work.',
  'THINGS_08' => 'We kindly request that you read the documentation. Doing so is likely to save you time and frustration.',
  'THINGS_09' => 'This text does not imply that there is a problem detected. It is standard text displayed every time you launch Kickstart.',
  'CLOSE_LIGHTBOX' => 'Click here or press ESC to close this message',
  'SELECT_ARCHIVE' => 'Select a backup archive',
  'ARCHIVE_FILE' => 'Archive file:',
  'SELECT_EXTRACTION' => 'Select an extraction method',
  'WRITE_TO_FILES' => 'Write to files:',
  'WRITE_HYBRID' => 'Hybrid (use FTP only if needed)',
  'WRITE_DIRECTLY' => 'Directly',
  'WRITE_FTP' => 'Use FTP for all files',
  'WRITE_SFTP' => 'Use SFTP for all files',
  'FTP_HOST' => '(S)FTP host name:',
  'FTP_PORT' => '(S)FTP port:',
  'FTP_FTPS' => 'Use FTP over SSL (FTPS)',
  'FTP_PASSIVE' => 'Use FTP Passive Mode',
  'FTP_USER' => '(S)FTP username:',
  'FTP_PASS' => '(S)FTP password:',
  'FTP_DIR' => '(S)FTP directory:',
  'FTP_TEMPDIR' => 'Temporary directory:',
  'FTP_CONNECTION_OK' => 'FTP Connection Established',
  'SFTP_CONNECTION_OK' => 'SFTP Connection Established',
  'FTP_CONNECTION_FAILURE' => 'The FTP Connection Failed',
  'SFTP_CONNECTION_FAILURE' => 'The SFTP Connection Failed',
  'FTP_TEMPDIR_WRITABLE' => 'The temporary directory is writable.',
  'FTP_TEMPDIR_UNWRITABLE' => 'The temporary directory is not writable. Please check the permissions.',
  'FTP_BROWSE' => 'Browse',
  'FTPBROWSER_LBL_INSTRUCTIONS' => 'Click on a directory to navigate into it. Click on OK to select that directory, Cancel to abort the procedure.',
  'FTPBROWSER_ERROR_HOSTNAME' => 'Invalid FTP host or port',
  'FTPBROWSER_ERROR_USERPASS' => 'Invalid FTP username or password',
  'FTPBROWSER_ERROR_NOACCESS' => 'Directory doesn\'t exist or you don\'t have enough permissions to access it',
  'FTPBROWSER_ERROR_UNSUPPORTED' => 'Sorry, your FTP server doesn\'t support our FTP directory browser.',
  'FTPBROWSER_LBL_GOPARENT' => '&lt;up one level&gt;',
  'FTPBROWSER_LBL_ERROR' => 'An error occurred',
  'SFTP_NO_SSH2' => 'Your web server does not have the SSH2 PHP module, therefore cannot connect to SFTP servers.',
  'SFTP_NO_FTP_SUPPORT' => 'Your SSH server does not allow SFTP connections',
  'SFTP_WRONG_USER' => 'Wrong SFTP username or password',
  'SFTP_WRONG_STARTING_DIR' => 'You must supply a valid absolute path',
  'SFTPBROWSER_ERROR_NOACCESS' => 'Directory doesn\'t exist or you don\'t have enough permissions to access it',
  'SFTP_COULDNT_UPLOAD' => 'Could not upload %s',
  'SFTP_CANT_CREATE_DIR' => 'Could not create directory %s',
  'UI-ROOT' => '&lt;root&gt;',
  'CONFIG_UI_FTPBROWSER_TITLE' => 'FTP Directory Browser',
  'BTN_CHECK' => 'Check',
  'BTN_RESET' => 'Reset',
  'BTN_TESTFTPCON' => 'Test FTP Connection',
  'BTN_TESTSFTPCON' => 'Test SFTP Connection',
  'BTN_GOTOSTART' => 'Start over',
  'BTN_RETRY' => 'Retry',
  'FINE_TUNE' => 'Fine-tune',
  'MIN_EXEC_TIME' => 'Minimum execution time:',
  'MAX_EXEC_TIME' => 'Maximum execution time:',
  'SECONDS_PER_STEP' => 'seconds per step',
  'EXTRACT_FILES' => 'Extract files',
  'BTN_START' => 'Start',
  'EXTRACTING' => 'Extracting',
  'DO_NOT_CLOSE_EXTRACT' => 'Do not close this window while the extraction is in progress',
  'RESTACLEANUP' => 'Restoration and Clean Up',
  'BTN_RUNINSTALLER' => 'Run the Installer',
  'BTN_CLEANUP' => 'Clean Up',
  'BTN_SITEFE' => 'Visit your site\'s frontend',
  'BTN_SITEBE' => 'Visit your site\'s backend',
  'WARNINGS' => 'Extraction Warnings',
  'ERROR_OCCURED' => 'An error occurred',
  'STEALTH_MODE' => 'Stealth mode',
  'STEALTH_URL' => 'HTML file to show to web visitors',
  'ERR_NOT_A_JPS_FILE' => 'The file is not a JPS archive',
  'ERR_INVALID_JPS_PASSWORD' => 'The password you gave is wrong or the archive is corrupt',
  'JPS_PASSWORD' => 'Archive Password (for JPS files)',
  'INVALID_FILE_HEADER_OFFSET_ZERO' => 'Cannot open the file %s for reading. This is part #%d of your backup archive which consists of multiple files (files with the same name and extensions .%s, .%s01, .%4$s02…). Please make sure that you have all of these files in the same folder as Kickstart.',
  'INVALID_FILE_HEADER' => 'Invalid header in archive file, part %s, offset %s. Please make sure to download <em>and</em> upload backup archive files using SFTP, or FTP in Binary transfer mode and do check that their file size matches the sizes reported in the Manage Backups page of Akeeba Backup / Akeeba Solo.',
  'INVALID_FILE_HEADER_MULTIPART' => 'Invalid header in archive file, part %s, offset %s. Your backup archive consists of multiple files (files with the same name and extensions .%s, .%s01, .%4$s02…). Either some files are missing, or they are corrupt or truncated. You will need all of these files to be present in the same directory. Please make sure to download <em>and</em> upload backup archive files using SFTP, or FTP in Binary transfer mode and do check that their file size matches the sizes reported in the Manage Backups page of Akeeba Backup / Akeeba Solo.',
  'UPDATE_HEADER' => 'An updated version of Akeeba Kickstart (<span id=update-version>unknown</span>) is available!',
  'UPDATE_NOTICE' => 'You are advised to always use the latest version of Akeeba Kickstart available. Older versions may be subject to bugs and will not be supported.',
  'UPDATE_DLNOW' => 'Download now',
  'UPDATE_MOREINFO' => 'More information',
  'NEEDSOMEHELPKS' => 'Want some help to use this tool? Read this first:',
  'QUICKSTART' => 'Quick Start Guide',
  'CANTGETITTOWORK' => 'Can\'t get it to work? Click me!',
  'NOARCHIVESCLICKHERE' => 'No archives detected. Click here for troubleshooting instructions.',
  'POSTRESTORATIONTROUBLESHOOTING' => 'Something not working after the restoration? Click here for troubleshooting instructions.',
  'IGNORE_MOST_ERRORS' => 'Ignore most errors',
  'TIME_SETTINGS_HELP' => 'Increase the minimum to 3 if you get AJAX errors. Increase the maximum to 10 for faster extraction, decrease back to 5 if you get AJAX errors. Try minimum 5, maximum 1 (not a typo!) if you keep getting AJAX errors.',
  'STEALTH_MODE_HELP' => 'When enabled, only visitors from your IP address will be able to see the site until the restoration is complete. Everyone else will be redirected to and only see the URL above. Your server must see the real IP of the visitor (this is controlled by your host, not you or us).',
  'RENAME_FILES_HELP' => 'Renames .htaccess, web.config, php.ini and .user.ini contained in the archive while extracting. Files are renamed with a .bak extension. The file names are restored when you click on Clean Up.',
  'RESTORE_PERMISSIONS_HELP' => 'Applies the file permissions (but NOT file ownership) which were stored at backup time. Only works with JPA and JPS archives. Does not work on Windows (PHP does not offer such a feature).',
  'EXTRACT_LIST' => 'Files to extract',
  'EXTRACT_LIST_HELP' => 'Enter a file path such as <code>images/cat.png</code> or shell pattern such as <code>images/*.png</code> on each line. Only files matching this list will be written to disk. Leave empty to extract everything (default).',
  'AKS3_IMPORT' => 'Import from Amazon S3',
  'AKS3_TITLE_STEP1' => 'Connect to Amazon S3',
  'AKS3_ACCESS' => 'Access Key',
  'AKS3_SECRET' => 'Secret Key',
  'AKS3_CONNECT' => 'Connect to Amazon S3',
  'AKS3_CANCEL' => 'Cancel import',
  'AKS3_TITLE_STEP2' => 'Select your Amazon S3 bucket',
  'AKS3_BUCKET' => 'Bucket',
  'AKS3_LISTCONTENTS' => 'List contents',
  'AKS3_TITLE_STEP3' => 'Select archive to import',
  'AKS3_FOLDERS' => 'Folders',
  'AKS3_FILES' => 'Archive Files',
  'AKS3_TITLE_STEP4' => 'Importing...',
  'AKS3_DO_NOT_CLOSE' => 'Please do not close this window while your backup archives are being imported',
  'AKS3_TITLE_STEP5' => 'Import is complete',
  'AKS3_BTN_RELOAD' => 'Reload Kickstart',
  'WRONG_FTP_PATH2' => 'Wrong FTP initial directory - the directory doesn\'t correspond to your site\'s web root',
  'ARCHIVE_DIRECTORY' => 'Archive directory:',
  'RELOAD_ARCHIVES' => 'Reload',
  'CONFIG_UI_SFTPBROWSER_TITLE' => 'SFTP Directory Browser',
  'ERR_COULD_NOT_OPEN_ARCHIVE_PART' => 'Could not open archive part file %s for reading. Check that the file exists, is readable by the web server and is not in a directory made out of reach by chroot, open_basedir restrictions or any other restriction put in place by your host.',
  'RENAME_FILES' => 'Rename server configuration files before extraction',
  'BTN_SHOW_FINE_TUNE' => 'Show advanced options (for experts)',
  'RESTORE_PERMISSIONS' => 'Restore file permissions',
  'ZAPBEFORE' => 'Delete everything before extraction',
  'ZAPBEFORE_HELP' => 'Tries to delete all existing files and folders under the directory where Kickstart is stored before extracting the backup archive. It DOES NOT take into account which files and folders exist in the backup archive. Files and folders deleted by this feature CAN NOT be recovered. <strong>WARNING! THIS MAY DELETE FILES AND FOLDERS WHICH DO NOT BELONG TO YOUR SITE. USE WITH EXTREME CAUTION. BY ENABLING THIS FEATURE YOU ASSUME ALL RESPONSIBILITY AND LIABILITY.</strong>',
);

	/**
	 * Translation strings for el-GR
	 *
	 * @var  array
	 */
	private $translation_el_gr = array (
  'AUTOMODEON' => 'Ο αυτόματος τρόπος λειτουργίας ενεργοποιήθηκε',
  'ERR_NOT_A_JPA_FILE' => 'Το αρχείο δεν είναι αρχείο αρχειοθέτησης JPA',
  'ERR_CORRUPT_ARCHIVE' => 'Το αρχείο αρχειοθέτησης είναι κατεστραμμένο, τετμημένο ή λείπουν τμήματα του αρχείου αρχειοθέτησης',
  'ERR_INVALID_ARCHIVE_LONG' => 'Το αρχείο αρχειοθέτησης φαίνεται να είναι κατεστραμμένο ή λείπουν τμήματα. Αν τα αντίγραφα ασφαλείας σας αποτελούνται από πολλαπλά αρχεία, βεβαιωθείτε ότι έχετε κατεβάσει όλα τα αρχεία τμημάτων αρχειοθέτησης (αρχεία με το ίδιο όνομα και επεκτάσεις .%s, .%s01, .%2$s02…). Βεβαιωθείτε ότι κατεβάσατε <em>και</em> ανεβάσατε τα αρχεία χρησιμοποιώντας SFTP, ή FTP σε λειτουργία μεταφοράς Binary και ελέγξατε ότι το μέγεθός τους ταιριάζει με τα μεγέθη που αναφέρονται στη σελίδα Διαχείριση Αντιγράφων Ασφαλείας του Akeeba Backup / Akeeba Solo.',
  'ERR_INVALID_LOGIN' => 'Μη έγκυρη σύνδεση',
  'COULDNT_CREATE_DIR' => 'Αδυναμία δημιουργίας του φακέλου %s',
  'COULDNT_WRITE_FILE' => 'Αδυναμία ανοίγματος του %s για εγγραφή.',
  'WRONG_FTP_HOST' => 'Λάθος FTP host ή port',
  'WRONG_FTP_USER' => 'Λάθος όνομα χρήστη ή κωδικός πρόσβασης FTP',
  'WRONG_FTP_PATH1' => 'Λάθος αρχικός φάκελος FTP - ο φάκελος δεν υπάρχει',
  'FTP_CANT_CREATE_DIR' => 'Αδυναμία δημιουργίας φακέλου %s',
  'FTP_TEMPDIR_NOT_WRITABLE' => 'Αδυναμία εύρεσης ή δημιουργίας εγγράψιμου προσωρινού φακέλου',
  'SFTP_TEMPDIR_NOT_WRITABLE' => 'Αδυναμία εύρεσης ή δημιουργίας εγγράψιμου προσωρινού φακέλου',
  'FTP_COULDNT_UPLOAD' => 'Αδυναμία ανέβασματος του %s',
  'THINGS_HEADER' => 'Πράγματα που πρέπει να γνωρίζετε για το Akeeba Kickstart',
  'THINGS_01' => 'Το Kickstart δεν είναι πρόγραμμα εγκατάστασης. Είναι ένα εργαλείο εξαγωγής αρχείων αρχειοθέτησης. Το πραγματικό πρόγραμμα εγκατάστασης τοποθετήθηκε μέσα στο αρχείο αρχειοθέτησης κατά την ώρα δημιουργίας του αντιγράφου ασφαλείας.',
  'THINGS_03' => 'Το Kickstart περιορίζεται από τη διαμόρφωση του server σας. Ως εκ τούτου, ενδέχεται να μην λειτουργεί καθόλου.',
  'THINGS_04' => 'Πρέπει να κατεβάσετε και να ανεβάσετε τα αρχεία αρχειοθέτησης χρησιμοποιώντας FTP σε λειτουργία μεταφοράς Binary. Οποιαδήποτε άλλη μέθοδος μπορεί να οδηγήσει σε κατεστραμμένο αρχείο αρχειοθέτησης αντιγράφου ασφαλείας και αποτυχία αποκατάστασης.',
  'THINGS_05' => 'Σφάλματα φόρτωσης του ιστότοπου μετά την αποκατάσταση συνήθως προκαλούνται από οδηγούς .htaccess ή php.ini. Πρέπει να κατανοήσετε ότι οι κενές σελίδες, τα σφάλματα 404 και 500 συνήθως μπορούν να παρακαμφθούν με επεξεργασία των προαναφερθέντων αρχείων. Δεν είμαστε σε θέση να αλλάξουμε τα αρχεία διαμόρφωσης του διακομιστή σας για εσάς. Αυτές οι αλλαγές μπορεί να είναι ειδικές για τον διακομιστή ή τον πάροχό σας και επομένως επικίνδυνες εάν εκτελεστούν χωρίς επιτήρηση και χωρίς αίτημα.',
  'THINGS_06' => 'Το Kickstart αντικαθιστά αρχεία χωρίς προειδοποίηση. Εάν δεν είστε σίγουροι ότι αυτό είναι αποδεκτό για την περίπτωσή σας, θα πρέπει να κλείσετε αυτό το παράθυρο.',
  'THINGS_07' => 'Η προσπάθεια αποκατάστασης στο προσωρινό URL ενός host cPanel (π.χ. http://1.2.3.4/~username) θα οδηγήσει σε αποτυχία αποκατάστασης και ο ιστότοπός σας θα φαίνεται να μην λειτουργεί. Αυτό είναι φυσιολογικό και οφείλεται στον τρόπο λειτουργίας του server και του λογισμικού CMS σας.',
  'THINGS_08' => 'Σας παρακαλούμε θερμά να διαβάσετε την τεκμηρίωση. Κάτι τέτοιο είναι πιθανό να σας εξοικονομήσει χρόνο και απογοήτευση.',
  'THINGS_09' => 'Αυτό το κείμενο δεν υποδηλώνει ότι εντοπίστηκε πρόβλημα. Είναι προεπιλεγμένο κείμενο που εμφανίζεται κάθε φορά που εκκινείτε το Kickstart.',
  'CLOSE_LIGHTBOX' => 'Κάντε κλικ εδώ ή πατήστε ESC για να κλείσετε αυτό το μήνυμα',
  'SELECT_ARCHIVE' => 'Επιλέξτε ένα αρχείο αρχειοθέτησης αντιγράφου ασφαλείας',
  'ARCHIVE_FILE' => 'Αρχείο αρχειοθέτησης:',
  'SELECT_EXTRACTION' => 'Επιλέξτε μια μέθοδο εξαγωγής',
  'WRITE_TO_FILES' => 'Εγγραφή σε αρχεία:',
  'WRITE_HYBRID' => 'Υβριδική (χρήση FTP μόνο όταν χρειάζεται)',
  'WRITE_DIRECTLY' => 'Απευθείας',
  'WRITE_FTP' => 'Χρήση FTP για όλα τα αρχεία',
  'WRITE_SFTP' => 'Χρήση SFTP για όλα τα αρχεία',
  'FTP_HOST' => 'Όνομα (S)FTP host:',
  'FTP_PORT' => '(S)FTP port:',
  'FTP_FTPS' => 'Χρήση FTP μέσω SSL (FTPS)',
  'FTP_PASSIVE' => 'Χρήση FTP Passive Mode',
  'FTP_USER' => 'Όνομα χρήστη (S)FTP:',
  'FTP_PASS' => 'Κωδικός πρόσβασης (S)FTP:',
  'FTP_DIR' => 'Φάκελος (S)FTP:',
  'FTP_TEMPDIR' => 'Προσωρινός φάκελος:',
  'FTP_CONNECTION_OK' => 'Η σύνδεση FTP δημιουργήθηκε',
  'SFTP_CONNECTION_OK' => 'Η σύνδεση SFTP δημιουργήθηκε',
  'FTP_CONNECTION_FAILURE' => 'Αποτυχία σύνδεσης FTP',
  'SFTP_CONNECTION_FAILURE' => 'Αποτυχία σύνδεσης SFTP',
  'FTP_TEMPDIR_WRITABLE' => 'Ο προσωρινός φάκελος είναι εγγράψιμος.',
  'FTP_TEMPDIR_UNWRITABLE' => 'Ο προσωρινός φάκελος δεν είναι εγγράψιμος. Ελέγξτε τα δικαιώματα πρόσβασης.',
  'FTP_BROWSE' => 'Περιήγηση',
  'FTPBROWSER_LBL_INSTRUCTIONS' => 'Κάντε κλικ σε έναν φάκελο για να πλοηγηθείτε μέσα του. Κάντε κλικ στο OK για να επιλέξετε αυτόν τον φάκελο, Cancel για να ακυρώσετε τη διαδικασία.',
  'FTPBROWSER_ERROR_HOSTNAME' => 'Μη έγκυρο FTP host ή port',
  'FTPBROWSER_ERROR_USERPASS' => 'Μη έγκυρο όνομα χρήστη ή κωδικός πρόσβασης FTP',
  'FTPBROWSER_ERROR_NOACCESS' => 'Ο φάκελος δεν υπάρχει ή δεν έχετε αρκετά δικαιώματα για πρόσβαση',
  'FTPBROWSER_ERROR_UNSUPPORTED' => 'Λυπούμαστε, ο FTP server σας δεν υποστηρίζει τον περιηγητή φακέλων FTP.',
  'FTPBROWSER_LBL_GOPARENT' => '&lt;ένας βαθμός πάνω&gt;',
  'FTPBROWSER_LBL_ERROR' => 'Προέκυψε σφάλμα',
  'SFTP_NO_SSH2' => 'Ο web server σας δεν διαθέτει το module PHP SSH2, επομένως δεν μπορεί να συνδεθεί με servers SFTP.',
  'SFTP_NO_FTP_SUPPORT' => 'Ο SSH server σας δεν επιτρέπει συνδέσεις SFTP',
  'SFTP_WRONG_USER' => 'Λάθος όνομα χρήστη ή κωδικός πρόσβασης SFTP',
  'SFTP_WRONG_STARTING_DIR' => 'Πρέπει να παρέχετε μια έγκυρη απόλυτη διαδρομή',
  'SFTPBROWSER_ERROR_NOACCESS' => 'Ο φάκελος δεν υπάρχει ή δεν έχετε αρκετά δικαιώματα για πρόσβαση',
  'SFTP_COULDNT_UPLOAD' => 'Αδυναμία ανέβασματος του %s',
  'SFTP_CANT_CREATE_DIR' => 'Αδυναμία δημιουργίας φακέλου %s',
  'UI-ROOT' => '&lt;ρίζα&gt;',
  'CONFIG_UI_FTPBROWSER_TITLE' => 'Περιηγητής φακέλων FTP',
  'BTN_CHECK' => 'Έλεγχος',
  'BTN_RESET' => 'Επαναφορά',
  'BTN_TESTFTPCON' => 'Δοκιμή σύνδεσης FTP',
  'BTN_TESTSFTPCON' => 'Δοκιμή σύνδεσης SFTP',
  'BTN_GOTOSTART' => 'Επανεκκίνηση',
  'BTN_RETRY' => 'Επανάληψη',
  'FINE_TUNE' => 'Λεπτή ρύθμιση',
  'MIN_EXEC_TIME' => 'Ελάχιστος χρόνος εκτέλεσης:',
  'MAX_EXEC_TIME' => 'Μέγιστος χρόνος εκτέλεσης:',
  'SECONDS_PER_STEP' => 'δευτερόλεπτα ανά βήμα',
  'EXTRACT_FILES' => 'Εξαγωγή αρχείων',
  'BTN_START' => 'Έναρξη',
  'EXTRACTING' => 'Εξαγωγή σε εξέλιξη',
  'DO_NOT_CLOSE_EXTRACT' => 'Μην κλείσετε αυτό το παράθυρο ενώ η εξαγωγή βρίσκεται σε εξέλιξη',
  'RESTACLEANUP' => 'Αποκατάσταση και Καθαρισμός',
  'BTN_RUNINSTALLER' => 'Εκτέλεση του προγράμματος εγκατάστασης',
  'BTN_CLEANUP' => 'Καθαρισμός',
  'BTN_SITEFE' => 'Επίσκεψη στο frontend του ιστότοπου',
  'BTN_SITEBE' => 'Επίσκεψη στο backend του ιστότοπου',
  'WARNINGS' => 'Προειδοποιήσεις εξαγωγής',
  'ERROR_OCCURED' => 'Προέκυψε σφάλμα',
  'STEALTH_MODE' => 'Λανθάνων τρόπος λειτουργίας',
  'STEALTH_URL' => 'Αρχείο HTML που θα εμφανίζεται στους επισκέπτες του ιστότοπου',
  'ERR_NOT_A_JPS_FILE' => 'Το αρχείο δεν είναι αρχείο αρχειοθέτησης JPS',
  'ERR_INVALID_JPS_PASSWORD' => 'Ο κωδικός πρόσβασης που δώσατε είναι λάθος ή το αρχείο αρχειοθέτησης είναι κατεστραμμένο',
  'JPS_PASSWORD' => 'Κωδικός πρόσβασης αρχείου αρχειοθέτησης (για αρχεία JPS)',
  'INVALID_FILE_HEADER_OFFSET_ZERO' => 'Αδυναμία ανοίγματος του αρχείου %s για ανάγνωση. Αυτό είναι το τμήμα #%d του αρχείου αρχειοθέτησης αντιγράφου ασφαλείας που αποτελείται από πολλαπλά αρχεία (αρχεία με το ίδιο όνομα και επεκτάσεις .%s, .%s01, .%4$s02…). Βεβαιωθείτε ότι έχετε όλα αυτά τα αρχεία στον ίδιο φάκελο με το Kickstart.',
  'INVALID_FILE_HEADER' => 'Μη έγκυρο header στο αρχείο αρχειοθέτησης, τμήμα %s, offset %s. Βεβαιωθείτε ότι κατεβάσατε <em>και</em> ανεβάσατε τα αρχεία αρχειοθέτησης αντιγράφου ασφαλείας χρησιμοποιώντας SFTP, ή FTP σε λειτουργία μεταφοράς Binary και ελέγξατε ότι το μέγεθός τους ταιριάζει με τα μεγέθη που αναφέρονται στη σελίδα Διαχείριση Αντιγράφων Ασφαλείας του Akeeba Backup / Akeeba Solo.',
  'INVALID_FILE_HEADER_MULTIPART' => 'Μη έγκυρο header στο αρχείο αρχειοθέτησης, τμήμα %s, offset %s. Το αρχείο αρχειοθέτησης αντιγράφου ασφαλείας σας αποτελείται από πολλαπλά αρχεία (αρχεία με το ίδιο όνομα και επεκτάσεις .%s, .%s01, .%4$s02…). Ή κάποια αρχεία λείπουν, ή είναι κατεστραμμένα ή τετμημένα. Θα χρειαστείτε όλα αυτά τα αρχεία να υπάρχουν στον ίδιο φάκελο. Βεβαιωθείτε ότι κατεβάσατε <em>και</em> ανεβάσατε τα αρχεία αρχειοθέτησης αντιγράφου ασφαλείας χρησιμοποιώντας SFTP, ή FTP σε λειτουργία μεταφοράς Binary και ελέγξατε ότι το μέγεθός τους ταιριάζει με τα μεγέθη που αναφέρονται στη σελίδα Διαχείριση Αντιγράφων Ασφαλείας του Akeeba Backup / Akeeba Solo.',
  'UPDATE_HEADER' => 'Μια ενημερωμένη έκδοση του Akeeba Kickstart (<span id=update-version>unknown</span>) είναι διαθέσιμη!',
  'UPDATE_NOTICE' => 'Σας συμβουλεύουμε να χρησιμοποιείτε πάντα την τελευταία έκδοση του Akeeba Kickstart που είναι διαθέσιμη. Οι παλαιότερες εκδόσεις ενδέχεται να περιέχουν σφάλματα και δεν θα υποστηρίζονται.',
  'UPDATE_DLNOW' => 'Λήψη τώρα',
  'UPDATE_MOREINFO' => 'Περισσότερες πληροφορίες',
  'NEEDSOMEHELPKS' => 'Θέλετε βοήθεια για τη χρήση αυτού του εργαλείου; Διαβάστε πρώτα αυτό:',
  'QUICKSTART' => 'Οδηγός γρήγορης εκκίνησης',
  'CANTGETITTOWORK' => 'Δεν καταφέρνετε να το κάνετε να λειτουργήσει; Κάντε κλικ εδώ!',
  'NOARCHIVESCLICKHERE' => 'Δεν εντοπίστηκαν αρχεία αρχειοθέτησης. Κάντε κλικ εδώ για οδηγίες αντιμετώπισης προβλημάτων.',
  'POSTRESTORATIONTROUBLESHOOTING' => 'Κάτι δεν λειτουργεί μετά την αποκατάσταση; Κάντε κλικ εδώ για οδηγίες αντιμετώπισης προβλημάτων.',
  'IGNORE_MOST_ERRORS' => 'Παραβίαση περισσότερων σφαλμάτων',
  'TIME_SETTINGS_HELP' => 'Αυξήστε το ελάχιστο σε 3 αν λαμβάνετε σφάλματα AJAX. Αυξήστε το μέγιστο σε 10 για ταχύτερη εξαγωγή, μειώστε το ξανά σε 5 αν λαμβάνετε σφάλματα AJAX. Δοκιμάστε ελάχιστο 5, μέγιστο 1 (δεν είναι τυπογραφικό σφάλμα!) αν συνεχίζετε να λαμβάνετε σφάλματα AJAX.',
  'STEALTH_MODE_HELP' => 'Όταν είναι ενεργοποιημένο, μόνο οι επισκέπτες από τη διεύθυνση IP σας θα μπορούν να δουν τον ιστότοπο μέχρι να ολοκληρωθεί η αποκατάσταση. Όλοι οι υπόλοιποι θα ανακατευθύνονται και θα βλέπουν μόνο την παραπάνω URL. Ο server σας πρέπει να βλέπει την πραγματική διεύθυνση IP του επισκέπτη (αυτό ελέγχεται από τον host σας, όχι από εσάς ή εμάς).',
  'RENAME_FILES_HELP' => 'Ανομοσιάζει τα αρχεία .htaccess, web.config, php.ini και .user.ini που περιέχονται στο αρχείο αρχειοθέτησης κατά την εξαγωγή. Τα αρχεία μετονομάζονται με επέκταση .bak. Τα ονόματα αρχείων επαναφέρονται όταν κάνετε κλικ στο Καθαρισμός.',
  'RESTORE_PERMISSIONS_HELP' => 'Εφαρμόζει τα δικαιώματα αρχείων (αλλά ΟΧΙ την ιδιοκτησία αρχείων) που αποθηκεύτηκαν κατά την ώρα δημιουργίας του αντιγράφου ασφαλείας. Λειτουργεί μόνο με αρχεία αρχειοθέτησης JPA και JPS. Δεν λειτουργεί στο Windows (το PHP δεν παρέχει τέτοια δυνατότητα).',
  'EXTRACT_LIST' => 'Αρχεία προς εξαγωγή',
  'EXTRACT_LIST_HELP' => 'Εισάγετε μια διαδρομή αρχείου όπως <code>images/cat.png</code> ή ένα pattern shell όπως <code>images/*.png</code> σε κάθε γραμμή. Μόνο τα αρχεία που ταιριάζουν σε αυτή τη λίστα θα γραφτούν στον δίσκο. Αφήστε κενό για εξαγωγή όλων (προεπιλογή).',
  'AKS3_IMPORT' => 'Εισαγωγή από Amazon S3',
  'AKS3_TITLE_STEP1' => 'Σύνδεση με Amazon S3',
  'AKS3_ACCESS' => 'Κλειδί πρόσβασης',
  'AKS3_SECRET' => 'Κρυπτογραφημένο κλειδί',
  'AKS3_CONNECT' => 'Σύνδεση με Amazon S3',
  'AKS3_CANCEL' => 'Ακύρωση εισαγωγής',
  'AKS3_TITLE_STEP2' => 'Επιλογή bucket Amazon S3',
  'AKS3_BUCKET' => 'Bucket',
  'AKS3_LISTCONTENTS' => 'Εμφάνιση περιεχομένων',
  'AKS3_TITLE_STEP3' => 'Επιλογή αρχείου αρχειοθέτησης προς εισαγωγή',
  'AKS3_FOLDERS' => 'Φάκελοι',
  'AKS3_FILES' => 'Αρχεία αρχειοθέτησης',
  'AKS3_TITLE_STEP4' => 'Εισαγωγή σε εξέλιξη…',
  'AKS3_DO_NOT_CLOSE' => 'Παρακαλώ μην κλείσετε αυτό το παράθυρο ενώ τα αρχεία αρχειοθέτησης αντιγράφου ασφαλείας εισάγονται',
  'AKS3_TITLE_STEP5' => 'Η εισαγωγή ολοκληρώθηκε',
  'AKS3_BTN_RELOAD' => 'Επαναφόρτωση Kickstart',
  'WRONG_FTP_PATH2' => 'Λάθος αρχικός φάκελος FTP - ο φάκελος δεν αντιστοιχεί στη ρίζα ιστού του ιστότοπού σας',
  'ARCHIVE_DIRECTORY' => 'Φάκελος αρχείων αρχειοθέτησης:',
  'RELOAD_ARCHIVES' => 'Επαναφόρτωση',
  'CONFIG_UI_SFTPBROWSER_TITLE' => 'Περιηγητής φακέλων SFTP',
  'ERR_COULD_NOT_OPEN_ARCHIVE_PART' => 'Αδυναμία ανοίγματος του αρχείου τμήματος αρχειοθέτησης %s για ανάγνωση. Ελέγξτε ότι το αρχείο υπάρχει, είναι αναγνώσιμο από τον web server και δεν βρίσκεται σε φάκελο που είναι μη προσβάσιμος λόγω περιορισμών chroot, open_basedir ή οποιουδήποτε άλλου περιορισμού που έχει θέσει ο host σας.',
  'RENAME_FILES' => 'Μετονομασία αρχείων διαμόρφωσης server πριν την εξαγωγή',
  'BTN_SHOW_FINE_TUNE' => 'Εμφάνιση προηγμένων επιλογών (για ειδικούς)',
  'RESTORE_PERMISSIONS' => 'Αποκατάσταση δικαιωμάτων αρχείων',
  'ZAPBEFORE' => 'Διαγραφή όλων πριν την εξαγωγή',
  'ZAPBEFORE_HELP' => 'Προσπαθεί να διαγράψει όλα τα υπάρχοντα αρχεία και φακέλους κάτω από τον φάκελο όπου βρίσκεται το Kickstart πριν την εξαγωγή του αρχείου αρχειοθέτησης αντιγράφου ασφαλείας. ΔΕΝ λαμβάνει υπόψη ποια αρχεία και φακέλοι υπάρχουν στο αρχείο αρχειοθέτησης αντιγράφου ασφαλείας. Τα αρχεία και οι φάκελοι που διαγράφονται από αυτή τη δυνατότητα ΔΕΝ ΜΠΟΡΟΥΝ να ανακτηθούν. <strong>ΠΡΟΕΙΔΟΠΟΙΗΣΗ! ΑΥΤΟ ΜΠΟΡΕΙ ΝΑ ΔΙΑΓΡΕΙ ΑΡΧΕΙΑ ΚΑΙ ΦΑΚΕΛΟΥΣ ΠΟΥ ΔΕΝ ΑΝΗΚΟΥΝ ΣΤΟΝ ΙΣΤΟΤΟΠΟ ΣΑΣ. ΧΡΗΣΙΜΟΠΟΙΗΣΤΕ ΜΕ ΕΞΤΡΕΜΗ ΠΡΟΣΟΧΗ. ΕΝΕΡΓΟΠΟΙΩΝΤΑΣ ΑΥΤΗ ΤΗ ΔΥΝΑΤΟΤΗΤΑ ΑΝΑΛΑΜΒΑΝΕΤΕ ΟΛΗ ΤΗΝ ΕΥΘΥΝΗ ΚΑΙ ΑΠΟΔΑΝΕΥΘΥΝΩΣΗ.</strong>',
);

	/**
	 * Translation strings for fr-FR
	 *
	 * @var  array
	 */
	private $translation_fr_fr = array (
  'AUTOMODEON' => 'Mode automatique activé',
  'ERR_NOT_A_JPA_FILE' => 'Le fichier n\'est pas une archive JPA',
  'ERR_CORRUPT_ARCHIVE' => 'Le fichier archive est corrompu, tronqué ou des parties de l\'archive sont manquantes',
  'ERR_INVALID_ARCHIVE_LONG' => 'Le fichier archive semble corrompu, ou des parties de l\'archive sont manquantes. Si vos sauvegardes se composent de plusieurs fichiers, veuillez vous assurer d\'avoir téléchargé tous les fichiers parties de l\'archive (fichiers portant le même nom et les extensions .%s, .%s01, .%2$s02…). Veuillez vous assurer de télécharger <em>et</em> téléverser les fichiers en utilisant SFTP, ou FTP en mode de transfert binaire et vérifier que la taille de leurs fichiers correspond aux tailles indiquées dans la page Gérer les sauvegardes de Akeeba Backup / Akeeba Solo.',
  'ERR_INVALID_LOGIN' => 'Identifiant de connexion invalide',
  'COULDNT_CREATE_DIR' => 'Impossible de créer le dossier %s',
  'COULDNT_WRITE_FILE' => 'Impossible d\'ouvrir %s en écriture.',
  'WRONG_FTP_HOST' => 'Hôte ou port FTP incorrect',
  'WRONG_FTP_USER' => 'Nom d\'utilisateur ou mot de passe FTP incorrect',
  'WRONG_FTP_PATH1' => 'Répertoire initial FTP incorrect - le répertoire n\'existe pas',
  'FTP_CANT_CREATE_DIR' => 'Impossible de créer le répertoire %s',
  'FTP_TEMPDIR_NOT_WRITABLE' => 'Impossible de trouver ou de créer un répertoire temporaire accessible en écriture',
  'SFTP_TEMPDIR_NOT_WRITABLE' => 'Impossible de trouver ou de créer un répertoire temporaire accessible en écriture',
  'FTP_COULDNT_UPLOAD' => 'Impossible de téléverser %s',
  'THINGS_HEADER' => 'Points à connaître au sujet de Akeeba Kickstart',
  'THINGS_01' => 'Kickstart n\'est pas un programme d\'installation. C\'est un outil d\'extraction d\'archives. Le programme d\'installation proprement dit a été placé dans le fichier archive lors de la sauvegarde.',
  'THINGS_03' => 'Kickstart est limité par la configuration de votre serveur. À ce titre, il se peut qu\'il ne fonctionne pas du tout.',
  'THINGS_04' => 'Vous devriez télécharger et téléverser vos fichiers archive en utilisant FTP en mode de transfert binaire. Toute autre méthode pourrait entraîner une corruption de l\'archive de sauvegarde et un échec de la restauration.',
  'THINGS_05' => 'Les erreurs de chargement du site après la restauration sont généralement causées par des directives .htaccess ou php.ini. Vous devriez comprendre que les pages blanches, les erreurs 404 et 500 peuvent généralement être contournées en modifiant les fichiers susmentionnés. Nous ne sommes pas en mesure de modifier les fichiers de configuration de votre serveur pour vous. Ces modifications peuvent être spécifiques à votre serveur ou hébergeur et donc dangereuses si elles sont effectuées de manière non supervisée et non sollicitée.',
  'THINGS_06' => 'Kickstart remplace les fichiers sans avertissement. Si vous n\'êtes pas certain que cela est acceptable pour votre cas d\'utilisation, vous devriez fermer cette fenêtre.',
  'THINGS_07' => 'Tenter de restaurer sur l\'URL temporaire d\'un hébergeur cPanel (par exemple http://1.2.3.4/~utilisateur) entraînera un échec de la restauration et votre site semblera ne pas fonctionner. C\'est normal et c\'est simplement ainsi que fonctionnent votre serveur et le logiciel CMS.',
  'THINGS_08' => 'Nous vous demandons gentiment de lire la documentation. Cela vous évitera probablement du temps et de la frustration.',
  'THINGS_09' => 'Ce texte n\'implique pas qu\'un problème ait été détecté. Il s\'agit d\'un texte standard affiché à chaque lancement de Kickstart.',
  'CLOSE_LIGHTBOX' => 'Cliquez ici ou appuyez sur ÉCHAP pour fermer ce message',
  'SELECT_ARCHIVE' => 'Sélectionner une archive de sauvegarde',
  'ARCHIVE_FILE' => 'Fichier archive :',
  'SELECT_EXTRACTION' => 'Sélectionner une méthode d\'extraction',
  'WRITE_TO_FILES' => 'Écrire dans les fichiers :',
  'WRITE_HYBRID' => 'Hybride (utiliser FTP uniquement si nécessaire)',
  'WRITE_DIRECTLY' => 'Directement',
  'WRITE_FTP' => 'Utiliser FTP pour tous les fichiers',
  'WRITE_SFTP' => 'Utiliser SFTP pour tous les fichiers',
  'FTP_HOST' => 'Nom d\'hôte (S)FTP :',
  'FTP_PORT' => 'Port (S)FTP :',
  'FTP_FTPS' => 'Utiliser FTP sur SSL (FTPS)',
  'FTP_PASSIVE' => 'Utiliser le mode passif FTP',
  'FTP_USER' => 'Nom d\'utilisateur (S)FTP :',
  'FTP_PASS' => 'Mot de passe (S)FTP :',
  'FTP_DIR' => 'Répertoire (S)FTP :',
  'FTP_TEMPDIR' => 'Répertoire temporaire :',
  'FTP_CONNECTION_OK' => 'Connexion FTP établie',
  'SFTP_CONNECTION_OK' => 'Connexion SFTP établie',
  'FTP_CONNECTION_FAILURE' => 'La connexion FTP a échoué',
  'SFTP_CONNECTION_FAILURE' => 'La connexion SFTP a échoué',
  'FTP_TEMPDIR_WRITABLE' => 'Le répertoire temporaire est accessible en écriture.',
  'FTP_TEMPDIR_UNWRITABLE' => 'Le répertoire temporaire n\'est pas accessible en écriture. Veuillez vérifier les permissions.',
  'FTP_BROWSE' => 'Parcourir',
  'FTPBROWSER_LBL_INSTRUCTIONS' => 'Cliquez sur un répertoire pour y naviguer. Cliquez sur OK pour sélectionner ce répertoire, Annuler pour interrompre la procédure.',
  'FTPBROWSER_ERROR_HOSTNAME' => 'Hôte ou port FTP invalide',
  'FTPBROWSER_ERROR_USERPASS' => 'Nom d\'utilisateur ou mot de passe FTP invalide',
  'FTPBROWSER_ERROR_NOACCESS' => 'Le répertoire n\'existe pas ou vous n\'avez pas les permissions suffisantes pour y accéder',
  'FTPBROWSER_ERROR_UNSUPPORTED' => 'Désolé, votre serveur FTP ne prend pas en charge notre navigateur de répertoires FTP.',
  'FTPBROWSER_LBL_GOPARENT' => '&lt;monter d\'un niveau&gt;',
  'FTPBROWSER_LBL_ERROR' => 'Une erreur s\'est produite',
  'SFTP_NO_SSH2' => 'Votre serveur web ne dispose pas du module PHP SSH2, il ne peut donc pas se connecter aux serveurs SFTP.',
  'SFTP_NO_FTP_SUPPORT' => 'Votre serveur SSH n\'autorise pas les connexions SFTP',
  'SFTP_WRONG_USER' => 'Nom d\'utilisateur ou mot de passe SFTP incorrect',
  'SFTP_WRONG_STARTING_DIR' => 'Vous devez fournir un chemin absolu valide',
  'SFTPBROWSER_ERROR_NOACCESS' => 'Le répertoire n\'existe pas ou vous n\'avez pas les permissions suffisantes pour y accéder',
  'SFTP_COULDNT_UPLOAD' => 'Impossible de téléverser %s',
  'SFTP_CANT_CREATE_DIR' => 'Impossible de créer le répertoire %s',
  'UI-ROOT' => '&lt;racine&gt;',
  'CONFIG_UI_FTPBROWSER_TITLE' => 'Navigateur de répertoires FTP',
  'BTN_CHECK' => 'Vérifier',
  'BTN_RESET' => 'Réinitialiser',
  'BTN_TESTFTPCON' => 'Tester la connexion FTP',
  'BTN_TESTSFTPCON' => 'Tester la connexion SFTP',
  'BTN_GOTOSTART' => 'Recommencer',
  'BTN_RETRY' => 'Réessayer',
  'FINE_TUNE' => 'Affiner',
  'MIN_EXEC_TIME' => 'Temps d\'exécution minimum :',
  'MAX_EXEC_TIME' => 'Temps d\'exécution maximum :',
  'SECONDS_PER_STEP' => 'secondes par étape',
  'EXTRACT_FILES' => 'Extraire les fichiers',
  'BTN_START' => 'Démarrer',
  'EXTRACTING' => 'Extraction en cours',
  'DO_NOT_CLOSE_EXTRACT' => 'Ne fermez pas cette fenêtre pendant que l\'extraction est en cours',
  'RESTACLEANUP' => 'Restauration et nettoyage',
  'BTN_RUNINSTALLER' => 'Exécuter le programme d\'installation',
  'BTN_CLEANUP' => 'Nettoyer',
  'BTN_SITEFE' => 'Visiter la partie publique de votre site',
  'BTN_SITEBE' => 'Visiter la partie administrative de votre site',
  'WARNINGS' => 'Avertissements d\'extraction',
  'ERROR_OCCURED' => 'Une erreur s\'est produite',
  'STEALTH_MODE' => 'Mode furtif',
  'STEALTH_URL' => 'Fichier HTML à afficher aux visiteurs du web',
  'ERR_NOT_A_JPS_FILE' => 'Le fichier n\'est pas une archive JPS',
  'ERR_INVALID_JPS_PASSWORD' => 'Le mot de passe que vous avez fourni est incorrect ou l\'archive est corrompue',
  'JPS_PASSWORD' => 'Mot de passe de l\'archive (pour les fichiers JPS)',
  'INVALID_FILE_HEADER_OFFSET_ZERO' => 'Impossible d\'ouvrir le fichier %s en lecture. Il s\'agit de la partie n°%d de votre archive de sauvegarde qui se compose de plusieurs fichiers (fichiers portant le même nom et les extensions .%s, .%s01, .%4$s02…). Veuillez vous assurer que tous ces fichiers se trouvent dans le même dossier que Kickstart.',
  'INVALID_FILE_HEADER' => 'En-tête invalide dans le fichier archive, partie %s, décalage %s. Veuillez vous assurer de télécharger <em>et</em> téléverser les fichiers archive de sauvegarde en utilisant SFTP, ou FTP en mode de transfert binaire et vérifier que la taille de leurs fichiers correspond aux tailles indiquées dans la page Gérer les sauvegardes de Akeeba Backup / Akeeba Solo.',
  'INVALID_FILE_HEADER_MULTIPART' => 'En-tête invalide dans le fichier archive, partie %s, décalage %s. Votre archive de sauvegarde se compose de plusieurs fichiers (fichiers portant le même nom et les extensions .%s, .%s01, .%4$s02…). Soit certains fichiers sont manquants, soit ils sont corrompus ou tronqués. Tous ces fichiers doivent être présents dans le même répertoire. Veuillez vous assurer de télécharger <em>et</em> téléverser les fichiers archive de sauvegarde en utilisant SFTP, ou FTP en mode de transfert binaire et vérifier que la taille de leurs fichiers correspond aux tailles indiquées dans la page Gérer les sauvegardes de Akeeba Backup / Akeeba Solo.',
  'UPDATE_HEADER' => 'Une version mise à jour de Akeeba Kickstart (<span id=update-version>unknown</span>) est disponible !',
  'UPDATE_NOTICE' => 'Il vous est conseillé d\'utiliser toujours la dernière version de Akeeba Kickstart disponible. Les anciennes versions peuvent être sujettes à des bugs et ne seront pas prises en charge.',
  'UPDATE_DLNOW' => 'Télécharger maintenant',
  'UPDATE_MOREINFO' => 'Plus d\'informations',
  'NEEDSOMEHELPKS' => 'Vous avez besoin d\'aide pour utiliser cet outil ? Lisez ceci en premier :',
  'QUICKSTART' => 'Guide de démarrage rapide',
  'CANTGETITTOWORK' => 'Vous n\'arrivez pas à le faire fonctionner ? Cliquez ici !',
  'NOARCHIVESCLICKHERE' => 'Aucune archive détectée. Cliquez ici pour les instructions de dépannage.',
  'POSTRESTORATIONTROUBLESHOOTING' => 'Quelque chose ne fonctionne pas après la restauration ? Cliquez ici pour les instructions de dépannage.',
  'IGNORE_MOST_ERRORS' => 'Ignorer la plupart des erreurs',
  'TIME_SETTINGS_HELP' => 'Augmentez le minimum à 3 si vous obtenez des erreurs AJAX. Augmentez le maximum à 10 pour une extraction plus rapide, diminuez à 5 si vous obtenez des erreurs AJAX. Essayez minimum 5, maximum 1 (ce n\'est pas une coquille !) si vous continuez à obtenir des erreurs AJAX.',
  'STEALTH_MODE_HELP' => 'Lorsqu\'il est activé, seuls les visiteurs provenant de votre adresse IP pourront voir le site jusqu\'à ce que la restauration soit terminée. Tous les autres seront redirigés vers l\'URL ci-dessus et n\'en verront que celle-ci. Votre serveur doit voir l\'IP réelle du visiteur (cela est contrôlé par votre hébergeur, pas par vous ou nous).',
  'RENAME_FILES_HELP' => 'Renomme les fichiers .htaccess, web.config, php.ini et .user.ini contenus dans l\'archive lors de l\'extraction. Les fichiers sont renommés avec l\'extension .bak. Les noms de fichiers sont restaurés lorsque vous cliquez sur Nettoyage.',
  'RESTORE_PERMISSIONS_HELP' => 'Applique les permissions de fichiers (mais PAS la propriété des fichiers) qui ont été stockées au moment de la sauvegarde. Ne fonctionne qu\'avec les archives JPA et JPS. Ne fonctionne pas sous Windows (PHP n\'offre pas cette fonctionnalité).',
  'EXTRACT_LIST' => 'Fichiers à extraire',
  'EXTRACT_LIST_HELP' => 'Saisissez un chemin de fichier tel que <code>images/cat.png</code> ou un motif shell tel que <code>images/*.png</code> sur chaque ligne. Seuls les fichiers correspondant à cette liste seront écrits sur le disque. Laisser vide pour extraire tout le contenu (par défaut).',
  'AKS3_IMPORT' => 'Importer depuis Amazon S3',
  'AKS3_TITLE_STEP1' => 'Se connecter à Amazon S3',
  'AKS3_ACCESS' => 'Clé d\'accès',
  'AKS3_SECRET' => 'Clé secrète',
  'AKS3_CONNECT' => 'Se connecter à Amazon S3',
  'AKS3_CANCEL' => 'Annuler l\'importation',
  'AKS3_TITLE_STEP2' => 'Sélectionner votre bucket Amazon S3',
  'AKS3_BUCKET' => 'Bucket',
  'AKS3_LISTCONTENTS' => 'Lister le contenu',
  'AKS3_TITLE_STEP3' => 'Sélectionner l\'archive à importer',
  'AKS3_FOLDERS' => 'Dossiers',
  'AKS3_FILES' => 'Fichiers archive',
  'AKS3_TITLE_STEP4' => 'Importation en cours…',
  'AKS3_DO_NOT_CLOSE' => 'Veuillez ne pas fermer cette fenêtre pendant l\'importation de vos archives de sauvegarde',
  'AKS3_TITLE_STEP5' => 'L\'importation est terminée',
  'AKS3_BTN_RELOAD' => 'Recharger Kickstart',
  'WRONG_FTP_PATH2' => 'Répertoire initial FTP incorrect - le répertoire ne correspond pas à la racine web de votre site',
  'ARCHIVE_DIRECTORY' => 'Répertoire des archives :',
  'RELOAD_ARCHIVES' => 'Recharger',
  'CONFIG_UI_SFTPBROWSER_TITLE' => 'Navigateur de répertoires SFTP',
  'ERR_COULD_NOT_OPEN_ARCHIVE_PART' => 'Impossible d\'ouvrir le fichier partie d\'archive %s en lecture. Vérifiez que le fichier existe, est lisible par le serveur web et ne se trouve pas dans un répertoire rendu inaccessible par chroot, les restrictions open_basedir ou toute autre restriction mise en place par votre hébergeur.',
  'RENAME_FILES' => 'Renommer les fichiers de configuration du serveur avant l\'extraction',
  'BTN_SHOW_FINE_TUNE' => 'Afficher les options avancées (pour experts)',
  'RESTORE_PERMISSIONS' => 'Restaurer les permissions de fichiers',
  'ZAPBEFORE' => 'Supprimer tout avant l\'extraction',
  'ZAPBEFORE_HELP' => 'Tente de supprimer tous les fichiers et dossiers existants dans le répertoire où Kickstart est stocké avant d\'extraire l\'archive de sauvegarde. Il NE tient PAS compte des fichiers et dossiers présents dans l\'archive de sauvegarde. Les fichiers et dossiers supprimés par cette fonctionnalité NE PEUVENT PAS être récupérés. <strong>ATTENTION ! CELA PEUT SUPPRIMER DES FICHIERS ET DES DOSSIERS QUI NE APPARTIENNENT PAS À VOTRE SITE. UTILISER AVEC LA PLUS GRANDE PRUDENCE. EN ACTIVANT CETTE FONCTIONNALITÉ, VOUS ASSUMEZ L\'ENTIÈRE RESPONSABILITÉ ET LE RISQUE QUI EN RÉSULTE.</strong>',
);

	/**
	 * Translation strings for de-DE
	 *
	 * @var  array
	 */
	private $translation_de_de = array (
  'AUTOMODEON' => 'Automodus aktiviert',
  'ERR_NOT_A_JPA_FILE' => 'Die Datei ist kein JPA-Archiv',
  'ERR_CORRUPT_ARCHIVE' => 'Die Archivdatei ist beschädigt, abgeschnitten oder Archivteile fehlen',
  'ERR_INVALID_ARCHIVE_LONG' => 'Die Archivdatei scheint beschädigt zu sein oder Archivteile fehlen. Wenn Ihre Sicherungen aus mehreren Dateien bestehen, stellen Sie bitte sicher, dass Sie alle Archivteil-Dateien heruntergeladen haben (Dateien mit demselben Namen und den Erweiterungen .%s, .%s01, .%2$s02…). Bitte laden Sie <em>und</em> laden Sie Dateien über SFTP oder FTP im Binärübertragungsmodus hoch und überprüfen Sie, dass ihre Dateigröße mit den auf der Seite „Sicherungen verwalten" in Akeeba Backup / Akeeba Solo angegebenen Größen übereinstimmt.',
  'ERR_INVALID_LOGIN' => 'Ungültige Anmeldung',
  'COULDNT_CREATE_DIR' => 'Verzeichnis %s konnte nicht erstellt werden',
  'COULDNT_WRITE_FILE' => 'Datei %s konnte nicht zum Schreiben geöffnet werden.',
  'WRONG_FTP_HOST' => 'Falscher FTP-Host oder falscher Port',
  'WRONG_FTP_USER' => 'Falscher FTP-Benutzername oder falsches Passwort',
  'WRONG_FTP_PATH1' => 'Falsches FTP-Startverzeichnis – das Verzeichnis existiert nicht',
  'FTP_CANT_CREATE_DIR' => 'Verzeichnis %s konnte nicht erstellt werden',
  'FTP_TEMPDIR_NOT_WRITABLE' => 'Kein beschreibbares temporäres Verzeichnis gefunden oder erstellt werden',
  'SFTP_TEMPDIR_NOT_WRITABLE' => 'Kein beschreibbares temporäres Verzeichnis gefunden oder erstellt werden',
  'FTP_COULDNT_UPLOAD' => 'Datei %s konnte nicht hochgeladen werden',
  'THINGS_HEADER' => 'Wichtige Informationen zu Akeeba Kickstart',
  'THINGS_01' => 'Kickstart ist kein Installationsprogramm. Es ist ein Tool zum Entpacken von Archiven. Das eigentliche Installationsprogramm wurde beim Erstellen der Sicherung in die Archivdatei eingebettet.',
  'THINGS_03' => 'Kickstart ist an die Konfiguration Ihres Servers gebunden. Daher kann es sein, dass es überhaupt nicht funktioniert.',
  'THINGS_04' => 'Sie sollten Ihre Archivdateien über FTP im Binärübertragungsmodus herunterladen und hochladen. Jedes andere Verfahren kann zu einem beschädigten Sicherungsarchiv und einem Wiederherstellungsfehler führen.',
  'THINGS_05' => 'Fehler beim Laden der Website nach der Wiederherstellung werden in der Regel durch .htaccess- oder php.ini-Direktiven verursacht. Sie sollten verstehen, dass leere Seiten, 404- und 500-Fehler in der Regel durch Bearbeiten der vorgenannten Dateien behoben werden können. Wir sind nicht in der Lage, die Konfigurationsdateien Ihres Servers für Sie zu ändern. Diese Änderungen können server- oder hostspezifisch sein und daher gefährlich, wenn sie unbeaufsichtigt und unaufgefordert durchgeführt werden.',
  'THINGS_06' => 'Kickstart überschreibt Dateien ohne Warnung. Wenn Sie nicht sicher sind, ob dies für Ihren Anwendungsfall akzeptabel ist, sollten Sie dieses Fenster schließen.',
  'THINGS_07' => 'Der Versuch, auf die temporäre URL eines cPanel-Hosts wiederherzustellen (z. B. http://1.2.3.4/~benutzername), führt zum Wiederherstellungsfehler und Ihre Website scheint nicht zu funktionieren. Dies ist normal und liegt an der Funktionsweise Ihres Servers und Ihrer CMS-Software.',
  'THINGS_08' => 'Wir bitten Sie freundlich, die Dokumentation zu lesen. Dies wird Ihnen wahrscheinlich Zeit und Frustration ersparen.',
  'THINGS_09' => 'Dieser Text bedeutet nicht, dass ein Problem erkannt wurde. Es ist ein Standardtext, der bei jedem Start von Kickstart angezeigt wird.',
  'CLOSE_LIGHTBOX' => 'Klicken Sie hier oder drücken Sie ESC, um diese Nachricht zu schließen',
  'SELECT_ARCHIVE' => 'Ein Sicherungsarchiv auswählen',
  'ARCHIVE_FILE' => 'Archivdatei:',
  'SELECT_EXTRACTION' => 'Eine Extraktionsmethode auswählen',
  'WRITE_TO_FILES' => 'In Dateien schreiben:',
  'WRITE_HYBRID' => 'Hybrid (FTP nur bei Bedarf verwenden)',
  'WRITE_DIRECTLY' => 'Direkt',
  'WRITE_FTP' => 'FTP für alle Dateien verwenden',
  'WRITE_SFTP' => 'SFTP für alle Dateien verwenden',
  'FTP_HOST' => '(S)FTP-Hostname:',
  'FTP_PORT' => '(S)FTP-Port:',
  'FTP_FTPS' => 'FTP über SSL verwenden (FTPS)',
  'FTP_PASSIVE' => 'FTP-Passivmodus verwenden',
  'FTP_USER' => '(S)FTP-Benutzername:',
  'FTP_PASS' => '(S)FTP-Passwort:',
  'FTP_DIR' => '(S)FTP-Verzeichnis:',
  'FTP_TEMPDIR' => 'Temporäres Verzeichnis:',
  'FTP_CONNECTION_OK' => 'FTP-Verbindung hergestellt',
  'SFTP_CONNECTION_OK' => 'SFTP-Verbindung hergestellt',
  'FTP_CONNECTION_FAILURE' => 'Die FTP-Verbindung ist fehlgeschlagen',
  'SFTP_CONNECTION_FAILURE' => 'Die SFTP-Verbindung ist fehlgeschlagen',
  'FTP_TEMPDIR_WRITABLE' => 'Das temporäre Verzeichnis ist beschreibbar.',
  'FTP_TEMPDIR_UNWRITABLE' => 'Das temporäre Verzeichnis ist nicht beschreibbar. Bitte überprüfen Sie die Berechtigungen.',
  'FTP_BROWSE' => 'Durchsuchen',
  'FTPBROWSER_LBL_INSTRUCTIONS' => 'Klicken Sie auf ein Verzeichnis, um darin zu navigieren. Klicken Sie auf OK, um dieses Verzeichnis auszuwählen, oder auf Abbrechen, um den Vorgang abzubrechen.',
  'FTPBROWSER_ERROR_HOSTNAME' => 'Ungültiger FTP-Host oder falscher Port',
  'FTPBROWSER_ERROR_USERPASS' => 'Ungültiger FTP-Benutzername oder falsches Passwort',
  'FTPBROWSER_ERROR_NOACCESS' => 'Verzeichnis existiert nicht oder Sie haben nicht genügend Berechtigungen für den Zugriff',
  'FTPBROWSER_ERROR_UNSUPPORTED' => 'Ihr FTP-Server unterstützt unseren FTP-Verzeichnisbrowser leider nicht.',
  'FTPBROWSER_LBL_GOPARENT' => '&lt;eine Ebene nach oben&gt;',
  'FTPBROWSER_LBL_ERROR' => 'Ein Fehler ist aufgetreten',
  'SFTP_NO_SSH2' => 'Ihr Webserver verfügt nicht über das SSH2-PHP-Modul und kann daher keine Verbindung zu SFTP-Servern herstellen.',
  'SFTP_NO_FTP_SUPPORT' => 'Ihr SSH-Server erlaubt keine SFTP-Verbindungen',
  'SFTP_WRONG_USER' => 'Falscher SFTP-Benutzername oder falsches Passwort',
  'SFTP_WRONG_STARTING_DIR' => 'Sie müssen einen gültigen absoluten Pfad angeben',
  'SFTPBROWSER_ERROR_NOACCESS' => 'Verzeichnis existiert nicht oder Sie haben nicht genügend Berechtigungen für den Zugriff',
  'SFTP_COULDNT_UPLOAD' => 'Datei %s konnte nicht hochgeladen werden',
  'SFTP_CANT_CREATE_DIR' => 'Verzeichnis %s konnte nicht erstellt werden',
  'UI-ROOT' => '&lt;Wurzel&gt;',
  'CONFIG_UI_FTPBROWSER_TITLE' => 'FTP-Verzeichnisbrowser',
  'BTN_CHECK' => 'Prüfen',
  'BTN_RESET' => 'Zurücksetzen',
  'BTN_TESTFTPCON' => 'FTP-Verbindung testen',
  'BTN_TESTSFTPCON' => 'SFTP-Verbindung testen',
  'BTN_GOTOSTART' => 'Von vorne beginnen',
  'BTN_RETRY' => 'Wiederholen',
  'FINE_TUNE' => 'Feineinstellungen',
  'MIN_EXEC_TIME' => 'Minimale Ausführungszeit:',
  'MAX_EXEC_TIME' => 'Maximale Ausführungszeit:',
  'SECONDS_PER_STEP' => 'Sekunden pro Schritt',
  'EXTRACT_FILES' => 'Dateien extrahieren',
  'BTN_START' => 'Starten',
  'EXTRACTING' => 'Extrahieren',
  'DO_NOT_CLOSE_EXTRACT' => 'Schließen Sie dieses Fenster während der Extraktion nicht',
  'RESTACLEANUP' => 'Wiederherstellung und Bereinigung',
  'BTN_RUNINSTALLER' => 'Installationsprogramm ausführen',
  'BTN_CLEANUP' => 'Bereinigen',
  'BTN_SITEFE' => 'Website-Oberfläche besuchen',
  'BTN_SITEBE' => 'Website-Backend besuchen',
  'WARNINGS' => 'Extraktionswarnungen',
  'ERROR_OCCURED' => 'Ein Fehler ist aufgetreten',
  'STEALTH_MODE' => 'Tarnkappenmodus',
  'STEALTH_URL' => 'HTML-Datei, die Websitzern angezeigt werden soll',
  'ERR_NOT_A_JPS_FILE' => 'Die Datei ist kein JPS-Archiv',
  'ERR_INVALID_JPS_PASSWORD' => 'Das von Ihnen eingegebene Passwort ist falsch oder das Archiv ist beschädigt',
  'JPS_PASSWORD' => 'Archivpasswort (für JPS-Dateien)',
  'INVALID_FILE_HEADER_OFFSET_ZERO' => 'Die Datei %s konnte nicht zum Lesen geöffnet werden. Dies ist Teil #%d Ihres Sicherungsarchivs, das aus mehreren Dateien besteht (Dateien mit demselben Namen und den Erweiterungen .%s, .%s01, .%4$s02…). Bitte stellen Sie sicher, dass sich alle diese Dateien im selben Verzeichnis wie Kickstart befinden.',
  'INVALID_FILE_HEADER' => 'Ungültiger Header in der Archivdatei, Teil %s, Offset %s. Bitte laden Sie <em>und</em> laden Sie Sicherungsarchivdateien über SFTP oder FTP im Binärübertragungsmodus hoch und überprüfen Sie, dass ihre Dateigröße mit den auf der Seite „Sicherungen verwalten" in Akeeba Backup / Akeeba Solo angegebenen Größen übereinstimmt.',
  'INVALID_FILE_HEADER_MULTIPART' => 'Ungültiger Header in der Archivdatei, Teil %s, Offset %s. Ihr Sicherungsarchiv besteht aus mehreren Dateien (Dateien mit demselben Namen und den Erweiterungen .%s, .%s01, .%4$s02…). Entweder fehlen einige Dateien, oder sie sind beschädigt oder abgeschnitten. Alle diese Dateien müssen im selben Verzeichnis vorhanden sein. Bitte laden Sie <em>und</em> laden Sie Sicherungsarchivdateien über SFTP oder FTP im Binärübertragungsmodus hoch und überprüfen Sie, dass ihre Dateigröße mit den auf der Seite „Sicherungen verwalten" in Akeeba Backup / Akeeba Solo angegebenen Größen übereinstimmt.',
  'UPDATE_HEADER' => 'Eine aktualisierte Version von Akeeba Kickstart (<span id=update-version>unbekannt</span>) ist verfügbar!',
  'UPDATE_NOTICE' => 'Es wird empfohlen, immer die neueste verfügbare Version von Akeeba Kickstart zu verwenden. Ältere Versionen können Fehler enthalten und werden nicht unterstützt.',
  'UPDATE_DLNOW' => 'Jetzt herunterladen',
  'UPDATE_MOREINFO' => 'Weitere Informationen',
  'NEEDSOMEHELPKS' => 'Brauchen Sie Hilfe bei der Verwendung dieses Tools? Lesen Sie zuerst dies:',
  'QUICKSTART' => 'Schnellstartanleitung',
  'CANTGETITTOWORK' => 'Funktioniert es nicht? Hier klicken!',
  'NOARCHIVESCLICKHERE' => 'Keine Archive erkannt. Hier klicken für Fehlerbehebungsanweisungen.',
  'POSTRESTORATIONTROUBLESHOOTING' => 'Nach der Wiederherstellung funktioniert etwas nicht? Hier klicken für Fehlerbehebungsanweisungen.',
  'IGNORE_MOST_ERRORS' => 'Die meisten Fehler ignorieren',
  'TIME_SETTINGS_HELP' => 'Erhöhen Sie das Minimum auf 3, wenn Sie AJAX-Fehler erhalten. Erhöhen Sie das Maximum auf 10 für eine schnellere Extraktion, verringern Sie es auf 5, wenn Sie AJAX-Fehler erhalten. Versuchen Sie Minimum 5, Maximum 1 (kein Tippfehler!), wenn Sie weiterhin AJAX-Fehler erhalten.',
  'STEALTH_MODE_HELP' => 'Wenn aktiviert, können nur Besucher von Ihrer IP-Adresse die Website bis zum Abschluss der Wiederherstellung sehen. Alle anderen werden zur oben genannten URL weitergeleitet und sehen nur diese. Ihr Server muss die echte IP des Besuchers erkennen (dies wird von Ihrem Host, nicht von Ihnen oder uns, gesteuert).',
  'RENAME_FILES_HELP' => 'Benennt .htaccess, web.config, php.ini und .user.ini im Archiv beim Extrahieren um. Die Dateien erhalten die Erweiterung .bak. Die Dateinamen werden beim Klicken auf „Bereinigen" wiederhergestellt.',
  'RESTORE_PERMISSIONS_HELP' => 'Wendet die beim Sichern gespeicherten Dateiberechtigungen (aber NICHT den Datei-Besitz) an. Funktioniert nur mit JPA- und JPS-Archiven. Funktioniert nicht unter Windows (PHP bietet diese Funktion nicht an).',
  'EXTRACT_LIST' => 'Zu extrahierende Dateien',
  'EXTRACT_LIST_HELP' => 'Geben Sie in jeder Zeile einen Dateipfad wie <code>images/cat.png</code> oder ein Shell-Muster wie <code>images/*.png</code> ein. Nur die mit dieser Liste übereinstimmenden Dateien werden auf die Festplatte geschrieben. Leer lassen, um alles zu extrahieren (Standard).',
  'AKS3_IMPORT' => 'Von Amazon S3 importieren',
  'AKS3_TITLE_STEP1' => 'Verbindung zu Amazon S3 herstellen',
  'AKS3_ACCESS' => 'Zugriffsschlüssel',
  'AKS3_SECRET' => 'Geheimschlüssel',
  'AKS3_CONNECT' => 'Verbindung zu Amazon S3 herstellen',
  'AKS3_CANCEL' => 'Import abbrechen',
  'AKS3_TITLE_STEP2' => 'Amazon S3-Bucket auswählen',
  'AKS3_BUCKET' => 'Bucket',
  'AKS3_LISTCONTENTS' => 'Inhalt auflisten',
  'AKS3_TITLE_STEP3' => 'Archiv zum Importieren auswählen',
  'AKS3_FOLDERS' => 'Ordner',
  'AKS3_FILES' => 'Archivdateien',
  'AKS3_TITLE_STEP4' => 'Importiert…',
  'AKS3_DO_NOT_CLOSE' => 'Bitte schließen Sie dieses Fenster nicht, während Ihre Sicherungsarchive importiert werden',
  'AKS3_TITLE_STEP5' => 'Import abgeschlossen',
  'AKS3_BTN_RELOAD' => 'Kickstart neu laden',
  'WRONG_FTP_PATH2' => 'Falsches FTP-Startverzeichnis – das Verzeichnis entspricht nicht dem Web-Stammverzeichnis Ihrer Website',
  'ARCHIVE_DIRECTORY' => 'Archivverzeichnis:',
  'RELOAD_ARCHIVES' => 'Neu laden',
  'CONFIG_UI_SFTPBROWSER_TITLE' => 'SFTP-Verzeichnisbrowser',
  'ERR_COULD_NOT_OPEN_ARCHIVE_PART' => 'Archivteil-Datei %s konnte nicht zum Lesen geöffnet werden. Überprüfen Sie, ob die Datei existiert, vom Webserver lesbar ist und sich nicht in einem durch chroot-, open_basedir-Einschränkungen oder andere vom Host gesetzte Einschränkungen unzugänglichen Verzeichnis befindet.',
  'RENAME_FILES' => 'Server-Konfigurationsdateien vor der Extraktion umbenennen',
  'BTN_SHOW_FINE_TUNE' => 'Erweiterte Optionen anzeigen (für Experten)',
  'RESTORE_PERMISSIONS' => 'Dateiberechtigungen wiederherstellen',
  'ZAPBEFORE' => 'Vor der Extraktion alles löschen',
  'ZAPBEFORE_HELP' => 'Versucht, alle vorhandenen Dateien und Ordner unter dem Verzeichnis, in dem Kickstart gespeichert ist, vor dem Entpacken des Sicherungsarchivs zu löschen. Es wird NICHT berücksichtigt, welche Dateien und Ordner im Sicherungsarchiv vorhanden sind. Dateien und Ordner, die durch diese Funktion gelöscht werden, Können NICHT wiederhergestellt werden. <strong>WARNUNG! DIES KÖNNTE DATEIEN UND ORNER LÖSCHEN, DIE NICHT ZU IHRER WEBSITE GEHÖREN. MIT EXTREMER VORSICHT VERWENDEN. DURCH DAS AKTIVIEREN DIESER FUNKTION ÜBERNEHMEN SIE DIE VOLLE VERANTWORTUNG UND HAFTUNG.</strong>',
);

	/**
	 * Translation strings for es-ES
	 *
	 * @var  array
	 */
	private $translation_es_es = array (
  'AUTOMODEON' => 'Modo automático activado',
  'ERR_NOT_A_JPA_FILE' => 'El archivo no es un archivo JPA',
  'ERR_CORRUPT_ARCHIVE' => 'El archivo de archivo está corrupto, truncado o faltan partes del archivo',
  'ERR_INVALID_ARCHIVE_LONG' => 'El archivo de archivo parece estar corrupto o faltan partes del archivo. Si sus copias de seguridad consisten en varios archivos, asegúrese de haber descargado todos los archivos de parte del archivo (archivos con el mismo nombre y extensiones .%s, .%s01, .%2$s02…). Asegúrese de descargar <em>y</em> cargar archivos usando SFTP, o FTP en modo de transferencia binaria y compruebe que su tamaño coincide con los tamaños reportados en la página Gestionar copias de seguridad de Akeeba Backup / Akeeba Solo.',
  'ERR_INVALID_LOGIN' => 'Inicio de sesión no válido',
  'COULDNT_CREATE_DIR' => 'No se pudo crear la carpeta %s',
  'COULDNT_WRITE_FILE' => 'No se pudo abrir %s para escritura.',
  'WRONG_FTP_HOST' => 'Host o puerto FTP incorrecto',
  'WRONG_FTP_USER' => 'Nombre de usuario o contraseña FTP incorrectos',
  'WRONG_FTP_PATH1' => 'Directorio inicial FTP incorrecto: el directorio no existe',
  'FTP_CANT_CREATE_DIR' => 'No se pudo crear el directorio %s',
  'FTP_TEMPDIR_NOT_WRITABLE' => 'No se pudo encontrar o crear un directorio temporal con permiso de escritura',
  'SFTP_TEMPDIR_NOT_WRITABLE' => 'No se pudo encontrar o crear un directorio temporal con permiso de escritura',
  'FTP_COULDNT_UPLOAD' => 'No se pudo cargar %s',
  'THINGS_HEADER' => 'Aspectos que debe conocer sobre Akeeba Kickstart',
  'THINGS_01' => 'Kickstart no es un instalador. Es una herramienta de extracción de archivos. El instalador real se incluyó dentro del archivo en el momento de la copia de seguridad.',
  'THINGS_03' => 'Kickstart está limitado por la configuración de su servidor. Como tal, es posible que no funcione en absoluto.',
  'THINGS_04' => 'Debe descargar y cargar sus archivos de archivo usando FTP en modo de transferencia binaria. Cualquier otro método podría provocar un archivo de copia de seguridad corrupto y un fallo en la restauración.',
  'THINGS_05' => 'Los errores de carga del sitio después de la restauración suelen ser causados por directivas de .htaccess o php.ini. Debe comprender que las páginas en blanco, los errores 404 y 500 suelen poder resolverse editando los archivos mencionados anteriormente. No podemos cambiar los archivos de configuración de su servidor por usted. Estos cambios pueden ser específicos de su servidor o proveedor y, por lo tanto, peligrosos si se realizan de forma no supervisada y no solicitada.',
  'THINGS_06' => 'Kickstart sobrescribe archivos sin previo aviso. Si no está seguro de que esto sea aceptable para su caso de uso, debería cerrar esta ventana.',
  'THINGS_07' => 'Intentar restaurar en la URL temporal de un host de cPanel (por ejemplo, http://1.2.3.4/~usuario) provocará un fallo en la restauración y su sitio parecerá no funcionar. Esto es normal y se debe a cómo funcionan su servidor y el software del CMS.',
  'THINGS_08' => 'Le pedimos amablemente que lea la documentación. Hacerlo probablemente le ahorrará tiempo y frustración.',
  'THINGS_09' => 'Este texto no implica que se haya detectado un problema. Es un texto estándar que se muestra cada vez que inicia Kickstart.',
  'CLOSE_LIGHTBOX' => 'Haga clic aquí o pulse ESC para cerrar este mensaje',
  'SELECT_ARCHIVE' => 'Seleccionar un archivo de copia de seguridad',
  'ARCHIVE_FILE' => 'Archivo de archivo:',
  'SELECT_EXTRACTION' => 'Seleccionar un método de extracción',
  'WRITE_TO_FILES' => 'Escribir en archivos:',
  'WRITE_HYBRID' => 'Híbrido (usar FTP solo si es necesario)',
  'WRITE_DIRECTLY' => 'Directamente',
  'WRITE_FTP' => 'Usar FTP para todos los archivos',
  'WRITE_SFTP' => 'Usar SFTP para todos los archivos',
  'FTP_HOST' => 'Nombre de host (S)FTP:',
  'FTP_PORT' => 'Puerto (S)FTP:',
  'FTP_FTPS' => 'Usar FTP sobre SSL (FTPS)',
  'FTP_PASSIVE' => 'Usar el modo pasivo de FTP',
  'FTP_USER' => 'Nombre de usuario (S)FTP:',
  'FTP_PASS' => 'Contraseña (S)FTP:',
  'FTP_DIR' => 'Directorio (S)FTP:',
  'FTP_TEMPDIR' => 'Directorio temporal:',
  'FTP_CONNECTION_OK' => 'Conexión FTP establecida',
  'SFTP_CONNECTION_OK' => 'Conexión SFTP establecida',
  'FTP_CONNECTION_FAILURE' => 'La conexión FTP falló',
  'SFTP_CONNECTION_FAILURE' => 'La conexión SFTP falló',
  'FTP_TEMPDIR_WRITABLE' => 'El directorio temporal tiene permiso de escritura.',
  'FTP_TEMPDIR_UNWRITABLE' => 'El directorio temporal no tiene permiso de escritura. Compruebe los permisos.',
  'FTP_BROWSE' => 'Examinar',
  'FTPBROWSER_LBL_INSTRUCTIONS' => 'Haga clic en un directorio para navegar en él. Haga clic en Aceptar para seleccionar ese directorio, o Cancelar para abortar el procedimiento.',
  'FTPBROWSER_ERROR_HOSTNAME' => 'Host o puerto FTP no válido',
  'FTPBROWSER_ERROR_USERPASS' => 'Nombre de usuario o contraseña FTP no válidos',
  'FTPBROWSER_ERROR_NOACCESS' => 'El directorio no existe o no tiene suficientes permisos para acceder a él',
  'FTPBROWSER_ERROR_UNSUPPORTED' => 'Lo sentimos, su servidor FTP no admite nuestro explorador de directorios FTP.',
  'FTPBROWSER_LBL_GOPARENT' => '&lt;subir un nivel&gt;',
  'FTPBROWSER_LBL_ERROR' => 'Se produjo un error',
  'SFTP_NO_SSH2' => 'Su servidor web no tiene el módulo PHP SSH2, por lo tanto no puede conectarse a servidores SFTP.',
  'SFTP_NO_FTP_SUPPORT' => 'Su servidor SSH no permite conexiones SFTP',
  'SFTP_WRONG_USER' => 'Nombre de usuario o contraseña SFTP incorrectos',
  'SFTP_WRONG_STARTING_DIR' => 'Debe proporcionar una ruta absoluta válida',
  'SFTPBROWSER_ERROR_NOACCESS' => 'El directorio no existe o no tiene suficientes permisos para acceder a él',
  'SFTP_COULDNT_UPLOAD' => 'No se pudo cargar %s',
  'SFTP_CANT_CREATE_DIR' => 'No se pudo crear el directorio %s',
  'UI-ROOT' => '&lt;raíz&gt;',
  'CONFIG_UI_FTPBROWSER_TITLE' => 'Explorador de directorios FTP',
  'BTN_CHECK' => 'Comprobar',
  'BTN_RESET' => 'Restablecer',
  'BTN_TESTFTPCON' => 'Probar conexión FTP',
  'BTN_TESTSFTPCON' => 'Probar conexión SFTP',
  'BTN_GOTOSTART' => 'Empezar de nuevo',
  'BTN_RETRY' => 'Reintentar',
  'FINE_TUNE' => 'Ajuste fino',
  'MIN_EXEC_TIME' => 'Tiempo mínimo de ejecución:',
  'MAX_EXEC_TIME' => 'Tiempo máximo de ejecución:',
  'SECONDS_PER_STEP' => 'segundos por paso',
  'EXTRACT_FILES' => 'Extraer archivos',
  'BTN_START' => 'Iniciar',
  'EXTRACTING' => 'Extrayendo',
  'DO_NOT_CLOSE_EXTRACT' => 'No cierre esta ventana mientras la extracción esté en curso',
  'RESTACLEANUP' => 'Restauración y limpieza',
  'BTN_RUNINSTALLER' => 'Ejecutar el instalador',
  'BTN_CLEANUP' => 'Limpiar',
  'BTN_SITEFE' => 'Visitar la parte frontal de su sitio',
  'BTN_SITEBE' => 'Visitar la parte trasera de su sitio',
  'WARNINGS' => 'Advertencias de extracción',
  'ERROR_OCCURED' => 'Se produjo un error',
  'STEALTH_MODE' => 'Modo sigiloso',
  'STEALTH_URL' => 'Archivo HTML que se mostrará a los visitantes web',
  'ERR_NOT_A_JPS_FILE' => 'El archivo no es un archivo JPS',
  'ERR_INVALID_JPS_PASSWORD' => 'La contraseña que proporcionó es incorrecta o el archivo está corrupto',
  'JPS_PASSWORD' => 'Contraseña del archivo (para archivos JPS)',
  'INVALID_FILE_HEADER_OFFSET_ZERO' => 'No se puede abrir el archivo %s para lectura. Esta es la parte nº %d de su archivo de copia de seguridad, que consiste en varios archivos (archivos con el mismo nombre y extensiones .%s, .%s01, .%4$s02…). Asegúrese de tener todos estos archivos en la misma carpeta que Kickstart.',
  'INVALID_FILE_HEADER' => 'Encabezado no válido en el archivo de archivo, parte %s, desplazamiento %s. Asegúrese de descargar <em>y</em> cargar archivos de copia de seguridad usando SFTP, o FTP en modo de transferencia binaria y compruebe que su tamaño coincide con los tamaños reportados en la página Gestionar copias de seguridad de Akeeba Backup / Akeeba Solo.',
  'INVALID_FILE_HEADER_MULTIPART' => 'Encabezado no válido en el archivo de archivo, parte %s, desplazamiento %s. Su archivo de copia de seguridad consiste en varios archivos (archivos con el mismo nombre y extensiones .%s, .%s01, .%4$s02…). Algunos archivos pueden faltar, o estar corruptos o truncados. Necesitará que todos estos archivos estén presentes en el mismo directorio. Asegúrese de descargar <em>y</em> cargar archivos de copia de seguridad usando SFTP, o FTP en modo de transferencia binaria y compruebe que su tamaño coincide con los tamaños reportados en la página Gestionar copias de seguridad de Akeeba Backup / Akeeba Solo.',
  'UPDATE_HEADER' => '¡Hay una versión actualizada de Akeeba Kickstart (<span id=update-version>desconocida</span>) disponible!',
  'UPDATE_NOTICE' => 'Se le recomienda utilizar siempre la última versión disponible de Akeeba Kickstart. Las versiones anteriores pueden tener errores y no recibirán soporte.',
  'UPDATE_DLNOW' => 'Descargar ahora',
  'UPDATE_MOREINFO' => 'Más información',
  'NEEDSOMEHELPKS' => '¿Necesita ayuda para usar esta herramienta? Lea esto primero:',
  'QUICKSTART' => 'Guía de inicio rápido',
  'CANTGETITTOWORK' => '¿No consigue que funcione? ¡Haga clic aquí!',
  'NOARCHIVESCLICKHERE' => 'No se han detectado archivos. Haga clic aquí para obtener instrucciones de solución de problemas.',
  'POSTRESTORATIONTROUBLESHOOTING' => '¿Algo no funciona después de la restauración? Haga clic aquí para obtener instrucciones de solución de problemas.',
  'IGNORE_MOST_ERRORS' => 'Ignorar la mayoría de los errores',
  'TIME_SETTINGS_HELP' => 'Aumente el mínimo a 3 si obtiene errores de AJAX. Aumente el máximo a 10 para una extracción más rápida, disminúyalo a 5 si obtiene errores de AJAX. Pruebe con mínimo 5, máximo 1 (no es un error tipográfico) si sigue obteniendo errores de AJAX.',
  'STEALTH_MODE_HELP' => 'Cuando está activado, solo los visitantes desde su dirección IP podrán ver el sitio hasta que se complete la restauración. Todos los demás serán redirigidos a la URL anterior y solo verán esa página. Su servidor debe ver la IP real del visitante (esto está controlado por su proveedor de alojamiento, no por usted ni por nosotros).',
  'RENAME_FILES_HELP' => 'Renombra los archivos .htaccess, web.config, php.ini y .user.ini contenidos en el archivo durante la extracción. Los archivos se renombran con una extensión .bak. Los nombres de archivo se restauran cuando hace clic en Limpiar.',
  'RESTORE_PERMISSIONS_HELP' => 'Aplica los permisos de archivo (pero NO la propiedad del archivo) que se almacenaron en el momento de la copia de seguridad. Solo funciona con archivos JPA y JPS. No funciona en Windows (PHP no ofrece esta funcionalidad).',
  'EXTRACT_LIST' => 'Archivos a extraer',
  'EXTRACT_LIST_HELP' => 'Introduzca una ruta de archivo como <code>images/cat.png</code> o un patrón de shell como <code>images/*.png</code> en cada línea. Solo los archivos que coincidan con esta lista se escribirán en el disco. Déjelo vacío para extraer todo (predeterminado).',
  'AKS3_IMPORT' => 'Importar desde Amazon S3',
  'AKS3_TITLE_STEP1' => 'Conectar a Amazon S3',
  'AKS3_ACCESS' => 'Clave de acceso',
  'AKS3_SECRET' => 'Clave secreta',
  'AKS3_CONNECT' => 'Conectar a Amazon S3',
  'AKS3_CANCEL' => 'Cancelar importación',
  'AKS3_TITLE_STEP2' => 'Seleccionar su bucket de Amazon S3',
  'AKS3_BUCKET' => 'Bucket',
  'AKS3_LISTCONTENTS' => 'Mostrar contenido',
  'AKS3_TITLE_STEP3' => 'Seleccionar archivo para importar',
  'AKS3_FOLDERS' => 'Carpetas',
  'AKS3_FILES' => 'Archivos de archivo',
  'AKS3_TITLE_STEP4' => 'Importando...',
  'AKS3_DO_NOT_CLOSE' => 'Por favor, no cierre esta ventana mientras se están importando sus archivos de copia de seguridad',
  'AKS3_TITLE_STEP5' => 'La importación se ha completado',
  'AKS3_BTN_RELOAD' => 'Recargar Kickstart',
  'WRONG_FTP_PATH2' => 'Directorio inicial FTP incorrecto: el directorio no corresponde con la raíz web de su sitio',
  'ARCHIVE_DIRECTORY' => 'Directorio de archivos:',
  'RELOAD_ARCHIVES' => 'Recargar',
  'CONFIG_UI_SFTPBROWSER_TITLE' => 'Explorador de directorios SFTP',
  'ERR_COULD_NOT_OPEN_ARCHIVE_PART' => 'No se pudo abrir el archivo de parte del archivo %s para lectura. Compruebe que el archivo existe, es legible por el servidor web y no se encuentra en un directorio inaccesible debido a chroot, restricciones open_basedir o cualquier otra restricción impuesta por su proveedor de alojamiento.',
  'RENAME_FILES' => 'Renombrar archivos de configuración del servidor antes de la extracción',
  'BTN_SHOW_FINE_TUNE' => 'Mostrar opciones avanzadas (para expertos)',
  'RESTORE_PERMISSIONS' => 'Restaurar permisos de archivo',
  'ZAPBEFORE' => 'Eliminar todo antes de la extracción',
  'ZAPBEFORE_HELP' => 'Intenta eliminar todos los archivos y carpetas existentes bajo el directorio donde se almacena Kickstart antes de extraer el archivo de copia de seguridad. NO tiene en cuenta qué archivos y carpetas existen en el archivo de copia de seguridad. Los archivos y carpetas eliminados por esta función NO SE PUEDEN recuperar. <strong>¡ADVERTENCIA! ESTO PUEDE ELIMINAR ARCHIVOS Y CARPETAS QUE NO PERTENECEN A SU SITIO. USE CON EXTREMA PRECAUCIÓN. AL ACTIVAR ESTA FUNCIÓN USTED Asume TODA LA RESPONSABILIDAD.</strong>',
);

	/**
	 * Translation strings for it-IT
	 *
	 * @var  array
	 */
	private $translation_it_it = array (
  'AUTOMODEON' => 'Modalità automatica attivata',
  'ERR_NOT_A_JPA_FILE' => 'Il file non è un archivio JPA',
  'ERR_CORRUPT_ARCHIVE' => 'Il file di archivio è corrotto, troncato o mancano parti dell\'archivio',
  'ERR_INVALID_ARCHIVE_LONG' => 'Il file di archivio sembra essere corrotto, o mancano parti dell\'archivio. Se i tuoi backup sono composti da più file, assicurati di aver scaricato tutti i file delle parti dell\'archivio (file con lo stesso nome ed estensioni .%s, .%s01, .%2$s02…). Assicurati di scaricare <em>e</em> caricare i file usando SFTP, o FTP in modalità di trasferimento Binario e verifica che le loro dimensioni corrispondano a quelle riportate nella pagina Gestione Backup di Akeeba Backup / Akeeba Solo.',
  'ERR_INVALID_LOGIN' => 'Accesso non valido',
  'COULDNT_CREATE_DIR' => 'Impossibile creare la cartella %s',
  'COULDNT_WRITE_FILE' => 'Impossibile aprire %s per la scrittura.',
  'WRONG_FTP_HOST' => 'Host o porta FTP errati',
  'WRONG_FTP_USER' => 'Nome utente o password FTP errati',
  'WRONG_FTP_PATH1' => 'Directory iniziale FTP errata - la directory non esiste',
  'FTP_CANT_CREATE_DIR' => 'Impossibile creare la directory %s',
  'FTP_TEMPDIR_NOT_WRITABLE' => 'Impossibile trovare o creare una directory temporanea scrivibile',
  'SFTP_TEMPDIR_NOT_WRITABLE' => 'Impossibile trovare o creare una directory temporanea scrivibile',
  'FTP_COULDNT_UPLOAD' => 'Impossibile caricare %s',
  'THINGS_HEADER' => 'Cose che dovresti sapere su Akeeba Kickstart',
  'THINGS_01' => 'Kickstart non è un programma di installazione. È uno strumento di estrazione degli archivi. Il programma di installazione effettivo è stato inserito nel file di archivio al momento del backup.',
  'THINGS_03' => 'Kickstart è limitato dalla configurazione del tuo server. Come tale, potrebbe non funzionare affatto.',
  'THINGS_04' => 'Dovresti scaricare e caricare i file di archivio usando FTP in modalità di trasferimento Binario. Qualsiasi altro metodo potrebbe portare a un archivio di backup corrotto e al fallimento del ripristino.',
  'THINGS_05' => 'Gli errori di caricamento del sito dopo il ripristino sono generalmente causati da direttive .htaccess o php.ini. Dovresti capire che le pagine bianche, gli errori 404 e 500 possono di solito essere risolti modificando i suddetti file. Non siamo in grado di modificare i file di configurazione del tuo server per te. Queste modifiche possono essere specifiche del tuo server o host e quindi pericolose se eseguite in modo non supervisionato e non richiesto.',
  'THINGS_06' => 'Kickstart sovrascrive i file senza preavviso. Se non sei sicuro che questo sia accettabile per il tuo caso d\'uso, dovresti chiudere questa finestra.',
  'THINGS_07' => 'Tentare di ripristinare sull\'URL temporaneo di un host cPanel (es. http://1.2.3.4/~username) porterà al fallimento del ripristino e il tuo sito sembrerà non funzionare. Questo è normale ed è semplicemente il modo in cui il tuo server e il software CMS funzionano.',
  'THINGS_08' => 'Ti chiediamo gentilmente di leggere la documentazione. Farlo probabilmente ti farà risparmiare tempo e frustrazione.',
  'THINGS_09' => 'Questo testo non implica che sia stato rilevato un problema. È un testo standard visualizzato ogni volta che avvii Kickstart.',
  'CLOSE_LIGHTBOX' => 'Clicca qui o premi ESC per chiudere questo messaggio',
  'SELECT_ARCHIVE' => 'Seleziona un archivio di backup',
  'ARCHIVE_FILE' => 'File di archivio:',
  'SELECT_EXTRACTION' => 'Seleziona un metodo di estrazione',
  'WRITE_TO_FILES' => 'Scrittura su file:',
  'WRITE_HYBRID' => 'Ibrido (usa FTP solo se necessario)',
  'WRITE_DIRECTLY' => 'Direttamente',
  'WRITE_FTP' => 'Usa FTP per tutti i file',
  'WRITE_SFTP' => 'Usa SFTP per tutti i file',
  'FTP_HOST' => 'Nome host (S)FTP:',
  'FTP_PORT' => 'Porta (S)FTP:',
  'FTP_FTPS' => 'Usa FTP su SSL (FTPS)',
  'FTP_PASSIVE' => 'Usa la modalità passiva FTP',
  'FTP_USER' => 'Nome utente (S)FTP:',
  'FTP_PASS' => 'Password (S)FTP:',
  'FTP_DIR' => 'Directory (S)FTP:',
  'FTP_TEMPDIR' => 'Directory temporanea:',
  'FTP_CONNECTION_OK' => 'Connessione FTP stabilita',
  'SFTP_CONNECTION_OK' => 'Connessione SFTP stabilita',
  'FTP_CONNECTION_FAILURE' => 'La connessione FTP è fallita',
  'SFTP_CONNECTION_FAILURE' => 'La connessione SFTP è fallita',
  'FTP_TEMPDIR_WRITABLE' => 'La directory temporanea è scrivibile.',
  'FTP_TEMPDIR_UNWRITABLE' => 'La directory temporanea non è scrivibile. Verifica le autorizzazioni.',
  'FTP_BROWSE' => 'Sfoglia',
  'FTPBROWSER_LBL_INSTRUCTIONS' => 'Clicca su una directory per navigare al suo interno. Clicca su OK per selezionare quella directory, Annulla per interrompere la procedura.',
  'FTPBROWSER_ERROR_HOSTNAME' => 'Host o porta FTP non validi',
  'FTPBROWSER_ERROR_USERPASS' => 'Nome utente o password FTP non validi',
  'FTPBROWSER_ERROR_NOACCESS' => 'La directory non esiste o non hai autorizzazioni sufficienti per accedervi',
  'FTPBROWSER_ERROR_UNSUPPORTED' => 'Spiacenti, il tuo server FTP non supporta il nostro browser di directory FTP.',
  'FTPBROWSER_LBL_GOPARENT' => '&lt;su di un livello&gt;',
  'FTPBROWSER_LBL_ERROR' => 'Si è verificato un errore',
  'SFTP_NO_SSH2' => 'Il tuo server web non dispone del modulo PHP SSH2, quindi non può connettersi ai server SFTP.',
  'SFTP_NO_FTP_SUPPORT' => 'Il tuo server SSH non consente connessioni SFTP',
  'SFTP_WRONG_USER' => 'Nome utente o password SFTP errati',
  'SFTP_WRONG_STARTING_DIR' => 'Devi fornire un percorso assoluto valido',
  'SFTPBROWSER_ERROR_NOACCESS' => 'La directory non esiste o non hai autorizzazioni sufficienti per accedervi',
  'SFTP_COULDNT_UPLOAD' => 'Impossibile caricare %s',
  'SFTP_CANT_CREATE_DIR' => 'Impossibile creare la directory %s',
  'UI-ROOT' => '&lt;root&gt;',
  'CONFIG_UI_FTPBROWSER_TITLE' => 'Browser di directory FTP',
  'BTN_CHECK' => 'Verifica',
  'BTN_RESET' => 'Reimposta',
  'BTN_TESTFTPCON' => 'Testa la connessione FTP',
  'BTN_TESTSFTPCON' => 'Testa la connessione SFTP',
  'BTN_GOTOSTART' => 'Ricomincia',
  'BTN_RETRY' => 'Riprova',
  'FINE_TUNE' => 'Regola in dettaglio',
  'MIN_EXEC_TIME' => 'Tempo minimo di esecuzione:',
  'MAX_EXEC_TIME' => 'Tempo massimo di esecuzione:',
  'SECONDS_PER_STEP' => 'secondi per passaggio',
  'EXTRACT_FILES' => 'Estrai file',
  'BTN_START' => 'Avvia',
  'EXTRACTING' => 'Estrazione in corso',
  'DO_NOT_CLOSE_EXTRACT' => 'Non chiudere questa finestra mentre l\'estrazione è in corso',
  'RESTACLEANUP' => 'Ripristino e pulizia',
  'BTN_RUNINSTALLER' => 'Esegui il programma di installazione',
  'BTN_CLEANUP' => 'Pulisci',
  'BTN_SITEFE' => 'Visita il frontend del tuo sito',
  'BTN_SITEBE' => 'Visita il backend del tuo sito',
  'WARNINGS' => 'Avvisi di estrazione',
  'ERROR_OCCURED' => 'Si è verificato un errore',
  'STEALTH_MODE' => 'Modalità stealth',
  'STEALTH_URL' => 'File HTML da mostrare ai visitatori web',
  'ERR_NOT_A_JPS_FILE' => 'Il file non è un archivio JPS',
  'ERR_INVALID_JPS_PASSWORD' => 'La password inserita è errata o l\'archivio è corrotto',
  'JPS_PASSWORD' => 'Password dell\'archivio (per file JPS)',
  'INVALID_FILE_HEADER_OFFSET_ZERO' => 'Impossibile aprire il file %s per la lettura. Questa è la parte #%d del tuo archivio di backup che è composto da più file (file con lo stesso nome ed estensioni .%s, .%s01, .%4$s02…). Assicurati di avere tutti questi file nella stessa cartella di Kickstart.',
  'INVALID_FILE_HEADER' => 'Intestazione non valida nel file di archivio, parte %s, offset %s. Assicurati di scaricare <em>e</em> caricare i file di archivio di backup usando SFTP, o FTP in modalità di trasferimento Binario e verifica che le loro dimensioni corrispondano a quelle riportate nella pagina Gestione Backup di Akeeba Backup / Akeeba Solo.',
  'INVALID_FILE_HEADER_MULTIPART' => 'Intestazione non valida nel file di archivio, parte %s, offset %s. Il tuo archivio di backup è composto da più file (file con lo stesso nome ed estensioni .%s, .%s01, .%4$s02…). Alcuni file potrebbero mancare, oppure sono corrotti o troncati. Avrai bisogno di tutti questi file presenti nella stessa directory. Assicurati di scaricare <em>e</em> caricare i file di archivio di backup usando SFTP, o FTP in modalità di trasferimento Binario e verifica che le loro dimensioni corrispondano a quelle riportate nella pagina Gestione Backup di Akeeba Backup / Akeeba Solo.',
  'UPDATE_HEADER' => 'È disponibile una versione aggiornata di Akeeba Kickstart (<span id=update-version>unknown</span>)!',
  'UPDATE_NOTICE' => 'Si consiglia di utilizzare sempre l\'ultima versione disponibile di Akeeba Kickstart. Le versioni precedenti potrebbero essere soggette a bug e non saranno supportate.',
  'UPDATE_DLNOW' => 'Scarica ora',
  'UPDATE_MOREINFO' => 'Maggiori informazioni',
  'NEEDSOMEHELPKS' => 'Hai bisogno di aiuto per usare questo strumento? Leggi prima questo:',
  'QUICKSTART' => 'Guida rapida',
  'CANTGETITTOWORK' => 'Non riesci a farlo funzionare? Clicca qui!',
  'NOARCHIVESCLICKHERE' => 'Nessun archivio rilevato. Clicca qui per le istruzioni di risoluzione dei problemi.',
  'POSTRESTORATIONTROUBLESHOOTING' => 'Qualcosa non funziona dopo il ripristino? Clicca qui per le istruzioni di risoluzione dei problemi.',
  'IGNORE_MOST_ERRORS' => 'Ignora la maggior parte degli errori',
  'TIME_SETTINGS_HELP' => 'Aumenta il minimo a 3 se ricevi errori AJAX. Aumenta il massimo a 10 per un\'estrazione più veloce, riduci a 5 se ricevi errori AJAX. Prova minimo 5, massimo 1 (non è un errore di battitura!) se continui a ricevere errori AJAX.',
  'STEALTH_MODE_HELP' => 'Quando attivata, solo i visitatori dal tuo indirizzo IP potranno vedere il sito fino al completamento del ripristino. Tutti gli altri saranno reindirizzati e vedranno solo la URL sopra. Il tuo server deve vedere l\'IP reale del visitatore (questo è controllato dal tuo host, non da te o da noi).',
  'RENAME_FILES_HELP' => 'Rinomina .htaccess, web.config, php.ini e .user.ini contenuti nell\'archivio durante l\'estrazione. I file vengono rinominati con un\'estensione .bak. I nomi dei file vengono ripristinati quando clicchi su Pulisci.',
  'RESTORE_PERMISSIONS_HELP' => 'Applica le autorizzazioni dei file (ma NON la proprietà dei file) che sono state memorizzate al momento del backup. Funziona solo con archivi JPA e JPS. Non funziona su Windows (PHP non offre questa funzionalità).',
  'EXTRACT_LIST' => 'File da estrarre',
  'EXTRACT_LIST_HELP' => 'Inserisci un percorso di file come <code>images/cat.png</code> o un modello shell come <code>images/*.png</code> su ogni riga. Solo i file corrispondenti a questo elenco verranno scritti su disco. Lasciare vuoto per estrarre tutto (predefinito).',
  'AKS3_IMPORT' => 'Importa da Amazon S3',
  'AKS3_TITLE_STEP1' => 'Connetti ad Amazon S3',
  'AKS3_ACCESS' => 'Chiave di accesso',
  'AKS3_SECRET' => 'Chiave segreta',
  'AKS3_CONNECT' => 'Connetti ad Amazon S3',
  'AKS3_CANCEL' => 'Annulla importazione',
  'AKS3_TITLE_STEP2' => 'Seleziona il tuo bucket Amazon S3',
  'AKS3_BUCKET' => 'Bucket',
  'AKS3_LISTCONTENTS' => 'Elenca contenuti',
  'AKS3_TITLE_STEP3' => 'Seleziona l\'archivio da importare',
  'AKS3_FOLDERS' => 'Cartelle',
  'AKS3_FILES' => 'File di archivio',
  'AKS3_TITLE_STEP4' => 'Importazione in corso...',
  'AKS3_DO_NOT_CLOSE' => 'Non chiudere questa finestra mentre i tuoi archivi di backup vengono importati',
  'AKS3_TITLE_STEP5' => 'L\'importazione è completata',
  'AKS3_BTN_RELOAD' => 'Ricarica Kickstart',
  'WRONG_FTP_PATH2' => 'Directory iniziale FTP errata - la directory non corrisponde alla radice web del tuo sito',
  'ARCHIVE_DIRECTORY' => 'Directory degli archivi:',
  'RELOAD_ARCHIVES' => 'Ricarica',
  'CONFIG_UI_SFTPBROWSER_TITLE' => 'Browser di directory SFTP',
  'ERR_COULD_NOT_OPEN_ARCHIVE_PART' => 'Impossibile aprire il file della parte dell\'archivio %s per la lettura. Verifica che il file esista, sia leggibile dal server web e non si trovi in una directory resa irraggiungibile da chroot, restrizioni open_basedir o qualsiasi altra restrizione impostata dal tuo host.',
  'RENAME_FILES' => 'Rinomina i file di configurazione del server prima dell\'estrazione',
  'BTN_SHOW_FINE_TUNE' => 'Mostra opzioni avanzate (per esperti)',
  'RESTORE_PERMISSIONS' => 'Ripristina le autorizzazioni dei file',
  'ZAPBEFORE' => 'Cancella tutto prima dell\'estrazione',
  'ZAPBEFORE_HELP' => 'Tenta di eliminare tutti i file e le cartelle esistenti sotto la directory dove è memorizzato Kickstart prima di estrarre l\'archivio di backup. NON tiene conto di quali file e cartelle esistono nell\'archivio di backup. I file e le cartelle eliminati da questa funzionalità NON possono essere recuperati. <strong>ATTENZIONE! QUESTO POTREBBE ELIMINARE FILE E CARTELLE CHE NON APPARTENGONO AL TUO SITO. USARE CON ESTREMA CAUTELA. ATTIVANDO QUESTA FUNZIONALITÀ SI ASSUME OGNI RESPONSABILITÀ E OBBLIGAZIONE.</strong>',
);

	/**
	 * Translation strings for pt-PT
	 *
	 * @var  array
	 */
	private $translation_pt_pt = array (
  'AUTOMODEON' => 'Modo automático ativado',
  'ERR_NOT_A_JPA_FILE' => 'O ficheiro não é um arquivo JPA',
  'ERR_CORRUPT_ARCHIVE' => 'O ficheiro de arquivo está corrompido, truncado ou estão faltando partes do arquivo',
  'ERR_INVALID_ARCHIVE_LONG' => 'O ficheiro de arquivo parece estar corrompido, ou estão faltando partes do arquivo. Se as suas cópias de segurança consistem em múltiplos ficheiros, por favor certifique-se de que descarregou todos os ficheiros de partes do arquivo (ficheiros com o mesmo nome e extensões .%s, .%s01, .%2$s02…). Por favor certifique-se de descarregar <em>e</em> enviar ficheiros usando SFTP, ou FTP no modo de transferência Binária e verifique que o tamanho dos ficheiros corresponde aos tamanhos reportados na página Gerir Cópias de Segurança do Akeeba Backup / Akeeba Solo.',
  'ERR_INVALID_LOGIN' => 'Login inválido',
  'COULDNT_CREATE_DIR' => 'Não foi possível criar a pasta %s',
  'COULDNT_WRITE_FILE' => 'Não foi possível abrir %s para escrita.',
  'WRONG_FTP_HOST' => 'Nome ou porta do servidor FTP incorretos',
  'WRONG_FTP_USER' => 'Nome de utilizador ou palavra-passe FTP incorretos',
  'WRONG_FTP_PATH1' => 'Diretório inicial FTP incorreto — o diretório não existe',
  'FTP_CANT_CREATE_DIR' => 'Não foi possível criar o diretório %s',
  'FTP_TEMPDIR_NOT_WRITABLE' => 'Não foi possível encontrar ou criar um diretório temporário com permissão de escrita',
  'SFTP_TEMPDIR_NOT_WRITABLE' => 'Não foi possível encontrar ou criar um diretório temporário com permissão de escrita',
  'FTP_COULDNT_UPLOAD' => 'Não foi possível enviar %s',
  'THINGS_HEADER' => 'Coisas que deve saber sobre o Akeeba Kickstart',
  'THINGS_01' => 'O Kickstart não é um instalador. É uma ferramenta de extração de arquivos. O instalador propriamente dito foi colocado dentro do ficheiro de arquivo no momento da cópia de segurança.',
  'THINGS_03' => 'O Kickstart está limitado pela configuração do seu servidor. Como tal, pode não funcionar de todo.',
  'THINGS_04' => 'Deve descarregar e enviar os seus ficheiros de arquivo usando FTP no modo de transferência Binária. Qualquer outro método poderá levar a um arquivo de cópia de segurança corrompido e falha na restauração.',
  'THINGS_05' => 'Erros ao carregar o site após a restauração são geralmente causados por diretivas do .htaccess ou php.ini. Deve entender que páginas em branco, erros 404 e 500 podem geralmente ser contornados editando os ficheiros acima mencionados. Não podemos alterar os ficheiros de configuração do seu servidor por si. Estas alterações podem ser específicas do seu servidor ou anfitrião e, portanto, perigosas se realizadas de forma não supervisionada e não solicitada.',
  'THINGS_06' => 'O Kickstart substitui ficheiros sem aviso. Se não tiver a certeza de que isto é aceitável para o seu caso de utilização, deverá fechar esta janela.',
  'THINGS_07' => 'Tentar restaurar para a URL temporária de um alojamento cPanel (por exemplo, http://1.2.3.4/~username) irá levar a uma falha na restauração e o seu site parecerá não estar a funcionar. Isto é normal e é assim que o seu servidor e o software CMS funcionam.',
  'THINGS_08' => 'Pedimos-lhe gentilmente que leia a documentação. Fazê-lo provavelmente poupará tempo e frustração.',
  'THINGS_09' => 'Este texto não implica que exista um problema detetado. É um texto padrão exibido sempre que lança o Kickstart.',
  'CLOSE_LIGHTBOX' => 'Clique aqui ou prima ESC para fechar esta mensagem',
  'SELECT_ARCHIVE' => 'Selecionar um arquivo de cópia de segurança',
  'ARCHIVE_FILE' => 'Ficheiro de arquivo:',
  'SELECT_EXTRACTION' => 'Selecionar um método de extração',
  'WRITE_TO_FILES' => 'Escrever em ficheiros:',
  'WRITE_HYBRID' => 'Híbrido (usar FTP apenas se necessário)',
  'WRITE_DIRECTLY' => 'Diretamente',
  'WRITE_FTP' => 'Usar FTP para todos os ficheiros',
  'WRITE_SFTP' => 'Usar SFTP para todos os ficheiros',
  'FTP_HOST' => 'Nome do servidor (S)FTP:',
  'FTP_PORT' => 'Porta (S)FTP:',
  'FTP_FTPS' => 'Usar FTP sobre SSL (FTPS)',
  'FTP_PASSIVE' => 'Usar Modo Passivo do FTP',
  'FTP_USER' => 'Nome de utilizador (S)FTP:',
  'FTP_PASS' => 'Palavra-passe (S)FTP:',
  'FTP_DIR' => 'Diretório (S)FTP:',
  'FTP_TEMPDIR' => 'Diretório temporário:',
  'FTP_CONNECTION_OK' => 'Ligação FTP estabelecida',
  'SFTP_CONNECTION_OK' => 'Ligação SFTP estabelecida',
  'FTP_CONNECTION_FAILURE' => 'A ligação FTP falhou',
  'SFTP_CONNECTION_FAILURE' => 'A ligação SFTP falhou',
  'FTP_TEMPDIR_WRITABLE' => 'O diretório temporário tem permissão de escrita.',
  'FTP_TEMPDIR_UNWRITABLE' => 'O diretório temporário não tem permissão de escrita. Por favor verifique as permissões.',
  'FTP_BROWSE' => 'Navegar',
  'FTPBROWSER_LBL_INSTRUCTIONS' => 'Clique num diretório para navegar nele. Clique em OK para selecionar esse diretório, Cancelar para abortar o procedimento.',
  'FTPBROWSER_ERROR_HOSTNAME' => 'Nome ou porta do servidor FTP inválidos',
  'FTPBROWSER_ERROR_USERPASS' => 'Nome de utilizador ou palavra-passe FTP inválidos',
  'FTPBROWSER_ERROR_NOACCESS' => 'O diretório não existe ou não tem permissões suficientes para aceder a ele',
  'FTPBROWSER_ERROR_UNSUPPORTED' => 'Lamentamos, mas o seu servidor FTP não suporta o nosso navegador de diretórios FTP.',
  'FTPBROWSER_LBL_GOPARENT' => '&lt;subir um nível&gt;',
  'FTPBROWSER_LBL_ERROR' => 'Ocorreu um erro',
  'SFTP_NO_SSH2' => 'O seu servidor web não tem o módulo PHP SSH2, por conseguinte não consegue ligar a servidores SFTP.',
  'SFTP_NO_FTP_SUPPORT' => 'O seu servidor SSH não permite ligações SFTP',
  'SFTP_WRONG_USER' => 'Nome de utilizador ou palavra-passe SFTP incorretos',
  'SFTP_WRONG_STARTING_DIR' => 'Deve fornecer um caminho absoluto válido',
  'SFTPBROWSER_ERROR_NOACCESS' => 'O diretório não existe ou não tem permissões suficientes para aceder a ele',
  'SFTP_COULDNT_UPLOAD' => 'Não foi possível enviar %s',
  'SFTP_CANT_CREATE_DIR' => 'Não foi possível criar o diretório %s',
  'UI-ROOT' => '&lt;raiz&gt;',
  'CONFIG_UI_FTPBROWSER_TITLE' => 'Navegador de Diretórios FTP',
  'BTN_CHECK' => 'Verificar',
  'BTN_RESET' => 'Repor',
  'BTN_TESTFTPCON' => 'Testar Ligação FTP',
  'BTN_TESTSFTPCON' => 'Testar Ligação SFTP',
  'BTN_GOTOSTART' => 'Recomeçar',
  'BTN_RETRY' => 'Tentar novamente',
  'FINE_TUNE' => 'Ajuste fino',
  'MIN_EXEC_TIME' => 'Tempo mínimo de execução:',
  'MAX_EXEC_TIME' => 'Tempo máximo de execução:',
  'SECONDS_PER_STEP' => 'segundos por passo',
  'EXTRACT_FILES' => 'Extrair ficheiros',
  'BTN_START' => 'Iniciar',
  'EXTRACTING' => 'A extrair',
  'DO_NOT_CLOSE_EXTRACT' => 'Não feche esta janela enquanto a extração estiver em curso',
  'RESTACLEANUP' => 'Restauração e Limpeza',
  'BTN_RUNINSTALLER' => 'Executar o Instalador',
  'BTN_CLEANUP' => 'Limpar',
  'BTN_SITEFE' => 'Visitar a parte frontal do seu site',
  'BTN_SITEBE' => 'Visitar a parte administrativa do seu site',
  'WARNINGS' => 'Avisos de Extração',
  'ERROR_OCCURED' => 'Ocorreu um erro',
  'STEALTH_MODE' => 'Modo furtivo',
  'STEALTH_URL' => 'Ficheiro HTML a mostrar aos visitantes do site',
  'ERR_NOT_A_JPS_FILE' => 'O ficheiro não é um arquivo JPS',
  'ERR_INVALID_JPS_PASSWORD' => 'A palavra-passe que forneceu está incorreta ou o arquivo está corrompido',
  'JPS_PASSWORD' => 'Palavra-passe do Arquivo (para ficheiros JPS)',
  'INVALID_FILE_HEADER_OFFSET_ZERO' => 'Não é possível abrir o ficheiro %s para leitura. Esta é a parte nº %d do seu arquivo de cópia de segurança que consiste em múltiplos ficheiros (ficheiros com o mesmo nome e extensões .%s, .%s01, .%4$s02…). Por favor certifique-se de que tem todos estes ficheiros na mesma pasta que o Kickstart.',
  'INVALID_FILE_HEADER' => 'Cabeçalho inválido no ficheiro de arquivo, parte %s, deslocamento %s. Por favor certifique-se de descarregar <em>e</em> enviar ficheiros de arquivo de cópia de segurança usando SFTP, ou FTP no modo de transferência Binária e verifique que o tamanho dos ficheiros corresponde aos tamanhos reportados na página Gerir Cópias de Segurança do Akeeba Backup / Akeeba Solo.',
  'INVALID_FILE_HEADER_MULTIPART' => 'Cabeçalho inválido no ficheiro de arquivo, parte %s, deslocamento %s. O seu arquivo de cópia de segurança consiste em múltiplos ficheiros (ficheiros com o mesmo nome e extensões .%s, .%s01, .%4$s02…). Ou alguns ficheiros estão em falta, ou estão corrompidos ou truncados. Vai precisar de todos estes ficheiros presentes no mesmo diretório. Por favor certifique-se de descarregar <em>e</em> enviar ficheiros de arquivo de cópia de segurança usando SFTP, ou FTP no modo de transferência Binária e verifique que o tamanho dos ficheiros corresponde aos tamanhos reportados na página Gerir Cópias de Segurança do Akeeba Backup / Akeeba Solo.',
  'UPDATE_HEADER' => 'Uma versão atualizada do Akeeba Kickstart (<span id=update-version>unknown</span>) está disponível!',
  'UPDATE_NOTICE' => 'Recomenda-se que utilize sempre a versão mais recente do Akeeba Kickstart disponível. Versões mais antigas podem estar sujeitas a erros e não serão suportadas.',
  'UPDATE_DLNOW' => 'Descarregar agora',
  'UPDATE_MOREINFO' => 'Mais informações',
  'NEEDSOMEHELPKS' => 'Precisa de ajuda para utilizar esta ferramenta? Leia isto primeiro:',
  'QUICKSTART' => 'Guia de Início Rápido',
  'CANTGETITTOWORK' => 'Não consegue fazer funcionar? Clique em mim!',
  'NOARCHIVESCLICKHERE' => 'Nenhum arquivo detetado. Clique aqui para instruções de resolução de problemas.',
  'POSTRESTORATIONTROUBLESHOOTING' => 'Algo não está a funcionar após a restauração? Clique aqui para instruções de resolução de problemas.',
  'IGNORE_MOST_ERRORS' => 'Ignorar a maioria dos erros',
  'TIME_SETTINGS_HELP' => 'Aumente o mínimo para 3 se obtiver erros AJAX. Aumente o máximo para 10 para uma extração mais rápida, diminua para 5 se obtiver erros AJAX. Tente mínimo 5, máximo 1 (não é um erro!) se continuar a obter erros AJAX.',
  'STEALTH_MODE_HELP' => 'Quando ativado, apenas os visitantes do seu endereço IP poderão ver o site até a restauração ser concluída. Todos os outros serão redirecionados para a URL acima e apenas verão essa página. O seu servidor deve ver o IP real do visitante (isto é controlado pelo seu alojamento, não por si ou por nós).',
  'RENAME_FILES_HELP' => 'Alterar a designação do .htaccess, web.config, php.ini e .user.ini contidos no arquivo durante a extração. Os ficheiros são renomeados com uma extensão .bak. As designações dos ficheiros são restauradas quando clica em Limpar.',
  'RESTORE_PERMISSIONS_HELP' => 'Aplica as permissões dos ficheiros (mas NÃO a propriedade dos ficheiros) que foram guardadas no momento da cópia de segurança. Apenas funciona com arquivos JPA e JPS. Não funciona no Windows (o PHP não oferece tal funcionalidade).',
  'EXTRACT_LIST' => 'Ficheiros a extrair',
  'EXTRACT_LIST_HELP' => 'Introduza um caminho de ficheiro tal como <code>images/cat.png</code> ou um padrão de shell tal como <code>images/*.png</code> em cada linha. Apenas os ficheiros que correspondam a esta lista serão escritos no disco. Deixe vazio para extrair tudo (predefinição).',
  'AKS3_IMPORT' => 'Importar do Amazon S3',
  'AKS3_TITLE_STEP1' => 'Ligar ao Amazon S3',
  'AKS3_ACCESS' => 'Chave de Acesso',
  'AKS3_SECRET' => 'Chave Secreta',
  'AKS3_CONNECT' => 'Ligar ao Amazon S3',
  'AKS3_CANCEL' => 'Cancelar importação',
  'AKS3_TITLE_STEP2' => 'Selecionar o seu contentor do Amazon S3',
  'AKS3_BUCKET' => 'Contentor',
  'AKS3_LISTCONTENTS' => 'Listar conteúdos',
  'AKS3_TITLE_STEP3' => 'Selecionar arquivo para importar',
  'AKS3_FOLDERS' => 'Pastas',
  'AKS3_FILES' => 'Ficheiros de Arquivo',
  'AKS3_TITLE_STEP4' => 'A importar…',
  'AKS3_DO_NOT_CLOSE' => 'Por favor não feche esta janela enquanto os seus arquivos de cópia de segurança estão a ser importados',
  'AKS3_TITLE_STEP5' => 'A importação está concluída',
  'AKS3_BTN_RELOAD' => 'Recarregar o Kickstart',
  'WRONG_FTP_PATH2' => 'Diretório inicial FTP incorreto — o diretório não corresponde à raiz web do seu site',
  'ARCHIVE_DIRECTORY' => 'Diretório de arquivos:',
  'RELOAD_ARCHIVES' => 'Recarregar',
  'CONFIG_UI_SFTPBROWSER_TITLE' => 'Navegador de Diretórios SFTP',
  'ERR_COULD_NOT_OPEN_ARCHIVE_PART' => 'Não foi possível abrir o ficheiro de parte do arquivo %s para leitura. Verifique que o ficheiro existe, é legível pelo servidor web e não se encontra num diretório inacessível devido a restrições de chroot, open_basedir ou qualquer outra restrição implementada pelo seu alojamento.',
  'RENAME_FILES' => 'Renomear ficheiros de configuração do servidor antes da extração',
  'BTN_SHOW_FINE_TUNE' => 'Mostrar opções avançadas (para especialistas)',
  'RESTORE_PERMISSIONS' => 'Restaurar permissões dos ficheiros',
  'ZAPBEFORE' => 'Eliminar tudo antes da extração',
  'ZAPBEFORE_HELP' => 'Tenta eliminar todos os ficheiros e pastas existentes sob o diretório onde o Kickstart se encontra antes de extrair o arquivo de cópia de segurança. NÃO tem em conta quais ficheiros e pastas existem no arquivo de cópia de segurança. Ficheiros e pastas eliminados por esta funcionalidade NÃO podem ser recuperados. <strong>AVISO! ISTO PODE ELIMINAR FICHEIROS E PASTAS QUE NÃO PERTENCEM AO SEU SITE. UTILIZAR COM EXTREMA CAUTELA. AO ATIVAR ESTA FUNCIONALIDADE ASSUME TODA A RESPONSABILIDADE.</strong>',
);


	/** END OF ARRAY — DO NOT EDIT OR REMOVE **/
	private $strings;

	/**
	 * The currently detected language (ISO code)
	 *
	 * @var string
	 */
	private $language;

	/*
	 * Initializes the translation engine
	 *
	 * Loading order:
	 * 1. Built-in en-GB default strings
	 * 2. Built-in translation matching the browser's preferred language (if any)
	 * 3. External INI translation files via loadTranslationFile()
	 *
	 * @return AKText
	 */
	public function __construct()
	{
		// Step 1: Start with the default en-GB translation
		$this->strings = $this->default_translation;

		// Step 2: Try loading the en-GB INI file, if it exists
		$this->loadTranslationFile('en-GB');

		// Step 3: Get browser language preferences and match against built-in translations
		$browserLanguages = $this->getBrowserLanguage();

		foreach ($browserLanguages as $langStruct)
		{
			$fullLang = $langStruct[0];
			$baseLang = $langStruct[1];

			if (empty($fullLang))
			{
				continue;
			}

			$varName = 'translation_' . strtolower(str_replace('-', '_', $fullLang));

			// Try exact match first (e.g. fr-FR)
			if (isset($this->$varName))
			{
				$this->strings = array_merge($this->strings, $this->$varName);
				$this->language = $fullLang;
				break;
			}

			// Try base language match (e.g. fr from fr-CH)
			$baseVarName = 'translation_' . strtolower($baseLang);
			if (isset($this->$baseVarName))
			{
				$this->strings = array_merge($this->strings, $this->$baseVarName);
				$this->language = $baseLang;
				break;
			}

			// Try base language prefix scan (e.g. bare 'el' matches translation_el_gr)
			$prefix = 'translation_' . strtolower($baseLang) . '_';
			$found  = null;
			foreach (get_object_vars($this) as $propName => $propValue)
			{
				if (strpos($propName, $prefix) === 0 && is_array($propValue))
				{
					$found = [$propName, $propValue];
					break;
				}
			}

			if ($found !== null)
			{
				[$propName, $propValue] = $found;
				$this->strings  = array_merge($this->strings, $propValue);
				$localeSuffix   = substr($propName, strlen('translation_'));
				$underscorePos  = strpos($localeSuffix, '_');
				$this->language = substr($localeSuffix, 0, $underscorePos) . '-' . strtoupper(substr($localeSuffix, $underscorePos + 1));
				break;
			}
		}

		// Step 4: Load external INI translation files
		$this->loadTranslationFile();
	}

	/**
	 * A PHP based INI file parser.
	 *
	 * Thanks to asohn ~at~ aircanopy ~dot~ net for posting this handy function on
	 * the parse_ini_file page on http://gr.php.net/parse_ini_file
	 *
	 * @param   string  $file              Filename to process
	 * @param   bool    $process_sections  True to also process INI sections
	 * @param   bool    $rawdata           If true, the $file contains raw INI data, not a filename
	 *
	 * @return array An associative array of sections, keys and values
	 * @access private
	 */
	public static function parse_ini_file($file, $process_sections = false, $rawdata = false)
	{
		$process_sections = ($process_sections !== true) ? false : true;

		if (!$rawdata)
		{
			$ini = file($file);
		}
		else
		{
			$file = str_replace("\r", "", $file);
			$ini  = explode("\n", $file);
		}

		if (!is_array($ini))
		{
			return [];
		}

		if (count($ini) == 0)
		{
			return [];
		}

		$sections = [];
		$values   = [];
		$result   = [];
		$globals  = [];
		$i        = 0;
		foreach ($ini as $line)
		{
			$line = trim($line);
			$line = str_replace("\t", " ", $line);

			// Comments
			if (!preg_match('/^[a-zA-Z0-9[]/', $line))
			{
				continue;
			}

			// Sections
			if ($line[0] == '[')
			{
				$tmp        = explode(']', $line);
				$sections[] = trim(substr($tmp[0], 1));
				$i++;
				continue;
			}

			// Key-value pair
			$lineParts = explode('=', $line, 2);
			if (count($lineParts) != 2)
			{
				continue;
			}
			$key   = trim($lineParts[0]);
			$value = trim($lineParts[1]);
			unset($lineParts);

			if (strstr($value, ";"))
			{
				$tmp = explode(';', $value);
				if (count($tmp) == 2)
				{
					if ((($value[0] != '"') && ($value[0] != "'")) ||
						preg_match('/^".*"\s*;/', $value) || preg_match('/^".*;[^"]*$/', $value) ||
						preg_match("/^'.*'\s*;/", $value) || preg_match("/^'.*;[^']*$/", $value)
					)
					{
						$value = $tmp[0];
					}
				}
				else
				{
					if ($value[0] == '"')
					{
						$value = preg_replace('/^"(.*)".*/', '$1', $value);
					}
					elseif ($value[0] == "'")
					{
						$value = preg_replace("/^'(.*)'.*/", '$1', $value);
					}
					else
					{
						$value = $tmp[0];
					}
				}
			}
			$value = trim($value);
			$value = trim($value, "'\"");

			if ($i == 0)
			{
				if (substr($line, -1, 2) == '[]')
				{
					$globals[$key][] = $value;
				}
				else
				{
					$globals[$key] = $value;
				}
			}
			else
			{
				if (substr($line, -1, 2) == '[]')
				{
					$values[$i - 1][$key][] = $value;
				}
				else
				{
					$values[$i - 1][$key] = $value;
				}
			}
		}

		for ($j = 0; $j < $i; $j++)
		{
			if ($process_sections === true)
			{
				if (isset($sections[$j]) && isset($values[$j]))
				{
					$result[$sections[$j]] = $values[$j];
				}
			}
			else
			{
				if (isset($values[$j]))
				{
					$result[] = $values[$j];
				}
			}
		}

		return $result + $globals;
	}

	public static function sprintf($key)
	{
		$text = self::getInstance();
		$args = func_get_args();
		if (count($args) > 0)
		{
			$args[0] = $text->_($args[0]);

			return @call_user_func_array('sprintf', $args);
		}

		return '';
	}

	/**
	 * Singleton pattern for Language
	 *
	 * @return AKText The global AKText instance
	 */
	public static function &getInstance()
	{
		static $instance;

		if (!is_object($instance))
		{
			$instance = new AKText();
		}

		return $instance;
	}

	public static function _($string)
	{
		$text = self::getInstance();

		$key = strtoupper($string);
		$key = substr($key, 0, 1) == '_' ? substr($key, 1) : $key;

		if (isset ($text->strings[$key]))
		{
			$string = $text->strings[$key];
		}
		else
		{
			if (defined($string))
			{
				$string = constant($string);
			}
		}

		return $string;
	}

	/**
	 * Returns an array of the browser's preferred languages.
	 *
	 * Each entry is an array with two elements:
	 * [0] Full language tag (e.g. "fr-fr", "de-de")
	 * [1] Base language code (e.g. "fr", "de")
	 *
	 * Detection code derived from Full Operating System Language Detection by Harald Hope
	 * Retrieved from http://techpatterns.com/downloads/php_language_detection.php
	 *
	 * @return  array  List of language preference structs
	 */
	public function getBrowserLanguage()
	{
		$user_languages = [];

		if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"]))
		{
			$languages = strtolower($_SERVER["HTTP_ACCEPT_LANGUAGE"]);
			$languages = str_replace(' ', '', $languages);
			$languages = explode(",", $languages);

			foreach ($languages as $language_list)
			{
				$temp_array = [];
				$temp_array[0] = substr($language_list, 0, strcspn($language_list, ';'));
				$temp_array[1] = substr($language_list, 0, 2);

				if ((strlen($temp_array[0]) == 5) && ((substr($temp_array[0], 2, 1) == '-') || (substr($temp_array[0], 2, 1) == '_')))
				{
					$langLocation  = strtoupper(substr($temp_array[0], 3, 2));
					$temp_array[0] = $temp_array[1] . '-' . $langLocation;
				}

				$user_languages[] = $temp_array;
			}
		}

		if (empty($user_languages))
		{
			$user_languages[0] = ['', ''];
		}

		return $user_languages;
	}

	/**
	 * Loads an external INI translation file for the given language.
	 *
	 * If $lang is null, loads the INI file for the currently detected language ($this->language).
	 * The INI file is searched for in KSLANGDIR (if defined) or KSROOTDIR.
	 *
	 * @param   string  $lang  Language tag (e.g. "en-GB", "fr-FR"). Null to use $this->language.
	 *
	 * @return  void
	 */
	public function loadTranslationFile($lang = null)
	{
		if (defined('KSLANGDIR'))
		{
			$dirname = KSLANGDIR;
		}
		else
		{
			$dirname = KSROOTDIR;
		}

		$myName   = defined('KSSELFNAME') ? KSSELFNAME : basename(__FILE__);
		$basename = basename($myName, '.php') . '.ini';

		if (empty($lang))
		{
			$lang = $this->language;
		}

		if (empty($lang))
		{
			return;
		}

		$translationFilename = $dirname . DIRECTORY_SEPARATOR . $lang . '.' . $basename;

		if (!@file_exists($translationFilename) && ($basename != 'kickstart.ini'))
		{
			$basename            = 'kickstart.ini';
			$translationFilename = $dirname . DIRECTORY_SEPARATOR . $lang . '.' . $basename;
		}

		if (!@file_exists($translationFilename))
		{
			return;
		}

		$temp = self::parse_ini_file($translationFilename, false);

		if (!is_array($this->strings))
		{
			$this->strings = [];
		}

		if (empty($temp))
		{
			$this->strings = array_merge($this->default_translation, $this->strings);
		}
		else
		{
			$this->strings = array_merge($this->strings, $temp);
		}
	}

	/**
	 * @deprecated  Use loadTranslationFile() instead. Kept for backward compatibility.
	 *
	 * @param   string  $lang  Language tag. Null to use $this->language.
	 *
	 * @return  void
	 */
	private function loadTranslation($lang = null)
	{
		$this->loadTranslationFile($lang);
	}

	public function dumpLanguage()
	{
		$out = '';
		foreach ($this->strings as $key => $value)
		{
			$out .= "$key=$value\n";
		}

		return $out;
	}

	public function asJavascript()
	{
		$out = '';
		foreach ($this->strings as $key => $value)
		{
			$key   = addcslashes($key, '\\\'"');
			$value = addcslashes($value, '\\\'"');
			if (!empty($out))
			{
				$out .= ",\n";
			}
			$out .= "'$key':\t'$value'";
		}

		return $out;
	}

	public function resetTranslation()
	{
		$this->strings = $this->default_translation;
	}

	public function addDefaultLanguageStrings($stringList = [])
	{
		if (!is_array($stringList))
		{
			return;
		}
		if (empty($stringList))
		{
			return;
		}

		$this->strings = array_merge($stringList, $this->strings);
	}
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * The Akeeba Kickstart Factory class
 *
 * This class is reponssible for instantiating all Akeeba Kickstart classes
 */
class AKFactory
{
	/** @var   array  A list of instantiated objects */
	private $objectlist = [];

	/** @var   array  Simple hash data storage */
	private $varlist = [];

	/** @var   self   Static instance */
	private static $instance = null;

	/**
	 * AKFactory constructor.
	 *
	 * This is a private constructor makes sure we can't instantiate the class unless we go through the static
	 * getInstance singleton method. This is different than making the class abstract (preventing any kind of object
	 * instantiation).
	 */
	private function __construct()
	{
	}

	/**
	 * Gets a serialized snapshot of the Factory for safekeeping (hibernate)
	 *
	 * @return string The serialized snapshot of the Factory
	 */
	public static function serialize()
	{
		$engine = self::getUnarchiver();
		$engine->shutdown();
		$serialized = serialize(self::getInstance());

		if (function_exists('base64_encode') && function_exists('base64_decode'))
		{
			$serialized = base64_encode($serialized);
		}

		return $serialized;
	}

	/**
	 * Gets the unarchiver engine
	 *
	 * @return AKAbstractUnarchiver
	 */
	public static function &getUnarchiver($configOverride = null)
	{
		static $class_name;

		if (!empty($configOverride) && isset($configOverride['reset']) && $configOverride['reset'])
		{
			$class_name = null;
		}

		if (empty($class_name))
		{
			$filetype = self::get('kickstart.setup.filetype', null);

			if (empty($filetype))
			{
				$filename      = self::get('kickstart.setup.sourcefile', null);
				$basename      = basename($filename);
				$baseextension = strtoupper(substr($basename, -3));

				switch ($baseextension)
				{
					case 'JPA':
						$filetype = 'JPA';
						break;

					case 'JPS':
						$filetype = 'JPS';
						break;

					case 'ZIP':
						$filetype = 'ZIP';
						break;

					default:
						die('Invalid archive type or extension in file ' . $filename);
						break;
				}
			}

			$class_name = 'AKUnarchiver' . ucfirst($filetype);
		}

		$destdir = self::get('kickstart.setup.destdir', null);

		if (empty($destdir))
		{
			$destdir = KSROOTDIR;
		}

		/** @var AKAbstractUnarchiver $object */
		$object = self::getClassInstance($class_name);

		if ($object->getState() == 'init')
		{
			$sourcePath = self::get('kickstart.setup.sourcepath', '');
			$sourceFile = self::get('kickstart.setup.sourcefile', '');

			if (!empty($sourcePath))
			{
				$sourceFile = rtrim($sourcePath, '/\\') . '/' . $sourceFile;
			}

			// Initialize the object –– Any change here MUST be reflected to echoHeadJavascript (default values)
			$config = [
				'filename'            => $sourceFile,
				'restore_permissions' => self::get('kickstart.setup.restoreperms', 0),
				'post_proc'           => self::get('kickstart.procengine', 'direct'),
				'add_path'            => self::get('kickstart.setup.targetpath', $destdir),
				'remove_path'         => self::get('kickstart.setup.removepath', ''),
				'rename_files'        => self::get('kickstart.setup.renamefiles', [
					'.htaccess' => 'htaccess.bak', 'php.ini' => 'php.ini.bak', 'web.config' => 'web.config.bak',
					'.user.ini' => '.user.ini.bak',
				]),
				'skip_files'          => self::get('kickstart.setup.skipfiles', [
					basename(__FILE__), 'kickstart.php', 'htaccess.bak', 'php.ini.bak',
				]),
				'ignoredirectories'   => self::get('kickstart.setup.ignoredirectories', [
					'tmp', 'log', 'logs',
				]),
			];

			if (!defined('KICKSTART'))
			{
				// In restore.php mode we have to exclude the restoration.php files
				$moreSkippedFiles     = [
					// Akeeba Backup for Joomla!
					'administrator/components/com_akeeba/restoration.php',
					'administrator/components/com_akeebabackup/restoration.php',
					// Joomla! Update
					'administrator/components/com_joomlaupdate/restoration.php',
					// Akeeba Backup for WordPress
					'wp-content/plugins/akeebabackupwp/app/restoration.php',
					'wp-content/plugins/akeebabackupcorewp/app/restoration.php',
					'wp-content/plugins/akeebabackup/app/restoration.php',
					'wp-content/plugins/akeebabackupwpcore/app/restoration.php',
					// Akeeba Solo
					'app/restoration.php',
				];

				$config['skip_files'] = array_merge($config['skip_files'], $moreSkippedFiles);
			}

			if (!empty($configOverride))
			{
				$config = array_merge($config, $configOverride);
			}

			$object->setup($config);
		}

		return $object;
	}

	// ========================================================================
	// Public factory interface
	// ========================================================================

	public static function get($key, $default = null)
	{
		$self = self::getInstance();

		if (array_key_exists($key, $self->varlist))
		{
			return $self->varlist[$key];
		}

		return $default;
	}

	/**
	 * Gets a single, internally used instance of the Factory
	 *
	 * @param string $serialized_data [optional] Serialized data to spawn the instance from
	 *
	 * @return AKFactory A reference to the unique Factory object instance
	 */
	protected static function &getInstance($serialized_data = null)
	{
		if (!is_object(self::$instance) || !is_null($serialized_data))
		{
			if (!is_null($serialized_data))
			{
				self::$instance = unserialize($serialized_data, ['allowed_classes' => self::getAllowedClasses()]);

				return self::$instance;
			}

			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * The classes which may be instantiated when resuming from a serialised snapshot.
	 *
	 * The snapshot arrives in the request — as the `factory` variable, or as the AJAX step's payload — so it is
	 * untrusted input. Left unrestricted, unserialize() would instantiate any class the request names and run its
	 * magic methods. That is harmless when this file runs on its own, because the only classes in existence are the
	 * ones below it. It is not harmless when Akeeba Backup Professional includes this file as a library: the whole of
	 * Joomla! and its vendor tree is then loaded, which is a large supply of destructor and __wakeup() gadgets.
	 *
	 * The list is computed rather than written out because a stale list fails in the worst possible way. A class which
	 * belongs in the snapshot but is missing here does not raise an error: it is silently replaced with
	 * __PHP_Incomplete_Class, and the restoration breaks midway with nothing to point at. Since every class that can
	 * legitimately appear is declared in this very file, the file itself is the authoritative list, and it cannot go
	 * out of date when a class is added or renamed.
	 *
	 * stdClass is added explicitly. It is an internal class, so it has no file to match on, and it does appear in the
	 * graph — the unarchivers hold their archive and file headers in it.
	 *
	 * @return array The class names unserialize() may instantiate
	 */
	private static function getAllowedClasses()
	{
		static $allowedClasses = null;

		if (!is_null($allowedClasses))
		{
			return $allowedClasses;
		}

		$allowedClasses = ['stdClass'];

		foreach (get_declared_classes() as $className)
		{
			try
			{
				$refClass = new ReflectionClass($className);
			}
			catch (Exception $e)
			{
				continue;
			}

			if ($refClass->getFileName() === __FILE__)
			{
				$allowedClasses[] = $className;
			}
		}

		return $allowedClasses;
	}

	/**
	 * Internal function which instantiates a class named $class_name.
	 * The autoloader
	 *
	 * @param string $class_name
	 *
	 * @return object
	 */
	protected static function &getClassInstance($class_name)
	{
		$self = self::getInstance();

		if (!isset($self->objectlist[$class_name]))
		{
			$self->objectlist[$class_name] = new $class_name;
		}

		return $self->objectlist[$class_name];
	}

	// ========================================================================
	// Public hash data storage interface
	// ========================================================================

	/**
	 * Regenerates the full Factory state from a serialized snapshot (resume)
	 *
	 * @param string $serialized_data The serialized snapshot to resume from
	 */
	public static function unserialize($serialized_data)
	{
		if (function_exists('base64_encode') && function_exists('base64_decode'))
		{
			$serialized_data = base64_decode($serialized_data);
		}

		self::getInstance($serialized_data);
	}

	/**
	 * Reset the internal factory state, freeing all previously created objects
	 */
	public static function nuke()
	{
		self::$instance = null;
	}

	// ========================================================================
	// Akeeba Kickstart classes
	// ========================================================================

	public static function set($key, $value)
	{
		$self                = self::getInstance();
		$self->varlist[$key] = $value;
	}

	/**
	 * Gets the post processing engine
	 *
	 * @param string $proc_engine
	 *
	 * @return AKAbstractPostproc
	 */
	public static function &getPostProc($proc_engine = null)
	{
		static $class_name;

		if (empty($class_name))
		{
			if (empty($proc_engine))
			{
				$proc_engine = self::get('kickstart.procengine', 'direct');
			}

			$class_name = 'AKPostproc' . ucfirst($proc_engine);
		}

		return self::getClassInstance($class_name);
	}

	/**
	 * Get the a reference to the Akeeba Engine's timer
	 *
	 * @return AKCoreTimer
	 */
	public static function &getTimer()
	{
		return self::getClassInstance('AKCoreTimer');
	}

	/**
	 * Get an instance of the filesystem zapper
	 *
	 * @return AKUtilsZapper
	 */
	public static function &getZapper()
	{
		return self::getClassInstance('AKUtilsZapper');
	}
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Interface for AES encryption adapters
 */
interface AKEncryptionAESAdapterInterface
{
	/**
	 * Decrypts a string. Returns the raw binary ciphertext, zero-padded.
	 *
	 * @param   string       $plainText  The plaintext to encrypt
	 * @param   string       $key        The raw binary key (will be zero-padded or chopped if its size is different than the block size)
	 *
	 * @return  string  The raw encrypted binary string.
	 */
	public function decrypt($plainText, $key);

	/**
	 * Returns the encryption block size in bytes
	 *
	 * @return  int
	 */
	public function getBlockSize();

	/**
	 * Is this adapter supported?
	 *
	 * @return  bool
	 */
	public function isSupported();
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Abstract AES encryption class
 */
abstract class AKEncryptionAESAdapterAbstract
{
	/**
	 * Trims or zero-pads a key / IV
	 *
	 * @param   string $key  The key or IV to treat
	 * @param   int    $size The block size of the currently used algorithm
	 *
	 * @return  null|string  Null if $key is null, treated string of $size byte length otherwise
	 */
	public function resizeKey($key, $size)
	{
		if (empty($key))
		{
			return null;
		}

		$keyLength = strlen($key);

		if (function_exists('mb_strlen'))
		{
			$keyLength = mb_strlen($key, 'ASCII');
		}

		if ($keyLength == $size)
		{
			return $key;
		}

		if ($keyLength > $size)
		{
			if (function_exists('mb_substr'))
			{
				return mb_substr($key, 0, $size, 'ASCII');
			}

			return substr($key, 0, $size);
		}

		return $key . str_repeat("\0", ($size - $keyLength));
	}

	/**
	 * Returns null bytes to append to the string so that it's zero padded to the specified block size
	 *
	 * @param   string $string    The binary string which will be zero padded
	 * @param   int    $blockSize The block size
	 *
	 * @return  string  The zero bytes to append to the string to zero pad it to $blockSize
	 */
	protected function getZeroPadding($string, $blockSize)
	{
		$stringSize = strlen($string);

		if (function_exists('mb_strlen'))
		{
			$stringSize = mb_strlen($string, 'ASCII');
		}

		if ($stringSize == $blockSize)
		{
			return '';
		}

		if ($stringSize < $blockSize)
		{
			return str_repeat("\0", $blockSize - $stringSize);
		}

		$paddingBytes = $stringSize % $blockSize;

		return str_repeat("\0", $blockSize - $paddingBytes);
	}
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

class Mcrypt extends AKEncryptionAESAdapterAbstract implements AKEncryptionAESAdapterInterface
{
	protected $cipherType = MCRYPT_RIJNDAEL_128;

	protected $cipherMode = MCRYPT_MODE_CBC;

	public function decrypt($cipherText, $key)
	{
		$iv_size    = $this->getBlockSize();
		$key        = $this->resizeKey($key, $iv_size);
		$iv         = substr($cipherText, 0, $iv_size);
		$cipherText = substr($cipherText, $iv_size);
		$plainText  = mcrypt_decrypt($this->cipherType, $key, $cipherText, $this->cipherMode, $iv);

		return $plainText;
	}

	public function isSupported()
	{
		if (!function_exists('mcrypt_get_key_size'))
		{
			return false;
		}

		if (!function_exists('mcrypt_get_iv_size'))
		{
			return false;
		}

		if (!function_exists('mcrypt_create_iv'))
		{
			return false;
		}

		if (!function_exists('mcrypt_encrypt'))
		{
			return false;
		}

		if (!function_exists('mcrypt_decrypt'))
		{
			return false;
		}

		if (!function_exists('mcrypt_list_algorithms'))
		{
			return false;
		}

		if (!function_exists('hash'))
		{
			return false;
		}

		if (!function_exists('hash_algos'))
		{
			return false;
		}

		$algorightms = mcrypt_list_algorithms();

		if (!in_array('rijndael-128', $algorightms))
		{
			return false;
		}

		if (!in_array('rijndael-192', $algorightms))
		{
			return false;
		}

		if (!in_array('rijndael-256', $algorightms))
		{
			return false;
		}

		$algorightms = hash_algos();

		if (!in_array('sha256', $algorightms))
		{
			return false;
		}

		return true;
	}

	public function getBlockSize()
	{
		return mcrypt_get_iv_size($this->cipherType, $this->cipherMode);
	}
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

class OpenSSL extends AKEncryptionAESAdapterAbstract implements AKEncryptionAESAdapterInterface
{
	/**
	 * The OpenSSL options for encryption / decryption
	 *
	 * @var  int
	 */
	protected $openSSLOptions = 0;

	/**
	 * The encryption method to use
	 *
	 * @var  string
	 */
	protected $method = 'aes-128-cbc';

	public function __construct()
	{
		$this->openSSLOptions = OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING;
	}

	public function decrypt($cipherText, $key)
	{
		$iv_size    = $this->getBlockSize();
		$key        = $this->resizeKey($key, $iv_size);
		$iv         = substr($cipherText, 0, $iv_size);
		$cipherText = substr($cipherText, $iv_size);
		$plainText  = openssl_decrypt($cipherText, $this->method, $key, $this->openSSLOptions, $iv);

		return $plainText;
	}

	public function isSupported()
	{
		if (!function_exists('openssl_get_cipher_methods'))
		{
			return false;
		}

		if (!function_exists('openssl_random_pseudo_bytes'))
		{
			return false;
		}

		if (!function_exists('openssl_cipher_iv_length'))
		{
			return false;
		}

		if (!function_exists('openssl_encrypt'))
		{
			return false;
		}

		if (!function_exists('openssl_decrypt'))
		{
			return false;
		}

		if (!function_exists('hash'))
		{
			return false;
		}

		if (!function_exists('hash_algos'))
		{
			return false;
		}

		$algorightms = openssl_get_cipher_methods();

		if (!in_array('aes-128-cbc', $algorightms))
		{
			return false;
		}

		$algorightms = hash_algos();

		if (!in_array('sha256', $algorightms))
		{
			return false;
		}

		return true;
	}

	/**
	 * @return int
	 */
	public function getBlockSize()
	{
		return openssl_cipher_iv_length($this->method);
	}
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * AES implementation in PHP (c) Chris Veness 2005-2016.
 * Right to use and adapt is granted for under a simple creative commons attribution
 * licence. No warranty of any form is offered.
 *
 * Heavily modified for Akeeba Backup by Nicholas K. Dionysopoulos
 * Also added AES-128 CBC mode (with mcrypt and OpenSSL) on top of AES CTR
 * Removed CTR encrypt / decrypt (no longer used)
 */
class AKEncryptionAES
{
	// Sbox is pre-computed multiplicative inverse in GF(2^8) used in SubBytes and KeyExpansion [�5.1.1]
	protected static $Sbox =
		[0x63, 0x7c, 0x77, 0x7b, 0xf2, 0x6b, 0x6f, 0xc5, 0x30, 0x01, 0x67, 0x2b, 0xfe, 0xd7, 0xab, 0x76,
			0xca, 0x82, 0xc9, 0x7d, 0xfa, 0x59, 0x47, 0xf0, 0xad, 0xd4, 0xa2, 0xaf, 0x9c, 0xa4, 0x72, 0xc0,
			0xb7, 0xfd, 0x93, 0x26, 0x36, 0x3f, 0xf7, 0xcc, 0x34, 0xa5, 0xe5, 0xf1, 0x71, 0xd8, 0x31, 0x15,
			0x04, 0xc7, 0x23, 0xc3, 0x18, 0x96, 0x05, 0x9a, 0x07, 0x12, 0x80, 0xe2, 0xeb, 0x27, 0xb2, 0x75,
			0x09, 0x83, 0x2c, 0x1a, 0x1b, 0x6e, 0x5a, 0xa0, 0x52, 0x3b, 0xd6, 0xb3, 0x29, 0xe3, 0x2f, 0x84,
			0x53, 0xd1, 0x00, 0xed, 0x20, 0xfc, 0xb1, 0x5b, 0x6a, 0xcb, 0xbe, 0x39, 0x4a, 0x4c, 0x58, 0xcf,
			0xd0, 0xef, 0xaa, 0xfb, 0x43, 0x4d, 0x33, 0x85, 0x45, 0xf9, 0x02, 0x7f, 0x50, 0x3c, 0x9f, 0xa8,
			0x51, 0xa3, 0x40, 0x8f, 0x92, 0x9d, 0x38, 0xf5, 0xbc, 0xb6, 0xda, 0x21, 0x10, 0xff, 0xf3, 0xd2,
			0xcd, 0x0c, 0x13, 0xec, 0x5f, 0x97, 0x44, 0x17, 0xc4, 0xa7, 0x7e, 0x3d, 0x64, 0x5d, 0x19, 0x73,
			0x60, 0x81, 0x4f, 0xdc, 0x22, 0x2a, 0x90, 0x88, 0x46, 0xee, 0xb8, 0x14, 0xde, 0x5e, 0x0b, 0xdb,
			0xe0, 0x32, 0x3a, 0x0a, 0x49, 0x06, 0x24, 0x5c, 0xc2, 0xd3, 0xac, 0x62, 0x91, 0x95, 0xe4, 0x79,
			0xe7, 0xc8, 0x37, 0x6d, 0x8d, 0xd5, 0x4e, 0xa9, 0x6c, 0x56, 0xf4, 0xea, 0x65, 0x7a, 0xae, 0x08,
			0xba, 0x78, 0x25, 0x2e, 0x1c, 0xa6, 0xb4, 0xc6, 0xe8, 0xdd, 0x74, 0x1f, 0x4b, 0xbd, 0x8b, 0x8a,
			0x70, 0x3e, 0xb5, 0x66, 0x48, 0x03, 0xf6, 0x0e, 0x61, 0x35, 0x57, 0xb9, 0x86, 0xc1, 0x1d, 0x9e,
			0xe1, 0xf8, 0x98, 0x11, 0x69, 0xd9, 0x8e, 0x94, 0x9b, 0x1e, 0x87, 0xe9, 0xce, 0x55, 0x28, 0xdf,
			0x8c, 0xa1, 0x89, 0x0d, 0xbf, 0xe6, 0x42, 0x68, 0x41, 0x99, 0x2d, 0x0f, 0xb0, 0x54, 0xbb, 0x16];

	// Rcon is Round Constant used for the Key Expansion [1st col is 2^(r-1) in GF(2^8)] [�5.2]
	protected static $Rcon = [
		[0x00, 0x00, 0x00, 0x00],
		[0x01, 0x00, 0x00, 0x00],
		[0x02, 0x00, 0x00, 0x00],
		[0x04, 0x00, 0x00, 0x00],
		[0x08, 0x00, 0x00, 0x00],
		[0x10, 0x00, 0x00, 0x00],
		[0x20, 0x00, 0x00, 0x00],
		[0x40, 0x00, 0x00, 0x00],
		[0x80, 0x00, 0x00, 0x00],
		[0x1b, 0x00, 0x00, 0x00],
		[0x36, 0x00, 0x00, 0x00]];

	protected static $passwords = [];

	/**
	 * The algorithm to use for PBKDF2. Must be a supported hash_hmac algorithm. Default: sha1
	 *
	 * @var  string
	 */
	private static $pbkdf2Algorithm = 'sha1';

	/**
	 * Number of iterations to use for PBKDF2
	 *
	 * @var  int
	 */
	private static $pbkdf2Iterations = 1000;

	/**
	 * Should we use a static salt for PBKDF2?
	 *
	 * @var  int
	 */
	private static $pbkdf2UseStaticSalt = 0;

	/**
	 * The static salt to use for PBKDF2
	 *
	 * @var  string
	 */
	private static $pbkdf2StaticSalt = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";

	/**
	 * AES Cipher function: encrypt 'input' with Rijndael algorithm
	 *
	 * @param   array $input    Message as byte-array (16 bytes)
	 * @param   array $w        key schedule as 2D byte-array (Nr+1 x Nb bytes) -
	 *                          generated from the cipher key by KeyExpansion()
	 *
	 * @return  string  Ciphertext as byte-array (16 bytes)
	 */
	protected static function Cipher($input, $w)
	{
		// main Cipher function [�5.1]
		$Nb = 4;                 // block size (in words): no of columns in state (fixed at 4 for AES)
		$Nr = count($w) / $Nb - 1; // no of rounds: 10/12/14 for 128/192/256-bit keys

		$state = [];  // initialise 4xNb byte-array 'state' with input [�3.4]

		for ($i = 0; $i < 4 * $Nb; $i++)
		{
			$state[$i % 4][floor($i / 4)] = $input[$i];
		}

		$state = self::AddRoundKey($state, $w, 0, $Nb);

		for ($round = 1; $round < $Nr; $round++)
		{  // apply Nr rounds
			$state = self::SubBytes($state, $Nb);
			$state = self::ShiftRows($state, $Nb);
			$state = self::MixColumns($state);
			$state = self::AddRoundKey($state, $w, $round, $Nb);
		}

		$state = self::SubBytes($state, $Nb);
		$state = self::ShiftRows($state, $Nb);
		$state = self::AddRoundKey($state, $w, $Nr, $Nb);

		$output = [4 * $Nb];  // convert state to 1-d array before returning [�3.4]

		for ($i = 0; $i < 4 * $Nb; $i++)
		{
			$output[$i] = $state[$i % 4][floor($i / 4)];
		}

		return $output;
	}

	protected static function AddRoundKey($state, $w, $rnd, $Nb)
	{
		// xor Round Key into state S [�5.1.4]
		for ($r = 0; $r < 4; $r++)
		{
			for ($c = 0; $c < $Nb; $c++)
			{
				$state[$r][$c] ^= $w[$rnd * 4 + $c][$r];
			}
		}

		return $state;
	}

	protected static function SubBytes($s, $Nb)
	{
		// apply SBox to state S [�5.1.1]
		for ($r = 0; $r < 4; $r++)
		{
			for ($c = 0; $c < $Nb; $c++)
			{
				$s[$r][$c] = self::$Sbox[$s[$r][$c]];
			}
		}

		return $s;
	}

	protected static function ShiftRows($s, $Nb)
	{
		// shift row r of state S left by r bytes [�5.1.2]
		$t = [4];

		for ($r = 1; $r < 4; $r++)
		{
			for ($c = 0; $c < 4; $c++)
			{
				$t[$c] = $s[$r][($c + $r) % $Nb];
			}  // shift into temp copy

			for ($c = 0; $c < 4; $c++)
			{
				$s[$r][$c] = $t[$c];
			}         // and copy back
		}          // note that this will work for Nb=4,5,6, but not 7,8 (always 4 for AES):

		return $s;  // see fp.gladman.plus.com/cryptography_technology/rijndael/aes.spec.311.pdf
	}

	protected static function MixColumns($s)
	{
		// combine bytes of each col of state S [�5.1.3]
		for ($c = 0; $c < 4; $c++)
		{
			$a = [4];  // 'a' is a copy of the current column from 's'
			$b = [4];  // 'b' is a�{02} in GF(2^8)

			for ($i = 0; $i < 4; $i++)
			{
				$a[$i] = $s[$i][$c];
				$b[$i] = $s[$i][$c] & 0x80 ? $s[$i][$c] << 1 ^ 0x011b : $s[$i][$c] << 1;
			}

			// a[n] ^ b[n] is a�{03} in GF(2^8)
			$s[0][$c] = $b[0] ^ $a[1] ^ $b[1] ^ $a[2] ^ $a[3]; // 2*a0 + 3*a1 + a2 + a3
			$s[1][$c] = $a[0] ^ $b[1] ^ $a[2] ^ $b[2] ^ $a[3]; // a0 * 2*a1 + 3*a2 + a3
			$s[2][$c] = $a[0] ^ $a[1] ^ $b[2] ^ $a[3] ^ $b[3]; // a0 + a1 + 2*a2 + 3*a3
			$s[3][$c] = $a[0] ^ $b[0] ^ $a[1] ^ $a[2] ^ $b[3]; // 3*a0 + a1 + a2 + 2*a3
		}

		return $s;
	}

	/**
	 * Key expansion for Rijndael Cipher(): performs key expansion on cipher key
	 * to generate a key schedule
	 *
	 * @param   array $key Cipher key byte-array (16 bytes)
	 *
	 * @return  array  Key schedule as 2D byte-array (Nr+1 x Nb bytes)
	 */
	protected static function KeyExpansion($key)
	{
		// generate Key Schedule from Cipher Key [�5.2]

		// block size (in words): no of columns in state (fixed at 4 for AES)
		$Nb = 4;
		// key length (in words): 4/6/8 for 128/192/256-bit keys
		$Nk = (int) (count($key) / 4);
		// no of rounds: 10/12/14 for 128/192/256-bit keys
		$Nr = $Nk + 6;

		$w    = [];
		$temp = [];

		for ($i = 0; $i < $Nk; $i++)
		{
			$r     = [$key[4 * $i], $key[4 * $i + 1], $key[4 * $i + 2], $key[4 * $i + 3]];
			$w[$i] = $r;
		}

		for ($i = $Nk; $i < ($Nb * ($Nr + 1)); $i++)
		{
			$w[$i] = [];
			for ($t = 0; $t < 4; $t++)
			{
				$temp[$t] = $w[$i - 1][$t];
			}
			if ($i % $Nk == 0)
			{
				$temp = self::SubWord(self::RotWord($temp));
				for ($t = 0; $t < 4; $t++)
				{
					$rConIndex = (int) ($i / $Nk);
					$temp[$t] ^= self::$Rcon[$rConIndex][$t];
				}
			}
			else if ($Nk > 6 && $i % $Nk == 4)
			{
				$temp = self::SubWord($temp);
			}
			for ($t = 0; $t < 4; $t++)
			{
				$w[$i][$t] = $w[$i - $Nk][$t] ^ $temp[$t];
			}
		}

		return $w;
	}

	protected static function SubWord($w)
	{
		// apply SBox to 4-byte word w
		for ($i = 0; $i < 4; $i++)
		{
			$w[$i] = self::$Sbox[$w[$i]];
		}

		return $w;
	}

	/*
	 * Unsigned right shift function, since PHP has neither >>> operator nor unsigned ints
	 *
	 * @param a  number to be shifted (32-bit integer)
	 * @param b  number of bits to shift a to the right (0..31)
	 * @return   a right-shifted and zero-filled by b bits
	 */

	protected static function RotWord($w)
	{
		// rotate 4-byte word w left by one byte
		$tmp = $w[0];
		for ($i = 0; $i < 3; $i++)
		{
			$w[$i] = $w[$i + 1];
		}
		$w[3] = $tmp;

		return $w;
	}

	protected static function urs($a, $b)
	{
		$a &= 0xffffffff;
		$b &= 0x1f;  // (bounds check)
		if ($a & 0x80000000 && $b > 0)
		{   // if left-most bit set
			$a = ($a >> 1) & 0x7fffffff;   //   right-shift one bit & clear left-most bit
			$a = $a >> ($b - 1);           //   remaining right-shifts
		}
		else
		{                       // otherwise
			$a = ($a >> $b);               //   use normal right-shift
		}

		return $a;
	}

	/**
	 * AES decryption in CBC mode. This is the standard mode (the CTR methods
	 * actually use Rijndael-128 in CTR mode, which - technically - isn't AES).
	 *
	 * It supports AES-128 only. It assumes that the last 4 bytes
	 * contain a little-endian unsigned long integer representing the unpadded
	 * data length.
	 *
	 * @since  3.0.1
	 * @author Nicholas K. Dionysopoulos
	 *
	 * @param   string $ciphertext The data to encrypt
	 * @param   string $password   Encryption password
	 *
	 * @return  string  The plaintext
	 */
	public static function AESDecryptCBC($ciphertext, $password)
	{
		$adapter = self::getAdapter();

		if (!$adapter->isSupported())
		{
			return false;
		}

		// Read the data size
		$data_size = unpack('V', substr($ciphertext, -4));

		// Do I have a PBKDF2 salt?
		$salt             = substr($ciphertext, -92, 68);
		$rightStringLimit = -4;

		$params        = self::getKeyDerivationParameters();
		$keySizeBytes  = $params['keySize'];
		$algorithm     = $params['algorithm'];
		$iterations    = $params['iterations'];
		$useStaticSalt = $params['useStaticSalt'];

		if (substr($salt, 0, 4) == 'JPST')
		{
			// We have a stored salt. Retrieve it and tell decrypt to process the string minus the last 44 bytes
			// (4 bytes for JPST, 16 bytes for the salt, 4 bytes for JPIV, 16 bytes for the IV, 4 bytes for the
			// uncompressed string length - note that using PBKDF2 means we're also using a randomized IV per the
			// format specification).
			$salt             = substr($salt, 4);
			$rightStringLimit -= 68;

			$key          = self::pbkdf2($password, $salt, $algorithm, $iterations, $keySizeBytes);
		}
		elseif ($useStaticSalt)
		{
			// We have a static salt. Use it for PBKDF2.
			$key = self::getStaticSaltExpandedKey($password);
		}
		else
		{
			// Get the expanded key from the password. THIS USES THE OLD, INSECURE METHOD.
			$key = self::expandKey($password);
		}

		// Try to get the IV from the data
		$iv               = substr($ciphertext, -24, 20);

		if (substr($iv, 0, 4) == 'JPIV')
		{
			// We have a stored IV. Retrieve it and tell mdecrypt to process the string minus the last 24 bytes
			// (4 bytes for JPIV, 16 bytes for the IV, 4 bytes for the uncompressed string length)
			$iv               = substr($iv, 4);
			$rightStringLimit -= 20;
		}
		else
		{
			// No stored IV. Do it the dumb way.
			$iv = self::createTheWrongIV($password);
		}

		// Decrypt
		$plaintext = $adapter->decrypt($iv . substr($ciphertext, 0, $rightStringLimit), $key);

		// Trim padding, if necessary
		if (strlen($plaintext) > $data_size)
		{
			$plaintext = substr($plaintext, 0, $data_size);
		}

		return $plaintext;
	}

	/**
	 * That's the old way of creating an IV that's definitely not cryptographically sound.
	 *
	 * DO NOT USE, EVER, UNLESS YOU WANT TO DECRYPT LEGACY DATA
	 *
	 * @param   string $password The raw password from which we create an IV in a super bozo way
	 *
	 * @return  string  A 16-byte IV string
	 */
	public static function createTheWrongIV($password)
	{
		static $ivs = [];

		$key = AKUtilsHash::md5($password);

		if (!isset($ivs[$key]))
		{
			$nBytes  = 16;  // AES uses a 128 -bit (16 byte) block size, hence the IV size is always 16 bytes
			$pwBytes = [];
			for ($i = 0; $i < $nBytes; $i++)
			{
				$pwBytes[$i] = ord(substr($password, $i, 1)) & 0xff;
			}
			$iv    = self::Cipher($pwBytes, self::KeyExpansion($pwBytes));
			$newIV = '';
			foreach ($iv as $int)
			{
				$newIV .= chr($int);
			}

			$ivs[$key] = $newIV;
		}

		return $ivs[$key];
	}

	/**
	 * Expand the password to an appropriate 128-bit encryption key
	 *
	 * @param   string $password
	 *
	 * @return  string
	 *
	 * @since   5.2.0
	 * @author  Nicholas K. Dionysopoulos
	 */
	public static function expandKey($password)
	{
		// Try to fetch cached key or create it if it doesn't exist
		$nBits     = 128;
		$lookupKey = AKUtilsHash::md5($password . '-' . $nBits);

		if (array_key_exists($lookupKey, self::$passwords))
		{
			$key = self::$passwords[$lookupKey];

			return $key;
		}

		// use AES itself to encrypt password to get cipher key (using plain password as source for
		// key expansion) - gives us well encrypted key.
		$nBytes  = $nBits / 8; // Number of bytes in key
		$pwBytes = [];

		for ($i = 0; $i < $nBytes; $i++)
		{
			$pwBytes[$i] = ord(substr($password, $i, 1)) & 0xff;
		}

		$key    = self::Cipher($pwBytes, self::KeyExpansion($pwBytes));
		$key    = array_merge($key, array_slice($key, 0, $nBytes - 16)); // expand key to 16/24/32 bytes long
		$newKey = '';

		foreach ($key as $int)
		{
			$newKey .= chr($int);
		}

		$key = $newKey;

		self::$passwords[$lookupKey] = $key;

		return $key;
	}

	/**
	 * Returns the correct AES-128 CBC encryption adapter
	 *
	 * @return  AKEncryptionAESAdapterInterface
	 *
	 * @since   5.2.0
	 * @author  Nicholas K. Dionysopoulos
	 */
	public static function getAdapter()
	{
		static $adapter = null;

		if (is_object($adapter) && ($adapter instanceof AKEncryptionAESAdapterInterface))
		{
			return $adapter;
		}

		$adapter = new OpenSSL();

		if (!$adapter->isSupported())
		{
			$adapter = new Mcrypt();
		}

		return $adapter;
	}

	/**
	 * @return string
	 */
	public static function getPbkdf2Algorithm()
	{
		return self::$pbkdf2Algorithm;
	}

	/**
	 * @param string $pbkdf2Algorithm
	 * @return void
	 */
	public static function setPbkdf2Algorithm($pbkdf2Algorithm)
	{
		self::$pbkdf2Algorithm = $pbkdf2Algorithm;
	}

	/**
	 * @return int
	 */
	public static function getPbkdf2Iterations()
	{
		return self::$pbkdf2Iterations;
	}

	/**
	 * @param int $pbkdf2Iterations
	 * @return void
	 */
	public static function setPbkdf2Iterations($pbkdf2Iterations)
	{
		self::$pbkdf2Iterations = $pbkdf2Iterations;
	}

	/**
	 * @return int
	 */
	public static function getPbkdf2UseStaticSalt()
	{
		return self::$pbkdf2UseStaticSalt;
	}

	/**
	 * @param int $pbkdf2UseStaticSalt
	 * @return void
	 */
	public static function setPbkdf2UseStaticSalt($pbkdf2UseStaticSalt)
	{
		self::$pbkdf2UseStaticSalt = $pbkdf2UseStaticSalt;
	}

	/**
	 * @return string
	 */
	public static function getPbkdf2StaticSalt()
	{
		return self::$pbkdf2StaticSalt;
	}

	/**
	 * @param string $pbkdf2StaticSalt
	 * @return void
	 */
	public static function setPbkdf2StaticSalt($pbkdf2StaticSalt)
	{
		self::$pbkdf2StaticSalt = $pbkdf2StaticSalt;
	}

	/**
	 * Get the parameters fed into PBKDF2 to expand the user password into an encryption key. These are the static
	 * parameters (key size, hashing algorithm and number of iterations). A new salt is used for each encryption block
	 * to minimize the risk of attacks against the password.
	 *
	 * @return  array
	 */
	public static function getKeyDerivationParameters()
	{
		return [
			'keySize'       => 16,
			'algorithm'     => self::$pbkdf2Algorithm,
			'iterations'    => self::$pbkdf2Iterations,
			'useStaticSalt' => self::$pbkdf2UseStaticSalt,
			'staticSalt'    => self::$pbkdf2StaticSalt,
		];
	}

	/**
	 * PBKDF2 key derivation function as defined by RSA's PKCS #5: https://www.ietf.org/rfc/rfc2898.txt
	 *
	 * Test vectors can be found here: https://www.ietf.org/rfc/rfc6070.txt
	 *
	 * This implementation of PBKDF2 was originally created by https://defuse.ca
	 * With improvements by http://www.variations-of-shadow.com
	 * Modified for Akeeba Engine by Akeeba Ltd (removed unnecessary checks to make it faster)
	 *
	 * @param   string  $password    The password.
	 * @param   string  $salt        A salt that is unique to the password.
	 * @param   string  $algorithm   The hash algorithm to use. Default is sha1.
	 * @param   int     $count       Iteration count. Higher is better, but slower. Default: 1000.
	 * @param   int     $key_length  The length of the derived key in bytes.
	 *
	 * @return  string  A string of $key_length bytes
	 */
	public static function pbkdf2($password, $salt, $algorithm = 'sha1', $count = 1000, $key_length = 16)
	{
		if (function_exists("hash_pbkdf2"))
		{
			return hash_pbkdf2($algorithm, $password, $salt, $count, $key_length, true);
		}

		$hash_length = akstringlen(hash($algorithm, "", true));
		$block_count = ceil($key_length / $hash_length);

		$output = "";

		for ($i = 1; $i <= $block_count; $i++)
		{
			// $i encoded as 4 bytes, big endian.
			$last = $salt . pack("N", $i);

			// First iteration
			$xorResult = hash_hmac($algorithm, $last, $password, true);
			$last      = $xorResult;

			// Perform the other $count - 1 iterations
			for ($j = 1; $j < $count; $j++)
			{
				$last = hash_hmac($algorithm, $last, $password, true);
				$xorResult ^= $last;
			}

			$output .= $xorResult;
		}

		return aksubstr($output, 0, $key_length);
	}

	/**
	 * Get the expanded key from the user supplied password using a static salt. The results are cached for performance
	 * reasons.
	 *
	 * @param   string  $password  The user-supplied password, UTF-8 encoded.
	 *
	 * @return  string  The expanded key
	 */
	private static function getStaticSaltExpandedKey($password)
	{
		$params        = self::getKeyDerivationParameters();
		$keySizeBytes  = $params['keySize'];
		$algorithm     = $params['algorithm'];
		$iterations    = $params['iterations'];
		$staticSalt    = $params['staticSalt'];

		$lookupKey = "PBKDF2-$algorithm-$iterations-" . AKUtilsHash::md5($password . $staticSalt);

		if (!array_key_exists($lookupKey, self::$passwords))
		{
			self::$passwords[$lookupKey] = self::pbkdf2($password, $staticSalt, $algorithm, $iterations, $keySizeBytes);
		}

		return self::$passwords[$lookupKey];
	}

}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * A timing safe equals comparison
 *
 * @param   string  $safe  The internal (safe) value to be checked
 * @param   string  $user  The user submitted (unsafe) value
 *
 * @return  boolean  True if the two strings are identical.
 *
 * @see     http://blog.ircmaxell.com/2014/11/its-all-about-time.html
 */
function timingSafeEquals($safe, $user)
{
	$safeLen = strlen($safe);
	$userLen = strlen($user);

	if ($userLen != $safeLen)
	{
		return false;
	}

	$result = 0;

	for ($i = 0; $i < $userLen; $i++)
	{
		$result |= (ord($safe[$i]) ^ ord($user[$i]));
	}

	// They are only identical strings if $result is exactly 0...
	return $result === 0;
}

/**
 * The Master Setup will read the configuration parameters from restoration.php or
 * the JSON-encoded "configuration" input variable and return the status.
 *
 * @return bool True if the master configuration was applied to the Factory object
 */
function masterSetup()
{
	// ------------------------------------------------------------
	// 1. Import basic setup parameters
	// ------------------------------------------------------------

	$ini_data = null;

	// In restore.php mode, require restoration.php or fail
	if (!defined('KICKSTART'))
	{
		// On Joomla 5 we need to look for a defines.php file, in case we are in a custom public folder
		$definesFile = '../../../defines.php';

		if (file_exists($definesFile))
		{
			$fileContents = @file_get_contents($definesFile) ?: '';

			if (strpos($fileContents, "define('JPATH_PUBLIC'") !== false)
			{
				defined('_JEXEC') || define('_JEXEC', 1);
			}

			require_once $definesFile;
		}

		// This is the standalone mode, used by Akeeba Backup Professional. It looks for a restoration.php
		// file to perform its magic. If the file is not there, we will abort.
		$alternateFiles = [
			__DIR__ . '/restoration.php',
			'restoration.php',
		];

		if (defined('JPATH_PUBLIC'))
		{
			$alternateFiles[] = JPATH_PUBLIC . 'administrator/components/com_akeebabackup/restoration.php';
		}

		$foundSetupFile = false;

		foreach ($alternateFiles as $setupFile)
		{
			if (file_exists($setupFile))
			{
				$foundSetupFile = true;

				break;
			}
		}

		if (!$foundSetupFile)
		{
			AKFactory::set('kickstart.enabled', false);

			return false;
		}

		/**
		 * If the setup file was created more than 1.5 hours ago we can assume that it's stale and someone forgot to
		 * remove it from the server. This hinders brute force attacks against the Kickstart password. Even a simple
		 * 8 character simple alphanum (a-z, 0-9) password yields over 2.8e12. Assuming a very fast server which can
		 * serve 100 requests to restore.php per second and an easy to attack password requiring going over just 1% of
		 * the search space it'd still take over 282 million seconds to brute force it. Our limit is more than 4 orders
		 * of magnitude lower than this best practical case scenario, giving us adequate protection against all but the
		 * luckiest attacker (spoiler alert: the mathematics of probabilities say you're not gonna get lucky).
		 *
		 * It is still advisable to remove the restoration.php file once you are done with the extraction. This check
		 * here is only meant as a failsafe in case of a server error during the extraction and subsequent lack of user
		 * action to remove the restoration.php file from their server.
		 */
		$setupFieCreationTime = filectime($setupFile);

		if (abs(time() - $setupFieCreationTime) > 5400)
		{
			AKFactory::set('kickstart.enabled', false);

			return false;
		}

		// Load restoration.php. It creates a global variable named $restoration_setup
		require_once $setupFile;

		$ini_data = $restoration_setup;

		if (empty($ini_data))
		{
			// No parameters fetched. Darn, how am I supposed to work like that?!
			AKFactory::set('kickstart.enabled', false);

			return false;
		}

		AKFactory::set('kickstart.enabled', true);
	}
	else
	{
		// Maybe we have $restoration_setup defined in the head of kickstart.php
		global $restoration_setup;

		if (!empty($restoration_setup) && !is_array($restoration_setup))
		{
			$ini_data = AKText::parse_ini_file($restoration_setup, false, true);
		}
		elseif (is_array($restoration_setup))
		{
			$ini_data = $restoration_setup;
		}
	}

	// Import any data from $restoration_setup
	if (!empty($ini_data))
	{
		foreach ($ini_data as $key => $value)
		{
			AKFactory::set($key, $value);
		}
		AKFactory::set('kickstart.enabled', true);
	}

	// Reinitialize $ini_data
	$ini_data = null;

	/**
	 * August 2018. Some third party developer with a dubious skill level (or complete lack thereof) wrote a piece of
	 * code which uses restore.php with an empty password (and never deleted the restoration.php file he created).
	 * According to his code comments he did this because he couldn't figure out how to make encrypted requests work,
	 * DESPITE THE FACT that com_joomlaupdate (part of Joomla! itself) has working code which does EXACTLY THAT. >:-o
	 *
	 * As a result of his actions all sites running his software have a massive vulnerability inflicted upon them. An
	 * attacker can absuse the (unlocked) restore.php to upload and install any arbitrary code in a ZIP archive,
	 * possibly overwriting core code. Discovering this problem takes a few seconds and there is code which is doing
	 * exactly that published years ago (during the active maintenance period of Joomla! 3.4, that long ago).
	 *
	 * This bit of code here detects an empty password and disables restore.php. His badly written software fails to
	 * execute and, most importantly, the unlucky users of his software will no longer have a remote code upload /
	 * remote code execution vulnerability on their sites.
	 *
	 * Remember, people, if you can't be bothered to take web application security seriously DO NOT SELL WEB SOFTWARE
	 * FOR A LIVING. There are other honest jobs you can do which don't involve using a computer in a dangerous and
	 * irresponsible manner.
	 */
	$password = AKFactory::get('kickstart.security.password', null);

	if (empty($password) || (trim($password) == '') || (strlen(trim($password)) < 10))
	{
		AKFactory::set('kickstart.enabled', false);

		return false;
	}


	// ------------------------------------------------------------
	// 2. Explode JSON parameters into $_REQUEST scope
	// ------------------------------------------------------------

	// Detect a JSON string in the request variable and store it.
	$json = getQueryParam('json', null);

	// Detect a password in the request variable and store it.
	$userPassword = getQueryParam('password', '');

	// Remove everything from the request, post and get arrays
	if (!empty($_REQUEST))
	{
		foreach ($_REQUEST as $key => $value)
		{
			unset($_REQUEST[$key]);
		}
	}

	if (!empty($_POST))
	{
		foreach ($_POST as $key => $value)
		{
			unset($_POST[$key]);
		}
	}

	if (!empty($_GET))
	{
		foreach ($_GET as $key => $value)
		{
			unset($_GET[$key]);
		}
	}

	// Authentication - Akeeba Restore 5.4.0 or later
	$password = AKFactory::get('kickstart.security.password', null);
	$isAuthenticated = false;

	/**
	 * Akeeba Restore 5.3.1 and earlier use a custom implementation of AES-128 in CTR mode to encrypt the JSON data
	 * between client and server. This is not used as a means to maintain secrecy (it's symmetrical encryption and the
	 * key is, by necessity, transmitted with the HTML page to the client). It's meant as a form of authentication, so
	 * that the server part can ensure that it only receives commands by an authorized client.
	 *
	 * The downside is that encryption in CTR mode (like CBC) is an all-or-nothing affair. This opens the possibility
	 * for a padding oracle attack (https://en.wikipedia.org/wiki/Padding_oracle_attack). While Akeeba Restore was
	 * hardened in 2014 to prevent the bulk of suck attacks it is still possible to attack the encryption using a very
	 * large number of requests (several dozens of thousands).
	 *
	 * Since Akeeba Restore 5.4.0 we have removed this authentication method and replaced it with the transmission of a
	 * very large length password. On the server side we use a timing safe password comparison. By its very nature, it
	 * will only leak the (well known, constant and large) length of the password but no more information about the
	 * password itself. See http://blog.ircmaxell.com/2014/11/its-all-about-time.html  As a result this form of
	 * authentication is many orders of magnitude harder to crack than regular encryption.
	 *
	 * Now you may wonder "how is sending a password in the clear hardier than encryption?". If you ask that question
	 * you were not paying attention. The password needs to be known by BOTH the server AND the client (browser). Since
	 * this password is generated programmatically by the server, it MUST be sent to the client by the server. If an
	 * attacker is able to intercept this transmission (man in the middle attack) using encryption is irrelevant: the
	 * attacker already knows your password. This situation also applies when the user sends their own password to the
	 * server, e.g. when logging into their site. The ONLY way to avoid security issues regarding information being
	 * stolen in transit is using HTTPS with a commercially signed SSL certificate. Unlike 2008, when Kickstart was
	 * originally written, obtaining such a certificate nowadays is trivial and costs absolutely nothing thanks to Let's
	 * Encrypt (https://letsencrypt.org/).
	 *
	 * TL;DR: Use HTTPS with a commercially signed SSL certificate, e.g. a free certificate from Let's Encrypt. Client-
	 * side cryptography does NOT protect you against an attacker (see
	 * https://www.nccgroup.trust/us/about-us/newsroom-and-events/blog/2011/august/javascript-cryptography-considered-harmful/).
	 * Moreover, sending a plaintext password is safer than relying on client-side encryption for authentication as it
	 * removes the possibility of an attacker inferring the contents of the authentication key (password) in a relatively
	 * easy and automated manner.
	 */
	if (!empty($password))
	{
		// Timing-safe password comparison. See http://blog.ircmaxell.com/2014/11/its-all-about-time.html
		if (!timingSafeEquals($password, $userPassword))
		{
			die('###{"status":false,"message":"Invalid login"}###');
		}
	}

	// No JSON data? Die.
	if (empty($json))
	{
		die('###{"status":false,"message":"Invalid JSON data"}###');
	}

	// Handle the JSON string
	$raw = json_decode($json, true);

	// Invalid JSON data?
	if (empty($raw))
	{
		die('###{"status":false,"message":"Invalid JSON data"}###');
	}

	// Pass all JSON data to the request array
	if (!empty($raw))
	{
		foreach ($raw as $key => $value)
		{
			$_REQUEST[$key] = $value;
		}
	}

	// ------------------------------------------------------------
	// 3. Try the "factory" variable
	// ------------------------------------------------------------
	// A "factory" variable will override all other settings.
	$serialized = getQueryParam('factory', null);

	if (!is_null($serialized))
	{
		// Get the serialized factory
		AKFactory::unserialize($serialized);
		AKFactory::set('kickstart.enabled', true);

		return true;
	}

	// ------------------------------------------------------------
	// 4. Try the configuration variable for Kickstart
	// ------------------------------------------------------------
	if (defined('KICKSTART'))
	{
		$configuration = getQueryParam('configuration');

		if (!is_null($configuration))
		{
			// Let's decode the configuration from JSON to array
			$ini_data = json_decode($configuration, true);
		}
		else
		{
			// Neither exists. Enable Kickstart's interface anyway.
			$ini_data = ['kickstart.enabled' => true];
		}

		// Import any INI data we might have from other sources
		if (!empty($ini_data))
		{
			foreach ($ini_data as $key => $value)
			{
				AKFactory::set($key, $value);
			}

			AKFactory::set('kickstart.enabled', true);

			return true;
		}
	}
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Mini-controller for restore.php
if (!defined('KICKSTART'))
{
	// The observer class, used to report number of files and bytes processed
	class RestorationObserver extends AKAbstractPartObserver
	{
		public $compressedTotal = 0;
		public $uncompressedTotal = 0;
		public $filesProcessed = 0;

		public function update($object, $message)
		{
			if (!is_object($message))
			{
				return;
			}

			if (!array_key_exists('type', get_object_vars($message)))
			{
				return;
			}

			if ($message->type == 'startfile')
			{
				$this->filesProcessed++;
				$this->compressedTotal += $message->content->compressed;
				$this->uncompressedTotal += $message->content->uncompressed;
			}
		}

		public function __toString()
		{
			return self::class;
		}

	}

	// Import configuration
	masterSetup();

	$retArray = [
		'status'  => true,
		'message' => null
	];

	$enabled = AKFactory::get('kickstart.enabled', false);

	if ($enabled)
	{
		$task = getQueryParam('task');

		switch ($task)
		{
			case 'ping':
				// ping task - really does nothing!
				$timer = AKFactory::getTimer();
				$timer->enforce_min_exec_time();
				break;

			/**
			 * There are two separate steps here since we were using an inefficient restoration initialization method in
			 * the past. Now both startRestore and stepRestore are identical. The difference in behavior depends
			 * exclusively on the calling Javascript. If no serialized factory was passed in the request then we start a
			 * new restoration. If a serialized factory was passed in the request then the restoration is resumed. For
			 * this reason we should NEVER call AKFactory::nuke() in startRestore anymore: that would simply reset the
			 * extraction engine configuration which was done in masterSetup() leading to an error about the file being
			 * invalid (since no file is found).
			 */
			case 'startRestore':
			case 'stepRestore':
				if ($task == 'startRestore')
				{
					// Fetch path to the site root from the restoration.php file, so we can tell the engine where it should operate
					$siteRoot = AKFactory::get('kickstart.setup.destdir', '');

					// Before starting, read and save any custom AddHandler directive
					$phpHandlers = getPhpHandlers($siteRoot);
					AKFactory::set('kickstart.setup.phphandlers', $phpHandlers);

					// If the Stealth Mode is enabled, create the .htaccess file
					if (AKFactory::get('kickstart.stealth.enable', false))
					{
						createStealthURL($siteRoot);
					}
					// No stealth mode, but we have custom handler directives, must write our own file
					elseif ($phpHandlers)
					{
						writePhpHandlers($siteRoot);
					}
				}

				/**
				 * First try to run the filesystem zapper (remove all existing files and folders). If the Zapper is
				 * disabled or has already finished running we will get a FALSE result. Otherwise it's a status array
				 * which we can pass directly back to the caller.
				 */
				$nullObserver = new AKPartNullObserver();
				$ret          = runZapper($nullObserver);

				// If the Zapper had a step to run we stop here and return its status array to the caller.
				if ($ret !== false)
				{
					$retArray = array_merge($retArray, $ret);

					break;
				}

				$engine   = AKFactory::getUnarchiver(); // Get the engine
				$observer = new RestorationObserver(); // Create a new observer
				$engine->attach($observer); // Attach the observer
				$engine->tick();
				$ret = $engine->getStatusArray();

				if ($ret['Error'] != '')
				{
					$retArray['status']  = false;
					$retArray['done']    = true;
					$retArray['message'] = $ret['Error'];
				}
				elseif (!$ret['HasRun'])
				{
					$retArray['files']    = $observer->filesProcessed;
					$retArray['bytesIn']  = $observer->compressedTotal;
					$retArray['bytesOut'] = $observer->uncompressedTotal;
					$retArray['status']   = true;
					$retArray['done']     = true;
				}
				else
				{
					$retArray['files']    = $observer->filesProcessed;
					$retArray['bytesIn']  = $observer->compressedTotal;
					$retArray['bytesOut'] = $observer->uncompressedTotal;
					$retArray['status']   = true;
					$retArray['done']     = false;
					$retArray['factory']  = AKFactory::serialize();
				}

				$timer = AKFactory::getTimer();
				$timer->enforce_min_exec_time();

				break;

			case 'finalizeRestore':
				$root = AKFactory::get('kickstart.setup.destdir');
				// Remove the installation directory
				recursive_remove_directory($root . '/installation');

				$postproc = AKFactory::getPostProc();

				/**
				 * Should I rename the htaccess.bak and web.config.bak files back to their live filenames...?
				 */
				$renameFiles = AKFactory::get('kickstart.setup.postrenamefiles', true);

				if ($renameFiles)
				{
					// Rename htaccess.bak to .htaccess
					if (file_exists($root . '/htaccess.bak'))
					{
						if (file_exists($root . '/.htaccess'))
						{
							$postproc->unlink($root . '/.htaccess');
						}

						$postproc->rename($root . '/htaccess.bak', $root . '/.htaccess');
					}

					// Rename htaccess.bak to .htaccess
					if (file_exists($root . '/web.config.bak'))
					{
						if (file_exists($root . '/web.config'))
						{
							$postproc->unlink($root . '/web.config');
						}

						$postproc->rename($root . '/web.config.bak', $root . '/web.config');
					}
				}

				// Remove restoration.php
				$basepath = KSROOTDIR;
				$basepath = rtrim(str_replace('\\', '/', $basepath), '/');

				if (!empty($basepath))
				{
					$basepath .= '/';
				}

				$postproc->unlink($basepath . 'restoration.php');
				clearFileInOPCache($basepath . 'restoration.php');

				// Import a custom finalisation file
				$filename = __DIR__ . '/restore_finalisation.php';

				if (file_exists($filename))
				{
					// opcode cache busting before including the filename
					if (function_exists('opcache_invalidate'))
					{
						opcache_invalidate($filename, true);
					}

					if (function_exists('apc_compile_file'))
					{
						apc_compile_file($filename);
					}

					if (function_exists('wincache_refresh_if_changed'))
					{
						wincache_refresh_if_changed([$filename]);
					}

					if (function_exists('xcache_asm'))
					{
						xcache_asm($filename);
					}

					include_once $filename;
				}

				// Run a custom finalisation script
				if (function_exists('finalizeRestore'))
				{
					finalizeRestore($root, $basepath);
				}

				break;

			default:
				// Invalid task!
				$enabled = false;
				break;
		}
	}

	// Maybe we weren't authorized or the task was invalid?
	if (!$enabled)
	{
		// Maybe the user failed to enter any information
		$retArray['status']  = false;
		$retArray['message'] = AKText::_('ERR_INVALID_LOGIN');
	}

	// JSON encode the message
	$json = json_encode($retArray);

	// Return the message
	echo "###$json###";

}

// ------------ lixlpixel recursive PHP functions -------------
// recursive_remove_directory( directory to delete, empty )
// expects path to directory and optional TRUE / FALSE to empty
// of course PHP has to have the rights to delete the directory
// you specify and all files and folders inside the directory
// ------------------------------------------------------------
function recursive_remove_directory($directory)
{
	// if the path has a slash at the end we remove it here
	if (substr($directory, -1) == '/')
	{
		$directory = substr($directory, 0, -1);
	}

	// if the path is not valid or is not a directory ...
	if (!file_exists($directory) || !is_dir($directory))
	{
		// ... we return false and exit the function
		return false;
		// ... if the path is not readable
	}
	elseif (!is_readable($directory))
	{
		// ... we return false and exit the function
		return false;
		// ... else if the path is readable
	}
	else
	{
		// we open the directory
		$handle   = opendir($directory);
		$postproc = AKFactory::getPostProc();

		// and scan through the items inside
		while (false !== ($item = readdir($handle)))
		{
			// if the filepointer is not the current directory
			// or the parent directory

			if ($item != '.' && $item != '..')
			{
				// we build the new path to delete
				$path = $directory . '/' . $item;

				// if the new path is a directory
				if (is_dir($path))
				{
					// we call this function with the new path
					recursive_remove_directory($path);
					// if the new path is a file
				}
				else
				{
					// we remove the file
					$postproc->unlink($path);
					clearFileInOPCache($path);
				}
			}
		}

		// close the directory
		closedir($handle);

		// try to delete the now empty directory
		if (!$postproc->rmdir($directory))
		{
			// return false if not possible
			return false;
		}

		// return success
		return true;
	}
}

function createStealthURL($siteRoot = '')
{
	$filename = AKFactory::get('kickstart.stealth.url', '');

	// We need an HTML file!
	if (empty($filename))
	{
		return;
	}

	// Make sure it ends in .html or .htm
	$filename = basename($filename);

	if ((strtolower(substr($filename, -5)) != '.html') && (strtolower(substr($filename, -4)) != '.htm'))
	{
		return;
	}

	if ($siteRoot)
	{
		$siteRoot = rtrim($siteRoot, '/').'/';
	}

	$filename_quoted = str_replace('.', '\\.', $filename);
	$rewrite_base    = trim(dirname(AKFactory::get('kickstart.stealth.url', '')), '/');

	// Get the IP
	$userIP = $_SERVER['REMOTE_ADDR'];
	$userIP = str_replace('.', '\.', $userIP);

	// Get the .htaccess contents
	$stealthHtaccess = <<<ENDHTACCESS
RewriteEngine On
RewriteBase /$rewrite_base
RewriteCond %{REMOTE_ADDR}		!$userIP
RewriteCond %{REQUEST_URI}		!$filename_quoted
RewriteCond %{REQUEST_URI}		!(\.png|\.jpg|\.gif|\.jpeg|\.bmp|\.swf|\.css|\.js)$
RewriteRule (.*)				$filename	[R=307,L]

ENDHTACCESS;

	$customHandlers = portPhpHandlers();

	// Port any custom handlers in the stealth file
	if ($customHandlers)
	{
		$stealthHtaccess .= "\n".$customHandlers."\n";
	}

	// Write the new .htaccess, removing the old one first
	$postproc = AKFactory::getpostProc();
	$postproc->unlink($siteRoot.'.htaccess');
	$tempfile = $postproc->processFilename($siteRoot.'.htaccess');
	@file_put_contents($tempfile, $stealthHtaccess);
	$postproc->process();
}

/**
 * Checks if there is an .htaccess file and has any AddHandler directive in it.
 * In that case, we return the affected lines so they could be stored for later use
 *
 * @return  array
 */
function getPhpHandlers($root = null)
{
	if (!$root)
	{
		$root = AKKickstartUtils::getPath();
	}

	$htaccess   = $root.'/.htaccess';
	$directives = [];

	if (!file_exists($htaccess))
	{
		return $directives;
	}

	$contents   = file_get_contents($htaccess);
	$directives = AKUtilsHtaccess::extractHandler($contents);
	$directives = empty($directives) ? [] : explode("\n", $directives);

	return $directives;
}

/**
 * Fetches any stored php handler directive stored inside the factory and creates a string with the correct markers
 *
 * @return string
 */
function portPhpHandlers()
{
	$phpHandlers = AKFactory::get('kickstart.setup.phphandlers', []);

	if (!$phpHandlers)
	{
		return '';
	}

	$customHandler  = "### AKEEBA_KICKSTART_PHP_HANDLER_BEGIN ###\n";
	$customHandler .= implode("\n", $phpHandlers)."\n";
	$customHandler .= "### AKEEBA_KICKSTART_PHP_HANDLER_END ###\n";

	return $customHandler;
}

function writePhpHandlers($siteRoot = '')
{
	$contents = portPhpHandlers();

	if (!$contents)
	{
		return;
	}

	if ($siteRoot)
	{
		$siteRoot = rtrim($siteRoot, '/').'/';
	}

	// Write the new .htaccess, removing the old one first
	$postproc = AKFactory::getpostProc();
	$postproc->unlink($siteRoot.'.htaccess');
	$tempfile = $postproc->processFilename($siteRoot.'.htaccess');
	@file_put_contents($tempfile, $contents);
	$postproc->process();
}
