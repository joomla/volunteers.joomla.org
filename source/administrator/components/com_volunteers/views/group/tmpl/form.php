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

?>

<div class="volunteers">
	<form action="index.php" method="post" name="adminForm" id="adminForm" class="form form-horizontal">
		<input type="hidden" name="option" value="com_volunteers" />
		<input type="hidden" name="view" value="group" />
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="volunteers_group_id" value="<?php echo $this->item->volunteers_group_id ?>" />
		<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />

		<!-- Start row -->
		<div class="row-fluid">
			<!-- Start left -->
			<div class="span7">
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
					<label for="ownership" class="control-label">
						<?php echo JText::_('COM_VOLUNTEERS_FIELD_OWNERSHIP'); ?>
					</label>
					<div class="controls">
						<?php echo VolunteersHelperSelect::ownership($this->item->ownership); ?>
					</div>
				</div>
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
					<label for="bio" class="control-label">
						<?php echo JText::_('Get Involved')?>
					</label>
					<div class="controls">
						<?php echo $editor->display( 'getinvolved',  $this->item->getinvolved, '100%', '150', '50', '10', false ) ; ?>
					</div>
				</div>
			</div>
			<!-- End left -->

			<!-- Start right -->
			<div class="span5">
				<div class="well iteminfo">
					<div class="control-group">
						<label for="enabled" class="control-label">
							<?php echo JText::_('JPUBLISHED'); ?>
						</label>
						<div class="controls">
							<?php echo VolunteersHelperSelect::published($this->item->enabled); ?>
						</div>
					</div>
					<div class="control-group">
						<label for="created_by" class="control-label">
							<?php echo JText::_('JGLOBAL_FIELD_CREATED_BY_LABEL'); ?>
						</label>
						<div class="controls">
							<input type="text" class="input" name="created_by" id="created_by" value="<?php echo JFactory::getUser($this->item->created_by)->name; ?>" disabled="disabled" />
						</div>
					</div>
					<div class="control-group">
						<label for="created_on" class="control-label">
							<?php echo JText::_('JGLOBAL_FIELD_CREATED_LABEL'); ?>
						</label>
						<div class="controls">
							<?php echo JHTML::_('calendar', $this->item->created_on, 'created_on', 'created_on'); ?>
						</div>
					</div>
					<div class="control-group">
						<label for="modified_by" class="control-label">
							<?php echo JText::_('JGLOBAL_FIELD_MODIFIED_BY_LABEL'); ?>
						</label>
						<div class="controls">
							<input type="text" class="input" name="modified_by" id="modified_by" value="<?php echo JFactory::getUser($this->item->modified_by)->name; ?>" disabled="disabled" />
						</div>
					</div>
					<div class="control-group">
						<label for="modified_on" class="control-label">
							<?php echo JText::_('JGLOBAL_FIELD_MODIFIED_LABEL'); ?>
						</label>
						<div class="controls">
							<?php echo JHTML::_('calendar', $this->item->modified_on, 'modified_on', 'modified_on'); ?>
						</div>
					</div>
				</div>

				<div class="well iteminfo">
					<div class="control-group">
						<h3><?php echo JText::_('COM_VOLUNTEERS_FIELD_NOTES_INTERNAL')?></h3>
						<textarea name="notes" id="notes" rows="4" class="span"><?php echo $this->item->notes?></textarea>
						<span class="help-block"><?php echo JText::_('COM_VOLUNTEERS_FIELD_NOTES_INTERNAL_DESC')?></span>
					</div>
				</div>
			</div>
			<!-- End right -->
		</div>
		<!-- End row -->
	</form>
</div>