<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
use Joomla\CMS\Language\Text as JText;

defined('_JEXEC') or die();

/** @var  \Akeeba\Backup\Admin\View\RemoteFiles\Html $this */

// Is the engine incapable of any action?
$noCapabilities = !$this->capabilities['delete'] && !$this->capabilities['downloadToFile']
	&& !$this->capabilities['downloadToBrowser'];

// Are all remote files no longer present?
$downloadToFileNotAvailable = !$this->actions['downloadToFile'] && $this->capabilities['downloadToFile'];
$deleteNotAvailable = !$this->actions['delete'] && $this->capabilities['delete'];
$allRemoteFilesGone = $downloadToFileNotAvailable && $deleteNotAvailable;
?>
<div class="akeeba-panel--info" id="akeebaBackupRemoteFilesWorkInProgress" style="text-align: center; display: none">
    <header class="akeeba-block-header">
        <h3>@lang('COM_AKEEBA_REMOTEFILES_INPROGRESS_HEADER')</h3>
    </header>
    <img src="<?= $this->getContainer()->template->parsePath('media://com_akeeba/icons/spinner.gif') ?>"
         alt="@lang('COM_AKEEBA_REMOTEFILES_INPROGRESS_LBL_PLEASEWAIT')" />
    <p>
		@lang('COM_AKEEBA_REMOTEFILES_INPROGRESS_LBL_UNDERWAY')
    </p>
    <p>
		@lang('COM_AKEEBA_REMOTEFILES_INPROGRESS_LBL_WAITINGINFO')
    </p>
</div>

<div id="akeebaBackupRemoteFilesMainInterface">

    <div class="akeeba-panel--primary">
        <header class="akeeba-block-header">
            <h3>@lang('COM_AKEEBA_REMOTEFILES')</h3>
        </header>

        {{-- ===== No capabilities ===== --}}
        @if($noCapabilities)
            <div class="akeeba-block--failure">
                <h3>
                    @lang('COM_AKEEBA_REMOTEFILES_ERR_NOTSUPPORTED_HEADER')
                </h3>
                <p>
                    @lang('COM_AKEEBA_REMOTEFILES_ERR_NOTSUPPORTED')
                </p>
            </div>
            {{-- ===== Remote files gone, no operations available ===== --}}
        @elseif($deleteNotAvailable)
            <div class="akeeba-block--failure">
                <h3>
                    @lang('COM_AKEEBA_REMOTEFILES_ERR_NOTSUPPORTED_HEADER')
                </h3>
                <p>
                    @lang('COM_AKEEBA_REMOTEFILES_ERR_NOTSUPPORTED_ALREADYONSERVER')
                </p>
            </div>
        @else
            @if($this->actions['downloadToFile'])
                <a class="akeeba-btn--teal akeebaRemoteFilesShowWait"
                   href="index.php?option=com_akeeba&view=RemoteFiles&task=dltoserver&tmpl=component&id=<?= $this->id ?>&part=-1"
                >
                    <span class="akion-android-download"></span>
                    <span>@lang('COM_AKEEBA_REMOTEFILES_FETCH')</span>
                </a>
            @else
                <button class="akeeba-btn--teal" disabled="disabled"
                        title="@lang($this->capabilities['downloadToFile'] ? 'COM_AKEEBA_REMOTEFILES_ERR_DOWNLOADEDTOFILE_ALREADY' : 'COM_AKEEBA_REMOTEFILES_ERR_UNSUPPORTED')">
                    <span class="akion-android-download"></span>
                    <span>@lang('COM_AKEEBA_REMOTEFILES_FETCH')</span>
                </button>
            @endif

            @if($this->actions['delete'])
                <a class="akeeba-btn--red akeebaRemoteFilesShowWait"
                   href="index.php?option=com_akeeba&view=RemoteFiles&task=delete&tmpl=component&id=<?= $this->id ?>&part=-1"
                >
                    <span class="akion-trash-a"></span>
                    <span>@lang('COM_AKEEBA_REMOTEFILES_DELETE')</span>
                </a>
            @else
                <button class="akeeba-btn--teal" disabled="disabled"
                        title="@lang($this->capabilities['delete'] ? 'COM_AKEEBA_REMOTEFILES_ERR_DELETE_ALREADY' : 'COM_AKEEBA_REMOTEFILES_ERR_UNSUPPORTED')">
                    <span class="akion-trash-a"></span>
                    <span>@lang('COM_AKEEBA_REMOTEFILES_DELETE')</span>
                </button>
            @endif
        @endif
    </div>

    @if($this->actions['downloadToBrowser'] != 0)
        <div class="akeeba-panel--info">
            <header class="akeeba-block-header">
                <h3>@lang('COM_AKEEBA_REMOTEFILES_LBL_DOWNLOADLOCALLY')</h3>
            </header>

            @for($part = 0; $part < $this->actions['downloadToBrowser']; $part++)
                <a href="index.php?option=com_akeeba&view=RemoteFiles&task=dlfromremote&id=<?= $this->id ?>&part=<?= $part ?>"
                   class="akeeba-btn--small--grey">
                    <span class="akion-ios-download"></span>
					@sprintf('COM_AKEEBA_REMOTEFILES_PART', $part)
                </a>
            @endfor
        </div>
    @endif

</div>
