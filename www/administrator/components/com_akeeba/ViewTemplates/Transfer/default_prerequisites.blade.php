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
<div class="akeeba-panel--{{ empty($this->latestBackup) ? 'red' : 'information' }}">
    <header class="akeeba-block-header">
        <h3>
			@lang('COM_AKEEBA_TRANSFER_HEAD_PREREQUISITES')
        </h3>
    </header>

    <table class="akeeba-table akeeba-table--striped" width="100%">
        <tbody>
        <tr>
            <td>
                <strong>
					@lang('COM_AKEEBA_TRANSFER_LBL_COMPLETEBACKUP')
                </strong>

                <br/>
                <small>
					@if(empty($this->latestBackup))
						@lang('COM_AKEEBA_TRANSFER_ERR_COMPLETEBACKUP')
					@else
						@sprintf('COM_AKEEBA_TRANSFER_LBL_COMPLETEBACKUP_INFO', $this->lastBackupDate)
					@endif
                </small>
            </td>
            <td width="20%">
				@if(empty($this->latestBackup))
                    <a href="index.php?option=com_akeeba&view=Backup" class="akeeba-btn--green"
                       id="akeeba-transfer-btn-backup">
						@lang('COM_AKEEBA_BACKUP_LABEL_START')
                    </a>
				@endif
            </td>
        </tr>
		@if(!(empty($this->latestBackup)))
        <tr>
            <td>
                <strong>
                    @sprintf('COM_AKEEBA_TRANSFER_LBL_SPACE', $this->spaceRequired['string'])
                </strong>
                <br/>
                <small id="akeeba-transfer-err-space" style="display: none">
                    @lang('COM_AKEEBA_TRANSFER_ERR_SPACE')
                </small>
            </td>
            <td>
            </td>
        </tr>
		@endif
        </tbody>
    </table>
</div>

