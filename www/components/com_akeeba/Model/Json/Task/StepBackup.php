<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Site\Model\Json\Task;

// Protect from unauthorized access
defined('_JEXEC') || die();

use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Joomla\CMS\Filter\InputFilter;

/**
 * Step through a backup job
 */
class StepBackup extends AbstractTask
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
			'tag'      => 'json',
			'backupid' => null,
		];

		$defConfig = array_merge($defConfig, $parameters);

		$tag      = $filter->clean($defConfig['tag'], 'cmd');
		$backupid = $filter->clean($defConfig['backupid'], 'cmd');

		if (empty($backupid))
		{
			throw new \RuntimeException("JSON API :: stepBackup -- You have not provided the required backupid parameter. This parameter is MANDATORY since May 2016. Please update your client software to include this parameter.");
		}

		/** @var \Akeeba\Backup\Site\Model\Backup $model */
		$model = $this->container->factory->model('Backup')->tmpInstance();

		$profile = max(1, (int) $model->getLastBackupProfile($tag, $backupid));
		$this->container->platform->setSessionVar('profile', $profile, 'akeeba');
		define('AKEEBA_PROFILE', $profile);


		$model->setState('tag', $tag);
		$model->setState('backupid', $backupid);
		$model->setState('profile', $profile);

		$array = $model->stepBackup(true);

		if ($array['Error'] != '')
		{
			throw new \RuntimeException('A backup error has occurred: ' . $array['Error'], 500);
		}

		// BackupID contains the numeric backup record ID. backupid contains the backup id (usually in the form id123)
		$statistics        = Factory::getStatistics();
		$array['BackupID'] = $statistics->getId();

		// Remote clients expect a boolean, not an integer.
		$array['HasRun'] = ($array['HasRun'] === 0);
		$array['Profile'] = Platform::getInstance()->get_active_profile();

		return $array;
	}
}
