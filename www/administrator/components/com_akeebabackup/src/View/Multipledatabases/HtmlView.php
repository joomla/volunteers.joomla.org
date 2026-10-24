<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\View\Multipledatabases;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewLoadAnyTemplateTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewProfileIdAndNameTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewTaskBasedEventsTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewToolbarTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Model\MultipledatabasesModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;

#[\AllowDynamicProperties]
class HtmlView extends BaseHtmlView
{
	use ViewProfileIdAndNameTrait;
	use ViewLoadAnyTemplateTrait;
	use ViewTaskBasedEventsTrait;
	use ViewToolbarTrait;

	/**
	 * Main page
	 */
	public function onBeforeMain()
	{
		$this->addToolbar();

		$this->getDocument()->getWebAssetManager()
			->useScript('com_akeebabackup.multipledatabases');

		/** @var MultipledatabasesModel $model */
		$model = $this->getModel();

		$this->getProfileIdAndName();

		// Push translations
		Text::script('COM_AKEEBABACKUP_MULTIDB_GUI_LBL_LOADING');
		Text::script('COM_AKEEBABACKUP_MULTIDB_GUI_LBL_CONNECTOK');
		Text::script('COM_AKEEBABACKUP_MULTIDB_GUI_LBL_CONNECTFAIL');
		Text::script('COM_AKEEBABACKUP_MULTIDB_GUI_LBL_SAVEFAIL');
		Text::script('COM_AKEEBABACKUP_MULTIDB_GUI_LBL_LOADING');

		$this->getDocument()
			->addScriptOptions('akeebabackup.System.params.AjaxURL', Route::_('index.php?option=com_akeebabackup&view=Multipledatabases&task=ajax', false, Route::TLS_IGNORE, true))
			->addScriptOptions('akeebabackup.Multidb.loadingGif', Uri::root() . 'media/com_akeebabackup/icons/loading.gif')
			->addScriptOptions('akeebabackup.Multidb.guiData', $model->get_databases());
	}

	private function addToolbar()
	{
		$toolbar = $this->getToolbarCompat();
		ToolbarHelper::title(Text::_('COM_AKEEBABACKUP_MULTIDB'), 'icon-akeeba');

		$toolbar->back()
			->text('COM_AKEEBABACKUP_CONTROLPANEL')
			->icon('fa fa-' . (\Joomla\CMS\Factory::getApplication()->getLanguage()->isRtl() ? 'arrow-right' : 'arrow-left'))
			->url('index.php?option=com_akeebabackup');

		$toolbar->help(null, false, 'https://www.akeeba.com/documentation/akeeba-backup-joomla/include-data-to-archive.html#multiple-db-definitions');
	}
}