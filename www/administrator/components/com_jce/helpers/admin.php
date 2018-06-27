<?php

defined('_JEXEC') or die;

/**
 * Admin helper.
 *
 * @package     WF
 * @subpackage  com_jce
 * @since       3.0
 */
class WfHelperAdmin {

    /**
     * Configure the Submenu links.
     *
     * @param   string  $vName  The view name.
     *
     * @return  void
     *
     * @since   3.0
     */
    public static function addSubmenu($vName) {

        $uri = (string) JUri::getInstance();
        $return = urlencode(base64_encode($uri));

        JHtmlSidebar::addEntry(
                JText::_('Options'), 'index.php?option=com_config&amp;view=component&amp;component=com_jce&amp;return=' . $return
        );
        JHtmlSidebar::addEntry(
                JText::_('Edit Profiles'), 'index.php?option=com_jce&view=profiles', $vName == 'profiles'
        );
    }

}
