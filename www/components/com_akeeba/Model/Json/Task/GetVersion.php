<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Site\Model\Json\Task;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\Backup\Site\Model\Updates;

/**
 * Get the version information of Akeeba Backup
 */
class GetVersion extends AbstractTask
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
		/** @var Updates $model */
		$model = $this->container->factory->model('Updates')->tmpInstance();

		$updateInformation = $model->getUpdates();

		if (is_array($updateInformation) && array_key_exists('releasenotes', $updateInformation))
		{
			unset ($updateInformation['releasenotes']);
		}

		$edition = AKEEBA_PRO ? 'pro' : 'core';

		return (object)array(
			'api'        => AKEEBA_JSON_API_VERSION,
			'component'  => AKEEBA_VERSION,
			'date'       => AKEEBA_DATE,
			'edition'    => $edition,
			'updateinfo' => $updateInformation,
		);
	}
}
