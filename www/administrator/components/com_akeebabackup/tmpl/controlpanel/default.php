<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/** @var $this \Akeeba\Component\AkeebaBackup\Administrator\View\Controlpanel\HtmlView */

// Protect from unauthorized access
use Joomla\CMS\Component\ComponentHelper;

defined('_JEXEC') || die();

?>

<?= $this->loadAnyTemplate('Controlpanel/warnings') ?>

<?php // Main area ?>
<div class="container">
	<div class="row">
		<?php // LEFT COLUMN (66% desktop width) ?>
		<div class="col col-12 col-lg-8">
			<?php // Active profile switch ?>
			<?= $this->loadAnyTemplate('Controlpanel/profile') ?>

			<?php //  One Click Backup icons ?>
			<?php if( ! (empty($this->quickIconProfiles)) && $this->permissions['backup']): ?>
				<?= $this->loadAnyTemplate('Controlpanel/oneclick') ?>
			<?php endif ?>

			<?php // Web Push ?>
			<?php
			if (ComponentHelper::getParams('com_akeebabackup')->get('push_preference') === 'webpush') {
				echo $this->loadAnyTemplate('Controlpanel/webpush');
			}
			?>

			<?php //  Basic operations ?>
			<?= $this->loadAnyTemplate('Controlpanel/icons_basic') ?>

			<?php //  Core Upgrade ?>
			<?= $this->loadAnyTemplate('Controlpanel/upgrade') ?>

			<?php //  Troubleshooting ?>
			<?= $this->loadAnyTemplate('Controlpanel/icons_troubleshooting') ?>

			<?php //  Advanced operations ?>
			<?= $this->loadAnyTemplate('Controlpanel/icons_advanced') ?>

			<?php //  Include / Exclude data ?>
			<?php if($this->permissions['configure']): ?>
				<?= $this->loadAnyTemplate('Controlpanel/icons_includeexclude') ?>
			<?php endif ?>
		</div>
		<?php //  RIGHT COLUMN (33% desktop width) ?>
		<div class="col-12 col-lg-4">
			<?php //  Status Summary ?>
			<?= $this->loadAnyTemplate('Controlpanel/sidebar_status') ?>

			<?php //  Backup stats ?>
			<?= $this->loadAnyTemplate('Controlpanel/sidebar_backup') ?>
		</div>
	</div>

	<div class="row">
		<div class="col">
			<?php //  Footer ?>
			<?= $this->loadAnyTemplate('Controlpanel/footer') ?>
		</div>
	</div>
</div>