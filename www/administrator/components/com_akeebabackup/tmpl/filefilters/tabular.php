<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die();

use Joomla\CMS\Language\Text;

/** @var \Akeeba\Component\AkeebaBackup\Administrator\View\Filefilters\HtmlView $this */

echo $this->loadAnyTemplate('commontemplates/errormodal');
echo $this->loadAnyTemplate('commontemplates/profilename');
?>

<div class="border row row-cols-lg-auto g-3 align-items-center my-3 mx-1 pb-3">
	<div class="col-12">
		<label>
			<?= Text::_('COM_AKEEBABACKUP_FILEFILTERS_LABEL_ROOTDIR') ?>
		</label>
	</div>
	<div class="col-12">
		<span><?= $this->root_select ?></span>
	</div>
	<div class="col-12">
		<label>
			<?= Text::_('COM_AKEEBABACKUP_FILEFILTERS_LABEL_ADDNEWFILTER') ?>
		</label>
	</div>
	<div class="col-12">
		<button type="button"
				class="btn btn-dark" id="comAkeebaFilefiltersAddDirectories">
			<?= Text::_('COM_AKEEBABACKUP_FILEFILTERS_TYPE_DIRECTORIES') ?>
		</button>
	</div>
	<div class="col-12">
		<button type="button"
				class="btn btn-dark" id="comAkeebaFilefiltersAddSkipfiles">
			<?= Text::_('COM_AKEEBABACKUP_FILEFILTERS_TYPE_SKIPFILES') ?>
		</button>
	</div>
	<div class="col-12">
		<button type="button"
				class="btn btn-dark" id="comAkeebaFilefiltersAddSkipdirs">
			<?= Text::_('COM_AKEEBABACKUP_FILEFILTERS_TYPE_SKIPDIRS') ?>
		</button>
	</div>
	<div class="col-12">
		<button type="button"
				class="btn btn-dark" id="comAkeebaFilefiltersAddFiles">
			<?= Text::_('COM_AKEEBABACKUP_FILEFILTERS_TYPE_FILES') ?>
		</button>
	</div>
</div>

<div id="ak_list_container">
	<table id="ak_list_table" class="table table-striped">
		<thead>
		<tr>
			<th class="w-25">
				<?= Text::_('COM_AKEEBABACKUP_FILEFILTERS_LABEL_TYPE') ?>
			</th>
			<th>
				<?= Text::_('COM_AKEEBABACKUP_FILEFILTERS_LABEL_FILTERITEM') ?>
			</th>
		</tr>
		</thead>
		<tbody id="ak_list_contents">
		</tbody>
	</table>
</div>
