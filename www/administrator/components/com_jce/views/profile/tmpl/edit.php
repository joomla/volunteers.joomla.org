<?php
/**
 * @package   	JCE
 * @copyright 	Copyright (c) 2009-2015 Ryan Demmer. All rights reserved.
 * @license   	GNU/GPL 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * JCE is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');

?>

<form action="<?php echo JRoute::_('index.php?option=com_jce&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="profile-form" class="form-validate">
	<div class="form-horizontal ui-jce">
		<?php echo JHtml::_('bootstrap.startTabSet', 'profile', array('active' => 'tabs-setup')); ?>

		<?php echo JHtml::_('bootstrap.addTab', 'profile', 'tabs-setup', JText::_('COM_JCE_PROFILE_SETUP', true)); ?>
		<div class="row-fluid">
                    <div class="span12">
                        <?php echo JLayoutHelper::render('edit.setup', $this); ?>
                    </div>
		</div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>

		<?php echo JHtml::_('bootstrap.addTab', 'profile', 'tabs-features', JText::_('COM_JCE_PROFILE_FEATURES', true)); ?>
		<div class="row-fluid">
			<div class="span12">
				<?php echo JLayoutHelper::render('edit.features', $this); ?>
			</div>
		</div>

		<?php echo JHtml::_('bootstrap.endTab'); ?>

		<?php echo JHtml::_('bootstrap.addTab', 'profile', 'tabs-editor', JText::_('COM_JCE_PROFILE_EDITOR', true)); ?>
		<div class="row-fluid">
			<div class="span12">
				<?php echo JLayoutHelper::render('edit.editor', $this); ?>
			</div>
		</div>

		<?php echo JHtml::_('bootstrap.endTab'); ?>

		<?php echo JHtml::_('bootstrap.addTab', 'profile', 'tabs-plugins', JText::_('COM_JCE_PROFILE_PLUGINS', true)); ?>
		<div class="row-fluid">
			<div class="span12">
				<?php echo JLayoutHelper::render('edit.plugins', $this); ?>
			</div>
		</div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>
		<?php echo JHtml::_('bootstrap.endTabSet'); ?>

	</div>

	<input type="hidden" name="task" value="" />
	<?php echo JHtml::_('form.token'); ?>
</form>
