<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

JHtml::_('behavior.formvalidator');
JHtml::_('formbehavior.chosen', 'select');

JFactory::getDocument()->addScriptDeclaration("
	Joomla.submitbutton = function(task)
	{
		if (task == 'team.cancel' || document.formvalidator.isValid(document.getElementById('team-form'))) {
			" . $this->form->getField('getinvolved')->save() . "
			Joomla.submitform(task, document.getElementById('team-form'));
		}
	};
");
?>

<form action="<?php echo JRoute::_('index.php?option=com_volunteers&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="team-form" class="form-validate">

	<?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

	<hr>

	<div class="row-fluid">
		<div class="span9">
			<div class="form-horizontal">
				<?php echo $this->form->renderFieldset('item'); ?>
			</div>
		</div>
		<div class="span3">
			<div class="form-vertical well">
				<?php echo $this->form->renderFieldset('details'); ?>
			</div>
		</div>
	</div>

	<input type="hidden" name="task" value=""/>
	<?php echo JHtml::_('form.token'); ?>
</form>
