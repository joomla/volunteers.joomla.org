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

/** @var \Akeeba\Component\AkeebaBackup\Administrator\View\S3import\HtmlView $this */
?>
<form action="<?= Route::_('index.php?option=com_akeebabackup&view=S3import') ?>"
	  method="post" name="adminForm" id="adminForm">

	<input type="hidden" id="ak_s3import_folder" name="folder" value="<?= $this->escape($this->root) ?>" />

    <div class="akeebabackup-s3import-head card card-body p-3 bg-light mb-3">
		<div class="row row-cols-lg-auto g-3 align-items-center">
			<div class="col">
				<label class="visually-hidden" for="s3access">
					<?= Text::_('COM_AKEEBABACKUP_CONFIG_S3ACCESSKEY_TITLE') ?>
				</label>
				<input type="text" size="40" name="s3access" id="s3access"
					   class="form-control" autocomplete="off"
					   value="<?= $this->escape($this->s3access) ?>"
					   placeholder="<?= Text::_('COM_AKEEBABACKUP_CONFIG_S3ACCESSKEY_TITLE') ?>" />
			</div>

			<div class="col">
				<label class="visually-hidden" for="s3secret">
					<?= Text::_('COM_AKEEBABACKUP_CONFIG_S3SECRETKEY_TITLE') ?>
				</label>
				<input type="password" size="40" name="s3secret" id="s3secret"
					   class="form-control" autocomplete="off"
					   value="<?= $this->escape($this->s3secret) ?>"
					   placeholder="<?= Text::_('COM_AKEEBABACKUP_CONFIG_S3SECRETKEY_TITLE') ?>" />
			</div>

			<?php if(empty($this->buckets)): ?>
			<div class="col">
				<button class="btn btn-primary" id="akeebaS3importResetRoot" type="submit">
					<span class="fa fa-wifi"></span>
					<?= Text::_('COM_AKEEBABACKUP_S3IMPORT_LABEL_CONNECT') ?>
				</button>
			</div>
			<?php else: ?>
			<div class="col">
				<?= $this->bucketSelect ?>
			</div>

			<div class="col">
				<button class="btn btn-primary" id="akeebaS3importResetRoot" type="submit">
					<span class="fa fa-folder-open"></span>
					<?= Text::_('COM_AKEEBABACKUP_S3IMPORT_LABEL_CHANGEBUCKET') ?>
				</button>
			</div>
			<?php endif ?>
		</div>
    </div>

	<nav aria-label="breadcrumb" id="ak_crumbs_container">
		<ol class="breadcrumb border p-2 mb-3">
			<li>
				<a data-s3prefix="<?= base64_encode('') ?>" class="akeebaS3importChangeDirectory">
					&lt; root &gt;
				</a>
				<span class="divider">/</span>
			</li>

			<?php if(!empty($this->crumbs)): ?>
				<?php $runningCrumb = ''; $i = 0;
				foreach($this->crumbs as $crumb):
					$runningCrumb .= $crumb . '/'; $i++; ?>
					<li class="breadcrumb-item <?= $i == count($this->crumbs) ? 'active' : '' ?>">
						<a
								class="akeebaS3importChangeDirectory" style="cursor: pointer"
								data-s3prefix="<?= base64_encode($runningCrumb) ?>"
						>
							<?= $this->escape( $crumb ) ?>
						</a>
					</li>
				<?php endforeach; ?>
			<?php endif ?>
		</ol>
	</nav>

    <div class="row row-cols-1 row-cols-lg-2 g-3">
        <div class="col">
            <div id="ak_folder_container" class="card">
				<h3 class="card-header">
		            <?= Text::_('COM_AKEEBABACKUP_FILEFILTERS_LABEL_DIRS') ?>
				</h3>

                <div id="folders" class="card-body overflow-scroll" style="height: 45vh;">
					<?php if(!empty($this->contents['folders'])): ?>
						<?php foreach($this->contents['folders'] as $name => $record): ?>
                            <div class="folder-container">
                                <span class="folder-icon-container">
                                    <span class="fa fa-folder"></span>
                                </span>
                                <span class="folder-name akeebaS3importChangeDirectory"
									  style="cursor: pointer"
                                      data-s3prefix="<?= base64_encode($record['prefix']) ?>"
                                >
                                    <?= $this->escape( basename(rtrim($name, '/')) ) ?>
                                </span>
                            </div>
						<?php endforeach ?>
					<?php endif ?>
                </div>
            </div>
        </div>

        <div class="col">
            <div id="ak_files_container" class="card">
				<h3 class="card-header">
		            <?= Text::_('COM_AKEEBABACKUP_FILEFILTERS_LABEL_FILES') ?>
				</h3>
                <div id="files" class="card-body overflow-scroll" style="height: 45vh;">
					<?php if(!empty($this->contents['files'])): ?>
						<?php foreach($this->contents['files'] as $name => $record): ?>
                            <div class="file-container">
                                <span class="file-icon-container">
                                    <span class="fa fa-file"></span>
                                </span>
                                <span class="file-name file-clickable akeebaS3importObjectDownload"
									  style="cursor: pointer"
                                      data-s3object="<?= base64_encode($name) ?>">
                                    <?= $this->escape( basename($record['name']) ) ?>
                                </span>
                            </div>
                        <?php endforeach ?>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>
</form>