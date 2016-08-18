<?php
/**
 * Joomla.org site template
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/** @var JDocumentHtml $this */

// Load the template helper
JLoader::register('JoomlaTemplateHelper', __DIR__ . '/helpers/template.php');

// Declare the template as HTML5
$this->setHtml5(true);

$app = JFactory::getApplication();

// Detecting Active Variables
$option   = $app->input->getCmd('option', '');
$view     = $app->input->getCmd('view', '');
$layout   = $app->input->getCmd('layout', 'default');
$task     = $app->input->getCmd('task', 'display');
$itemid   = $app->input->getUint('Itemid', 0);
$sitename = $app->get('sitename');

// Add JavaScript Frameworks
JHtml::_('bootstrap.framework');

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
if ($this->direction === 'rtl')
{
	JHtml::_('stylesheet', 'template-rtl.min.css', [], true, false, false, (bool) JDEBUG);

	// Optional support for custom RTL CSS rules
	JHtml::_('stylesheet', 'custom-rtl.css', [], true, false, false, false);
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
JHtml::_('script', 'template.js', false, true, false, false, (bool) JDEBUG);
JHtml::_('script', 'blockadblock.js', false, true, false, false, (bool) JDEBUG);

// Get the path for the HTML5 shim with optional override
$html5Shim = JHtml::_('script', 'jui/html5.js', false, true, true, false, (bool) JDEBUG);

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

// Set template metadata
$this->setMetaData('viewport', 'width=device-width, initial-scale=1.0');
$this->setMetaData('apple-mobile-web-app-capable', 'yes');
$this->setMetaData('apple-mobile-web-app-status-bar-style', 'blue');
$this->setMetaData('twitter:card', 'summary_large_image');
$this->setMetaData('twitter:site', '@joomla');
$this->setMetaData('og:site_name', $sitename, 'property');
$this->addHeadLink("$templateBaseUrl/images/apple-touch-icon-144-precomposed.png", 'apple-touch-icon-precomposed', 'rel', ['sizes' => '144x144']);
$this->addHeadLink("$templateBaseUrl/images/apple-touch-icon-114-precomposed.png", 'apple-touch-icon-precomposed', 'rel', ['sizes' => '114x114']);
$this->addHeadLink("$templateBaseUrl/images/apple-touch-icon-72-precomposed.png", 'apple-touch-icon-precomposed', 'rel', ['sizes' => '72x72']);
$this->addHeadLink("$templateBaseUrl/images/apple-touch-icon-57-precomposed.png", 'apple-touch-icon-precomposed');

// Check if Open Graph metadata was set by content otherwise add template defaults
// Note: Even though Open Graph may support multiple tags, Joomla doesn't, so it's either this or back to custom tags and I'd rather do this
if (!$this->getMetaData('og:image', 'property'))
{
	$this->setMetaData('og:image', 'https://cdn.joomla.org/images/joomla-org-og.jpg', 'property');
}

// Check if Twitter metadata was set by content otherwise add template defaults
if (!$this->getMetaData('twitter:description'))
{
	$this->setMetaData('twitter:description', $this->params->get('twitterCardDescription', 'The Platform Millions of Websites Are Built On'));
}

if (!$this->getMetaData('twitter:image'))
{
	$this->setMetaData('twitter:image', 'https://cdn.joomla.org/images/joomla-twitter-card.jpg');
}

if (!$this->getMetaData('twitter:title'))
{
	$this->setMetaData('twitter:title', $this->params->get('twitterCardTitle', $sitename));
}

// Get the GTM property ID
$gtmId = JoomlaTemplateHelper::getGtmId(JUri::getInstance()->toString(['host']));
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<jdoc:include type="head" />
	<!--[if lt IE 9]><script src="<?php echo $html5Shim ?>"></script><![endif]-->
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
				<div class="container">
					<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</a>

					<?php echo file_get_contents('https://cdn.joomla.org/template/menu/v3_menu.php'); ?>
				</div>
			</div>
		</div>
	</nav>
	<!-- Header -->
	<header class="header">
		<div class="container">
			<div class="row-fluid">
				<div class="span7">
					<h1 class="page-title">
						<a href="<?php echo $this->baseurl; ?>/"><?php echo JHtml::_('string.truncate', $sitename, 40, false, false);?></a>
					</h1>
				</div>
				<div class="span5">
					<div class="btn-toolbar pull-right">
						<div class="btn-group">
							<a href="https://www.joomla.org/download.html" class="btn btn-large btn-warning"><?php echo JText::_('TPL_JOOMLA_DOWNLOAD_BUTTON'); ?></a>
						</div>
						<div class="btn-group">
							<a href="https://demo.joomla.org" class="btn btn-large btn-primary"><?php echo JText::_('TPL_JOOMLA_DEMO_BUTTON'); ?></a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</header>
	<nav class="subnav-wrapper">
		<div class="subnav">
			<div class="container">
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

			<div class="social">
				<ul class="soc">
					<li><a href="https://twitter.com/joomla" target="_blank" class="soc-twitter2" title="<?php echo JText::_('TPL_JOOMLA_FOLLOW_ON_TWITTER'); ?>"><span class="element-invisible"><?php echo JText::_('TPL_JOOMLA_FOLLOW_ON_TWITTER'); ?></span></a></li>
					<li><a href="https://www.facebook.com/joomla" target="_blank" class="soc-facebook" title="<?php echo JText::_('TPL_JOOMLA_FOLLOW_ON_FACEBOOK'); ?>"><span class="element-invisible"><?php echo JText::_('TPL_JOOMLA_FOLLOW_ON_FACEBOOK'); ?></span></a></li>
					<li><a href="https://plus.google.com/+joomla/posts" target="_blank" class="soc-google" title="<?php echo JText::_('TPL_JOOMLA_FOLLOW_ON_GOOGLE_PLUS'); ?>"><span class="element-invisible"><?php echo JText::_('TPL_JOOMLA_FOLLOW_ON_GOOGLE_PLUS'); ?></span></a></li>
					<li><a href="https://www.youtube.com/user/joomla" target="_blank" class="soc-youtube3" title="<?php echo JText::_('TPL_JOOMLA_FOLLOW_ON_YOUTUBE'); ?>"><span class="element-invisible"><?php echo JText::_('TPL_JOOMLA_FOLLOW_ON_YOUTUBE'); ?></span></a></li>
					<li><a href="https://www.linkedin.com/company/joomla" target="_blank" class="soc-linkedin" title="<?php echo JText::_('TPL_JOOMLA_FOLLOW_ON_LINKEDIN'); ?>"><span class="element-invisible"><?php echo JText::_('TPL_JOOMLA_FOLLOW_ON_LINKEDIN'); ?></span></a></li>
					<li><a href="https://www.pinterest.com/joomla" target="_blank" class="soc-pinterest" title="<?php echo JText::_('TPL_JOOMLA_FOLLOW_ON_PINTEREST'); ?>"><span class="element-invisible"><?php echo JText::_('TPL_JOOMLA_FOLLOW_ON_PINTEREST'); ?></span></a></li>
					<li><a href="https://github.com/joomla" target="_blank" class="soc-github3 soc-icon-last" title="<?php echo JText::_('TPL_JOOMLA_FOLLOW_ON_GITHUB'); ?>"><span class="element-invisible"><?php echo JText::_('TPL_JOOMLA_FOLLOW_ON_GITHUB'); ?></span></a></li>
				</ul>
			</div>

			<div class="footer-menu">
				<ul class="nav-inline">
					<li><a href="https://www.joomla.org"><span><?php echo JText::_('TPL_JOOMLA_FOOTER_LINK_HOME'); ?></span></a></li>
					<li><a href="https://www.joomla.org/about-joomla.html"><span><?php echo JText::_('TPL_JOOMLA_FOOTER_LINK_ABOUT'); ?></span></a></li>
					<li><a href="https://community.joomla.org"><span><?php echo JText::_('TPL_JOOMLA_FOOTER_LINK_COMMUNITY'); ?></span></a></li>
					<li><a href="http://forum.joomla.org"><span><?php echo JText::_('TPL_JOOMLA_FOOTER_LINK_FORUM'); ?></span></a></li>
					<li><a href="http://extensions.joomla.org"><span><?php echo JText::_('TPL_JOOMLA_FOOTER_LINK_JED'); ?></span></a></li>
					<li><a href="http://resources.joomla.org"><span><?php echo JText::_('TPL_JOOMLA_FOOTER_LINK_JRD'); ?></span></a></li>
					<li><a href="https://docs.joomla.org"><span><?php echo JText::_('TPL_JOOMLA_FOOTER_LINK_DOCS'); ?></span></a></li>
					<li><a href="https://developer.joomla.org"><span><?php echo JText::_('TPL_JOOMLA_FOOTER_LINK_DEVELOPER'); ?></span></a></li>
					<li><a href="https://shop.joomla.org"><span><?php echo JText::_('TPL_JOOMLA_FOOTER_LINK_SHOP'); ?></span></a></li>
				</ul>

				<ul class="nav-inline">
					<li><a href="https://www.joomla.org/accessibility-statement.html"><?php echo JText::_('TPL_JOOMLA_FOOTER_LINK_ACCESSIBILITY_STATEMENT'); ?></a></li>
					<li><a href="https://www.joomla.org/privacy-policy.html"><?php echo JText::_('TPL_JOOMLA_FOOTER_LINK_PRIVACY_POLICY'); ?></a></li>
					<li><a href="<?php echo JoomlaTemplateHelper::getIssueLink(JUri::getInstance()->toString(['host'])); ?>"><?php echo JText::_('TPL_JOOMLA_FOOTER_LINK_REPORT_AN_ISSUE'); ?></a></li>
					<li><a href="<?php echo JRoute::_(JoomlaTemplateHelper::getLoginRoute()); ?>"><?php echo JFactory::getUser()->guest ? JText::_('TPL_JOOMLA_FOOTER_LINK_LOG_IN') : JText::_('TPL_JOOMLA_FOOTER_LINK_LOG_OUT'); ?></a></li>
				</ul>

				<p class="copyright"><?php echo JText::sprintf('TPL_JOOMLA_FOOTER_LINK_COPYRIGHT', 2005, date('Y'), '<a href="http://opensourcematters.org">Open Source Matters, Inc.</a>'); ?></p>

				<div class="hosting">
					<div class="hosting-image"><a href="https://www.rochen.com/joomla-hosting" target="_blank"><img class="rochen" src="https://cdn.joomla.org/rochen/rochen_footer_logo_white.png" alt="Rochen" /></a></div>
					<div class="hosting-text"><a href="https://www.rochen.com/joomla-hosting" target="_blank"><?php echo JText::sprintf('TPL_JOOMLA_FOOTER_LINK_HOSTING', 'Rochen'); ?></a></div>
				</div>
			</div>

			<div id="adblock-msg" class="navbar navbar-fixed-bottom hide">
				<div class="navbar-inner">
					<i class="icon-warning"></i>
					<?php echo JText::_('TPL_JOOMLA_AD_BLOCK_BLURB'); ?>
				</div>
			</div>
		</div>
	</footer>

	<jdoc:include type="modules" name="debug" style="none" />
</body>
</html>
