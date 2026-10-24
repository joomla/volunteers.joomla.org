<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/** @var $this \Akeeba\Component\AkeebaBackup\Administrator\View\Controlpanel\HtmlView */

// Protect from unauthorized access
defined('_JEXEC') || die();

use Joomla\CMS\Language\Text;

?>
<div class="card mb-2">
	<h3 class="card-header">
		<?= Text::_('COM_AKEEBABACKUP_CPANEL_LABEL_STATUSSUMMARY') ?>
	</h3>

	<div class="card-body">
        <?php // Backup status summary ?>
        <?= $this->statusCell ?>

		<?php // Warnings ?>
        <?php if($this->countWarnings): ?>
            <div class="akeebabackup-engine-warnings-container">
                <?= $this->detailsCell ?>
            </div>
            <hr />
        <?php endif ?>

        <?php // Version ?>
        <p class="ak_version">
			<strong><?= Text::_('COM_AKEEBABACKUP_' . (AKEEBABACKUP_PRO ? 'PRO' : 'CORE')) ?></strong>
			<?= AKEEBABACKUP_VERSION ?>
			<span class="text-muted">
				(<?= AKEEBABACKUP_DATE ?>)
			</span>
        </p>

		<div class="d-flex flex-column">
			<?php // Changelog ?>
			<button type="button"
					id="btnchangelog" class="btn btn-outline-primary mb-2 me-2"
					data-bs-toggle="modal" data-bs-target="#akeeba-changelog">
				<span class="fa fa-clipboard-check"></span>
				CHANGELOG
			</button>

			<?php // Donation CTA ?>
			<?php if(!AKEEBABACKUP_PRO): ?>
				<a
						href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=KDVQPB4EREBPY&source=url"
						class="btn btn-outline-success mb-2 me-2">
					<span class="fa fa-donate"></span>
					Donate via PayPal
				</a>
			<?php endif ?>

			<?php // Pro upsell ?>
			<?php if(!AKEEBABACKUP_PRO && (time() - $this->lastUpsellDismiss < 1296000)): ?>
				<a href="https://www.akeeba.com/landing/akeeba-backup.html"
				   class="btn btn-sm btn-outline-dark mb-2 me-2">
					<span class="icon-akeeba"></span>
					<?= Text::_('COM_AKEEBABACKUP_CONTROLPANEL_BTN_LEARNMORE') ?>
				</a>
			<?php endif ?>
		</div>

    </div>
</div>

<div class="modal fade" id="akeeba-changelog" tabindex="-1"
	 aria-labelledby="akeeba-changelog-header" aria-hidden="true"
	 role="dialog">
	<div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h3 class="modal-title" id="akeeba-changelog-header">
					<?= Text::_('CHANGELOG') ?>
				</h3>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= Text::_('JLIB_HTML_BEHAVIOR_CLOSE') ?>"></button>
			</div>
			<div class="modal-body p-3">
				<?= $this->formattedChangelog ?>
			</div>
		</div>
	</div>
</div>