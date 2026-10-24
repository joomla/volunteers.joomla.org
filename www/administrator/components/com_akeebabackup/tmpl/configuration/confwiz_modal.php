<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
use Joomla\CMS\Language\Text;

defined('_JEXEC') || die();

/** @var \Joomla\CMS\MVC\View\HtmlView|\Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewLoadAnyTemplateTrait $this */

// Make sure we only ever add this HTML and JS once per page
if (defined('AKEEBA_VIEW_JAVASCRIPT_CONFWIZ_MODAL'))
{
	return;
}

define('AKEEBA_VIEW_JAVASCRIPT_CONFWIZ_MODAL', 1);

$js = <<< JS
window.addEventListener('DOMContentLoaded', function() {
	new window.bootstrap.Modal(document.getElementById('akeeba-config-confwiz-bubble'), {
	        backdrop: 'static',
	        keyboard: true,
	        focus: true
	    }).show();
});

JS;

$this->getDocument()->getWebAssetManager()
	->useScript('bootstrap.modal')
	->addInlineScript($js, [], [], ['bootstrap.modal']);
?>

<div id="akeeba-config-confwiz-bubble"
	 class="modal fade"
	 role="dialog"
	 tabindex="-1"
	 aria-labelledby="akeeba-config-confwiz-title"
	 aria-hidden="true"
>
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h3 class="modal-title" id="akeeba-config-confwiz-title">
					<?= Text::_('COM_AKEEBABACKUP_CONFIG_HEADER_CONFWIZ') ?>
				</h3>
				<button type="button" class="btn-close novalidate" data-bs-dismiss="modal"
						aria-label="<?= Text::_('JLIB_HTML_BEHAVIOR_CLOSE') ?>"></button>
			</div>
			<div class="modal-body p-3">
				<p>
					<?= Text::_('COM_AKEEBABACKUP_CONFIG_LBL_CONFWIZ_INTRO') ?>
				</p>
				<p class="d-grid gap-2">
					<a href="index.php?option=com_akeebabackup&view=Configurationwizard"
					   class="btn bg-success text-white btn-lg"> <span class="fa fa-bolt"></span>&nbsp;
						<?= Text::_('COM_AKEEBABACKUP_CONFWIZ') ?>
					</a>
				</p>
				<p>
					<?= Text::_('COM_AKEEBABACKUP_CONFIG_LBL_CONFWIZ_AFTER') ?>
				</p>
			</div>
			<div class="modal-footer">
				<button
						class="btn btn-primary btn-sm"
						data-bs-dismiss="modal"
				>
					<span class="fa fa-times"></span>
					<?= Text::_('JCANCEL') ?>
				</button>
			</div>
		</div>
	</div>

</div>
