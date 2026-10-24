<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/** @var \Akeeba\Component\AkeebaBackup\Administrator\View\Schedule\HtmlView $this */

// Protect from unauthorized access
defined('_JEXEC') || die();

use Joomla\CMS\Language\Text;

?>
<div class="card mb-3">
	<h3 class="card-header">
		<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_ALTCLICRON') ?>
	</h3>

	<div class="card-body">
		<div class="alert alert-info">
			<p>
				<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_ALTCLICRON_INFO') ?>
			</p>
			<a class="btn btn-primary"
			   href="https://www.akeeba.com/documentation/akeeba-backup-joomla/alternative-cron-script.html"
			   target="_blank">
				<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_GENERICREADDOC') ?>
			</a>
		</div>

		<?php if(!$this->croninfo->info->legacyapi): ?>
			<div class="alert alert-danger">
				<p>
					<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_LEGACYAPI_DISABLED') ?>
				</p>
				<p>
					<a href="<?= $this->enableLegacyFrontendURL ?>" class="btn btn-primary">
						<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_BTN_ENABLE_LEGACYAPI') ?>
					</a>
				</p>
			</div>
		<?php elseif(!trim($this->croninfo->info->secret)): ?>
			<div class="alert alert-danger">
				<p>
					<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_FRONTEND_SECRET') ?>
				</p>
				<p>
					<a href="<?= $this->resetSecretWordURL ?>" class="btn btn-primary">
						<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_BTN_RESET_SECRETWORD') ?>
					</a>
				</p>
			</div>
		<?php else: ?>
			<p>
				<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_GENERICUSECLI') ?><br/>
				<code>
					<?php echo $this->escape($this->croninfo->info->php_path); ?>
					<?php echo $this->escape($this->croninfo->altcli->path); ?>
				</code>
			</p>
			<p>
				<span class="badge bg-warning">
					<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_CLIGENERICIMPROTANTINFO') ?>
				</span>
				<?php if (!$this->croninfo->info->php_accurate): ?>
				<?= Text::sprintf('COM_AKEEBABACKUP_SCHEDULE_LBL_CLIGENERICINFO', $this->croninfo->info->php_path) ?>
				<?php else: ?>
				<?= Text::sprintf('COM_AKEEBABACKUP_SCHEDULE_LBL_ACCURATEINFO', $this->croninfo->info->php_path) ?>
				<?php endif ?>
			</p>
		<?php endif ?>
	</div>
</div>
