<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

$hasFiles = !empty($this->files);
?>
<form name="adminForm" id="adminForm" action="index.php" method="post" class="akeeba-form--horizontal">
    @if($hasFiles)
        <div class="akeeba-panel--information akeeba-form--horizontal">
            <div class="akeeba-form-group">
                <label for="directory2">@lang('COM_AKEEBA_DISCOVER_LABEL_DIRECTORY')</label>
                <input type="text" name="directory2" id="directory2" value="{{{ $this->directory }}}"
                       disabled="disabled" size="70" />
            </div>
        </div>

        <div class="akeeba-form-group">
            <label for="files">
                @lang('COM_AKEEBA_DISCOVER_LABEL_FILES')
            </label>
            <select name="files[]" id="files" multiple="multiple" class="input-xxlarge">
                @foreach($this->files as $file)
                    <option value="{{{ basename($file) }}}">{{{ basename($file) }}}</option>
                @endforeach
            </select>
            <p class="akeeba-help-text">
                @lang('COM_AKEEBA_DISCOVER_LABEL_SELECTFILES')
            </p>
        </div>

        <div class="akeeba-form-group--pull-right">
            <div class="akeeba-form-group--actions">
                <button class="akeeba-btn--primary" type="submit">
                    <span class="akion-ios-upload"></span>
                    @lang('COM_AKEEBA_DISCOVER_LABEL_IMPORT')
                </button>
            </div>
        </div>
    @endif

    @unless($hasFiles)
        <div class="akeeba-panel--warning">
            @lang('COM_AKEEBA_DISCOVER_ERROR_NOFILES')
        </div>
        <p>
            <button class="akeeba-btn--orange" type="submit">
                <span class="akion-arrow-left-a"></span>
                @lang('COM_AKEEBA_DISCOVER_LABEL_GOBACK')
            </button>
        </p>
    @endunless

    <div class="akeeba-hidden-fields-container">
        <input type="hidden" name="option" value="com_akeeba" />
        <input type="hidden" name="view" value="Discover" />
        @if($hasFiles)
            <input type="hidden" name="task" value="import" />
            <input type="hidden" name="directory" value="{{{ $this->directory }}}" />
        @else
            <input type="hidden" name="task" value="default" />
        @endif
        <input type="hidden" name="@token(true)" value="1" />
    </div>

</form>
