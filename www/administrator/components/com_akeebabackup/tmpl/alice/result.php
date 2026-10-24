<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Language\Text;

/** @var  \Akeeba\Component\AkeebaBackup\Administrator\View\Alice\HtmlView $this */

?>
<div class="card">
	<h3 class="card-header"><?= Text::_('COM_AKEEBABACKUP_ALICE_ANALYSIS_REPORT_HEAD') ?></h3>
	<div class="card-body">
		<p>
			<?= Text::sprintf('COM_AKEEBABACKUP_ALICE_ANALYSIS_REPORT_LBL_SUMMARY', $this->doneChecks) ?>
		</p>
	</div>
</div>

<?php if ($this->aliceStatus == 'success'): ?>
    <p class="alert alert-success">
        <?= Text::_('COM_AKEEBABACKUP_ALICE_ANALYSIS_REPORT_LBL_SUMMARY_SUCCESS') ?>
    </p>
<?php elseif ($this->aliceStatus == 'warnings'): ?>
    <p class="alert alert-warning">
        <?= Text::_('COM_AKEEBABACKUP_ALICE_ANALYSIS_REPORT_LBL_SUMMARY_WARNINGS') ?>
    </p>
<?php else: ?>
    <p class="alert alert-danger">
        <?= Text::_('COM_AKEEBABACKUP_ALICE_ANALYSIS_REPORT_LBL_SUMMARY_ERRORS') ?>
    </p>
<?php endif ?>

<?php if ($this->aliceStatus != 'success'): ?>
    <div class="card">
		<h3 class="card-header bg-<?= ($this->aliceStatus == 'error') ? 'danger text-white' : 'warning' ?>">
		    <?php if ($this->aliceStatus == 'error'): ?>
			    <?= Text::_('COM_AKEEBABACKUP_ALICE_ANALYSIS_REPORT_LBL_ERROR') ?>
		    <?php else: ?>
			    <?= Text::_('COM_AKEEBABACKUP_ALICE_ANALYSIS_REPORT_LBL_WARNINGS') ?>
		    <?php endif; ?>
		</h3>

		<div class="card-body">
			<?php if ($this->aliceStatus == 'error'): ?>
				<h4><?= $this->aliceError['message'] ?></h4>
				<p class="fst-italic">
					<?= Text::_('COM_AKEEBABACKUP_ALICE_ANALYSIS_REPORT_LBL_SOLUTION') ?>
				</p>
				<p>
					<?= $this->aliceError['solution'] ?>
				</p>
			<?php else: ?>
				<table class="table table-striped">
					<tbody>
					<?php foreach($this->aliceWarnings as $warning): ?>
						<tr>
							<td>
								<h5><?= $warning['message'] ?></h5>
								<p class="fst-italic">
									<?= Text::_('COM_AKEEBABACKUP_ALICE_ANALYSIS_REPORT_LBL_SOLUTION') ?>
								</p>
								<p>
									<?= $warning['solution'] ?>
								</p>
							</td>
						</tr>
					<?php endforeach ?>
					</tbody>
				</table>
			<?php endif ?>
		</div>
    </div>

    <p class="my-3 alert alert-info">
        <?= Text::_('COM_AKEEBABACKUP_ALICE_ANALYSIS_REPORT_LBL_NEXTSTEPS') ?>
    </p>
<?php endif ?>