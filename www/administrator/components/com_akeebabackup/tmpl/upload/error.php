<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') || die();

use Akeeba\Component\AkeebaBackup\Administrator\View\Upload\HtmlView;
use Joomla\CMS\Language\Text;

/** @var HtmlView $this */

$errorParts = explode("\n", $this->errorMessage, 2);

?>
<div class="alert alert-danger">
	<?php if (!empty($this->errorMessage)): ?>
    <h3 class="alert-heading">
        <?= Text::_('COM_AKEEBABACKUP_TRANSFER_MSG_FAILED') ?>
    </h3>
    <p>
        <?= $errorParts[0] ?>
    </p>
    <?php if(isset($errorParts[1])): ?>
        <pre><?= $errorParts[1] ?></pre>
    <?php endif ?>
	<?php else: ?>
		<?= Text::_('COM_AKEEBABACKUP_TRANSFER_MSG_FAILED') ?>
	<?php endif; ?>
</div>
