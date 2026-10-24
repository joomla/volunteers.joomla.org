<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\View\Schedule;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewLoadAnyTemplateTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewProfileIdAndNameTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewTaskBasedEventsTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewToolbarTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Model\ScheduleModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

#[\AllowDynamicProperties]
class HtmlView extends BaseHtmlView
{
	use ViewTaskBasedEventsTrait;
	use ViewLoadAnyTemplateTrait;
	use ViewProfileIdAndNameTrait;
	use ViewToolbarTrait;

	/**
	 * Check for failed backups information
	 *
	 * @var   object
	 * @since 9.0.0
	 */
	public $checkinfo = null;

	/**
	 * Check for failed backup uploads information
	 *
	 * @var    object
	 * @since  10.1.0
	 */
	public $uploadcheck = null;

	/**
	 * CRON information
	 *
	 * @var   object
	 * @since 9.0.0
	 */
	public $croninfo = null;

	/**
	 * Is the console plugin enabled?
	 *
	 * @var   bool
	 * @since 9.0.12
	 */
	public $isConsolePluginEnabled = false;

	/**
	 * URL to automatically enable the legacy frontend API (and set a Secret Key, if necessary)
	 *
	 * @var    string|null
	 * @since  9.5.2
	 */
	private ?string $enableLegacyFrontendURL;

	/**
	 * URL to automatically enable the JSON API (and set a Secret Key, if necessary)
	 *
	 * @var    string|null
	 * @since  9.5.2
	 */
	private ?string $enableJsonApiURL;

	/**
	 * URL to reset the secret word to something that actually works
	 *
	 * @var    string|null
	 * @since  9.5.2
	 */
	private ?string $resetSecretWordURL;

	protected function onBeforeMain()
	{
		$toolbar = $this->getToolbarCompat();
		ToolbarHelper::title(Text::_('COM_AKEEBABACKUP_SCHEDULE'), 'icon-akeeba');

		$toolbar->back()
			->text('COM_AKEEBABACKUP_CONTROLPANEL')
			->icon('fa fa-' . (Factory::getApplication()->getLanguage()->isRtl() ? 'arrow-right' : 'arrow-left'))
			->url('index.php?option=com_akeebabackup');

		$toolbar->help(
			null, false, 'https://www.akeeba.com/documentation/akeeba-backup-joomla/automating-your-backup.html'
		);

		$this->getProfileIdAndName();

		$this->isConsolePluginEnabled = PluginHelper::isEnabled('console', 'akeebabackup');

		// Get the CRON paths
		/** @var ScheduleModel $model */
		$model             = $this->getModel();
		$this->croninfo    = $model->getPaths();
		$this->checkinfo   = $model->getCheckPaths();
		$this->uploadcheck = $model->getUploadcheckPaths();

		$this->enableLegacyFrontendURL = Route::_(
			sprintf(
				'index.php?option=com_akeebabackup&task=Schedule.enableFrontend&%s=1',
				Factory::getApplication()->getFormToken()
			)
		);

		$this->enableJsonApiURL = Route::_(
			sprintf(
				'index.php?option=com_akeebabackup&task=Schedule.enableJsonApi&%s=1',
				Factory::getApplication()->getFormToken()
			)
		);

		$this->resetSecretWordURL = Route::_(
			sprintf(
				'index.php?option=com_akeebabackup&task=Schedule.resetSecretWord&%s=1',
				Factory::getApplication()->getFormToken()
			)
		);

	}
}