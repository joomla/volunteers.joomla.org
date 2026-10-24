<?php
/**
 * @package     SSO.Plugin
 * @subpackage  Authentication.sso
 *
 * @copyright   Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Rolandd\Plugin\Authentication\Sso\Extension;

use Exception;
use InvalidArgumentException;
use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\User\AuthenticationEvent;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;
use Rolandd\Component\Sso\Administrator\Model\ProfileModel;
use Rolandd\Component\Sso\Administrator\Sso\Sso as SsoHelper;
use Rolandd\Component\Sso\Site\Model\LoginModel;
use Rolandd\Component\Sso\Site\Traits\SimpleSamlPhpTrait;
use SAML2\Constants;
use SimpleSAML\Auth\Simple;

defined('_JEXEC') or die;

/**
 * SSO Authentication Plugin
 *
 * @since  1.0
 */
class Sso extends CMSPlugin  implements SubscriberInterface
{
    use DatabaseAwareTrait, SimpleSamlPhpTrait;

    /**
     * A SimpleSAML instance.
     *
     * @var   Simple
     *
     * @since 1.0
     */
    private Simple $instance;

    /**
     * Affects constructor behavior. If true, language files will be loaded automatically.
     *
     * @var    boolean
     * @since  1.0.0
     */
    protected $autoloadLanguage = true;

    /**
     * Base path to SimpleSAMLphp
     *
     * @var    string
     * @since  2.0.0
     */
    private string $pathSimpleSamlPhp = JPATH_LIBRARIES . '/simplesamlphp';

    /**
     * Set if SimpleSAMLphp has been loaded
     *
     * @var    bool
     * @since  2.0.0
     */
    private bool $loaded = false;

    /**
     * Constructor
     *
     * @param   DispatcherInterface  $dispatcher  The event dispatcher
     * @param   array                $config      An optional associative array of configuration settings.
     *                                            Recognized key values include 'name', 'group', 'params', 'language'
     *                                            (this list is not meant to be comprehensive).
     *
     * @since   2.0.0
     */
    public function __construct(DispatcherInterface $dispatcher, array $config = [])
    {
        parent::__construct($dispatcher, $config);

        $this->checkLibrary();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'onUserAuthenticate'   => 'onUserAuthenticate',
        ];
    }

    /**
     * Load SimpleSAMLphp.
     *
     * @param   string  $authorizationSource  The authorization profile to use
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function loadSimpleSaml(string $authorizationSource = 'default-sp'): void
    {
        if (!$this->loaded)
        {
            $this->instance = $this->loadSimpleSamlPhp($authorizationSource);
            $this->loaded = true;
        }
    }

    /**
     * This method should handle any authentication and report back to the subject
     *
     * @param   AuthenticationEvent  $event
     *
     * @return  void
     *
     * @throws Exception
     * @since   1.0
     */
    public function onUserAuthenticate(AuthenticationEvent $event): void
    {
        $credentials = $event->getCredentials();

	    if (isset($credentials['responseType']) && $credentials['responseType'] !== 'saml')
	    {
		    return;
	    }

        if ($this->isEscaped())
        {
            return;
        }

        $response           = $event->getAuthenticationResponse();
        $options            = $event->getOptions();
        $authenticationArea = $this->params->get('authenticateArea', '*');

        if ($authenticationArea !== '*')
        {
            if (!$this->getApplication()->isClient($authenticationArea))
            {
                return;
            }
        }

        $option = $this->getApplication()->input->getCmd('option', 'com_sso');

        if (!in_array($option, ['com_sso', 'com_login', 'com_users', 'com_joomlaupdate']) || !ComponentHelper::isEnabled('com_sso'))
        {
            return;
        }

        if (!array_key_exists('profile', $options))
        {
            $options['profile'] = $this->params->get('profile', false);
        }

        if ($options['profile'] === false)
        {
            throw new InvalidArgumentException(Text::_('PLG_AUTHENTICATION_SSO_NO_PROFILE_SUPPLIED'));
        }

        $authorizationSource = $options['profile'] ?: 'default-sp';
        $this->loadSimpleSaml($authorizationSource);

        $params = [];

        if ($option === 'com_login' || $option === 'com_users')
        {
            $params = $this->initComLogin($credentials, $options, $params);
        }

        try
        {
            $this->instance->requireAuth($params);
        }
        catch (Exception $exception)
        {
            $response->status        = Authentication::STATUS_FAILURE;
            $response->error_message = Text::sprintf('JGLOBAL_AUTH_FAILED', $exception->getMessage());
        }

        if (!$this->instance->isAuthenticated())
        {
            $response->status        = Authentication::STATUS_FAILURE;
            $response->error_message = Text::sprintf(
                'JGLOBAL_AUTH_FAILED',
                Text::_('PLG_AUTHENTICATION_SSO_ERROR_USER_NOT_AUTHENTICATED')
            );

            return;
        }

        // Clean up the attributes
        $helper     = new SsoHelper();
        $attributes = $this->instance->getAttributes();
        $nodeName = $this->instance->getAuthData('saml:sp:NameID');
        $attributes['nameId'][] = '';

        if (!is_null($nodeName))
        {
            $attributes['nameId'][0] = $nodeName->getValue();
        }

        try {
            $userFields = $helper->processAttributes($authorizationSource, $attributes);
            $response->status         = Authentication::STATUS_SUCCESS;
            $response->error_message  = '';
            $response->email          = '';
            $response->username       = '';
            $response->fullname       = '';

            /*
             * The IdP's joomla:Joomla auth source releases an 'authnmethod' attribute set to
             * 'passwordless' when the user authenticated with a Passkey. Reporting the same
             * response type Joomla's own native Passkey login uses ('Passwordless') lets
             * Joomla's own onUserAfterLogin handler (disableMfaOnSilentLogin() in
             * plg_user_joomla) skip the MFA captive page here too - the same way it already does
             * for a native Passkey login, since a WebAuthn Passkey is itself a strong second
             * factor. Anything else (plain password login, or the attribute missing) keeps the
             * MFA captive page exactly as before.
             *
             * Separately, 'ssoSessionReused' (set by LoginModel::requireAuth()/processUser(),
             * still present here on the original $credentials from the event since it's only
             * overwritten further down) tells us whether *this* login involved no interactive
             * (re-)authentication at all - the browser already carried a live SimpleSAMLphp
             * session, e.g. the Joomla admin session timed out but the underlying SSO session
             * didn't. That's reported as its own 'SamlSilent' type rather than folded into
             * 'SAML', so a genuinely fresh SSO login still gets MFA by default - 'SamlSilent'
             * only skips MFA once an admin opts in by adding it to com_users' "Silent login
             * response types" (Users > Options), the same list 'cookie'/'passwordless' live in.
             */
            $authnMethod   = $attributes['authnmethod'][0] ?? '';
            $sessionReused = $credentials['ssoSessionReused'] ?? false;

            $response->type = match (true) {
                $authnMethod === 'passwordless' => 'Passwordless',
                (bool)$sessionReused => 'SamlSilent',
                default => 'SAML',
            };

            if (empty($userFields['name'][0]))
            {
                $response->status = Authentication::STATUS_FAILURE;
                $response->error_message = Text::_('PLG_AUTHENTICATION_SSO_NO_USER_FIELDMAP_NAME');
            }

            if (empty($userFields['username'][0]))
            {
                $response->status = Authentication::STATUS_FAILURE;
                $response->error_message = Text::_('PLG_AUTHENTICATION_SSO_NO_USER_FIELDMAP_USERNAME');
            }

            if (empty($userFields['email'][0]))
            {
                $response->status = Authentication::STATUS_FAILURE;
                $response->error_message = Text::_('PLG_AUTHENTICATION_SSO_NO_USER_FIELDMAP_EMAIL');
            }

            if ($response->status === Authentication::STATUS_SUCCESS)
            {
                /** @var LoginModel $loginModel */
                $loginModel = $this->getApplication()->bootComponent('com_sso')->getMVCFactory()->createModel('Login', 'Site', ['ignore_request' => true]);
                $loginModel->setState('authorizationSource', $authorizationSource);
                $credentials = $loginModel->processUser($userFields);

                $response->email    = $credentials['email'];
                $response->username = $credentials['username'];
                $response->fullname = $credentials['name'];

                $event->stopPropagation();
            }
        }
        catch (Exception $exception)
        {
            $response->status = Authentication::STATUS_UNKNOWN;
            $response->error_message = $exception->getMessage();
        }
    }

    /**
     * Check if the current request carries plg_system_sso's configured escape word, allowing the
     * user to bypass SSO and log in with their local Joomla credentials instead.
     *
     * @return  bool
     *
     * @since   2.3.1
     */
    private function isEscaped(): bool
    {
        $systemPlugin = PluginHelper::getPlugin('system', 'sso');

        if (!$systemPlugin)
        {
            return false;
        }

        $escape = (new Registry($systemPlugin->params))->get('escape', '');

        if (empty($escape))
        {
            return false;
        }

        return (bool) stristr(Uri::getInstance()->getQuery(), $escape);
    }

    /**
     * Check if the SimpleSAMLphp library can be found.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function checkLibrary(): void
    {
        if (!class_exists('SimpleSAML_Configuration'))
        {
            include_once $this->pathSimpleSamlPhp . '/lib/_autoload.php';
        }
    }

    /**
     * Initialise the session for logging in via com_login.
     *
     * @return  array  The updated params.
     *
     * @throws  Exception
     * @since   1.5.0
     */
    private function initComLogin(array $credentials, array $options, array $params): array
    {
        $helper    = new SsoHelper;
        $profileId = $helper->getProfileId($options['profile']);
        /** @var ProfileModel $profileModel */
        $profileModel = $this->getApplication()->bootComponent('com_sso')->getMVCFactory()->createModel('Profile', 'Administrator', ['ignore_request' => true]);
        $profile      = $profileModel->getItem($profileId);

        if (!isset($profile->protocol) || $profile->protocol !== 'ldap')
        {
            return $params;
        }

        $_SERVER['PHP_AUTH_USER'] = $credentials['username'];
        $_SERVER['PHP_AUTH_PW']   = $credentials['password'];
        $params['saml:Binding']   = Constants::BINDING_PAOS;

        return $params;
    }
}
