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
<h2>
    <?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_RUN_BACKUPS') ?>
</h2>

<p>
    <?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_HEADERINFO') ?>
</p>

<?php // CLI CRON jobs ?>
<?= $this->loadAnyTemplate('schedule/backup_cli') ?>

<?php // Joomla Scheduled Tasks ?>
<?= $this->loadAnyTemplate('schedule/backup_joomla') ?>

<?php // Alternate CLI CRON jobs (using legacy front-end) ?>
<?= $this->loadAnyTemplate('schedule/backup_altcli') ?>

<?php // Legacy front-end backup ?>
<?= $this->loadAnyTemplate('schedule/backup_legacy') ?>

<?php // JSON API ?>
<?= $this->loadAnyTemplate('schedule/backup_json') ?>