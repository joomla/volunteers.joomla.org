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
		if (task == 'volunteer.cancel' || document.formvalidator.isValid(document.getElementById('volunteer'))) {
			Joomla.submitform(task, document.getElementById('volunteer'));
		}
	}
");
?>

<div class="volunteer-edit">

    <form id="volunteer" action="<?php echo JRoute::_('index.php?option=com_volunteers&task=volunteer.save&id=' . (int) $this->item->id); ?>" method="post" class="form-validate form-horizontal" enctype="multipart/form-data">
        <div class="row-fluid">
            <div class="filter-bar">
                <div class="btn-toolbar pull-right">
                    <div id="toolbar-cancel" class="btn-group">
                        <button class="btn btn-danger" onclick="Joomla.submitbutton('volunteer.cancel')">
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
                <h1><?php echo JText::_('COM_VOLUNTEERS_TITLE_VOLUNTEERS_EDIT_MY') ?></h1>
            </div>
        </div>

        <h3><?php echo JText::_('COM_VOLUNTEERS_PROFILE_ACCOUNT') ?></h3>

        <div class="control-group">
            <div class="controls">
                <div class="alert alert-info">
					<?php echo JText::_('COM_VOLUNTEERS_FIELD_NAME_DESC') ?>
                </div>
            </div>
        </div>
		<?php echo $this->form->renderField('name'); ?>
		<?php echo $this->form->renderField('email'); ?>
		<?php echo $this->form->renderField('password1'); ?>
		<?php echo $this->form->renderField('password2'); ?>

        <hr>

        <h3><?php echo JText::_('COM_VOLUNTEERS_PROFILE_BIRTHDAY') ?></h3>

        <div class="control-group">
            <div class="controls">
                <div class="alert alert-info">
					<?php echo JText::_('COM_VOLUNTEERS_FIELD_BIRTHDAY_DESC') ?>
                </div>
            </div>
        </div>

		<?php echo $this->form->renderField('birthday'); ?>

        <hr>

        <h3><?php echo JText::_('COM_VOLUNTEERS_PROFILE_PHOTO') ?></h3>

        <div class="control-group">
            <div class="controls">
                <div class="alert alert-info">
					<?php echo JText::_('COM_VOLUNTEERS_FIELD_IMAGE_DESC') ?>
                </div>
            </div>
        </div>

		<?php echo $this->form->renderField('image'); ?>

        <hr>

        <h3><?php echo JText::_('COM_VOLUNTEERS_PROFILE_LOCATION') ?></h3>

		<?php echo $this->form->renderField('country'); ?>
		<?php echo $this->form->renderField('city'); ?>
		<?php echo $this->form->renderField('location'); ?>

        <hr>

        <h3><?php echo JText::_('COM_VOLUNTEERS_PROFILE_ADDRESS') ?></h3>

        <div class="control-group">
            <div class="controls">
                <div class="alert alert-info">
					<?php echo JText::_('COM_VOLUNTEERS_PROFILE_ADDRESS_DESC') ?>
                </div>
            </div>
        </div>

		<?php if ($this->item->teams->activemember): ?>
            <div class="control-group">
                <div class="controls">
                    <div class="alert alert-warning">
						<?php echo JText::_('COM_VOLUNTEERS_PROFILE_ACTIVEMEMBER_DESC') ?>
                    </div>
                </div>
            </div>
		<?php endif; ?>

		<?php echo $this->form->renderField('address'); ?>
		<?php echo $this->form->renderField('region'); ?>
		<?php echo $this->form->renderField('zip'); ?>

        <div class="control-group checkbox">
            <div class="controls">
				<?php echo $this->form->getInput('send_permission'); ?>
				<?php echo $this->form->getLabel('send_permission'); ?>
            </div>
        </div>

        <hr>

        <h3><?php echo JText::_('COM_VOLUNTEERS_PROFILE_JOOMLA') ?></h3>

		<?php echo $this->form->renderField('joomlaforum'); ?>
		<?php echo $this->form->renderField('joomladocs'); ?>
		<?php echo $this->form->renderField('certification'); ?>

        <hr>

        <h3><?php echo JText::_('COM_VOLUNTEERS_PROFILE_SOCIAL') ?></h3>

		<?php echo $this->form->renderField('website'); ?>
		<?php echo $this->form->renderField('github'); ?>
		<?php echo $this->form->renderField('crowdin'); ?>
		<?php echo $this->form->renderField('twitter'); ?>
		<?php echo $this->form->renderField('facebook'); ?>
		<?php echo $this->form->renderField('googleplus'); ?>
		<?php echo $this->form->renderField('linkedin'); ?>

        <hr>

        <h3><?php echo JText::_('COM_VOLUNTEERS_PROFILE_INTRODUCTION') ?></h3>

        <div class="control-group">
            <div class="controls">
                <div class="alert alert-info">
					<?php echo JText::_('COM_VOLUNTEERS_FIELD_INTRO_DESC') ?>
                </div>
            </div>
        </div>

		<?php echo $this->form->renderField('intro'); ?>

        <div class="control-group">
            <div class="controls">
                <div class="alert alert-info">
					<?php echo JText::_('COM_VOLUNTEERS_FIELD_JOOMLASTORY_DESC') ?>
                </div>
            </div>
        </div>

		<?php echo $this->form->renderField('joomlastory'); ?>

        <hr>

        <h3><?php echo JText::_('COM_VOLUNTEERS_PROFILE_SETTINGS') ?></h3>

        <div class="control-group">
            <div class="controls">
                <div class="alert alert-info">
					<?php echo JText::_('COM_VOLUNTEERS_FIELD_PEAKON_INTRO'); ?>
                </div>
            </div>
        </div>

		<?php echo $this->form->renderField('peakon'); ?>

        <hr>

        <div class="row-fluid">
            <div class="btn-toolbar pull-right">

                <div id="toolbar-cancel" class="btn-group">
                    <a class="btn btn-danger" href="<?php echo JRoute::_('index.php?option=com_volunteers&view=my') ?>">
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
        <input type="hidden" name="task" value="volunteer.save"/>
		<?php echo JHtml::_('form.token'); ?>
    </form>
</div>

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