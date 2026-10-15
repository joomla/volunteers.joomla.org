<?php
/**
 * Joomla.org site template
 *
 * @copyright   Copyright (C) 2005 - 2023 Open Source Matters, Inc. All rights reserved.
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
$this->addScriptOptions(
    'skipto-settings',
    [
        'settings' => [
            'skipTo' => [
                'attachElement' => '.navigation'
            ]
        ]
    ]
);

$app = Factory::getApplication();

// Detecting Active Variables
$option   = $app->input->getCmd('option', '');
$view     = $app->input->getCmd('view', '');
$layout   = $app->input->getCmd('layout', 'default');
$task     = $app->input->getCmd('task', 'display');
$itemid   = $app->input->getUint('Itemid', 0);
$sitename = $app->get('sitename');
$wa       = $this->getWebAssetManager();

// Add Bootstrap Javascript Frameworks
HTMLHelper::_('bootstrap.collapse');
HTMLHelper::_('bootstrap.dropdown');

// Load template stylesheet and javascript
$wa->useStyle('template.joomla.custom.' . ($this->direction === 'rtl' ? 'rtl' : 'ltr'))
    ->useScript('template.joomla');

// Override 'template.active' asset to set correct ltr/rtl dependency and with CDN Dependency
$wa->registerStyle('template.active', '', [], [], [((!JDEBUG && $this->params->get('useCdn', '1')) ? 'cdn.' : '') . 'template.joomla.' . ($this->direction === 'rtl' ? 'rtl' : 'ltr')]);

$fontName = 'Open Sans';
$escapedFontName = str_replace(' ', '+', $fontName);

$this->getPreloadManager()->preconnect('https://fonts.googleapis.com/', ['crossorigin' => 'anonymous']);
$this->getPreloadManager()->preconnect('https://fonts.gstatic.com/', ['crossorigin' => 'anonymous']);
$paramsFontScheme = 'https://fonts.googleapis.com/css2?family=' . $escapedFontName . ':wght@300;400;700&display=swap';
$this->getPreloadManager()->preload($paramsFontScheme, ['as' => 'style', 'crossorigin' => 'anonymous']);
$this->getWebAssetManager()->registerAndUseStyle('googlefonts', $paramsFontScheme, [], ['crossorigin' => 'anonymous']);

$leftPosition  = 'position-8';
$rightPosition = 'position-7';

$leftColumnWidth  = $this->params->get('leftColumnWidth', 3);
$rightColumnWidth = $this->params->get('rightColumnWidth', 3);

// Default full width
$span = 'col-md-12';

// Width if both columns are displayed
if ($this->countModules($rightPosition) && $this->countModules($leftPosition))
{
    $span  = 'col-md-' . (12 - $leftColumnWidth - $rightColumnWidth);
}
// Width if right column is displayed only
elseif ($this->countModules($rightPosition) && !$this->countModules($leftPosition))
{
    $span  = 'col-md-' . (12 - $rightColumnWidth);
}
// Width if left column is displayed only
elseif (!$this->countModules($rightPosition) && $this->countModules($leftPosition))
{
    $span  = 'col-md-' . (12 - $leftColumnWidth);
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
            p.src = 'https://rum-static.pingdom.net/prum.min.js';
            s.parentNode.insertBefore(p, s);
        })();
    </script>
</head>
<body class="<?php echo "site $option view-$view layout-$layout task-$task itemid-$itemid" . ($this->direction == 'rtl' ? ' rtl' : ''); ?>">
<?php
// Add Google Tag Manager code if one is set
if ($gtmId) : ?>
    <!-- Google Tag Manager -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo $gtmId; ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?php echo $gtmId; ?>');</script>
    <!-- End Google Tag Manager -->
<?php endif; ?>
<!-- Top Nav -->
<nav class="navigation" role="navigation" aria-label="<?php echo Text::_('TPL_JOOMLA_NAV_CROSS_SITE'); ?>">
    <div id="mega-menu" class="navbar navbar-expand-md py-md-1">
        <div class="container-xxl">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#primaryMenu" aria-controls="primaryMenu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <?php echo JoomlaTemplateHelper::getTemplateMenu($this->language, (bool) $this->params->get('useCdn', '1')); ?>
        </div>
    </div>
</nav>
<!-- Header -->
<header class="header">
    <div class="container-md">
        <div class="row">
            <div class="col-md-7">
                <div class="page-title h1">
                    <a href="<?php echo $this->baseurl; ?>/">
                        <?php $headerLogo = $this->params->get('headerLogo', 'https://cdn.joomla.org/images/joomla-colours-logo.svg'); ?>
                        <img height="50px;" src="<?php echo $headerLogo; ?>" alt="Joomla!" class="site-logo me-2 mb-1">
                        <?php $customHeaderTitle = $this->params->get('customHeaderTitle', null); ?>
                        <?php echo $customHeaderTitle ? HTMLHelper::_('string.truncate', $customHeaderTitle, 40, false, false) : ''; ?>
                    </a>
                </div>
            </div>
            <div class="col-md-5">
                <div class="btn-toolbar pt-md-1 row">
                    <div class="btn-group col-6">
                        <a href="https://downloads.joomla.org/" class="btn btn-lg btn-warning"><?php echo Text::_('TPL_JOOMLA_DOWNLOAD_BUTTON'); ?></a>
                    </div>
                    <div class="btn-group col-6">
                        <a href="https://launch.joomla.org" class="btn btn-lg btn-primary"><?php echo Text::_('TPL_JOOMLA_DEMO_BUTTON'); ?><span aria-hidden="true" class="icon-rocket"></span></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
<nav class="subnav-wrapper" aria-label="<?php echo Text::_('TPL_JOOMLA_NAV_MAIN_MENU'); ?>">
    <div class="subnav">
        <div class="container-md">
            <jdoc:include type="modules" name="position-1" style="none" />
        </div>
    </div>
</nav>
<!-- Body -->
<div class="body">
    <div class="container<?php echo $this->params->get('fluidContainer') ? '-fluid g-0' : ''; ?>">
        <jdoc:include type="modules" name="banner" style="xhtml" />
        <div class="row">
            <?php if ($this->countModules($leftPosition)): ?>
                <!-- Begin Sidebar -->
                <div id="sidebar" class="<?php echo "col-md-$leftColumnWidth"; ?> sidebar-left">
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
                <aside class="<?php echo "col-md-$rightColumnWidth"; ?> sidebar-right">
                    <!-- Begin Right Sidebar -->
                    <jdoc:include type="modules" name="position-7" style="well" />
                    <!-- End Right Sidebar -->
                </aside>
            <?php endif; ?>
        </div>
        <?php if ($this->countModules('position-5')) : ?>
            <div class="row">
                <jdoc:include type="modules" name="position-5" style="xhtml" />
            </div>
        <?php endif; ?>
    </div>
</div>
<!-- Footer -->
<footer class="footer text-center">
    <div class="container">
        <hr />
        <jdoc:include type="modules" name="footer" style="none" />

        <?php echo JoomlaTemplateHelper::getTemplateFooter($this->language, (bool) $this->params->get('useCdn', '1')); ?>
    </div>
</footer>

<jdoc:include type="modules" name="debug" style="none" />
</body>
</html>
