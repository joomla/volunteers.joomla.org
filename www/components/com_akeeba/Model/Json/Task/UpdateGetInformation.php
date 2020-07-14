<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Site\Model\Json\Task;

// Protect from unauthorized access
defined('_JEXEC') || die();

use Akeeba\Backup\Site\Model\Updates;
use Joomla\CMS\Filter\InputFilter;

/**
 * Get the update information
 */
class UpdateGetInformation extends AbstractTask
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
			'force' => 0,
		];

		$defConfig = array_merge($defConfig, $parameters);

		$force = $filter->clean($defConfig['force'], 'bool');

		/** @var Updates $model */
		$model = $this->container->factory->model('Updates')->tmpInstance();

		$updateInformation = $model->getUpdates($force);

		return (object) $updateInformation;
	}
}
