<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') || die();

use Joomla\CMS\Language\Text;

?>
<form action="index.php?option=com_akeebabackup&task=Upload.upload&tmpl=component"
	  method="post" name="akeebauploadform">
	<input type="hidden" name="id" value="<?= (int) $this->id ?>" />
	<input type="hidden" name="part" value="<?= (int) $this->part ?>" />
	<input type="hidden" name="frag" value="<?= (int) $this->frag ?>" />
	<?= \Joomla\CMS\HTML\HTMLHelper::_('form.token') ?>
</form>

<div class="alert alert-info">
	<?php if($this->frag == 0): ?>
		<?= Text::sprintf('COM_AKEEBABACKUP_TRANSFER_MSG_UPLOADINGPART', $this->part+1, max($this->parts, 1)) ?>
	<?php else: ?>
		<?= Text::sprintf('COM_AKEEBABACKUP_TRANSFER_MSG_UPLOADINGFRAG', $this->part+1, max($this->parts, 1), max(++$this->frag, 1)) ?>
	<?php endif ?>
</div>
