<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

$uri = clone Uri::getInstance();
$uri->setVar('_akeeba_backup_on_update_toggle', $this->getApplication()->getSession()->getToken());

?>
<div class="card border-success border-2 mb-3">
	<div class="h3 card-header bg-success text-white d-flex flex-row">
		<div class="flex-grow-1">
			<?= Text::_('PLG_SYSTEM_BACKUPONUPDATE_LBL_TITLE') ?>
			<span class="fs-4 text-light">
				&ndash;
				<?= Text::_('PLG_SYSTEM_BACKUPONUPDATE_LBL_ENABLED') ?>
			</span>
		</div>

		<div class="small">
			<span class="fa fa-info-circle" aria-hidden="true"
				  title="<?= Text::_('PLG_SYSTEM_BACKUPONUPDATE_LBL_POWERED') ?>"
			></span>
			<span class="visually-hidden"><?= Text::_('PLG_SYSTEM_BACKUPONUPDATE_LBL_POWERED') ?></span>
		</div>
	</div>
	<div class="card-body">
		<p>
			<span class="fa fa-check-circle text-success me-2" aria-hidden="true"></span>
			<?= Text::_('PLG_SYSTEM_BACKUPONUPDATE_LBL_CONTENT_ACTIVE') ?>
		</p>
		<p>
			<a href="<?= htmlentities($uri->toString()) ?>"
			   class="btn btn-outline-danger btn-sm">
				<span class="fa fa-power-off" aria-hidden="true"></span>
				<?= Text::_('PLG_SYSTEM_BACKUPONUPDATE_LBL_TOGGLE_DEACTIVATE') ?>
			</a>
		</p>
		<p class="text-muted fst-italic small">
			<?= Text::_('PLG_SYSTEM_BACKUPONUPDATE_LBL_CONTENT_TIP') ?>
		</p>
	</div>
</div>
