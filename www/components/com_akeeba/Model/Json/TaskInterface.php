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
 * Interface for JSON API tasks
 */
interface TaskInterface
{
	/**
	 * Public constructor
	 *
	 * @param   Container  $container  The container of the component we belong to
	 */
	public function __construct(Container $container);

	/**
	 * Return the JSON API task's name ("method" name). Remote clients will use it to call us.
	 *
	 * @return  string
	 */
	public function getMethodName();

	/**
	 * Execute the JSON API task
	 *
	 * @param   array  $parameters  The parameters to this task
	 *
	 * @return  mixed
	 *
	 * @throws  \RuntimeException  In case of an error
	 */
	public function execute(array $parameters = array());
}
