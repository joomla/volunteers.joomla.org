<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/** @var \Akeeba\Component\AkeebaBackup\Administrator\View\Schedule\HtmlView $this */

// Protect from unauthorized access
defined('_JEXEC') || die();

use Joomla\CMS\Language\Text;

if ($this->secretIsStrong)
{
	return;
}

?>
<div class="alert alert-danger">
	<h4 class="alert-heading">
		<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_WEAKSECRET_HEAD') ?>
	</h4>
	<p>
		<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_LBL_WEAKSECRET_BODY') ?>
	</p>
	<p>
		<a href="<?= $this->resetSecretWordURL ?>" class="btn btn-primary">
			<?= Text::_('COM_AKEEBABACKUP_SCHEDULE_BTN_RESET_SECRETWORD') ?>
		</a>
	</p>
</div>
