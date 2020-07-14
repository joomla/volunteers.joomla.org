<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  \Akeeba\Backup\Admin\View\Alice\Html $this */

?>
<div class="akeeba-panel--information">
    <header class="akeeba-block-header">
        <h3>@lang('COM_AKEEBA_ALICE_ANALYSIS_REPORT_HEAD')</h3>
    </header>
    <p>
        @sprintf('COM_AKEEBA_ALICE_ANALYSIS_REPORT_LBL_SUMMARY', $this->doneChecks)
    </p>
</div>

@if ($this->aliceStatus == 'success')
    <p class="akeeba-block--success large">
        @lang('COM_AKEEBA_ALICE_ANALYSIS_REPORT_LBL_SUMMARY_SUCCESS')
    </p>
@elseif ($this->aliceStatus == 'warnings')
    <p class="akeeba-block--warning">
        @lang('COM_AKEEBA_ALICE_ANALYSIS_REPORT_LBL_SUMMARY_WARNINGS')
    </p>
@else
    <p class="akeeba-block--error">
        @lang('COM_AKEEBA_ALICE_ANALYSIS_REPORT_LBL_SUMMARY_ERRORS')
    </p>
@endif

@if ($this->aliceStatus != 'success')
    <div class="akeeba-panel--{{ ($this->aliceStatus == 'error') ? 'danger' : 'warning' }}">
        <header class="akeeba-block-header">
            @if ($this->aliceStatus == 'error')
                @lang('COM_AKEEBA_ALICE_ANALYSIS_REPORT_LBL_ERROR')
            @else
                @lang('COM_AKEEBA_ALICE_ANALYSIS_REPORT_LBL_WARNINGS')
            @endif
        </header>

        @if ($this->aliceStatus == 'error')
            <h5>{{ $this->aliceError['message'] }}</h5>
            <p>
                <em>@lang('COM_AKEEBA_ALICE_ANALYSIS_REPORT_LBL_SOLUTION')</em>
                {{ $this->aliceError['solution'] }}
            </p>
        @else
            <table class="akeeba-table--striped" width="100%">
            <tbody>
            @foreach($this->aliceWarnings as $warning)
                <tr>
                    <td>
                        <h5>{{ $warning['message'] }}</h5>
                        <p>
                            <em>@lang('COM_AKEEBA_ALICE_ANALYSIS_REPORT_LBL_SOLUTION')</em>
                            {{ $warning['solution'] }}
                        </p>
                    </td>
                </tr>
            @endforeach
            </tbody>
            </table>
        @endif
    </div>

    <p>
        @lang('COM_AKEEBA_ALICE_ANALYSIS_REPORT_LBL_NEXTSTEPS')
    </p>
@endif