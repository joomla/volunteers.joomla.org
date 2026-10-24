<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Site\Model\Json\Task;

// Protect from unauthorized access
defined('_JEXEC') || die();

use Akeeba\Component\AkeebaBackup\Administrator\Model\ProfileModel;
use Akeeba\Component\AkeebaBackup\Administrator\Table\ProfileTable;
use Akeeba\Engine\Factory;
use Exception;

/**
 * Export the profile's configuration
 */
class ExportConfiguration extends AbstractTask
{
	/**
	 * Execute the JSON API task
	 *
	 * @param   array  $parameters  The parameters to this task
	 *
	 * @return  mixed
	 *
	 * @throws  Exception  In case of an error
	 */
	public function execute(array $parameters = [])
	{
		// Get the passed configuration values
		$defConfig = [
			'profile' => 0,
		];

		$defConfig = array_merge($defConfig, $parameters);

		$profile_id = (int) $defConfig['profile'];

		if ($profile_id <= 0)
		{
			$profile_id = 1;
		}

		/** @var ProfileModel $profileModel */
		$profileModel = $this->factory->createModel('Profile', 'Administrator', ['ignore_request' => true]);
		/** @var ProfileTable $profile */
		$profile      = $profileModel->getTable();

		$profile->load($profile_id);

		$data = $profile->getProperties();

		if (substr($data['configuration'], 0, 12) == '###AES128###')
		{
			// Load the server key file if necessary
			if (!defined('AKEEBA_SERVERKEY'))
			{
				$filename = JPATH_ADMINISTRATOR . '/components/com_akeebabackup/serverkey.php';

				include_once $filename;
			}

			$key = Factory::getSecureSettings()->getKey();

			$data['configuration'] = Factory::getSecureSettings()->decryptSettings($data['configuration'], $key);
		}

		return [
			'description'   => $data['description'],
			'configuration' => $data['configuration'],
			'filters'       => $data['filters'],
		];
	}
}
