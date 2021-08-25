<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_config
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

$this->name        = JText::_('COM_CONFIG_PERMISSION_SETTINGS');
$this->description = '';
$this->fieldsname  = 'permissions';
$this->formclass   = 'form-vertical';
$this->showlabel   = false;
echo JLayoutHelper::render('joomla.content.options_default', $this);
