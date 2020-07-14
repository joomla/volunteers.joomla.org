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
<div class="akeeba-panel--information">
    <header class="akeeba-block-header">
        <h3>@lang('COM_AKEEBA_SCHEDULE_LBL_ALTCLICRON')</h3>
    </header>

    <div class="akeeba-block--info">
        <p>
            @lang('COM_AKEEBA_SCHEDULE_LBL_ALTCLICRON_INFO')
        </p>
        <a class="akeeba-btn--teal"
           href="https://www.akeebabackup.com/documentation/akeeba-backup-documentation/alternative-cron-script.html"
           target="_blank">
            <span class="akion-ios-book"></span>
            @lang('COM_AKEEBA_SCHEDULE_LBL_GENERICREADDOC')
        </a>
    </div>

    @if(!$this->croninfo->info->legacyapi)
        <div class="akeeba-block--failure">
            <p>
                @lang('COM_AKEEBA_SCHEDULE_LBL_LEGACYAPI_DISABLED')
            </p>
        </div>
    @elseif(!trim($this->croninfo->info->secret))
        <div class="akeeba-block--failure">
            <p>
                @lang('COM_AKEEBA_SCHEDULE_LBL_FRONTEND_SECRET')
            </p>
        </div>
    @else
        <p>
            @lang('COM_AKEEBA_SCHEDULE_LBL_GENERICUSECLI')
            <code>
				<?php echo $this->escape($this->croninfo->info->php_path); ?>
				<?php echo $this->escape($this->croninfo->altcli->path); ?>
            </code>
        </p>
        <p>
            <span class="akeeba-label--warning">@lang('COM_AKEEBA_SCHEDULE_LBL_CLIGENERICIMPROTANTINFO')</span>
            @sprintf('COM_AKEEBA_SCHEDULE_LBL_CLIGENERICINFO', $this->croninfo->info->php_path)
        </p>
    @endif
</div>