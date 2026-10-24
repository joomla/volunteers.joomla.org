<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die();

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri as JUri;

/** @var  \Akeeba\Component\AkeebaBackup\Administrator\View\Log\HtmlView  $this */
?>

<?php if(isset($this->logs) && count($this->logs)): ?>
<form name="adminForm" id="adminForm" method="post"
	  action="<?= Route::_('index.php?option=com_akeebabackup&view=Log') ?>"
	class="card card-body d-flex flex-column gap-2">
    <div class="row row-cols-lg-auto gap-1 align-items-center">
		<?php if(empty($this->tag)): ?>
		<div class="col">
			<label for="tag"><?= Text::_('COM_AKEEBABACKUP_LOG_CHOOSE_FILE_TITLE')?></label>
		</div>
		<?php endif ?>
		<div class="col flex-grow-1">
			<?php if(!empty($this->tag)): ?>
				<label for="tag" class="visually-hidden"><?= Text::_('COM_AKEEBABACKUP_LOG_CHOOSE_FILE_TITLE')?></label>
			<?php endif ?>
		    <?= HTMLHelper::_('select.genericlist', $this->logs, 'tag', [
			    'list.select' => $this->tag,
			    'list.attr'   => [
				    'class'    => 'advancedSelect form-select w-100',
				    'onchange' => 'document.forms.adminForm.submit();',
			    ], 'id'       => 'comAkeebaLogTagSelector',
		    ]) ?>
		</div>
	    <?php if(!empty($this->tag)): ?>
			<div class="col flex-shrink-1">
				<a class="btn btn-primary" href="<?= $this->escape(JUri::base()) ?>index.php?option=com_akeebabackup&view=Log&task=download&tag=<?= $this->escape($this->tag) ?>">
					<span class="fa fa-download"></span>
				    <?= Text::_('COM_AKEEBABACKUP_LOG_LABEL_DOWNLOAD') ?>
				</a>
			</div>

			<?php if ($this->hasAlice): ?>
			<div class="col flex-shrink-1">
				<a class="btn btn-outline-success" href="<?= $this->escape(JUri::base()) ?>index.php?option=com_akeebabackup&view=Alice&log=<?= $this->escape($this->tag) ?>&task=start&<?= \Joomla\CMS\Factory::getApplication()->getFormToken() ?>=1">
					<span class="fa fa-diagnoses"></span>
					<?= Text::_('COM_AKEEBABACKUP_ALICE_ANALYZE') ?>
				</a>
			</div>
			<?php endif ?>
	    <?php endif ?>
	</div>

	<?= HTMLHelper::_('form.token') ?>
</form>
<?php endif ?>

<?php if(!empty($this->tag)): ?>
    <?php if ($this->logTooBig): ?>
        <div class="alert alert-warning">
            <p>
                <?= Text::sprintf('COM_AKEEBABACKUP_LOG_SIZE_WARNING', number_format($this->logSize / (1024 * 1024), 2)) ?>
            </p>
            <a class="btn btn-dark" id="showlog" href="#">
                <?= Text::_('COM_AKEEBABACKUP_LOG_SHOW_LOG') ?>
            </a>
        </div>
    <?php endif ?>

    <div id="iframe-holder" class="border p-0"
		 style="display: <?= $this->logTooBig ? 'none' : 'block' ?>;">
		<?php if(!$this->logTooBig): ?>
            <iframe
                src="index.php?option=com_akeebabackup&view=Log&task=iframe&format=raw&tag=<?= urlencode($this->tag) ?>"
                width="99%" height="400px">
            </iframe>
		<?php endif ?>
    </div>
<?php endif ?>

<?php if( ! (isset($this->logs) && count($this->logs))): ?>
<div class="alert alert-danger">
	<?= Text::_('COM_AKEEBABACKUP_LOG_NONE_FOUND') ?>
</div>
<?php endif ?>