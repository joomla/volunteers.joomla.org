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

?>
<form id="adminForm" class="form-validate form-horizontal" name="adminForm" method="post" action="<?php echo JRoute::_('index.php?option=com_volunteers&view=group&id='.$this->item->volunteers_group_id)?>">
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

	<hr>

	<?php foreach($fields as $field) : ?>
		<?php if(strpos($field, 'ready4transition') !== false) :?>
			<?php if($item->ready4transition == 1) :?>
				<?php $user_set_ready = JFactory::getUser($item->ready4transitionsetby); ?>
				<div class="row-fluid">
					Ready for transtion set by <?php echo $user_set_ready->name;?> at <?php echo $item->ready4transitiondate; ?>
				</div>
			<?php else: ?>
				<hr />
				<div class="row-fluid">
					<div class="span2"></div>
					<div class="span10">
						<label id="ready4transition-lbl" for="ready4transition" class="checkbox">
							<input type="checkbox" name="ready4transition" id="ready4transition" value="1" class="inputbox form-control">
							Ready for Transition into the new structure
						</label>
						<p><br/><strong><?php echo JText::_('COM_VOLUNTEERS_FIELD_READY4TRANSITION_NOTE');?></strong></p>
					</div>
				</div>
				<hr />
			<?php endif; ?>
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
	<input type="hidden" value="group" name="view">
	<input type="hidden" value="save" name="task">
	<input type="hidden" value="<?php echo $this->item->volunteers_group_id ?>" name="volunteers_group_id" />
	
	<?php echo JHtml::_('form.token'); ?>

</form>