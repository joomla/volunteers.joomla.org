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
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerRegisterTasksTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerReusableModelsTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Model\BackupModel;
use Akeeba\Component\AkeebaBackup\Administrator\Model\ConfigurationwizardModel;
use Akeeba\Component\AkeebaBackup\Administrator\Model\ControlpanelModel;
use Akeeba\Component\AkeebaBackup\Administrator\Model\IncludefoldersModel;
use Akeeba\Component\AkeebaBackup\Administrator\Model\UpdatesModel;
use Akeeba\Component\AkeebaBackup\Administrator\Model\UpgradeModel;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Akeeba\Engine\Util\RandomValue;
use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Uri\Uri;
use Joomla\Input\Input;
use RuntimeException;

class ControlpanelController extends BaseController
{
	use ControllerEventsTrait;
	use ControllerCustomACLTrait;
	use ControllerRegisterTasksTrait;
	use ControllerReusableModelsTrait;

	/**
	 * The default view.
	 *
	 * @var    string
	 * @since  1.6
	 */
	protected $default_view = 'Controlpanel';

	public function __construct($config = [], ?MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

		$this->registerControllerTasks('main');
	}

	public function main($cachable = false, $urlparams = [])
	{
		/** @var ControlpanelModel $model */
		$model = $this->getModel('Controlpanel', 'Administrator');

		// Invalidate stale backups
		$params = ComponentHelper::getParams('com_akeebabackup');

		try
		{
			Factory::resetState([
				'global' => true,
				'log'    => false,
				'maxrun' => $params->get('failure_timeout', 180),
			]);
		}
		catch (Exception $e)
		{
			// This will die if the output directory is invalid. Let it die, then.
		}

		// Just in case the reset() loaded a stale configuration...
		Platform::getInstance()->load_configuration();
		Platform::getInstance()->apply_quirk_definitions();

		// Let's make sure the temporary and output directories are set correctly and writable...
		/** @var ConfigurationwizardModel $wizmodel */
		$wizmodel = $this->getModel('Configurationwizard', 'Administrator');
		$wizmodel->autofixDirectories();

		// Rebase Off-site Folder Inclusion filters to use site path variables
		/** @var IncludefoldersModel $incFoldersModel */
		$incFoldersModel = $this->getModel('Includefolders', 'Administrator');

		if (is_object($incFoldersModel) && method_exists($incFoldersModel, 'rebaseFiltersToSiteDirs'))
		{
			$incFoldersModel->rebaseFiltersToSiteDirs();
		}

		// Check if we need to toggle the settings encryption feature
		$model->checkSettingsEncryption();
		$model->updateMagicParameters($this->app->bootComponent('com_akeebabackup')->getComponentParametersService());

		// Convert existing log files to the new .log.php format
		/** @var BackupModel $backupModel */
		$backupModel = $this->getModel('Backup', 'Administrator');
		$backupModel->convertLogFiles();

		// Run the automatic update site refresh
		/** @var UpdatesModel $updateModel */
		$updateModel = $this->getModel('Updates', 'Administrator');
		$updateModel->refreshUpdateSite();

		// Push the update model to the HTML view
		$this->getView()->setModel($updateModel, false);

		// Make sure all of my extensions are assigned to my package.
		/** @var UpgradeModel $upgradeModel */
		$upgradeModel = $this->getModel('Upgrade', 'Administrator');
		$upgradeModel->init();
		$upgradeModel->adoptMyExtensions();

		// Push the upgrade model to the HTML view
		$this->getView()->setModel($upgradeModel, false);

		// Push the usage statistics model into the HTML view
		$usagestatsModel = $this->getModel('Usagestats');
		$this->getView()->setModel($usagestatsModel, false);

		// Push the Push model into the view
		$pushModel = $this->getModel('Push');
		$this->getView()->setModel($pushModel, false);

		return parent::display($cachable, $urlparams);
	}

	public function SwitchProfile($cachable = false, $urlparams = [])
	{
		// CSRF prevention
		$this->checkToken('request');

		$newProfile = $this->input->get('profileid', -10, 'int');

		if (!is_numeric($newProfile) || ($newProfile <= 0))
		{
			$this->setRedirect(Uri::base() . 'index.php?option=com_akeebabackup', Text::_('COM_AKEEBABACKUP_CPANEL_PROFILE_SWITCH_ERROR'), 'error');

			return;
		}

		JoomlaFactory::getApplication()->getSession()->set('akeebabackup.profile', $newProfile);
		$returnurl = $this->input->get('returnurl', '', 'base64');
		$url       = Utils::safeDecodeReturnUrl($returnurl);

		if (empty($url))
		{
			$url = 'index.php?option=com_akeebabackup';
		}

		if ((strpos($url, 'http://') === false) && (strpos($url, 'https://') === false))
		{
			$url = Uri::base() . ltrim($url, '/');
		}

		$this->setRedirect($url, Text::_('COM_AKEEBABACKUP_CPANEL_PROFILE_SWITCH_OK'));
	}

	/**
	 * Reset the Secret Word for front-end and remote backup
	 *
	 * @return  void
	 */
	public function resetSecretWord($cachable = false, $urlparams = [])
	{
		// CSRF prevention
		$this->checkToken('request');

		$newSecret = JoomlaFactory::getApplication()->getSession()->get('akeebabackup.cpanel.newSecretWord', null);

		if (empty($newSecret))
		{
			$random    = new RandomValue();
			$newSecret = $random->generateString(32);
			JoomlaFactory::getApplication()->getSession()->set('akeebabackup.cpanel.newSecretWord', $newSecret);
		}

		$params = ComponentHelper::getParams('com_akeebabackup');

		$params->set('frontend_secret_word', $newSecret);

		$this->app->bootComponent('com_akeebabackup')
		          ->getComponentParametersService()
		          ->save($params);

		JoomlaFactory::getApplication()->getSession()->set('akeebabackup.cpanel.newSecretWord', null);

		$msg = Text::sprintf('COM_AKEEBABACKUP_CPANEL_MSG_FESECRETWORD_RESET', $newSecret);

		$url = Uri::base() . 'index.php?option=com_akeebabackup';
		$this->setRedirect($url, $msg);
	}

	/**
	 * Check the security of the backup output directory and return the results for consumption through AJAX
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 *
	 * @since   7.0.3
	 */
	public function checkOutputDirectory($cachable = false, $urlparams = [])
	{
		// Anti-CSRF protection. The token is sent by akeebabackup.System.doAjax() in the POST body.
		$this->checkToken();

		/** @var ControlpanelModel $model */
		$model  = $this->getModel('Controlpanel', 'Administrator');
		$outDir = $model->getOutputDirectory();

		try
		{
			$result = $model->getOutputDirectoryWebAccessibleState($outDir);
		}
		catch (RuntimeException $e)
		{
			$result = [
				'readFile'   => false,
				'listFolder' => false,
				'isSystem'   => $model->isOutputDirectoryInSystemFolder(),
				'hasRandom'  => $model->backupFilenameHasRandom(),
			];
		}

		@ob_end_clean();

		echo '###' . json_encode($result) . '###';

		JoomlaFactory::getApplication()->close();
	}

	/**
	 * Add security files to the output directory of the currently configured backup profile
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 *
	 * @since   7.0.3
	 */
	public function fixOutputDirectory($cachable = false, $urlparams = [])
	{
		// CSRF prevention
		$this->checkToken();

		/** @var ControlpanelModel $model */
		$model  = $this->getModel('Controlpanel', 'Administrator');
		$outDir = $model->getOutputDirectory();

		$fsUtils = Factory::getFilesystemTools();
		$fsUtils->ensureNoAccess($outDir, true);

		$this->setRedirect(Uri::base() . 'index.php?option=com_akeebabackup');
	}

	/**
	 * Adds the [RANDOM] variable to the backup output filename, save the configuration and reload the Control Panel.
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 *
	 * @since   7.0.3
	 */
	public function addRandomToFilename($cachable = false, $urlparams = [])
	{
		// CSRF prevention
		$this->checkToken();

		$registry     = Factory::getConfiguration();
		$templateName = $registry->get('akeeba.basic.archive_name');

		if (strpos($templateName, '[RANDOM]') === false)
		{
			$templateName .= '-[RANDOM]';
			$registry->set('akeeba.basic.archive_name', $templateName);
			Platform::getInstance()->save_configuration();
		}

		$this->setRedirect(Uri::base() . 'index.php?option=com_akeebabackup');
	}

	/**
	 * Dismisses the Core to Pro upsell for 15 days
	 *
	 * @return  void
	 */
	public function dismissUpsell($cachable = false, $urlparams = [])
	{
		// Anti-CSRF protection. The token is appended to the upsell dismissal link.
		$this->checkToken('get');

		$reset = $this->input->getBool('reset', false);

		$params = ComponentHelper::getParams('com_akeebabackup');

		// Reset the flag so the updates could take place
		$params->set('lastUpsellDismiss', $reset ? 0 : time());

		$this->app->bootComponent('com_akeebabackup')
			->getComponentParametersService()
			->save($params);

		$this->setRedirect(Uri::base() . 'index.php?option=com_akeebabackup');
	}

}