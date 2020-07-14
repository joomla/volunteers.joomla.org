<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Admin\View\ConfigurationWizard;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\Engine\Platform;
use FOF30\View\DataView\Html as BaseView;
use JText;

class Html extends BaseView
{
	protected function onBeforeMain()
	{
		// Push translations
		// -- Wizard
		JText::script('COM_AKEEBA_CONFWIZ_UI_MINEXECTRY');
		JText::script('COM_AKEEBA_CONFWIZ_UI_CANTSAVEMINEXEC');
		JText::script('COM_AKEEBA_CONFWIZ_UI_SAVEMINEXEC');
		JText::script('COM_AKEEBA_CONFWIZ_UI_CANTDETERMINEMINEXEC');
		JText::script('COM_AKEEBA_CONFWIZ_UI_CANTFIXDIRECTORIES');
		JText::script('COM_AKEEBA_CONFWIZ_UI_CANTDBOPT');
		JText::script('COM_AKEEBA_CONFWIZ_UI_EXECTOOLOW');
		JText::script('COM_AKEEBA_CONFWIZ_UI_SAVINGMAXEXEC');
		JText::script('COM_AKEEBA_CONFWIZ_UI_CANTSAVEMAXEXEC');
		JText::script('COM_AKEEBA_CONFWIZ_UI_CANTDETERMINEPARTSIZE');
		JText::script('COM_AKEEBA_CONFWIZ_UI_PARTSIZE');

		// -- Backup
		JText::script('COM_AKEEBA_BACKUP_TEXT_LASTRESPONSE', true);

		// Load the Configuration Wizard Javascript file
		$this->container->template->addJS('media://com_akeeba/js/Backup.min.js');
		$this->container->template->addJS('media://com_akeeba/js/ConfigurationWizard.min.js');

		$platform = $this->container->platform;
		$platform->addScriptOptions('akeeba.System.params.AjaxURL', 'index.php?option=com_akeeba&view=ConfigurationWizard&task=ajax');

		// Set the layour
		$this->setLayout('wizard');
	}
}
