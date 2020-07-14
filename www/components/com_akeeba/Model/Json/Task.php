<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Site\Model\Json;

// Protect from unauthorized access
defined('_JEXEC') or die();

use FOF30\Container\Container;

/**
 * Handles task execution
 */
class Task
{
	/**
	 * The component's container
	 *
	 * @var Container
	 */
	protected $container = null;

	/** @var  TaskInterface[]  The task handlers known to us */
	protected $handlers = array();

	/**
	 * Public constructor. Populates the list of task handlers.
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;

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

	/**
	 * Execute a JSON API method
	 *
	 * @param   string  $method      The method's name
	 * @param   array   $parameters  The parameters to the method (optional)
	 *
	 * @return  mixed
	 *
	 * @throws  \RuntimeException  When the method requested is not known to us
	 */
	public function execute($method, $parameters = array())
	{
		if ((!defined('AKEEBA_PRO') || !AKEEBA_PRO) && (time() >= 1583020800))
		{
			throw new \RuntimeException('Access denied', 503);
		}

		if (!$this->hasMethod($method))
		{
			throw new \RuntimeException("Invalid method $method", 405);
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
		$this->handlers = array();

		// Look all files in the Task handlers' directory
		$dh = new \DirectoryIterator(__DIR__ . '/Task');

		/** @var \DirectoryIterator $entry */
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
			$className = '\\Akeeba\\Backup\\Site\\Model\\Json\\Task\\' . substr($fileName, 0, -4);

			// Check if the class really exists
			if (!class_exists($className, true))
			{
				continue;
			}

			/** @var TaskInterface $o */
			$o = new $className($this->container);
			$name = $o->getMethodName();
			$name = strtolower($name);
			$this->handlers[$name] = $o;
		}
	}

}
