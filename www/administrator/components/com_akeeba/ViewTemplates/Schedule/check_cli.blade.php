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
        <h3>@lang('COM_AKEEBA_SCHEDULE_LBL_CLICRON')</h3>
    </header>

    <p>
        @lang('COM_AKEEBA_SCHEDULE_LBL_GENERICUSECLI')
        <code>
			<?php echo $this->escape($this->checkinfo->info->php_path); ?>
			<?php echo $this->escape($this->checkinfo->cli->path); ?>
        </code>
    </p>
    <p>
        <span class="akeeba-label--warning">@lang('COM_AKEEBA_SCHEDULE_LBL_CLIGENERICIMPROTANTINFO')</span>
        @sprintf('COM_AKEEBA_SCHEDULE_LBL_CLIGENERICINFO', $this->checkinfo->info->php_path)
    </p>
</div>