<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Site\Model\Json\Task;

// Protect from unauthorized access
defined('_JEXEC') || die();

use Akeeba\Backup\Site\Model\IncludeFolders;
use Akeeba\Engine\Platform;

/**
 * Get the extra included directories
 */
class GetIncludedDirectories extends AbstractTask
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
		// Get the passed configuration values
		$defConfig = array(
			'profile' => 0,
		);

		$defConfig = array_merge($defConfig, $parameters);

		$profile = (int)$defConfig['profile'];

		if ($profile <= 0)
		{
			$profile = 1;
		}

		// Set the active profile
		$this->container->platform->setSessionVar('profile', $profile, 'akeeba');

		// Load the configuration
		Platform::getInstance()->load_configuration($profile);

		/** @var IncludeFolders $model */
		$model = $this->container->factory->model('IncludeFolders')->tmpInstance();

		return $model->get_directories();
	}
}
