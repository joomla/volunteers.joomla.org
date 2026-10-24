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
		<?= Text::_('COM_AKEEBABACKUP_CPANEL_HEADER_INCLUDEEXCLUDE') ?>
	</h3>

	<div class="card-body">

		<div class="alert alert-info small mt-0 mb-2">
			<span class="fa fa-fw fa-info-circle" aria-hidden="true"></span>
			<?= Text::_('COM_AKEEBABACKUP_CPANEL_LBL_INCLUDEEXCLUDE_CALLOUT') ?>
		</div>

		<div class="akeeba-cpanel-container d-flex flex-row flex-wrap align-items-stretch">
			<?php if(AKEEBABACKUP_PRO): ?>
				<a class="akeeba-cpanel-button text-center align-self-stretch btn btn-outline-success border-0" style="width: 10em"
				   href="index.php?option=com_akeebabackup&view=Multipledatabases">
					<div class="bg-success text-white d-block text-center p-3 h2">
						<span class="fa fa-database"></span>
					</div>
					<span>
						<?= Text::_('COM_AKEEBABACKUP_MULTIDB') ?>
					</span>
				</a>

				<a class="akeeba-cpanel-button text-center align-self-stretch btn btn-outline-success border-0" style="width: 10em"
				   href="index.php?option=com_akeebabackup&view=Includefolders">
					<div class="bg-success text-white d-block text-center p-3 h2">
						<span class="fa fa-folder-plus"></span>
					</div>
					<span>
						<?= Text::_('COM_AKEEBABACKUP_INCLUDEFOLDER') ?>
					</span>
				</a>
			<?php endif ?>

			<a class="akeeba-cpanel-button text-center align-self-stretch btn btn-outline-danger border-0" style="width: 10em"
			   href="index.php?option=com_akeebabackup&view=Databasefilters">
				<div class="bg-danger text-white d-block text-center p-3 h2">
					<span class="fa fa-table"></span>
				</div>
				<span>
					<?= Text::_('COM_AKEEBABACKUP_DBFILTER') ?>
				</span>
			</a>

			<a class="akeeba-cpanel-button text-center align-self-stretch btn btn-outline-danger border-0" style="width: 10em"
			   href="index.php?option=com_akeebabackup&view=Filefilters">
				<div class="bg-danger text-white d-block text-center p-3 h2">
					<span class="fa fa-folder-minus"></span>
				</div>
				<span>
					<?= Text::_('COM_AKEEBABACKUP_FILEFILTERS') ?>
				</span>
			</a>

			<?php if(AKEEBABACKUP_PRO): ?>
				<a class="akeeba-cpanel-button text-center align-self-stretch btn btn-outline-danger border-0" style="width: 10em"
				   href="index.php?option=com_akeebabackup&view=Regexdatabasefilters">
					<div class="bg-danger text-white d-block text-center p-3 h2">
						<span class="fa fa-clipboard"></span>
					</div>
					<span>
						<?= Text::_('COM_AKEEBABACKUP_REGEXDBFILTERS') ?>
					</span>
				</a>

				<a class="akeeba-cpanel-button text-center align-self-stretch btn btn-outline-danger border-0" style="width: 10em"
				   href="index.php?option=com_akeebabackup&view=Regexfilefilters">
					<div class="bg-danger text-white d-block text-center p-3 h2">
						<span class="fa fa-folder"></span>
					</div>
					<span>
						<?= Text::_('COM_AKEEBABACKUP_REGEXFSFILTERS') ?>
					</span>
				</a>
			<?php endif ?>

		</div>
	</div>
</div>
