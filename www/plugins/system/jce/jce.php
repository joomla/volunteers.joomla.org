<?php

/**
 * @copyright   Copyright (C) 2015 Ryan Demmer. All rights reserved
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved
 * @license     GNU General Public License version 2 or later
 */
defined('JPATH_BASE') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;

/**
 * JCE.
 *
 * @since       2.5.5
 */
class PlgSystemJce extends CMSPlugin
{
    public function onPlgSystemJceContentPrepareForm($form, $data)
    {
        return $this->onContentPrepareForm($form, $data);
    }

    private function getMediaRedirectUrl()
    {
        $app = Factory::getApplication();

        require_once JPATH_ADMINISTRATOR . '/components/com_jce/helpers/browser.php';

        $id = $app->input->get('fieldid', '');
        $mediatype = $app->input->getVar('mediatype', $app->input->getVar('view', 'images'));
        $context = $app->input->getVar('context', '');
        $plugin = $app->input->getCmd('plugin', '');

        $options = WFBrowserHelper::getMediaFieldOptions(array(
            'element' => $id,
            'converted' => true,
            'mediatype' => $mediatype,
            'context' => $context,
            'plugin' => $plugin,
        ));

        if (empty($options['url'])) {
            return false;
        }

        return $options['url'];
    }

    private function redirectMedia()
    {
        $url = $this->getMediaRedirectUrl();

        if ($url) {
            Factory::getApplication()->redirect($url);
        }
    }

    private function isEditorEnabled()
    {
        return ComponentHelper::isEnabled('com_jce') && PluginHelper::isEnabled('editors', 'jce');
    }

    private function canRedirectMedia()
    {
        $app = Factory::getApplication();
        $params = ComponentHelper::getParams('com_jce');

        // must have fieldid
        if (!$app->input->get('fieldid')) {
            return false;
        }

        // jce converted mediafield
        if ($app->input->getCmd('option') == 'com_jce' && $app->input->getCmd('task') == 'mediafield.display') {
            return true;
        }

        if ((bool) $params->get('replace_media_manager', 1) == true) {
            // flexi-content mediafield
            if ($app->input->getCmd('option') == 'com_media' && $app->input->getCmd('asset') == 'com_flexicontent') {
                return true;
            }
        }

        return false;
    }

    public function onAfterRoute()
    {
        if (false == $this->isEditorEnabled()) {
            return false;
        }

        if ($this->canRedirectMedia() && $this->isEditorEnabled()) {
            // redirect to file browser
            $this->redirectMedia();
        }

        /*$app = Factory::getApplication();
        $params = ComponentHelper::getParams('com_jce');

        // flexi-content mediafield
        if ($app->input->getCmd('option') == 'com_media') {
            if ((bool) $params->get('replace_media_manager', 1) == true) {
                $vars = $app->input->getArray();

                $valid = true;

                foreach($vars as $key => $value) {
                    if ($key == 'task' || strpos('api', $key) !== false) {
                        $valid = false;
                    }
                }

                if ($valid) {
                    Factory::getApplication()->redirect('index.php?option=com_jce&view=browser');
                }
            }
        }*/
    }

    public function onAfterDispatch()
    {
        $app = Factory::getApplication();

        // only in "site"
        if ($app->getClientId() !== 0) {
            return;
        }

        $document = Factory::getDocument();

        // only if enabled
        if ((int) $this->params->get('column_styles', 1)) {
            $hash = md5_file(__DIR__ . '/css/content.css');
            $document->addStyleSheet(JURI::root(true) . '/plugins/system/jce/css/content.css?' . $hash);
        }
    }

    public function onWfContentPreview($context, &$article, &$params, $page)
    {
        $article->text = '<style type="text/css">@import url("' . JURI::root(true) . '/plugins/system/jce/css/content.css");</style>' . $article->text;
    }

    /**
     * adds additional fields to the user editing form.
     *
     * @param JForm $form The form to be altered
     * @param mixed $data The associated data for the form
     *
     * @return bool
     *
     * @since   2.5.20
     */
    public function onContentPrepareForm($form, $data)
    {
        $app = Factory::getApplication();

        $version = new Joomla\CMS\Version();

        // Joomla 3.10 or later...
        if (!$version->isCompatible('3.9')) {
            return true;
        }

        if (!($form instanceof Form)) {
            $this->_subject->setError('JERROR_NOT_A_FORM');
            return false;
        }

        // editor not enabled
        if (false == $this->isEditorEnabled()) {
            return true;
        }

        // File Browser not enabled
        if (false == $this->getMediaRedirectUrl()) {
            return true;
        }

        $params = ComponentHelper::getParams('com_jce');

        $hasMedia = false;
        $fields = $form->getFieldset();

        // should the Joomla Media field be converted?
        $replace_media_manager = (bool) $params->get('replace_media_manager', 1);

        foreach ($fields as $field) {
            if (method_exists($field, 'getAttribute') === false) {
                continue;
            }

            $name = $field->getAttribute('name');

            // avoid processing twice
            if ($form->getFieldAttribute($name, 'class') && strpos($form->getFieldAttribute($name, 'class'), 'wf-media-input') !== false) {
                continue;
            }

            $type = $field->getAttribute('type');

            if ($type) {
                // joomla media field and flexi-content converted media field
                if (strtolower($type) == 'media' || strtolower($type) == 'fcmedia') {

                    // media replacement disabled, skip...
                    if ($replace_media_manager == false) {
                        continue;
                    }

                    $group = (string) $field->group;
                    $form->setFieldAttribute($name, 'type', 'mediajce', $group);
                    $form->setFieldAttribute($name, 'converted', '1', $group);
                    $hasMedia = true;
                }

                // jce media field
                if (strtolower($type) == 'mediajce' || strtolower($type) == 'extendedmedia') {
                    $hasMedia = true;
                }
            }
        }

        // form has a media field
        if ($hasMedia) {
            $option = $app->input->getCmd('option');
            $component = ComponentHelper::getComponent($option);

            Factory::getDocument()->addScriptOptions('plg_system_jce', array(
                'replace_media' => $replace_media_manager,
                'context' => $component->id,
            ), true);

            $form->addFieldPath(JPATH_PLUGINS . '/fields/mediajce/fields');

            // Include jQuery
            HTMLHelper::_('jquery.framework');

            $document = JFactory::getDocument();
            $document->addScript(JURI::root(true) . '/plugins/system/jce/js/media.js', array('version' => 'auto'));
            $document->addStyleSheet(JURI::root(true) . '/plugins/system/jce/css/media.css', array('version' => 'auto'));
        }

        return true;
    }

    public function onBeforeWfEditorLoad()
    {
        $items = glob(__DIR__ . '/templates/*.php');

        $app = Factory::getApplication();

        if (method_exists($app, 'getDispatcher')) {
            $dispatcher = Factory::getApplication()->getDispatcher();
        } else {
            $dispatcher = JEventDispatcher::getInstance();
        }

        foreach ($items as $item) {
            $name = basename($item, '.php');

            $className = 'WfTemplate' . ucfirst($name);

            require_once $item;

            if (class_exists($className)) {
                // Instantiate and register the event
                $plugin = new $className($dispatcher);

                if ($plugin instanceof \Joomla\CMS\Extension\PluginInterface) {
                    $plugin->registerListeners();
                }
            }
        }
    }

    public function onWfPluginInit($instance)
    {
        $app = Factory::getApplication();
        $user = Factory::getUser();

        // set mediatype values for Template Manager parameters
        if ($app->input->getCmd('plugin') == 'browser.templatemanager') {

            // only in "admin"
            if ($app->getClientId() !== 1) {
                return;
            }

            // restrict to admin with component manage access
            if (!$user->authorise('core.manage', 'com_jce')) {
                return false;
            }

            // check for element and standalone should indicate mediafield
            if ($app->input->getVar('element') && $app->input->getInt('standalone')) {
                $mediatype = $app->input->getVar('mediatype');

                if (!$mediatype) {
                    return false;
                }

                $accept = $instance->getParam('templatemanager.extensions', '');

                if ($accept) {
                    $instance->setFileTypes($accept);
                    $accept = $instance->getFileTypes();
                    $mediatype = implode(',', array_intersect(explode(',', $mediatype), $accept));
                }

                $instance->setFileTypes($mediatype);
            }
        }
    }
}
