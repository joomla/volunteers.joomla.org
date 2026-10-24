<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Site\Model\Json\Task;

// Protect from unauthorized access
defined('_JEXEC') || die();

use Akeeba\Component\AkeebaBackup\Administrator\Model\BackupModel;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\CMS\Filter\InputFilter;
use RuntimeException;

/**
 * Start a backup job
 */
class StartBackup extends AbstractTask
{
	/**
	 * The backend view.task key this API method is equivalent to.
	 *
	 * @var    string
	 * @since  10.4.0
	 */
	protected $aclKey = 'backup';

	/**
	 * Execute the JSON API task
	 *
	 * @param   array  $parameters  The parameters to this task
	 *
	 * @return  mixed
	 *
	 * @throws  RuntimeException  In case of an error
	 */
	public function execute(array $parameters = [])
	{
		$filter = InputFilter::getInstance();

		// Get the passed configuration values
		$defConfig = [
			'profile'     => 1,
			'description' => '',
			'comment'     => '',
			'backupid'    => null,
			'overrides'   => [],
		];

		$defConfig = array_merge($defConfig, $parameters);

		$profile     = (int) $defConfig['profile'];
		$profile     = max(1, $profile); // Make sure $profile is a positive integer >= 1
		$description = $filter->clean($defConfig['description'], 'string');
		$comment     = $filter->clean($defConfig['comment'], 'string');
		$overrides   = $filter->clean($defConfig['overrides'], 'array');

		if (empty($description))
		{
			/** @var BackupModel $backupModel */
			$backupModel = $this->factory->createModel('Backup', 'Administrator', ['ignore_request' => true]);
			$description = $backupModel->getDefaultDescription() . ' (JSON API)';
		}

		JoomlaFactory::getApplication()->getSession()->set('akeebabackup.profile', $profile);
		define('AKEEBA_PROFILE', $profile);

		/**
		 * DO NOT REMOVE!
		 *
		 * The Model will only try to load the configuration after nuking the factory. This causes Profile 1 to be
		 * loaded first. Then it figures out it needs to load a different profile and it does – but the protected keys
		 * are NOT replaced, meaning that certain configuration parameters are not replaced. Most notably, the chain.
		 * This causes backups to behave weirdly. So, DON'T REMOVE THIS UNLESS WE REFACTOR THE MODEL.
		 */
		Platform::getInstance()->load_configuration($profile);

		/** @var BackupModel $model */
		$model = $this->factory->createModel('Backup', 'Administrator', ['ignore_request' => true]);
		$model->setState('tag', 'json');
		$model->setState('description', $description);
		$model->setState('comment', $comment);
		$model->setState('profile', $profile);

		$array = $model->startBackup($overrides);

		if ($array['Error'] != '')
		{
			throw new RuntimeException('A backup error has occurred: ' . $array['Error'], 500);
		}

		$this->warnAboutDeprecatedApiVersion($array);

		// BackupID contains the numeric backup record ID. backupid contains the backup id (usually in the form id123)
		$statistics        = Factory::getStatistics();
		$array['BackupID'] = $statistics->getId();

		// Remote clients expect a boolean, not an integer.
		$array['HasRun'] = ($array['HasRun'] === 0);
		$array['Profile'] = Platform::getInstance()->get_active_profile();

		return $array;
	}

	/**
	 * Raise a backup warning when the backup was started through the deprecated v2 JSON API.
	 *
	 * The warning is raised through the Akeeba Engine logger, which both writes it to the backup log and pushes it
	 * onto the warnings queue — the queue everything consuming a backup's warnings reads from, including the backup
	 * progress display and the post-backup notification e-mails. A configuration check ("quirk") would not do: those
	 * are reported into the log at the informational level, prefaced with a note that they can usually be ignored,
	 * and they describe the state of the site rather than the manner in which a particular request arrived.
	 *
	 * @param   array  $array  The backup step's return array. The warning is added to it so it also shows up in the
	 *                         response to the very request which raised it.
	 *
	 * @return  void
	 * @since   10.4.0
	 */
	private function warnAboutDeprecatedApiVersion(array &$array): void
	{
		if (!defined('AKEEBA_BACKUP_JSON_API_VERSION') || AKEEBA_BACKUP_JSON_API_VERSION !== 2)
		{
			return;
		}

		$warning = 'This backup was started through version 2 of the Akeeba Backup JSON API, which is deprecated. '
			. 'It will be removed in Akeeba Backup 11.0, currently planned for release alongside Joomla! 7.0 in '
			. 'October 2027. Please move your remote client to version 3 of the JSON API, authenticating with a '
			. 'Joomla! API token.';

		Factory::getLog()->warning($warning);

		$array['Warnings']   = $array['Warnings'] ?? [];
		$array['Warnings'][] = $warning;
	}
}
