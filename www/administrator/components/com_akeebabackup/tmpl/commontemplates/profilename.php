<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') || die();

use Joomla\CMS\Language\Text;

/** @var \Joomla\CMS\MVC\View\HtmlView|\Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewProfileIdAndNameTrait $this */

?>
<div class="alert alert-info">
	<strong><?= Text::_('COM_AKEEBABACKUP_CPANEL_PROFILE_TITLE') ?></strong>:
	#<?= (int)($this->profileId) ?> <?= $this->escape($this->profileName) ?>
</div>
