<?php
/**
 * Joomla.org site template
 *
 * @copyright   Copyright (C) 2005 - 2023 Open Source Matters, Inc. All rights reserved.
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

// Declare the template as HTML5
$this->setHtml5(true);

$app    = Factory::getApplication();
$params = $app->getTemplate(true)->params;

// Detecting Active Variables
$option   = $app->input->getCmd('option', '');
$view     = $app->input->getCmd('view', '');
$layout   = $app->input->getCmd('layout', 'default');
$task     = $app->input->getCmd('task', 'display');
$itemid   = $app->input->getUint('Itemid', 0);
$sitename = $app->get('sitename');

$faCSSUrl = HTMLHelper::_('stylesheet', 'system/joomla-fontawesome.min.css', ['pathOnly' => true, 'version' => 'auto', 'relative' => true, 'detectDebug' => false], []);

if ($this->direction === 'ltr')
{
    // Set the CSS URL based on whether we're in debug mode or it was explicitly chosen to not use the CDN
    if (JDEBUG || !$this->params->get('useCdn', '1'))
    {
        $cssURL = HTMLHelper::_('stylesheet', 'template.min.css', ['pathOnly' => true, 'relative' => true, 'detectDebug' => (bool)JDEBUG, 'version' => '4.0.10']);
    }
    else
    {
        $cssURL = 'https://cdn.joomla.org/template/css/template_4.0.10.min.css';
    }

    // Optional site specific CSS override, prefer a minified custom.css file first
    $customCss = HTMLHelper::_('stylesheet', 'custom.css', ['pathOnly' => true, 'version' => 'auto', 'relative' => true, 'detectDebug' => false], []);
}
else
{
    // Set the CSS URL based on whether we're in debug mode or it was explicitly chosen to not use the CDN
    if (JDEBUG || !$this->params->get('useCdn', '1'))
    {
        $cssURL = HTMLHelper::_('stylesheet', 'template-rtl.min.css', ['pathOnly' => true, 'relative' => true, 'detectDebug' => (bool) JDEBUG, 'version' => '4.0.10']);
    }
    else
    {
        $cssURL = 'https://cdn.joomla.org/template/css/template-rtl_4.0.10.min.css';
    }

    // Optional support for custom RTL CSS rules
    $customCss = HTMLHelper::_('stylesheet', 'custom-rtl.css', ['pathOnly' => true, 'version' => 'auto', 'relative' => true, 'detectDebug' => false], []);
}

$languageModCss = false;

// If the multilanguage module is enabled, load its CSS - We do this solely by an isEnabled check because there isn't efficient API to do more checks
if (ModuleHelper::isEnabled('mod_languages'))
{
    $languageModCss = HTMLHelper::_('stylesheet', 'mod_languages/template.css', ['pathOnly' => true, 'version' => 'auto', 'relative' => true, 'detectDebug' => (bool) JDEBUG], []);
}

// Load template JavaScript
$templateJs = HTMLHelper::_('script', 'templates/site/joomla/template.js', ['pathOnly' => true, 'version' => 'auto', 'relative' => true, 'detectDebug' => (bool) JDEBUG], []);
$adBlockJs  = HTMLHelper::_('script', 'templates/site/joomla/blockadblock.js', ['pathOnly' => true, 'version' => 'auto', 'relative' => true, 'detectDebug' => (bool) JDEBUG], []);
$cookieJs   = HTMLHelper::_('script', 'templates/site/joomla/js.cookie.js', ['pathOnly' => true, 'version' => 'auto', 'relative' => true, 'detectDebug' => (bool) JDEBUG], []);

// Set the replacement for the position-0 module loaded from the CDN'd menu
$search      = '<jdoc:include type="modules" name="position-0" style="none" />';
$replacement = '';

foreach (ModuleHelper::getModules('position-0') as $module)
{
    $replacement .= ModuleHelper::renderModule($module, ['style' => 'none']);
}

// Get the GTM property ID
$gtmId = JoomlaTemplateHelper::getGtmId(Uri::getInstance()->toString(['host']));

// If Cookie Control is enabled, we expose the GTM ID as a JavaScript var versus registering GTM directly
$hasCookieControl = $params->get('cookieControlActive', 0);
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
    <meta charset="utf-8" />
    <base href="<?php echo $this->getBase(); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="language" content="<?php echo $this->language; ?>" />
    <title><?php echo $this->title; ?> <?php echo $this->error->getMessage();?></title>
    <link href="/templates/joomla/favicon.ico" rel="icon" type="image/vnd.microsoft.icon" />
    <link href="<?php echo $faCSSUrl ?>" rel="stylesheet" />
    <link href="<?php echo $cssURL ?>" rel="stylesheet" />
    <?php if ($customCss) : ?>
        <link href="<?php echo $customCss ?>" rel="stylesheet" />
    <?php endif; ?>
    <?php if ($languageModCss) : ?>
        <link href="<?php echo $languageModCss ?>" rel="stylesheet" />
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
    <?php if ($gtmId && $hasCookieControl) : ?>
        <?php // Purposefully declare a global variable versus using the Joomla.options JavaScript API for compatibility with non-Joomla (CMS) installations ?>
        <script type="text/javascript">var propertyGtmId = '<?php echo $gtmId; ?>';</script>
    <?php endif; ?>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:wght@300;400;700&display=swap" rel="stylesheet" />
</head>
<body class="<?php echo "site error $option view-$view layout-$layout task-$task itemid-$itemid" . ($this->direction == 'rtl' ? ' rtl' : ''); ?>">
<?php
// Add Google Tag Manager code if one is set
if ($gtmId && !$hasCookieControl) : ?>
    <!-- Google Tag Manager -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo $gtmId; ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?php echo $gtmId; ?>');</script>
    <!-- End Google Tag Manager -->
<?php endif; ?>
<!-- Top Nav -->
<nav class="navigation py-md-1" role="navigation">
    <div id="mega-menu" class="navbar navbar-expand-md">
        <div class="container-xxl">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#primaryMenu" aria-controls="primaryMenu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <?php echo str_replace($search, $replacement, JoomlaTemplateHelper::getTemplateMenu($this->language, (bool) $params->get('useCdn', '1'))); ?>
        </div>
    </div>
</nav>
<!-- Header -->
<header class="header">
    <div class="container-md">
        <div class="row">
            <div class="col-md-7">
                <h1 class="page-title">
                    <a href="<?php echo Uri::root(); ?>/"><span aria-hidden="true" class="icon-joomla me-2"></span><?php echo HTMLHelper::_('string.truncate', $sitename, 40, false, false);?></a>
                </h1>
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
<nav class="subnav-wrapper">
    <div class="subnav">
        <div class="container-md">
            <?php foreach (ModuleHelper::getModules('position-1') as $searchmodule) :
                echo ModuleHelper::renderModule($searchmodule, ['style' => 'none']);
            endforeach; ?>
        </div>
    </div>
</nav>
<!-- Body -->
<div class="body">
    <div class="container">
        <div class="row">
            <main id="content" class="col-md-12">
                <!-- Begin Content -->
                <div class="marge">
                    <div class="row">
                        <div class="col-md-6 text-center">
                            <img class="mw-100" src="templates/joomla/images/error.jpg" alt="Oops! Something went wrong">
                        </div>
                        <div class="col-md-6">
                            <div class="errorborder">
                                <h2><?php echo Text::_('TPL_JOOMLA_ERROR_LAYOUT_ERROR_HAS_OCCURRED'); ?></h2>
                                <p><?php echo Text::_('TPL_JOOMLA_ERROR_LAYOUT_DONT_WORRY'); ?></p>
                            </div>
                            <h3><?php echo Text::_('TPL_JOOMLA_ERROR_LAYOUT_SEARCH'); ?></h3>
                            <p><?php echo Text::_('TPL_JOOMLA_ERROR_LAYOUT_SEARCH_SITE'); ?></p>
                            <?php echo ModuleHelper::renderModule(ModuleHelper::getModule($params->get('searchModule', 'search'))); ?>
                            <p><?php echo Text::_('TPL_JOOMLA_ERROR_LAYOUT_START_AGAIN'); ?></p>
                            <p><a href="<?php echo Uri::root(); ?>" class="btn btn-primary btn-lg error"> <?php echo Text::_('JERROR_LAYOUT_HOME_PAGE'); ?></a></p>
                        </div>
                    </div>
                    <hr />
                    <p><?php echo Text::_('JERROR_LAYOUT_PLEASE_CONTACT_THE_SYSTEM_ADMINISTRATOR'); ?></p>
                    <blockquote>
                        <span class="badge bg-dark"><?php echo $this->error->getCode(); ?></span> <?php echo htmlspecialchars($this->error->getMessage(), ENT_QUOTES, 'UTF-8'); ?>
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
<footer class="footer text-center">
    <div class="container">
        <hr />

        <?php echo JoomlaTemplateHelper::getTemplateFooter($this->language, (bool) $params->get('useCdn', '1')); ?>
    </div>
</footer>
</body>
</html>
