<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\View\Upgrade;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewLoadAnyTemplateTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewTaskBasedEventsTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewToolbarTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Model\UpgradeModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

#[\AllowDynamicProperties]
class HtmlView extends BaseHtmlView
{
	use ViewTaskBasedEventsTrait;
	use ViewLoadAnyTemplateTrait;
	use ViewToolbarTrait;

	public $needsMigration = false;

	public $hasCompatibleVersion = false;

	protected function onBeforeMain()
	{
		$this->addToolbar();

		/** @var UpgradeModel $model */
		$model = $this->getModel();

		$this->needsMigration = in_array(true, $model->runCustomHandlerEvent('onNeedsMigration'), true);
		$this->hasCompatibleVersion = in_array(true, $model->runCustomHandlerEvent('onHasCompatibleAkeebaVersion'), true);
	}

	private function addToolbar(): void
	{
		$toolbar = $this->getToolbarCompat();
		ToolbarHelper::title(Text::_('COM_AKEEBABACKUP_UPGRADE'), 'icon-akeeba');

		$toolbar->back()
			->text('COM_AKEEBABACKUP_CONTROLPANEL')
			->icon('fa fa-' . (Factory::getApplication()->getLanguage()->isRtl() ? 'arrow-right' : 'arrow-left'))
			->url('index.php?option=com_akeebabackup');

		$toolbar->help(null, false, 'https://www.akeeba.com/documentation/akeeba-backup-joomla/using-akeeba-backup-component.html#menu-upgrade');
	}
}