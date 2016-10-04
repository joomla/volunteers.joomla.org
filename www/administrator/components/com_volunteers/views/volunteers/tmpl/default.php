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
	$saveOrderingUrl = 'index.php?option=com_volunteers&task=volunteers.saveOrderAjax&tmpl=component';
	JHtml::_('sortablelist.sortable', 'itemsList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
?>
<style>
	.volunteer-image img {
		width: 75px;
		height: 75px;
	}
</style>

<form action="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteers'); ?>" method="post" name="adminForm" id="adminForm">
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
						<th width="75" class="nowrap"></th>
						<th width="15%" class="volunteer">
							<?php echo JHtml::_('searchtools.sort', 'COM_VOLUNTEERS_FIELD_VOLUNTEER', 'a.name', $listDirn, $listOrder); ?>
						</th>
						<th></th>
						<th width="6%" class="nowrap hidden-phone">
							<?php echo JHtml::_('searchtools.sort', 'COM_VOLUNTEERS_FIELD_TEAMS_NUM', 'num_teams', $listDirn, $listOrder); ?>
						</th>
						<th width="6%" class="nowrap hidden-phone">
							<?php echo JHtml::_('searchtools.sort', 'COM_VOLUNTEERS_FIELD_SPAM', 'a.spam', $listDirn, $listOrder); ?>
						</th>
						<th width="8%" class="nowrap hidden-phone">
							<?php echo JHtml::_('searchtools.sort', 'JGLOBAL_CREATED_DATE', 'a.created', $listDirn, $listOrder); ?>
						</th>
						<th width="8%" class="nowrap hidden-phone">
							<?php echo JHtml::_('searchtools.sort', 'JGLOBAL_MODIFIED_DATE', 'a.modified', $listDirn, $listOrder); ?>
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
						<tr class="row<?php echo $i % 2; ?>" sortable-volunteer-id="<?php echo $item->id ?>">
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
								<?php echo JHtml::_('jgrid.published', $item->state, $i, 'volunteers.', $canChange, 'cb'); ?>
							</td>
							<td class="volunteer-image">
								<?php if ($item->image): ?>
									<img class="img-rounded" src="<?php echo JURI::root() . 'images/volunteers/' . $item->image; ?>">
								<?php else: ?>
									<img class="img-rounded" src="<?php echo JURI::root() . 'images/joomla.png'; ?>">
								<?php endif; ?>
							</td>
							<td class="nowrap">
								<?php if ($item->checked_out) : ?>
									<?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'volunteers.', $canCheckin); ?>
								<?php endif; ?>
								<?php if ($canEdit) : ?>
									<a href="<?php echo JRoute::_('index.php?option=com_volunteers&task=volunteer.edit&id=' . (int) $item->id); ?>">
										<?php echo $this->escape($item->name); ?></a>
								<?php else : ?>
									<?php echo $this->escape($item->name); ?>
								<?php endif; ?>
								<div class="small">
									<span class="icon-user"></span>
									<?php if ($canEdit) : ?>
										<a href="<?php echo JRoute::_('index.php?option=com_users&task=user.edit&id=' . (int) $item->user_id); ?>">
											<?php echo $this->escape($item->user_username); ?></a>
									<?php else : ?>
										<?php echo $this->escape($item->user_username); ?>
									<?php endif; ?>
								</div>
								<div class="small">
									<span class="icon-mail"></span>
									<?php echo $this->escape($item->user_email); ?>
								</div>
								<div class="small">
									<span class="icon-location"></span>
									<?php echo $item->city; ?>,
									<?php if ($item->country): ?>
										<?php echo JHtmlVolunteers::$countries[$item->country]; ?>
									<?php else: ?>
										<?php echo $item->country; ?>
									<?php endif; ?>
								</div>
								<div class="small">
									<span class="icon-calendar-3"></span>
									<?php echo $item->birthday; ?>
								</div>
							</td>
							<td>
								<?php echo JHtml::_('string.truncate', $item->intro, 250); ?>
							</td>
							<td class="center">
								<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=members&filter[volunteer]=' . $item->id); ?>"><?php echo $item->num_teams; ?></a>
							</td>
							<td class="center">
								<?php echo $item->spam; ?>
							</td>
							<td class="nowrap small hidden-phone">
								<?php echo JHtml::_('date', $item->created, JText::_('DATE_FORMAT_LC4')); ?>
							</td>
							<td class="nowrap small hidden-phone">
								<?php echo JHtml::_('date', $item->modified, JText::_('DATE_FORMAT_LC4')); ?>
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
