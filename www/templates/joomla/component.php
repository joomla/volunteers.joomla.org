<?php
/**
 * Joomla.org site template
 *
 * @copyright   Copyright (C) 2005 - 2023 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;

/** @var \Joomla\CMS\Document\HtmlDocument $this */

// Declare the template as HTML5
$this->setHtml5(true);

$wa = $this->getWebAssetManager();

// Add Bootstrap Javascript Frameworks
HTMLHelper::_('bootstrap.collapse');
HTMLHelper::_('bootstrap.dropdown');

// Load template stylesheet
$wa->useStyle('template.joomla.custom.' . ($this->direction === 'rtl' ? 'rtl' : 'ltr'));

// Override 'template.active' asset to set correct ltr/rtl dependency and with CDN Dependency
$wa->registerStyle('template.active', '', [], [], [((!JDEBUG && $this->params->get('useCdn', '1')) ? 'cdn.' : '') . 'template.joomla.' . ($this->direction === 'rtl' ? 'rtl' : 'ltr')]);

$fontName = 'Open Sans';
$escapedFontName = str_replace(' ', '+', $fontName);

$this->getPreloadManager()->preconnect('https://fonts.googleapis.com/', ['crossorigin' => 'anonymous']);
$this->getPreloadManager()->preconnect('https://fonts.gstatic.com/', ['crossorigin' => 'anonymous']);
$paramsFontScheme = 'https://fonts.googleapis.com/css2?family=' . $escapedFontName . ':wght@300;400;700&display=swap';
$this->getPreloadManager()->preload($paramsFontScheme, ['as' => 'style', 'crossorigin' => 'anonymous']);
$this->getWebAssetManager()->registerAndUseStyle('googlefonts', $paramsFontScheme, [], ['crossorigin' => 'anonymous']);

// Set template metadata
$this->setMetaData('viewport', 'width=device-width, initial-scale=1.0');
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<jdoc:include type="head" />
</head>
<body class="contentpane">
	<jdoc:include type="message" />
	<jdoc:include type="component" />
</body>
</html>
