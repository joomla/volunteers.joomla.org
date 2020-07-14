<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Site\Model\Json\Task;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\Backup\Site\Model\IncludeFolders;
use Akeeba\Engine\Platform;
use Akeeba\Engine\Util\RandomValue;

/**
 * Set up or edit an extra directory definition
 */
class SetIncludedDirectory extends AbstractTask
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
			'profile'       => 0,
			'uuid'          => '',
			'path'          => '',
			'virtualFolder' => '',
		);

		$defConfig = array_merge($defConfig, $parameters);

		$profile       = $filter->clean($defConfig['profile'], 'int');
		$path          = $filter->clean($defConfig['path'], 'path');
		$uuid          = $filter->clean($defConfig['uuid'], 'string');
		$virtualFolder = $filter->clean($defConfig['virtualFolder'], 'string');

		// We need a valid profile ID
		if ($profile <= 0)
		{
			$profile = 1;
		}

		// We need a path
		if (empty($path))
		{
			throw new \RuntimeException('Path is required', 500);
		}

		// We need a uuid
		if (empty($uuid))
		{
			$uuid = $this->uuid_v4();
		}

		// We need a vf
		if (empty($virtualFolder))
		{
			$virtualFolder = basename($path);
		}

		// Set the active profile
		$this->container->platform->setSessionVar('profile', $profile);

		// Load the configuration
		Platform::getInstance()->load_configuration($profile);

		/** @var IncludeFolders $model */
		$model = $this->container->factory->model('IncludeFolders')->tmpInstance();

		$data = array($path, $virtualFolder);

		return $model->setFilter($uuid, $data);
	}

	/**
	 * Generate a UUID v4
	 *
	 * @return  string
	 */
	private function uuid_v4()
	{
		$randval = new RandomValue();
		$data    = $randval->generate(16);

		$data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
		$data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}
}
