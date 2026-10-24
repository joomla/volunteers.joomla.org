<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') || die();

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/** @var  $this  \Akeeba\Component\AkeebaBackup\Administrator\View\Transfer\HtmlView */

?>
<div class="card mb-3">
	<h3 class="card-header bg-primary text-white">
		<?= Text::_('COM_AKEEBABACKUP_TRANSFER_HEAD_REMOTECONNECTION') ?>
	</h3>

    <div class="card-body">
        <div id="akeeba-transfer-main-container">
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label"
					   for="akeeba-transfer-url">
                    <?= Text::_('COM_AKEEBABACKUP_TRANSFER_LBL_NEWURL') ?>
                </label>

				<div class="col-sm-9">
					<div class="input-group">
						<input class="form-control" id="akeeba-transfer-url" placeholder="http://www.example.com"
							   type="url" autocomplete="off"
							   value="<?= $this->escape($this->newSiteUrl)?>">
						<button class="btn btn-dark"
								id="akeeba-transfer-btn-url" type="button">
							<?= Text::_('COM_AKEEBABACKUP_TRANSFER_ERR_NEWURL_BTN') ?>
						</button>
					</div>

					<div class="form-text" id="akeeba-transfer-lbl-url">
						<p>
							<?= Text::_('COM_AKEEBABACKUP_TRANSFER_LBL_NEWURL_TIP') ?>
						</p>
					</div>
				</div>
            </div>

            <div id="akeeba-transfer-row-url" class="row mb-3">
				<div class="col-sm-9 col-sm-offset-3">
					<img alt="Loading. Please wait..."
						 id="akeeba-transfer-loading"
						 src="<?= Uri::root() ?>media/com_akeebabackup/icons/loading.gif"
						 style="display: none;" />
					<br />

					<div class="alert alert-danger"
						 id="akeeba-transfer-err-url-same" style="display: none;">
						<?= Text::_('COM_AKEEBABACKUP_TRANSFER_ERR_NEWURL_SAME') ?>
						<p class="text-center">
							<a href="https://www.akeeba.com/videos/1212-akeeba-backup/1618-abtc04-restore-site-new-server.html"
							   class="btn btn-link">
								<?= Text::_('COM_AKEEBABACKUP_TRANSFER_LBL_MANUALTRANSFER_LINK') ?>
							</a>
						</p>
					</div>

					<div class="alert alert-danger"
						 id="akeeba-transfer-err-url-invalid" style="display: none;">
						<?= Text::_('COM_AKEEBABACKUP_TRANSFER_ERR_NEWURL_INVALID') ?>
					</div>

					<div class="alert alert-danger"
						 id="akeeba-transfer-err-url-notexists" style="display: none;">
						<p>
							<?= Text::_('COM_AKEEBABACKUP_TRANSFER_ERR_NEWURL_NOTEXISTS') ?>
						</p>
						<p>
							<button class="btn btn-danger" id="akeeba-transfer-err-url-notexists-btn-ignore"
									type="button">
								&#9888;
								<?= Text::_('COM_AKEEBABACKUP_TRANSFER_ERR_NEWURL_BTN_IGNOREERROR') ?>
							</button>
						</p>
					</div>
				</div>
            </div>
        </div>

        <div id="akeeba-transfer-ftp-container" style="display: none">
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label"
					   for="akeeba-transfer-ftp-method">
                    <?= Text::_('COM_AKEEBABACKUP_TRANSFER_LBL_TRANSFERMETHOD') ?>
                </label>

				<div class="col-sm-9">
					<?= HTMLHelper::_('select.genericlist', $this->transferOptions, 'akeeba-transfer-ftp-method', ['list.attr' => ['class' => 'form-select']], 'value', 'text', $this->transferOption, 'akeeba-transfer-ftp-method') ?>
					<?php if($this->hasFirewalledMethods): ?>
					<div class="alert alert-warning">
						<h5>
							<?= Text::_('COM_AKEEBABACKUP_TRANSFER_WARN_FIREWALLED_HEAD') ?>
						</h5>
						<p>
							<?= Text::_('COM_AKEEBABACKUP_TRANSFER_WARN_FIREWALLED_BODY') ?>
						</p>
					</div>
					<?php endif ?>
				</div>

            </div>

            <div class="row mb-3">
                <label class="col-sm-3 col-form-label"
					   for="akeeba-transfer-ftp-host">
                    <?= Text::_('COM_AKEEBABACKUP_TRANSFER_LBL_FTP_HOST') ?>
                </label>
				<div class="col-sm-9">
                	<input class="form-control" id="akeeba-transfer-ftp-host" placeholder="ftp.example.com"
						   type="text"
						   value="<?= $this->escape($this->ftpHost)?>" />
				</div>
            </div>

            <div class="row mb-3">
                <label class="col-sm-3 col-form-label"
					   for="akeeba-transfer-ftp-port">
                    <?= Text::_('COM_AKEEBABACKUP_TRANSFER_LBL_FTP_PORT') ?>
                </label>
				<div class="col-sm-9">
                	<input class="form-control" id="akeeba-transfer-ftp-port" placeholder="21"
						   type="text" value="<?= $this->escape($this->ftpPort)?>" />
				</div>
            </div>

            <div class="row mb-3">
                <label class="col-sm-3 col-form-label"
					   for="akeeba-transfer-ftp-username">
                    <?= Text::_('COM_AKEEBABACKUP_TRANSFER_LBL_FTP_USERNAME') ?>
                </label>
				<div class="col-sm-9">
                	<input class="form-control" id="akeeba-transfer-ftp-username" placeholder="myUserName" type="text"
						   value="<?= $this->escape($this->ftpUsername)?>" />
				</div>
            </div>

            <div class="row mb-3">
                <label for="akeeba-transfer-ftp-password"
					   class="col-sm-3 col-form-label">
                    <?= Text::_('COM_AKEEBABACKUP_TRANSFER_LBL_FTP_PASSWORD') ?>
                </label>
				<div class="col-sm-9">
                	<input class="form-control" id="akeeba-transfer-ftp-password" placeholder="myPassword"
						   type="password" value="<?= $this->escape($this->ftpPassword)?>" />
				</div>
            </div>

            <div class="row mb-3">
                <label class="col-sm-3 col-form-label"
					   for="akeeba-transfer-ftp-pubkey">
                    <?= Text::_('COM_AKEEBABACKUP_TRANSFER_LBL_FTP_PUBKEY') ?>
                </label>
				<div class="col-sm-9">
                	<input class="form-control" id="akeeba-transfer-ftp-pubkey" placeholder="<?= $this->escape(JPATH_SITE . DIRECTORY_SEPARATOR)?>id_rsa.pub"
						   type="text"
						   value="<?= $this->escape($this->ftpPubKey)?>" />
				</div>
            </div>

            <div class="row mb-3">
                <label class="col-sm-3 col-form-label"
					   for="akeeba-transfer-ftp-privatekey">
                    <?= Text::_('COM_AKEEBABACKUP_TRANSFER_LBL_FTP_PRIVATEKEY') ?>
                </label>
				<div class="col-sm-9">
                	<input class="form-control" id="akeeba-transfer-ftp-privatekey"
						   placeholder="<?= $this->escape(JPATH_SITE . DIRECTORY_SEPARATOR)?>id_rsa"
						   type="text"
						   value="<?= $this->escape($this->ftpPrivateKey)?>" />
				</div>
            </div>

            <div class="row mb-3">
                <label class="col-sm-3 col-form-label"
					   for="akeeba-transfer-ftp-directory">
                    <?= Text::_('COM_AKEEBABACKUP_TRANSFER_LBL_FTP_DIRECTORY') ?>
                </label>
				<div class="col-sm-9">
                	<input class="form-control" id="akeeba-transfer-ftp-directory"
						   placeholder="public_html" type="text" value="<?= $this->escape($this->ftpDirectory)?>" />
				</div>
            </div>

            <!-- Chunk method -->
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label"
					   for="akeeba-transfer-chunkmode">
                    <?= Text::_('COM_AKEEBABACKUP_TRANSFER_LBL_TRANSFERMODE') ?>
                </label>
				<div class="col-sm-9">
					<?= HTMLHelper::_('select.genericlist', $this->chunkOptions, 'akeeba-transfer-chunkmode', ['list.attr' => ['class' => 'form-select']], 'value', 'text', $this->chunkMode, 'akeeba-transfer-chunkmode') ?>
					<p class="form-text">
						<?= Text::_('COM_AKEEBABACKUP_TRANSFER_LBL_TRANSFERMODE_INFO') ?>
					</p>
				</div>
            </div>

            <!-- Chunk size -->
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label"
					   for="akeeba-transfer-chunksize">
                    <?= Text::_('COM_AKEEBABACKUP_TRANSFER_LBL_CHUNKSIZE') ?>
                </label>
				<div class="col-sm-9">
					<?= HTMLHelper::_('select.genericlist', $this->chunkSizeOptions, 'akeeba-transfer-chunksize', ['list.attr' => ['class' => 'form-select']], 'value', 'text', $this->chunkSize, 'akeeba-transfer-chunksize') ?>
					<p class="form-text">
						<?= Text::_('COM_AKEEBABACKUP_TRANSFER_LBL_CHUNKSIZE_INFO') ?>
					</p>
				</div>
            </div>

            <div class="row mb-3" id="akeeba-transfer-ftp-passive-container">
                <label class="col-sm-3 col-form-label"
					   for="akeeba-transfer-ftp-passive">
                    <?= Text::_('COM_AKEEBABACKUP_TRANSFER_LBL_FTP_PASSIVE') ?>
                </label>
				<div class="col-sm-9">
					<?= $this->booleanSwitch('akeeba-transfer-ftp-passive', $this->ftpPassive ? 1 : 0) ?>
                </div>
            </div>

            <div class="row mb-3" id="akeeba-transfer-ftp-passive-fix-container">
                <label class="col-sm-3 col-form-label"
					   for="akeeba-transfer-ftp-passive-fix">
                    <?= Text::_('COM_AKEEBABACKUP_CONFIG_ENGINE_ARCHIVER_DIRECTFTPCURL_PASVWORKAROUND_TITLE') ?>
                </label>
				<div class="col-sm-9">
	                <?= $this->booleanSwitch('akeeba-transfer-ftp-passive-fix', $this->ftpPassiveFix ? 1 : 0) ?>
					<p class="form-text">
						<?= Text::_('COM_AKEEBABACKUP_CONFIG_ENGINE_ARCHIVER_DIRECTFTPCURL_PASVWORKAROUND_DESCRIPTION') ?>
					</p>
                </div>
            </div>

            <div class="alert alert-danger" id="akeeba-transfer-ftp-error" style="display:none;">
                <p id="akeeba-transfer-ftp-error-body">MESSAGE</p>

                <a class="btn btn-warning"
				   href="<?= Route::_('index.php?option=com_akeebabackup&view=Transfer&force=1') ?>"
				   id="akeeba-transfer-ftp-error-force" style="display:none">
                    <?= Text::_('COM_AKEEBABACKUP_TRANSFER_ERR_OVERRIDE') ?>
                </a>
            </div>

            <div class="row mb-3">
                <div class="col-sm-9 col-sm-offset-3">
                    <button class="btn btn-primary"
							id="akeeba-transfer-btn-apply"
							type="button">
                        <?= Text::_('COM_AKEEBABACKUP_TRANSFER_BTN_FTP_PROCEED') ?>
                    </button>
                </div>
            </div>

            <div class="alert alert-info" id="akeeba-transfer-apply-loading" style="display: none;">
                <h4>
                    <?= Text::_('COM_AKEEBABACKUP_TRANSFER_LBL_VALIDATING') ?>
                </h4>
                <p class="text-center">
                    <img src="<?= Uri::root() ?>media/com_akeebabackup/icons/loading.gif"
                         alt="<?= Text::_('COM_AKEEBABACKUP_TRANSFER_LBL_VALIDATING') ?>" />
                </p>
            </div>
        </div>
    </div>
</div>
