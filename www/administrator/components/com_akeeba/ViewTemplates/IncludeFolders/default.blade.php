<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/** @var \Akeeba\Backup\Admin\View\IncludeFolders\Html $this */

?>
@include('admin:com_akeeba/CommonTemplates/ErrorModal')
@include('admin:com_akeeba/CommonTemplates/FolderBrowser')
@include('admin:com_akeeba/CommonTemplates/ProfileName')

<div class="akeeba-container--primary">
    <div id="ak_list_container">
        <table id="ak_list_table" class="akeeba-table--striped--dynamic-line-editor">
            <thead>
            <tr>
                <!-- Delete -->
                <th width="50px">&nbsp;</th>
                <!-- Edit -->
                <th width="100px">&nbsp;</th>
                <!-- Directory path -->
                <th>
						<span rel="popover" data-original-title="@lang('COM_AKEEBA_INCLUDEFOLDER_LABEL_DIRECTORY')"
                              data-content="@lang('COM_AKEEBA_INCLUDEFOLDER_LABEL_DIRECTORY_HELP')">
							@lang('COM_AKEEBA_INCLUDEFOLDER_LABEL_DIRECTORY')
						</span>
                </th>
                <!-- Directory path -->
                <th>
						<span rel="popover" data-original-title="@lang('COM_AKEEBA_INCLUDEFOLDER_LABEL_VINCLUDEDIR')"
                              data-content="@lang('COM_AKEEBA_INCLUDEFOLDER_LABEL_VINCLUDEDIR_HELP')">
							@lang('COM_AKEEBA_INCLUDEFOLDER_LABEL_VINCLUDEDIR')
						</span>
                </th>
            </tr>
            </thead>
            <tbody id="ak_list_contents">
            </tbody>
        </table>
    </div>
</div>
