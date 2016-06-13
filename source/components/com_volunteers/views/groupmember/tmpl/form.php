<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

$this->loadHelper('params');
$this->loadHelper('select');
$this->loadHelper('format');

// Joomla! editor object
$editor = JFactory::getEditor();

// Get the Itemid
$itemId = FOFInput::getInt('Itemid',0,$this->input);
if($itemId != 0) {
	$actionURL = 'index.php?Itemid='.$itemId;
} else {
	$actionURL = 'index.php';
}

// Default group role
if($this->item->role)
{
	$role = $this->item->role;
}
else
{
	$role = 1;
}
// Default date
if($this->item->date_started)
{
	$date_started = $this->item->date_started;
}
else
{
	$date_started = date('Y');
}

// Get group
$group = $this->item->volunteers_group_id;

if(!isset($group))
{
	$group 	= JFactory::getApplication()->input->get('group', 0);
}
$task 	= JFactory::getApplication()->input->get('task', '');
?>

<form name="adminForm" class="form form-horizontal" action="<?php echo $actionURL ?>" method="post" enctype="multipart/form-data">
	<div class="row-fluid">
		<h1 class="pull-left"><?php echo JText::_('COM_VOLUNTEERS_EDIT_GROUPMEMBER')?></h1>
		<div class="btn-toolbar pull-right">
			<div id="toolbar-cancel" class="btn-group">
				<a class="btn btn-small btn-danger" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=group&id='.$this->item->volunteers_group_id)?>">
					<span class="icon-cancel"></span> <?php echo JText::_('JCANCEL')?>
				</a>
			</div>
			<div id="toolbar-apply" class="btn-group">
				<button class="btn btn-small btn-success" type="submit">
					<span class="icon-pencil"></span> <?php echo JText::_('JSAVE')?>
				</button>
			</div>
		</div>
	</div>

	<div class="row-fluid">
		<div class="span12">
			<input type="hidden" name="option" value="com_volunteers" />
			<input type="hidden" name="view" value="groupmember" />
			<input type="hidden" name="task" value="save" />
			<input type="hidden" name="volunteers_group_id" value="<?php echo $group ?>" />
			<input type="hidden" name="volunteers_groupmember_id" value="<?php echo $this->item->volunteers_groupmember_id ?>" />
			<input type="hidden" name="Itemid" value="<?php echo $itemId ?>" />
			<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />
			<input type="hidden" name="enabled" value="<?php echo($this->item->enabled); ?>" />

			<!-- Start row -->
			<div class="row-fluid">
				<!-- Start left -->
				<div class="span12">
					<hr>
					<div class="control-group">
						<label for="funxvotes_artist_id" class="control-label">
							<?php echo JText::_('COM_VOLUNTEERS_FIELD_GROUP'); ?>
						</label>
						<div class="controls">
							<?php echo VolunteersHelperSelect::group($group, 'volunteers_group_id', array('class' => 'chosen', 'disabled' => 'disabled')); ?>
						</div>
					</div>
					<div class="control-group">
						<label for="funxvotes_artist_id" class="control-label">
							<?php echo JText::_('COM_VOLUNTEERS_FIELD_NAME'); ?>
						</label>
						<div class="controls">
							<?php if($task == 'edit'):?>
								<?php echo VolunteersHelperSelect::volunteer($this->item->volunteers_volunteer_id, $group, 'volunteers_volunteer_id', array('class' => 'chosen', 'disabled' => 'disabled')); ?>
							<?php else:?>
								<?php echo VolunteersHelperSelect::volunteer($this->item->volunteers_volunteer_id, $group); ?>
							<?php endif;?>
						</div>
					</div>
					<?php if($this->roles->lead || $this->roles->liaison):?>
					<div class="control-group">
						<label for="funxvotes_artist_id" class="control-label">
							<?php echo JText::_('COM_VOLUNTEERS_FIELD_ROLE'); ?>
						</label>
						<div class="controls">
							<?php echo VolunteersHelperSelect::role($role); ?>
						</div>
					</div>
					<div class="control-group">
						<label for="title" class="control-label">
							<?php echo JText::_('COM_VOLUNTEERS_FIELD_POSITION')?>
						</label>
						<div class="controls">
							<input type="text" name="position" id="position" class="span" value="<?php echo $this->item->position?>"/>
						</div>
					</div>
					<?php endif;?>
					<hr>
					<div class="control-group">
						<label for="created_on" class="control-label">
							<?php echo JText::_('COM_VOLUNTEERS_FIELD_DATE_STARTED'); ?>
						</label>
						<div class="controls">
							<?php echo JHTML::_('calendar', $date_started, 'date_started', 'date_started'); ?>
						</div>
					</div>
					<div class="control-group">
						<label for="created_on" class="control-label">
							<?php echo JText::_('COM_VOLUNTEERS_FIELD_DATE_ENDED'); ?>
						</label>
						<div class="controls">
							<div class="alert alert-info">
								<?php echo JText::_('COM_VOLUNTEERS_FIELD_DATE_ENDED_DESC') ?>
							</div>
							<?php echo JHTML::_('calendar', $this->item->date_ended, 'date_ended', 'date_ended'); ?>
						</div>
					</div>
					<hr>
				</div>
			</div>
		</div>
	</div>

	<div class="row-fluid">
		<div class="btn-toolbar pull-right">
			<div id="toolbar-cancel" class="btn-group">
				<a class="btn btn-small btn-danger" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=group&id='.$this->item->volunteers_group_id)?>">
					<span class="icon-cancel"></span> <?php echo JText::_('JCANCEL')?>
				</a>
			</div>
			<div id="toolbar-apply" class="btn-group">
				<button class="btn btn-small btn-success" type="submit">
					<span class="icon-pencil"></span> <?php echo JText::_('JSAVE')?>
				</button>
			</div>
		</div>
	</div>
</form>