<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') || die();

use Akeeba\Component\AkeebaBackup\Administrator\Helper\Utils;
use Akeeba\Engine\Factory;
use Joomla\CMS\Language\Text;

/** @var  \Akeeba\Component\AkeebaBackup\Administrator\View\Manage\HtmlView $this */
/** @var  array $record */

if (!isset($record['remote_filename']))
{
	$record['remote_filename'] = '';
}

$archiveExists    = $record['meta'] == 'ok';
$showManageRemote = $record['hasRemoteFiles'] && (AKEEBABACKUP_PRO == 1);
$engineForProfile = array_key_exists($record['profile_id'], $this->enginesPerProfile) ? $this->enginesPerProfile[$record['profile_id']] : 'none';
$showUploadRemote = $this->permissions['backup'] && $archiveExists && !$showManageRemote && ($engineForProfile != 'none') && ($record['meta'] != 'obsolete') && (AKEEBABACKUP_PRO == 1);
$showDownload     = $this->permissions['download'] && $archiveExists;
$showViewLog      = $this->permissions['backup'] && isset($record['backupid']) && !empty($record['backupid']);
$postProcEngine   = '';
$thisPart         = '';
$thisID           = urlencode($record['id']);

if ($showUploadRemote)
{
	$postProcEngine   = $engineForProfile ?: 'none';
	$showUploadRemote = !empty($postProcEngine);
}

$relativePath = Utils::getRelativePath(JPATH_SITE, dirname($record['absolute_path']));

if (substr($relativePath, 0, 2) === './')
{
	$relativePath = substr($relativePath, 2);
}

?>
<div class="modal fade"
	 id="akeeba-buadmin-<?= (int)$record['id'] ?>"
	 tabindex="-1"
	 role="dialog"
	 aria-labelledby="akeeba-buadmin-<?= (int)$record['id'] ?>-title"
	 aria-hidden="true"
>
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
			<div class="modal-header">
            	<h3 class="modal-title" id="akeeba-buadmin-<?= (int)$record['id'] ?>-title">
					<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LBL_BACKUPINFO') ?>
				</h3>
				<button type="button" class="btn-close novalidate" data-bs-dismiss="modal"
						aria-label="<?= Text::_('JLIB_HTML_BEHAVIOR_CLOSE') ?>"></button>
			</div>
			<div class="modal-body p-3">
				<div class="row mb-3">
					<div class="col-md-5 fw-bold">
						<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LBL_ARCHIVEEXISTS') ?>
					</div>
					<div class="col-md-7 fw-bold">
						<?php if($record['meta'] == 'ok'): ?>
							<span class="text-success">
								<?= Text::_('JYES') ?>
							</span>
						<?php else : ?>
							<span class="text-danger">
								<?= Text::_('JNO') ?>
							</span>
						<?php endif ?>
					</div>
				</div>
				<div class="row mb-3">
					<div class="col-md-5 fw-bold">
						<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LBL_ARCHIVEPATH' . ($archiveExists ? '' : '_PAST'))?>
					</div>
					<div class="col-md-7 text-break">
						<?= $this->escape($relativePath) ?>
					</div>
				</div>
				<div class="row mb-3">
					<div class="col-md-5 fw-bold">
						<p>
							<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LBL_ARCHIVENAME' . ($archiveExists ? '' : '_PAST'))?>
						</p>
						<p class="alert alert-info">
							<span class="fa fa-info-circle"></span>
							<?= Text::plural('COM_AKEEBABACKUP_BUADMIN_LBL_ARCHIVEPARTS', max($record['multipart'], 1)) ?>
						</p>
					</div>
					<div class="col-md-7">
						<?php if ($record['multipart'] < 2): ?>
						<code>
							<?= $this->escape($record['archivename']) ?>
						</code>
						<?php else: ?>
						<ul>
							<?php foreach(Factory::getStatistics()->get_all_filenames($record, false) as $file): ?>
							<li>
								<code><?= basename($file) ?></code>
							</li>
							<?php endforeach ?>
						</ul>
						<?php endif ?>
					</div>
				</div>
			</div>
        </div>
    </div>
</div>

<?php if($showDownload): ?>
	<div id="akeeba-buadmin-download-<?= (int)$record['id'] ?>"
		 class="modal fade"
		 tabindex="-1"
		 role="dialog"
		 aria-labelledby="akeeba-buadmin-download-<?= (int)$record['id'] ?>-title"
		 aria-hidden="true"
	>
		<div class="modal-dialog modal-lg modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h3 class="modal-title" id="akeeba-buadmin-download-<?= (int)$record['id'] ?>">
						<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LBL_DOWNLOAD_TITLE')?>
					</h3>
					<button type="button" class="btn-close novalidate" data-bs-dismiss="modal"
							aria-label="<?= Text::_('JLIB_HTML_BEHAVIOR_CLOSE') ?>"></button>
				</div>
				<div class="modal-body p-3">
					<div class="alert alert-warning">
						<p>
							<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LBL_DOWNLOAD_WARNING')?>
						</p>
					</div>

					<?php if($record['multipart'] < 2): ?>
						<button type="button"
								class="btn btn-primary btn-sm comAkeebaManageDownloadButton"
								data-id="<?= (int) $record['id'] ?>">
							<span class="fa fa-file-download"></span>
							<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LOG_DOWNLOAD') ?>
						</button>
					<?php else: ?>
						<div>
							<?= Text::plural('COM_AKEEBABACKUP_BUADMIN_LBL_DOWNLOAD_PARTS', (int)$record['multipart']) ?>
						</div>
						<div class="d-flex flex-row flex-wrap justify-content-start align-items-start">
							<?php for($count = 0; $count < $record['multipart']; $count++): ?>
								<button type="button"
										class="btn btn-secondary btn-sm text-decoration-none me-2 mb-2 comAkeebaManageDownloadButton"
										data-id="<?= (int) $record['id'] ?>"
										data-part="<?= (int) $count ?>">
									<span class="fa fa-file-download"></span>
									<?= Text::sprintf('COM_AKEEBABACKUP_BUADMIN_LABEL_PART', $count) ?>
								</button>
							<?php endfor ?>
						</div>
					<?php endif ?>
				</div>
			</div>
		</div>
	</div>
<?php endif ?>

<?php if($showManageRemote || $showUploadRemote): ?>
<div class="mb-3">
	<?php if($showManageRemote): ?>
		<div style="padding-bottom: 3pt;">
			<button type="button"
					class="btn btn-primary akeeba_remote_management_link"
					data-management="index.php?option=com_akeebabackup&view=Remotefiles&tmpl=component&task=listactions&id=<?= (int) $record['id'] ?>"
					data-reload="index.php?option=com_akeebabackup&view=Manage"
			>
				<span class="fa fa-cloud"></span>
				<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_REMOTEFILEMGMT') ?>
			</button>
		</div>
	<?php elseif($showUploadRemote): ?>
		<button type="button"
				class="btn btn-primary akeeba_upload"
				data-upload="index.php?option=com_akeebabackup&view=Upload&tmpl=component&task=start&id=<?= (int) $record['id'] ?>&<?= \Joomla\CMS\Factory::getApplication()->getFormToken() ?>=1"
				data-reload="index.php?option=com_akeebabackup&view=Manage"
				title="<?= Text::sprintf('COM_AKEEBABACKUP_TRANSFER_DESC', Text::_("ENGINE_POSTPROC_{$postProcEngine}_TITLE")) ?>">
			<span class="fa fa-cloud-upload-alt"></span>
			<?= Text::_('COM_AKEEBABACKUP_TRANSFER_TITLE') ?>
			(<span class="fst-italic"><?= $this->escape($postProcEngine) ?></span>)
		</button>
	<?php endif ?>
</div>
<?php endif ?>

<div>
	<?php if($showDownload): ?>
		<button type="button"
				class="btn btn-<?= ($showManageRemote || $showUploadRemote) ? 'secondary' : 'success' ?> me-2 mb-2"
				data-bs-toggle="modal"
				data-bs-target="#akeeba-buadmin-download-<?= (int)$record['id'] ?>"
		>
			<span class="fa fa-file-download"></span>
			<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LOG_DOWNLOAD') ?>
		</button>
	<?php endif ?>

	<?php if($showViewLog): ?>
        <?php if ($record['log_present']): ?>
            <a class="akeebabackup-viewlog-button btn btn-outline-dark btn-small text-decoration-none me-2 mb-2 akeebaCommentPopover"
               <?= ($record['meta'] != 'obsolete') ? '' : 'disabled="disabled"' ?>
               href="index.php?option=com_akeebabackup&view=Log&tag=<?= $this->escape($record['tag']) ?>.<?= $this->escape($record['backupid']) ?>&profileid=<?= (int)$record['profile_id'] ?>"
               title="<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LBL_LOGFILEID') ?>"
               data-bs-content="<?= $this->escape($record['backupid']) ?>">
                <span class="fa fa-search"></span>
                <?= Text::_('COM_AKEEBABACKUP_LOG') ?>
            </a>
        <?php else: ?>
            <button type="button"
                    class="btn btn-outline-dark btn-small me-2 mb-2 akeebaTooltip"
                    disabled
                    title="<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LOG_NOT_AVAILABLE') ?>">
                <span class="fa fa-search"></span>
                <?= Text::_('COM_AKEEBABACKUP_LOG') ?>
            </button>
        <?php endif ?>
    <?php endif ?>

	<button type="button"
			class="btn btn-info btn-sm akeebaTooltip"
			data-bs-toggle="modal"
			data-bs-target="#akeeba-buadmin-<?= (int) $record['id'] ?>"
			title="<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LBL_BACKUPINFO') ?>"
	>
		<span class="fa fa-info-circle"></span>
	</button>
</div>
