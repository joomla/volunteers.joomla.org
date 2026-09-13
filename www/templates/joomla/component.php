<?php
/**
 * Joomla.org site template
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;

/** @var \Joomla\CMS\Document\HtmlDocument $this */

// Declare the template as HTML5
$this->setHtml5(true);

// Add Stylesheets - if the site is in debug mode or has explicitly chosen to not use the CDN, load the local media
if (JDEBUG || !$this->params->get('useCdn', '1'))
{
    HTMLHelper::_('stylesheet', 'template.min.css', ['relative' => true, 'detectDebug' => (bool) JDEBUG, 'version' => '3.1.2-dev']);
}
else
{
	$this->addStyleSheet('https://cdn.joomla.org/template/css/template_3.1.1.min.css');
}

// Bootstrap 3 polyfill
if ($this->params->get('bs3Grid', '0'))
{
    HTMLHelper::_('stylesheet', 'bs3-polyfill.css', ['version' => 'auto', 'relative' => true, 'detectDebug' => false], []);
}

// Optional site specific CSS override
HTMLHelper::_('stylesheet', 'custom.css', ['version' => 'auto', 'relative' => true, 'detectDebug' => false], []);

// Load optional RTL Bootstrap CSS
if ($this->direction === 'rtl')
{
	HTMLHelper::_('stylesheet', 'template-rtl.min.css', ['version' => 'auto', 'relative' => true, 'detectDebug' => (bool) JDEBUG], []);

	// Optional support for custom RTL CSS rules
	HTMLHelper::_('stylesheet', 'custom-rtl.css', ['version' => 'auto', 'relative' => true, 'detectDebug' => false], []);
}

// Load Google Font
$this->addHeadLink('https://fonts.googleapis.com/css?family=Open+Sans&display=swap', 'preload', 'rel', ['as' => 'style']);

// Load the HTML5 shim with optional override
HTMLHelper::_('script', 'jui/html5.js', ['version' => 'auto', 'relative' => true, 'detectDebug' => (bool) JDEBUG, 'conditional' => 'lt IE 9'], []);

// Set template metadata
$this->setMetaData('viewport', 'width=device-width, initial-scale=1.0');
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<jdoc:include type="head" />
</head>
<body class="contentpane modal">
	<jdoc:include type="message" />
	<jdoc:include type="component" />
</body>
</html>
