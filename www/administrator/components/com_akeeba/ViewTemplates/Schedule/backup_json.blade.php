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
        <h3>@lang('COM_AKEEBA_SCHEDULE_LBL_JSONAPIBACKUP')</h3>
    </header>

    <div class="akeeba-block--info">
        <p>
            @lang('COM_AKEEBA_SCHEDULE_LBL_JSONAPIBACKUP_INFO')
        </p>
    </div>

    @if(!$this->croninfo->info->jsonapi)
        <div class="akeeba-block--failure">
            <p>
                @lang('COM_AKEEBA_SCHEDULE_LBL_JSONAPI_DISABLED')
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
            @lang('COM_AKEEBA_SCHEDULE_LBL_JSONAPI_INTRO')
        </p>

    <table class="akeeba-table--striped">
        <tbody>
        <tr>
            <td>@lang('COM_AKEEBA_SCHEDULE_LBL_JSONAPI_ENDPOINT')</td>
            <td>{{{ $this->croninfo->info->root_url }}}/{{{ $this->croninfo->json->path }}}</td>
        </tr>
        <tr>
            <td>@lang('COM_AKEEBA_SCHEDULE_LBL_JSONAPI_SECRET')</td>
            <td>{{{ $this->croninfo->info->secret }}}</td>
        </tr>
        </tbody>
    </table>

        <p>
            <small>
                @lang('COM_AKEEBA_SCHEDULE_LBL_JSONAPI_DISCLAIMER')
            </small>
        </p>
    @endif
</div>