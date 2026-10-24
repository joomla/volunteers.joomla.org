<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die();

/** @var \Akeeba\Component\AkeebaBackup\Administrator\View\Multipledatabases\HtmlView $this */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

?>
<div id="akEditorDialog"
	 class="modal fade"
	 tabindex="-1"
	 role="dialog"
	 aria-labelledby="akEditorDialogLabel"
	 aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="akEditorDialogLabel" class="modal-title">
                    <?= Text::_('COM_AKEEBABACKUP_FILEFILTERS_EDITOR_TITLE') ?>
					<button type="button" class="btn-close novalidate" data-bs-dismiss="modal"
							aria-label="<?= Text::_('JLIB_HTML_BEHAVIOR_CLOSE') ?>"></button>
                </h3>
            </div>

            <div id="akEditorDialogBody" class="modal-body p-3">
				<div id="ak_editor_table">
					<div class="row mb-3">
						<label class="col-xs-3 col-form-label" for="ake_driver">
							<?= Text::_('COM_AKEEBABACKUP_MULTIDB_GUI_LBL_DRIVER')?>
						</label>
						<div class="col-xs-9">
							<?= HTMLHelper::_('select.genericlist', [
								'mysqli'   => 'MySQLi',
								'pdomysql' => 'PDO MySQL',
							], 'ake_driver', [
								'list.attr' => [
									'class' => 'form-select',
								],
							], 'value', 'text', null, 'ake_driver') ?>
						</div>
					</div>

					<div class="row mb-3">
						<label class="col-xs-3 col-form-label" for="ake_host">
							<?= Text::_('COM_AKEEBABACKUP_MULTIDB_GUI_LBL_HOST') ?>
						</label>
						<div class="col-xs-9">
							<input class="form-control" id="ake_host" type="text" />
						</div>
					</div>

					<div class="row mb-3">
						<label class="col-xs-3 col-form-label" for="ake_port">
							<?= Text::_('COM_AKEEBABACKUP_MULTIDB_GUI_LBL_PORT') ?>
						</label>
						<div class="col-xs-9">
							<input id="ake_port" type="text" class="form-control" />
						</div>
					</div>

					<div class="row mb-3">
						<label class="col-xs-3 col-form-label" for="ake_username">
							<?= Text::_('COM_AKEEBABACKUP_MULTIDB_GUI_LBL_USERNAME')  ?>
						</label>
						<div class="col-xs-9">
							<input id="ake_username" type="text" class="form-control" />
						</div>
					</div>

					<div class="row mb-3">
						<label class="col-xs-3 col-form-label" for="ake_password">
							<?= Text::_('COM_AKEEBABACKUP_MULTIDB_GUI_LBL_PASSWORD') ?>
						</label>
						<div class="col-xs-9">
							<input id="ake_password" type="password" class="form-control" />
						</div>
					</div>

					<div class="row mb-3">
						<label class="col-xs-3 col-form-label" for="ake_database">
							<?= Text::_('COM_AKEEBABACKUP_MULTIDB_GUI_LBL_DATABASE') ?>
						</label>
						<div class="col-xs-9">
							<input id="ake_database" type="text" class="form-control" />
						</div>
					</div>

					<div class="row mb-3">
						<label class="col-xs-3 col-form-label" for="ake_prefix">
							<?= Text::_('COM_AKEEBABACKUP_MULTIDB_GUI_LBL_PREFIX') ?>
						</label>
						<div class="col-xs-9">
							<input id="ake_prefix" type="text" class="form-control" />
						</div>
					</div>
				</div>
            </div>
			<div class="modal-footer">
				<div class="row mb-3">
					<div class="col">
						<button type="button"
								class="btn btn-dark text-dark" id="akEditorBtnDefault">
							<span class="fa fa-heartbeat"></span>
							<?= Text::_('COM_AKEEBABACKUP_MULTIDB_GUI_LBL_TEST') ?>
						</button>

						<button type="button"
								class="btn btn-success" id="akEditorBtnSave">
							<span class="fa fa-check"></span>
							<?= Text::_('COM_AKEEBABACKUP_MULTIDB_GUI_LBL_SAVE') ?>
						</button>

						<button type="button"
								class="btn btn-danger" id="akEditorBtnCancel">
							<span class="fa fa-times-circle"></span>
							<?= Text::_('COM_AKEEBABACKUP_MULTIDB_GUI_LBL_CANCEL') ?>
						</button>
					</div>
				</div>
			</div>
        </div>
    </div>
</div>

<?php
echo $this->loadAnyTemplate('commontemplates/errormodal');
echo $this->loadAnyTemplate('commontemplates/profilename');
?>

<div class="card">
    <div id="ak_list_container" class="card-body">
        <table id="ak_list_table" class="table table-striped">
            <thead>
            <tr>
                <td style="width: 40px">&nbsp;</td>
                <td style="width: 40px">&nbsp;</td>
                <th>
					<?= Text::_('COM_AKEEBABACKUP_MULTIDB_LABEL_HOST') ?>
				</th>
                <th>
					<?= Text::_('COM_AKEEBABACKUP_MULTIDB_LABEL_DATABASE') ?>
				</th>
            </tr>
            </thead>
            <tbody id="ak_list_contents">
            </tbody>
        </table>
    </div>
</div>
