<?php

/**
 * @package       JCE
 * @copyright     Copyright (c) 2009-2014 Ryan Demmer. All rights reserved.
 * @license       GNU/GPL 3 - http://www.gnu.org/copyleft/gpl.html
 * JCE is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
defined('JPATH_PLATFORM') or die;

// load app class
require_once JPATH_SITE . '/libraries/wfeditor/app.php';

class JceControllerPlugin extends JControllerLegacy
{
    public function execute($task)
    {        
        $app = Wf\Application\Application::getInstance();

        // check for session token
        $app->platform->checkToken() or die;

        // check a valid profile exists
        $app->getProfile() or die;

        // load language files
        $language = JFactory::getLanguage();
        $language->load('com_jce', JPATH_SITE);
        $language->load('lib_wfeditor', JPATH_SITE);

        $plugin = new Wf\Application\Plugin();

        if (strpos($task, '.') !== false) {
            list($name, $task) = explode('.', $task);
        }

        // default to execute if task is not available
        if (is_callable(array($plugin, $task)) === false) {
            $task = 'execute';
        }

        $plugin->$task();

        jexit();
    }
}