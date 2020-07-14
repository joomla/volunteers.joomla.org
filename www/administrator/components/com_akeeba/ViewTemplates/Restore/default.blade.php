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
@include('admin:com_akeeba/CommonTemplates/FTPBrowser')
@include('admin:com_akeeba/CommonTemplates/FTPConnectionTest')
@include('admin:com_akeeba/CommonTemplates/ErrorModal')

<form name="adminForm" id="adminForm" action="index.php" method="post" class="akeeba-form--horizontal">
    <input type="hidden" name="option" value="com_akeeba" />
    <input type="hidden" name="view" value="Restore" />
    <input type="hidden" name="task" value="start" />
    <input type="hidden" name="id" value="{{ (int)$this->id }}" />
    <input type="hidden" name="@token(true)" value="1" />

    <h4>
        @lang('COM_AKEEBA_RESTORE_LABEL_EXTRACTIONMETHOD')
    </h4>

    <input id="ftp_passive_mode" type="checkbox" checked autocomplete="off" style="display: none">
    <input id="ftp_ftps" type="checkbox" autocomplete="off" style="display: none">
    <input id="ftp_passive_mode_workaround" type="checkbox" autocomplete="off" style="display: none">

    <div class="akeeba-form-group">
        <label for="procengine">
            @lang('COM_AKEEBA_RESTORE_LABEL_EXTRACTIONMETHOD')
        </label>
        @jhtml('select.genericlist', $this->extractionmodes, 'procengine', '', 'value', 'text', $this->ftpparams['procengine'])
        <p class="akeeba-help-text">
            @lang('COM_AKEEBA_RESTORE_LABEL_REMOTETIP')
        </p>
    </div>

    @if($this->container->params->get('showDeleteOnRestore', 0) == 1)
        <div class="akeeba-form-group">
            <label for="zapbefore">
                @lang('COM_AKEEBA_RESTORE_LABEL_ZAPBEFORE')
            </label>
            @jhtml('FEFHelper.select.booleanswitch', 'zapbefore', 0)
            <p class="akeeba-help-text">
                @lang('COM_AKEEBA_RESTORE_LABEL_ZAPBEFORE_HELP')
            </p>
        </div>
    @endif

    @if($this->extension == 'jps')
        <h4>
            @lang('COM_AKEEBA_RESTORE_LABEL_JPSOPTIONS')
        </h4>

        <div class="akeeba-form-group">
            <label for="jps_key">
                @lang('COM_AKEEBA_CONFIG_JPS_KEY_TITLE')
            </label>
            <input id="jps_key" name="jps_key" value="" type="password" />
        </div>
    @endif

    <div id="ftpOptions">
        <h4>@lang('COM_AKEEBA_RESTORE_LABEL_FTPOPTIONS')</h4>

        <div class="akeeba-form-group">
            <label for="ftp_host">
                @lang('COM_AKEEBA_CONFIG_DIRECTFTP_HOST_TITLE')
            </label>
            <input id="ftp_host" name="" value="{{{ $this->ftpparams['ftp_host'] }}}" type="text" />
        </div>
        <div class="akeeba-form-group">
            <label for="ftp_port">
                @lang('COM_AKEEBA_CONFIG_DIRECTFTP_PORT_TITLE')
            </label>
            <input id="ftp_port" name="ftp_port" value="{{{ $this->ftpparams['ftp_port'] }}}" type="text" />
        </div>
        <div class="akeeba-form-group">
            <label for="ftp_user">
                @lang('COM_AKEEBA_CONFIG_DIRECTFTP_USER_TITLE')
            </label>
            <input id="ftp_user" name="ftp_user" value="{{{ $this->ftpparams['ftp_user'] }}}" type="text" />
        </div>
        <div class="akeeba-form-group">
            <label for="ftp_pass">
                @lang('COM_AKEEBA_CONFIG_DIRECTFTP_PASSWORD_TITLE')
            </label>
            <input id="ftp_pass" name="ftp_pass" value="{{{ $this->ftpparams['ftp_pass'] }}}" type="password" />
        </div>
        <div class="akeeba-form-group">
            <label for="ftp_initial_directory">
                @lang('COM_AKEEBA_CONFIG_DIRECTFTP_INITDIR_TITLE')
            </label>
            <input id="ftp_initial_directory" name="ftp_root" value="{{{ $this->ftpparams['ftp_root'] }}}" type="text" />
        </div>
    </div>

    <h4>@lang('COM_AKEEBA_RESTORE_LABEL_TIME_HEAD')</h4>

    <div class="akeeba-form-group">
        <label for="min_exec">
            @lang('COM_AKEEBA_RESTORE_LABEL_MIN_EXEC')
        </label>
        <input type="number" min="0" max="180" name="min_exec"
               value="{{ $this->getModel()->getState('min_exec', 0, 'int') }}" />
        <p class="akeeba-help-text">
            @lang('COM_AKEEBA_RESTORE_LABEL_MIN_EXEC_TIP')
        </p>
    </div>
    <div class="akeeba-form-group">
        <label for="max_exec">
            @lang('COM_AKEEBA_RESTORE_LABEL_MAX_EXEC')
        </label>
        <input type="number" min="0" max="180" name="max_exec"
               value="{{ $this->getModel()->getState('max_exec', 5, 'int') }}" />
        <p class="akeeba-help-text">
            @lang('COM_AKEEBA_RESTORE_LABEL_MAX_EXEC_TIP')
        </p>
    </div>

    <hr />

    <div class="akeeba-form-group--pull-right">
        <div class="akeeba-form-group--actions">
            <button class="akeeba-btn--primary" id="backup-start">
                <span class="akion-refresh"></span>
                @lang('COM_AKEEBA_RESTORE_LABEL_START')
            </button>
            <button class="akeeba-btn--grey" id="testftp">
                <span class="akion-ios-pulse-strong"></span>
                @lang('COM_AKEEBA_CONFIG_DIRECTFTP_TEST_TITLE')
            </button>
        </div>
    </div>

</form>
