<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

/** @var  $this  \Akeeba\Backup\Admin\View\Transfer\Html */
?>

@if ($this->force):
<div class="akeeba-block--warning">
    <h3>@lang('COM_AKEEBA_TRANSFER_FORCE_HEADER')</h3>
    <p>@lang('COM_AKEEBA_TRANSFER_FORCE_BODY')</p>
</div>
@endif

@include('admin:com_akeeba/CommonTemplates/FTPBrowser')
@include('admin:com_akeeba/CommonTemplates/SFTPBrowser')
@include('admin:com_akeeba/Transfer/default_prerequisites')

@unless(empty($this->latestBackup))
    @include('admin:com_akeeba/Transfer/default_remoteconnection')
    @include('admin:com_akeeba/Transfer/default_manualtransfer')
    @include('admin:com_akeeba/Transfer/default_upload')
@endunless
