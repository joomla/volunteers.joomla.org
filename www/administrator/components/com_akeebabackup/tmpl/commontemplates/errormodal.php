<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die();

use Joomla\CMS\Language\Text;

?>
<div id="errorDialog"
	 class="modal fade"
	 tabindex="-1"
	 role="dialog"
	 aria-labelledby="errorDialogLabel"
	 aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h3 id="errorDialogLabel" class="modal-title">
					<?= Text::_('COM_AKEEBABACKUP_CONFIG_UI_AJAXERRORDLG_TITLE') ?>
				</h3>
				<button type="button" class="btn-close novalidate" data-bs-dismiss="modal"
						aria-label="<?= Text::_('JLIB_HTML_BEHAVIOR_CLOSE') ?>">
			</div>
			<div class="modal-body p-3">
				<p>
					<?= Text::_('COM_AKEEBABACKUP_CONFIG_UI_AJAXERRORDLG_TEXT') ?>
				</p>
				<pre id="errorDialogPre"></pre>
			</div>
		</div>
	</div>

</div>
