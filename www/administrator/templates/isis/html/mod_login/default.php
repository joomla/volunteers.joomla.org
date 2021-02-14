<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  mod_login
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('bootstrap.tooltip');

$db = Factory::getDbo();
$query = $db->getQuery(true)
	->select($db->quoteName('params'))
	->from($db->quoteName('#__extensions'))
	->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
	->where($db->quoteName('folder') . ' = ' . $db->quote('authentication'))
	->where($db->quoteName('element') . ' = ' . $db->quote('sso'));
$db->setQuery($query);
$params = new Registry($db->loadResult());
$profile = $params->get('profile');
$language = Factory::getLanguage();
$language->load('com_sso', JPATH_ADMINISTRATOR . '/components/com_sso/');
$componentParams = ComponentHelper::getParams('com_sso');
$backendLogin = $componentParams->get('backendLogin', true);

// Load chosen if we have language selector, ie, more than one administrator language installed and enabled.
if ($langs)
{
	HTMLHelper::_('formbehavior.chosen', '.advancedSelect');
}
?>
<form action="<?php echo JRoute::_('index.php', true, $params->get('usesecure', 0)); ?>" method="post" id="form-login" class="form-inline">
	<fieldset class="loginform">
		<div class="control-group">
			<div class="controls">
				<div class="input-prepend input-append">
					<span class="add-on">
						<span class="icon-user hasTooltip" title="<?php echo Text::_('JGLOBAL_USERNAME'); ?>"></span>
						<label for="mod-login-username" class="element-invisible">
							<?php echo Text::_('JGLOBAL_USERNAME'); ?>
						</label>
					</span>
					<input name="username" tabindex="1" id="mod-login-username" type="text" class="input-medium" placeholder="<?php echo Text::_('JGLOBAL_USERNAME'); ?>" size="15" autofocus="true" />
					<a href="<?php echo Uri::root(); ?>index.php?option=com_users&view=remind" class="btn width-auto hasTooltip" title="<?php echo Text::_('MOD_LOGIN_REMIND'); ?>">
						<span class="icon-help"></span>
					</a>
				</div>
			</div>
		</div>
		<div class="control-group">
			<div class="controls">
				<div class="input-prepend input-append">
					<span class="add-on">
						<span class="icon-lock hasTooltip" title="<?php echo Text::_('JGLOBAL_PASSWORD'); ?>"></span>
						<label for="mod-login-password" class="element-invisible">
							<?php echo Text::_('JGLOBAL_PASSWORD'); ?>
						</label>
					</span>
					<input name="passwd" tabindex="2" id="mod-login-password" type="password" class="input-medium" placeholder="<?php echo Text::_('JGLOBAL_PASSWORD'); ?>" size="15"/>
					<a href="<?php echo Uri::root(); ?>index.php?option=com_users&view=reset" class="btn width-auto hasTooltip" title="<?php echo Text::_('MOD_LOGIN_RESET'); ?>">
						<span class="icon-help"></span>
					</a>
				</div>
			</div>
		</div>
		<?php if (count($twofactormethods) > 1): ?>
		<div class="control-group">
			<div class="controls">
				<div class="input-prepend input-append">
					<span class="add-on">
						<span class="icon-star hasTooltip" title="<?php echo Text::_('JGLOBAL_SECRETKEY'); ?>"></span>
						<label for="mod-login-secretkey" class="element-invisible">
							<?php echo Text::_('JGLOBAL_SECRETKEY'); ?>
						</label>
					</span>
					<input name="secretkey" autocomplete="off" tabindex="3" id="mod-login-secretkey" type="text" class="input-medium" placeholder="<?php echo Text::_('JGLOBAL_SECRETKEY'); ?>" size="15"/>
					<span class="btn width-auto hasTooltip" title="<?php echo Text::_('JGLOBAL_SECRETKEY_HELP'); ?>">
						<span class="icon-help"></span>
					</span>
				</div>
			</div>
		</div>
		<?php endif; ?>
		<?php if (!empty($langs)) : ?>
			<div class="control-group">
				<div class="controls">
					<div class="input-prepend">
						<span class="add-on">
							<span class="icon-comment hasTooltip" title="<?php echo HTMLHelper::_('tooltipText', 'MOD_LOGIN_LANGUAGE'); ?>"></span>
							<label for="lang" class="element-invisible">
								<?php echo Text::_('MOD_LOGIN_LANGUAGE'); ?>
							</label>
						</span>
						<?php echo $langs; ?>
					</div>
				</div>
			</div>
		<?php endif; ?>
		<div class="control-group">
			<div class="controls">
				<div class="btn-group">
					<button tabindex="5" class="btn btn-primary btn-block btn-large login-button">
						<span class="icon-lock icon-white"></span> <?php echo Text::_('MOD_LOGIN_LOGIN'); ?>
					</button>
				</div>
			</div>
		</div>
		<input type="hidden" name="option" value="com_login"/>
		<input type="hidden" name="task" value="login"/>
		<input type="hidden" name="return" value="<?php echo $return; ?>"/>
		<?php echo HTMLHelper::_('form.token'); ?>
	</fieldset>
</form>
<?php if ($backendLogin && $profile) : ?>
	<div class="control-group">
		<div class="controls">
			<div class="btn-group">
				<a href="<?php echo Route::_('index.php?option=com_sso&task=login.login&profile=' . $profile); ?>" class="btn btn-info btn-large login-button">
					<span class="icon-lock icon-white"></span> <?php echo Text::_('COM_SSO_BACKEND_LOGIN_BUTTON'); ?>
				</a>
			</div>
		</div>
	</div>
<?php endif; ?>
