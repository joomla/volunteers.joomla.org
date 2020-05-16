<?php
/**
 * @package    SSO.Module
 *
 * @copyright  Copyright (C) 2017 - 2018 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;

$user = Factory::getUser();

// Check if the user is logged in
if ($user->id)
{
	echo Text::sprintf('MOD_SSO_GREETING', $user->name);
	?>
	<p>
		<a href="<?php echo Uri::root(); ?>index.php?option=com_sso&task=login.logout&profile=<?php echo $params->get('profile'); ?>" class="btn">
			<?php echo Text::_('MOD_SSO_LOGOUT_BUTTON'); ?>
		</a>
	</p>
	<?php
}
else
{
	?>
	<p>
		<a href="<?php echo Uri::root(); ?>index.php?option=com_sso&task=login.login&profile=<?php echo $params->get('profile'); ?>" class="btn">
			<?php echo Text::_('MOD_SSO_LOGIN_BUTTON'); ?>
		</a>
	</p>
	<?php
}
