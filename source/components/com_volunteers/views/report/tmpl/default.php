<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

$this->loadHelper('params');
$this->loadHelper('modules');
$this->loadHelper('format');
?>
<div class="row-fluid report">
	<div class="span2 volunteer-image">
		<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id='.$this->volunteer->volunteers_volunteer_id)?>">
			<?php echo VolunteersHelperFormat::image($this->volunteer->image, 'large'); ?>
		</a>
	</div>
	<div class="span10">
		<?php if($this->item->created_by == JFactory::getUser()->id):?>
		<a class="btn btn-small pull-right" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=report&task=edit&id='.$this->item->volunteers_report_id.'&group='.$this->group->volunteers_group_id)?>">
			<span class="icon-edit"></span>  <?php echo JText::_('COM_VOLUNTEERS_EDIT') ?>
		</a>
		<?php endif;?>
		<h1 class="report-title"><?php echo $this->item->title?></h1>
		<p class="muted">
			<?php echo JText::_('COM_VOLUNTEERS_BY') ?> <a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id='.$this->volunteer->volunteers_volunteer_id)?>"><?php echo($this->volunteer->firstname)?> <?php echo($this->volunteer->lastname)?></a>
			<?php echo JText::_('COM_VOLUNTEERS_ON') ?> <?php echo VolunteersHelperFormat::date($this->item->created_on,'Y-m-d H:i'); ?>
			<?php echo JText::_('COM_VOLUNTEERS_IN') ?> <a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=group&id='.$this->group->volunteers_group_id)?>"><?php echo($this->group->title)?></a>
		</p>
		<?php echo ($this->item->description)?>
	</div>
</div>