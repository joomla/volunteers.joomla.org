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

// This brilliant code can be removed after the transtion, so 2020 then.

$params = JComponentHelper::getParams('com_volunteers');

$show = $params->get('transactiv') == 1 && $this->item->ready4transition == 1;

if($show)
{
	$user_set_ready = JFactory::getUser($this->item->ready4transitionsetby);
}

?>
<?php if($show) :?>
	<div class="alert alert-info">
		Ready for transition set by <?php echo $user_set_ready->name;?> at <?php echo $this->item->ready4transitiondate; ?>
	</div>
<?php endif; ?>

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
	<input type="hidden" value="group" name="view">
	<input type="hidden" value="" name="task">
	<input type="hidden" value="<?php echo $this->item->volunteers_group_id; ?>" name="volunteers_group_id">
	<?php echo JHtml::_('form.token'); ?>

</form>
