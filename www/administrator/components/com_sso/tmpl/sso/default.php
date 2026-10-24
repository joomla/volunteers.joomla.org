<?php
/**
 * @package    SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\WebAsset\WebAssetManager;
use Rolandd\Component\Sso\Administrator\View\Sso\HtmlView;

defined('_JEXEC') or die;

/** @var HtmlView $this */

/** @var WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useStyle('com_sso.core');
$wa->useScript('com_sso.dashboard');

$basePath = stripos($this->config->get('baseurlpath'), 'http') !== false
    ? $this->config->get('baseurlpath')
    : Uri::root() . $this->config->get('baseurlpath');

$isLink                     = is_link(JPATH_SITE . '/' . (!str_ends_with($this->config->get('baseurlpath'), '/') ?: substr($this->config->get('baseurlpath'), 0, -1)));
$hasMetadataCertificate     = $this->config->get('metadata.sign.certificate', false);
$enableAuthenticationPlugin = PluginHelper::isEnabled('authentication', 'sso');
$enableSystemPlugin         = PluginHelper::isEnabled('system', 'sso');
$isIdentityProvider         = $this->config->get('enable.saml20-idp', false);
$isServiceProvider          = count($this->identityProviders) > 0;

$cronCommand = '/usr/bin/php ' . JPATH_SITE . '/libraries/simplesamlphp/modules/cron/bin/cron.php -t hourly > /dev/null 2>&1';

$statusPill = static function (bool $ready, ?string $pendingLabel = null, ?string $pendingHref = null): string
{
    if ($pendingLabel !== null)
    {
        $label = $pendingHref
            ? '<a href="' . $pendingHref . '">' . $pendingLabel . '</a>'
            : $pendingLabel;

        return '<span class="sso-pill sso-pill--pending"><i class="fa fa-arrow-right" aria-hidden="true"></i> ' . $label . '</span>';
    }

    return $ready
        ? '<span class="sso-pill sso-pill--ok"><i class="fa fa-check" aria-hidden="true"></i> ' . Text::_('COM_SSO_DASHBOARD_STATUS_READY') . '</span>'
        : '<span class="sso-pill sso-pill--warn"><i class="fa fa-ban" aria-hidden="true"></i> ' . Text::_('COM_SSO_DASHBOARD_STATUS_ACTION_NEEDED') . '</span>';
};

$copyButton = static function (string $value): string
{
    return '<button type="button" class="sso-copy-button" data-clipboard-text="' . htmlspecialchars($value, ENT_QUOTES) . '" title="' . Text::_('COM_SSO_COPY_TO_CLIPBOARD') . '">'
        . '<span class="fa fa-copy" aria-hidden="true"></span>'
        . '<span class="visually-hidden">' . Text::_('COM_SSO_COPY_TO_CLIPBOARD') . '</span>'
        . '</button>';
};
?>
<div id="j-main-container" class="j-main-container">
    <div class="sso-dashboard">
        <section class="sso-card sso-card--status">
            <div class="sso-card__header">
                <h3 class="sso-card__title"><?php echo Text::_('COM_SSO_DASHBOARD_CHECKLIST'); ?></h3>
            </div>
            <ul class="sso-status-list">
                <li class="sso-status-item">
                    <span class="sso-status-item__label"><?php echo Text::_('COM_SSO_DASHBOARD_SYMLINK'); ?></span>
                    <?php echo $statusPill($isLink); ?>
                </li>
                <li class="sso-status-item">
                    <?php echo HTMLHelper::_('link', 'index.php?option=com_plugins&view=plugins&filter[search]=Single%20Sign', Text::_('COM_SSO_DASHBOARD_ENABLE_AUTHENTICATION_PLUGIN'), 'class="sso-status-item__label" target="_blank"'); ?>
                    <?php echo $statusPill($enableAuthenticationPlugin); ?>
                </li>
                <li class="sso-status-item">
                    <?php echo HTMLHelper::_('link', 'index.php?option=com_plugins&view=plugins&filter[search]=Single%20Sign', Text::_('COM_SSO_DASHBOARD_ENABLE_SYSTEM_PLUGIN'), 'class="sso-status-item__label" target="_blank"'); ?>
                    <?php echo $statusPill($enableSystemPlugin); ?>
                </li>
                <?php if ($isIdentityProvider) : ?>
                    <li class="sso-status-item">
                        <span class="sso-status-item__label"><?php echo Text::_('COM_SSO_DASHBOARD_METADATA_SIGN_CERTIFICATE'); ?></span>
                        <?php echo $statusPill($hasMetadataCertificate); ?>
                    </li>
                    <li class="sso-status-item">
                        <span class="sso-status-item__label"><?php echo Text::_('COM_SSO_DASHBOARD_SITE_CONFIGURED_AS_IDENTITY_PROVIDER'); ?></span>
                        <?php echo $statusPill(true); ?>
                    </li>
                <?php endif; ?>
                <?php if ($isServiceProvider) : ?>
                    <li class="sso-status-item">
                        <span class="sso-status-item__label"><?php echo Text::_('COM_SSO_DASHBOARD_SITE_CONFIGURED_AS_SERVICE_PROVIDER'); ?></span>
                        <?php echo $statusPill(true); ?>
                    </li>
                <?php endif; ?>
                <li class="sso-status-item">
                    <span class="sso-status-item__label"><?php echo Text::_('COM_SSO_DASHBOARD_SETUP_CRONJOB'); ?></span>
                    <?php echo $statusPill(false, Text::_('COM_SSO_DASHBOARD_STATUS_SEE_BELOW'), '#sso-card-cron'); ?>
                </li>
            </ul>
        </section>

        <div class="sso-grid">
            <section class="sso-card">
                <div class="sso-card__header">
                    <h3 class="sso-card__title"><i class="fa fa-key" aria-hidden="true"></i> <?php echo Text::_('COM_SSO_DASHBOARD_METADATA'); ?></h3>
                </div>
                <?php if ($isIdentityProvider) :
                    $idpMetadataUrl = $basePath . 'module.php/saml/idp/metadata';
                    ?>
                    <span class="sso-eyebrow"><?php echo Text::_('COM_SSO_IDP_METADATA_LINK'); ?></span>
                    <ul class="sso-metadata-list">
                        <li class="sso-data-row">
                            <div class="sso-data-row__value">
                                <?php echo HTMLHelper::_('link', $idpMetadataUrl, $idpMetadataUrl, 'class="sso-mono-link"'); ?>
                            </div>
                            <?php echo $copyButton($idpMetadataUrl); ?>
                        </li>
                    </ul>
                <?php endif; ?>
                <?php if ($isServiceProvider) : ?>
                    <span class="sso-eyebrow"><?php echo Text::_('COM_SSO_SP_METADATA_LINK'); ?></span>
                    <ul class="sso-metadata-list">
                        <?php foreach ($this->identityProviders as $identityProvider) :
                            $url = $basePath . 'module.php/saml/sp/metadata/' . $identityProvider;
                            ?>
                            <li class="sso-data-row">
                                <div class="sso-data-row__value">
                                    <?php echo HTMLHelper::_('link', $url, $url, 'class="sso-mono-link"'); ?>
                                </div>
                                <?php echo $copyButton($url); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if (!$isIdentityProvider && !$isServiceProvider) : ?>
                    <p class="sso-empty-state"><?php echo Text::_('COM_SSO_DASHBOARD_METADATA_EMPTY'); ?></p>
                <?php endif; ?>
                <?php if ($isServiceProvider) : ?>
                    <div class="sso-card__footer">
                        <?php echo HTMLHelper::_('link', $basePath . 'module.php/admin/federation', Text::_('COM_SSO_SSP_FEDERATION'), 'target="_blank"'); ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="sso-card" id="sso-card-cron">
                <div class="sso-card__header">
                    <h3 class="sso-card__title"><i class="fa fa-clock" aria-hidden="true"></i> <?php echo Text::_('COM_SSO_DASHBOARD_SIMPLESAMLPHP_CRON'); ?></h3>
                </div>
                <p><?php echo HTMLHelper::_('link', $basePath . 'module.php/cron/info', Text::_('COM_SSO_SIMPLESAMLPHP_CRON'), 'target="_blank"'); ?></p>
                <p><?php echo HTMLHelper::_('link', 'https://simplesamlphp.org/docs/stable/cron/cron.html', Text::_('COM_SSO_SIMPLESAMLPHP_CRON_DOCUMENTATION'), 'target="_blank"'); ?></p>
                <span class="sso-eyebrow"><?php echo Text::_('COM_SSO_DASHBOARD_CRON_COMMAND'); ?></span>
                <div class="sso-command-row">
                    <code class="sso-command"><?php echo htmlspecialchars($cronCommand, ENT_QUOTES); ?></code>
                    <?php echo $copyButton($cronCommand); ?>
                </div>
            </section>

            <section class="sso-card">
                <div class="sso-card__header">
                    <h3 class="sso-card__title"><i class="fa fa-external-link-alt" aria-hidden="true"></i> <?php echo Text::_('COM_SSO_SIMPLESAMLPHP'); ?></h3>
                </div>
                <p><?php echo HTMLHelper::_('link', $basePath . 'admin', Text::_('COM_SSO_SIMPLESAMLPHP'), 'target="_blank"'); ?></p>
            </section>

            <section class="sso-card">
                <div class="sso-card__header">
                    <h3 class="sso-card__title"><i class="fa fa-tag" aria-hidden="true"></i> <?php echo Text::_('COM_SSO_DASHBOARD_VERSION'); ?></h3>
                </div>
                <dl class="sso-version-list">
                    <dt>SimpleSAMLphp</dt>
                    <dd><?php echo $this->samlVersion; ?></dd>
                    <dt>RO Single Sign On</dt>
                    <dd>2.3.1</dd>
                </dl>
            </section>
        </div>
    </div>
</div>
<form name="adminForm" id="adminForm" action="<?php echo Route::_('index.php?option=com_sso&view=sso', false); ?>" method="post">
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="<?php echo Factory::getApplication()->getSession()->getFormToken();?>" value="1" />
</form>
