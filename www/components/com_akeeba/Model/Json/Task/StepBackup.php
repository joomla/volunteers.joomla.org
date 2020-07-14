<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Site\Model\Json\Task;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;

/**
 * Step through a backup job
 */
class StepBackup extends AbstractTask
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
			'profile'  => null,
			'tag'      => AKEEBA_BACKUP_ORIGIN,
			'backupid' => null,
		);

		$defConfig = array_merge($defConfig, $parameters);

		$profile  = $filter->clean($defConfig['profile'], 'int');
		$tag      = $filter->clean($defConfig['tag'], 'cmd');
		$backupid = $filter->clean($defConfig['backupid'], 'cmd');

		if (is_null($backupid) && defined('AKEEBA_BACKUP_ID'))
		{
			$tag = AKEEBA_BACKUP_ID;
		}

		if (empty($backupid))
		{
			throw new \RuntimeException("JSON API :: stepBackup -- You have not provided the required backupid parameter. This parameter is MANDATORY since May 2016. Please update your client software to include this parameter.");
		}

		// Try to set the profile from the setup parameters
		if (!empty($profile))
		{
			$profile  = max(1, $profile); // Make sure $profile is a positive integer >= 1
			$this->container->platform->setSessionVar('profile', $profile);
			define('AKEEBA_PROFILE', $profile);
		}

		/** @var \Akeeba\Backup\Site\Model\Backup $model */
		$model = $this->container->factory->model('Backup')->tmpInstance();
		$model->setState('tag', $tag);
		$model->setState('backupid', $backupid);
		$array = $model->stepBackup(false);

		if ($array['Error'] != '')
		{
			throw new \RuntimeException('A backup error has occurred: ' . $array['Error'], 500);
		}

		// BackupID contains the numeric backup record ID. backupid contains the backup id (usually in the form id123)
		$statistics        = Factory::getStatistics();
		$array['BackupID'] = $statistics->getId();

		// Remote clients expect a boolean, not an integer.
		$array['HasRun'] = ($array['HasRun'] === 0);

		return $array;
	}
}
