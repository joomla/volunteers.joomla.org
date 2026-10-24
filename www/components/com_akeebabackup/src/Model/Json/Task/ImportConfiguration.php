<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Site\Model\Json\Task;

// Protect from unauthorized access
defined('_JEXEC') || die();

use Akeeba\Component\AkeebaBackup\Administrator\Model\ProfilesModel;
use Exception;
use RuntimeException;

/**
 * Import the profile's configuration
 */
class ImportConfiguration extends AbstractTask
{
	/**
	 * Execute the JSON API task
	 *
	 * @param   array  $parameters  The parameters to this task
	 *
	 * @return  mixed
	 *
	 * @throws  RuntimeException|Exception  In case of an error
	 */
	public function execute(array $parameters = [])
	{
		// Get the passed configuration values
		$defConfig = [
			'profile' => 0,
			'data'    => null,
		];

		$defConfig = array_merge($defConfig, $parameters);
		$data      = $defConfig['data'];

		/** @var ProfilesModel $profileModel */
		$profileModel = $this->factory->createModel('Profiles', 'Administrator', ['ignore_request' => true]);

		$profileModel->import($data);

		return true;
	}
}
