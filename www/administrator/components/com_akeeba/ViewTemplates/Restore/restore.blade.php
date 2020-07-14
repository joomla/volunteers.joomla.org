<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

/** @var \Akeeba\Backup\Admin\View\Restore\Html $this */
?>

<div class="akeeba-block--info">
    <p>
        @lang('COM_AKEEBA_RESTORE_LABEL_DONOTCLOSE')
    </p>
</div>


<div id="restoration-progress">
    <h4>
        @lang('COM_AKEEBA_RESTORE_LABEL_INPROGRESS')
    </h4>

    <table class="akeeba-table--striped">
        <tr>
            <td width="25%">
                @lang('COM_AKEEBA_RESTORE_LABEL_BYTESREAD')
            </td>
            <td>
                <span id="extbytesin"></span>
            </td>
        </tr>
        <tr>
            <td width="25%">
                @lang('COM_AKEEBA_RESTORE_LABEL_BYTESEXTRACTED')
            </td>
            <td>
                <span id="extbytesout"></span>
            </td>
        </tr>
        <tr>
            <td width="25%">
                @lang('COM_AKEEBA_RESTORE_LABEL_FILESEXTRACTED')
            </td>
            <td>
                <span id="extfiles"></span>
            </td>
        </tr>
    </table>

    <div id="response-timer">
        <div class="color-overlay"></div>
        <div class="text"></div>
    </div>
</div>

<div id="restoration-error" style="display:none">
    <div class="akeeba-block--failure">
        <h4>
            @lang('COM_AKEEBA_RESTORE_LABEL_FAILED')
        </h4>
        <div id="errorframe">
            <p>
                @lang('COM_AKEEBA_RESTORE_LABEL_FAILED_INFO')
            </p>
            <p id="backup-error-message"></p>
        </div>
    </div>
</div>

<div id="restoration-extract-ok" style="display:none">
    <div class="akeeba-block--success">
        <h4>
            @lang('COM_AKEEBA_RESTORE_LABEL_SUCCESS')
        </h4>
        <p>
            @lang('COM_AKEEBA_RESTORE_LABEL_SUCCESS_INFO2')
        </p>
        <p>
            @lang('COM_AKEEBA_RESTORE_LABEL_SUCCESS_INFO2B')
        </p>
    </div>

    <p>
        <button class="akeeba-btn--primary" id="restoration-runinstaller">
            <span class="akion-android-share"></span>
            @lang('COM_AKEEBA_RESTORE_LABEL_RUNINSTALLER')
        </button>
    </p>
    <p>
        <button class="akeeba-btn--green" id="restoration-finalize" style="display: none">
            <span class="akion-android-exit"></span>
            @lang('COM_AKEEBA_RESTORE_LABEL_FINALIZE')
        </button>
    </p>
</div>
