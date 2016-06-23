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
<form id="adminForm" class="form-validate form-horizontal" name="adminForm" method="post" action="<?php echo JRoute::_('index.php'); ?>">
	<div class="row-fluid">
		<h1 class="pull-left">
			<?php if($item->volunteers_member_id) :?>
				<?php echo JText::_('COM_VOLUNTEERS_EDIT_MEMBER_' . strtoupper($item->type))?>
			<?php else: ?>
				<?php echo JText::_('COM_VOLUNTEERS_ADD_MEMBER_' . strtoupper($item->type))?>
			<?php endif; ?>
		</h1>

		<div class="btn-toolbar pull-right">
			<div id="toolbar-cancel" class="btn-group">
				<a class="btn btn-small btn-danger" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=' . $item->type .'&id='.$item->reltable_id)?>">
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

	<div class="row-fluid">
		<h2>
			<?php echo JText::_('COM_VOLUNTEERS_GROUP');?>:
			<?php echo $this->escape($item->group->title)?>
			<div class="small">
				<?php echo JText::_('COM_VOLUNTEERS_DEPARTMENT');?>:
				<?php echo $this->escape($item->department->title)?>
			</div>
		</h2>
	</div>

	<hr>

	<?php foreach($fields as $field) : ?>
		<div class="row-fluid">
			<div class="span2">
				<?php echo $form->getLabel($field); ?>
			</div>
			<div class="span10">
				<?php echo $form->getInput($field); ?>

			</div>
		</div>
		<br />
	<?php endforeach; ?>

	<input type="hidden" value="com_volunteers" name="option">
	<input type="hidden" value="member" name="view">
	<input type="hidden" value="save" name="task">
	<input type="hidden" value="<?php echo $item->type; ?>" name="type">
	<input type="hidden" value="<?php echo $item->reltable_id; ?>" name="reltable_id" />
	
	<?php echo JHtml::_('form.token'); ?>

</form>