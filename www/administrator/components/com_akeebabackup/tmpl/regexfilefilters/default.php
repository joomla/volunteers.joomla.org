<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die();

use Joomla\CMS\Language\Text;

/** @var \Akeeba\Component\AkeebaBackup\Administrator\View\Regexfilefilters\HtmlView $this */

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

</div>

<div id="ak_list_container">
	<table id="ak_list_table" class="table table-striped">
		<thead>
		<tr>
			<td style="width: 8rem"></td>
			<th class="w-25" scope="col">
				<?= Text::_('COM_AKEEBABACKUP_FILEFILTERS_LABEL_TYPE') ?>
			</th>
			<th scope="col">
				<?= Text::_('COM_AKEEBABACKUP_FILEFILTERS_LABEL_FILTERITEM') ?>
			</th>
		</tr>
		</thead>
		<tbody id="ak_list_contents">
		</tbody>
	</table>
</div>