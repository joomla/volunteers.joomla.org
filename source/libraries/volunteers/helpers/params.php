<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

class VolunteersHelperParams
{
	public static function getParam($key, $default = null)
	{
		static $params = null;

		if(!is_object($params)) 
		{
			jimport('joomla.application.component.helper');
			$params = JComponentHelper::getParams('com_volunteers');
		}
		
		return $params->get($key, $default);
	}
}