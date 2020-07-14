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
 * Get the filesystem entities along with their filtering status (typically for rendering a GUI)
 */
class GetFSEntities extends AbstractTask
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
			'profile'      => 0,
			'root'         => '[SITEROOT]',
			'subdirectory' => '',
		);

		$defConfig = array_merge($defConfig, $parameters);

		$profile      = $filter->clean($defConfig['profile'], 'int');
		$root         = $filter->clean($defConfig['root'], 'string');
		$subdirectory = $filter->clean($defConfig['subdirectory'], 'path');
		$crumbs       = array();

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

		// Get the subdirectory and explode it to its parts
		if (!empty($subdirectory))
		{
			$subdirectory = trim($subdirectory, '/');
		}

		if (!empty($subdirectory))
		{
			$crumbs = explode('/', $subdirectory);
		}

		// Set the active profile
		$this->container->platform->setSessionVar('profile', $profile);

		// Load the configuration
		Platform::getInstance()->load_configuration($profile);

		/** @var FileFilters $model */
		$model = $this->container->factory->model('FileFilters')->tmpInstance();

		return $model->make_listing($root, $crumbs);
	}
}
