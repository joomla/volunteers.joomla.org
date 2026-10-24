<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Site\Model\Json;

// Protect from unauthorized access
use DirectoryIterator;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use RuntimeException;

defined('_JEXEC') || die();

/**
 * Handles task execution
 */
class Task
{
	/** @var  TaskInterface[]  The task handlers known to us */
	protected $handlers = [];

	/**
	 * The MVC Factory of the extension.
	 *
	 * This is used by tasks to instantiate the various Models we need.
	 *
	 * @var MVCFactoryInterface
	 */
	protected $factory;

	private static ?self $instance = null;

	/**
	 * Returns a static instance of this object
	 *
	 * @param   MVCFactoryInterface|null  $factory  The MVCFactory of the Akeeba Backup component
	 *
	 * @return  Task
	 * @throws  \Exception
	 * @since   9.6.0
	 */
	public static function getInstance(?MVCFactoryInterface $factory = null): self
	{
		if (!self::$instance)
		{
			$factory = $factory ?? Factory::getApplication()->bootComponent('com_akeebabackup')->getMVCFactory();

			self::$instance = new self($factory);
		}

		return self::$instance;
	}

	/**
	 * Public constructor. Populates the list of task handlers.
	 *
	 * @param   MVCFactoryInterface  $factory
	 */
	public function __construct(MVCFactoryInterface $factory)
	{
		$this->factory = $factory;

		// Populate the list of task handlers
		$this->initialiseHandlers();
	}

	/**
	 * Do I have a specific task handling method?
	 *
	 * @param   string  $method  The method to check for
	 *
	 * @return  bool
	 */
	public function hasMethod($method)
	{
		$method = strtolower($method);

		return isset($this->handlers[$method]);
	}

	public function getMethods(): array
	{
		return array_keys($this->handlers);
	}

	/**
	 * Returns the backend `view` or `view.task` ACL key a JSON API method is equivalent to.
	 *
	 * @param   string  $method  The method's name
	 *
	 * @return  string
	 *
	 * @throws  RuntimeException  When the method requested is not known to us
	 * @since   10.4.0
	 */
	public function getAclKeyForMethod($method): string
	{
		if (!$this->hasMethod($method))
		{
			throw new RuntimeException("Invalid method $method", 405);
		}

		return $this->handlers[strtolower($method)]->getAclKey();
	}

	/**
	 * Execute a JSON API method
	 *
	 * @param   string  $method      The method's name
	 * @param   array   $parameters  The parameters to the method (optional)
	 *
	 * @return  mixed
	 *
	 * @throws  RuntimeException  When the method requested is not known to us
	 */
	public function execute($method, $parameters = [])
	{
		if (!defined('AKEEBABACKUP_PRO') || !AKEEBABACKUP_PRO)
		{
			throw new RuntimeException('Access denied', 503);
		}

		if (!$this->hasMethod($method))
		{
			throw new RuntimeException("Invalid method $method", 405);
		}

		$method = strtolower($method);

		return $this->handlers[$method]->execute($parameters);
	}

	/**
	 * Initialises the encapsulation handlers
	 *
	 * @return  void
	 */
	protected function initialiseHandlers()
	{
		// Reset the array
		$this->handlers = [];

		// Look all files in the Task handlers' directory
		$dh = new DirectoryIterator(__DIR__ . '/Task');

		/** @var DirectoryIterator $entry */
		foreach ($dh as $entry)
		{
			$fileName = $entry->getFilename();

			// Ignore non-PHP files
			if (substr($fileName, -4) != '.php')
			{
				continue;
			}

			// Ignore the Base class
			if ($fileName == 'AbstractTask.php')
			{
				continue;
			}

			// Get the class name
			$className = __NAMESPACE__ . '\\Task\\' . substr($fileName, 0, -4);

			// Check if the class really exists
			if (!class_exists($className, true))
			{
				continue;
			}

			/** @var TaskInterface $o */
			$o = new $className($this->factory);
			$name = $o->getMethodName();
			$name = strtolower($name);
			$this->handlers[$name] = $o;
		}
	}

}
