<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\View\Databasefilters;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewLoadAnyTemplateTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewProfileIdAndNameTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewTaskBasedEventsTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewToolbarTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Model\DatabasefiltersModel;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

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
	 * Main page
	 */
	public function onBeforeMain()
	{
		$this->getDocument()->getWebAssetManager()
			->useScript('com_akeebabackup.databasefilters');

		$this->addToolbar();

		HTMLHelper::_('bootstrap.tooltip', '.hasTooltip', [
			'placement' => 'right',
		]);

		/** @var DatabasefiltersModel $model */
		$model = $this->getModel();

		// Get a JSON representation of the available roots
		$root_info = $model->getRoots();
		$roots     = [];
		$options   = [];

		if (!empty($root_info))
		{
			// Loop all dir definitions
			foreach ($root_info as $def)
			{
				$roots[]   = $def->value;
				$options[] = HTMLHelper::_('select.option', $def->value, $def->text);
			}
		}

		$siteRoot          = '[SITEDB]';
		$selectOptions     = [
			'list.select' => $siteRoot,
			'id'          => 'active_root',
			'list.attr'   => [
				'class' => 'form-select',
			],
		];
		$this->root_select = HTMLHelper::_('select.genericlist', $options, 'root', $selectOptions);
		$this->roots       = $roots;

		// Add script options
		$this->getDocument()
			->addScriptOptions('akeebabackup.System.params.AjaxURL', Route::_('index.php?option=com_akeebabackup&view=Databasefilters&task=ajax', false));

		switch (strtolower($this->getLayout()))
		{
			case 'default':
			default:
				// Get the database entities GUI data
				$this->getDocument()
					->addScriptOptions('akeebabackup.Databasefilters.guiData', $model->makeListing($siteRoot))
					->addScriptOptions('akeebabackup.Databasefilters.viewType', 'list');

				break;

			case 'tabular':
				// Get the filter data for tabular display
				$this->getDocument()
					->addScriptOptions('akeebabackup.Databasefilters.guiData', [
						'list' => $model->getFilters($siteRoot)
					])
					->addScriptOptions('akeebabackup.Databasefilters.viewType', 'tabular');

				break;
		}

		// Translations
		Text::script('COM_AKEEBABACKUP_FILEFILTERS_LABEL_UIROOT');
		Text::script('COM_AKEEBABACKUP_FILEFILTERS_LABEL_UIERRORFILTER');
		Text::script('COM_AKEEBABACKUP_DBFILTER_TYPE_TABLES');
		Text::script('COM_AKEEBABACKUP_DBFILTER_TYPE_TABLEDATA');
		Text::script('COM_AKEEBABACKUP_DBFILTER_TABLE_MISC');
		Text::script('COM_AKEEBABACKUP_DBFILTER_TABLE_TABLE');
		Text::script('COM_AKEEBABACKUP_DBFILTER_TABLE_VIEW');
		Text::script('COM_AKEEBABACKUP_DBFILTER_TABLE_PROCEDURE');
		Text::script('COM_AKEEBABACKUP_DBFILTER_TABLE_FUNCTION');
		Text::script('COM_AKEEBABACKUP_DBFILTER_TABLE_TRIGGER');
		Text::script('COM_AKEEBABACKUP_DBFILTER_TABLE_META_ROWCOUNT');

		$this->getProfileIdAndName();
	}

	private function addToolbar(): void
	{
		$toolbar = $this->getToolbarCompat();
		ToolbarHelper::title(Text::_('COM_AKEEBABACKUP_DBFILTER'), 'icon-akeeba');

		$toolbar->back()
			->text('COM_AKEEBABACKUP_CONTROLPANEL')
			->icon('fa fa-' . (\Joomla\CMS\Factory::getApplication()->getLanguage()->isRtl() ? 'arrow-right' : 'arrow-left'))
			->url('index.php?option=com_akeebabackup');

		$toolbar->linkButton('normal')
			->icon('fa fa-columns')
			->text('COM_AKEEBABACKUP_FILEFILTERS_LABEL_NORMALVIEW')
			->url(Route::_('index.php?option=com_akeebabackup&view=Databasefilters&layout=default'));

		$toolbar->linkButton('tabular')
			->icon('fa fa-list-ul')
			->text('COM_AKEEBABACKUP_FILEFILTERS_LABEL_TABULARVIEW')
			->url(Route::_('index.php?option=com_akeebabackup&view=Databasefilters&layout=tabular'));


		$toolbar->help(null, false, 'https://www.akeeba.com/documentation/akeeba-backup-joomla/exclude-data-from-backup.html#files-and-directories-exclusion');
	}

}