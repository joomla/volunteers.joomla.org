	<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') || die();

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/** @var  \Akeeba\Component\AkeebaBackup\Administrator\View\Manage\HtmlView $this */

/**
 * HTMLHelper's `behavior.multiselect` is deprecated in Joomla 6.
 *
 * See Joomla PR 45925.
 */
call_user_func(function(string $formName = 'adminForm') {
	if (version_compare(JVERSION, '5.999.999', 'lt'))
	{
		HTMLHelper::_('behavior.multiselect');

		return;
	}

	$doc       = \Joomla\CMS\Factory::getApplication()->getDocument();
	$doc->addScriptOptions('js-multiselect', ['formName' => $formName]);
	$doc->getWebAssetManager()->useScript('multiselect');
});

HTMLHelper::_('bootstrap.popover', '.akeebaCommentPopover', [
		'trigger' => 'click hover focus'
]);
HTMLHelper::_('bootstrap.tooltip', '.akeebaTooltip');

$this->tableColumnsAutohide();
$this->tableColumnsMultiselect('#articleList');

if ($this->promptForBackupRestoration)
{
	echo $this->loadAnyTemplate('howtorestore_modal');
}

?>
<div id="akeebabackup-manage-iframe-modal"
	 class="modal fade"
	 tabindex="-1"
	 role="dialog"
	 aria-labelledby="akeebabackup-manage-iframe-modal-title"
	 aria-hidden="true"
>
	<div class="modal-dialog modal-lg modal-dialog-scrollable">
		<div class="modal-content">
			<div class="modal-header">
				<h3 class="modal-title" id="akeebabackup-manage-iframe-modal-title">
				</h3>
				<button type="button" class="btn-close novalidate"
						id="akeebabackup-manage-iframe-modal-close"
						data-bs-dismiss="modal"
						aria-label="<?= Text::_('JLIB_HTML_BEHAVIOR_CLOSE') ?>"></button>
			</div>
			<div class="modal-body p-3" id="akeebabackup-manage-iframe-modal-content"></div>
		</div>
	</div>
</div>


<div class="alert alert-info">
	<h3><?= Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_HOWDOIRESTORE_LEGEND') ?></h3>
	<p>
		<?= Text::sprintf('COM_AKEEBABACKUP_BUADMIN_LABEL_HOWDOIRESTORE_TEXT_' . (AKEEBABACKUP_PRO ? 'PRO' : 'CORE'), 'http://akee.ba/abrestoreanywhere', 'index.php?option=com_akeebabackup&view=Transfer', 'https://www.akeeba.com/latest-kickstart-core.zip') ?>
	</p>
	<?php if (!AKEEBABACKUP_PRO): ?>
		<p>
			<?= Text::sprintf('COM_AKEEBABACKUP_BUADMIN_LABEL_HOWDOIRESTORE_TEXT_CORE_INFO_ABOUT_PRO', 'https://www.akeeba.com/products/akeeba-backup.html') ?>
		</p>
	<?php endif ?>
</div>


<form action="<?= Route::_('index.php?option=com_akeebabackup&view=Manage'); ?>"
	  method="post" name="adminForm" id="adminForm">
	<div class="row">
		<div class="col-md-12">
			<div id="j-main-container" class="j-main-container">
				<?= LayoutHelper::render('joomla.searchtools.default', ['view' => $this]) ?>
				<?php if (empty($this->items)) : ?>
					<div class="alert alert-info">
						<span class="icon-info-circle" aria-hidden="true"></span><span
								class="visually-hidden"><?= Text::_('INFO'); ?></span>
						<?= Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
					</div>
				<?php else : ?>
					<table class="table" id="articleList">
						<caption class="visually-hidden">
							<?= Text::_('COM_AKEEBABACKUP_BUADMIN_TABLE_CAPTION'); ?>, <span
									id="orderedBy"><?= Text::_('JGLOBAL_SORTED_BY'); ?> </span>, <span
									id="filteredBy"><?= Text::_('JGLOBAL_FILTERED_BY'); ?></span>
						</caption>
						<thead>
						<tr>
							<td class="w-1 text-center">
								<?= HTMLHelper::_('grid.checkall'); ?>
							</td>
							<th scope="col" class="text-center d-none d-md-table-cell" style="max-width: 48px;">
								<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_ID') ?>
							</th>
							<th scope="col" class="text-center" style="max-width: 40px">
								<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_FROZEN') ?>
							</th>
							<th scope="col" class="text-center">
								<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_DESCRIPTION') ?>
							</th>
							<th scope="col" class="text-center d-none d-md-table-cell">
								<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_PROFILEID') ?>
							</th>
							<th scope="col" class="text-center" style="max-width: 40px;">
								<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_STATUS') ?>
							</th>
							<th scope="col" class="text-center d-none d-sm-table-cell">
								<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_MANAGEANDDL') ?>
							</th>
						</tr>
						</thead>
						<tbody>
						<?php foreach ($this->items as $i => $record) : ?>
							<?php
							[$originDescription, $originIcon] = $this->getOriginInformation($record);
							[$startTime, $duration, $timeZoneText] = $this->getTimeInformation($record);
							[$statusClass, $statusIcon] = $this->getStatusInformation($record);
							$profileName = $this->getProfileName($record);
                            $comment     = $this->escapeComment($record['comment']);

							$frozenIcon  = 'akion-waterdrop';
							$frozenTask  = 'freeze';
							$frozenTitle = Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_ACTION_FREEZE');

							if ($record['frozen'])
							{
								$frozenIcon  = 'akion-ios-snowy';
								$frozenTask  = 'unfreeze';
								$frozenTitle = Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_ACTION_UNFREEZE');
							}
							?>
							<tr class="row<?= $i % 2; ?>">
								<?php // Checkbox ?>
								<td class="text-center">
									<?= HTMLHelper::_('grid.id', $i, $record['id'], false, 'cid', 'cb', $record['id']); ?>
								</td>
								<?php // Backup ID ?>
								<td class="d-none d-md-table-cell">
									<?= $record['id'] ?>
								</td>
								<?php // Frozen ?>
								<td>
									<?php // $frozenIcon ?>
									<?= HTMLHelper::_('jgrid.state', $this->getFrozenStates(), $record['frozen'], $i, array('prefix' => 'Manage.', 'translate' => true), true, true, 'cb'); ?>
								</td>
								<?php // Description, backup date, duration and size ?>
								<td>
									<span class="<?= $originIcon ?> akeebaCommentPopover" rel="popover"
									  title="<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_ORIGIN') ?>"
									  data-bs-content="<?= $originDescription ?>"></span>
									<?php if (!(empty($comment))): ?>
										<span class="fa fa-info-circle akeebaCommentPopover" rel="popover"
											  title="<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_COMMENT') ?>"
											  data-bs-content="<?= $comment ?>"></span>
									<?php endif ?>
									<a href="<?= Uri::base() ?>index.php?option=com_akeebabackup&view=Statistic&layout=edit&id=<?= $record['id'] ?>">
										<?= empty($record['description'])
											? Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_NODESCRIPTION')
											: $this->escape($record['description']) ?>
									</a>
									<div class="row">
										<span class="col-lg akeeba-buadmin-startdate">
												<span class="fa fa-calendar akeebaTooltip"
													  title="<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_START') ?>"
												></span>&nbsp;
												<?= $startTime ?> <?= $timeZoneText ?>
										</span>

										<span class="col-lg akeeba-buadmin-duration">
											<span class="fa fa-stopwatch akeebaTooltip"
												  title="<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_DURATION') ?>"
											></span>&nbsp;
											<?= $duration ?: '&mdash;' ?>
										</span>

										<span class="col-lg akeeba-buadmin-size">
											<span class="fa fa-weight-hanging akeebaTooltip"
												  title="<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_SIZE') ?>"
											></span>&nbsp;
											<?php if ($record['meta'] == 'ok'): ?>
												<?= $this->formatFilesize($record['size']) ?>
											<?php elseif ($record['total_size'] > 0): ?>
												<i><?= $this->formatFilesize($record['total_size']) ?></i>
											<?php else: ?>
												&mdash;
											<?php endif ?>
										</span>
									</div>
									<div class="row d-block d-md-none">
										<div class="col-md">
											<span class="fa fa-users"></span>&nbsp;
										 	#<?= (int) $record['profile_id'] ?>.
											<?= $profileName == '&mdash;' ? $profileName : $this->escape($profileName) ?>
										</div>
										<div class="col-md">
											<span class="fa fa-align-justify"></span>&nbsp;
											<span class="fs-6 fst-italic">
												<?= $this->translateBackupType($record['type']) ?>
											</span>
										</div>
									</div>
								</td>
								<?php // Backup profile ?>
								<td class="d-none d-md-table-cell">
									<div>
										 #<?= (int) $record['profile_id'] ?>.
										<?= $profileName == '&mdash;' ? $profileName : $this->escape($profileName) ?>
									</div>
									<div>
										<span class="fs-6 fst-italic">
											<?= $this->translateBackupType($record['type']) ?>
										</span>
									</div>
								</td>
								<?php // Status ?>
								<td>
								<span class="badge fs-3 rounded-pill w-100 <?= $statusClass ?> akeebaTooltip"
									  rel="popover"
									  title="<?= Text::_('COM_AKEEBABACKUP_BUADMIN_LABEL_STATUS_' . $record['meta']) ?>"
								>
									&nbsp;<span class="<?= $statusIcon ?>"></span>&nbsp;
								</span>
								</td>
								<?php // Manage & Download ?>
								<td class="d-none d-sm-table-cell">
									<?= $this->loadAnyTemplate('manage_column', false, ['record' => &$record]); ?>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>

					<?php // Load the pagination. ?>
					<?= $this->pagination->getListFooter(); ?>
				<?php endif; ?>

				<input type="hidden" name="task" value=""> <input type="hidden" name="boxchecked" value="0">
				<?= HTMLHelper::_('form.token'); ?>
			</div>
		</div>
	</div>
</form>