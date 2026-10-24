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
<div id="backup-percentage" class="progress">
	<div class="progress-bar" role="progressbar"
		 style="width: <?= (int) $this->percent ?>%"
		 aria-valuenow="<?= (int) $this->percent ?>" aria-valuemin="0" aria-valuemax="100">
		<?= (int) $this->percent ?>%
	</div>
</div>

<div class="alert alert-info">
    <p>
		<?= Text::sprintf('COM_AKEEBABACKUP_REMOTEFILES_LBL_DOWNLOADEDSOFAR',
			$this->done, $this->total, $this->percent) ?>
    </p>
</div>
