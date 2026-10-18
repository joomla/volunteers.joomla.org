<?php

/**
 * @package        JED
 *
 * @copyright  (C) 2023 Open Source Matters, Inc.  <https://www.joomla.org>
 * @license        GNU General Public License version 2 or later; see LICENSE.txt
 */
/** @var \Joomla\Component\Volunteers\Administrator\View\Setupdemo\HtmlView $this */
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Component\Menus\Administrator\Table\MenuTable;
use Joomla\Component\Menus\Administrator\Table\MenuTypeTable;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects
$wa = $this->getDocument()->
getWebAssetManager();
$wa->useScript('keepalive')->useScript('form.validate');
HTMLHelper::_('bootstrap.tooltip');
?>
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <p>Clicking GO below will create a new front end menu called jvpdemo and populate it with menu links to
                Volunteers Component</p>
        </div>
    </div>
</div>

<div class="container">
    <div class="row">
        <div class="col-md-12"><a href="<?php
            echo $_SERVER['REQUEST_URI'] . '&task=GO'; ?>"
            <button class="btn btn-primary" type="button">Go</button>
            </a></div>
    </div>
</div>
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <?php


            if ($this->task === "GO") {
                try {
                    $db    = Factory::getContainer()->get('DatabaseDriver');
                    $query = $db->getQuery(true)->select('extension_id')->from($db->quoteName('#__extensions'))->where('name = "com_volunteers"');
                    $db->setQuery($query);
                    $extension_id = $db->loadResult();
                    echo  '<br/>' . 'Extension com_volunteers found - ' . $extension_id . '<br/>';
                    $query = $db->getQuery(true)->select('extension_id')->from($db->quoteName('#__extensions'))->where('name = "com_login"');
                    $db->setQuery($query);
                    $login_component_id = $db->loadResult();
                    echo '<br/>' . 'Extension com_login found - ' . $login_component_id . '<br/>';



                    $mtt              = new MenuTypeTable($db);
                    $mtt->menutype    = 'jvpdemo';
                    $mtt->title       = 'Joomla Volunteer Portal Demo Menu';
                    $mtt->description = '';
                    $mtt->client_id   = 0;
                    $mtt->store();
                    echo "<br/>Created jvpdemo Menu Type<br/><br/>";
                    $mi          = new MenuTable($db);
                    $menuitems[] = [
                        'title'        => 'Home',
                        'alias'        => 'homejvp',
                        'path'         => 'homejvp',
                        'link'         => 'index.php?option=com_volunteers&view=home',
                        'component_id' => $extension_id,
                        'params'       => '{"menu-anchor_title":"","menu-anchor_css":"","menu_icon_css":"","menu_image":"","menu_image_css":"","menu_text":1,"menu_show":1,"page_title":"","show_page_heading":"","page_heading":"","pageclass_sfx":"","menu-meta_description":"","robots":""}'
                    ];
                    $menuitems[] = [
                        'title'        => 'Board of Directors',
                        'alias'        => 'board-of-directors',
                        'path'         => 'board-of-directors',
                        'link'         => 'index.php?option=com_volunteers&view=board&id=3',
                        'component_id' => $extension_id,
                        'params'       => '{"menu-anchor_title":"","menu-anchor_css":"","menu_icon_css":"","menu_image":"","menu_image_css":"","menu_text":1,"menu_show":1,"page_title":"","show_page_heading":"","page_heading":"","pageclass_sfx":"","menu-meta_description":"","robots":""}'
                    ];
                    $menuitems[] = [
                        'title'        => 'Departments',
                        'alias'        => 'departments',
                        'path'         => 'departments',
                        'link'         => 'index.php?option=com_volunteers&view=departments',
                        'component_id' => $extension_id,
                        'params'       => '{"menu-anchor_title":"","menu-anchor_css":"","menu_icon_css":"","menu_image":"","menu_image_css":"","menu_text":1,"menu_show":1,"page_title":"","show_page_heading":"","page_heading":"","pageclass_sfx":"","menu-meta_description":"","robots":""}'
                    ];
                    $menuitems[] = [
                        'title'        => 'Teams',
                        'alias'        => 'teams',
                        'path'         => 'teams',
                        'link'         => 'index.php?option=com_volunteers&view=teams&id=50',
                        'component_id' => $extension_id,
                        'params'       => '{"menu-anchor_title":"","menu-anchor_css":"","menu_icon_css":"","menu_image":"","menu_image_css":"","menu_text":1,"menu_show":1,"page_title":"","show_page_heading":"","page_heading":"","pageclass_sfx":"","menu-meta_description":"","robots":""}'
                    ];
                    $menuitems[] = [
                        'title'        => 'Groups',
                        'alias'        => 'groups',
                        'path'         => 'groups',
                        'link'         => 'index.php?option=com_volunteers&view=teams&id=58',
                        'component_id' => $extension_id,
                        'params'       => '{"menu-anchor_title":"","menu-anchor_css":"","menu_icon_css":"","menu_image":"","menu_image_css":"","menu_text":1,"menu_show":1,"page_title":"","show_page_heading":"","page_heading":"","pageclass_sfx":"","menu-meta_description":"","robots":""}'
                    ];
                    $menuitems[] = [
                        'title'        => 'Joomlers',
                        'alias'        => 'joomlers',
                        'path'         => 'joomlers',
                        'link'         => 'index.php?option=com_volunteers&view=volunteers',
                        'component_id' => $extension_id,
                        'params'       => '{"menu-anchor_title":"","menu-anchor_css":"","menu_icon_css":"","menu_image":"","menu_image_css":"","menu_text":1,"menu_show":1,"page_title":"","show_page_heading":"","page_heading":"","pageclass_sfx":"","menu-meta_description":"","robots":""}'
                    ];
                    $menuitems[] = [
                        'title'        => 'Reports',
                        'alias'        => 'reports',
                        'path'         => 'reports',
                        'link'         => 'index.php?option=com_volunteers&view=reports',
                        'component_id' => $extension_id,
                        'params'       => '{"menu-anchor_title":"","menu-anchor_css":"","menu_icon_css":"","menu_image":"","menu_image_css":"","menu_text":1,"menu_show":1,"page_title":"","show_page_heading":"","page_heading":"","pageclass_sfx":"","menu-meta_description":"","robots":""}'
                    ];
                    $menuitems[] = [
                        'title'        => 'Help Wanted',
                        'alias'        => 'help-wanted',
                        'path'         => 'help-wanted',
                        'link'         => 'index.php?option=com_volunteers&view=roles',
                        'component_id' => $extension_id,
                        'params'       => '{"menu-anchor_title":"","menu-anchor_css":"","menu_icon_css":"","menu_image":"","menu_image_css":"","menu_text":1,"menu_show":1,"page_title":"","show_page_heading":"","page_heading":"","pageclass_sfx":"","menu-meta_description":"","robots":""}'
                    ];
                    $menuitems[] = [
                        'title'        => 'FAQs',
                        'alias'        => 'faqs',
                        'path'         => 'faqs',
                        'link'         => 'index.php?option=com_volunteers&view=faq',
                        'component_id' => $extension_id,
                        'params'       => '{"menu-anchor_title":"","menu-anchor_css":"","menu_icon_css":"","menu_image":"","menu_image_css":"","menu_text":1,"menu_show":1,"page_title":"","show_page_heading":"","page_heading":"","pageclass_sfx":"","menu-meta_description":"","robots":""}'
                    ];
                    $menuitems[] = [
                        'title'        => 'Register',
                        'alias'        => 'register',
                        'path'         => 'register',
                        'link'         => 'index.php?option=com_volunteers&view=registration',
                        'component_id' => $extension_id,
                        'params'       => '{"menu-anchor_title":"","menu-anchor_css":"","menu_icon_css":"","menu_image":"","menu_image_css":"","menu_text":1,"menu_show":1,"page_title":"","show_page_heading":"","page_heading":"","pageclass_sfx":"","menu-meta_description":"","robots":""}'
                    ];
                    $menuitems[] = [
                        'title'        => 'Login',
                        'alias'        => 'login',
                        'path'         => 'login',
                        'link'         => 'index.php?option=com_users&view=login',
                        'component_id' => $login_component_id,
                        'params'       => '{"loginredirectchoice":"1","login_redirect_url":"","login_redirect_menuitem":"","logindescription_show":"1",' . '"login_description":"","login_image":"","login_image_alt":"","logoutredirectchoice":"1","logout_redirect_url":"",' . '"logout_redirect_menuitem":"","logoutdescription_show":"1","logout_description":"","logout_image":"","logout_image_alt":"",' . '"menu-anchor_title":"","menu-anchor_css":"","menu_icon_css":"","menu_image":"","menu_image_css":"","menu_text":1,"menu_show":1,' . '"page_title":"","show_page_heading":"","page_heading":"","pageclass_sfx":"","menu-meta_description":"","robots":""}'
                    ];
                    foreach ($menuitems as $m) {
                        $mi               = new MenuTable($db);
                        $mi->menutype     = 'jvpdemo';
                        $mi->title        = htmlspecialchars_decode($m['title']);
                        $mi->alias        = $m['alias'] . '-jvp';
                        $mi->path         = $m['path'];
                        $mi->link         = $m['link'];
                        $mi->params       = $m['params'];
                        $mi->type         = "component";
                        $mi->published    = 1;
                        $mi->parent_id    = 1;
                        $mi->client_id    = 0;
                        $mi->level        = 1;
                        $mi->component_id = $m['component_id'];
                        $mi->setLocation(1, 'last-child');
                        $mi->img               = '';
                        $mi->language          = "*";
                        $mi->note              = '';
                        $mi->browserNav        = 0;
                        $mi->template_style_id = 0;
                        $mi->home              = 0;
                        if ($mi->store()) {
                            echo "Successfully Created Menu Item - " . $m['title'] . '<br/>' . $mi->getError() . '<br/>';
                        } else {
                            echo "Failed to create Menu Item - " . $m['title'] . '<br/>' . $mi->getError() . '<br/>';
                        }
                    }
                } catch (Exception $e) {
                    throw new Exception('Something went really wrong. ' . $e->getMessage(), 500);
                }
            }

            ?>

        </div>
    </div>
</div>
