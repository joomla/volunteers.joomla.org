<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Site\Model\Json\Task;

// Protect from unauthorized access
defined('_JEXEC') || die();

use Akeeba\Backup\Site\Model\MultipleDatabases;
use Joomla\CMS\Filter\InputFilter;

/**
 * Test an extra database definition
 */
class TestDBConnection extends AbstractTask
{
	/**
	 * Execute the JSON API task
	 *
	 * @param   array  $parameters  The parameters to this task
	 *
	 * @return  mixed
	 *
	 * @throws  \RuntimeException  In case of an error
	 */
	public function execute(array $parameters = [])
	{
		$filter = InputFilter::getInstance();

		// Get the passed configuration values
		$defConfig = [
			'connection' => [],
		];

		$defConfig = array_merge($defConfig, $parameters);

		$connection = $filter->clean($defConfig['connection'], 'array');

		if (
			empty($connection) || !isset($connection['host']) || !isset($connection['driver'])
			|| !isset($connection['database']) || !isset($connection['user'])
			|| !isset($connection['password'])
		)
		{
			throw new \RuntimeException('Connection information missing or incomplete', 500);
		}

		/** @var MultipleDatabases $model */
		$model = $this->container->factory->model('MultipleDatabases')->tmpInstance();

		return $model->test($connection);
	}
}
