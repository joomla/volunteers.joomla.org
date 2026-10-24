<?php
/**
 * @package     SSO.Plugin
 * @subpackage  Authentication.sso
 *
 * @copyright   Copyright (C) 2017 - 2018 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Rolandd\Plugin\System\Sso\Extension;

use Exception;
use InvalidArgumentException;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Application\MultiFactorAuthenticationHandler;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Rolandd\Component\Sso\Administrator\Model\ProfileModel;
use Rolandd\Component\Sso\Administrator\Sso as SsoHelper;
use Rolandd\Component\Sso\Site\Model\LoginModel;
use Rolandd\Component\Sso\Site\Traits\SimpleSamlPhpTrait;
use stdClass;

defined('_JEXEC') or die;

/**
 * SSO Login Plugin
 *
 * @since  1.0.0
 */
class Sso extends CMSPlugin
{
    use DatabaseAwareTrait, SimpleSamlPhpTrait;

    /**
     * Check if a user needs to be logged-in via SSO. If there is no Itemid, access is granted
     *
     * @return  void
     *
     * @throws  Exception
     *
     * @since   1.0.0
     */
    public function onAfterInitialise()
    {
        $app = $this->getApplication();

        if ($app->isClient('administrator')) {
            $this->excludeBackgroundAjaxFromMfaCaptive($app);
            return;
        }

        if (!$this->params->get('autoLogin', false)) {
            $this->preventLogin();
            return;
        }

        $autoLoginArea = $this->params->get('autoLoginArea', '*');

        if ($autoLoginArea !== '*') {
            if (!$app->isClient($autoLoginArea)) {
                return;
            }
        }

        $profile    = $this->getProfile();
        $protocol   = $profile->protocol ?? 'saml';
        $userId     = (int)$app->getIdentity()->id;
        $uri        = Uri::getInstance();

        if ($userId !== 0
            || $this->isUrlExcluded($uri->toString())
            || $app->input->getCmd('task') === 'user.login') {
            return;
        }

        $url = '';

        if ($protocol === 'ldap') {

            if (empty($_SERVER['PHP_AUTH_USER'])) {
                return;
            }

            $this->tryLogin();

            return;
        }

        if (
            ($protocol === 'saml' && $app->input->get('option') !== 'com_sso')
        ) {
            $return = base64_encode($uri->toString());
            $url    = Uri::root() .
                'index.php?option=com_sso&task=login.login&profile=' . $this->params->get('profile', 'default-sp')
                . '&return=' . $return;
        }

        if ($url) {
            $app->redirect($url);
        }
    }

    /**
     * Joomla's Multi-factor Authentication captive login only skips storing the current
     * request as its post-login return URL when the request's 'format' is not 'html'
     * (@see MultiFactorAuthenticationHandler::isHandlingMultiFactorAuthentication,
     * which runs during route(), right after onAfterInitialise). The admin dashboard's Joomla
     * Update health-status widget fires a background AJAX call without setting 'format', so if
     * that call races a pending MFA challenge it can get stored as the return URL instead of the
     * page the user actually navigated to, leaving them stranded on the raw AJAX response after
     * completing MFA. Mark known background AJAX tasks as non-HTML ourselves so core treats them
     * as non-interactive instead of capturing them as the return URL.
     *
     * @param   CMSApplicationInterface  $app  The application object.
     *
     * @return  void
     *
     * @since   2.4.0
     */
    private function excludeBackgroundAjaxFromMfaCaptive(CMSApplicationInterface $app): void
    {
        $backgroundAjaxTasks = [
            'com_joomlaupdate' => ['update.healthstatus'],
        ];

        $option = $app->input->getCmd('option');
        $task   = $app->input->getCmd('task');

        if (in_array($task, $backgroundAjaxTasks[$option] ?? [], true)) {
            $app->input->set('format', 'json');
        }
    }

    /**
     * Hook into the onAfterRoute to facilitate the Single Sign On routine for the backend.
     *
     * @return  void
     *
     * @since   2.0.0
     */
    public function onAfterRoute(): void
    {
        $app = $this->getApplication();
        $user = $app->getIdentity();

        if ($user->id > 0) {
            return;
        }

        $escape = $this->params->get('escape', '');
        $uri    = Uri::getInstance();

        if (!empty($escape) && stristr($uri->getQuery(), $escape)) {
            return;
        }

        $autoLogin = $this->params->get('autoLogin', false);
        $input     = $app->input;
        $option    = $input->getCmd('option');
        $task      = $input->getCmd('task');
        $profile   = trim($input->getCmd('profile', ''));

        if ($app->isClient('site') || !$autoLogin && ($app->isClient('administrator') && $option !== 'com_sso' && $task !== 'login.login'))
        {
            return;
        }

        if (empty($profile)) {
            $profile = $this->params->get('profile', 'default-sp');
        }

        if (!stristr($uri->getPath(), '/administrator/') && ($option !== 'com_sso'
                || ($task !== 'login.login' && $task !== 'login.logout'))
        ) {
            return;
        }

        $originalQuery  = $uri->getQuery(true);
        $wasForcedLogin = !in_array($task, ['login.login', 'login.logout']);

        if ($wasForcedLogin) {
            $task = 'login.login';
            $input->set('option', 'com_login');
        }

        $autoLoginArea = $this->params->get('autoLoginArea', '*');

        if ($autoLogin && $autoLoginArea === 'site' && $app->isClient('administrator')) {
            return;
        }

        if (empty($originalQuery)) {
            $originalQuery['option']  = 'com_cpanel';
            $originalQuery['task']    = '';
            $originalQuery['profile'] = '';
        }

        try {
            /** @var LoginModel $model */
            $model = $this->getApplication()->bootComponent('com_sso')->getMVCFactory()->createModel('Login', 'Site', ['ignore_request' => true]);
            $model->setState('authorizationSource', $profile);

            if ($task === 'login.login') {
                $model->processLogin();

                /**
                 * Joomla's MFA captive-login gate (isHandlingMultiFactorAuthentication()) runs
                 * in route(), before onAfterRoute is dispatched - i.e. before the login above
                 * ever happened, while the user was still a guest. Continuing to dispatch the
                 * originally-requested page within this same request would render it for the
                 * now-authenticated user without that gate ever having run for them, letting one
                 * page through with no MFA challenge. Force a fresh HTTP request instead, so the
                 * gate gets a chance to see the logged-in user before any protected content is
                 * rendered.
                 */
                if ($wasForcedLogin) {
                    $app->redirect(Uri::base() . 'index.php?' . http_build_query($originalQuery));
                }
            }

            if ($task === 'login.logout') {
                $app->redirect($model->processLogout(true));
            }

            foreach ($originalQuery as $name => $value) {
                $app->input->set($name, $value);
            }
        } catch (Exception $exception) {
            $app->enqueueMessage($exception->getMessage());
        }
    }

    /**
     * Check if a URL is excluded from being forced to login SSO.
     *
     * @return  bool  True if URL is excluded | False otherwise.
     *
     * @since   1.5.0
     */
    private function isUrlExcluded(string $url): bool
    {
        $db    = $this->getDatabase();
        $query = $db->createQuery()
            ->select('COUNT(' . $db->quoteName('id') . ')')
            ->from($db->quoteName('#__sso_excludes'))
            ->where($db->quoteName('published') . ' = 1')
            ->where('INSTR(' . $db->quote($url) . ', ' . $db->quoteName('url') . ')');
        $db->setQuery($query);

        $matches = (int)$db->loadResult();

        if ($matches > 0) {
            return true;
        }

        return false;
    }

    /**
     * Logout a user from Single Sign On as well.
     *
     * @param   array  $options  The options received, client and firstname
     *
     * @return  void
     *
     * @since   1.5.0
     */
    public function onUserAfterLogout(array $options): void
    {
        if (!array_key_exists('profile', $options)) {
            $options['profile'] = $this->params->get('profile', false);
        }

        if ($options['profile'] === false) {
            throw new InvalidArgumentException(Text::_('PLG_SYSTEM_SSO_NO_PROFILE_SUPPLIED'));
        }

        $authorizationSource = $options['profile'] ?: 'default-sp';
        $instance = $this->loadSimpleSamlPhp($authorizationSource);
        $returnTo = rtrim(Uri::base(), '/');

        $instance->logout(['ReturnTo' => $returnTo]);
    }

    /**
     * Prevent user login on frontend.
     *
     * @return  void
     *
     * @throws  Exception
     * @since   1.0.0
     */
    private function preventLogin(): void
    {
        if (!$this->params->get('preventLogin', false)
            || $this->getApplication()->isClient('administrator')
        ) {
            return;
        }

        $app      = $this->getApplication();
        $redirect = false;
        $input    = $app->input;
        $uri      = Uri::getInstance();

        if (($input->getCmd('option') === 'com_users' && $uri->getVar('view', '') === '')
            || (stripos($uri->getPath(), '/component/users/login') !== false)
            || (($input->getCmd('option') === 'com_users' || $uri->getPath() === '/component/users/')
                && $uri->getVar('view') === 'login')
        ) {
            $session = $app->getSession();
            $session->set('application.queue', null);
            $redirect = true;
        }

        if (!$redirect) {
            $itemId = $input->getInt('Itemid');

            if ($itemId) {
                $menuLink = $this->getMenuLink($itemId);
                $menuUri  = Uri::getInstance($menuLink);

                if ($menuUri->getVar('option') === 'com_users' && $menuUri->getVar('view') === 'login') {
                    $redirect = true;
                }
            }
        }

        if ($redirect) {
            // Get the menu link
            $menuLink = $this->getMenuLink($this->params->get('menuRedirect', 0));
            $url      = '/';

            if ($menuLink !== null) {
                $url = '/' . ltrim($menuLink, '/');
            }

            $app->redirect($url);
        }
    }

    /**
     * Find the path for a given menu item ID.
     *
     * @param   integer  $id  The id of the menu to load
     *
     * @return  string  The menu path.
     *
     * @since   1.0.0
     */
    private function getMenuLink(int $id): string
    {
        $db    = $this->getDatabase();
        $query = $db->createQuery()
            ->select($db->quoteName('path'))
            ->from($db->quoteName('#__menu'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);
        $db->setQuery($query);

        return $db->loadResult();
    }

    /**
     * Load the Identity Provider profile.
     *
     * @return  stdClass  The profile details.
     *
     * @throws  Exception
     * @since   1.5.0
     */
    private function getProfile(): stdClass
    {
        $alias     = $this->params->get('profile', 'default-sp');
        $helper    = new SsoHelper\Sso;
        $profileId = $helper->getProfileId($alias);
        /** @var ProfileModel $model */
        $model = $this->getApplication()->bootComponent('com_sso')->getMVCFactory()->createModel('Profile', 'Administrator', ['ignore_request' => true]);
        $profile = $model->getItem($profileId);

        if (!$profile) {
            $profile = new stdClass();
        }

        return $profile;
    }

    /**
     * Try to log in a user with LDAP.
     *
     * @return  bool  True if login is successful.
     *
     * @since   1.6.0
     */
    private function tryLogin(): bool
    {
        try {
            /** @var LoginModel $loginModel */
            $loginModel = $this->getApplication()->bootComponent('com_sso')->getMVCFactory()->createModel('Login', 'Site', ['ignore_request' => true]);
            $loginModel->setState('authorizationSource', $this->params->get('profile', 'default-sp'));
            $loginModel->processLogin();

            return true;
        }
        catch (Exception $exception) {
            return false;
        }
    }
}
