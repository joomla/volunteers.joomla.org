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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;

require_once JPATH_COMPONENT_ADMINISTRATOR . '/helpers/sso.php';

// Check if the user is logged in
if (Factory::getUser()->id)
{
	?><p><a href="<?php echo Uri::root(); ?>index.php?option=com_sso&task=login.logout" class="btn"><?php echo Text::_('COM_SSO_LOGOUT_BUTTON'); ?></a></p><?php
}
else
{
	?><p><a href="<?php echo Uri::root(); ?>index.php?option=com_sso&task=login.login" class="btn"><?php echo Text::_('COM_SSO_LOGIN_BUTTON'); ?></a></p><?php
}
