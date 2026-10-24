<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/** @var $this \Akeeba\Component\AkeebaBackup\Administrator\View\Controlpanel\HtmlView */

// Protect from unauthorized access
defined('_JEXEC') || die();

use Joomla\CMS\Language\Text;

?>
<div class="card mb-2">
	<h3 class="card-header">
		<?= Text::_('COM_AKEEBABACKUP_BACKUP_STATS') ?>
	</h3>

	<div class="card-body">
		<?= $this->latestBackupCell ?>
	</div>
</div>
