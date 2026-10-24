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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use Rolandd\Component\Sso\Site\Model\LoginModel;

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $app->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('field.passwordview')
    ->registerAndUseScript('mod_login.admin', 'mod_login/admin-login.min.js', [], ['defer' => true], ['core', 'form.validate']);

/** @var LoginModel $model */
$model = Factory::getApplication()->bootComponent('com_sso')->getMVCFactory()->createModel('Login', 'Site', ['ignore_request' => true]);

if (!$model) {
    require_once JPATH_ADMINISTRATOR . '/modules/mod_login/tmpl/default.php';

    return;
}

$db = Factory::getContainer()->get(DatabaseInterface::class);
$query = $db->createQuery()
        ->select($db->quoteName('params'))
        ->from($db->quoteName('#__extensions'))
        ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
        ->where($db->quoteName('folder') . ' = ' . $db->quote('authentication'))
        ->where($db->quoteName('element') . ' = ' . $db->quote('sso'));
$db->setQuery($query);
$params = new Registry($db->loadResult());
$profile = $params->get('profile');
$query->clear('where')
        ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
        ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
        ->where($db->quoteName('element') . ' = ' . $db->quote('sso'));
$db->setQuery($query);
$params = new Registry($db->loadResult());
$escape = $params->get('escape');
$language = Factory::getApplication()->getLanguage();
$language->load('com_sso', JPATH_ADMINISTRATOR . '/components/com_sso/');
$componentParams = ComponentHelper::getParams('com_sso');
$backendLogin = $componentParams->get('backendLogin', false);

Text::script('JSHOWPASSWORD');
Text::script('JHIDEPASSWORD');
?>
<form class="form-validate" action="<?php echo Route::_('index.php', true);  echo ($escape) ? '?escape=' . $escape : ''; ?>" method="post" id="form-login">
    <fieldset>
        <legend class="visually-hidden"><?php echo Text::_('MOD_LOGIN'); ?></legend>
        <div class="form-group">
            <label for="mod-login-username">
                <?php echo Text::_('JGLOBAL_USERNAME'); ?>
            </label>
            <div class="input-group">

                <input
                    name="username"
                    id="mod-login-username"
                    type="text"
                    class="form-control"
                    required="required"
                    autofocus
                    autocomplete="username"
                >
            </div>
        </div>
        <div class="form-group">
            <label for="mod-login-password">
                <?php echo Text::_('JGLOBAL_PASSWORD'); ?>
            </label>
            <div class="input-group">

                <input
                    name="passwd"
                    id="mod-login-password"
                    type="password"
                    class="form-control input-full"
                    required="required"
                    autocomplete="current-password"
                >
                <button type="button" class="btn btn-primary input-password-toggle">
                    <span class="icon-eye icon-fw" aria-hidden="true"></span>
                    <span class="visually-hidden"><?php echo Text::_('JSHOWPASSWORD'); ?></span>
                </button>

            </div>
        </div>

        <div class="mt-4">
            <?php if (!empty($langs)) : ?>
                <div class="form-group">
                    <label for="lang">
                        <?php echo Text::_('MOD_LOGIN_LANGUAGE'); ?>
                    </label>
                    <?php echo $langs; ?>
                </div>
            <?php endif; ?>
            <?php foreach ($extraButtons as $button) :
                $dataAttributeKeys = array_filter(array_keys($button), function ($key) {
                    return substr($key, 0, 5) == 'data-';
                });
                ?>
                <div class="form-group">
                    <button type="button"
                            class="btn btn-secondary w-100 <?php echo $button['class'] ?? '' ?>"
                    <?php foreach ($dataAttributeKeys as $key) : ?>
                        <?php echo $key ?>="<?php echo $button[$key] ?>"
                    <?php endforeach; ?>
                    <?php if ($button['onclick']) : ?>
                        onclick="<?php echo $button['onclick'] ?>"
                    <?php endif; ?>
                    title="<?php echo Text::_($button['label']) ?>"
                    id="<?php echo $button['id'] ?>"
                    >
                    <?php if (!empty($button['icon'])) : ?>
                        <span class="<?php echo $button['icon'] ?>"></span>
                    <?php elseif (!empty($button['image'])) : ?>
                        <?php echo $button['image']; ?>
                    <?php elseif (!empty($button['svg'])) : ?>
                        <?php echo $button['svg']; ?>
                    <?php endif; ?>
                    <?php echo Text::_($button['label']) ?>
                    </button>
                </div>
            <?php endforeach; ?>
            <div class="form-group">
                <button type="submit" id="btn-login-submit" class="btn btn-primary w-100 btn-lg"><?php echo Text::_('JLOGIN'); ?></button>
            </div>
            <input type="hidden" name="option" value="com_login">
            <input type="hidden" name="task" value="login">
            <input type="hidden" name="return" value="<?php echo $return; ?>">
            <?php echo HTMLHelper::_('form.token'); ?>
        </div>
    </fieldset>
</form>
<?php if ($backendLogin && $profile) : ?>
    <div class="mt-4">
        <div class="form-group">
            <?php if (!$model->isAuthenticated()) : ?>
                <a class="btn btn-primary w-100 btn-lg" href="<?php echo Route::_('index.php?option=com_sso&task=login.login&profile=' . $profile); ?>" class="btn btn-info btn-large login-button">
                    <span class="icon-lock icon-white"></span> <?php echo Text::_('COM_SSO_BACKEND_LOGIN_BUTTON'); ?>
                </a>
            <?php else: ?>
                <a class="btn btn-primary w-100 btn-lg" href="<?php echo Route::_('index.php?option=com_sso&task=login$$.logout&profile=' . $profile); ?>" class="btn btn-info btn-large login-button">
                    <span class="icon-lock icon-white"></span> <?php echo Text::_('COM_SSO_BACKEND_LOGOUT_BUTTON'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
<div class="text-center">
    <div>
        <?php echo HTMLHelper::link(
            Text::_('MOD_LOGIN_CREDENTIALS_LINK'),
            Text::_('MOD_LOGIN_CREDENTIALS'),
            [
                'target' => '_blank',
                'rel'    => 'noopener nofollow',
                'title'  => Text::sprintf('JBROWSERTARGET_NEW_TITLE', Text::_('MOD_LOGIN_CREDENTIALS'))
            ]
        ); ?>
    </div>
</div>
