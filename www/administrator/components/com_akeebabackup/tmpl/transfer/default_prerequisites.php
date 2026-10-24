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
<div class="card mb-3">
	<h3 class="card-header <?= empty($this->latestBackup) ? 'bg-danger' : 'bg-success' ?> text-white">
		<?= Text::_('COM_AKEEBABACKUP_TRANSFER_HEAD_PREREQUISITES') ?>
	</h3>

	<div class="card-body">
		<table class="table table-striped w-100">
			<tbody>
			<tr>
				<td>
					<strong>
						<?= Text::_('COM_AKEEBABACKUP_TRANSFER_LBL_COMPLETEBACKUP') ?>
					</strong>
					<br/>
					<small>
						<?php if(empty($this->latestBackup) && $this->selectedBackupId > 0): ?>
							<?= Text::sprintf('COM_AKEEBABACKUP_TRANSFER_ERR_COMPLETEBACKUP_SPECIFIC', $this->selectedBackupId) ?>
						<?php elseif(empty($this->latestBackup)): ?>
							<?= Text::_('COM_AKEEBABACKUP_TRANSFER_ERR_COMPLETEBACKUP') ?>
						<?php elseif($this->selectedBackupId > 0): ?>
							<?= Text::sprintf('COM_AKEEBABACKUP_TRANSFER_LBL_COMPLETEBACKUP_INFO_SPECIFIC', $this->selectedBackupId, $this->lastBackupDate) ?>
						<?php else: ?>
							<?= Text::sprintf('COM_AKEEBABACKUP_TRANSFER_LBL_COMPLETEBACKUP_INFO', $this->lastBackupDate) ?>
						<?php endif ?>
					</small>
				</td>
				<td width="20%">
					<?php if(empty($this->latestBackup)): ?>
						<a href="<?= Route::_('index.php?option=com_akeebabackup&view=Backup') ?>"
						   class="btn btn-success"
						   id="akeeba-transfer-btn-backup">
							<?= Text::_('COM_AKEEBABACKUP_BACKUP_LABEL_START') ?>
						</a>
					<?php endif ?>
				</td>
			</tr>
			<?php if(!(empty($this->latestBackup))): ?>
				<tr>
					<td>
						<strong>
							<?= Text::sprintf('COM_AKEEBABACKUP_TRANSFER_LBL_SPACE', $this->spaceRequired['string']) ?>
						</strong>
						<br/>
						<small id="akeeba-transfer-err-space" style="display: none">
							<?= Text::_('COM_AKEEBABACKUP_TRANSFER_ERR_SPACE') ?>
						</small>
					</td>
					<td>
					</td>
				</tr>
			<?php endif ?>
			</tbody>
		</table>
	</div>
</div>
