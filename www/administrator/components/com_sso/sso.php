<?php
/**
 * @package     SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2018 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

defined('_JEXEC') or die;

// Access check.
if (!Factory::getUser()->authorise('core.manage', 'com_sso'))
{
	throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'));
}

// Require the helper
require_once JPATH_COMPONENT_ADMINISTRATOR . '/helpers/sso.php';
require_once JPATH_LIBRARIES . '/simplesamlphp/www/_include.php';

// Execute the task
try
{
	$controller = BaseController::getInstance('sso');
	$controller->execute(Factory::getApplication()->input->get('task'));
	$controller->redirect();
}
catch (Exception $e)
{
	echo $e->getMessage();
}

