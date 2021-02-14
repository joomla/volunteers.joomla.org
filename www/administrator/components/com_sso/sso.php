<?php
/**
 * @package     SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2021 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

// Access check.
if (!Factory::getUser()->authorise('core.manage', 'com_sso'))
{
	throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'));
}

// Require the helper
JLoader::register('SsoConfig', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/config.php');
JLoader::register('SsoAuthsources', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/authsources.php');
JLoader::register('SsoMetarefresh', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/metarefresh.php');
JLoader::register('SsoHelper', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/sso.php');
JLoader::register('SsoAttribute', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/attribute.php');
require_once JPATH_LIBRARIES . '/simplesamlphp/www/_include.php';

// Execute the task
try
{
	$controller = BaseController::getInstance('sso');
	$controller->execute(Factory::getApplication()->input->get('task'));
	$controller->redirect();
}
catch (Exception $exception)
{
	echo $exception->getMessage();
}

