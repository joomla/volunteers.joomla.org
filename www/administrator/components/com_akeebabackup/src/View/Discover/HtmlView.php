<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\View\Discover;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewLoadAnyTemplateTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewTaskBasedEventsTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewToolbarTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Model\DiscoverModel;
use Akeeba\Engine\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

#[\AllowDynamicProperties]
class HtmlView extends BaseHtmlView
{
	use ViewLoadAnyTemplateTrait;
	use ViewTaskBasedEventsTrait;
	use ViewToolbarTrait;

	/**
	 * The directory we are currently listing
	 *
	 * @var  string
	 */
	public $directory;

	/**
	 * The list of importable archive files in the current directory
	 *
	 * @var  array
	 */
	public $files;

	public function onBeforeMain()
	{
		$this->getDocument()->getWebAssetManager()
			->useScript('com_akeebabackup.discover');

		$this->addToolbar();

		/** @var DiscoverModel $model */
		$model = $this->getModel();

		$this->directory = $model->getState('directory', '');

		if (empty($this->directory))
		{
			$this->directory = Factory::getConfiguration()->get('akeeba.basic.output_directory', '[DEFAULT_OUTPUT]');
		}

		// Push translations
		Text::script('COM_AKEEBABACKUP_CONFIG_UI_BROWSE');
		Text::script('COM_AKEEBABACKUP_FILEFILTERS_LABEL_UIROOT');

		$this->getDocument()
			->addScriptOptions('akeebabackup.Configuration.URLs', [
				'browser' => Route::_('index.php?option=com_akeebabackup&view=Browser&processfolder=0&tmpl=component&folder=', false, Route::TLS_IGNORE, true),
			]);
	}

	public function onBeforeDiscover()
	{
		/** @var DiscoverModel $model */
		$model = $this->getModel();

		$this->addToolbar();

		$filter          = InputFilter::getInstance();
		$this->directory = $model->getState('directory', '');
		$this->files     = $model->getFiles();

		$this->setLayout('discover');
	}

	private function addToolbar()
	{
		$toolbar = $this->getToolbarCompat();
		ToolbarHelper::title(Text::_('COM_AKEEBABACKUP_DISCOVER'), 'icon-akeeba');

		$toolbar->back()
			->text('COM_AKEEBABACKUP_CONTROLPANEL')
			->icon('fa fa-' . (\Joomla\CMS\Factory::getApplication()->getLanguage()->isRtl() ? 'arrow-right' : 'arrow-left'))
			->url('index.php?option=com_akeebabackup');

		$toolbar->help(null, false, 'https://www.akeeba.com/documentation/akeeba-backup-joomla/discover-import-archives.html');
	}

}