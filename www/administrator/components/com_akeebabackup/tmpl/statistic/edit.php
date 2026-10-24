<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

/** @var \Akeeba\Component\AkeebaBackup\Administrator\View\Profile\HtmlView $this */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')
	->useScript('form.validate');

?>
<form action="<?php echo Route::_('index.php?option=com_akeebabackup&view=Statistic&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="profile-form"
      aria-label="<?php echo Text::_('COM_AKEEBABACKUP_BUADMIN_LOG_EDITCOMMENT', true); ?>"
      class="form-validate">

	<div>
		<div class="card">
			<div class="card-body">
				<?php echo $this->form->renderField('id'); ?>
				<?php echo $this->form->renderField('description'); ?>
				<?php echo $this->form->renderField('comment'); ?>
				<?php echo $this->form->renderField('frozen'); ?>
				<?php echo $this->form->renderField('profile_id'); ?>
				<?php echo $this->form->renderField('origin'); ?>
				<?php echo $this->form->renderField('status'); ?>
				<?php echo $this->form->renderField('backupstart'); ?>
				<?php echo $this->form->renderField('backupend'); ?>
			</div>
		</div>
	</div>

	<input type="hidden" name="task" value="">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>