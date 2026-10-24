<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\View\Transfer;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewLoadAnyTemplateTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewTaskBasedEventsTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewToolbarTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Model\TransferModel;
use DateTimeZone;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Toolbar\ToolbarHelper;

#[\AllowDynamicProperties]
class HtmlView extends BaseHtmlView
{
	use ViewTaskBasedEventsTrait;
	use ViewLoadAnyTemplateTrait;
	use ViewToolbarTrait;

	/** @var   array|null  Information for the backup the wizard will transfer */
	public $latestBackup = [];

	/** @var   int  The specific backup record ID selected for transfer, or 0 when using the latest backup */
	public $selectedBackupId = 0;

	/** @var   string  Date of the latest backup, human readable */
	public $lastBackupDate = '';

	/** @var   array  Space required on the target server */
	public $spaceRequired = [
		'size'   => 0,
		'string' => '0.00 Kb',
	];

	/** @var   string  The URL to the site we are restoring to (from the session) */
	public $newSiteUrl = '';

	/** @var   string */
	public $newSiteUrlResult = '';

	/** @var   array  Results of support and firewall status of the known file transfer methods */
	public $ftpSupport = [
		'supported'  => [
			'ftp'  => false,
			'ftps' => false,
			'sftp' => false,
		],
		'firewalled' => [
			'ftp'  => false,
			'ftps' => false,
			'sftp' => false,
		],
	];

	/** @var   array  Available transfer options, for use by JHTML */
	public $transferOptions = [];

	/** @var   array  Available chunk options, for use by JHTML */
	public $chunkOptions = [];

	/** @var   array  Available chunk size options, for use by JHTML */
	public $chunkSizeOptions = [];

	/** @var   bool  Do I have supported but firewalled methods? */
	public $hasFirewalledMethods = false;

	/** @var   string  Currently selected transfer option */
	public $transferOption = 'manual';

	/** @var   string  Currently selected chunk option */
	public $chunkMode = 'chunked';

	/** @var   string  Currently selected chunk size */
	public $chunkSize = 5242880;

	/** @var   string  FTP/SFTP host name */
	public $ftpHost = '';

	/** @var   string  FTP/SFTP port (empty for default port) */
	public $ftpPort = '';

	/** @var   string  FTP/SFTP username */
	public $ftpUsername = '';

	/** @var   string  FTP/SFTP password – or certificate password if you're using SFTP with SSL certificates */
	public $ftpPassword = '';

	/** @var   string  SFTP public key certificate path */
	public $ftpPubKey = '';

	/** @var   string  SFTP private key certificate path */
	public $ftpPrivateKey = '';

	/** @var   string  FTP/SFTP directory to the new site's root */
	public $ftpDirectory = '';

	/** @var   string  FTP passive mode (default is true) */
	public $ftpPassive = true;

	/** @var   string  FTP passive mode workaround, for FTP/FTPS over cURL (default is true) */
	public $ftpPassiveFix = true;

	/** @var   int     Forces the transfer by skipping some checks on the target site */
	public $force = 0;

	/**
	 * Translations to pass to the view
	 *
	 * @var  array
	 */
	public $translations = [];

	public function booleanSwitch(string $name, int $selected = 0, string $class = ''): string
	{
		$layoutVariables = [
			'class'   => $class,
			'id'      => $name,
			'name'    => $name,
			'value'   => $selected,
			'options' => [
				HTMLHelper::_('select.option', 0, Text::_('JNO')),
				HTMLHelper::_('select.option', 1, Text::_('JYES')),
			],
		];

		return LayoutHelper::render('joomla.form.field.radio.switcher', $layoutVariables);
	}

	protected function onBeforeMain()
	{
		$this->addToolbar();

		$this->getDocument()->getWebAssetManager()
			->useScript('com_akeebabackup.transfer');

		/** @var CMSApplication $app */
		$app     = Factory::getApplication();
		$session = $app->getSession();

		/** @var TransferModel $model */
		$model = $this->getModel();

		$this->latestBackup     = $model->getTransferBackupInformation();
		$this->selectedBackupId = (int) $session->get('akeebabackup.transfer.backupId', 0);
		$this->spaceRequired    = $model->getApproximateSpaceRequired();
		$this->newSiteUrl       = $session->get('akeebabackup.transfer.url', '');
		$this->newSiteUrlResult = $session->get('akeebabackup.transfer.url_status', '');
		$this->ftpSupport       = $session->get('akeebabackup.transfer.ftpsupport', null);
		$this->transferOption   = $session->get('akeebabackup.transfer.transferOption', null);
		$this->chunkMode        = $session->get('akeebabackup.transfer.chunkMode', 'chunked');
		$this->chunkSize        = $session->get('akeebabackup.transfer.chunkSize', 5242880);
		$this->ftpHost          = $session->get('akeebabackup.transfer.ftpHost', null);
		$this->ftpPort          = $session->get('akeebabackup.transfer.ftpPort', null);
		$this->ftpUsername      = $session->get('akeebabackup.transfer.ftpUsername', null);
		$this->ftpPassword      = $session->get('akeebabackup.transfer.ftpPassword', null);
		$this->ftpPubKey        = $session->get('akeebabackup.transfer.ftpPubKey', null);
		$this->ftpPrivateKey    = $session->get('akeebabackup.transfer.ftpPrivateKey', null);
		$this->ftpDirectory     = $session->get('akeebabackup.transfer.ftpDirectory', null);
		$this->ftpPassive       = $session->get('akeebabackup.transfer.ftpPassive', 1);
		$this->ftpPassiveFix    = $session->get('akeebabackup.transfer.ftpPassiveFix', 1);

		if (!empty($this->latestBackup))
		{
			$user           = Factory::getApplication()->getIdentity();
			$lastBackupDate = clone Factory::getDate($this->latestBackup['backupstart'], 'UTC');
			$tz             = new DateTimeZone($user->getParam('timezone', $app->get('offset')));
			$lastBackupDate->setTimezone($tz);

			$this->lastBackupDate = $lastBackupDate->format(Text::_('DATE_FORMAT_LC2'), true);

			$session->set('akeebabackup.transfer.lastBackup', $this->latestBackup);
		}

		if (empty($this->ftpSupport))
		{
			$this->ftpSupport = $model->getFTPSupport();

			$session->set('akeebabackup.transfer.ftpsupport', $this->ftpSupport);
		}

		$this->transferOptions  = $this->getTransferMethodOptions();
		$this->chunkOptions     = $this->getChunkOptions();
		$this->chunkSizeOptions = $this->getChunkSizeOptions();
		$randomPrefix  = $model->makeRandomPrefix();

		Text::script('COM_AKEEBABACKUP_FILEFILTERS_LABEL_UIROOT');
		Text::script('COM_AKEEBABACKUP_CONFIG_DIRECTFTP_TEST_FAIL');

		$this->getDocument()
			->addScriptOptions('akeebabackup.System.params.AjaxURL', Route::_(sprintf("index.php?option=com_akeebabackup&view=Transfer&format=raw&force=%d", $this->force), false, Route::TLS_IGNORE, true))
			->addScriptOptions('akeebabackup.Transfer.lastUrl', $this->newSiteUrl)
			->addScriptOptions('akeebabackup.Transfer.lastResult', $this->newSiteUrlResult)
			->addScriptOptions('akeebabackup.transfer', ['randomName' => $randomPrefix . '.php']);
	}

	/**
	 * Returns the JHTML options for a transfer methods drop-down, filtering out the unsupported and firewalled methods
	 *
	 * @return   array
	 */
	private function getTransferMethodOptions(): array
	{
		$options = [];

		foreach ($this->ftpSupport['supported'] as $method => $supported)
		{
			if (!$supported)
			{
				continue;
			}

			$methodName = Text::_('COM_AKEEBABACKUP_TRANSFER_LBL_TRANSFERMETHOD_' . $method);

			if ($this->ftpSupport['firewalled'][$method])
			{
				$methodName = '&#128274; ' . $methodName;
			}

			$options[] = HTMLHelper::_('select.option', $method, $methodName);
		}

		$options[] = HTMLHelper::_('select.option', 'manual', Text::_('COM_AKEEBABACKUP_TRANSFER_LBL_TRANSFERMETHOD_MANUALLY'));

		return $options;
	}

	/**
	 * Returns the JHTML options for a chunk methods drop-down
	 *
	 * @return   array
	 */
	private function getChunkOptions(): array
	{
		$options = [];

		$options[] = ['value' => 'chunked', 'text' => Text::_('COM_AKEEBABACKUP_TRANSFER_LBL_TRANSFERMODE_CHUNKED')];
		$options[] = ['value' => 'post', 'text' => Text::_('COM_AKEEBABACKUP_TRANSFER_LBL_TRANSFERMODE_POST')];

		return $options;
	}

	/**
	 * Returns the JHTML options for a chunk size drop-down
	 *
	 * @return   array
	 */
	private function getChunkSizeOptions(): array
	{
		$options    = [];
		$multiplier = 1048576;

		$options[] = ['value' => 0.5 * $multiplier, 'text' => '512 KB'];
		$options[] = ['value' => 1 * $multiplier, 'text' => '1 MB'];
		$options[] = ['value' => 2 * $multiplier, 'text' => '2 MB'];
		$options[] = ['value' => 5 * $multiplier, 'text' => '5 MB'];
		$options[] = ['value' => 10 * $multiplier, 'text' => '10 MB'];
		$options[] = ['value' => 20 * $multiplier, 'text' => '20 MB'];
		$options[] = ['value' => 30 * $multiplier, 'text' => '30 MB'];
		$options[] = ['value' => 50 * $multiplier, 'text' => '50 MB'];
		$options[] = ['value' => 100 * $multiplier, 'text' => '100 MB'];

		return $options;
	}

	private function addToolbar(): void
	{
		$toolbar = $this->getToolbarCompat();
		ToolbarHelper::title(Text::_('COM_AKEEBABACKUP_TRANSFER'), 'icon-akeeba');

		$toolbar->back()
			->text('COM_AKEEBABACKUP_CONTROLPANEL')
			->icon('fa fa-' . (Factory::getApplication()->getLanguage()->isRtl() ? 'arrow-right' : 'arrow-left'))
			->url('index.php?option=com_akeebabackup');

		$toolbar->link(
			'COM_AKEEBABACKUP_TRANSFER_BTN_RESET',
			Route::_('index.php?option=com_akeebabackup&view=Transfer&task=reset&' . Session::getFormToken() . '=1', false)
		)->icon('icon-refresh');

		$toolbar->help(null, false, 'https://www.akeeba.com/documentation/akeeba-backup-joomla/using-akeeba-backup-component.html#menu-transfer');
	}

}