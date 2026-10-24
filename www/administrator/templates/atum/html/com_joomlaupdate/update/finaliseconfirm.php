<?php

/**
 * @package    SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

/** @var \Joomla\Component\Joomlaupdate\Administrator\View\Update\HtmlView $this */

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive');

$app = Factory::getApplication();
$db  = Factory::getContainer()->get(DatabaseInterface::class);
$query = $db->createQuery()
    ->select($db->quoteName('params'))
    ->from($db->quoteName('#__extensions'))
    ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
    ->where($db->quoteName('folder') . ' = ' . $db->quote('authentication'))
    ->where($db->quoteName('element') . ' = ' . $db->quote('sso'));
$db->setQuery($query);
$params  = new Registry($db->loadResult());
$profile = $params->get('profile');

$language = $app->getLanguage();
$language->load('com_sso', JPATH_ADMINISTRATOR . '/components/com_sso/');
?>

<div class="col-12 col-lg-8 col-xl-6 mx-auto">

    <div class="alert warning">
        <h4 class="alert-heading">
            <?php echo Text::_('COM_JOOMLAUPDATE_VIEW_UPDATE_FINALISE_HEAD'); ?>
        </h4>
        <p class="mb-0">
            <?php echo Text::sprintf('COM_JOOMLAUPDATE_VIEW_UPDATE_FINALISE_HEAD_DESC', $app->get('sitename')); ?>
        </p>
    </div>

    <div class="card mt-4">
        <div class="card-body p-4">
            <form action="<?php echo Route::_('index.php', true); ?>" method="post" id="form-login" class="text-center">
                <fieldset class="loginform">
                    <legend class="h5 mb-3"><?php echo Text::_('COM_JOOMLAUPDATE_CONFIRM'); ?></legend>
                    <div class="control-group">
                        <div class="controls">
                            <div class="input-group">
                                <input name="username" id="mod-login-username" type="text" class="form-control" required="required" autocomplete="username" placeholder="<?php echo Text::_('JGLOBAL_USERNAME'); ?>" size="15" autofocus="true">
                                <span class="input-group-text">
                                    <span class="icon-user" aria-hidden="true"></span>
                                    <label for="mod-login-username" class="visually-hidden">
                                        <?php echo Text::_('JGLOBAL_USERNAME'); ?>
                                    </label>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="control-group mt-3">
                        <div class="controls">
                            <div class="input-group">
                                <input name="passwd" id="mod-login-password" type="password" class="form-control" required="required" autocomplete="current-password" placeholder="<?php echo Text::_('JGLOBAL_PASSWORD'); ?>" size="15">
                                <span class="input-group-text">
                                    <span class="icon-lock" aria-hidden="true"></span>
                                    <label for="mod-login-password" class="visually-hidden">
                                        <?php echo Text::_('JGLOBAL_PASSWORD'); ?>
                                    </label>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="control-group mt-4">
                        <div class="controls">
                            <div class="btn-group">
                                <a class="btn btn-danger" href="index.php?option=com_joomlaupdate">
                                    <span class="icon-times" aria-hidden="true"></span> <?php echo Text::_('JCANCEL'); ?>
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <span class="icon-play" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMLAUPDATE_VIEW_UPDATE_FINALISE_CONFIRM_AND_CONTINUE'); ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="option" value="com_joomlaupdate">
                    <input type="hidden" name="task" value="update.finaliseconfirm">
                    <?php echo HTMLHelper::_('form.token'); ?>
                </fieldset>
            </form>

            <?php if ($profile) : ?>
                <div class="d-flex align-items-center text-muted my-4">
                    <hr class="flex-grow-1">
                    <span class="mx-3 small text-uppercase"><?php echo Text::_('COM_SSO_BACKEND_LOGIN_DIVIDER'); ?></span>
                    <hr class="flex-grow-1">
                </div>

                <form action="<?php echo Route::_('index.php', true); ?>" method="post" class="text-center">
                    <button type="submit" class="btn btn-primary">
                        <span class="icon-lock icon-white" aria-hidden="true"></span>
                        <?php echo Text::_('COM_SSO_BACKEND_LOGIN_BUTTON'); ?>
                    </button>
                    <input type="hidden" name="username" value="<?php echo htmlspecialchars($app->getIdentity()->username, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="passwd" value="sso">
                    <input type="hidden" name="option" value="com_joomlaupdate">
                    <input type="hidden" name="task" value="update.finaliseconfirm">
                    <?php echo HTMLHelper::_('form.token'); ?>
                </form>
            <?php endif; ?>
        </div>
    </div>

</div>
