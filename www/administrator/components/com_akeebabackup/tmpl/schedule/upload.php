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
    <?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_CHECK_UPLOADS') ?>
</h2>

<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_CHECK_UPLOADS_HEADERINFO') ?>

<?php //  CLI CRON jobs ?>
<?= $this->loadAnyTemplate('schedule/upload_cli') ?>

<?php // Alternate CLI CRON jobs (using legacy front-end) ?>
<?= $this->loadAnyTemplate('schedule/upload_altcli') ?>

<?php // Legacy front-end backup ?>
<?= $this->loadAnyTemplate('schedule/upload_legacy') ?>
