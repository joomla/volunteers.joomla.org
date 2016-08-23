<?php

/**
 * @package   	JCE
 * @copyright 	Copyright (c) 2009-2016 Ryan Demmer. All rights reserved.
 * @license   	GNU/GPL 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * JCE is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
defined('_JEXEC') or die('RESTRICTED');

require_once(WF_EDITOR_LIBRARIES . '/classes/manager.php');

final class WFFileBrowserPlugin extends WFMediaManager {
    /*
     * @var string
     */

    protected $_filetypes = 'word=doc,docx;powerpoint=ppt,pptx;excel=xls,xlsx;image=gif,jpeg,jpg,png;acrobat=pdf;archive=zip,tar,gz;flash=swf;winrar=rar;quicktime=mov,mp4,qt;windowsmedia=wmv,asx,asf,avi;audio=wav,mp3,aiff;openoffice=odt,odg,odp,ods,odf;text=rtf,txt,csv';

    /**
     * @access	protected
     */
    public function __construct() {
        parent::__construct();

        $browser = $this->getBrowser();

        if (JRequest::getWord('type', 'file') == 'file') {
            // Add all files
            $browser->addFileTypes(array('WF_FILEGROUP_ALL' => '*.*'));
        } else {
            $browser->setFileTypes('images=jpg,jpeg,png,gif');
        }

        $filter = JRequest::getString('filter');

        if ($filter) {
            if ($filter === 'images') {
                $filetypes = 'images=jpg,jpeg,png,gif';
            } else if ($filter === 'media') {
                $filetypes = 'windowsmedia=avi,wmv,wm,asf,asx,wmx,wvx;quicktime=mov,qt,mpg,mpeg,m4a;flash=swf;shockwave=dcr;real=rm,ra,ram;divx=divx;video=mp4,ogv,ogg,webm,flv,f4v;audio=mp3,ogg,wav;silverlight=xap';
            } else if ($filter === 'html') {
                $filetypes = 'html=html,htm,txt';
            } else {
                // custom filter list, eg: jpg,jpeg,png,pdf
                if (strpos($filter, ',') !== false) {
                  $filetypes = 'files=' . $filter;
                } else {
                  $filetypes = $this->get('_filetypes');
                }
            }

            $browser->setFileTypes($filetypes);
        }
        // remove insert button
        $browser->removeButton('file', 'insert');
    }

    /**
     * Display the plugin
     * @access public
     */
    public function display() {
        parent::display();

        $document = WFDocument::getInstance();
        $settings = $this->getSettings();

        $document->addScript(array('browser'), 'plugins');

        if ($document->get('standalone') == 1) {
            $document->addScript(array('browser'), 'component');

            $element = JRequest::getCmd('element', JRequest::getCmd('fieldid', ''));

            $options = array(
                'plugin' => array(
                    'root' => JURI::root(),
                    'site' => JURI::base(true) . '/'
                ),
                'manager' => $settings,
                'element' => $element
            );

            $document->addScriptDeclaration('jQuery(document).ready(function($){$.WFBrowserWidget.init(' . json_encode($options) . ');});');

        } else {
            $document->addScriptDeclaration('BrowserDialog.settings=' . json_encode($settings) . ';');
        }
    }
}

?>
