<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
?>

<div class="row-fluid">
	<div class="filter-bar">
	</div>
	<h1><?php echo JText::_('COM_VOLUNTEERS_PAGETITLE_VOLUNTEERS') ?></h1>
</div>

<?php if(empty($this->items)) :?>
	<div class="row-fluid">
		<?php echo JText::_('COM_VOLUNTEERS_NOITEMS');?>
	</div>
<?php else: ?>
	<?php $chunks = array_chunk($this->items, 4);?>
	<?php foreach ($chunks as $row) : ?>
		<div class="row-fluid">
			<?php foreach ($row as $item) : ?>
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
			<?php endforeach; ?>
		</div>
	<?php endforeach; ?>
<?php endif; ?>

<div class="row-fluid">
	<div class="pagination">
		<?php echo $this->pagination->getPagesLinks(); ?>
	</div>
</div>
