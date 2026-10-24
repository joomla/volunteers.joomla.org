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
<div class="modal fade"
	 id="testFtpDialog"
	 tabindex="-1"
	 role="dialog"
	 aria-labelledby="testFtpDialogLabel"
	 aria-hidden="true"
>
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h3 class="modal-title" id="testFtpDialogLabel"></h3>
				<button type="button" class="btn-close novalidate" data-bs-dismiss="modal"
						aria-label="<?= Text::_('JLIB_HTML_BEHAVIOR_CLOSE') ?>">
			</div>
			<div class="modal-body p-3">
				<div class="alert alert-success" id="testFtpDialogBodyOk"></div>
				<div class="alert alert-danger" id="testFtpDialogBodyFail"></div>
			</div>
		</div>
	</div>
</div>
