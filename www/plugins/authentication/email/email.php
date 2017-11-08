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

class plgAuthenticationEmail extends JPlugin {
    private $_paj;
    
    public function __construct(&$subject, $config = array()) {
	    parent::__construct($subject, $config = array());
            require_once JPATH_PLUGINS . '/authentication/joomla/joomla.php';
	    $paj = JPluginHelper::getPlugin('authentication','joomla');
	    $this->_paj = new PlgAuthenticationJoomla($subject,(array)$paj);
    }

    /**
     * This method should handle any authentication and report back to the subject
     */
    function onUserAuthenticate(&$credentials, $options, &$response) {
        // Get a database object
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('id, username, password');
        $query->from('#__users');
        $query->where('email LIKE ' . $db->Quote($credentials['username']));

        $db->setQuery($query);
        $result = $db->loadObject();

        if ($result) {
            // why mess with re-creating authentication - just use the system.
            $credentials['username'] = $result->username;
            $this->_paj->onUserAuthenticate($credentials, $options, $response);
        } else {
            $response->status = JAuthentication::STATUS_FAILURE;
            $response->error_message = JText::_('JGLOBAL_AUTH_INVALID_PASS');            
        }
    }

}
