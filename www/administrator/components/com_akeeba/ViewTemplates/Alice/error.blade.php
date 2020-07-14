<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  \Akeeba\Backup\Admin\View\Alice\Html $this */
?>

<div class="akeeba-panel--red">
    <header class="akeeba-block-header">
        <h3>
            @lang('COM_AKEEBA_ALICE_ERR_ANALYZEFAILED_HEADER')
        </h3>
    </header>
    <p>
        @lang('COM_AKEEBA_ALICE_ERR_ANALYZEFAILED_INFO')
    </p>
    <h4>
            <span class="akeeba-label--red--small">
                {{ $this->errorException->getCode() }}
            </span>
        {{ $this->errorException->getMessage() }}
    </h4>
    <p>
        {{ $this->errorException->getFile() }} :: L{{ $this->errorException->getLine() }}
    </p>
    <pre>{{ $this->errorException->getTraceAsString() }}</pre>
</div>