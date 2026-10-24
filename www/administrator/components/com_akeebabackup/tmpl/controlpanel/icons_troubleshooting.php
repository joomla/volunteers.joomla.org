<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/** @var $this \Akeeba\Component\AkeebaBackup\Administrator\View\Controlpanel\HtmlView */

// Protect from unauthorized access
defined('_JEXEC') || die();

use Joomla\CMS\Language\Text;

?>
<div class="card mb-2">
	<h3 class="card-header">
		<?= Text::_('COM_AKEEBABACKUP_CPANEL_HEADER_TROUBLESHOOTING')?>
	</h3>

	<div class="card-body">
		<div class="akeeba-cpanel-container d-flex flex-row flex-wrap align-items-stretch">
			<?php if($this->permissions['backup']): ?>
				<a class="akeeba-cpanel-button text-center align-self-stretch btn btn-outline-primary border-0" style="width: 10em"
				   href="index.php?option=com_akeebabackup&view=Log">
					<div class="bg-primary text-white d-block text-center p-3 h2">
						<span class="fa fa-search"></span>
					</div>
					<span>
						<?= Text::_('COM_AKEEBABACKUP_LOG') ?>
					</span>
				</a>
			<?php endif ?>

			<?php if(AKEEBABACKUP_PRO && $this->permissions['configure']): ?>
				<a class="akeeba-cpanel-button text-center align-self-stretch btn btn-outline-primary border-0" style="width: 10em"
				   href="index.php?option=com_akeebabackup&view=Alice">
					<div class="bg-primary text-white d-block text-center p-3 h2">
						<span class="fa fa-briefcase-medical"></span>
					</div>
					<span>
						<?= Text::_('COM_AKEEBABACKUP_ALICE') ?>
					</span>
				</a>
			<?php endif ?>
		</div>
	</div>
</div>
