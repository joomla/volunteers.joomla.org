<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

?>
<form action="index.php" method="get" name="akeebaform">
    <input type="hidden" name="option" value="com_akeeba" />
    <input type="hidden" name="view" value="Upload" />
    <input type="hidden" name="task" value="upload" />
    <input type="hidden" name="tmpl" value="component" />
    <input type="hidden" name="id" value="{{ (int) $this->id }}" />
    <input type="hidden" name="part" value="{{ (int)$this->part }}" />
    <input type="hidden" name="frag" value="{{ (int)$this->frag }}" />
</form>

<div class="akeeba-panel--information">
    <p>
        @if($this->frag == 0)
            @sprintf('COM_AKEEBA_TRANSFER_MSG_UPLOADINGPART', $this->part+1, max($this->parts, 1))
        @else
            @sprintf('COM_AKEEBA_TRANSFER_MSG_UPLOADINGFRAG', $this->part+1, max($this->parts, 1), max(++$this->frag, 1))
        @endif
    </p>
</div>
