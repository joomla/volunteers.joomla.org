<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

/** @var \Akeeba\Backup\Admin\View\Discover\Html $this */

$document = $this->container->platform->getDocument();
$document->addScriptOptions('akeeba.Configuration.URLs.browser', 'index.php?option=com_akeeba&view=Browser&processfolder=1&tmpl=component&folder=');
?>
@include('admin:com_akeeba/CommonTemplates/FolderBrowser')

<div class="akeeba-block--info">
    <p>
        @sprintf('COM_AKEEBA_DISCOVER_LABEL_S3IMPORT', 'index.php?option=com_akeeba&view=S3Import')
    </p>
    <p>
        <a class="akeeba-btn--teal--small" href="index.php?option=com_akeeba&view=S3Import">
            <span class="icon-box-add"></span>
            @lang('COM_AKEEBA_S3IMPORT')
        </a>
    </p>
</div>

<form name="adminForm" id="adminForm" action="index.php" method="post" class="akeeba-form--horizontal">

    <div class="akeeba-form-group">
        <label for="directory">
            @lang('COM_AKEEBA_DISCOVER_LABEL_DIRECTORY')
        </label>
        <div class="akeeba-input-group">
            <input type="text" name="directory" id="directory" value="{{{ $this->directory }}}" />
            <span class="akeeba-input-group-btn">
                <button class="akeeba-btn--inverse" id="browserbutton">
                    <span class="akion-folder"></span>
                    @lang('COM_AKEEBA_CONFIG_UI_BROWSE')
                </button>
            </span>
        </div>
        <p class="akeeba-help-text">
            @lang('COM_AKEEBA_DISCOVER_LABEL_SELECTDIR')
        </p>
    </div>

    <div class="akeeba-form-group--pull-right">
        <div class="akeeba-form-group--actions">
            <button class="akeeba-btn--primary" type="submit">
                @lang('COM_AKEEBA_DISCOVER_LABEL_SCAN')
            </button>
        </div>
    </div>

    <div class="akeeba-hidden-fields-container">
        <input type="hidden" name="option" value="com_akeeba" />
        <input type="hidden" name="view" value="Discover" />
        <input type="hidden" name="task" value="discover" />
        <input type="hidden" name="@token(true)" value="1" />
    </div>
</form>
