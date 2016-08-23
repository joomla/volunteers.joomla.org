<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_login
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

require_once JPATH_SITE . '/components/com_users/helpers/route.php';

JHtml::_('behavior.keepalive');
JHtml::_('bootstrap.tooltip');

// load volunteers language file
$jlang = JFactory::getLanguage();
$jlang->load('com_volunteers', JPATH_ADMINISTRATOR, 'en-GB', true);
$jlang->load('com_volunteers', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
$jlang->load('com_volunteers', JPATH_ADMINISTRATOR, null, true);
?>

<form action="<?php echo JRoute::_(htmlspecialchars(JUri::getInstance()->toString()), true, $params->get('usesecure')); ?>" method="post" id="login-form" class="">
	<div class="userdata">
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
				<label for="modlgn-remember" class="control-label"><?php echo JText::_('MOD_LOGIN_REMEMBER_ME') ?></label>
				<input id="modlgn-remember" type="checkbox" name="remember" class="inputbox" value="yes"/>
			</div>
		</div>
		<div id="form-login-submit" class="control-group">
			<div class="controls">
				<button type="submit" tabindex="0" name="Submit" class="btn btn-primary"><?php echo JText::_('JLOGIN') ?></button>
			</div>
		</div>
		<ul class="unstyled">
			<li>
				<a href="<?php echo JRoute::_('index.php?option=com_volunteers&view=registration'); ?>">
					<?php echo JText::_('MOD_LOGIN_REGISTER'); ?> <span class="icon-arrow-right"></span>
				</a>
			</li>
			<li>
				<a href="<?php echo JRoute::_('index.php?option=com_users&view=reset&Itemid=' . UsersHelperRoute::getResetRoute()); ?>">
					<?php echo JText::_('MOD_LOGIN_FORGOT_YOUR_PASSWORD'); ?>
				</a>
			</li>
		</ul>
		<input type="hidden" name="option" value="com_users"/>
		<input type="hidden" name="task" value="user.login"/>
		<input type="hidden" name="return" value="<?php echo $return; ?>"/>
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>
