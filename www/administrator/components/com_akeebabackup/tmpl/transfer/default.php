<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') || die();

use Joomla\CMS\Language\Text;

/** @var  $this  \Akeeba\Component\AkeebaBackup\Administrator\View\Transfer\HtmlView */
?>

<?php if ($this->force): ?>
	<div class="alert alert-warning">
		<h3>
			<?= Text::_('COM_AKEEBABACKUP_TRANSFER_FORCE_HEADER') ?>
		</h3>
		<p>
			<?= Text::_('COM_AKEEBABACKUP_TRANSFER_FORCE_BODY') ?>
		</p>
	</div>
<?php endif ?>

<?= $this->loadTemplate('prerequisites') ?>

<?php if (!empty($this->latestBackup)): ?>
	<?= $this->loadTemplate('remoteconnection') ?>
	<?= $this->loadTemplate('manualtransfer') ?>
	<?= $this->loadTemplate('upload') ?>
<?php endif; ?>
