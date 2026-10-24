<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') || die();

use Joomla\CMS\Language\Text as JText;

/** @var  \Akeeba\Component\AkeebaBackup\Administrator\View\Remotefiles\HtmlView $this */

// Is the engine incapable of any action?
$noCapabilities = !$this->capabilities['delete']
	&& !$this->capabilities['downloadToFile']
	&& !$this->capabilities['downloadToBrowser'];

// Are all remote files no longer present?
$downloadToFileNotAvailable = !$this->actions['downloadToFile'] && $this->capabilities['downloadToFile'];
$deleteNotAvailable = !$this->actions['delete'] && $this->capabilities['delete'];
$allRemoteFilesGone = $downloadToFileNotAvailable && $deleteNotAvailable;

?>
<div class="card d-none text-center mb-3" id="akeebaBackupRemoteFilesWorkInProgress">
	<h3 class="card-header"><?= JText::_('COM_AKEEBABACKUP_REMOTEFILES_INPROGRESS_HEADER') ?></h3>
	<div class="card-body">
		<?= \Joomla\CMS\HTML\HTMLHelper::_('image',
			\Joomla\CMS\Uri\Uri::root() . 'media/com_akeebabackup/icons/spinner.gif',
			JText::_('COM_AKEEBABACKUP_REMOTEFILES_INPROGRESS_LBL_PLEASEWAIT')
		) ?>

		<p>
			<?= JText::_('COM_AKEEBABACKUP_REMOTEFILES_INPROGRESS_LBL_UNDERWAY')?>
		</p>
		<p>
			<?= JText::_('COM_AKEEBABACKUP_REMOTEFILES_INPROGRESS_LBL_WAITINGINFO')?>
		</p>
	</div>
</div>

<div id="akeebaBackupRemoteFilesMainInterface">
    <div class="card mb-3">
		<h3 class="card-header bg-primary text-white"><?= JText::_('COM_AKEEBABACKUP_REMOTEFILES')?></h3>
		<div class="card-body">
			<?php // ===== No capabilities ===== ?>
			<?php if($noCapabilities): ?>
				<div class="alert alert-danger">
					<h3>
						<?= JText::_('COM_AKEEBABACKUP_REMOTEFILES_ERR_NOTSUPPORTED_HEADER')?>
					</h3>
					<p>
						<?= JText::_('COM_AKEEBABACKUP_REMOTEFILES_ERR_NOTSUPPORTED')?>
					</p>
				</div>
				<?php // ===== Remote files gone, no operations available ===== ?>
			<?php elseif($deleteNotAvailable): ?>
				<div class="alert alert-danger">
					<h3>
						<?= JText::_('COM_AKEEBABACKUP_REMOTEFILES_ERR_NOTSUPPORTED_HEADER')?>
					</h3>
					<p>
						<?= JText::_('COM_AKEEBABACKUP_REMOTEFILES_ERR_NOTSUPPORTED_ALREADYONSERVER')?>
					</p>
				</div>
			<?php else: ?>
				<?php if($this->actions['downloadToFile']): ?>
					<a class="btn btn-primary text-decoration-none akeebaRemoteFilesShowWait"
					   href="index.php?option=com_akeebabackup&view=Remotefiles&task=dltoserver&tmpl=component&id=<?= $this->id ?>&part=-1&<?= \Joomla\CMS\Factory::getApplication()->getFormToken() ?>=1"
					>
						<span class="fa fa-cloud-download-alt"></span>
						<span><?= JText::_('COM_AKEEBABACKUP_REMOTEFILES_FETCH')?></span>
					</a>
				<?php else: ?>
					<button type="button"
							class="btn btn-primary"
							disabled="disabled"
							title="<?= JText::_($this->capabilities['downloadToFile'] ? 'COM_AKEEBABACKUP_REMOTEFILES_ERR_DOWNLOADEDTOFILE_ALREADY' : 'COM_AKEEBABACKUP_REMOTEFILES_ERR_UNSUPPORTED')?>">
						<span class="fa fa-cloud-download-alt"></span>
						<span><?= JText::_('COM_AKEEBABACKUP_REMOTEFILES_FETCH')?></span>
					</button>
				<?php endif; ?>

				<?php if($this->actions['delete']): ?>
					<a class="btn btn-danger text-decoration-none akeebaRemoteFilesShowWait"
					   href="index.php?option=com_akeebabackup&view=Remotefiles&task=delete&tmpl=component&id=<?= $this->id ?>&part=-1&<?= \Joomla\CMS\Factory::getApplication()->getFormToken() ?>=1"
					>
						<span class="fa fa-trash"></span>
						<span><?= JText::_('COM_AKEEBABACKUP_REMOTEFILES_DELETE')?></span>
					</a>
				<?php else: ?>
					<button type="button"
							class="btn btn-primary" disabled="disabled"
							title="<?= JText::_($this->capabilities['delete'] ? 'COM_AKEEBABACKUP_REMOTEFILES_ERR_DELETE_ALREADY' : 'COM_AKEEBABACKUP_REMOTEFILES_ERR_UNSUPPORTED')?>">
						<span class="fa fa-trash"></span>
						<span><?= JText::_('COM_AKEEBABACKUP_REMOTEFILES_DELETE')?></span>
					</button>
				<?php endif ?>
			<?php endif ?>
		</div>
    </div>

	<?php if($this->actions['downloadToBrowser'] != 0): ?>
		<div class="card mb-3">
			<h3 class="card-header bg-info text-white">
				<?= JText::_('COM_AKEEBABACKUP_REMOTEFILES_LBL_DOWNLOADLOCALLY')?>
			</h3>

			<div class="card-body">
				<?php for($part = 0; $part < $this->actions['downloadToBrowser']; $part++): ?>
					<a href="index.php?option=com_akeebabackup&view=Remotefiles&task=dlfromremote&id=<?= $this->id ?>&part=<?= $part ?>&<?= \Joomla\CMS\Factory::getApplication()->getFormToken() ?>=1"
					   class="btn btn-sm btn-secondary">
						<span class="fa fa-file-download"></span>
						<?= JText::sprintf('COM_AKEEBABACKUP_REMOTEFILES_PART', $part) ?>
					</a>
				<?php endfor ?>
			</div>
		</div>
	<?php endif ?>
</div>