<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') || die();

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var  Akeeba\Component\AkeebaBackup\Administrator\View\Remotefiles\HtmlView $this */
?>
<div id="backup-percentage" class="progress">
    <div id="progressbar-inner" class="progress-bar" style="width: <?= min(max(0, (int) $this->percent), 100) ?>%"></div>
</div>

<div class="alert alert-info">
    <?= Text::sprintf('COM_AKEEBABACKUP_REMOTEFILES_LBL_DOWNLOADEDSOFAR', $this->done, $this->total, $this->percent) ?>
</div>

<form action="<?= Route::_('index.php?option=com_akeebabackup&task=Remotefiles.dltoserver&tmpl=component') ?>" method="post" name="adminForm" id="adminForm">
    <input type="hidden" name="id" value="<?= (int)$this->id ?>" />
    <input type="hidden" name="part" value="<?= (int)$this->part ?>" />
    <input type="hidden" name="frag" value="<?= (int)$this->frag ?>" />
	<?= HTMLHelper::_('form.token') ?>
</form>
