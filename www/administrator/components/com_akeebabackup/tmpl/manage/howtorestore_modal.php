<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
use Joomla\CMS\Language\Text;

defined('_JEXEC') || die();

/** @var  \Akeeba\Component\AkeebaBackup\Administrator\View\Manage\HtmlView $this */

// Make sure we only ever add this HTML and JS once per page
if (defined('AKEEBA_VIEW_JAVASCRIPT_HOWTORESTORE'))
{
	return;
}

define('AKEEBA_VIEW_JAVASCRIPT_HOWTORESTORE', 1);

$this->getDocument()->addScriptOptions('akeebabackup.Manage.ShowHowToRestoreModal', 1);

?>
<div id="akeebabackup-config-howtorestore-bubble"
	 class="modal fade"
	 tabindex="-1"
	 role="dialog"
	 aria-labelledby="akeeba-config-confwiz-title"
	 aria-hidden="true"
>
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h3 class="modal-title" id="akeeba-config-confwiz-title">
					<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_HOWDOIRESTORE_LEGEND') ?>
				</h3>
				<button type="button" class="btn-close novalidate" data-bs-dismiss="modal"
						aria-label="<?= Text::_('JLIB_HTML_BEHAVIOR_CLOSE') ?>"></button>
			</div>
			<div class="modal-body p-3">
				<p>
					<?= Text::sprintf('COM_AKEEBABACKUP_BUADMIN_LABEL_HOWDOIRESTORE_TEXT_' . (AKEEBABACKUP_PRO ? 'PRO' : 'CORE'), 'http://akee.ba/abrestoreanywhere', 'index.php?option=com_akeebabackup&view=Transfer', 'https://www.akeeba.com/latest-kickstart-core.zip') ?>
				</p>
				<p>
					<?php if (!AKEEBABACKUP_PRO): ?>
						<?= Text::sprintf('COM_AKEEBABACKUP_BUADMIN_LABEL_HOWDOIRESTORE_TEXT_CORE_INFO_ABOUT_PRO', 'https://www.akeeba.com/products/akeeba-backup.html') ?>
					<?php endif; ?>
				</p>
			</div>
			<div class="modal-footer">
				<button type="button"
						class="btn btn-primary novalidate" data-bs-dismiss="modal">
					<span class="fa fa-times"></span>
					<?= Text::_('COM_AKEEBABACKUP_BUADMIN_BTN_REMINDME') ?>
				</button>

				<a href="index.php?option=com_akeebabackup&view=Manage&task=hidemodal&<?= \Joomla\CMS\Factory::getApplication()->getFormToken() ?>=1" class="btn btn-success">
					<span class="fa fa-check-circle"></span>
					<?= Text::_('COM_AKEEBABACKUP_BUADMIN_BTN_DONTSHOWTHISAGAIN') ?>
				</a>
			</div>
		</div>
	</div>
</div>
