<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

// Load the helpers
$this->loadHelper('params');
$this->loadHelper('select');
$this->loadHelper('format');

// Sorting filters
$sortFields = array(
	'enabled' 				=> JText::_('JPUBLISHED'),
	'title'					=> JText::_('COM_VOLUNTEERS_FIELD_NAME'),
	'event'					=> JText::_('COM_VOLUNTEERS_FIELD_EVENT'),
	'modified_on'			=> JText::_('JGLOBAL_FIELD_MODIFIED_LABEL'),
);

//JHtml::_('bootstrap.tooltip');
JHtml::_('bootstrap.popover');
?>

<?php if (version_compare(JVERSION, '3.0', 'ge')): ?>
	<script type="text/javascript">
		Joomla.orderTable = function() {
			table = document.getElementById("sortTable");
			direction = document.getElementById("directionTable");
			order = table.options[table.selectedIndex].value;
			if (!order)
			{
				dirn = 'asc';
			}
			else {
				dirn = direction.options[direction.selectedIndex].value;
			}
			Joomla.tableOrdering(order, dirn);
		}
	</script>
<?php endif; ?>

<div class="volunteers">
	<form name="adminForm" id="adminForm" action="index.php" method="post">
		<input type="hidden" name="option" id="option" value="com_volunteers" />
		<input type="hidden" name="view" id="view" value="volunteers" />
		<input type="hidden" name="task" id="task" value="browse" />
		<input type="hidden" name="boxchecked" id="boxchecked" value="0" />
		<input type="hidden" name="hidemainmenu" id="hidemainmenu" value="0" />
		<input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->lists->order ?>" />
		<input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->lists->order_Dir ?>" />
		<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />

		<?php if(version_compare(JVERSION, '3.0', 'gt')): ?>
		<div id="filter-bar" class="btn-toolbar">
			<div class="btn-group pull-right hidden-phone">
				<label for="limit" class="element-invisible"><?php echo JText::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC') ?></label>
				<?php echo $this->getModel()->getPagination()->getLimitBox(); ?>
			</div>
			<?php
			$asc_sel	= ($this->getLists()->order_Dir == 'asc') ? 'selected="selected"' : '';
			$desc_sel	= ($this->getLists()->order_Dir == 'desc') ? 'selected="selected"' : '';
			?>
			<div class="btn-group pull-right hidden-phone">
				<label for="directionTable" class="element-invisible"><?php echo JText::_('JFIELD_ORDERING_DESC') ?></label>
				<select name="directionTable" id="directionTable" class="input-medium" onchange="Joomla.orderTable()">
					<option value=""><?php echo JText::_('JFIELD_ORDERING_DESC') ?></option>
					<option value="asc" <?php echo $asc_sel ?>><?php echo JText::_('JGLOBAL_ORDER_ASCENDING') ?></option>
					<option value="desc" <?php echo $desc_sel ?>><?php echo JText::_('JGLOBAL_ORDER_DESCENDING') ?></option>
				</select>
			</div>
			<div class="btn-group pull-right">
				<label for="sortTable" class="element-invisible"><?php echo JText::_('JGLOBAL_SORT_BY') ?></label>
				<select name="sortTable" id="sortTable" class="input-medium" onchange="Joomla.orderTable()">
					<option value=""><?php echo JText::_('JGLOBAL_SORT_BY') ?></option>
					<?php echo JHtml::_('select.options', $sortFields, 'value', 'text', $this->getLists()->order) ?>
				</select>
			</div>
		</div>
		<div class="clearfix"> </div>
		<?php endif; ?>

		<table class="adminlist table table-striped">
			<thead>
				<tr>
					<th width="20">
						<input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $this->items ) + 1; ?>);" />
					</th>
					<th width="20">
						<?php echo JHTML::_('grid.sort', 'COM_VOLUNTEERS_FIELD_STATUS', 'enabled', $this->lists->order_Dir, $this->lists->order); ?>
					</th>
					<th colspan="2">
						<?php echo JHTML::_('grid.sort', 'COM_VOLUNTEERS_FIELD_NAME', 'firstname', $this->lists->order_Dir, $this->lists->order) ?>
					</th>
					<th></th>
					<th class="center" width="7%">
						<?php echo JText::_('COM_VOLUNTEERS_FIELD_BIO') ?>
					</th>
					<th width="7%" class="nowrap">
						<?php echo JHtml::_('grid.sort', 'JGLOBAL_FIELD_MODIFIED_LABEL', 'modified_on', $this->lists->order_Dir, $this->lists->order, 'browse') ?>
					</th>
				</tr>
				<tr>
					<td></td>
					<td class="center">
						<?php echo VolunteersHelperFormat::enabled($this->escape($this->getModel()->getState('enabled',''))); ?>
					</td>
					<td colspan="2">
						<?php echo VolunteersHelperFormat::search($this->escape($this->getModel()->getState('firstname',''))); ?>
					</td>
					<td></td>
					<td></td>
					<td></td>
				</tr>
			</thead>

			<tfoot>
				<tr>
					<td colspan="20">
						<?php if($this->pagination->total > 0) echo $this->pagination->getListFooter() ?>
					</td>
				</tr>
			</tfoot>

			<tbody>
			<?php if($count = count($this->items)): ?>
			<?php $i = -1; $m = 1; ?>
				<?php foreach ($this->items as $item) : ?>
				<?php
					$i++; $m = 1-$m;
					$checkedOut = ($item->locked_by != 0);
					$ordering = $this->lists->order == 'ordering';
					$item->published = $item->enabled;
				?>
				<tr class="<?php echo 'row'.$m; ?>">
					<td>
						<?php echo JHTML::_('grid.id', $i, $item->volunteers_volunteer_id, $checkedOut); ?>
					</td>
					<td class="center">
						<?php echo JHTML::_('jgrid.published', $item->enabled, $i); ?>
					</td>
					<td align="center" width="60">
						<div class="pull-left">
						<?php if($item->image): ?>
							<img width="50" class="thumbnail" src="<?php echo JURI::root().$item->image?>">
						<?php else:?>
							<img width="50" class="thumbnail" src="http://www.placehold.it/50x50/EFEFEF/AAAAAA&text=no+image" />
						<?php endif;?>
						</div>
					</td>
					<td align="left">
						<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id='.$item->volunteers_volunteer_id) ?>" class="volunteersitem">
							<strong><?php echo $this->escape($item->firstname) ?> <?php echo $this->escape($item->lastname) ?></strong>
						</a><br/>
						<?php echo JText::_('JGLOBAL_USERNAME') ?>: <a href="<?php echo JRoute::_('index.php?option=com_users&task=user.edit&id='.$item->user_id)?>"><?php echo JFactory::getUser($item->user_id)->username ?></a>
					</td>
					<td class="left">
						<?php if(VolunteersHelperParams::getParam('twitter',0) && $item->twitter): ?>
							<a class="btn" target="_blank" href="http://twitter.com/<?php echo $this->escape($item->twitter) ?>"><span class="icon volunteers-twitter"></span></a>
						<?php endif;?>
						<?php if(VolunteersHelperParams::getParam('facebook',0) && $item->facebook): ?>
							<a class="btn" target="_blank" href="http://facebook.com/<?php echo $this->escape($item->facebook) ?>"><span class="icon volunteers-facebook"></span></a>
						<?php endif;?>
						<?php if(VolunteersHelperParams::getParam('googleplus',0) && $item->googleplus): ?>
							<a class="btn" target="_blank" href="http://plus.google.com/<?php echo $this->escape($item->googleplus) ?>"><span class="icon volunteers-google-plus"></span></a>
						<?php endif;?>
						<?php if(VolunteersHelperParams::getParam('linkedin',0) && $item->linkedin): ?>
							<a class="btn" target="_blank" href="http://linkedin.com/<?php echo $this->escape($item->linkedin) ?>"><span class="icon volunteers-linkedin"></span></a>
						<?php endif;?>
						<?php if(VolunteersHelperParams::getParam('website',0) && $item->website): ?>
							<a class="btn" target="_blank" href="http://<?php echo $this->escape($item->website) ?>"><span class="icon volunteers-earth"></span></a>
						<?php endif;?>
					</td>

					<td class="nowrap">
						<?php echo($item->email); ?>
					</td>

					<td class="nowrap">
						<?php echo($item->country); ?>
					</td>

					<td class="nowrap">
						<?php echo($item->intro); ?>
					</td>

					<td class="center">
						<?php if($item->intro): ?>
						<span class="badge badge-success"><i class="icon-checkmark"></i></span>
						<?php else:?>
						<span class="badge badge-important"><i class="icon-delete"></i></span>
						<?php endif;?>
					</td>

					<td class="nowrap">
						<?php if($item->modified_on == '0000-00-00 00:00:00'): ?>
							&mdash;
						<?php else: ?>
							<?php echo JHtml::_('date',$item->modified_on, JText::_('DATE_FORMAT_LC4')); ?>
						<?php endif; ?>
					</td>
				</tr>
				<?php endforeach; ?>
			<?php else: ?>
				<tr>
					<td colspan="20">
						<?php echo  JText::_('COM_VOLUNTEERS_NORECORDS') ?>
					</td>
				</tr>
			<?php endif; ?>
			</tbody>
		</table>
	</form>
</div>