<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

/** @var \Akeeba\Component\AkeebaBackup\Administrator\View\Profile\HtmlView $this */

use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$user = JoomlaFactory::getApplication()->getIdentity();
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')
	->useScript('form.validate');

?>
<form action="<?php echo Route::_('index.php?option=com_akeebabackup&view=Profile&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="profile-form"
      aria-label="<?php echo Text::_('COM_AKEEBABACKUP_PROFILES_PAGETITLE_' . ( (int) $this->item->id === 0 ? 'NEW' : 'EDIT'), true); ?>"
      class="form-validate">

	<div>
		<div class="card">
			<div class="card-body">
				<?php echo $this->form->renderField('description'); ?>
				<?php echo $this->form->renderField('quickicon'); ?>

            <?php
                // If we're working on the default profile (ID=1), hide the access level field. Since this is our fallback
                // field, it MUST be always available to everyone
                if ($this->item->id != 1 && $user->authorise('core.manage', 'com_akeebabackup'))
                {
	                echo $this->form->renderField('access');
                }
                ?>
			</div>
		</div>
	</div>

	<input type="hidden" name="task" value="">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>