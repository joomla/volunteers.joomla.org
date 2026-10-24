<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/** @var $this \Akeeba\Component\AkeebaBackup\Administrator\View\Controlpanel\HtmlView */

// Protect from unauthorized access
defined('_JEXEC') || die();

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

// Only show in the Core version with a 10% probability
if (AKEEBABACKUP_PRO) return;

// Only show if it's at least 15 days since the last time the user dismissed the upsell
if (time() - $this->lastUpsellDismiss < 1296000) return;

?>
<div class="card my-4 border border-info">
	<h3 class="card-header bg-info text-white">
		<span class="fa fa-star"></span>
		<?= Text::_('COM_AKEEBABACKUP_CONTROLPANEL_HEAD_PROUPSELL') ?>
	</h3>

	<div class="card-body">
		<p>
			<?= Text::_('COM_AKEEBABACKUP_CONTROLPANEL_HEAD_LBL_PROUPSELL_1') ?>
		</p>

		<p>
			<?= Text::_('COM_AKEEBABACKUP_CONTROLPANEL_HEAD_LBL_PROUPSELL_2') ?>
		</p>

		<div class="mb-2 text-center">
			<a href="https://www.akeeba.com/landing/akeeba-backup.html"
			   class="btn btn-info btn-lg">
				<span class="icon-akeeba"></span>
				<?= Text::_('COM_AKEEBABACKUP_CONTROLPANEL_BTN_LEARNMORE') ?>
			</a>


			<a href="<?= Route::_('index.php?option=com_akeebabackup&view=Controlpanel&task=dismissUpsell&' . \Joomla\CMS\Factory::getApplication()->getFormToken() . '=1') ?>"
			   class="btn btn-sm btn-outline-danger m-2">
				<span class="fa fa-bell"></span>
				<?= Text::_('COM_AKEEBABACKUP_CONTROLPANEL_BTN_HIDE') ?>
			</a>
		</div>
	</div>
</div>
