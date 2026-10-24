<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die();

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var  $this  \Akeeba\Component\AkeebaBackup\Administrator\View\Backup\HtmlView */

// Configuration Wizard pop-up
if ($this->promptForConfigurationwizard)
{
	echo $this->loadAnyTemplate('Configuration/confwiz_modal');
}

// The Javascript of the page
echo $this->loadTemplate('script');

?>

<?php // Backup Setup ?>
<div id="backup-setup" class="card">
	<h3 class="card-header bg-primary text-white">
		<?= Text::_('COM_AKEEBABACKUP_BACKUP_HEADER_STARTNEW') ?>
	</h3>
	<div class="card-body">
		<?php if($this->hasWarnings && !$this->unwriteableOutput): ?>
			<div id="quirks" class="alert alert-<?= $this->hasErrors ? 'danger' : 'warning' ?>">
				<h3 class="alert-heading">
					<?= Text::_('COM_AKEEBABACKUP_BACKUP_LABEL_DETECTEDQUIRKS') ?>
				</h3>
				<p>
					<?= Text::_('COM_AKEEBABACKUP_BACKUP_LABEL_QUIRKSLIST') ?>
				</p>
				<?= $this->warningsCell ?>

			</div>
		<?php endif ?>

		<?php if($this->unwriteableOutput): ?>
			<div id="akeeba-fatal-outputdirectory" class="alert alert-danger">
				<h3>
					<?= Text::_('COM_AKEEBABACKUP_BACKUP_ERROR_UNWRITABLEOUTPUT_' . ($this->autoStart ? 'AUTOBACKUP' : 'NORMALBACKUP')) ?>
				</h3>
				<p>
					<?= Text::sprintf('COM_AKEEBABACKUP_BACKUP_ERROR_UNWRITABLEOUTPUT_COMMON', 'index.php?option=com_akeebabackup&view=Configuration', 'https://www.akeeba.com/warnings/q001.html') ?>
				</p>
			</div>
		<?php endif ?>

		<form action="<?= Route::_('index.php?option=com_akeebabackup&view=Backup') ?>" method="post"
			  name="flipForm" id="flipForm"
			  class="akeebabackup-profile-switch-container d-md-flex flex-md-row justify-content-md-evenly align-items-center border border-1 bg-light border-rounded rounded-2 mt-1 mb-2 p-2"
			  autocomplete="off">

			<div class="m-2">
				<label>
					<?= Text::_('COM_AKEEBABACKUP_CPANEL_PROFILE_TITLE') ?>: #<?= (int)$this->profileId ?>
				</label>
			</div>
			<div class="flex-grow-1">
				<joomla-field-fancy-select
						search-placeholder="<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_PROFILEID') ?>"
				><?=
					HTMLHelper::_('select.genericlist', $this->profileList, 'profileid', [
						'list.select' => $this->profileId,
						'id' => 'comAkeebaControlPanelProfileSwitch',
					])
					?></joomla-field-fancy-select>
			</div>

			<input type="hidden" name="returnurl" value="<?= $this->escape($this->returnURL) ?>"/>
			<input type="hidden" name="description" id="flipDescription" value=""/>
			<input type="hidden" name="comment" id="flipComment" value=""/>
			<?= HTMLHelper::_('form.token') ?>
		</form>
		<div class="alert alert-info small mt-0 mb-3">
			<span class="fa fa-fw fa-info-circle" aria-hidden="true"></span>
			<?= Text::_('COM_AKEEBABACKUP_BACKUP_LBL_EXPLAIN_PROFILES') ?>
		</div>

		<form id="dummyForm" style="display: <?= $this->unwriteableOutput ? 'none' : 'block' ?>;">

			<div class="row mb-3">
				<label for="backup-description" class="col-sm-3 col-form-label">
					<?= Text::_('COM_AKEEBABACKUP_BACKUP_LABEL_DESCRIPTION') ?>
				</label>
				<div class="col-sm-9">
					<input type="text" name="description"
						   class="form-control"
						   id="backup-description"
						   value="<?= $this->escape(empty($this->description) ? $this->defaultDescription : $this->description)?>"
						   maxlength="255" size="80"  autocomplete="off" />
					<span class="text-muted"><?= Text::_('COM_AKEEBABACKUP_BACKUP_LABEL_DESCRIPTION_HELP') ?></span>
				</div>
			</div>

			<div class="row mb-3">
				<label for="comment" class="col-sm-3 col-form-label">
					<?= Text::_('COM_AKEEBABACKUP_BACKUP_LABEL_COMMENT') ?>
				</label>
				<div class="col-sm-9">
					<textarea
							name="comment" id="comment"
							class="form-control"
							rows="5" cols="73"><?= $this->comment ?></textarea>
					<span class="text-muted"><?= Text::_('COM_AKEEBABACKUP_BACKUP_LABEL_COMMENT_HELP') ?></span>
				</div>
			</div>

			<div class="row mb-3">
				<div class="col-sm-9 offset-sm-3">
					<button type="button"
							class="btn btn-primary btn-lg" id="backup-start">
						<span class="fa fa-play"></span>
						<?= Text::_('COM_AKEEBABACKUP_BACKUP_LABEL_START') ?>
					</button>

					<a class="btn btn-outline-danger" id="backup-default" href="#">
						<span class="fa fa-redo"></span>
						<?= Text::_('COM_AKEEBABACKUP_BACKUP_LABEL_RESTORE_DEFAULT') ?>
					</a>
				</div>
			</div>
		</form>
	</div>
</div>

<?php // Warning for having set an ANGIE password ?>
<div id="angie-password-warning" class="alert alert-warning alert-dismissible fade show" style="display: none">
    <h3>
		<?= Text::_('COM_AKEEBABACKUP_BACKUP_ANGIE_PASSWORD_WARNING_HEADER') ?>
		<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= Text::_('JLIB_HTML_BEHAVIOR_CLOSE') ?>"></button>
	</h3>
    <p><?= Text::_('COM_AKEEBABACKUP_BACKUP_ANGIE_PASSWORD_WARNING_1') ?></p>
    <p><?= Text::_('COM_AKEEBABACKUP_BACKUP_ANGIE_PASSWORD_WARNING_2') ?></p>
</div>

<?php // Backup in progress ?>
<div id="backup-progress-pane" style="display: none">
	<div class="alert alert-info">
		<?= Text::_('COM_AKEEBABACKUP_BACKUP_TEXT_BACKINGUP') ?>
	</div>

    <div class="card">
		<h3 class="card-header bg-primary text-white">
		    <?= Text::_('COM_AKEEBABACKUP_BACKUP_LABEL_PROGRESS') ?>
		</h3>

        <div id="backup-progress-content" class="card-body">
            <div id="backup-steps"></div>
            <div id="backup-status" class="mt-3 border rounded bg-light text-dark">
                <div id="backup-step" class="p-1"></div>
                <div id="backup-substep" class="p-1 text-muted border-top"></div>
            </div>
            <div id="backup-percentage" class="progress mt-3 mb-3">
                <div class="progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" style="width: 0"></div>
            </div>
            <div id="response-timer" class="text-muted">
                <div class="text"></div>
            </div>
        </div>
    </div>

    <?php if (!AKEEBABACKUP_PRO): ?>
    <div class="alert alert-primary lead text-center fst-italic">
		<span class="fa fa-question-circle"></span>
		<?= Text::_('COM_AKEEBABACKUP_BACKUP_LBL_UPGRADENAG') ?>
    </div>
	<?php endif ?>
</div>

<?php // Backup complete ?>
<div id="backup-complete" style="display: none">
    <div class="card">
		<h3 class="card-header bg-success text-white">
		    <?php if(empty($this->returnURL)): ?>
			    <?= Text::_('COM_AKEEBABACKUP_BACKUP_HEADER_BACKUPFINISHED') ?>
		    <?php else: ?>
			    <?= Text::_('COM_AKEEBABACKUP_BACKUP_HEADER_BACKUPWITHRETURNURLFINISHED') ?>
		    <?php endif ?>
		</h3>

		<div id="finishedframe" class="card-body">
            <p>
				<?php if(empty($this->returnURL)): ?>
					<?= Text::_('COM_AKEEBABACKUP_BACKUP_TEXT_CONGRATS') ?>
				<?php else: ?>
					<?= Text::_('COM_AKEEBABACKUP_BACKUP_TEXT_PLEASEWAITFORREDIRECTION') ?>
				<?php endif ?>
            </p>

			<?php if(empty($this->returnURL)): ?>
                <a class="btn btn-outline-dark btn-lg" href="index.php?option=com_akeebabackup">
                    <span class="fa fa-arrow-left"></span>
					<?= Text::_('COM_AKEEBABACKUP_CONTROLPANEL') ?>
                </a>
                <a class="btn btn-primary btn-lg" href="index.php?option=com_akeebabackup&view=Manage">
                    <span class="fa fa-list-alt"></span>
					<?= Text::_('COM_AKEEBABACKUP_BUADMIN') ?>
                </a>
                <a class="btn btn-outline-dark" id="ab-viewlog-success" href="index.php?option=com_akeebabackup&view=Log&latest=1">
                    <span class="fa fa-search"></span>
					<?= Text::_('COM_AKEEBABACKUP_LOG') ?>
                </a>
	        <?php endif ?>
        </div>
    </div>
</div>

<?php // Backup warnings ?>
<div id="backup-warnings-panel" style="display:none">
    <div class="card mt-3">
		<h3 class="card-header bg-warning">
		    <?= Text::_('COM_AKEEBABACKUP_BACKUP_LABEL_WARNINGS') ?>
		</h3>
        <div id="warnings-list" class="card-body overflow-scroll" style="height: 20em">
        </div>
    </div>
</div>

<?php // Backup retry after error ?>
<div id="retry-panel" style="display: none">
	<div class="card mt-3">
		<h3 class="card-header bg-warning">
			<?= Text::_('COM_AKEEBABACKUP_BACKUP_HEADER_BACKUPRETRY') ?>
		</h3>
		<div id="retryframe" class="card-body">
			<p><?= Text::_('COM_AKEEBABACKUP_BACKUP_TEXT_BACKUPFAILEDRETRY') ?></p>
			<p class="mt-2 mb-2 fw-bold">
				<?= Text::_('COM_AKEEBABACKUP_BACKUP_TEXT_WILLRETRY') ?>
				<span id="akeebabackup-retry-timeout">0</span>
				<?= Text::_('COM_AKEEBABACKUP_BACKUP_TEXT_WILLRETRYSECONDS') ?>
			</p>
			<p>
				<button type="button"
						class="btn btn-outline-danger" id="comAkeebaBackupCancelResume">
					<span class="fa fa-times"></span>
					<?= Text::_('COM_AKEEBABACKUP_MULTIDB_GUI_LBL_CANCEL') ?>
				</button>
				<button type="button"
						class="btn btn-success" id="comAkeebaBackupResumeBackup">
					<span class="fa fa-redo"></span>
					<?= Text::_('COM_AKEEBABACKUP_BACKUP_TEXT_BTNRESUME') ?>
				</button>
			</p>

			<p><?= Text::_('COM_AKEEBABACKUP_BACKUP_TEXT_LASTERRORMESSAGEWAS') ?></p>
			<p id="backup-error-message-retry"></p>
		</div>
	</div>
</div>

<?php // Backup error (halt) ?>
<div id="error-panel" style="display: none">
	<div class="card mt-3">
		<h3 class="card-header bg-danger text-white">
			<?= Text::_('COM_AKEEBABACKUP_BACKUP_HEADER_BACKUPFAILED') ?>
		</h3>

		<div id="errorframe" class="card-body">
			<p>
				<?= Text::_('COM_AKEEBABACKUP_BACKUP_TEXT_BACKUPFAILED') ?>
			</p>
			<p id="backup-error-message"></p>

			<p>
				<?= Text::_('COM_AKEEBABACKUP_BACKUP_TEXT_READLOGFAIL' . (AKEEBABACKUP_PRO ? 'PRO' : '')) ?>
			</p>

			<div class="alert alert-info" id="error-panel-troubleshooting">
				<p>
					<?php if(AKEEBABACKUP_PRO): ?>
					<?= Text::_('COM_AKEEBABACKUP_BACKUP_TEXT_RTFMTOSOLVEPRO') ?>
					<?php endif ?>

					<?= Text::sprintf('COM_AKEEBABACKUP_BACKUP_TEXT_RTFMTOSOLVE', 'https://www.akeeba.com/documentation/akeeba-backup-joomla/backup-now.html?utm_source=akeeba_backup&utm_campaign=backuperrorlink#troubleshoot-backup') ?>
				</p>
				<p>
					<?php if(AKEEBABACKUP_PRO): ?>
					<?= Text::sprintf('COM_AKEEBABACKUP_BACKUP_TEXT_SOLVEISSUE_PRO', 'https://www.akeeba.com/support.html?utm_source=akeeba_backup&utm_campaign=backuperrorpro') ?>
					<?php else: ?>
					<?= Text::sprintf('COM_AKEEBABACKUP_BACKUP_TEXT_SOLVEISSUE_CORE', 'https://www.akeeba.com/subscribe.html?utm_source=akeeba_backup&utm_campaign=backuperrorcore','https://www.akeeba.com/support.html?utm_source=akeeba_backup&utm_campaign=backuperrorcore') ?>
					<?php endif ?>

					<?= Text::sprintf('COM_AKEEBABACKUP_BACKUP_TEXT_SOLVEISSUE_LOG', 'index.php?option=com_akeebabackup&view=Log&latest=1') ?>
				</p>
			</div>

			<?php if(AKEEBABACKUP_PRO): ?>
			<a class="btn btn-success" id="ab-alice-error" href="index.php?option=com_akeebabackup&view=Alice">
				<span class="fa fa-briefcase-medical"></span>
				<?= Text::_('COM_AKEEBABACKUP_BACKUP_ANALYSELOG') ?>
			</a>
			<?php endif ?>

			<a class="btn btn-primary" href="https://www.akeeba.com/documentation/akeeba-backup-joomla/troubleshoot-backup.html?utm_source=akeeba_backup&utm_campaign=backuperrorbutton">
				<span class="fa fa-book"></span>
				<?= Text::_('COM_AKEEBABACKUP_BACKUP_TROUBLESHOOTINGDOCS') ?>
			</a>

            <a class="btn btn-outline-dark" id="ab-viewlog-error" href="index.php?option=com_akeebabackup&view=Log&latest=1">
				<span class="fa fa-search"></span>
				<?= Text::_('COM_AKEEBABACKUP_LOG') ?>
			</a>
		</div>
	</div>
</div>
