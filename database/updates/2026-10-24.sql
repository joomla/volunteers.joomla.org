-- Replace the Joomla 3 search (com_search / mod_search) with Smart Search (com_finder / mod_finder).
-- Run after the Volunteers package with the Smart Search plugins is installed, then build the index:
--   php cli/joomla.php finder:index

-- Enable the Smart Search plugins of the Volunteers Portal and the content plugin that keeps the index up to date
UPDATE `pa763_extensions` SET `enabled` = 1
 WHERE `type` = 'plugin'
   AND ((`folder` = 'finder' AND `element` IN ('volunteers_departments', 'volunteers_reports', 'volunteers_teams', 'volunteers_volunteers'))
    OR (`folder` = 'content' AND `element` = 'finder'));

-- Point the hidden /search menu item to Smart Search
UPDATE `pa763_menu`
   SET `link`         = 'index.php?option=com_finder&view=search',
       `component_id` = (SELECT `extension_id` FROM `pa763_extensions` WHERE `type` = 'component' AND `element` = 'com_finder'),
       `params`       = '{"show_date_filters":"","show_advanced":"","expand_advanced":"","show_taxonomy":"","show_date":"","show_url":"","show_autosuggest":"","menu-anchor_title":"","menu-anchor_css":"","menu_image":"","menu_text":1,"menu_show":1,"page_title":"","show_page_heading":"","page_heading":"","pageclass_sfx":"","menu-meta_description":"","robots":""}'
 WHERE `client_id` = 0 AND `link` = 'index.php?option=com_search&view=search';

-- Unpublish the old search module in the header
UPDATE `pa763_modules` SET `published` = 0 WHERE `client_id` = 0 AND `module` = 'mod_search';

-- Add the Smart Search module to the header, the same as on community.joomla.org
INSERT INTO `pa763_modules` (`asset_id`, `title`, `note`, `content`, `ordering`, `position`, `checked_out`, `checked_out_time`, `publish_up`, `publish_down`, `published`, `module`, `access`, `showtitle`, `params`, `client_id`, `language`)
SELECT 0, 'Search', '', '', 1, 'position-0', NULL, NULL, NULL, NULL, 1, 'mod_finder', 1, 0,
       CONCAT('{"searchfilter":"","show_autosuggest":"1","show_advanced":"0","show_label":"0","alt_label":"","show_button":"0","opensearch":"1","opensearch_name":"","set_itemid":"', `id`, '","layout":"_:default","moduleclass_sfx":"","module_tag":"div","bootstrap_size":"0","header_tag":"h3","header_class":"","style":"0"}'),
       0, '*'
  FROM `pa763_menu`
 WHERE `client_id` = 0 AND `link` = 'index.php?option=com_finder&view=search' AND `alias` = 'search';

INSERT INTO `pa763_modules_menu` (`moduleid`, `menuid`)
SELECT `id`, 0 FROM `pa763_modules` WHERE `client_id` = 0 AND `module` = 'mod_finder' AND `position` = 'position-0';

-- After uninstalling the old search: the pkg_search package and the four "Search - Volunteers" plugins,
-- remove the administrator menu item the uninstaller leaves behind
DELETE FROM `pa763_menu` WHERE `client_id` = 1 AND `link` = 'index.php?option=com_search';

-- Show Smart Search in the Components menu again, the Joomla 3 row is in the old "menu" menutype and unpublished
UPDATE `pa763_menu`
   SET `menutype`  = 'main',
       `published` = 1,
       `img`       = 'class:search-plus'
 WHERE `client_id` = 1 AND `link` = 'index.php?option=com_finder';

-- Add the Index, Maps, Filters and Searches submenu items of Smart Search, the same as on a clean Joomla 6 install.
-- Rebuild the administrator menu items afterwards to set lft and rgt.
INSERT INTO `pa763_menu` (`menutype`, `title`, `alias`, `note`, `path`, `link`, `type`, `published`, `parent_id`, `level`, `component_id`, `browserNav`, `access`, `img`, `template_style_id`, `params`, `lft`, `rgt`, `home`, `language`, `client_id`)
SELECT 'main', `child`.`title`, `child`.`alias`, '', CONCAT('Smart Search/', `child`.`alias`), `child`.`link`, 'component', 1, `parent`.`id`, 2, `parent`.`component_id`, 0, 0, `child`.`img`, 0, '', 0, 0, 0, '*', 1
  FROM `pa763_menu` AS `parent`
  JOIN (SELECT 'com_finder_index' AS `title`, 'Smart-Search-Index' AS `alias`, 'index.php?option=com_finder&view=index' AS `link`, 'class:finder' AS `img`
        UNION ALL SELECT 'com_finder_maps', 'Smart-Search-Maps', 'index.php?option=com_finder&view=maps', 'class:finder-maps'
        UNION ALL SELECT 'com_finder_filters', 'Smart-Search-Filters', 'index.php?option=com_finder&view=filters', 'class:finder-filters'
        UNION ALL SELECT 'com_finder_searches', 'Smart-Search-Searches', 'index.php?option=com_finder&view=searches', 'class:finder-searches') AS `child`
 WHERE `parent`.`client_id` = 1 AND `parent`.`link` = 'index.php?option=com_finder'
   AND NOT EXISTS (SELECT 1 FROM `pa763_menu` AS `existing` WHERE `existing`.`client_id` = 1 AND `existing`.`link` = `child`.`link`);
