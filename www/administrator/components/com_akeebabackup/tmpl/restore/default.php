<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') || die();

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Akeeba\Component\AkeebaBackup\Administrator\View\Restore\HtmlView $this */

echo $this->loadAnyTemplate('commontemplates/ftpconnectiontest');
echo $this->loadAnyTemplate('commontemplates/errormodal');

$cParams = ComponentHelper::getParams('com_akeebabackup');

[$startTime, $duration, $timeZoneText] = $this->getTimeInformation($this->backupRecord);
?>

<form name="adminForm" id="adminForm"
	  action="<?= Route::_('index.php?option=com_akeebabackup&task=Restore.start') ?>"
	  method="post">
    <input type="hidden" name="id" value="<?= (int) $this->id ?>" />
	<?= HTMLHelper::_('form.token') ?>

	<input id="ftp_passive_mode" type="checkbox" checked autocomplete="off" aria-hidden="true" class="visually-hidden">
	<input id="ftp_ftps" type="checkbox" autocomplete="off" aria-hidden="true" class="visually-hidden">
	<input id="ftp_passive_mode_workaround" type="checkbox" autocomplete="off" aria-hidden="true" class="visually-hidden">

	<div class="alert alert-warning">
		<span class="fa fa-exclamation-triangle"></span>
		<?= Text::_('COM_AKEEBABACKUP_RESTORE_LABEL_WARN_ABOUT_RESTORE') ?>
	</div>

	<div class="alert alert-info">
		<h3>
			<?= Text::sprintf('COM_AKEEBABACKUP_RESTORE_LABEL_ARCHIVE_INFORMATION', $this->backupRecord['id']) ?>
		</h3>
		<div class="row mb-1">
			<div class="col-sm-3">
				<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_DESCRIPTION') ?>
			</div>
			<div class="col-sm-9">
				<?= $this->escape($this->backupRecord['description']) ?>
			</div>
		</div>
		<?php if (!empty($this->backupRecord['comment'])): ?>
		<div class="row mb-1">
			<div class="col-sm-3">
				<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_COMMENT') ?>
			</div>
			<div class="col-sm-9">
				<?= $this->escape($this->backupRecord['comment']) ?>
			</div>
		</div>
		<?php endif ?>
		<div class="row mb-1">
			<div class="col-sm-3">
				<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_START') ?>
			</div>
			<div class="col-sm-9">
				<?= $startTime ?> <?= $timeZoneText ?>
			</div>
		</div>
	</div>

	<div class="card mb-2">
		<h3 class="card-header">
			<?= Text::_('COM_AKEEBABACKUP_RESTORE_LABEL_EXTRACTIONMETHOD') ?>
		</h3>
		<div class="card-body">
			<div class="row mb-3">
				<label for="procengine" class="col-sm-3 col-form-label">
					<?= Text::_('COM_AKEEBABACKUP_RESTORE_LABEL_EXTRACTIONMETHOD') ?>
				</label>
				<div class="col-sm-9">
					<?= HTMLHelper::_('select.genericlist', $this->extractionmodes, 'procengine', [
						'list.attr' => [
							'class' => 'form-select'
						]
					], 'value', 'text', $this->ftpparams['procengine']) ?>
					<p class="form-text text-muted">
						<?= Text::_('COM_AKEEBABACKUP_RESTORE_LABEL_REMOTETIP') ?>
					</p>
				</div>
			</div>

			<?php if($cParams->get('showDeleteOnRestore', 0) == 1): ?>
				<div class="row mb-3">
					<label for="zapbefore" class="col-sm-3 col-form-label">
						<?= Text::_('COM_AKEEBABACKUP_RESTORE_LABEL_ZAPBEFORE') ?>
					</label>
					<div class="col-sm-9">
						<div class="switcher">
							<input type="radio" id="zapbefore0" name="zapbefore" value="0" checked class="active">
							<label for="zapbefore0"><?= Text::_('JNO') ?></label>
							<input type="radio" id="zapbefore1" name="zapbefore" value="1">
							<label for="zapbefore1"><?= Text::_('JYES') ?></label>
							<span class="toggle-outside"><span class="toggle-inside"></span></span>
						</div>
						<p class="form-text text-muted">
							<?= Text::_('COM_AKEEBABACKUP_RESTORE_LABEL_ZAPBEFORE_HELP') ?>
						</p>
					</div>
				</div>
			<?php endif ?>

			<div class="row mb-3">
				<label for="stealthmode" class="col-sm-3 col-form-label">
					<?= Text::_('COM_AKEEBABACKUP_RESTORE_LABEL_STEALTHMODE') ?>
				</label>
				<div class="col-sm-9">
					<div class="switcher">
						<input type="radio" id="stealthmode0" name="stealthmode" value="0" checked class="active">
						<label for="stealthmode0"><?= Text::_('JNO') ?></label>
						<input type="radio" id="stealthmode1" name="stealthmode" value="1">
						<label for="stealthmode1"><?= Text::_('JYES') ?></label>
						<span class="toggle-outside"><span class="toggle-inside"></span></span>
					</div>
					<p class="form-text text-muted">
						<?= Text::_('COM_AKEEBABACKUP_RESTORE_LABEL_STEALTHMODE_HELP') ?>
					</p>
				</div>
			</div>

			<?php if($this->extension == 'jps'): ?>
				<div class="row mb-3">
					<label for="jps_key" class="col-sm-3 col-form-label">
						<?= Text::_('COM_AKEEBABACKUP_CONFIG_JPS_KEY_TITLE') ?>
					</label>
					<div class="col-sm-9">
						<input id="jps_key" name="jps_key" value="" type="password" class="form-control" autocomplete="off" />
					</div>
				</div>
			<?php endif ?>

			<div class="row mb-3">
				<div class="col-sm-9 offset-sm-3">
					<button type="button"
							class="btn btn-success btn-lg me-1" id="backup-start">
						<span class="fa fa-history"></span>
						<?= Text::_('COM_AKEEBABACKUP_RESTORE_LABEL_START') ?>
					</button>
					<a href="<?= Route::_('index.php?option=com_akeebabackup&view=Manage') ?>"
					   class="btn btn-outline-danger me-4">
						<span class="fa fa-arrow-left"></span>
						<?= Text::_('JCANCEL') ?>
					</a>
					<button type="button"
							class="btn btn-outline-dark btn" id="testftp">
						<span class="akion-ios-pulse-strong"></span>
						<?= Text::_('COM_AKEEBABACKUP_CONFIG_DIRECTFTP_TEST_TITLE') ?>
					</button>
				</div>
			</div>
		</div>
	</div>

    <div id="ftpOptions" class="card mb-2">
		<h3 class="card-header">
		    <?= Text::_('COM_AKEEBABACKUP_RESTORE_LABEL_FTPOPTIONS') ?>
		</h3>
		<div class="card-body">
			<div class="row mb-3">
				<label for="ftp_host" class="col-sm-3 col-form-label">
					<?= Text::_('COM_AKEEBABACKUP_CONFIG_DIRECTFTP_HOST_TITLE') ?>
				</label>
				<div class="col-sm-9">
					<input id="ftp_host" name="" value="<?= $this->escape($this->ftpparams['ftp_host']) ?>" type="text" class="form-control"/>
				</div>
			</div>

			<div class="row mb-3">
				<label for="ftp_port" class="col-sm-3 col-form-label">
					<?= Text::_('COM_AKEEBABACKUP_CONFIG_DIRECTFTP_PORT_TITLE') ?>
				</label>
				<div class="col-sm-9">
					<input id="ftp_port" name="ftp_port" value="<?= $this->escape($this->ftpparams['ftp_port']) ?>" type="text" class="form-control"/>
				</div>
			</div>

			<div class="row mb-3">
				<label for="ftp_user" class="col-sm-3 col-form-label">
					<?= Text::_('COM_AKEEBABACKUP_CONFIG_DIRECTFTP_USER_TITLE') ?>
				</label>
				<div class="col-sm-9">
					<input id="ftp_user" name="ftp_user" value="<?= $this->escape($this->ftpparams['ftp_user']) ?>" type="text" class="form-control"/>
				</div>
			</div>

			<div class="row mb-3">
				<label for="ftp_pass" class="col-sm-3 col-form-label">
					<?= Text::_('COM_AKEEBABACKUP_CONFIG_DIRECTFTP_PASSWORD_TITLE') ?>
				</label>
				<div class="col-sm-9">
					<input id="ftp_pass" name="ftp_pass" value="<?= $this->escape($this->ftpparams['ftp_pass'])?>" type="password" autocomplete="off" class="form-control"/>
				</div>
			</div>
			<div class="row mb-3">
				<label for="ftp_initial_directory" class="col-sm-3 col-form-label">
					<?= Text::_('COM_AKEEBABACKUP_CONFIG_DIRECTFTP_INITDIR_TITLE') ?>
				</label>
				<div class="col-sm-9">
					<input id="ftp_initial_directory" name="ftp_root" value="<?= $this->escape($this->ftpparams['ftp_root']) ?>" type="text" class="form-control"/>
				</div>
			</div>
		</div>
    </div>

	<div class="card mb-2">
		<h3 class="card-header">
			<?= Text::_('COM_AKEEBABACKUP_RESTORE_LABEL_TIME_HEAD') ?>
		</h3>
		<div class="card-body">
			<div class="row mb-3">
				<label for="min_exec" class="col-sm-3 col-form-label">
					<?= Text::_('COM_AKEEBABACKUP_RESTORE_LABEL_MIN_EXEC') ?>
				</label>
				<div class="col-sm-9">
					<input type="number" min="0" max="180" name="min_exec" id="min_exec" class="form-control"
						   value="<?= (int) $this->getModel()->getState('min_exec', 0) ?>" />
					<p class="form-text text-muted">
						<?= Text::_('COM_AKEEBABACKUP_RESTORE_LABEL_MIN_EXEC_TIP') ?>
					</p>
				</div>
			</div>
			<div class="row mb-3">
				<label for="max_exec" class="col-sm-3 col-form-label">
					<?= Text::_('COM_AKEEBABACKUP_RESTORE_LABEL_MAX_EXEC') ?>
				</label>
				<div class="col-sm-9">
					<input type="number" min="0" max="180" name="max_exec" id="max_exec" class="form-control"
						   value="<?= (int) $this->getModel()->getState('max_exec', 5) ?>" />
					<p class="form-text text-muted">
						<?= Text::_('COM_AKEEBABACKUP_RESTORE_LABEL_MAX_EXEC_TIP') ?>
					</p>
				</div>
			</div>
		</div>
	</div>
</form>
