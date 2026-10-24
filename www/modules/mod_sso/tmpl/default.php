<?php
/**
 * @package    SSO.Module
 *
 * @copyright  Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Rolandd\Component\Sso\Site\Model\LoginModel;

defined('_JEXEC') or die;

/**
 * @var $params Registry The module parameters
 */
$profile = $params->get('profile');
/** @var LoginModel $model */
$model = Factory::getApplication()->bootComponent('com_sso')->getMVCFactory()->createModel('Login', 'Site', ['ignore_request' => true]);
$model->setState('authorizationSource', $profile);

// Check if the user is logged in
if ($model->isAuthenticated())
{
	$user = Factory::getApplication()->getIdentity();
	echo Text::sprintf('MOD_SSO_GREETING', $user->name);
	?>
	<p>
		<a href="<?php echo Uri::root(); ?>index.php?option=com_sso&task=login.logout&profile=<?php echo $profile; ?>" class="btn btn-primary">
			<?php echo Text::_('MOD_SSO_LOGOUT_BUTTON'); ?>
		</a>
	</p>
	<?php
}
else
{
	?>
	<p>
		<a href="<?php echo Uri::root(); ?>index.php?option=com_sso&task=login.login&profile=<?php echo $profile; ?>" class="btn btn-primary">
			<?php echo Text::_('MOD_SSO_LOGIN_BUTTON'); ?>
		</a>
	</p>
	<?php
}
