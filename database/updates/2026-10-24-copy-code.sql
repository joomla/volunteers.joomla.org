-- Replace the {copyme} tags of the Copy Me plugin on the Banners page with code blocks.
-- The joomla template adds a copy button to every <pre class="copy-code"> block.
-- Run before uninstalling the Copy Me plugin (plg_content_copyme).

UPDATE `#__content`
   SET `introtext` = REPLACE(REPLACE(`introtext`, '<p>{copyme}', '<pre class="copy-code">'), '{/copyme}</p>', '</pre>')
 WHERE `alias` = 'spread-the-joomla-love'
   AND `introtext` LIKE '%{copyme}%';
