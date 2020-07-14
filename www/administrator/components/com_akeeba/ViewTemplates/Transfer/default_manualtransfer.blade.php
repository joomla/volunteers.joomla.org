<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

/** @var  $this  \Akeeba\Backup\Admin\View\Transfer\Html */

$dotPos    = strrpos($this->latestBackup['archivename'], '.');
$extension = substr($this->latestBackup['archivename'], $dotPos + 1);
$bareName  = basename($this->latestBackup['archivename'], '.' . $extension);

?>
<div id="akeeba-transfer-manualtransfer" class="akeeba-panel--primary" style="display: none;">
    <header class="akeeba-block-header">
        <h3>
            @lang('COM_AKEEBA_TRANSFER_HEAD_MANUALTRANSFER')
        </h3>
    </header>

    <div class="akeeba-block--info">
        @lang('COM_AKEEBA_TRANSFER_LBL_MANUALTRANSFER_INFO')
    </div>

    <p style="text-align: center">
        <iframe width="640" height="480" src="https://www.youtube.com/embed/5A8EqOux494"
				frameborder="0"  allowfullscreen></iframe>
    </p>

    <h4>@lang('COM_AKEEBA_BUADMIN_LBL_BACKUPINFO')</h4>

    <h5>
        @lang('COM_AKEEBA_BUADMIN_LBL_ARCHIVENAME')
    </h5>

    <p>
        @if($this->latestBackup['multipart'] < 2)
            {{{ $this->latestBackup['archivename'] }}}
        @else
            @sprintf('COM_AKEEBA_TRANSFER_LBL_MANUALTRANSFER_MULTIPART', $this->latestBackup['multipart'])
        @endif
    </p>

    @if($this->latestBackup['multipart'] >= 2)
        <ul>
            @for($i = 1; $i < $this->latestBackup['multipart']; $i++)
                <li>{{{ $bareName . '.' . substr($extension, 0, 1) . sprintf('%02u', $i) }}}</li>
            @endfor
            <li>
                {{{ $this->latestBackup['archivename'] }}}
            </li>
        </ul>
    @endif

    <h5>
        @lang('COM_AKEEBA_BUADMIN_LBL_ARCHIVEPATH')
    </h5>
    <p>
        {{{ Akeeba\Backup\Admin\Helper\Utils::getRelativePath(JPATH_SITE, dirname($this->latestBackup['absolute_path'])) }}}
    </p>
</div>
