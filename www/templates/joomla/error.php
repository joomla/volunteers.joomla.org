<?php
/**
 * Joomla.org site template
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/** @var JDocumentError $this */

// $this doesn't load the language file like its HTML counterpart, so do it ourselves if able
if (JFactory::$language)
{
	$lang = JFactory::getLanguage();
	$lang->load('tpl_joomla', JPATH_BASE, null, false, true) || $lang->load('tpl_joomla', __DIR__, null, false, true);
}

// Set the base URL
$this->setBase(htmlspecialchars(JUri::current()));

// Load the template helper
JLoader::register('JoomlaTemplateHelper', __DIR__ . '/helpers/template.php');

$app    = JFactory::getApplication();
$params = $app->getTemplate(true)->params;

// Detecting Active Variables
$option   = $app->input->getCmd('option', '');
$view     = $app->input->getCmd('view', '');
$layout   = $app->input->getCmd('layout', 'default');
$task     = $app->input->getCmd('task', 'display');
$itemid   = $app->input->getUint('Itemid', 0);
$sitename = $app->get('sitename');

// Set the CSS URL based on whether we're in debug mode
if (JDEBUG)
{
	$cssURL = JHtml::_('stylesheet', 'template.min.css', [], true, true, false, (bool) JDEBUG);
}
else
{
	$cssURL = 'https://cdn.joomla.org/template/css/template_2.0.0.min.css';
}

// Optional site specific CSS override, prefer a minified custom.css file first
$customCss = JHtml::_('stylesheet', 'custom.css', [], true, true, false, false);

$rtlCss       = false;
$customRtlCss = false;

// Load optional RTL Bootstrap CSS
if ($this->direction === 'rtl')
{
	$rtlCss = JHtml::_('stylesheet', 'template-rtl.min.css', [], true, true, false, (bool) JDEBUG);

	// Optional support for custom RTL CSS rules
	$customRtlCss = JHtml::_('stylesheet', 'custom-rtl.css', [], true, true, false, false);
}

$languageModCss = false;

// If the multilanguage module is enabled, load its CSS - We do this solely by an isEnabled check because there isn't efficient API to do more checks
if (JModuleHelper::isEnabled('mod_languages'))
{
	$languageModCss = JHtml::_('stylesheet', 'mod_languages/template.css', [], true, true, false, (bool) JDEBUG);
}

// Load template JavaScript
$templateJs = JHtml::_('script', 'template.js', false, true, true, false, (bool) JDEBUG);
$adBlockJs  = JHtml::_('script', 'blockadblock.js', false, true, true, false, (bool) JDEBUG);

// Load jQuery and Bootstrap JavaScript
$jQueryJs     = JHtml::_('script', 'jui/jquery.min.js', false, true, true, false, (bool) JDEBUG);
$noConflictJs = JHtml::_('script', 'jui/jquery-noconflict.js', false, true, true, false, (bool) JDEBUG);
$migrateJs    = JHtml::_('script', 'jui/jquery-migrate.min.js', false, true, true, false, (bool) JDEBUG);
$bootstrapJs  = JHtml::_('script', 'jui/bootstrap.min.js', false, true, true, false, (bool) JDEBUG);

// Get the path for the HTML5 shim with optional override
$html5Shim = JHtml::_('script', 'jui/html5.js', false, true, true, false, (bool) JDEBUG);

// Set the replacement for the position-0 module loaded from the CDN'd menu
$search      = '<jdoc:include type="modules" name="position-0" style="none" />';
$replacement = '';

foreach (JModuleHelper::getModules('position-0') as $module)
{
	$replacement .= JModuleHelper::renderModule($module, ['style' => 'none']);
}

// Get the GTM property ID
$gtmId = JoomlaTemplateHelper::getGtmId(JUri::getInstance()->toString(['host']));
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<meta charset="utf-8" />
	<base href="<?php echo $this->getBase(); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="language" content="<?php echo $this->language; ?>" />
	<title><?php echo $this->title; ?> <?php echo $this->error->getMessage();?></title>
	<link href="/templates/joomla/favicon.ico" rel="shortcut icon" type="image/vnd.microsoft.icon" />
	<link href="<?php echo $cssURL ?>" rel="stylesheet" />
	<?php if ($customCss) : ?>
		<link href="<?php echo $customCss ?>" rel="stylesheet" />
	<?php endif; ?>
	<?php if ($rtlCss) : ?>
		<link href="<?php echo $rtlCss; ?>" rel="stylesheet" />
	<?php endif; ?>
	<?php if ($customRtlCss) : ?>
		<link href="<?php echo $customRtlCss; ?>" rel="stylesheet" />
	<?php endif; ?>
	<?php if ($params->get('googleFont')) : ?>
		<link href="https://fonts.googleapis.com/css?family=<?php echo $params->get('googleFontName');?>" rel="stylesheet" />
		<style>
			h1, h2, h3, h4, h5, h6 {
				font-family: '<?php echo str_replace('+', ' ', $params->get('googleFontName'));?>', sans-serif;
			}
		</style>
	<?php endif; ?>
	<?php if ($languageModCss) : ?>
		<link href="<?php echo $languageModCss ?>" rel="stylesheet" />
	<?php endif; ?>
	<?php if ($jQueryJs) : ?>
		<script src="<?php echo $jQueryJs; ?>"></script>
	<?php endif; ?>
	<?php if ($noConflictJs) : ?>
		<script src="<?php echo $noConflictJs; ?>"></script>
	<?php endif; ?>
	<?php if ($migrateJs) : ?>
		<script src="<?php echo $migrateJs; ?>"></script>
	<?php endif; ?>
	<?php if ($bootstrapJs) : ?>
		<script src="<?php echo $bootstrapJs; ?>"></script>
	<?php endif; ?>
	<?php if ($templateJs) : ?>
		<script src="<?php echo $templateJs ?>"></script>
	<?php endif; ?>
	<?php if ($adBlockJs) : ?>
		<script src="<?php echo $adBlockJs ?>"></script>
	<?php endif; ?>
	<?php if ($html5Shim) : ?>
		<!--[if lt IE 9]><script src="<?php echo $html5Shim ?>"></script><![endif]-->
	<?php endif; ?>
</head>
<body class="<?php echo "site error $option view-$view layout-$layout task-$task itemid-$itemid" . ($params->get('fluidContainer') ? ' fluid' : '') . ($this->direction == 'rtl' ? ' rtl' : ''); ?>">
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

					<?php echo str_replace($search, $replacement, file_get_contents('https://cdn.joomla.org/template/menu/v3_menu.php')); ?>
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
						<a href="<?php echo JUri::root(); ?>"><?php echo JHtml::_('string.truncate', $sitename, 40, false, false);?></a>
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
				<?php foreach (JModuleHelper::getModules('position-1') as $searchmodule) :
					echo JModuleHelper::renderModule($searchmodule, ['style' => 'none']);
				endforeach; ?>
			</div>
		</div>
	</nav>
	<!-- Body -->
	<div class="body">
		<div class="container<?php echo $params->get('fluidContainer') ? '-fluid' : ''; ?>">
			<div class="row-fluid">
				<main id="content" class="span12">
					<!-- Begin Content -->
					<h1 class="page-header"><?php echo JText::_('JERROR_LAYOUT_PAGE_NOT_FOUND'); ?></h1>
					<div class="well">
						<div class="row-fluid">
							<div class="span6">
								<p><strong><?php echo JText::_('JERROR_LAYOUT_ERROR_HAS_OCCURRED_WHILE_PROCESSING_YOUR_REQUEST'); ?></strong></p>
								<p><?php echo JText::_('JERROR_LAYOUT_NOT_ABLE_TO_VISIT'); ?></p>
								<ul>
									<li><?php echo JText::_('JERROR_LAYOUT_AN_OUT_OF_DATE_BOOKMARK_FAVOURITE'); ?></li>
									<li><?php echo JText::_('JERROR_LAYOUT_MIS_TYPED_ADDRESS'); ?></li>
									<li><?php echo JText::_('JERROR_LAYOUT_SEARCH_ENGINE_OUT_OF_DATE_LISTING'); ?></li>
									<li><?php echo JText::_('JERROR_LAYOUT_YOU_HAVE_NO_ACCESS_TO_THIS_PAGE'); ?></li>
								</ul>
							</div>
							<div class="span6">
								<p><strong><?php echo JText::_('JERROR_LAYOUT_SEARCH'); ?></strong></p>
								<p><?php echo JText::_('JERROR_LAYOUT_SEARCH_PAGE'); ?></p>
								<?php echo JModuleHelper::renderModule(JModuleHelper::getModule($params->get('searchModule', 'search'))); ?>
								<p><?php echo JText::_('JERROR_LAYOUT_GO_TO_THE_HOME_PAGE'); ?></p>
								<p><a href="<?php echo JUri::root(); ?>" class="btn"><i class="icon-home"></i> <?php echo JText::_('JERROR_LAYOUT_HOME_PAGE'); ?></a></p>
							</div>
						</div>
						<hr />
						<p><?php echo JText::_('JERROR_LAYOUT_PLEASE_CONTACT_THE_SYSTEM_ADMINISTRATOR'); ?></p>
						<blockquote>
							<span class="label label-inverse"><?php echo $this->error->getCode(); ?></span> <?php echo htmlspecialchars($this->error->getMessage(), ENT_QUOTES, 'UTF-8'); ?>
							<?php if ($this->debug) : ?>
								in <?php echo $this->error->getFile(); ?> on line <?php echo $this->error->getLine(); ?>
							<?php endif; ?>
						</blockquote>
						<?php if ($this->debug) : ?>
							<div>
								<?php echo $this->renderBacktrace(); ?>
								<?php // Check if there are more Exceptions and render their data as well ?>
								<?php if ($this->error->getPrevious()) : ?>
									<?php $loop = true; ?>
									<?php // Reference $this->_error here and in the loop as setError() assigns errors to this property and we need this for the backtrace to work correctly ?>
									<?php // Make the first assignment to setError() outside the loop so the loop does not skip Exceptions ?>
									<?php $this->setError($this->_error->getPrevious()); ?>
									<?php while ($loop === true) : ?>
										<p><strong>Previous Error</strong></p>
										<blockquote>
											<span class="label label-inverse"><?php echo $this->_error->getCode(); ?></span> <?php echo htmlspecialchars($this->_error->getMessage(), ENT_QUOTES, 'UTF-8'); ?> in <?php echo $this->_error->getFile(); ?> on line <?php echo $this->_error->getLine(); ?>
										</blockquote>
										<?php echo $this->renderBacktrace(); ?>
										<?php $loop = $this->setError($this->_error->getPrevious()); ?>
									<?php endwhile; ?>
									<?php // Reset the main error object to the base error ?>
									<?php $this->setError($this->error); ?>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>
					<!-- End Content -->
				</main>
			</div>
		</div>
	</div>
	<!-- Footer -->
	<footer class="footer center">
		<div class="container<?php echo $params->get('fluidContainer') ? '-fluid' : ''; ?>">
			<hr />

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
</body>
</html>
