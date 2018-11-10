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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Controller\BaseController;

defined('_JEXEC') or die;

HTMLHelper::_('script', 'com_sso/script.js', array('version' => 'auto', 'relative' => true));
HTMLHelper::_('stylesheet', 'com_sso/style.css', array('version' => 'auto', 'relative' => true));

// Load the helper file
require_once JPATH_COMPONENT_ADMINISTRATOR . '/helpers/sso.php';

$controller = BaseController::getInstance('sso');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
