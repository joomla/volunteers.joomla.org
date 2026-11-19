<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Controller;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Helper\Utils;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerCustomACLTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerEventsTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerProfileAccessTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerRegisterTasksTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Model\BackupModel;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Session\Session;
use Joomla\Input\Input;
use Exception;
use Throwable;

class BackupController extends BaseController
{
	use ControllerEventsTrait;
	use ControllerCustomACLTrait;
	use ControllerRegisterTasksTrait;
	use ControllerProfileAccessTrait;

	private bool $noFlush = false;

	/**
	 * Should the backend backup AJAX flow check the anti-CSRF tokens?
	 *
	 * Controlled by the "Backend backup checks the anti-CSRF token" component option, on by default. Turning it off is
	 * NOT a security risk we need to fix. It exists for the very rare server whose session handling is broken beyond
	 * repair, where the token checks make every backend backup fail. On such a server the alternatives are having no
	 * backup at all (a far worse security outcome for the site) or asking the host to change the session handling,
	 * which is often impossible and can break the site, again leaving it without a backup. Only a user who can change
	 * the component's Options (core.admin or core.options) can turn it off, and the backup still needs a logged-in user
	 * with the Backup permission. See .claude/security-audit-triage.md and determination (b) of
	 * documentation/cra/risk-assessment.md.
	 *
	 * @var   bool
	 * @since 10.5.0
	 */
	private bool $checkCsrf = true;

	public function __construct(
		$config = [], ?MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null
	)
	{
		parent::__construct($config, $factory, $app, $input);

		$cParams = ComponentHelper::getParams('com_akeebabackup');

		$this->noFlush   = $cParams->get('no_flush', 0) == 1;
		$this->checkCsrf = $cParams->get('backend_backup_csrf', 1) != 0;

		$this->registerControllerTasks();
	}

	/**
	 * This task handles the AJAX requests
	 */
	public function ajax()
	{
		$ajaxTask = $this->input->get('ajax', '', 'cmd');

		/**
		 * Anti-CSRF protection for the backend backup AJAX flow.
		 *
		 * The 'start' action launches a brand-new backup. It fires right after the Backup page has loaded, so the global
		 * Joomla anti-CSRF token (sent in the POST body by akeebabackup.System.doAjax) is current and we validate it the
		 * usual way.
		 *
		 * Every other action (step, pushFail, …) operates on an already-running backup. A backup may take a very long
		 * time, during which the global session token could rotate or expire. We therefore authenticate those requests
		 * with a per-backup token: it is generated when the backup starts (further down), stored in the session keyed by
		 * the backup ID and echoed back by the page on every subsequent request.
		 *
		 * The global token, which the page also sends, is accepted for these actions too. Some servers do not keep the
		 * per-backup token which the 'start' request wrote to the session (e.g. a concurrent request overwriting the
		 * session, or session storage not shared between PHP processes). The global token is still a valid anti-CSRF
		 * token, so accepting it only costs us the immunity to token rotation on such servers.
		 *
		 * Neither check runs when the "Backend backup checks the anti-CSRF token" option is off; see $checkCsrf.
		 */
		if ($this->checkCsrf)
		{
			if ($ajaxTask === 'start')
			{
				if (!Session::checkToken('post'))
				{
					$this->outputBackupError(Text::_('JINVALID_TOKEN_NOTICE'));
				}
			}
			elseif (!$this->hasValidBackupToken() && !Session::checkToken('post'))
			{
				$this->logRejectedBackupStep();

				$this->outputBackupError(Text::_('COM_AKEEBABACKUP_BACKUP_ERROR_STEP_TOKEN'));
			}
		}

		$profile_id = $this->input->get('profileid', Platform::getInstance()->get_active_profile(), 'int');

		if ($profile_id <= 0)
		{
			$profile_id = (int) Platform::getInstance()->get_active_profile();
		}

		// Double check that the user is actually allowed to access this profile
		if (!$this->checkProfileAccess($profile_id))
		{
			$this->outputBackupError(Text::_('COM_AKEEBABACKUP_BACKUP_ERROR_PROFILE_NO_ACCESS'));
		}

		/**
		 * Starting a backup runs with the session's active profile. Make it the profile the page asked for, which may
		 * not be the session's if the active profile changed after the page loaded (e.g. in another browser tab).
		 */
		if ($ajaxTask === 'start')
		{
			$this->app->getSession()->set('akeebabackup.profile', $profile_id);
			Platform::getInstance()->load_configuration($profile_id);
		}

		/** @var BackupModel $model */
		$model = $this->getModel('Backup', 'Administrator');

		// Push all necessary information to the model's state
		$model->setState('profile', $profile_id);
		$model->setState('ajax', $this->input->get('ajax', '', 'cmd'));
		$model->setState('description', $this->input->get('description', '', 'string'));
		$model->setState('comment', $this->input->get('comment', '', 'html'));
		$model->setState('jpskey', $this->input->get('jpskey', '', 'raw'));
		$model->setState('angiekey', $this->input->get('angiekey', '', 'raw'));
		$model->setState('backupid', $this->input->get('backupid', null, 'cmd'));
		$model->setState('tag', $this->input->get('tag', 'backend', 'cmd'));
		$model->setState('errorMessage', $this->input->getString('errorMessage', ''));

		// System Restore Point backup state variables (obsolete)
		$model->setState('type', strtolower($this->input->get('type', '', 'cmd')));
		$model->setState('name', strtolower($this->input->get('name', '', 'cmd')));
		$model->setState('group', strtolower($this->input->get('group', '', 'cmd')));
		$model->setState('customdirs', $this->input->get('customdirs', [], 'array'));
		$model->setState('customfiles', $this->input->get('customfiles', [], 'array'));
		$model->setState('extraprefixes', $this->input->get('extraprefixes', [], 'array'));
		$model->setState('customtables', $this->input->get('customtables', [], 'array'));
		$model->setState('skiptables', $this->input->get('skiptables', [], 'array'));
		$model->setState('langfiles', $this->input->get('langfiles', [], 'array'));
		$model->setState('xmlname', $this->input->getString('xmlname', ''));

		// Set up the tag
		define('AKEEBA_BACKUP_ORIGIN', $this->input->get('tag', 'backend', 'cmd'));

		// Run the backup step
		$ret_array = $model->runBackup();

		if ($ajaxTask === 'start')
		{
			/**
			 * Starting a backup ran Factory::resetState() with maxrun = 0 (see BackupModel::startBackup), marking every
			 * previously running backup as failed. Any token left behind by such a backup is now dead weight in the
			 * session, so this is exactly the right moment to collect it.
			 */
			$this->pruneBackupTokens();

			/**
			 * Issue a fresh per-backup anti-CSRF token so the page can authenticate the subsequent backup steps even if
			 * the global session token rotates during a long-running backup.
			 */
			if (!empty($ret_array['backupid']))
			{
				$backupToken = bin2hex(random_bytes(32));

				$this->setBackupTokens(
					array_merge($this->getBackupTokens(), [$ret_array['backupid'] => $backupToken])
				);

				$ret_array['backupToken'] = $backupToken;
			}
		}

		// Once the backup is complete the per-backup token is no longer needed.
		if (!empty($ret_array['backupid']) && isset($ret_array['HasRun']) && $ret_array['HasRun'] == 1)
		{
			$tokens = $this->getBackupTokens();

			unset($tokens[$ret_array['backupid']]);

			$this->setBackupTokens($tokens);
		}

		$this->outputBackupResponse($ret_array);
	}

	/**
	 * Validates the per-backup anti-CSRF token submitted by the page against the one stored in the session.
	 *
	 * @return  bool  True if a valid per-backup token was submitted for the requested backup ID.
	 * @since   10.3.7
	 */
	private function hasValidBackupToken(): bool
	{
		$backupId = $this->input->get('backupid', null, 'cmd');
		$token    = $this->input->get('backupToken', '', 'raw');

		if (empty($backupId) || empty($token) || !is_string($token))
		{
			return false;
		}

		$expected = $this->getBackupTokens()[$backupId] ?? null;

		return !empty($expected) && is_string($expected) && hash_equals($expected, $token);
	}

	/**
	 * Records a backup step rejected for want of a valid anti-CSRF token in that backup's log file.
	 *
	 * The rejection happens before the backup engine runs, so the log would otherwise just stop mid-backup without a
	 * word as to why. We only ever write to the log of a backup which is still running, so a forged request cannot make
	 * us create log files of its choosing.
	 *
	 * @return  void
	 * @since   10.5.0
	 */
	private function logRejectedBackupStep(): void
	{
		$backupId = $this->input->get('backupid', null, 'cmd');

		if (empty($backupId))
		{
			return;
		}

		try
		{
			$record = null;

			foreach (Platform::getInstance()->get_running_backups() ?: [] as $runningBackup)
			{
				if (($runningBackup['backupid'] ?? null) === $backupId)
				{
					$record = $runningBackup;

					break;
				}
			}

			if (empty($record) || empty($record['tag']))
			{
				return;
			}

			// The log file lives in the output directory of the backup's own profile.
			Platform::getInstance()->load_configuration((int) $record['profile_id']);

			$submittedToken = $this->input->get('backupToken', '', 'raw');
			$sessionTokens  = $this->getBackupTokens();

			$logger = Factory::getLog();
			$logger->open($record['tag'] . '.' . $backupId);
			// An error, not a warning: the rejection ends the backup, and "errors only" log levels must still show it.
			$logger->error(
				sprintf(
					'Backup step rejected: neither the per-backup anti-CSRF token nor the Joomla session token is valid. Per-backup token submitted: %s. The session holds a per-backup token for this backup: %s. The session holds per-backup tokens for %d backup(s). The Joomla session was most likely lost or replaced while the backup was running.',
					empty($submittedToken) ? 'no' : 'yes',
					isset($sessionTokens[$backupId]) ? 'yes' : 'no',
					count($sessionTokens)
				)
			);
			$logger->close();
		}
		catch (Throwable $e)
		{
			// Logging is a diagnostic courtesy. It must never stand in the way of reporting the actual error.
		}
	}

	/**
	 * Returns the per-backup anti-CSRF tokens stored in the session, keyed by backup ID.
	 *
	 * The tokens live in a single session node rather than one node per backup ID. Joomla's session is backed by a
	 * Registry, so a key of the form 'foo.bar' is a *path*, not a literal key — storing tokens under
	 * 'akeebabackup.backuptokens.<backupid>' would let a backup ID containing a dot create a nested node we could
	 * neither find nor delete afterwards.
	 *
	 * @return  array  Backup ID => token. Empty if no backup has issued a token yet.
	 * @since   10.3.7
	 */
	private function getBackupTokens(): array
	{
		return (array) ($this->app->getSession()->get('akeebabackup.backuptokens', []) ?: []);
	}

	/**
	 * Stores the per-backup anti-CSRF tokens in the session, replacing whatever was there before.
	 *
	 * @param   array  $tokens  Backup ID => token.
	 *
	 * @return  void
	 * @since   10.3.7
	 */
	private function setBackupTokens(array $tokens): void
	{
		$this->app->getSession()->set('akeebabackup.backuptokens', $tokens);
	}

	/**
	 * Discards the per-backup anti-CSRF tokens of backups which are no longer running.
	 *
	 * Only a running backup has any use for a token. A backup which failed, was cancelled, or was simply abandoned
	 * half-way through — the user closed the browser tab — never reaches the completion step which removes its token,
	 * so without this the session would accumulate a 64 character token per abandoned backup, forever.
	 *
	 * @return  void
	 * @since   10.3.7
	 */
	private function pruneBackupTokens(): void
	{
		$tokens = $this->getBackupTokens();

		if (empty($tokens))
		{
			return;
		}

		try
		{
			$runningBackups = Platform::getInstance()->get_running_backups() ?: [];
		}
		catch (Exception $e)
		{
			// If we cannot tell which backups are running we must not throw away tokens which may still be in use.
			return;
		}

		$runningIds = array_filter(
			array_map(
				function (array $record) {
					return $record['backupid'] ?? null;
				},
				$runningBackups
			)
		);

		$this->setBackupTokens(array_intersect_key($tokens, array_flip($runningIds)));
	}

	/**
	 * Outputs a backup engine return array as the triple-hash wrapped JSON the page expects, then closes the app.
	 *
	 * @param   array  $ret_array  The backup engine return array to output.
	 *
	 * @return  void
	 * @since   10.3.7
	 */
	private function outputBackupResponse(array $ret_array): void
	{
		// We use this nasty trick to avoid broken 3PD plugins from barfing all over our output
		@ob_end_clean();
		header('Content-type: text/plain');
		header('Connection: close');
		// Make sure the response is never cached
		header('Expires: Wed, 17 Aug 2005 00:00:00 GMT');
		header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
		header('Pragma: no-cache');
		echo '###' . json_encode($ret_array) . '###';

		if (!$this->noFlush)
		{
			flush();
		}

		$this->app->close();
	}

	/**
	 * Outputs a backup error message in the format the page expects, then closes the app.
	 *
	 * @param   string  $error  The error message to report to the page.
	 *
	 * @return  void
	 * @since   10.3.7
	 */
	private function outputBackupError(string $error): void
	{
		$this->outputBackupResponse(
			[
				'HasRun'   => 0,
				'Domain'   => 'init',
				'Step'     => '',
				'Substep'  => '',
				'Error'    => $error,
				'Warnings' => [],
				'Progress' => 0,
			]
		);
	}

	/**
	 * Default task; shows the initial page where the user selects a profile and enters description and comment
	 */
	public function display($cachable = false, $urlparams = [])
	{
		$document   = $this->app->getDocument();
		$viewType   = $document->getType();
		$viewName   = $this->input->get('view', $this->default_view);
		$viewLayout = $this->input->get('layout', 'default', 'string');

		$view = $this->getView(
			$viewName, $viewType, '',
			[
				'base_path' => $this->basePath,
				'layout'    => $viewLayout,
			]
		);

		// Push the Control Panel model
		$controlPanelModel = $this->getModel('Controlpanel', 'Administrator');
		$view->setModel($controlPanelModel, false);

		// Get/Create the default model
		/** @var BackupModel $model */
		$model = $this->getModel('Backup', 'Administrator');
		$view->setModel($model, true);

		// Push the document
		$view->document = $document;

		// Did the user ask to switch the active profile?
		$newProfile = $this->input->get('profileid', -10, 'int');
		$autostart  = $this->input->get('autostart', 0, 'int');

		/**
		 * Starting a backup without anyone pressing the Backup Now button needs an anti-CSRF token, in the URL or in the
		 * request body. Otherwise any page could send a logged-in Super User to a link which wastes server resources by
		 * running a backup. Without a valid token we still show the Backup page, but the user has to start the backup.
		 * (The One-click Backup icons and Backup on Update add the token; menu items cannot, so they degrade this way.)
		 */
		if ($autostart && !Session::checkToken('request'))
		{
			$autostart = 0;
		}

		// Only activate a profile the current user is actually allowed to access
		if (is_numeric($newProfile) && ($newProfile > 0) && !$this->checkProfileAccess($newProfile))
		{
			$this->app->enqueueMessage(Text::_('COM_AKEEBABACKUP_PROFILE_ERR_NOACCESS'), 'error');

			$newProfile = -10;
		}

		if (is_numeric($newProfile) && ($newProfile > 0))
		{
			/**
			 * We have to remove CSRF protection due to the way the Joomla administrator menu manager works. Menu item
			 * options are passed as URL parameters. However, we cannot pass dynamic parameters (like the token). This
			 * means that a user can create a menu item with a specific backup profile ID. Normally this would cause a
			 * 403 which is frustrating to the user because they might want to give their client the option to run a
			 * backup with a specific profile AND let them enter a description and comment. Therefore we have to remove
			 * the CSRF protection.
			 *
			 * NB! We do understand the potential risk involved. Between Joomla's BAD implementation of custom
			 * administrator menus and user demands for features we have to (have these very vocal users and everyone
			 * else) assume that (actually really small) risk.
			 */
			// $this->checkToken();
			$this->app->getSession()->set('akeebabackup.profile', $newProfile);

			/**
			 * DO NOT REMOVE!
			 *
			 * The Model will only try to load the configuration after nuking the factory. This causes Profile 1 to be
			 * loaded first. Then it figures out it needs to load a different profile and it does – but the protected keys
			 * are NOT replaced, meaning that certain configuration parameters are not replaced. Most notably, the chain.
			 * This causes backups to behave weirdly. So, DON'T REMOVE THIS UNLESS WE REFACTOR THE MODEL.
			 */
			Platform::getInstance()->load_configuration($newProfile);
		}

		// Deactivate the menus
		$this->app->getInput()->set('hidemainmenu', 1);

		// Sanitize the return URL
		$returnUrl = $this->input->getRaw('returnurl', '');
		$returnUrl = Utils::safeDecodeReturnUrl($returnUrl);

		// Push data to the model
		//var_dump($model->getState('profile'));
		$model->setState('profile', $newProfile);
		$model->setState('description', $this->input->get('description', '', 'string'));
		$model->setState('comment', $this->input->get('comment', '', 'html'));
		$model->setState('ajax', $this->input->get('ajax', '', 'cmd'));
		$model->setState('autostart', $autostart);
		$model->setState('jpskey', $this->input->get('jpskey', '', 'raw'));
		$model->setState('angiekey', $this->input->get('angiekey', '', 'raw'));
		$model->setState('returnurl', $returnUrl);
		$model->setState('backupid', $this->input->get('backupid', null, 'cmd'));

		$view->display();

		return $this;
	}
}