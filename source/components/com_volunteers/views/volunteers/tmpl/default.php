<?php
/*
 * @package		FunX Awards
 * @copyright	Copyright (c) 2014 Perfect Web Team / perfectwebteam.nl
 * @license		GNU General Public License version 3 or later
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
	$actionURL = 'index.php?option=com_volunteers&view=volunteers&Itemid='.$itemId;
} else {
	$actionURL = 'index.php';
}

// Filters
$filter_title 		= $this->getModel()->getState('title','');
$order 				= $this->lists->order;
?>

<div class="row-fluid">
	<div class="filter-bar">
		<div class="btn-group pull-right">
			<button class="btn btn-sm btn-default<?php if($order == 'firstname'):?> active<?php endif;?>" type="button" onclick="document.getElementById('filter_order').value='firstname';document.forms.volunteerspagination.submit();">
				<span class="glyphicon glyphicon-music"></span>
				<span class="text"><?php echo JText::_('COM_VOLUNTEERS_SORT_FIRSTNAME') ?></span>
			</button>

			<button class="btn btn-sm btn-default<?php if($order == 'random'):?> active<?php endif;?>" type="button" onclick="document.getElementById('filter_order').value='random';document.forms.volunteerspagination.submit();">
				<span class="glyphicon glyphicon-random"></span>
				<span class="text"><?php echo JText::_('COM_VOLUNTEERS_SORT_RANDOM') ?></span>
			</button>
		</div>
	</div>
	<h1><?php echo JText::_('COM_VOLUNTEERS_PAGETITLE_VOLUNTEERS') ?></h1>
</div>

<div class="row-fluid">
<?php $i = 0;?>
<?php if(!empty($this->items)) foreach($this->items as $item): ?>
	<div class="span3">
		<div class="well well-small">
			<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id='.$item->volunteers_volunteer_id)?>">
				<?php echo VolunteersHelperFormat::image($item->image, 'large'); ?>
			</a>
			<h4 class="text-center">
				<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id='.$item->volunteers_volunteer_id)?>">
					<?php echo($item->firstname);?> <?php echo($item->lastname);?>
				</a>
			</h4>
		</div>
	</div>
	<?php $i++;?>
	<?php if($i == 4):?>
	</div>
	<div class="row-fluid">
	<?php $i = 0;?>
	<?php endif;?>
<?php endforeach; ?>
</div>

<?php if($order != 'random'):?>
<div class="row-fluid">
	<div class="pagination">
		<?php echo $this->pagination->getPagesLinks(); ?>
	</div>
</div>
<?php endif;?>

<form id="volunteers-pagination" name="volunteerspagination" action="<?php echo JRoute::_($actionURL); ?>" method="post">
	<input type="hidden" name="option" value="com_volunteers" />
	<input type="hidden" name="view" value="volunteers" />
	<input type="hidden" name="title" value="<?php echo $filter_title; ?>" id="title-filter" />
	<input type="hidden" name="filter_order" value="<?php echo $this->lists->order ?>" id="filter_order"/>
	<input type="hidden" name="filter_order_Dir" value="<?php echo $this->lists->order_Dir ?>" id="filter_order_Dir" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" id="token" />
</form>