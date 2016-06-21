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

$fields = array_keys($this->form->getFieldset('basic_configuration'));
?>
<form id="adminForm" class="form-validate" name="adminForm" method="post" action="index.php">

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
	</div>

	<input type="hidden" value="com_volunteers" name="option">
	<input type="hidden" value="subgroup" name="view">
	<input type="hidden" value="" name="task">
	<input type="hidden" value="<?php echo $this->item->volunteers_subgroup_id; ?>" name="volunteers_subgroup_id">
	<?php echo JHtml::_('form.token'); ?>

</form>