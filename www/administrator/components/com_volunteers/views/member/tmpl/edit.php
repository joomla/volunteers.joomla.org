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
		if (task == 'member.cancel' || document.formvalidator.isValid(document.getElementById('member-form'))) {
			Joomla.submitform(task, document.getElementById('member-form'));
		}
	};
");
?>

<script>
	jQuery(document).ready(function ($) {
		$(".team").change(function () {
			team = $("#" + this.id).val();
			$.ajax({
				url: 'index.php?option=com_volunteers&task=roles.getTeamRoles',
				type: "POST",
				data: {
					'team': team,
					'role': <?php echo $this->item->role; ?>,
				}
			}).done(function (options) {
				$(".roles").html(options);
				$(".roles").trigger("liszt:updated");
			})
		}).trigger('change');
	});
</script>

<form action="<?php echo JRoute::_('index.php?option=com_volunteers&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="member-form" class="form-validate">

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
