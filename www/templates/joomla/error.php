<?php
/**
 * Joomla.org site template
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/** @var \Joomla\CMS\Document\ErrorDocument $this */

// Set the base URL
$this->setBase(htmlspecialchars(Uri::current()));

// Load the template helper
JLoader::register('JoomlaTemplateHelper', __DIR__ . '/helpers/template.php');

$app    = Factory::getApplication();
$params = $app->getTemplate(true)->params;

// Detecting Active Variables
$option   = $app->input->getCmd('option', '');
$view     = $app->input->getCmd('view', '');
$layout   = $app->input->getCmd('layout', 'default');
$task     = $app->input->getCmd('task', 'display');
$itemid   = $app->input->getUint('Itemid', 0);
$sitename = $app->get('sitename');

// Set the CSS URL based on whether we're in debug mode or it was explicitly chosen to not use the CDN
if (JDEBUG || !$params->get('useCdn', '1'))
{
	$cssURL = HTMLHelper::_('stylesheet', 'template.min.css', ['pathOnly' => true, 'relative' => true, 'detectDebug' => (bool) JDEBUG, 'version' => '2.3.0']);
}
else
{
	$cssURL = 'https://cdn.joomla.org/template/css/template_2.3.0.min.css';
}

$bs3Css = false;

// Bootstrap 3 polyfill
if ($params->get('bs3Grid', '0'))
{
    $bs3Css = HTMLHelper::_('stylesheet', 'bs3-polyfill.css', ['pathOnly' => true, 'version' => 'auto', 'relative' => true, 'detectDebug' => false], []);
}

// Optional site specific CSS override, prefer a minified custom.css file first
$customCss = HTMLHelper::_('stylesheet', 'custom.css', ['pathOnly' => true, 'version' => 'auto', 'relative' => true, 'detectDebug' => false], []);

$rtlCss       = false;
$customRtlCss = false;

// Load optional RTL Bootstrap CSS
if ($this->direction === 'rtl')
{
	$rtlCss = HTMLHelper::_('stylesheet', 'template-rtl.min.css', ['pathOnly' => true, 'version' => 'auto', 'relative' => true, 'detectDebug' => (bool) JDEBUG], []);

	// Optional support for custom RTL CSS rules
	$customRtlCss = HTMLHelper::_('stylesheet', 'custom-rtl.css', ['pathOnly' => true, 'version' => 'auto', 'relative' => true, 'detectDebug' => false], []);
}

$languageModCss = false;

// If the multilanguage module is enabled, load its CSS - We do this solely by an isEnabled check because there isn't efficient API to do more checks
if (ModuleHelper::isEnabled('mod_languages'))
{
	$languageModCss = HTMLHelper::_('stylesheet', 'mod_languages/template.css', ['pathOnly' => true, 'version' => 'auto', 'relative' => true, 'detectDebug' => (bool) JDEBUG], []);
}

// Load template JavaScript
$templateJs = HTMLHelper::_('script', 'template.js', ['pathOnly' => true, 'version' => 'auto', 'relative' => true, 'detectDebug' => (bool) JDEBUG], []);
$adBlockJs  = HTMLHelper::_('script', 'blockadblock.js', ['pathOnly' => true, 'version' => 'auto', 'relative' => true, 'detectDebug' => (bool) JDEBUG], []);
$cookieJs   = HTMLHelper::_('script', 'js.cookie.js', ['pathOnly' => true, 'version' => 'auto', 'relative' => true, 'detectDebug' => (bool) JDEBUG], []);

// Load jQuery and Bootstrap JavaScript
$jQueryJs     = HTMLHelper::_('script', 'jui/jquery.min.js', ['pathOnly' => true, 'version' => 'auto', 'relative' => true, 'detectDebug' => (bool) JDEBUG], []);
$noConflictJs = HTMLHelper::_('script', 'jui/jquery-noconflict.min.js', ['pathOnly' => true, 'version' => 'auto', 'relative' => true, 'detectDebug' => (bool) JDEBUG], []);
$migrateJs    = HTMLHelper::_('script', 'jui/jquery-migrate.min.js', ['pathOnly' => true, 'version' => 'auto', 'relative' => true, 'detectDebug' => (bool) JDEBUG], []);
$bootstrapJs  = HTMLHelper::_('script', 'jui/bootstrap.min.js', ['pathOnly' => true, 'version' => 'auto', 'relative' => true, 'detectDebug' => (bool) JDEBUG], []);

// Get the path for the HTML5 shim with optional override
$html5Shim = HTMLHelper::_('script', 'jui/html5.js', ['pathOnly' => true, 'version' => 'auto', 'relative' => true, 'detectDebug' => (bool) JDEBUG], []);

// Set the replacement for the position-0 module loaded from the CDN'd menu
$search      = '<jdoc:include type="modules" name="position-0" style="none" />';
$replacement = '';

foreach (ModuleHelper::getModules('position-0') as $module)
{
	$replacement .= ModuleHelper::renderModule($module, ['style' => 'none']);
}

// Get the GTM property ID
$gtmId = JoomlaTemplateHelper::getGtmId(Uri::getInstance()->toString(['host']));
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
	<?php if ($bs3Css) : ?>
		<link href="<?php echo $bs3Css ?>" rel="stylesheet" />
	<?php endif; ?>
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
	<?php if ($cookieJs) : ?>
		<script src="<?php echo $cookieJs ?>"></script>
	<?php endif; ?>
	<?php if ($html5Shim) : ?>
		<!--[if lt IE 9]><script src="<?php echo $html5Shim ?>"></script><![endif]-->
	<?php endif; ?>
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
				<div class="container<?php echo $params->get('fluidContainer') ? '-fluid' : ''; ?>">
					<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</a>

					<?php echo str_replace($search, $replacement, JoomlaTemplateHelper::getTemplateMenu($this->language)); ?>
				</div>
			</div>
		</div>
	</nav>
	<!-- Header -->
	<header class="header">
		<div class="container<?php echo $params->get('fluidContainer') ? '-fluid' : ''; ?>">
			<div class="row-fluid">
				<div class="span7">
					<h1 class="page-title">
						<a href="<?php echo Uri::root(); ?>"><?php echo HTMLHelper::_('string.truncate', $sitename, 40, false, false);?></a>
					</h1>
				</div>
				<div class="span5">
					<div class="btn-toolbar pull-right">
						<div class="btn-group">
							<a href="https://downloads.joomla.org" class="btn btn-large btn-warning"><?php echo Text::_('TPL_JOOMLA_DOWNLOAD_BUTTON'); ?></a>
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
			<div class="container<?php echo $params->get('fluidContainer') ? '-fluid' : ''; ?>">
				<?php foreach (ModuleHelper::getModules('position-1') as $searchmodule) :
					echo ModuleHelper::renderModule($searchmodule, ['style' => 'none']);
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
					<div class="marge">
						<div class="row-fluid center">
							<div class="span6">
								<img src="/templates/joomla/images/error.jpg" alt="Joomla">
							</div>
							<div class="span6">
								<div class="errorborder">
									<h2><?php echo Text::_('TPL_JOOMLA_ERROR_LAYOUT_ERROR_HAS_OCCURRED'); ?></h2>
									<p><?php echo Text::_('TPL_JOOMLA_ERROR_LAYOUT_DONT_WORRY'); ?></p>
								</div>
								<h3><?php echo Text::_('TPL_JOOMLA_ERROR_LAYOUT_SEARCH'); ?></h3>
								<p><?php echo Text::_('TPL_JOOMLA_ERROR_LAYOUT_SEARCH_SITE'); ?></p>
								<?php echo ModuleHelper::renderModule(ModuleHelper::getModule($params->get('searchModule', 'search'))); ?>
								<p><?php echo Text::_('TPL_JOOMLA_ERROR_LAYOUT_START_AGAIN'); ?></p>
								<p><a href="<?php echo Uri::root(); ?>" class="btn btn-primary btn-large error"> <?php echo Text::_('JERROR_LAYOUT_HOME_PAGE'); ?></a></p>
							</div>
						</div>
						<hr />
						<p><?php echo Text::_('JERROR_LAYOUT_PLEASE_CONTACT_THE_SYSTEM_ADMINISTRATOR'); ?></p>
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
										<p><strong><?php echo Text::_('JERROR_LAYOUT_PREVIOUS_ERROR'); ?></strong></p>
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

			<?php echo JoomlaTemplateHelper::getTemplateFooter($this->language); ?>
		</div>
	</footer>
</body>
</html>
