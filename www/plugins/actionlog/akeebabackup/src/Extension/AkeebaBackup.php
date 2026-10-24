<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\ActionLog\AkeebaBackup\Extension;

defined('_JEXEC') || die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\User\User;
use Joomla\Component\Actionlogs\Administrator\Plugin\ActionLogPlugin;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use ReflectionMethod;

class AkeebaBackup extends ActionLogPlugin implements SubscriberInterface
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;

	private $defaultExtension = 'com_akeebabackup';

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since   9.0.0
	 */
	public static function getSubscribedEvents(): array
	{
		// Only subscribe events if the component is installed and enabled
		if (!ComponentHelper::isEnabled('com_akeebabackup'))
		{
			return [];
		}

		// Register all public onSomething methods as event handlers
		$events   = [];
		$refClass = new \ReflectionClass(self::class);
		$methods  = $refClass->getMethods(ReflectionMethod::IS_PUBLIC);

		foreach ($methods as $method)
		{
			$name = $method->getName();

			if (substr($name, 0, 2) != 'on')
			{
				continue;
			}

			$events[$name] = $name;
		}

		return $events;
	}

	public function onContentAfterSave(Event $event)
	{
		$arguments = array_values($event->getArguments());
		$context   = $arguments[0] ?? '';
		$table     = $arguments[1] ?? null;
		$isNew     = $arguments[2] ?? false;

		switch ($context)
		{
			// Akeeba Backup profiles
			case 'com_akeebabackup.profile':
				$extraData = [
					'title' => $table->description,
					'id'    => $table->getId(),
				];

				if ($isNew)
				{
					$this->logUserAction($extraData, 'COM_AKEEBABACKUP_LOGS_PROFILE_ADD');
				}
				else
				{
					$this->logUserAction($extraData, 'COM_AKEEBABACKUP_LOGS_PROFILE_EDIT');
				}
				break;

			// Akeeba Backup statistics (backup attempts)
			case 'com_akeebabackup.statistic':
				$extraData = [
					'id' => $table->getId(),
				];
				$this->logUserAction($extraData, 'COM_AKEEBABACKUP_LOGS_MANAGE_EDIT');

				break;
		}
	}

	public function onContentChangeState(Event $event)
	{
		$arguments = array_values($event->getArguments());
		$context   = $arguments[0] ?? '';
		$pks       = $arguments[1] ?? [];
		$value     = $arguments[2] ?? 1;

		switch ($context)
		{
			// Akeeba Backup profiles
			case 'com_akeebabackup.profile':
				$languageKey = ($value == 0) ? 'COM_AKEEBABACKUP_LOGS_PROFILE_UNQUICK' : 'COM_AKEEBABACKUP_LOGS_PROFILE_QUICK';

				$this->logUserAction(implode(', ', $pks), $languageKey);
				break;

			// Akeeba Backup statistics (backup attempts)
			case 'com_akeebabackup.statistic':
				$languageKey = ($value == 0) ? 'COM_AKEEBABACKUP_LOGS_MANAGE_UNFREEZE' : 'COM_AKEEBABACKUP_LOGS_MANAGE_FREEZE';

				$this->logUserAction(implode(', ', $pks), $languageKey);
				break;
		}
	}

	public function onContentAfterDelete(Event $event)
	{
		$arguments = array_values($event->getArguments());
		$context   = $arguments[0] ?? '';
		$table     = $arguments[1] ?? null;

		switch ($context)
		{
			// Akeeba Backup profiles
			case 'com_akeebabackup.profile':
				$this->logUserAction([
					'id' => $table->getId(),
				], 'COM_AKEEBABACKUP_LOGS_PROFILE_DELETE');
				break;

			// Akeeba Backup statistics (backup attempts)
			case 'com_akeebabackup.statistic':
				$this->logUserAction([
					'id' => $table->getId(),
				], 'COM_AKEEBABACKUP_LOGS_MANAGE_DELETE');
				break;
		}
	}

	public function onComAkeebabackupProfileControllerBeforeExport(Event $event)
	{
		$arguments = array_values($event->getArguments());
		$id        = $arguments[1] ?? null;

		$this->logUserAction([
			'id' => $id,
		], 'COM_AKEEBABACKUP_LOGS_PROFILE_EXPORT');
	}

	public function onComAkeebabackupProfileControllerAfterImport(Event $event)
	{
		$arguments = array_values($event->getArguments());
		$id        = $arguments[1] ?? null;

		$this->logUserAction([
			'id' => $id,
		], 'COM_AKEEBABACKUP_LOGS_PROFILE_IMPORT');
	}

	public function onComAkeebabackupModelStatisticAfterDeleteFiles(Event $event)
	{
		$arguments = array_values($event->getArguments());
		$id        = $arguments[1] ?? null;

		$this->logUserAction([
			'id' => $id,
		], 'COM_AKEEBABACKUP_LOGS_MANAGE_DELETEFILES');
	}

	public function onComAkeebabackupManageControllerBeforeDownload(Event $event)
	{
		$arguments = array_values($event->getArguments());
		$id        = $arguments[1] ?? null;
		$part      = $arguments[2] ?? null;

		$this->logUserAction([
			'id'   => $id,
			'part' => $part,
		], 'COM_AKEEBABACKUP_LOGS_MANAGE_DELETEFILES');
	}

	public function onComAkeebabackupConfigurationControllerAfterApply(Event $event)
	{
		$arguments = array_values($event->getArguments());
		$id        = $arguments[1] ?? null;

		$this->logUserAction([
			'id' => $id,
		], 'COM_AKEEBABACKUP_LOGS_CONFIGURATION_EDIT');
	}

	public function onComAkeebabackupModelBackupStart(Event $event)
	{
		$arguments = array_values($event->getArguments());
		$id        = $arguments[1] ?? null;
		$profile   = $arguments[2] ?? null;
		$this->logUserAction([
			'id'      => $id,
			'profile' => $profile,
		], 'COM_AKEEBABACKUP_LOGS_BACKUP_RUN');
	}

	public function onComAkeebabackupLogControllerDownload(Event $event)
	{
		$arguments = array_values($event->getArguments());
		$tag       = $arguments[1] ?? null;

		if (is_null($tag))
		{
			$this->logUserAction([
				'title' => 'latest',
			], 'COM_AKEEBABACKUP_LOGS_LOG_DOWNLOAD_LATEST');

			return;
		}

		$this->logUserAction([
			'title' => $tag,
		], 'COM_AKEEBABACKUP_LOGS_LOG_DOWNLOAD');
	}

	public function onComAkeebabackupTransferControllerBeforeInitialiseUpload(Event $event)
	{
		$this->logUserAction([
			'title' => $this->getApplication()->getSession()->get('akeebabackup.transfer.url', null),
		], 'COM_AKEEBABACKUP_LOGS_TRANSFER_RUN');
	}

	public function onComAkeebabackupRemoteFilesControllerFetch(Event $event)
	{
		$arguments = array_values($event->getArguments());
		$id        = $arguments[1] ?? null;

		$this->logUserAction([
			'id' => $id,
		], 'COM_AKEEBABACKUP_LOGS_REMOTEFILE_FETCH');
	}

	public function onComAkeebabackupRemoteFilesControllerDownload(Event $event)
	{
		$arguments = array_values($event->getArguments());
		$id        = $arguments[1] ?? null;
		$part      = $arguments[2] ?? null;

		$this->logUserAction([
			'id'   => $id,
			'part' => $part,
		], 'COM_AKEEBABACKUP_LOGS_REMOTEFILE_DOWNLOAD');
	}

	public function onComAkeebabackupRemoteFilesControllerDelete(Event $event)
	{
		$arguments = array_values($event->getArguments());
		$id        = $arguments[1] ?? null;

		$this->logUserAction([
			'id' => $id,
		], 'COM_AKEEBABACKUP_LOGS_REMOTEFILE_DELETE');
	}

	public function onComAkeebabackupUploadControllerStart(Event $event)
	{
		$arguments = array_values($event->getArguments());
		$id        = $arguments[1] ?? null;

		$this->logUserAction([
			'id' => $id,
		], 'COM_AKEEBABACKUP_LOGS_UPLOADS_ADD');
	}

	public function onComAkeebabackupUploadControllerSuccessfulImport(Event $event)
	{
		$arguments = array_values($event->getArguments());
		$id        = $arguments[1] ?? null;

		$this->logUserAction([
			'id' => $id,
		], 'COM_AKEEBABACKUP_LOGS_DISCOVER_IMPORT');
	}

	public function onComAkeebabackupS3importControllerSuccessfulImport(Event $event)
	{
		$arguments = array_values($event->getArguments());
		$bucket    = $arguments[1] ?? null;
		$folder    = $arguments[2] ?? null;
		$file      = $arguments[3] ?? null;

		$path = $folder . ($folder == '' ? '' : '/') . $file;

		$this->logUserAction([
			'bucket' => $bucket,
			'path'   => $path,
		], 'COM_AKEEBABACKUP_LOGS_S3IMPORT_IMPORT');
	}

	/**
	 * Log a user action.
	 *
	 * This is a simple wrapper around self::addLog
	 *
	 * @param   string|array  $title               Language key for title or an array of additional data to record in
	 *                                             the audit log.
	 * @param   string        $messageLanguageKey  Language key describing the user action taken.
	 * @param   string|null   $context             The name of the extension being logged (default: use
	 *                                             $this->defaultExtension).
	 * @param   User|null     $user                User object taking this action (default: currently logged in user).
	 *
	 * @return  void
	 *
	 * @see     self::addLog
	 * @since   9.0.0
	 */
	private function logUserAction($title, string $messageLanguageKey, ?string $context = null, ?User $user = null): void
	{
		// Get the user if not defined
		$user = $user ?? $this->getApplication()->getIdentity();

		// No log for guests
		if (empty($user) || ($user->guest))
		{
			return;
		}

		// Default extension if none defined
		$context = $context ?? $this->defaultExtension;

		$message = [
			'username'    => $user->username,
			'accountlink' => 'index.php?option=com_users&task=user.edit&id=' . $user->id,
		];

		if (!is_array($title))
		{
			$title = [
				'title' => $title,
			];
		}

		$message = array_merge($message, $title);

		$this->addLog([$message], $messageLanguageKey, $context, $user->id);
	}
}