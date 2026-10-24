<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') || die();

use Joomla\CMS\Language\Text;

/** @var \Akeeba\Component\AkeebaBackup\Administrator\View\Log\HtmlView $this */
?>

<div class="alert alert-info">
    <p>
        <?= Text::_('COM_AKEEBABACKUP_RESTORE_LABEL_DONOTCLOSE') ?>
    </p>
</div>

<div id="restoration-progress" class="card">
	<h4 class="card-header bg-primary text-white">
		<?= Text::_('COM_AKEEBABACKUP_RESTORE_LABEL_INPROGRESS') ?>
	</h4>

	<div class="card-body">
		<table class="table table-striped">
			<tr>
				<th scope="row" class="w-25">
					<?= Text::_('COM_AKEEBABACKUP_RESTORE_LABEL_BYTESREAD') ?>
				</th>
				<td>
					<span id="extbytesin"></span>
				</td>
			</tr>
			<tr>
				<th scope="row" class="w-25">
					<?= Text::_('COM_AKEEBABACKUP_RESTORE_LABEL_BYTESEXTRACTED') ?>
				</th>
				<td>
					<span id="extbytesout"></span>
				</td>
			</tr>
			<tr>
				<th scope="row" class="w-25">
					<?= Text::_('COM_AKEEBABACKUP_RESTORE_LABEL_FILESEXTRACTED') ?>
				</th>
				<td>
					<span id="extfiles"></span>
				</td>
			</tr>
		</table>

		<div id="response-timer" class="my-3 p-2 border bg-light">
			<div class="text"></div>
		</div>
	</div>
</div>

<div id="restoration-error" class="card" style="display:none">
    <div class="card header bg-danger text-white">
        <h4 class="card-title">
            <?= Text::_('COM_AKEEBABACKUP_RESTORE_LABEL_FAILED') ?>
        </h4>
	</div>
	<div id="errorframe" class="card-body">
		<p>
			<?= Text::_('COM_AKEEBABACKUP_RESTORE_LABEL_FAILED_INFO') ?>
		</p>
		<p id="backup-error-message"></p>
	</div>
</div>

<div id="restoration-extract-ok" class="card" style="display:none">
	<h4 class="card-header">
		<?= Text::_('COM_AKEEBABACKUP_RESTORE_LABEL_SUCCESS') ?>
	</h4>
	<div class="card-body">
		<div class="alert alert-success">
			<p>
				<?= Text::_('COM_AKEEBABACKUP_RESTORE_LABEL_SUCCESS_INFO2') ?>
			</p>
			<p>
				<?= Text::_('COM_AKEEBABACKUP_RESTORE_LABEL_SUCCESS_INFO2B') ?>
			</p>
		</div>

		<p class="d-flex">
			<button type="button"
					class="btn btn-primary me-3" id="restoration-runinstaller">
				<span class="fa fa-share"></span>
				<?= Text::_('COM_AKEEBABACKUP_RESTORE_LABEL_RUNINSTALLER') ?>
			</button>

			<button type="button"
					class="btn btn-success" id="restoration-finalize" style="display: none">
				<span class="fa fa-flag-checkered"></span>
				<?= Text::_('COM_AKEEBABACKUP_RESTORE_LABEL_FINALIZE') ?>
			</button>
		</p>
	</div>
</div>
