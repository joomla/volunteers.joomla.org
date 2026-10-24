<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die();

use Joomla\CMS\Language\Text;

/** @var \Akeeba\Component\AkeebaBackup\Administrator\View\Includefolders\HtmlView $this */

echo $this->loadAnyTemplate('commontemplates/errormodal');
echo $this->loadAnyTemplate('commontemplates/profilename');
echo $this->loadAnyTemplate('commontemplates/folderbrowser');

?>
<div class="card">
	<div id="ak_list_container" class="card-body">
		<table id="ak_list_table" class="table table-striped">
			<thead>
			<tr>
				<!-- Delete -->
				<td>&nbsp;</td>
				<!-- Edit -->
				<td>&nbsp;</td>
				<!-- Directory path -->
				<th scope="col">
						<span rel="popover" title="<?= Text::_('COM_AKEEBABACKUP_INCLUDEFOLDER_LABEL_DIRECTORY') ?>"
							  data-bs-content="<?= Text::_('COM_AKEEBABACKUP_INCLUDEFOLDER_LABEL_DIRECTORY_HELP') ?>">
							<?= Text::_('COM_AKEEBABACKUP_INCLUDEFOLDER_LABEL_DIRECTORY') ?>
						</span>
				</th>
				<!-- Directory path -->
				<th scope="col">
						<span rel="popover" title="<?= Text::_('COM_AKEEBABACKUP_INCLUDEFOLDER_LABEL_VINCLUDEDIR') ?>"
							  data-bs-content="<?= Text::_('COM_AKEEBABACKUP_INCLUDEFOLDER_LABEL_VINCLUDEDIR_HELP') ?>">
							<?= Text::_('COM_AKEEBABACKUP_INCLUDEFOLDER_LABEL_VINCLUDEDIR') ?>
						</span>
				</th>
			</tr>
			</thead>
			<tbody id="ak_list_contents">
			</tbody>
		</table>
	</div>
</div>
