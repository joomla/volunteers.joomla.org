<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') || die();

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var  $this  \Akeeba\Component\AkeebaBackup\Administrator\View\Transfer\HtmlView */
?>

<div id="akeeba-transfer-upload" class="card mb-3" style="display: none;">
	<h3 class="card-header bg-primary text-white">
		<?= Text::_('COM_AKEEBABACKUP_TRANSFER_HEAD_UPLOAD') ?>
	</h3>

	<div class="card-body">
		<div class="alert alert-danger" id="akeeba-transfer-upload-error" style="display: none">
			<p id="akeeba-transfer-upload-error-body">MESSAGE</p>
			<a class="btn btn-warning"
			   href="<?= Route::_('index.php?option=com_akeebabackup&view=Transfer&force=1') ?>"
			   id="akeeba-transfer-upload-error-force"
			   style="display:none">
				<?= Text::_('COM_AKEEBABACKUP_TRANSFER_ERR_OVERRIDE') ?>
			</a>
		</div>

		<div id="akeeba-transfer-upload-area-upload" style="display: none">
			<div id="backup-steps" class="d-flex flex-column align-items-stretch">
				<div class="mt-1 mb-1 p-1 border rounded bg-warning" id="akeeba-transfer-upload-lbl-kickstart">
					<?= Text::_('COM_AKEEBABACKUP_TRANSFER_LBL_UPLOAD_KICKSTART') ?>
				</div>
				<div class="mt-1 mb-1 p-1 border rounded bg-light" id="akeeba-transfer-upload-lbl-archive">
					<?= Text::_('COM_AKEEBABACKUP_TRANSFER_LBL_UPLOAD_BACKUP') ?>
				</div>
			</div>
			<div id="backup-status" class="backup-steps-container mt-4 p-2 bg-light border-top border-3">
				<div id="backup-step" class="border-bottom border-1">
					&#9729; <span id="akeeba-transfer-upload-percent"></span>
				</div>
				<div id="backup-substep">
					&#128190; <span id="akeeba-transfer-upload-size"></span>
				</div>
			</div>
		</div>

		<div id="akeeba-transfer-upload-area-kickstart" style="display: none">
			<p>
				<a class="btn btn-success btn-lg" href="" id="akeeba-transfer-upload-btn-kickstart" target="_blank">
					<span class="fa fa-arrow-right"></span>
					<?= Text::_('COM_AKEEBABACKUP_TRANSFER_BTN_OPEN_KICKSTART') ?>
				</a>
			</p>

			<div class="alert alert-info">
				<?= Text::_('COM_AKEEBABACKUP_TRANSFER_LBL_OPEN_KICKSTART_INFO') ?>
			</div>
		</div>
	</div>
</div>
