<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/** @var \Akeeba\Backup\Admin\View\Schedule\Html $this */

// Protect from unauthorized access
defined('_JEXEC') or die();
?>
<h2>
    @lang('COM_AKEEBA_SCHEDULE_LBL_RUN_BACKUPS')
</h2>

<p>
    @lang('COM_AKEEBA_SCHEDULE_LBL_HEADERINFO')
</p>

{{-- CLI CRON jobs --}}
@include('admin:com_akeeba/Schedule/backup_cli')

{{-- Alternate CLI CRON jobs (using legacy front-end) --}}
@include('admin:com_akeeba/Schedule/backup_altcli')

{{-- Legacy front-end backup --}}
@include('admin:com_akeeba/Schedule/backup_legacy')

{{-- JSON API --}}
@include('admin:com_akeeba/Schedule/backup_json')