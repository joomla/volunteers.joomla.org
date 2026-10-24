<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\View\Controlpanel;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Helper\Status;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewLoadAnyTemplateTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewProfileIdAndNameTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewProfileListTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewToolbarTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Model\ControlpanelModel;
use Akeeba\Component\AkeebaBackup\Administrator\Model\UpdatesModel;
use Akeeba\Component\AkeebaBackup\Administrator\Model\UpgradeModel;
use Akeeba\Component\AkeebaBackup\Administrator\Model\UsagestatsModel;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\User\User;
use Joomla\Session\Session;
use Throwable;

#[\AllowDynamicProperties]
class HtmlView extends BaseHtmlView
{
	use ViewProfileListTrait;
	use ViewProfileIdAndNameTrait;
	use ViewLoadAnyTemplateTrait;
	use ViewToolbarTrait;

	/**
	 * List of profiles to display as Quick Icons in the control panel page
	 *
	 * @var   array  Array of stdClass objects
	 */
	public $quickIconProfiles = [];

	/**
	 * The HTML for the backup status cell
	 *
	 * @var   string
	 */
	public $statusCell = '';

	/**
	 * HTML for the warnings (status details)
	 *
	 * @var   string
	 */
	public $detailsCell = '';

	/**
	 * Details of the latest backup as HTML
	 *
	 * @var   string
	 */
	public $latestBackupCell = '';

	/**
	 * Do I have to ask the user to fix the permissions?
	 *
	 * @var   bool
	 */
	public $areMediaPermissionsFixed = false;

	/**
	 * Do I have to ask the user to provide a Download ID?
	 *
	 * @var   bool
	 */
	public $needsDownloadID = false;

	/**
	 * Did a Core edition user provide a Download ID instead of installing Akeeba Backup Professional?
	 *
	 * @var   bool
	 */
	public $coreWarningForDownloadID = false;

	/**
	 * Our extension ID
	 *
	 * @var   int
	 */
	public $extension_id = 0;

	/**
	 * Should I have the browser ask for desktop notification permissions?
	 *
	 * @var   bool
	 */
	public $desktopNotifications = false;

	/**
	 * If front-end backup is enabled and the secret word has an issue (too insecure) we populate this variable
	 *
	 * @var  string
	 */
	public $frontEndSecretWordIssue = '';

	/**
	 * In case the existing Secret Word is insecure we generate a new one. This variable contains the new Secret Word.
	 *
	 * @var  string
	 */
	public $newSecretWord = '';

	/**
	 * Is the mbstring extension installed and enabled? This is required by Joomla and Akeeba Backup to correctly work
	 *
	 * @var  bool
	 */
	public $checkMbstring = true;

	/**
	 * The fancy formatted changelog of the component
	 *
	 * @var  string
	 */
	public $formattedChangelog = '';

	/**
	 * Should I pormpt the user ot run the configuration wizard?
	 *
	 * @var  bool
	 */
	public $promptForConfigurationwizard = false;

	/**
	 * How many warnings do I have to display?
	 *
	 * @var  int
	 */
	public $countWarnings = 0;

	/**
	 * Cache the user permissions
	 *
	 * @var   array
	 *
	 * @since 5.3.0
	 */
	public $permissions = [];

	/**
	 * Timestamp when the Core user last dismissed the upsell to Pro
	 *
	 * @var   int
	 * @since 7.0.0
	 */
	public $lastUpsellDismiss = 0;

	/**
	 * Is the output directory under the site's root?
	 *
	 * @var   bool
	 * @since 7.0.3
	 */
	public $isOutputDirectoryUnderSiteRoot = false;

	/**
	 * Does the output directory have the expected security files?
	 *
	 * @var   bool
	 * @since 7.0.3
	 */
	public $hasOutputDirectorySecurityFiles = false;

	/**
	 * Can I upgrade from Akeeba Backup 7 or 8?
	 *
	 * @var   bool
	 * @since 9.0.0
	 */
	public $canUpgradeFromAkeebaBackup8 = false;

	/** @var int Update site ID */
	public $updateSiteId = 0;

	/** @var int|null Extension ID for the obsolete Akeeba Backup 8 package */
	public $akeebaBackup8PackageId = 0;

	public function display($tpl = null)
	{
		$this->addToolbar();

		/** @var ControlpanelModel $model */
		$model = $this->getModel();

		$statusHelper      = Status::getInstance();

		try
		{
			/** @var UsagestatsModel $usageStatsModel */
			$this
				->getModel('Usagestats')
				->collectStatistics();
		}
		catch (Throwable $e)
		{
			// This is allowed to fail gracefully.
		}

		$this->getProfileList();
		$this->getProfileIdAndName();

		/** @var CMSApplication $app */
		$app = \Joomla\CMS\Factory::getApplication();
		/** @var Session $session */
		$session = $app->getSession();
		$params  = ComponentHelper::getParams('com_akeebabackup');

		$this->quickIconProfiles               = $model->getQuickIconProfiles();
		$this->statusCell                      = $statusHelper->getStatusCell();
		$this->detailsCell                     = $statusHelper->getQuirksCell();
		$this->latestBackupCell                = $statusHelper->getLatestBackupDetails();
		$this->areMediaPermissionsFixed        = $model->fixMediaPermissions();
		$this->checkMbstring                   = $model->checkMbstring();
		$this->needsDownloadID                 = $model->needsDownloadID() ? 1 : 0;
		$this->coreWarningForDownloadID        = $model->mustWarnAboutDownloadIDInCore();
		$this->extension_id                    = (int) $model->getState('extension_id', 0);
		$this->frontEndSecretWordIssue         = $model->getFrontendSecretWordError();
		$this->newSecretWord                   = $session->get('akeebabackup.cpanel.newSecretWord', null);
		$this->desktopNotifications            = $params->get('desktop_notifications', '0') ? 1 : 0;
		$this->formattedChangelog              = $this->formatChangelog();
		$this->promptForConfigurationwizard    = Factory::getConfiguration()->get('akeeba.flag.confwiz', 0) == 0;
		$this->countWarnings                   = count(Factory::getConfigurationChecks()->getDetailedStatus());
		$user                                  = $app->getIdentity() ?? (new User());
		$this->permissions                     = [
			'configure'   => $user->authorise('akeebabackup.configure', 'com_akeebabackup'),
			'backup'      => $user->authorise('akeebabackup.backup', 'com_akeebabackup'),
			'download'    => $user->authorise('akeebabackup.download', 'com_akeebabackup'),
			// Matches the privilege required to edit the component's Options page; gates the Schedule view.
			'editoptions' => $user->authorise('core.admin', 'com_akeebabackup'),
		];
		$this->isOutputDirectoryUnderSiteRoot  = $model->isOutputDirectoryUnderSiteRoot();
		$this->hasOutputDirectorySecurityFiles = $model->hasOutputDirectorySecurityFiles();

		$this->lastUpsellDismiss = $params->get('lastUpsellDismiss', 0);

		/** @var UpgradeModel $upgradeModel */
		$upgradeModel = $this->getModel('Upgrade');
		$upgradeModel->init();
		$this->canUpgradeFromAkeebaBackup8 = version_compare(JVERSION, '4.4.999999', 'lt') && in_array(
			true, $upgradeModel->runCustomHandlerEvent('onNeedsMigration'), true
		);
		$this->akeebaBackup8PackageId      = $this->getModel()->getAkeebaBackup8PackageId();

		/** @var UpdatesModel $updatesModel */
		$updatesModel       = $this->getModel('Updates');
		$this->updateSiteId = $updatesModel->getUpdateSiteIds()[0];

		// Load the version constants
		Platform::getInstance()->load_version_defines();

		// Add the Javascript to the document
		$wa = $app->getDocument()->getWebAssetManager();
		$wa->useScript('com_akeebabackup.controlpanel');

		$this->addJSScriptOptions();

		parent::display($tpl);
	}

	protected function addToolbar(): void
	{
		ToolbarHelper::title(Text::_('COM_AKEEBABACKUP_' . (AKEEBABACKUP_PRO ? 'PRO' : 'CORE')), 'icon-akeeba');

		$toolbar = $this->getToolbarCompat();
		$toolbar->preferences('com_akeebabackup');
		$toolbar->help(null, false, 'https://www.akeeba.com/documentation/akeeba-backup-joomla/control-panel.html');
	}

	/**
	 * Adds inline Javascript to the document
	 */
	protected function addJSScriptOptions()
	{
		$this->getDocument()->addScriptOptions(
			'akeeba.System.notification.hasDesktopNotification', (bool) $this->desktopNotifications
		);
		$this->getDocument()->addScriptOptions('akeeba.ControlPanel.needsDownloadID', (bool) $this->needsDownloadID);
		$this->getDocument()->addScriptOptions(
			'akeeba.ControlPanel.outputDirUnderSiteRoot', (bool) $this->isOutputDirectoryUnderSiteRoot
		);
		$this->getDocument()->addScriptOptions(
			'akeeba.ControlPanel.hasSecurityFiles', (bool) $this->hasOutputDirectorySecurityFiles
		);
	}

	protected function formatChangelog($onlyLast = false)
	{
		$ret   = '';
		$file  = __DIR__ . '/../../../CHANGELOG.php';
		$lines = @file($file);

		if (empty($lines))
		{
			return $ret;
		}

		array_shift($lines);

		foreach ($lines as $line)
		{
			$line = trim($line);

			if (empty($line))
			{
				continue;
			}

			$type = substr($line, 0, 1);

			switch ($type)
			{
				case '=':
					continue 2;
					break;

				case '+':
					$ret .= "\t" . '<li><span class="badge bg-success">Added</span> ' . htmlentities(
							trim(substr($line, 2))
						) . "</li>\n";
					break;

				case '-':
					$ret .= "\t" . '<li><span class="badge bg-dark">Removed</span> ' . htmlentities(
							trim(substr($line, 2))
						) . "</li>\n";
					break;

				case '~':
				case '^':
					$ret .= "\t" . '<li><span class="badge bg-info">Changed</span> ' . htmlentities(
							trim(substr($line, 2))
						) . "</li>\n";
					break;

				case '*':
					$ret .= "\t" . '<li><span class="badge bg-danger">Security</span> ' . htmlentities(
							trim(substr($line, 2))
						) . "</li>\n";
					break;

				case '!':
					$ret .= "\t" . '<li><span class="badge bg-warning">Important</span> ' . htmlentities(
							trim(substr($line, 2))
						) . "</li>\n";
					break;

				case '#':
					$ret .= "\t" . '<li><span class="badge bg-primary">Fixed</span> ' . htmlentities(
							trim(substr($line, 2))
						) . "</li>\n";
					break;

				default:
					if (!empty($ret))
					{
						$ret .= "</ul>";
						if ($onlyLast)
						{
							return $ret;
						}
					}

					if (!$onlyLast)
					{
						$ret .= "<h4>$line</h4>\n";
					}
					$ret .= "<ul class=\"akeeba-changelog\">\n";

					break;
			}
		}

		return $ret;
	}
}