<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\View\Filefilters;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewLoadAnyTemplateTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewProfileIdAndNameTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewTaskBasedEventsTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewToolbarTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Model\FilefiltersModel;
use Akeeba\Engine\Factory;
use Joomla\CMS\HTML\HTMLHelper;
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
	 * SELECT element for choosing a database root
	 *
	 * @var  string
	 */
	public $root_select = '';

	/**
	 * List of database roots
	 *
	 * @var  array
	 */
	public $roots = [];

	/**
	 * @return  void
	 */
	public function onBeforeMain()
	{
		$this->getDocument()->getWebAssetManager()
			->useScript('com_akeebabackup.filefilters');

		$this->addToolbar();

		HTMLHelper::_('bootstrap.tooltip', '.hasTooltip', [
			'placement' => 'right'
		]);

		/** @var FilefiltersModel $model */
		$model = $this->getModel();

		// Get a JSON representation of the available roots
		$filters   = Factory::getFilters();
		$root_info = $filters->getInclusions('dir');
		$roots     = [];
		$options   = [];

		if (!empty($root_info))
		{
			// Loop all dir definitions
			foreach ($root_info as $dir_definition)
			{
				if (is_null($dir_definition[1]))
				{
					// Site root definition has a null element 1. It is always pushed on top of the stack.
					array_unshift($roots, $dir_definition[0]);
				}
				else
				{
					$roots[] = $dir_definition[0];
				}

				$options[] = HTMLHelper::_('select.option', $dir_definition[0], $dir_definition[0]);
			}
		}

		$siteRoot      = $roots[0];
		$selectOptions = [
			'list.select' => $siteRoot,
			'id'          => 'active_root',
			'list.attr'   => [
				'class' => 'form-control',

			],
		];

		$this->root_select = HTMLHelper::_('select.genericlist', $options, 'root', $selectOptions);
		$this->roots       = $roots;

		// Add script options
		$this->getDocument()
			->addScriptOptions('akeebabackup.System.params.AjaxURL', Route::_('index.php?option=com_akeebabackup&view=Filefilters&task=ajax', false))
			->addScriptOptions('akeebabackup.Fsfilters.loadingGif', Uri::root() . 'media/com_akeebabackup/icons/loading.gif');

		switch (strtolower($this->getLayout()))
		{
			case 'default':
			default:
				// Get a JSON representation of the directory data
			$this->getDocument()
					->addScriptOptions('akeebabackup.Filefilters.guiData', $model->makeListing($siteRoot, [], ''))
					->addScriptOptions('akeebabackup.Filefilters.viewType', "list");

				break;

			case 'tabular':
				$this->setLayout('tabular');

				// Get a JSON representation of the tabular filter data
				$this->getDocument()
					->addScriptOptions('akeebabackup.Filefilters.guiData', [
						'list' => $model->getFilters($siteRoot)
					])
					->addScriptOptions('akeebabackup.Filefilters.viewType', "tabular");

				break;
		}

		// Push translations
		Text::script('COM_AKEEBABACKUP_FILEFILTERS_LABEL_UIROOT');
		Text::script('COM_AKEEBABACKUP_FILEFILTERS_LABEL_UIERRORFILTER');
		Text::script('COM_AKEEBABACKUP_FILEFILTERS_TYPE_DIRECTORIES');
		Text::script('COM_AKEEBABACKUP_FILEFILTERS_TYPE_SKIPFILES');
		Text::script('COM_AKEEBABACKUP_FILEFILTERS_TYPE_SKIPDIRS');
		Text::script('COM_AKEEBABACKUP_FILEFILTERS_TYPE_FILES');
		Text::script('COM_AKEEBABACKUP_FILEFILTERS_TYPE_DIRECTORIES_ALL');
		Text::script('COM_AKEEBABACKUP_FILEFILTERS_TYPE_SKIPFILES_ALL');
		Text::script('COM_AKEEBABACKUP_FILEFILTERS_TYPE_SKIPDIRS_ALL');
		Text::script('COM_AKEEBABACKUP_FILEFILTERS_TYPE_FILES_ALL');
		Text::script('COM_AKEEBABACKUP_FILEFILTERS_TYPE_APPLYTOALLDIRS');
		Text::script('COM_AKEEBABACKUP_FILEFILTERS_TYPE_APPLYTOALLFILES');

		$this->getProfileIdAndName();
	}

	private function addToolbar(): void
	{
		$toolbar = $this->getToolbarCompat();
		ToolbarHelper::title(Text::_('COM_AKEEBABACKUP_FILEFILTERS'), 'icon-akeeba');

		$toolbar->back()
			->text('COM_AKEEBABACKUP_CONTROLPANEL')
			->icon('fa fa-' . (\Joomla\CMS\Factory::getApplication()->getLanguage()->isRtl() ? 'arrow-right' : 'arrow-left'))
			->url('index.php?option=com_akeebabackup');

		$toolbar->linkButton('normal')
			->icon('fa fa-columns')
			->text('COM_AKEEBABACKUP_FILEFILTERS_LABEL_NORMALVIEW')
			->url(Route::_('index.php?option=com_akeebabackup&view=Filefilters&layout=default'));

		$toolbar->linkButton('tabular')
			->icon('fa fa-list-ul')
			->text('COM_AKEEBABACKUP_FILEFILTERS_LABEL_TABULARVIEW')
			->url(Route::_('index.php?option=com_akeebabackup&view=Filefilters&layout=tabular'));


		$toolbar->help(null, false, 'https://www.akeeba.com/documentation/akeeba-backup-joomla/exclude-data-from-backup.html#files-and-directories-exclusion');
	}

}