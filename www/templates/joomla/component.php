<?php
/**
 * Joomla.org site template
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/** @var JDocumentHtml $this */

// Declare the template as HTML5
$this->setHtml5(true);

// Add Stylesheets - if the site is in debug mode, load the local media, otherwise pull from the CDN
if (JDEBUG)
{
	JHtml::_('stylesheet', 'template.min.css', [], true, false, false, (bool) JDEBUG);
}
else
{
	$this->addStyleSheet('https://cdn.joomla.org/template/css/template_2.0.0.min.css');
}

// Optional site specific CSS override
JHtml::_('stylesheet', 'custom.css', [], true, false, false, false);

// Load optional RTL Bootstrap CSS
JHtml::_('bootstrap.loadCss', false, $this->direction);

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

// Get the path for the HTML5 shim with optional override
$html5Shim = JHtml::_('script', 'jui/html5.js', false, true, true, false, (bool) JDEBUG);

// Set template metadata
$this->setMetaData('viewport', 'width=device-width, initial-scale=1.0');

?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<jdoc:include type="head" />
	<!--[if lt IE 9]><script src="<?php echo $html5Shim ?>"></script><![endif]-->
</head>
<body class="contentpane modal">
	<jdoc:include type="message" />
	<jdoc:include type="component" />
</body>
</html>
