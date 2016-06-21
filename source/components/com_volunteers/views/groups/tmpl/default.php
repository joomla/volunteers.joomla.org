<?php 
/*
* @package		Joomla! Volunteers
* @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
* @license     GNU General Public License version 2 or later; see LICENSE.txt
*/

// No direct access.
defined('_JEXEC') or die;

$compParams = $this->get('ComponentConfiguration');

;?>
<div class="row-fluid">
	<div class="filter-bar">

	</div>
	<h1><?php echo JText::_('COM_VOLUNTEERS_PAGETITLE_WORKING_GROUPS') ?></h1>
</div>

<?php if(trim($compParams->get('group_pretext')) != '' ) :?>
	<div class="row-fluid">
		<?php echo $compParams->get('group_pretext');?>
	</div>
<?php endif; ?>

<?php if(empty($this->items)) :?>
	<div class="row-fluid">
		<?php echo JText::_('COM_VOLUNTEERS_NOITEMS');?>
	</div>
<?php else: ?>
	<?php foreach ($this->items as $item) :?>
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
							<?php if(empty($item->members)) :?>
								no groupmembers
							<?php else: ?>
								<?php $membersCount = count($item->members); ?>
								<?php for($i = 0; $i < 12 && $i < $membersCount;$i++) :?>
									<?php $member = $item->members[$i];?>
									<a class="tip hasTooltip" title="<?php echo($member->firstname);?> <?php echo($member->lastname);?>" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id='.$member->volunteers_volunteer_id)?>">
										<?php echo VolunteersHelperFormat::image($member->image, 'small'); ?>
									</a>
								<?php endfor; ?>
								<?php if($membersCount > 11) :?>
									<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=group&id='.$item->volunteers_group_id)?>" class="all-groupmembers">
										<span class="all"><?php echo JText::_('COM_VOLUNTEERS_ALL') ?></span><span class="number"><?php echo($membersCount);?></span>
									</a>
								<?php endif; ?>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php endforeach;?>
	<div class="row-fluid">
		<div class="pagination">
			<?php echo $this->pagination->getPagesLinks(); ?>
		</div>
	</div>
<?php endif; ?>