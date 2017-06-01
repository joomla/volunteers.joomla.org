<?php
/**
 * @version		$Id: email.php 20196 2011-03-04 02:40:25Z mrichey $
 * @package		plg_auth_email
 * @copyright	Copyright (C) 2005 - 2011 Michael Richey. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

class plgSystemEmail extends JPlugin
{
	function onAfterRoute()
	{
            $app = JFactory::getApplication();
            if($app->isAdmin()) return;
            $component = $app->input->getCmd('option');
            if($component != 'com_users') return;
            $task = $app->input->getCmd('task');
            if($task != 'reset.confirm') return;
            
            // ok, at this point we know that the form has been submitted.
            $jform = $app->input->get('jform',array(),'array');
            if(count($jform) && preg_match('/@/',$jform['username'])) {
                $db = JFactory::getDbo();
                $query = $db->getQuery(true);
                $query->select('username')->from('#__users')->where('email = '.$db->quote($jform['username']));
                $db->setQuery($query);
                $username = $db->loadObjectList();
                if(count($username)) {
                    $jform['username']=$username[0]->username;
                    $app->input->set('jform',$jform);
                }
            }
        }
}
