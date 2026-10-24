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
	<input type="hidden" name="part" value="-1" />
	<input type="hidden" name="frag" value="-1" />
	<?= \Joomla\CMS\HTML\HTMLHelper::_('form.token') ?>
</form>

<div class="alert alert-info">
	<?= Text::_('COM_AKEEBABACKUP_TRANSFER_MSG_START') ?>
</div>
