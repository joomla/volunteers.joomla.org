<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

$this->loadHelper('params');
$this->loadHelper('format');
$this->loadHelper('message');
$this->loadHelper('select');

// Get the Itemid
$itemId = FOFInput::getInt('Itemid',0,$this->input);
if($itemId != 0) {
	$actionURL = 'index.php?option=com_volunteers&view=groups&Itemid='.$itemId;
} else {
	$actionURL = 'index.php';
}

// Filters
$filter_title 		= $this->getModel()->getState('title','');
$order 				= $this->lists->order;

JHtml::_('bootstrap.tooltip');
?>

<div class="row-fluid">
	<div class="filter-bar">
		<div class="btn-group pull-right">
			<button class="btn btn-sm btn-default<?php if($order == 'title'):?> active<?php endif;?>" type="button" onclick="document.getElementById('filter_order').value='title';document.forms.volunteerspagination.submit();">
				<span class="glyphicon glyphicon-music"></span>
				<span class="text"><?php echo JText::_('COM_VOLUNTEERS_SORT_GROUPTITLE') ?></span>
			</button>

			<button class="btn btn-sm btn-default<?php if($order == 'random'):?> active<?php endif;?>" type="button" onclick="document.getElementById('filter_order').value='random';document.forms.volunteerspagination.submit();">
				<span class="glyphicon glyphicon-random"></span>
				<span class="text"><?php echo JText::_('COM_VOLUNTEERS_SORT_RANDOM') ?></span>
			</button>
		</div>
	</div>
	<h1><?php echo JText::_('COM_VOLUNTEERS_PAGETITLE_WORKING_GROUPS') ?></h1>
</div>

<?php if(!empty($this->items)) foreach($this->items as $i=>$item): ?>
<div class="row-fluid">
	<div class="group well group-<?php echo($item->volunteers_group_id);?>">
		<div class="row-fluid">
			<div class="span8">
				<h2 style="margin-top: 0;">
					<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=group&id='.$item->volunteers_group_id)?>">
						<?php echo($item->title);?><?php if($item->acronym):?> (<?php echo ($item->acronym)?>)<?php endif;?>
					</a>
				</h2>
				<p><?php echo($item->description);?></p>
				<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=group&id='.$item->volunteers_group_id)?>" class="btn">
					<?php echo JText::_('COM_VOLUNTEERS_MORE_ABOUT') ?> <?php echo($item->title);?>
				</a>
			</div>
			<div class="span4">
				<div class="groupmembers">
					<?php $i = 0;?>
					<?php if(!empty($this->groupmembers[$item->volunteers_group_id])) foreach($this->groupmembers[$item->volunteers_group_id] as $groupmember): ?>
					<a class="tip hasTooltip" title="<?php echo($groupmember->volunteer_firstname);?> <?php echo($groupmember->volunteer_lastname);?>" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id='.$groupmember->volunteers_volunteer_id)?>">
						<?php echo VolunteersHelperFormat::image($groupmember->volunteer_image, 'small'); ?>
					</a>
					<?php $i++; if($i == 11) { break; };?>
					<?php endforeach; ?>
					<?php
						$grouptotal = count($this->groupmembers[$item->volunteers_group_id]);
						if($grouptotal > 11):
					?>
						<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=group&id='.$item->volunteers_group_id)?>" class="all-groupmembers">
							<span class="all"><?php echo JText::_('COM_VOLUNTEERS_ALL') ?></span><span class="number"><?php echo($grouptotal);?></span>
						</a>
					<?php endif;?>
				</div>
			</div>
		</div>
	</div>
</div>
<?php endforeach; ?>

<?php if($order != 'random'):?>
<div class="row-fluid">
	<div class="pagination">
		<?php echo $this->pagination->getPagesLinks(); ?>
	</div>
</div>
<?php endif;?>

<form id="volunteers-pagination" name="volunteerspagination" action="<?php echo JRoute::_($actionURL); ?>" method="post">
	<input type="hidden" name="option" value="com_volunteers" />
	<input type="hidden" name="view" value="groups" />
	<input type="hidden" name="title" value="<?php echo $filter_title; ?>" id="title-filter" />
	<input type="hidden" name="filter_order" value="<?php echo $this->lists->order ?>" id="filter_order"/>
	<input type="hidden" name="filter_order_Dir" value="<?php echo $this->lists->order_Dir ?>" id="filter_order_Dir" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" id="token" />
</form>