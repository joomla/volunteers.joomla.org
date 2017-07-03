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
		if (task == 'volunteer.cancel' || document.formvalidator.isValid(document.getElementById('volunteer-form'))) {
			" . $this->form->getField('joomlastory')->save() . "
			Joomla.submitform(task, document.getElementById('volunteer-form'));
		}
	};
");
?>

<form action="<?php echo JRoute::_('index.php?option=com_volunteers&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="volunteer-form" class="form-validate">

    <div class="form-inline form-inline-header">
		<?php
		echo $this->form->renderField('name');
		echo $this->form->renderField('alias');
		?>
    </div>

    <hr>

    <div class="row-fluid">
        <div class="span9">
            <div class="form-horizontal">
				<?php echo $this->form->renderFieldset('item'); ?>
            </div>
        </div>
        <div class="span3">
			<?php if (JFactory::getUser()->authorise('core.admin')): ?>
                <div class="form-vertical well">
                    <h3><?php echo JText::_('COM_VOLUNTEERS_SECRETARY_ONLY') ?></h3>
                    <div class="control-group checkbox">
                        <div class="controls">
			                <?php echo $this->form->getInput('coc'); ?>
			                <?php echo $this->form->getLabel('coc'); ?>
                        </div>
                    </div>
                </div>
			<?php endif; ?>
            <div class="form-vertical well">
				<?php echo $this->form->renderFieldset('details'); ?>
            </div>
        </div>
    </div>

    <input type="hidden" name="task" value=""/>
	<?php echo JHtml::_('form.token'); ?>
</form>

<script>
    jQuery(document).ready(function () {
        jQuery('.location').on('change', function (e) {
            var city = jQuery('.location-city').val();
            var country = jQuery('.location-country').val();
            jQuery('.gllpSearchField').val(city + ', ' + country);
            jQuery('.gllpSearchButton').click();
        });
    });
</script>