<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') || die();

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

$steps = ['flush', 'minexec', 'directory', 'dbopt', 'maxexec', 'splitsize']

?>

<div id="akeeba-confwiz">

    <div id="backup-progress-pane">
        <div class="alert alert-warning">
            <?= Text::_('COM_AKEEBABACKUP_CONFWIZ_INTROTEXT') ?>
        </div>

        <div id="backup-progress-header" class="card">
			<h3 class="card-header bg-primary text-white">
				<span class="fa fa-diagnoses me-2"></span>
		        <?= Text::_('COM_AKEEBABACKUP_CONFWIZ_PROGRESS') ?>
			</h3>

            <div id="backup-progress-content" class="card-body">
                <div id="backup-steps" class="d-flex flex-column align-items-stretch gap-2">
					<?php foreach ($steps as $step): ?>
					<div id="step-<?= $step ?>" class="border rounded bg-light p-1">
						<span class="text-secondary px-1 py-1 rounded-5 float-start border border-light border-2" id="step-<?= $step ?>-wait">
							<span class="fa fa-hourglass-start fa-fw" aria-hidden="true"></span>
						</span>
						<span class="text-dark px-1 py-1 rounded-5 float-start border border-light border-2 d-none" id="step-<?= $step ?>-run">
							<span class="fa fa-play fa-fw" aria-hidden="true"></span>
						</span>
						<span class="bg-success text-white px-1 rounded-5 float-start border border-light border-2 d-none" id="step-<?= $step ?>-done">
							<span class="fa fa-check fa-fw" aria-hidden="true"></span>
						</span>
						<span class="bg-danger text-white px-1 rounded-5 float-end border border-light border-2 d-none" id="step-<?= $step ?>-error">
							<span class="fa fa-xmark fa-fw" aria-hidden="true"></span>
						</span>
						<span class="ms-2 text-dark bg-light"><?= Text::_('COM_AKEEBABACKUP_CONFWIZ_' . $step) ?></span>
					</div>
					<?php endforeach; ?>
				</div>
                <div class="backup-steps-container mt-4 p-2 bg-info border-top border-3 text-white">
                    <div id="backup-substep">&nbsp;</div>
                </div>
            </div>
        </div>

    </div>

    <div id="error-panel" class="card card-body my-3 border-2 border-danger" style="display:none">
        <h3 class="text-danger"><?= Text::_('COM_AKEEBABACKUP_CONFWIZ_HEADER_FAILED') ?></h3>
        <div id="errorframe">
            <p id="backup-error-message">
            </p>
        </div>
    </div>

    <div id="backup-complete" style="display: none">
        <div class="card card-body border-2 border-success">
            <h3 class="text-success"><?= Text::_('COM_AKEEBABACKUP_CONFWIZ_HEADER_FINISHED') ?></h3>
            <div id="finishedframe">
                <p>
                    <?= Text::_('COM_AKEEBABACKUP_CONFWIZ_CONGRATS') ?>
                </p>
                <p>
                    <a
                            class="btn btn-primary btn-lg"
                            href="<?= $this->escape( Uri::base() )?>index.php?option=com_akeebabackup&view=Backup">
                        <span class="fa fa-play"></span>
                        <?= Text::_('COM_AKEEBABACKUP_BACKUP') ?>
                    </a>
                    <a
                            class="btn btn-outline-secondary"
                            href="<?= $this->escape( Uri::base() )?>index.php?option=com_akeebabackup&view=Configuration">
                        <span class="fa fa-wrench"></span>
                        <?= Text::_('COM_AKEEBABACKUP_CONFIG') ?>
                    </a>
					<?php /* The Schedule page echoes the front-end/JSON API secrets, so it is only available to users who
					         are allowed to edit the component's Options page (core.admin). */ ?>
					<?php if(AKEEBABACKUP_PRO && $this->getCurrentUser()->authorise('core.admin', 'com_akeebabackup')): ?>
                    <a
                            class="btn btn-outline-dark"
                            href="<?= $this->escape( Uri::base() )?>index.php?option=com_akeebabackup&view=Schedule">
                        <span class="fa fa-calendar"></span>
                        <?= Text::_('COM_AKEEBABACKUP_SCHEDULE') ?>
                    </a>
                    <?php endif ?>
                </p>
            </div>
        </div>
    </div>
</div>
