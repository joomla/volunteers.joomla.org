<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Akeeba\Component\AkeebaBackup\Administrator\View\Upgrade\HtmlView $this */

$btnWarning = !$this->needsMigration || !$this->hasCompatibleVersion;
?>

<div class="card">
	<h3 class="card-header">
		<?= Text::_('COM_AKEEBABACKUP_UPGRADE') ?>
	</h3>
	<div class="card-body">
		<?php if ($this->needsMigration && $this->hasCompatibleVersion): ?>
			<div class="alert alert-warning">
				<span class="fa fa-exclamation-triangle"></span>
				<?= Text::_('COM_AKEEBABACKUP_UPGRADE_LBL_WARNING_STANDARD') ?>
			</div>
		<?php elseif (!$this->hasCompatibleVersion): ?>
			<div class="alert alert-danger">
				<p class="text-danger text-center fs-1">
					<span class="fa fa-exclamation-circle"></span>
					<strong>
						<?= Text::_('COM_AKEEBABACKUP_UPGRADE_LBL_WARNING_INCOMPATIBLE_HEAD') ?>
					</strong>
					<span class="fa fa-exclamation-circle"></span>
				</p>
				<p>
					<?= Text::_('COM_AKEEBABACKUP_UPGRADE_LBL_WARNING_INCOMPATIBLE_BODY' )?>
				</p>
			</div>
		<?php else: ?>
			<div class="alert alert-danger">
				<p class="text-danger text-center fs-1">
					<span class="fa fa-exclamation-circle"></span>
					<strong>
						<?= Text::_('COM_AKEEBABACKUP_UPGRADE_LBL_WARNING_ALREADY_RAN_HEAD' )?>
					</strong>
					<span class="fa fa-exclamation-circle"></span>
				</p>
				<p>
					<?= Text::_('COM_AKEEBABACKUP_UPGRADE_LBL_WARNING_ALREADY_RAN_BODY' )?>
				</p>
			</div>
		<?php endif; ?>


		<div class="my-2 p-3">
			<p>
				<?= Text::_('COM_AKEEBABACKUP_UPGRADE_LBL_WHAT_IT_DOES' )?>
			</p>
			<p>
				<?= Text::_('COM_AKEEBABACKUP_UPGRADE_LBL_REMEMBER_TO_UNINSTALL' )?>
			</p>
		</div>

		<p>
			<a class="btn btn-<?= $btnWarning ? 'outline-warning' : 'primary' ?> btn-lg me-4"
			   href="<?= Route::_('index.php?option=com_akeebabackup&task=Upgrade.migrate&' . Factory::getApplication()->getSession()->getFormToken() . '=1') ?>"
			>
				<span class="fa fa-file-import"></span>
				<?= Text::_('COM_AKEEBABACKUP_UPGRADE_BTN_PROCEED') ?>
			</a>
			<a class="btn btn-dark"
			   href="<?= Route::_('index.php?option=com_akeebabackup') ?>"
			>
				<span class="fa fa-<?= Factory::getApplication()->getLanguage()->isRtl() ? 'arrow-right' : 'arrow-left' ?>"></span>
				<?= Text::_('COM_AKEEBABACKUP_CONTROLPANEL') ?>
			</a>
		</p>
	</div>
</div>