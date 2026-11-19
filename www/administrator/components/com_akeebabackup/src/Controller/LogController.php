<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerCustomACLTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerEventsTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerProfileAccessTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerProfileRestrictionTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerRegisterTasksTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerReusableModelsTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Model\LogModel;
use Akeeba\Engine\Platform;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Input\Input;

class LogController extends BaseController
{
	use ControllerEventsTrait;
	use ControllerCustomACLTrait
	{
		ControllerCustomACLTrait::onBeforeExecute as onCustomACLBeforeExecute;
	}
	use ControllerRegisterTasksTrait;
	use ControllerReusableModelsTrait;
	use ControllerProfileAccessTrait;
	use ControllerProfileRestrictionTrait
	{
		ControllerProfileRestrictionTrait::onBeforeExecute as onBeforeExecuteRestrictedProfile;
	}

	private bool $noFlush = false;

	public function __construct(
		$config = [], ?MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null
	)
	{
		parent::__construct($config, $factory, $app, $input);

		$this->noFlush = ComponentHelper::getParams('com_akeebabackup')->get('no_flush', 0) == 1;

		$this->registerControllerTasks('main');
	}

	/**
	 * Display the log page
	 *
	 * @return  void
	 */
	public function onBeforeMain()
	{
		$tag    = $this->input->get('tag', null, 'cmd');
		$latest = $this->input->get('latest', false, 'int');

		if (empty($tag))
		{
			$tag = null;
		}

		/** @var LogModel $model */
		$model = $this->getModel('Log', 'Administrator');

		if ($latest)
		{
			$logFiles = $model->getLogFiles();
			$tag      = array_shift($logFiles);
		}

		$model->setState('tag', $tag);

		Platform::getInstance()->load_configuration(Platform::getInstance()->get_active_profile());
	}

	/**
	 * Renders the contents of the log, used inside the IFRAME of the log page
	 *
	 * @return  void
	 */
	public function iframe()
	{
		$tag = $this->input->get('tag', null, 'cmd');

		if (empty($tag))
		{
			$tag = null;
		}

		/** @var LogModel $model */
		$model = $this->getModel('Log', 'Administrator');
		$model->setState('tag', $tag);

		Platform::getInstance()->load_configuration(Platform::getInstance()->get_active_profile());

		$this->display();
	}

	/**
	 * Download the log file as a text file
	 *
	 * @return  void
	 */
	public function download()
	{
		Platform::getInstance()->load_configuration(Platform::getInstance()->get_active_profile());

		$tag = $this->input->get('tag', null, 'cmd');

		if (empty($tag))
		{
			$tag = null;
		}

		$this->triggerEvent('onDownload', [$tag]);

		$asAttachment = $this->input->getBool('attachment', true);

		@ob_end_clean(); // In case some braindead plugin spits its own HTML
		header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
		header("Content-Description: File Transfer");
		header('Content-Type: text/plain');

		if ($asAttachment)
		{
			header('Content-Disposition: attachment; filename="Akeeba Backup Debug Log.txt"');
		}

		/** @var LogModel $model */
		$model = $this->getModel('Log', 'Administrator');
		$model->setState('tag', $tag);
		$model->echoRawLog();

		if (!$this->noFlush)
		{
			flush();
		}

		$this->app->close();
	}

	public function inlineRaw()
	{
		Platform::getInstance()->load_configuration(Platform::getInstance()->get_active_profile());

		$tag = $this->input->get('tag', null, 'cmd');

		if (empty($tag))
		{
			$tag = null;
		}

		/** @var LogModel $model */
		$model = $this->getModel('Log', 'Administrator');
		$model->setState('tag', $tag);
		echo "<pre>";
		$model->echoRawLog();
		echo "</pre>";
	}

	/**
	 * Runs before any task of this controller.
	 *
	 * Applies the component's ACL privilege map and, optionally, switches the active backup profile. (M5)
	 *
	 * Switching the profile requires an anti-CSRF token, like every other profile switch in this component
	 * (see `ControlpanelController::SwitchProfile()`), and the target profile ID is validated with
	 * `checkProfileAccess()` before it is written into the session -- an unauthorised or invalid ID is silently
	 * ignored, leaving the previously active profile in place, rather than granting the switch. The resulting
	 * active profile is then re-validated by `ControllerProfileRestrictionTrait::onBeforeExecute()`, the same
	 * check every other profile-scoped controller runs.
	 *
	 * @param   string  $task  The task about to be executed, by reference.
	 *
	 * @return  void
	 */
	protected function onBeforeExecute(&$task)
	{
		$this->onCustomACLBeforeExecute($task);

		$profileId = $this->input->getInt('profileid', null);

		if (!empty($profileId) && is_numeric($profileId) && ($profileId > 0))
		{
			// Anti-CSRF protection for the profile switch (M5)
			$this->checkToken('get');

			// Only switch to a profile the current user is actually allowed to access (M5)
			if ($this->checkProfileAccess($profileId))
			{
				$this->app->getSession()->set('akeebabackup.profile', $profileId);
			}
		}

		$this->onBeforeExecuteRestrictedProfile($task);
	}
}