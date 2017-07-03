<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

$user      = JFactory::getUser();
$userId    = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$canOrder  = $user->authorise('core.edit.state', 'com_volunteers');
$saveOrder = $listOrder == 'a.ordering';

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_volunteers&task=members.saveOrderAjax&tmpl=component';
	JHtml::_('sortablelist.sortable', 'itemsList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
?>

<form action="<?php echo JRoute::_('index.php?option=com_volunteers&view=members'); ?>" method="post" name="adminForm" id="adminForm">
	<?php if (!empty($this->sidebar)) : ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
		<?php else : ?>
		<div id="j-main-container">
			<?php endif; ?>
			<?php echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
			<?php if (empty($this->items)) : ?>
				<div class="alert alert-no-items">
					<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
				</div>
			<?php else : ?>
				<table class="table table-striped" id="itemsList">
					<thead>
					<tr>
						<th width="1%" class="nowrap center hidden-phone">
							<?php echo JHtml::_('searchtools.sort', '', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
						</th>
						<th width="1%" class="hidden-phone center">
							<?php echo JHtml::_('grid.checkall'); ?>
						</th>
						<th width="1%" style="min-width:55px" class="nowrap center">
							<?php echo JHtml::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
						</th>
						<th width="8%"></th>
						<th width="15%" class="team">
							<?php echo JHtml::_('searchtools.sort', 'COM_VOLUNTEERS_FIELD_TEAM_DEPARTMENT', 'a.team', $listDirn, $listOrder); ?>
						</th>
						<th width="15%" class="name">
							<?php echo JHtml::_('searchtools.sort', 'COM_VOLUNTEERS_FIELD_VOLUNTEER', 'a.volunteer', $listDirn, $listOrder); ?>
						</th>
						<th>
							<?php echo JHtml::_('searchtools.sort', 'COM_VOLUNTEERS_FIELD_POSITION', 'a.position', $listDirn, $listOrder); ?>
						</th>
						<th>
							<?php echo JHtml::_('searchtools.sort', 'COM_VOLUNTEERS_FIELD_ROLE', 'a.role', $listDirn, $listOrder); ?>
						</th>
						<th>
							<?php echo JHtml::_('searchtools.sort', 'COM_VOLUNTEERS_FIELD_DATE_STARTED', 'a.date_started', $listDirn, $listOrder); ?>
						</th>
						<th>
							<?php echo JHtml::_('searchtools.sort', 'COM_VOLUNTEERS_FIELD_DATE_ENDED', 'a.date_ended', $listDirn, $listOrder); ?>
						</th>
						<th width="1%" class="nowrap center hidden-phone">
							<?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
						</th>
					</tr>
					</thead>
					<tfoot>
					<tr>
						<td colspan="10">
							<?php echo $this->pagination->getListFooter(); ?>
						</td>
					</tr>
					</tfoot>
					<tbody>
					<?php foreach ($this->items as $i => $item) :
						$ordering = ($listOrder == 'a.ordering');
						$canCreate = $user->authorise('core.create', 'com_volunteers');
						$canEdit = $user->authorise('core.edit', 'com_volunteers');
						$canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $user->get('id') || $item->checked_out == 0;
						$canChange = $user->authorise('core.edit.state', 'com_volunteers') && $canCheckin;
						?>
						<tr class="row<?php echo $i % 2; ?><?php if($item->date_ended == '0000-00-00'):?> success<?php else:?> error<?php endif;?>"  sortable-volunteer-id="<?php echo $item->id ?>">
							<td class="order nowrap center hidden-phone">
								<?php
								$iconClass = '';
								if (!$canChange)
								{
									$iconClass = ' inactive';
								}
								elseif (!$saveOrder)
								{
									$iconClass = ' inactive tip-top hasTooltip" title="' . JHtml::tooltipText('JORDERINGDISABLED');
								}
								?>
								<span class="sortable-handler<?php echo $iconClass ?>">
								<span class="icon-menu"></span>
							</span>
								<?php if ($canChange && $saveOrder) : ?>
									<input type="text" style="display:none" name="order[]" size="5" value="<?php echo $item->ordering; ?>" class="width-20 text-area-order "/>
								<?php endif; ?>
							</td>
							<td class="center hidden-phone">
								<?php echo JHtml::_('grid.id', $i, $item->id); ?>
							</td>
							<td class="center">
								<?php echo JHtml::_('jgrid.published', $item->state, $i, 'members.', $canChange, 'cb'); ?>
							</td>
							<td>
								<a class="btn btn-small" href="<?php echo JRoute::_('index.php?option=com_volunteers&task=member.edit&id=' . (int) $item->id); ?>">
									<span class="icon-edit"></span>Edit
								</a>
							</td>
							<td class="nowrap">
								<?php if ($canEdit && $item->department) : ?>
									<a href="<?php echo JRoute::_('index.php?option=com_volunteers&task=department.edit&id=' . (int) $item->department); ?>">
										<?php echo $this->escape($item->department_title); ?>
									</a>
								<?php elseif ($item->department) : ?>
									<?php echo $this->escape($item->department_title); ?>
								<?php endif; ?>
								<?php if ($canEdit && $item->team) : ?>
									<a href="<?php echo JRoute::_('index.php?option=com_volunteers&task=team.edit&id=' . (int) $item->team); ?>">
										<?php echo $this->escape($item->team_title); ?>
									</a>
								<?php elseif ($item->team) : ?>
									<?php echo $this->escape($item->team_title); ?>
								<?php endif; ?>
							</td>
							<td class="nowrap">
								<?php if ($item->checked_out) : ?>
									<?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'members.', $canCheckin); ?>
								<?php endif; ?>
								<?php if ($canEdit) : ?>
									<a href="<?php echo JRoute::_('index.php?option=com_volunteers&task=volunteer.edit&id=' . (int) $item->volunteer); ?>">
										<?php echo $this->escape($item->volunteer_name); ?>
									</a>
								<?php else : ?>
									<?php echo $this->escape($item->volunteer_name); ?>
								<?php endif; ?>
							</td>
							<td class="nowrap">
								<?php echo $item->position_title; ?>
							</td>
							<td>
								<?php echo $this->escape($item->role_title); ?>
							</td>
							<td>
								<?php echo $item->date_started; ?>
							</td>
							<td>
								<?php echo $item->date_ended; ?>
							</td>
							<td class="center hidden-phone">
								<?php echo (int) $item->id; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<input type="hidden" name="task" value=""/>
			<input type="hidden" name="boxchecked" value="0"/>
			<?php echo JHtml::_('form.token'); ?>
		</div>
</form>
