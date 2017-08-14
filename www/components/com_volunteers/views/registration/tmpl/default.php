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
?>

<div class="volunteer-edit">

	<form id="volunteer" action="<?php echo JRoute::_('index.php?option=com_volunteers&task=registration.register'); ?>" method="post" class="form-validate form-horizontal" enctype="multipart/form-data">
		<div class="row-fluid">
			<div class="page-header">
				<h1><?php echo JText::_('COM_VOLUNTEERS_PROFILE_NEW') ?></h1>
			</div>

			<p class="lead">
				<?php echo JText::_('COM_VOLUNTEERS_PROFILE_INTRO') ?>
			</p>
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

		<div class="address">
			<?php echo $this->form->renderField('address'); ?>
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

		<div class="control-group">
			<div class="controls">
				<div class="alert alert-info">
					<?php echo JText::_('COM_VOLUNTEERS_FIELD_PEAKON_INTRO'); ?>
				</div>
			</div>
		</div>

		<?php echo $this->form->renderField('peakon'); ?>

        <div class="row-fluid">
			<div class="btn-toolbar pull-right">

				<div id="toolbar-apply" class="btn-group">
					<button class="btn btn-large btn-success" type="submit">
						<?php echo JText::_('COM_VOLUNTEERS_PROFILE_CREATE') ?>
					</button>
				</div>

			</div>
		</div>

		<input type="hidden" name="option" value="com_volunteers"/>
		<input type="hidden" name="task" value="registration.register"/>
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