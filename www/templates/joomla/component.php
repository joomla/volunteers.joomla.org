<?php
/**
 * Joomla.org site template
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/** @var JDocumentHtml $this */

// Declare the template as HTML5
$this->setHtml5(true);

// Add Stylesheets - if the site is in debug mode or has explicitly chosen to not use the CDN, load the local media
if (JDEBUG || !$this->params->get('useCdn'))
{
    JHtml::_('stylesheet', 'template.min.css', ['relative' => true, 'detectDebug' => (bool) JDEBUG, 'version' => '2.2.0']);
}
else
{
	$this->addStyleSheet('https://cdn.joomla.org/template/css/template_2.2.0.min.css');
}

// Bootstrap 3 polyfill
if ($this->params->get('bs3Grid', '0'))
{
    JHtml::_('stylesheet', 'bs3-polyfill.css', ['version' => 'auto', 'relative' => true, 'detectDebug' => false], []);
}

// Optional site specific CSS override
JHtml::_('stylesheet', 'custom.css', ['version' => 'auto', 'relative' => true, 'detectDebug' => false], []);

// Load optional RTL Bootstrap CSS
if ($this->direction === 'rtl')
{
	JHtml::_('stylesheet', 'template-rtl.min.css', ['version' => 'auto', 'relative' => true, 'detectDebug' => (bool) JDEBUG], []);

	// Optional support for custom RTL CSS rules
	JHtml::_('stylesheet', 'custom-rtl.css', ['version' => 'auto', 'relative' => true, 'detectDebug' => false], []);
}

// Load Google Font if defined
if ($this->params->get('googleFont'))
{
	$escapedFontName = str_replace('+', ' ', $this->params->get('googleFontName'));
	$this->addStyleSheet('https://fonts.googleapis.com/css?family=' . $this->params->get('googleFontName'));
	$this->addStyleDeclaration(<<<CSS
		h1, h2, h3, h4, h5, h6, .site-title {
			font-family: '$escapedFontName', sans-serif;
		}
CSS
	);
}

// Load the HTML5 shim with optional override
JHtml::_('script', 'jui/html5.js', ['version' => 'auto', 'relative' => true, 'detectDebug' => (bool) JDEBUG, 'conditional' => 'lt IE 9'], []);

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
