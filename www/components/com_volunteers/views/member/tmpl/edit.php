<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

JHtml::_('behavior.keepalive');
JHtml::_('behavior.formvalidator');
JHtml::_('formbehavior.chosen', 'select');

JFactory::getDocument()->addScriptDeclaration("
	Joomla.submitbutton = function(task)
	{
		if (task == 'member.cancel' || document.formvalidator.isValid(document.getElementById('member'))) {
			Joomla.submitform(task, document.getElementById('member'));
		}
	}
");

if ($this->item->department)
{
	$view = 'department';
	$id   = $this->item->department;
}
elseif ($this->item->team)
{
	$view = 'team';
	$id   = $this->item->team;
}
?>

<div class="member-edit">

	<form id="member" action="<?php echo JRoute::_('index.php?option=com_volunteers&task=member.save&id=' . (int) $this->item->id); ?>" method="post" class="form-validate form-horizontal" enctype="multipart/form-data">
		<div class="row-fluid">
			<div class="filter-bar">
				<div class="btn-toolbar pull-right">
					<div id="toolbar-cancel" class="btn-group">
						<button class="btn btn-danger" onclick="Joomla.submitbutton('member.cancel')">
							<span class="icon-cancel"></span> <?php echo JText::_('JCANCEL') ?>
						</button>
					</div>
					<div id="toolbar-apply" class="btn-group">
						<button class="btn btn-success" type="submit">
							<span class="icon-pencil"></span> <?php echo JText::_('JSAVE') ?>
						</button>
					</div>
				</div>
			</div>
			<div class="page-header">
				<h1><?php echo JText::_('COM_VOLUNTEERS_TITLE_MEMBERS_EDIT') ?></h1>
			</div>
		</div>

		<?php if ($this->item->department): ?>
			<?php echo $this->form->renderField('department'); ?>
		<?php endif; ?>
		<?php if ($this->item->team): ?>
			<?php echo $this->form->renderField('team'); ?>
		<?php endif; ?>

		<?php echo $this->form->renderField('volunteer'); ?>

		<hr>

		<?php echo $this->form->renderField('position'); ?>
		<?php echo $this->form->renderField('role'); ?>

		<hr>

		<?php echo $this->form->renderField('date_started'); ?>

		<div class="control-group">
			<div class="control-label">
				<?php echo $this->form->getLabel('date_ended'); ?>
			</div>
			<div class="controls">
				<div class="alert alert-info">
					<?php echo JText::_('COM_VOLUNTEERS_FIELD_DATE_ENDED_MEMBER_DESC') ?>
				</div>
				<?php echo $this->form->getInput('date_ended'); ?>
			</div>
		</div>

		<hr>

		<div class="row-fluid">
			<div class="btn-toolbar pull-right">
				<div id="toolbar-cancel" class="btn-group">
					<a class="btn btn-danger" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=team&id=' . $this->item->team) ?>">
						<span class="icon-cancel"></span> <?php echo JText::_('JCANCEL') ?>
					</a>
				</div>
				<div id="toolbar-apply" class="btn-group">
					<button class="btn btn-success" type="submit">
						<span class="icon-pencil"></span> <?php echo JText::_('JSAVE') ?>
					</button>
				</div>
			</div>
		</div>

		<input type="hidden" name="option" value="com_volunteers"/>
		<input type="hidden" name="task" value="member.save"/>
		<?php echo JHtml::_('form.token'); ?>
	</form>
</div>
