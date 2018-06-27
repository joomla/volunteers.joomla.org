<?php

/**
 * @package   	JCE
 * @copyright 	Copyright (c) 2009-2013 Ryan Demmer. All rights reserved.
 * @license   	GNU/GPL 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * JCE is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
defined('JPATH_PLATFORM') or die;

class JceViewProfile extends JViewLegacy {

    protected $state;
    protected $item;
    protected $form;

    /**
     * Display the view
     */
    public function display($tpl = null) {
        $this->state    = $this->get('State');
        $this->item     = $this->get('Item');
        $this->form     = $this->get('Form');
        
        $this->plugins      = $this->get('Plugins');
        $this->rows         = $this->get('Rows');
        $this->available    = $this->get('AvailableButtons');

        // load language files
        $language = JFactory::getLanguage();
        $language->load('com_jce', JPATH_SITE);
        $language->load('com_jce_pro', JPATH_SITE);
        
        // set JLayoutHelper base path
        JLayoutHelper::$defaultBasePath = JPATH_COMPONENT_ADMINISTRATOR;

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            JError::raiseError(500, implode("\n", $errors));
            return false;
        }
        
        JHtml::_('behavior.modal', 'a.modal_users');
        JHtml::_('jquery.ui', array('core', 'sortable'));

        $this->addToolbar();
        parent::display($tpl);
        
        $document = JFactory::getDocument();
        $document->addStyleSheet('components/com_jce/media/css/profile.min.css');
        $document->addScript('components/com_jce/media/js/profile.min.js');
    }

    /**
     * Add the page title and toolbar.
     *
     * @since   3.0
     */
    protected function addToolbar() {
        JFactory::getApplication()->input->set('hidemainmenu', true);

        $user       = JFactory::getUser();
        $isNew      = ($this->item->id == 0);

        $checkedOut	= !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));

        JToolbarHelper::title(JText::_('COM_JCE_PROFILE_EDIT'), 'user');

        // If not checked out, can save the item.
        if (!$checkedOut && $user->authorise('core.create', 'com_jce')) {
            JToolbarHelper::apply('profile.apply');
            JToolbarHelper::save('profile.save');
        }
        if (!$checkedOut && $user->authorise('core.create', 'com_jce')) {
            JToolbarHelper::save2new('profile.save2new');
        }
        // If an existing item, can save to a copy.
        if (!$isNew && $user->authorise('core.create', 'com_jce')) {
            JToolbarHelper::save2copy('profile.save2copy');
        }
        if (empty($this->item->id)) {
            JToolbarHelper::cancel('profile.cancel');
        } else {
            JToolbarHelper::cancel('profile.cancel', 'JTOOLBAR_CLOSE');
        }

        JToolbarHelper::divider();
        JToolbarHelper::help('COM_JCE_PROFILE_EDIT');
    }

}
