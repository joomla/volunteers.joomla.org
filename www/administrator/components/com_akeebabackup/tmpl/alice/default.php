<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var  \Akeeba\Component\AkeebaBackup\Administrator\View\Alice\HtmlView $this */
?>
<?php if (empty($this->logs)): ?>
	<div class="alert alert-danger">
		<p>
			<?= Text::_('COM_AKEEBABACKUP_ALICE_ERR_NOLOGS') ?>
		</p>
	</div>
<?php else: ?>
	<?php if($this->autorun): ?>
		<div class="alert alert-warning">
			<p>
				<?= Text::_('COM_AKEEBABACKUP_ALICE_AUTORUN_NOTICE') ?>
			</p>
		</div>
	<?php endif ?>

	<form name="adminForm" id="adminForm"
		  action="<?= Route::_('index.php?option=com_akeebabackup&view=Alice&task=start') ?>"
		  method="post"
		  class="row row-cols-lg-auto g-3 align-items-center">

		<div class="col-12">
			<label for="tag">
				<?= Text::_('COM_AKEEBABACKUP_LOG_CHOOSE_FILE_TITLE') ?>
			</label>
		</div>

		<div class="col-12">
			<?= HTMLHelper::_('select.genericlist', $this->logs, 'log', [
				'list.attr' => [
					'class' => 'form-select',
				]
			], 'value', 'text', $this->log) ?>
		</div>

		<div class="col-12">
			<button type="submit"
					class="btn btn-primary" id="analyze-log">
				<span class="fa fa-diagnoses"></span>
				<?= Text::_('COM_AKEEBABACKUP_ALICE_ANALYZE') ?>
			</button>
		</div>

		<?= HTMLHelper::_('form.token') ?>
	</form>
<?php endif ?>

<div class="alert alert-info">
	<h2><?= Text::_('COM_AKEEBABACKUP_ALICE_HEAD_ONLYFAILED') ?></h2>
	<p>
		<?= Text::_('COM_AKEEBABACKUP_ALICE_LBL_ONLYFAILED_SHOWINGLOGS') ?>
	</p>
	<p>
		<?= Text::_('COM_AKEEBABACKUP_ALICE_LBL_ONLYFAILED_WHATISFAILED') ?>
	</p>
	<p>
		<?= Text::_('COM_AKEEBABACKUP_ALICE_LBL_ONLYFAILED_IFNOFAILED') ?>
	</p>
</div>
