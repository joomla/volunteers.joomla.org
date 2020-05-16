<?php
/**
 * @package     SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2020 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

// Load the tooltip behavior.
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('formbehavior.chosen');

Factory::getDocument()->addScriptDeclaration(
	'
	Joomla.submitbutton = function(task)
	{
		if (document.formvalidator.isValid(document.getElementById("adminForm")))
		{
			Joomla.submitform(task, document.getElementById("adminForm"));
		}
	};

	// Select first tab
	jQuery(document).ready(function() {
		jQuery("#configTabs a:first").tab("show");
	});'
);
?>
<div id="j-sidebar-container" class="span2">
	<?php echo $this->sidebar; ?>
</div>
<div id="j-main-container" class="span10">
	<form action="index.php?option=com_sso&view=config" method="post" name="adminForm" id="adminForm" class="form-validate form-horizontal">
		<ul class="nav nav-tabs" id="configTabs">
			<?php foreach ($this->form->getFieldsets() as $name => $fieldSet) : ?>
				<?php $label = empty($fieldSet->label) ? 'COM_CONFIG_' . $name . '_FIELDSET_LABEL' : $fieldSet->label; ?>
				<li><a data-toggle="tab" href="#<?php echo $name; ?>"><?php echo Text::_($label); ?></a></li>
			<?php endforeach; ?>
		</ul>

		<div class="tab-content" id="configContent">
			<?php foreach ($this->form->getFieldsets() as $name => $fieldSet) : ?>
				<div class="tab-pane" id="<?php echo $name; ?>">
					<?php if (isset($fieldSet->description) && !empty($fieldSet->description)) : ?>
						<div class="tab-description alert alert-info">
							<span class="icon-info" aria-hidden="true"></span> <?php echo Text::_($fieldSet->description); ?>
						</div>
					<?php endif; ?>
                    <?php echo $this->form->renderFieldset($name); ?>
				</div>
			<?php endforeach; ?>
		</div>
		<input type="hidden" name="task" value="" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>
