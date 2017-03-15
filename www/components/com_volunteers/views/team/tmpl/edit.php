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
		if (task == 'team.cancel' || document.formvalidator.isValid(document.getElementById('team'))) {
			Joomla.submitform(task, document.getElementById('team'));
		}
	}
");
?>

<div class="team-edit">

	<form id="team" action="<?php echo JRoute::_('index.php?option=com_volunteers&task=team.save&id=' . (int) $this->item->id); ?>" method="post" class="form-validate form-horizontal" enctype="multipart/form-data">
		<div class="row-fluid">

			<div class="filter-bar">
				<div class="btn-toolbar pull-right">
					<div id="toolbar-cancel" class="btn-group">
						<button class="btn btn-danger" onclick="Joomla.submitbutton('team.cancel')">
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
				<h1><?php echo JText::_('COM_VOLUNTEERS_TITLE_TEAMS_EDIT') ?></h1>
			</div>

		</div>

		<?php echo $this->form->renderField('title'); ?>
		<?php echo $this->form->renderField('alias'); ?>

		<hr>

		<?php echo $this->form->renderField('department'); ?>
		<?php echo $this->form->renderField('status'); ?>
		<?php echo $this->form->renderField('parent_id'); ?>

		<hr>

		<?php echo $this->form->renderField('acronym'); ?>
		<?php echo $this->form->renderField('email'); ?>
		<?php echo $this->form->renderField('website'); ?>
		<?php echo $this->form->renderField('date_started'); ?>
		<?php echo $this->form->renderField('date_ended'); ?>

		<hr>

		<?php echo $this->form->renderField('description'); ?>
		<?php echo $this->form->renderField('getinvolved'); ?>

		<hr>

		<div class="row-fluid">
			<div class="btn-toolbar pull-right">
				<div id="toolbar-cancel" class="btn-group">
					<a class="btn btn-danger" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=team&id=' . $this->item->id) ?>">
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
		<input type="hidden" name="task" value="team.save"/>
		<?php echo JHtml::_('form.token'); ?>
	</form>
</div>
