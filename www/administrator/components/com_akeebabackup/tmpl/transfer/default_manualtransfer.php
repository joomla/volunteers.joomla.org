<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') || die();

use Akeeba\Component\AkeebaBackup\Administrator\Helper\Utils;
use Joomla\CMS\Language\Text;

/** @var  $this  \Akeeba\Component\AkeebaBackup\Administrator\View\Transfer\HtmlView */

$dotPos    = strrpos($this->latestBackup['archivename'], '.');
$extension = substr($this->latestBackup['archivename'], $dotPos + 1);
$bareName  = basename($this->latestBackup['archivename'], '.' . $extension);

?>
<div id="akeeba-transfer-manualtransfer" class="card mb-3" style="display: none;">
	<h3 class="card-header bg-primary text-white">
		<?= Text::_('COM_AKEEBABACKUP_TRANSFER_HEAD_MANUALTRANSFER') ?>
	</h3>

	<div class="card-body">
		<div class="alert alert-info">
			<?= Text::_('COM_AKEEBABACKUP_TRANSFER_LBL_MANUALTRANSFER_INFO') ?>
		</div>

		<p>
			<a class="btn btn-primary btn-lg"
			   href="https://www.akeeba.com/videos/1212-akeeba-backup/1618-abtc04-restore-site-new-server.html"
			   target="_blank">

				<?= Text::_('COM_AKEEBABACKUP_TRANSFER_LBL_MANUALTRANSFER_LINK') ?>
			</a>
		</p>

		<h4>
			<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LBL_BACKUPINFO') ?>
		</h4>

		<h5>
			<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LBL_ARCHIVENAME') ?>
		</h5>

		<p>
			<?php if($this->latestBackup['multipart'] < 2): ?>
				<?= $this->escape($this->latestBackup['archivename']) ?>
			<?php else: ?>
				<?= Text::sprintf('COM_AKEEBABACKUP_TRANSFER_LBL_MANUALTRANSFER_MULTIPART', $this->latestBackup['multipart']) ?>
			<?php endif ?>
		</p>

		<?php if($this->latestBackup['multipart'] >= 2): ?>
			<ul>
				<?php for($i = 1; $i < $this->latestBackup['multipart']; $i++): ?>
					<li><?= $this->escape($bareName . '.' . substr($extension, 0, 1) . sprintf('%02u', $i)) ?></li>
				<?php endfor ?>
				<li>
					<?= $this->escape($this->latestBackup['archivename']) ?>
				</li>
			</ul>
		<?php endif ?>

		<h5>
			<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LBL_ARCHIVEPATH') ?>
		</h5>
		<p>
			<?= $this->escape(Utils::getRelativePath(JPATH_SITE, dirname($this->latestBackup['absolute_path'])))?>
		</p>
	</div>
</div>
