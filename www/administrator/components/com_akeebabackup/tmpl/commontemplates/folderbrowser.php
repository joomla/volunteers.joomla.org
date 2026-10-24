<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die();

use Joomla\CMS\Language\Text;

?>

<div class="modal"
	 id="folderBrowserDialog"
	 tabindex="-1"
	 role="dialog"
	 aria-labelledby="folderBrowserDialogLabel"
     aria-hidden="true"
>
	<div class="modal-dialog modal-lg modal-fullscreen-sm-down modal-dialog-scrollable modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h3 id="folderBrowserDialogLabel" class="modal-title">
					<?= Text::_('COM_AKEEBABACKUP_CONFIG_UI_BROWSER_TITLE') ?>
				</h3>
				<button type="button" class="btn-close novalidate" data-bs-dismiss="modal"
						aria-label="<?= Text::_('JLIB_HTML_BEHAVIOR_CLOSE') ?>">
			</div>
			<div class="modal-body p-3">
				<div id="folderBrowserDialogBody">
				</div>
			</div>
		</div>
	</div>
</div>
