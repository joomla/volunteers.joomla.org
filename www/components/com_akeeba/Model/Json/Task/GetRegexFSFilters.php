<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Site\Model\Json\Task;

// Protect from unauthorized access
defined('_JEXEC') || die();

use Akeeba\Backup\Site\Model\RegExFileFilters;
use Akeeba\Engine\Platform;
use Joomla\CMS\Filter\InputFilter;

/**
 * Get the regex filesystem filters
 */
class GetRegexFSFilters extends AbstractTask
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
			'profile' => 0,
			'root'    => '[SITEROOT]',
		];

		$defConfig = array_merge($defConfig, $parameters);

		$profile = $filter->clean($defConfig['profile'], 'int');
		$root    = $filter->clean($defConfig['root'], 'string');

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

		// Set the active profile
		$this->container->platform->setSessionVar('profile', $profile, 'akeeba');

		// Load the configuration
		Platform::getInstance()->load_configuration($profile);

		/** @var RegExFileFilters $model */
		$model = $this->container->factory->model('RegExFileFilters')->tmpInstance();

		return $model->get_regex_filters($root);
	}
}
