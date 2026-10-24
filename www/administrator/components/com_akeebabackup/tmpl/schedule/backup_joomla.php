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
		<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_JOOMLASCHEDULER') ?>
	</h3>

	<div class="card-body">
		<?php if ($this->croninfo->joomla->supported): ?>
			<div class="alert alert-info">
				<p>
					<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_JOOMLASCHEDULER_INFO') ?>
				</p>
				<p>
					<a class="btn btn-primary me-3"
							href="https://www.akeeba.com/documentation/akeeba-backup-joomla/joomla-scheduled-tasks.html"
							target="_blank">
						<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_GENERICREADDOC') ?>
					</a>
					<a class="btn btn-success"
							href="index.php?option=com_scheduler&view=tasks">
						<span class="icon-clock" aria-hidden="true"></span>
						<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_JOOMLASCHEDULER_BUTTON') ?>
					</a>
				</p>
			</div>
		<?php elseif (version_compare(JVERSION, '4.1.0', 'lt')): ?>
			<div class="alert alert-warning">
				<h3 class="alert-heading">
					<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_JOOMLASCHEDULER_ONLYJ41_HEAD') ?>
				</h3>
				<p>
					<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_JOOMLASCHEDULER_ONLYJ41_BODY') ?>
				</p>
			</div>
		<?php else: ?>
			<div class="alert alert-warning">
				<h3 class="alert-heading">
					<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_JOOMLASCHEDULER_PLUGIN_DISABLED_HEAD') ?>
				</h3>
				<p>
					<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_JOOMLASCHEDULER_PLUGIN_DISABLED_BODY') ?>
				</p>
				<a class="btn btn-dark"
						href="index.php?option=com_plugins&filter[folder]=task&filter[enabled]=&filter[element]=akeebabackup&filter[access]=&filter[search]=">
					<span class="icon-plug" aria-hidden="true"></span>
					<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_JOOMLASCHEDULER_PLUGIN_DISABLED_BUTTON') ?>
				</a>
			</div>
		<?php endif ?>

	</div>
</div>
