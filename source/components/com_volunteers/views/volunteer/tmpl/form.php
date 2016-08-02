	<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

JHTML::_('behavior.framework', true);
JHtml::_('behavior.formvalidator');
JHtml::_('behavior.keepalive');
JHtml::_('formbehavior.chosen', 'select');

JFactory::getDocument()->addScriptDeclaration('
	Joomla.submitbutton = function(task)
	{
		if (task == "cancel" || document.formvalidator.isValid(document.id("adminForm"))) {
			Joomla.submitform(task, document.getElementById("adminForm"));
		} else {
			alert("Invalid form");
		}
	};
');

$item   = $this->get('item');
$form   = $this->get('form');
$fields = array_keys($form->getFieldset('basic_configuration'));
	
/*
	echo "#<div style='text-align:left;font_size:1.2em;'><pre>";
	print_r($item);
	echo "</pre></div>#";
	
*/
?>
<div class="volunteer-edit">
	<form id="adminForm" class="form-validate form-horizontal" name="adminForm" method="post" action="<?php echo JRoute::_('index.php?option=com_volunteers&view=volunteer&id='. $item->volunteers_volunteer_id); ?>" enctype="multipart/form-data">
		<div class="row-fluid">
			<h1 class="pull-left"><?php echo JText::_('COM_VOLUNTEERS_EDIT_VOLUNTEER')?></h1>
			<div class="btn-toolbar pull-right">
				<div id="toolbar-cancel" class="btn-group">
					<a class="btn btn-small btn-danger" href="<?php echo JRoute::_('index.php')?>">
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
	
		<hr>
	
		<?php foreach($fields as $field) : ?>
			<?php if($field == 'image') :?>
				
				<?php if(trim($item->image) != '') :?>
					<div class="row-fluid">
						<div class="span2">
							<?php echo JText::_('COM_VOLTUNEERS_YOURPROFILE_PICTURE');?>
						</div>
						<div class="span10 volunteer-image">
							<?php echo VolunteersHelperFormat::image($item->image, 'large'); ?>
						</div>
					</div>
				<?php endif; ?>
				<div class="row-fluid">
					<div class="span12">
						<?php echo JText::_('COM_VOLTUNEERS_RELACE_IMAGE');?>
					</div>
				</div>
				<div class="row-fluid">
					<div class="span2">
						<?php echo $form->getLabel($field); ?>
					</div>
					<div class="span10">
						<?php echo $form->getInput($field); ?>
					
					</div>
				</div>
				<div class="row-fluid">
					<div class="span12">
						<?php echo JText::_('COM_VOLTUNEERS_REMOVE_IMAGE_TEXT');?>
					</div>
				</div>
			<?php else: ?>
				<div class="row-fluid">
					<div class="span2">
						<?php echo $form->getLabel($field); ?>
					</div>
					<div class="span10">
						<?php echo $form->getInput($field); ?>
					
					</div>
				</div>
			<?php endif; ?>
			<br />
		<?php endforeach; ?>
	
		<input type="hidden" value="com_volunteers" name="option">
		<input type="hidden" value="volunteer" name="view">
		<input type="hidden" value="save" name="task">
		<input type="hidden" value="<?php echo $item->volunteers_volunteer_id ?>" name="volunteers_volunteer_id" />
		
		<?php echo JHtml::_('form.token'); ?>
	
	</form>
</div>