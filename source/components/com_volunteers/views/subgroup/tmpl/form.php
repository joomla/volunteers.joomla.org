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
		<h1 class="pull-left"><?php echo JText::_('COM_VOLUNTEERS_EDIT_SUBGROUP')?></h1>
		<div class="btn-toolbar pull-right">
			<div id="toolbar-cancel" class="btn-group">
				<a class="btn btn-small btn-danger" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=group&id='.$item->group->volunteers_group_id, false)?>#subgroups   ">
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

	<div class="form-horizontal">
		<?php foreach($fields as $field) : ?>
			<div class="row-fluid">
				<div class="span2">
					<?php echo $this->form->getLabel($field); ?>
				</div>
				<div class="span10">
					<?php echo $this->form->getInput($field); ?>

				</div>
			</div>
			<br />
		<?php endforeach; ?>

		<h2>Members</h2>

		<hr>

		<?php // THESE ARE ALL MEMBERS OF THE GROUP NOT THE SUBGROUPMEMBERS !! ?>
		<?php $chunks = array_chunk($item->groupmembers, 4);?>
		<?php foreach($chunks as $row) : ?>
			<div class="row-fluid">
				<?php foreach($row as $member) : ?>
					<div class="span3">
						<label  for="id="groupmember<?php echo $member->volunteers_volunteer_id;?>"" class="checkbox">
							<input type="checkbox"  value="<?php echo $member->volunteers_volunteer_id;?>"
							       class="inputbox form-control" name="assigned[]"
							       id="groupmember<?php echo $member->volunteers_volunteer_id;?>"
							       <?php if(in_array($member->volunteers_volunteer_id, $item->subgroupmemberIds)) :?>
							            checked="checked"
							       <?php endif; ?>
							>
							<?php echo $member->firstname . ' ' . $member->lastname;?>
						</label>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
	</div>

	<input type="hidden" value="com_volunteers" name="option">
	<input type="hidden" value="subgroup" name="view">
	<input type="hidden" value="save" name="task">
	<input type="hidden" value="<?php echo $item->group->volunteers_group_id ?>" name="group_id" />
	<input type="hidden" value="<?php echo $item->volunteers_subgroup_id ?>" name="volunteers_subgroup_id" />
	
	<?php echo JHtml::_('form.token'); ?>

</form>