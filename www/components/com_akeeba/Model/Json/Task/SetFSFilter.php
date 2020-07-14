<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Site\Model\Json\Task;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\Backup\Site\Model\FileFilters;
use Akeeba\Engine\Platform;

/**
 * Set or unset a filesystem filter
 */
class SetFSFilter extends AbstractTask
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
			'root'    => '[SITEROOT]',
			'path'    => '',
			'type'    => '',
			'status'  => 1
		);

		$defConfig = array_merge($defConfig, $parameters);

		$profile = $filter->clean($defConfig['profile'], 'int');
		$root    = $filter->clean($defConfig['root'], 'string');
		$path    = $filter->clean($defConfig['path'], 'path');
		$type    = $filter->clean($defConfig['type'], 'cmd');
		$status  = $filter->clean($defConfig['status'], 'bool');

		$crumbs = array();
		$node   = '';

		// We need a valid profile ID
		if ($profile <= 0)
		{
			$profile = 1;
		}

		// We need a root
		if (empty($root))
		{
			throw new \RuntimeException('Unknown filesystem root', 500);
		}

		// We need a path
		if (empty($path))
		{
			throw new \RuntimeException('Unknown path', 500);
		}

		// Get the subdirectory and explode it to its parts
		$path = trim($path, '/');

		if (!empty($path))
		{
			$crumbs = explode('/', $root);
			$node   = array_pop($crumbs);
		}

		if (empty($node))
		{
			throw new \RuntimeException('Unknown path', 500);
		}

		// We need a table name
		if (empty($type))
		{
			throw new \RuntimeException('Filter type is mandatory', 500);
		}

		// Set the active profile
		$this->container->platform->setSessionVar('profile', $profile);

		// Load the configuration
		Platform::getInstance()->load_configuration($profile);

		/** @var FileFilters $model */
		$model = $this->container->factory->model('FileFilters')->tmpInstance();

		if ($status)
		{
			$ret = $model->setFilter($root, $crumbs, $node, $type);
		}
		else
		{
			$ret = $model->remove($root, $crumbs, $node, $type);
		}

		return $ret;
	}
}
