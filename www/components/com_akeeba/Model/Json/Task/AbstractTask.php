<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Site\Model\Json\Task;

// Protect from unauthorized access
defined('_JEXEC') or die();

// Protect from unauthorized access
use Akeeba\Backup\Site\Model\Json\TaskInterface;
use FOF30\Container\Container;

defined('_JEXEC') or die();

class AbstractTask implements TaskInterface
{
	/**
	 * The container of the component we belong to
	 *
	 * @var  Container
	 */
	protected $container = null;

	/**
	 * The method name
	 *
	 * @var  string
	 */
	protected $methodName = '';

	/**
	 * Public constructor
	 *
	 * @param   Container  $container  The container of the component we belong to
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;

		$path = explode('\\', get_class($this));
		$shortName = array_pop($path);
		$this->methodName = lcfirst($shortName);
	}

	/**
	 * Return the JSON API task's name ("method" name). Remote clients will use it to call us.
	 *
	 * @return  string
	 */
	public function getMethodName()
	{
		return $this->methodName;
	}

	/**
	 * Execute the JSON API task
	 *
	 * @param   array  $parameters  The parameters to this task
	 *
	 * @return  mixed
	 *
	 * @throws  \RuntimeException  In case of an error
	 */
	public function execute(array $parameters = array())
	{
		throw new \LogicException(__CLASS__ . ' has not implemented its execute() method yet.');
	}
}
