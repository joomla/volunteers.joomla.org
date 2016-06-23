<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
?>
<h1>
	<?php echo $this->escape($this->item->title);?>
</h1>

<div class="row-fluid">
	<?php echo $this->item->description;?>
</div>

<h2>
	<?php echo JText::_('COM_VOLUNTEERS_DEPARTMENT_LEADERSHIP');?>
</h2>
<?php if(! is_null($this->item->teamlead)) :?>
	<?php $leader = $this->item->teamlead;?>
	<div class="row-fluid">
		<div class="span3 volunteer-image">
			<?php echo VolunteersHelperFormat::image($leader->image, 'large'); ?>
		</div>
		<div class="span9">
			<h3>
				<?php echo JText::_('COM_VOLUNTEERS_DEPARTMENT_LEADER');?>
				<?php echo $leader->firstname?> <?php echo $leader->lastname?>
			</h3>
			<?php if($leader->city || $leader->country):?>
				<p class="muted"><span class="icon-location"></span> <?php echo VolunteersHelperFormat::location($leader->city, $leader->country); ?></p>
			<?php endif;?>
			<p class="lead"><?php echo ($leader->intro)?></p>
		</div>
	</div>
	<hr />
<?php endif; ?>
<?php if(! is_null($this->item->teamassistant1)) :?>
	<?php $leader = $this->item->teamassistant1;?>
		<div class="row-fluid">
			<div class="span3 volunteer-image">
				<?php echo VolunteersHelperFormat::image($leader->image, 'large'); ?>
			</div>
			<div class="span9">
				<h3>
					<?php echo JText::_('COM_VOLUNTEERS_DEPARTMENT_ASSISTANTLEADER');?>
					<?php echo $leader->firstname?> <?php echo $leader->lastname?>
				</h3>
				<?php if($leader->city || $leader->country):?>
					<p class="muted"><span class="icon-location"></span> <?php echo VolunteersHelperFormat::location($leader->city, $leader->country); ?></p>
				<?php endif;?>
				<p class="lead"><?php echo ($leader->intro)?></p>
			</div>
		</div>
	<hr />
<?php endif; ?>
<?php if(! is_null($this->item->teamassistant2)) :?>
	<?php $leader = $this->item->teamassistant2;?>
	<div class="row-fluid">
		<div class="span3 volunteer-image">
			<?php echo VolunteersHelperFormat::image($leader->image, 'large'); ?>
		</div>
		<div class="span9">
			<h3>
				<?php echo JText::_('COM_VOLUNTEERS_DEPARTMENT_ASSISTANTLEADER');?>
				<?php echo $leader->firstname?> <?php echo $leader->lastname?>
			</h3>
			<?php if($leader->city || $leader->country):?>
				<p class="muted"><span class="icon-location"></span> <?php echo VolunteersHelperFormat::location($leader->city, $leader->country); ?></p>
			<?php endif;?>
			<p class="lead"><?php echo ($leader->intro)?></p>
		</div>
	</div>
	<hr />
<?php endif; ?>

<?php if($this->item->groups):?>
<div class="row-fluid">
	<h2>
		<?php echo JText::_('COM_VOLUNTEERS_DEPARTMENT_GROUPS');?>
	</h2>
	<ul>
	<?php foreach($this->item->groups as $group):?>
		<li>
			<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=group&id='.$group->volunteers_group_id)?>">
				<?php echo $group->title;?>
			</a>
		</li>
	<?php endforeach;?>
	</ul>
</div>
<?php endif;?>
