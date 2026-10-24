<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\HTML\HTMLHelper as HTMLHelperAlias;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/** @var  \Akeeba\Component\AkeebaBackup\Administrator\View\Alice\HtmlView $this */

$js = <<< JS
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function () {
		document.forms.adminForm.submit();        
    }, 500);
});
JS;

$this->getDocument()->getWebAssetManager()
	->useScript('com_akeebabackup.system')
	->addInlineScript($js, [], [], [
			'com_akeebabackup.system'
	]);

?>

<div class="card">
	<h3 class="card-header">
		<?= Text::_('COM_AKEEBABACKUP_ALICE_ANALYZE_LABEL_PROGRESS') ?>
	</h3>
	<div class="card-body">
		<h4>
			<?= $this->currentSection ?>
		</h4>
		<p>
			<?= $this->currentCheck ?>
		</p>
		<div class="progress">
			<div class="progress-bar" role="progressbar" style="width: <?= $this->percentage ?>%;"
				 aria-valuenow="<?= $this->percentage ?>" aria-valuemin="0" aria-valuemax="100"
			><?= $this->percentage ?>%</div>
		</div>
		<p class="text-center my-5">
			<img src="<?= Uri::root() ?>/media/com_akeebabackup/icons/spinner.gif"
				 alt="<?= Text::_('COM_AKEEBABACKUP_ALICE_ANALYZE_LABEL_PROGRESS') ?>" />
		</p>
	</div>
</div>

<form name="adminForm" id="adminForm"
	  action="<?= Route::_('index.php?option=com_akeebabackup&task=Alice.step') ?>" method="post">
	<?= HTMLHelperAlias::_('form.token') ?>
</form>
