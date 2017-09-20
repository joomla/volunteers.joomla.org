<?php
/**
 * Joomla.org site template
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/** @var \Joomla\CMS\Document\HtmlDocument $this */

// Load the template helper
JLoader::register('JoomlaTemplateHelper', __DIR__ . '/helpers/template.php');

// Declare the template as HTML5
$this->setHtml5(true);

$app = Factory::getApplication();

// Detecting Active Variables
$option   = $app->input->getCmd('option', '');
$view     = $app->input->getCmd('view', '');
$layout   = $app->input->getCmd('layout', 'default');
$task     = $app->input->getCmd('task', 'display');
$itemid   = $app->input->getUint('Itemid', 0);
$sitename = $app->get('sitename');

// Add JavaScript Frameworks
HTMLHelper::_('bootstrap.framework');

// Add Stylesheets - if the site is in debug mode or has explicitly chosen to not use the CDN, load the local media
if (JDEBUG || !$this->params->get('useCdn', '1'))
{
	HTMLHelper::_('stylesheet', 'template.min.css', ['relative' => true, 'detectDebug' => (bool) JDEBUG, 'version' => '2.3.0']);
}
else
{
	$this->addStyleSheet('https://cdn.joomla.org/template/css/template_2.3.0.min.css');
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

// Load Google Font if defined
if ($this->params->get('googleFont'))
{
	$escapedFontName = str_replace('+', ' ', $this->params->get('googleFontName'));
	$this->addStyleSheet('https://fonts.googleapis.com/css?family=' . $this->params->get('googleFontName'));
	$this->addStyleDeclaration(<<<CSS
		h1, h2, h3, h4, h5, h6 {
			font-family: '$escapedFontName', sans-serif;
		}
CSS
	);
}

// Load template JavaScript
HTMLHelper::_('script', 'template.js', ['version' => 'auto', 'relative' => true, 'detectDebug' => (bool) JDEBUG], []);
HTMLHelper::_('script', 'blockadblock.js', ['version' => 'auto', 'relative' => true, 'detectDebug' => (bool) JDEBUG], []);
HTMLHelper::_('script', 'js.cookie.js', ['version' => 'auto', 'relative' => true, 'detectDebug' => (bool) JDEBUG], []);

// Load the HTML5 shim with optional override
HTMLHelper::_('script', 'jui/html5.js', ['version' => 'auto', 'relative' => true, 'detectDebug' => (bool) JDEBUG, 'conditional' => 'lt IE 9'], []);

$leftPosition  = 'position-8';
$rightPosition = 'position-7';

$leftColumnWidth  = $this->params->get('leftColumnWidth', 3);
$rightColumnWidth = $this->params->get('rightColumnWidth', 3);

// Default full width
$span = 'span12';

// Width if both columns are displayed
if ($this->countModules($rightPosition) && $this->countModules($leftPosition))
{
	$span  = 'span' . (12 - $leftColumnWidth - $rightColumnWidth);
}
// Width if right column is displayed only
elseif ($this->countModules($rightPosition) && !$this->countModules($leftPosition))
{
	$span  = 'span' . (12 - $rightColumnWidth);
}
// Width if left column is displayed only
elseif (!$this->countModules($rightPosition) && $this->countModules($leftPosition))
{
	$span  = 'span' . (12 - $leftColumnWidth);
}

$templateBaseUrl = $this->baseurl . '/templates/' . $this->template;

// Set default template metadata
$this->setMetaData('viewport', 'width=device-width, initial-scale=1.0');
$this->setMetaData('apple-mobile-web-app-capable', 'yes');
$this->setMetaData('apple-mobile-web-app-status-bar-style', 'blue');
$this->addHeadLink("$templateBaseUrl/images/apple-touch-icon-180x180.png", 'apple-touch-icon', 'rel', ['sizes' => '180x180']);
$this->addHeadLink("$templateBaseUrl/images/apple-touch-icon-152x152.png", 'apple-touch-icon', 'rel', ['sizes' => '152x152']);
$this->addHeadLink("$templateBaseUrl/images/apple-touch-icon-144x144.png", 'apple-touch-icon', 'rel', ['sizes' => '144x144']);
$this->addHeadLink("$templateBaseUrl/images/apple-touch-icon-120x120.png", 'apple-touch-icon', 'rel', ['sizes' => '120x120']);
$this->addHeadLink("$templateBaseUrl/images/apple-touch-icon-114x114.png", 'apple-touch-icon', 'rel', ['sizes' => '114x114']);
$this->addHeadLink("$templateBaseUrl/images/apple-touch-icon-76x76.png", 'apple-touch-icon', 'rel', ['sizes' => '76x76']);
$this->addHeadLink("$templateBaseUrl/images/apple-touch-icon-72x72.png", 'apple-touch-icon', 'rel', ['sizes' => '72x72']);
$this->addHeadLink("$templateBaseUrl/images/apple-touch-icon-57x57.png", 'apple-touch-icon', 'rel', ['sizes' => '57x57']);
$this->addHeadLink("$templateBaseUrl/images/apple-touch-icon.png", 'apple-touch-icon');

// Check if social metadata was set by content otherwise add template defaults
// Note: Even though Open Graph may support multiple tags, Joomla doesn't, so we need to check them anyway or go to custom tags
if (!$this->getMetaData('twitter:card'))
{
	$this->setMetaData('twitter:card', 'summary_large_image');
}

if (!$this->getMetaData('twitter:site'))
{
	$this->setMetaData('twitter:site', '@joomla');
}

if (!$this->getMetaData('og:site_name', 'property'))
{
	$this->setMetaData('og:site_name', $sitename, 'property');
}

if (!$this->getMetaData('og:image', 'property'))
{
	$this->setMetaData('og:image', $this->params->get('ogImage', 'https://cdn.joomla.org/images/joomla-org-og.jpg'), 'property');
}

if (!$this->getMetaData('twitter:description'))
{
	$this->setMetaData('twitter:description', $this->params->get('twitterCardDescription', 'The Platform Millions of Websites Are Built On'));
}

if (!$this->getMetaData('twitter:image'))
{
	$this->setMetaData('twitter:image', $this->params->get('twitterCardImage', 'https://cdn.joomla.org/images/joomla-twitter-card.jpg'));
}

if (!$this->getMetaData('twitter:title'))
{
	$this->setMetaData('twitter:title', $this->params->get('twitterCardTitle', $sitename));
}

if (!$this->getMetaData('referrer'))
{
	$this->setMetaData('referrer', 'unsafe-url');
}

// Get the GTM property ID
$gtmId = JoomlaTemplateHelper::getGtmId(Uri::getInstance()->toString(['host']));
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<jdoc:include type="head" />
	<script>
	var _prum = [['id', '59300ad15992c776ad970068'],
	             ['mark', 'firstbyte', (new Date()).getTime()]];
	(function() {
	    var s = document.getElementsByTagName('script')[0]
	      , p = document.createElement('script');
	    p.async = 'async';
	    p.src = '//rum-static.pingdom.net/prum.min.js';
	    s.parentNode.insertBefore(p, s);
	})();
	</script>
</head>
<body class="<?php echo "site $option view-$view layout-$layout task-$task itemid-$itemid" . ($this->params->get('fluidContainer') ? ' fluid' : '') . ($this->direction == 'rtl' ? ' rtl' : ''); ?>">
	<?php
	// Add Google Tag Manager code if one is set
	if ($gtmId) : ?>
	<!-- Google Tag Manager -->
	<noscript><iframe src="//www.googletagmanager.com/ns.html?id=<?php echo $gtmId; ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
	<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='//www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?php echo $gtmId; ?>');</script>
	<!-- End Google Tag Manager -->
	<?php endif; ?>
	<!-- Top Nav -->
	<nav class="navigation" role="navigation">
		<div id="mega-menu" class="navbar navbar-inverse navbar-fixed-top">
			<div class="navbar-inner">
				<div class="container<?php echo $this->params->get('fluidContainer') ? '-fluid' : ''; ?>">
					<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</a>

					<?php echo JoomlaTemplateHelper::getTemplateMenu($this->language); ?>
				</div>
			</div>
		</div>
	</nav>
	<!-- Header -->
	<header class="header">
		<div class="container<?php echo $this->params->get('fluidContainer') ? '-fluid' : ''; ?>">
			<div class="row-fluid">
				<div class="span7">
					<h1 class="page-title">
						<a href="<?php echo $this->baseurl; ?>/"><?php echo HTMLHelper::_('string.truncate', $sitename, 40, false, false);?></a>
					</h1>
				</div>
				<div class="span5">
					<div class="btn-toolbar pull-right">
						<div class="btn-group">
							<a href="https://downloads.joomla.org/" class="btn btn-large btn-warning"><?php echo Text::_('TPL_JOOMLA_DOWNLOAD_BUTTON'); ?></a>
						</div>
						<div class="btn-group">
							<a href="https://demo.joomla.org" class="btn btn-large btn-primary"><?php echo Text::_('TPL_JOOMLA_DEMO_BUTTON'); ?></a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</header>
	<nav class="subnav-wrapper">
		<div class="subnav">
			<div class="container<?php echo $this->params->get('fluidContainer') ? '-fluid' : ''; ?>">
				<jdoc:include type="modules" name="position-1" style="none" />
			</div>
		</div>
	</nav>
	<!-- Body -->
	<div class="body">
		<div class="container<?php echo $this->params->get('fluidContainer') ? '-fluid' : ''; ?>">
			<jdoc:include type="modules" name="banner" style="xhtml" />
			<div class="row-fluid">
				<?php if ($this->countModules($leftPosition)): ?>
				<!-- Begin Sidebar -->
				<div id="sidebar" class="<?php echo "span$leftColumnWidth"; ?>">
					<div class="sidebar-nav">
						<jdoc:include type="modules" name="position-8" style="xhtml" />
					</div>
				</div>
				<!-- End Sidebar -->
				<?php endif; ?>
				<main id="content" class="<?php echo $span;?>">
					<!-- Begin Content -->
					<jdoc:include type="modules" name="position-3" style="xhtml" />
					<jdoc:include type="message" />
					<jdoc:include type="component" />
					<jdoc:include type="modules" name="position-2" style="none" />
					<!-- End Content -->
				</main>
				<?php if ($this->countModules($rightPosition)) : ?>
				<aside class="<?php echo "span$rightColumnWidth"; ?>">
					<!-- Begin Right Sidebar -->
					<jdoc:include type="modules" name="position-7" style="well" />
					<!-- End Right Sidebar -->
				</aside>
				<?php endif; ?>
			</div>
			<?php if ($this->countModules('position-5')) : ?>
			<div class="row-fluid">
				<jdoc:include type="modules" name="position-5" style="xhtml" />
			</div>
			<?php endif; ?>
		</div>
	</div>
	<!-- Footer -->
	<footer class="footer center">
		<div class="container<?php echo $this->params->get('fluidContainer') ? '-fluid' : ''; ?>">
			<hr />
			<jdoc:include type="modules" name="footer" style="none" />

			<?php echo JoomlaTemplateHelper::getTemplateFooter($this->language); ?>
		</div>
	</footer>

	<jdoc:include type="modules" name="debug" style="none" />
</body>
</html>
