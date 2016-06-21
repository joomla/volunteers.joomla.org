<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

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
		<h1 class="pull-left"><?php echo JText::_('COM_VOLUNTEERS_EDIT_PROFILE')?></h1>
		<div class="btn-toolbar pull-right">
			<div id="toolbar-cancel" class="btn-group">
				<a class="btn btn-small btn-danger" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=my')?>">
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
			<input type="hidden" name="view" value="volunteer" />
			<input type="hidden" name="task" value="save" />
			<input type="hidden" name="volunteers_volunteer_id" value="<?php echo $this->item->volunteers_volunteer_id ?>" />
			<input type="hidden" name="Itemid" value="<?php echo $itemId ?>" />
			<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />
			<input type="hidden" name="enabled" value="<?php echo($this->item->enabled); ?>" />

			<!-- Start row -->
			<div class="row-fluid">
				<!-- Start left -->
				<div class="span12">
					<hr>
					<div class="control-group">
						<label for="firstname" class="control-label">
							<?php echo JText::_('COM_VOLUNTEERS_FIELD_FIRSTNAME')?>
						</label>
						<div class="controls">
							<input type="text" name="firstname" id="firstname" class="span" value="<?php echo $this->item->firstname?>" required="required"/>
						</div>
					</div>
					<div class="control-group">
						<label for="lastname" class="control-label">
							<?php echo JText::_('COM_VOLUNTEERS_FIELD_LASTNAME')?>
						</label>
						<div class="controls">
							<input type="text" name="lastname" id="lastname" class="span" value="<?php echo $this->item->lastname?>" required="required"/>
						</div>
					</div>
					<hr>
					<div class="control-group">
						<label for="country" class="control-label">
							<?php echo JText::_('COM_VOLUNTEERS_FIELD_COUNTRY')?>
						</label>
						<div class="controls">
							<?php echo VolunteersHelperSelect::countries($this->item->country); ?>
						</div>
					</div>
					<div class="control-group">
						<label for="city" class="control-label">
							<?php echo JText::_('COM_VOLUNTEERS_FIELD_CITY')?>
						</label>
						<div class="controls">
							<input type="text" name="city" id="city" class="span" value="<?php echo $this->item->city?>"/>
						</div>
					</div>
					<hr>
					<div class="control-group">
						<label for="intro" class="control-label">
							<?php echo JText::_('COM_VOLUNTEERS_FIELD_INTRO')?>
						</label>
						<div class="controls">
							<div class="alert alert-info">
								<?php echo JText::_('COM_VOLUNTEERS_FIELD_INTRO_DESC') ?>
							</div>
							<textarea name="intro" id="intro" rows="4" class="span"><?php echo $this->item->intro?></textarea>
						</div>
					</div>
					<hr>
					<div class="control-group">
						<label for="joomlastory" class="control-label">
							<?php echo JText::_('COM_VOLUNTEERS_FIELD_JOOMLASTORY')?>
						</label>
						<div class="controls">
							<div class="alert alert-info">
								<?php echo JText::_('COM_VOLUNTEERS_FIELD_JOOMLASTORY_DESC') ?>
							</div>
							<textarea name="joomlastory" id="joomlastory" rows="15" class="span"><?php echo $this->item->joomlastory?></textarea>
						</div>
					</div>
					<hr>
					<div class="control-group">
						<label for="image" class="control-label">
							<?php echo JText::_('COM_VOLUNTEERS_FIELD_IMAGE')?>
						</label>
						<div class="controls">
							<div class="alert alert-info">
								<?php echo JText::_('COM_VOLUNTEERS_NOTE_IMAGE') ?>
							</div>
							<input type="file" name="image" id="image" class="span" />
							<span class="help-block"><?php echo JText::_('COM_VOLUNTEERS_FIELD_IMAGE_DESC')?></span>
						</div>
					</div>
					<hr>
					<div class="control-group">
						<label for="email" class="control-label">
							<?php echo JText::_('JGLOBAL_EMAIL')?>
						</label>
						<div class="controls">
							<input type="text" name="email" id="email" class="span" placeholder="mail@website.com" required="required" value="<?php echo $this->item->email?>"/>
						</div>
					</div>
					<div class="control-group">
						<label for="twitter" class="control-label">
							<?php echo JText::_('COM_VOLUNTEERS_FIELD_TWITTER')?>
						</label>
						<div class="controls">
							<div class="input-prepend">
								<span class="add-on">@</span>
								<input type="text" name="twitter" id="twitter" class="span" placeholder="<?php echo JText::_('COM_VOLUNTEERS_PLACEHOLDER_USERNAME')?>" value="<?php echo $this->item->twitter?>"/>
							</div>
						</div>
					</div>
					<div class="control-group">
						<label for="facebook" class="control-label">
							<?php echo JText::_('COM_VOLUNTEERS_FIELD_FACEBOOK')?>
						</label>
						<div class="controls">
							<div class="input-prepend">
								<span class="add-on">http://www.facebook.com/</span>
								<input type="text" name="facebook" id="facebook" class="span" placeholder="<?php echo JText::_('COM_VOLUNTEERS_PLACEHOLDER_USERNAME')?>" value="<?php echo $this->item->facebook?>"/>
							</div>
						</div>
					</div>
					<div class="control-group">
						<label for="googleplus" class="control-label">
							<?php echo JText::_('COM_VOLUNTEERS_FIELD_GOOGLEPLUS')?>
						</label>
						<div class="controls">
							<div class="input-prepend">
								<span class="add-on">http://plus.google.com/</span>
								<input type="text" name="googleplus" id="googleplus" class="span" placeholder="<?php echo JText::_('COM_VOLUNTEERS_PLACEHOLDER_USERNAME')?>" value="<?php echo $this->item->googleplus?>"/>
							</div>
						</div>
					</div>
					<div class="control-group">
						<label for="googleplus" class="control-label">
							<?php echo JText::_('COM_VOLUNTEERS_FIELD_LINKEDIN')?>
						</label>
						<div class="controls">
							<div class="input-prepend">
								<span class="add-on">http://www.linkedin.com/in/</span>
								<input type="text" name="linkedin" id="linkedin" class="span" placeholder="<?php echo JText::_('COM_VOLUNTEERS_PLACEHOLDER_USERNAME')?>" value="<?php echo $this->item->linkedin?>"/>
							</div>
						</div>
					</div>
					<div class="control-group">
						<label for="website" class="control-label">
							<?php echo JText::_('COM_VOLUNTEERS_FIELD_GITHUB')?>
						</label>
						<div class="controls">
							<div class="input-prepend">
								<span class="add-on">http://github.com/</span>
								<input type="text" name="github" id="github" class="span" placeholder="<?php echo JText::_('COM_VOLUNTEERS_PLACEHOLDER_USERNAME')?>" value="<?php echo $this->item->github?>"/>
							</div>
						</div>
					</div>
					<div class="control-group">
						<label for="website" class="control-label">
							<?php echo JText::_('COM_VOLUNTEERS_FIELD_WEBSITE')?>
						</label>
						<div class="controls">
							<div class="input-prepend">
								<span class="add-on">http://</span>
								<input type="text" name="website" id="website" class="span" placeholder="<?php echo JText::_('COM_VOLUNTEERS_PLACEHOLDER_WEBSITE')?>" value="<?php echo $this->item->website?>"/>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="row-fluid">
		<div class="btn-toolbar pull-right">
			<div id="toolbar-cancel" class="btn-group">
				<a class="btn btn-small btn-danger" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=my')?>">
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