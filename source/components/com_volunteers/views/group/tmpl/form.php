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
?>

<form name="adminForm" class="form form-horizontal" action="<?php echo $actionURL ?>" method="post" enctype="multipart/form-data">
	<div class="row-fluid">
		<h1 class="pull-left"><?php echo JText::_('COM_VOLUNTEERS_EDIT_GROUP')?></h1>
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
			<input type="hidden" name="view" value="group" />
			<input type="hidden" name="task" value="save" />
			<input type="hidden" name="volunteers_group_id" value="<?php echo $this->item->volunteers_group_id ?>" />
			<input type="hidden" name="Itemid" value="<?php echo $itemId ?>" />
			<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />
			<input type="hidden" name="enabled" value="<?php echo($this->item->enabled); ?>" />

			<!-- Start row -->
			<div class="row-fluid">
				<!-- Start left -->
				<div class="span12">
					<hr>
					<div class="control-group">
						<label for="title" class="control-label">
							<?php echo JText::_('COM_VOLUNTEERS_FIELD_NAME')?>
						</label>
						<div class="controls">
							<input type="text" name="title" id="title" class="span" value="<?php echo $this->item->title?>"/>
						</div>
					</div>
					<div class="control-group">
						<label for="title" class="control-label">
							<?php echo JText::_('COM_VOLUNTEERS_FIELD_SLUG')?>
						</label>
						<div class="controls">
							<input type="text" name="slug" id="slug" class="span" value="<?php echo $this->item->slug?>"/>
						</div>
					</div>
					<hr>
					<div class="control-group">
						<label for="acronym" class="control-label">
							<?php echo JText::_('COM_VOLUNTEERS_FIELD_ACRONYM')?>
						</label>
						<div class="controls">
							<input type="text" name="acronym" id="acronym" class="span" value="<?php echo $this->item->acronym?>"/>
						</div>
					</div>
					<div class="control-group">
						<label for="title" class="control-label">
							<?php echo JText::_('COM_VOLUNTEERS_FIELD_WEBSITE')?>
						</label>
						<div class="controls">
							<input type="text" name="website" id="website" class="span" value="<?php echo $this->item->website?>"/>
						</div>
					</div>
					<div class="control-group">
						<label for="created_on" class="control-label">
							<?php echo JText::_('COM_VOLUNTEERS_FIELD_DATE_STARTED'); ?>
						</label>
						<div class="controls">
							<?php echo JHTML::_('calendar', $this->item->date_started, 'date_started', 'date_started'); ?>
						</div>
					</div>
					<div class="control-group">
						<label for="created_on" class="control-label">
							<?php echo JText::_('COM_VOLUNTEERS_FIELD_DATE_ENDED'); ?>
						</label>
						<div class="controls">
							<?php echo JHTML::_('calendar', $this->item->date_ended, 'date_ended', 'date_ended'); ?>
						</div>
					</div>
					<hr>
					<div class="control-group">
						<label for="description" class="control-label">
							<?php echo JText::_('COM_VOLUNTEERS_FIELD_DESCRIPTION')?>
						</label>
						<div class="controls">
							<textarea name="description" id="description" rows="4" class="span"><?php echo $this->item->description?></textarea>
						</div>
					</div>
					<div class="control-group">
						<label for="getinvolved" class="control-label">
							<?php echo JText::_('Get Involved')?>
						</label>
						<div class="controls">
							<?php echo $editor->display( 'getinvolved',  $this->item->getinvolved, '100%', '250', '50', '10', false ) ; ?>
						</div>
					</div>
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