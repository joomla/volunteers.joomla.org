<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::_('behavior.keepalive');
JHtml::_('behavior.formvalidator');

// Load volunteer language
$jlang = JFactory::getLanguage();
$jlang->load('com_volunteers', JPATH_ADMINISTRATOR, 'en-GB', true);
$jlang->load('com_volunteers', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
$jlang->load('com_volunteers', JPATH_ADMINISTRATOR, null, true);
?>
<div class="login<?php echo $this->pageclass_sfx; ?>">


	<div class="page-header">
		<h1>
			<?php echo JText::_('COM_VOLUNTEERS_LOGIN') ?>
		</h1>
	</div>

	<p class="lead">
		<?php echo JText::_('COM_VOLUNTEERS_LOGIN_DESC') ?>
	</p>

	<form action="<?php echo JRoute::_('index.php?option=com_users&task=user.login'); ?>" method="post" class="form-validate well">


		<div id="form-login-username" class="control-group">
			<div class="controls">
				<label for="modlgn-username"><?php echo JText::_('COM_VOLUNTEERS_FIELD_EMAIL') ?></label>
				<input id="modlgn-username" type="text" name="username" tabindex="0" class="input-block-level" placeholder="<?php echo JText::_('COM_VOLUNTEERS_FIELD_EMAIL') ?>"/>
			</div>
		</div>
		<div id="form-login-password" class="control-group">
			<div class="controls">
				<label for="modlgn-passwd"><?php echo JText::_('JGLOBAL_PASSWORD') ?></label>
				<input id="modlgn-passwd" type="password" name="password" tabindex="0" class="input-block-level" placeholder="<?php echo JText::_('JGLOBAL_PASSWORD') ?>"/>
			</div>
		</div>

		<div class="form-inline">
			<div id="form-login-remember" class="control-group checkbox">
				<label for="modlgn-remember" class="control-label"><?php echo JText::_('COM_USERS_LOGIN_REMEMBER_ME') ?></label>
				<input id="modlgn-remember" type="checkbox" name="remember" class="inputbox" value="yes"/>
			</div>
		</div>

		<div class="control-group">
			<div class="controls">
				<button type="submit" class="btn btn-primary">
					<?php echo JText::_('JLOGIN'); ?>
				</button>
			</div>
		</div>

		<?php if ($this->params->get('login_redirect_url')) : ?>
			<input type="hidden" name="return" value="<?php echo base64_encode($this->params->get('login_redirect_url', $this->form->getValue('return'))); ?>"/>
		<?php else : ?>
			<input type="hidden" name="return" value="<?php echo base64_encode($this->params->get('login_redirect_menuitem', $this->form->getValue('return'))); ?>"/>
		<?php endif; ?>
		<?php echo JHtml::_('form.token'); ?>

	</form>
</div>
<div>
	<ul class="nav nav-tabs nav-stacked">
		<li>
			<a href="<?php echo JRoute::_('index.php?option=com_users&view=reset'); ?>">
				<?php echo JText::_('COM_USERS_LOGIN_RESET'); ?>
			</a>
		</li>
		<li>
			<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=registration'); ?>">
				<?php echo JText::_('COM_USERS_LOGIN_REGISTER'); ?>
			</a>
		</li>
	</ul>
</div>
