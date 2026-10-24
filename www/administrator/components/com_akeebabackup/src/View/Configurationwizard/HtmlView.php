<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\View\Configurationwizard;

defined('_JEXEC') || die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

#[\AllowDynamicProperties]
class HtmlView extends BaseHtmlView
{
	public function display($tpl = null)
	{
		// Set up the toolbar
		ToolbarHelper::title(Text::_('COM_AKEEBABACKUP_CONFWIZ'), 'icon-akeeba');

		// Push translations
		// -- Wizard
		Text::script('COM_AKEEBABACKUP_CONFWIZ_UI_MINEXECTRY');
		Text::script('COM_AKEEBABACKUP_CONFWIZ_UI_CANTSAVEMINEXEC');
		Text::script('COM_AKEEBABACKUP_CONFWIZ_UI_SAVEMINEXEC');
		Text::script('COM_AKEEBABACKUP_CONFWIZ_UI_CANTDETERMINEMINEXEC');
		Text::script('COM_AKEEBABACKUP_CONFWIZ_UI_CANTFIXDIRECTORIES');
		Text::script('COM_AKEEBABACKUP_CONFWIZ_UI_CANTDBOPT');
		Text::script('COM_AKEEBABACKUP_CONFWIZ_UI_EXECTOOLOW');
		Text::script('COM_AKEEBABACKUP_CONFWIZ_UI_SAVINGMAXEXEC');
		Text::script('COM_AKEEBABACKUP_CONFWIZ_UI_CANTSAVEMAXEXEC');
		Text::script('COM_AKEEBABACKUP_CONFWIZ_UI_CANTDETERMINEPARTSIZE');
		Text::script('COM_AKEEBABACKUP_CONFWIZ_UI_PARTSIZE');

		// -- Backup
		Text::script('COM_AKEEBABACKUP_BACKUP_TEXT_LASTRESPONSE', true);

		// Load the Configuration Wizard Javascript file and its dependencies
		$this->getDocument()->getWebAssetManager()->useScript('com_akeebabackup.configuration_wizard');

		$this->getDocument()->addScriptOptions('akeebabackup.System.params.AjaxURL', 'index.php?option=com_akeebabackup&view=Configurationwizard&task=ajax');
		
		parent::display($tpl);
	}

}