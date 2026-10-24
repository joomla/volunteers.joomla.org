<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\View\Restore;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewBackupStartTimeTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewLoadAnyTemplateTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewTaskBasedEventsTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewToolbarTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Model\RestoreModel;
use Akeeba\Engine\Platform;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;

#[\AllowDynamicProperties]
class HtmlView extends BaseHtmlView
{
	use ViewLoadAnyTemplateTrait;
	use ViewBackupStartTimeTrait;
	use ViewTaskBasedEventsTrait;
	use ViewToolbarTrait;

	/**
	 * Backup record ID we are restoring
	 *
	 * @var int
	 */
	public $id;

	/**
	 * The backup record we are restoring
	 *
	 * @var array
	 */
	public $backupRecord;

	/**
	 * The extension of the backup archive we are restoring (jpa, zip, jps)
	 *
	 * @var string
	 */
	public $extension;

	/**
	 * Joomla FTP layer parameters
	 *
	 * @var array
	 */
	public $ftpparams;

	/**
	 * HTMLHelper options for the possible archive extraction modes
	 *
	 * @var array
	 */
	public $extractionmodes;

	protected function onBeforeMain()
	{
		$this->addToolbar();
		$this->loadCommonJavascript();

		$this->initTimeInformation();

		/** @var RestoreModel $model */
		$model = $this->getModel();

		$this->id              = (int) $model->getState('id', '');
		$this->ftpparams       = $this->getFTPParams();
		$this->extractionmodes = $this->getExtractionModes();

		$backup             = Platform::getInstance()->get_statistics($this->id);
		$this->extension    = strtolower(substr($backup['absolute_path'], -3));
		$this->backupRecord = $backup;

		$this->getDocument()->addScriptOptions('akeeba.Configuration.URLs', [
			'browser' => Route::_('index.php?option=com_akeebabackup&view=Browser&tmpl=component&processfolder=1&folder='),
			'testFtp' => Route::_('index.php?option=com_akeebabackup&view=Restore&task=ajax&ajax=testftp'),
		]);
	}

	protected function onBeforeStart()
	{
		$this->addToolbar(true);
		$this->loadCommonJavascript();

		/** @var RestoreModel $model */
		$model = $this->getModel();

		$this->setLayout('restore');

		// Pass script options
		$this->getDocument()
			->addScriptOptions('akeebabackup.Restore.password', $model->getState('password'))
			->addScriptOptions('akeebabackup.Restore.ajaxURL', Uri::base() . 'components/com_akeebabackup/restore.php')
			->addScriptOptions('akeebabackup.Restore.mainURL', Uri::base() . 'index.php')
			->addScriptOptions('akeebabackup.Restore.inMainRestoration', true);
	}

	/**
	 * Returns the available extraction modes for use by HTMLHelper
	 *
	 * @return  array
	 */
	private function getExtractionModes()
	{
		$options   = [];
		$options[] = HTMLHelper::_('select.option', 'hybrid', Text::_('COM_AKEEBABACKUP_RESTORE_LABEL_EXTRACTIONMETHOD_HYBRID'));
		$options[] = HTMLHelper::_('select.option', 'direct', Text::_('COM_AKEEBABACKUP_RESTORE_LABEL_EXTRACTIONMETHOD_DIRECT'));
		$options[] = HTMLHelper::_('select.option', 'ftp', Text::_('COM_AKEEBABACKUP_RESTORE_LABEL_EXTRACTIONMETHOD_FTP'));

		return $options;
	}

	/**
	 * Returns the FTP parameters from the Global Configuration
	 *
	 * @return  array
	 */
	private function getFTPParams()
	{
		$app = Factory::getApplication();

		return [
			'procengine' => $app->get('ftp_enable', 0) ? 'hybrid' : 'direct',
			'ftp_host'   => $app->get('ftp_host', 'localhost'),
			'ftp_port'   => $app->get('ftp_port', '21'),
			'ftp_user'   => $app->get('ftp_user', ''),
			'ftp_pass'   => $app->get('ftp_pass', ''),
			'ftp_root'   => $app->get('ftp_root', ''),
			'tempdir'    => $app->get('tmp_path', ''),
		];
	}

	private function loadCommonJavascript()
	{
		$this->getDocument()->getWebAssetManager()
			->useScript('com_akeebabackup.restore')
			->useStyle('switcher');

		// Push translations
		Text::script('COM_AKEEBABACKUP_CONFIG_UI_BROWSE');
		Text::script('COM_AKEEBABACKUP_CONFIG_UI_CONFIG');
		Text::script('COM_AKEEBABACKUP_CONFIG_UI_REFRESH');
		Text::script('COM_AKEEBABACKUP_FILEFILTERS_LABEL_UIROOT');
		Text::script('COM_AKEEBABACKUP_CONFIG_UI_FTPBROWSER_TITLE');
		Text::script('COM_AKEEBABACKUP_CONFIG_DIRECTFTP_TEST_OK');
		Text::script('COM_AKEEBABACKUP_CONFIG_DIRECTFTP_TEST_FAIL');
		Text::script('COM_AKEEBABACKUP_CONFIG_DIRECTSFTP_TEST_OK');
		Text::script('COM_AKEEBABACKUP_CONFIG_DIRECTSFTP_TEST_FAIL');
		Text::script('COM_AKEEBABACKUP_BACKUP_TEXT_LASTRESPONSE');
	}

	private function addToolbar($disableMenu = false): void
	{
		ToolbarHelper::title(Text::_('COM_AKEEBABACKUP_RESTORE'), 'icon-akeeba');

		if ($disableMenu)
		{
			Factory::getApplication()->getInput()->set('hidemainmenu', true);

			return;
		}

		$toolbar = $this->getToolbarCompat();

		$toolbar->back()
			->text('COM_AKEEBABACKUP_CONTROLPANEL')
			->icon('fa fa-' . (\Joomla\CMS\Factory::getApplication()->getLanguage()->isRtl() ? 'arrow-right' : 'arrow-left'))
			->url('index.php?option=com_akeebabackup&view=Manage');

		$toolbar->help(null, false, 'https://www.akeeba.com/documentation/akeeba-backup-joomla/adminsiter-backup-files.html#integrated-restoration');
	}

}