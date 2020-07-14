<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\Backup\Admin\View\Upload\Html;

/** @var Html $this */

$errorParts = explode("\n", $this->errorMessage, 2);

?>
<div class="akeeba-panel--failure">
    <h3>
        @lang('COM_AKEEBA_TRANSFER_MSG_FAILED')
    </h3>
    <p>
        {{{ $errorParts[0] }}}
    </p>
    @if(isset($errorParts[1]))
        <pre>{{{ $errorParts[1] }}}</pre>
    @endif
</div>
