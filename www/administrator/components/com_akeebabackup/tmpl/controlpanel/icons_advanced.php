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

// All of the buttons in this panel require the Configure privilege
if (!$this->permissions['configure'])
{
	return;
}

if (!AKEEBABACKUP_PRO)
{
	return;
}
?>
<div class="card mb-2">
	<h3 class="card-header">
		<?= Text::_('COM_AKEEBABACKUP_CPANEL_HEADER_ADVANCED') ?>
	</h3>

	<div class="card-body">
		<div class="akeeba-cpanel-container d-flex flex-row flex-wrap align-items-stretch">
			<?php /* The Schedule page echoes the front-end/JSON API secrets, so it is only available to users who are
			         allowed to edit the component's Options page (core.admin). */ ?>
			<?php if($this->permissions['editoptions']): ?>
				<a class="akeeba-cpanel-button text-center align-self-stretch btn btn-outline-primary border-0" style="width: 10em"
				   href="index.php?option=com_akeebabackup&view=Schedule">
					<div class="bg-primary text-white d-block text-center p-3 h2">
						<span class="fa fa-calendar"></span>
					</div>
					<span>
					<?= Text::_('COM_AKEEBABACKUP_SCHEDULE') ?>
				</span>
				</a>
			<?php endif ?>

			<?php if($this->permissions['configure']): ?>
				<a class="akeeba-cpanel-button text-center align-self-stretch btn btn-outline-warning text-dark border-0" style="width: 10em"
				   href="index.php?option=com_akeebabackup&view=Discover">
					<div class="bg-warning d-block text-center p-3 h2">
						<span class="fa fa-file-import"></span>
					</div>
					<span>
						<?= Text::_('COM_AKEEBABACKUP_DISCOVER') ?>
					</span>
				</a>
			<?php endif ?>

			<?php if($this->permissions['configure']): ?>
				<a class="akeeba-cpanel-button text-center align-self-stretch btn btn-outline-warning text-dark border-0" style="width: 10em"
				   href="index.php?option=com_akeebabackup&view=S3import">
					<div class="bg-warning d-block text-center p-3 h2">
						<span class="fa fa-cloud-download-alt"></span>
					</div>
					<span>
					<?= Text::_('COM_AKEEBABACKUP_S3IMPORT') ?>
				</span>
				</a>
			<?php endif ?>
		</div>
	</div>
</div>