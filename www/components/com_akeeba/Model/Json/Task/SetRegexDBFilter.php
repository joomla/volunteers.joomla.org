<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Site\Model\Json\Task;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\Backup\Site\Model\RegExDatabaseFilters;
use Akeeba\Engine\Platform;

/**
 * Set or unset a Regex database filter
 */
class SetRegexDBFilter extends AbstractTask
{
	/**
	 * Execute the JSON API task
	 *
	 * @param   array $parameters The parameters to this task
	 *
	 * @return  mixed
	 *
	 * @throws  \RuntimeException  In case of an error
	 */
	public function execute(array $parameters = array())
	{
		$filter = \JFilterInput::getInstance();

		// Get the passed configuration values
		$defConfig = array(
			'profile' => 0,
			'root'    => '[SITEDB]',
			'regex'   => '',
			'type'    => 'tables',
			'status'  => 1
		);

		$defConfig = array_merge($defConfig, $parameters);

		$profile = $filter->clean($defConfig['profile'], 'int');
		$root    = $filter->clean($defConfig['root'], 'string');
		$regex   = $filter->clean($defConfig['regex'], 'string');
		$type    = $filter->clean($defConfig['type'], 'cmd');
		$status  = $filter->clean($defConfig['status'], 'bool');

		// We need a valid profile ID
		if ($profile <= 0)
		{
			$profile = 1;
		}

		// We need a root
		if (empty($root))
		{
			throw new \RuntimeException('Unknown database root', 500);
		}

		// We need a regex name
		if (empty($regex))
		{
			throw new \RuntimeException('Regex is mandatory', 500);
		}

		// We need a regex name
		if (empty($type))
		{
			throw new \RuntimeException('Filter type is mandatory', 500);
		}

		// Set the active profile
		$this->container->platform->setSessionVar('profile', $profile);

		// Load the configuration
		Platform::getInstance()->load_configuration($profile);

		/** @var RegExDatabaseFilters $model */
		$model = $this->container->factory->model('RegExDatabaseFilters')->tmpInstance();

		if ($status)
		{
			$ret = $model->setFilter($type, $root, $regex);
		}
		else
		{
			$ret = $model->remove($type, $root, $regex);
		}

		return $ret;
	}
}
