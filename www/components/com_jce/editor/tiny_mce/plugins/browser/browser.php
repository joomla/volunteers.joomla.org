<?php

/**
 * @copyright     Copyright (c) 2009-2021 Ryan Demmer. All rights reserved
 * @license       GNU/GPL 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * JCE is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses
 */
defined('JPATH_PLATFORM') or die;

require_once WF_EDITOR_LIBRARIES . '/classes/manager.php';

class WFBrowserPlugin extends WFMediaManager
{
    /*
     * @var string
     */
    protected $_filetypes = 'doc,docx,dot,dotx,ppt,pps,pptx,ppsx,xls,xlsx,gif,jpeg,jpg,png,webp,apng,avif,pdf,zip,tar,gz,swf,rar,mov,mp4,m4a,flv,mkv,webm,ogg,ogv,qt,wmv,asx,asf,avi,wav,mp3,aiff,oga,odt,odg,odp,ods,odf,rtf,txt,csv';

    public function __construct($config = array())
    {
        $app = JFactory::getApplication();

        $config = array(
            'layout' => 'browser',
            'can_edit_images' => 1,
            'show_view_mode' => 1,
        );

        parent::__construct($config);

        // get mediatype from xml
        $mediatype = $app->input->getString('mediatype', $app->input->getString('filter', 'files'));

        if ($mediatype) {
            // clean and lowercase filter value
            $mediatype = (string) preg_replace('/[^\w_,]/i', '', strtolower($mediatype));

            // get filetypes from params
            $filetypes = $this->getParam('browser.extensions', $this->get('_filetypes'));

            // get file browser reference
            $browser = $this->getFileBrowser();

            // add upload event
            $browser->addEvent('onUpload', array($this, 'onUpload'));

            // map to comma seperated list
            $filetypes = $browser->getFileTypes('list', $filetypes);

            $map = array(
                'images' => 'jpg,jpeg,png,apng,gif,webp,avif',
                'media' => 'avi,wmv,wm,asf,asx,wmx,wvx,mov,qt,mpg,mpeg,m4a,m4v,swf,dcr,rm,ra,ram,divx,mp4,ogv,ogg,webm,flv,f4v,mp3,ogg,wav,xap',
                'files' => $filetypes,
            );

            // add svg support to images if it is allowed in filetypes
            if (in_array('svg', explode(',', $filetypes))) {
                $map['images'] .= ',svg';
            }

            if (array_key_exists($mediatype, $map)) {
                $filetypes = $map[$mediatype];
            } else {
                $filetypes = $mediatype;
            }

            // set updated filetypes
            $browser->setFileTypes($filetypes);

            $properties = array();

            // get existing upload values
            $upload = $browser->get('upload', array());
            $upload['filetypes'] = $filetypes;

            // update upload filetypes
            $properties['upload'] = $upload;

            $browser->setProperties($properties);
        }
    }

    /**
     * Display the plugin.
     */
    public function display()
    {
        parent::display();

        $app = JFactory::getApplication();

        $document = WFDocument::getInstance();
        $layout = $app->input->getCmd('layout', 'plugin');

        // update some document variables
        $document->setName('browser');
        $document->setTitle(JText::_('WF_BROWSER_TITLE'));

        if ($document->get('standalone') == 1) {
            if ($layout === 'plugin') {
                $document->addScript(array('window.min'), 'plugins');

                $callback = $app->input->getCmd('callback', '');
                $element = $app->input->getCmd('fieldid', '');

                // Joomla 4 field variable not converted
                if (!$element || $element === 'field-media-id') {
                    $element = $app->input->getCmd('element', '');
                }

                $settings = array(
                    'site_url' => JURI::base(true) . '/',
                    'document_base_url' => JURI::root(),
                    'language' => WFLanguage::getCode(),
                    'element' => $element,
                    'token' => JSession::getFormToken(),
                );

                if ($callback) {
                    $settings['callback'] = $callback;
                }

                $document->addScriptDeclaration('tinymce.settings=' . json_encode($settings) . ';');
            }

            $document->addScript(array('popup.min'), 'plugins');
            $document->addStyleSheet(array('browser.min'), 'plugins');
        }

        if ($layout === 'plugin') {
            $document->addScript(array('browser'), 'plugins');
        }
    }

    public function onUpload($file, $relative = '')
    {
        parent::onUpload($file, $relative);

        $app = JFactory::getApplication();

        // inline upload
        if ($app->input->getInt('inline', 0) === 1) {
            $result = array(
                'file' => $relative,
                'name' => basename($file),
            );

            return $result;
        }

        return array();
    }
}
