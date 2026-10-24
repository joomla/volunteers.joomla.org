<?php
/**
 * @package     SSO.Component
 *
 * @author      RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright   Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://rolandd.com
 */

namespace Rolandd\Component\Sso\Site\Model;

defined('_JEXEC') or die;

use Exception;
use InvalidArgumentException;
use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\User;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\Event;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Rolandd\Component\Sso\Administrator\Model\ProfileModel;
use Rolandd\Component\Sso\Administrator\Sso\Sso;
use Rolandd\Component\Sso\Site\Traits\SimpleSamlPhpTrait;
use RuntimeException;
use SAML2\Constants;
use SimpleSAML\Auth\Simple;

/**
 * Login model.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class LoginModel extends BaseDatabaseModel
{
    use SimpleSamlPhpTrait;

	/**
	 * A SimpleSAML instance.
	 *
	 * @var Simple|null
	 *
	 * @since 1.0.0
	 */
	private ?Simple $instance = null;

    /**
	 * Whether this login involved no interactive (re-)authentication at all, i.e. the browser
	 * already carried a live SimpleSAMLphp session from an earlier, unrelated visit, rather than
	 * one just established by the redirect round trip that led to this very request. Determined
	 * in requireAuth() via a marker in Joomla's own session (see PENDING_LOGIN_SESSION_KEY) -
	 * isAuthenticated() alone cannot tell the two apart, since requireAuth() only ever returns
	 * once a session already exists, whether that session was established moments ago by this
	 * very login or long before it, and how long before is not a reliable signal either: a
	 * second, genuinely-reused login attempted shortly after a first fresh one still carries the
	 * same, still-recent IdP authentication instant. See processUser().
     *
     * @var bool
     *
     * @since 2.1.0
     */
    private bool $ssoSessionReused = false;

	/**
	 * Joomla session key used to mark that this request is about to send the browser off to the
	 * IdP for an actual interactive login, so that the request handling the return leg of that
	 * same round trip - which finds a session that already validates as authenticated, just like
	 * a genuinely reused one would - can still recognise itself as the fresh login it is.
	 *
	 * @var string
	 *
	 * @since 2.1.1
	 */
	private const string PENDING_LOGIN_SESSION_KEY = 'com_sso.freshLoginPending';

	/**
	 * Constructor
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *                          Recognized key values include 'name', 'group', 'params', 'language'
	 *                          (this list is not meant to be comprehensive).
	 *
	 * @since   1.0.0
	 *
	 * @throws  Exception
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);

		$this->checkSimpleSamlPhp();
	}

	/**
	 * Process the user login.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 *
	 * @throws  Exception
	 */
	public function processLogin(): void
	{
		$this->loadSimpleSaml();

		// Load the profile name
		$authorizationSource = $this->getState('authorizationSource', 'default-sp');

		// Validate authorization with SAML
		$this->requireAuth($authorizationSource);

		if (!$this->instance->isAuthenticated())
		{
			throw new InvalidArgumentException(Text::_('COM_SSO_USER_NOT_AUTHENTICATED'));
		}

		// Get the user fields
		$helper     = new Sso;
		$attributes = $this->instance->getAttributes();

        $nodeName = $this->instance->getAuthData('saml:sp:NameID');
        $attributes['nameId'][] = '';

        if (!is_null($nodeName))
        {
		    $attributes['nameId'][0] = $nodeName->getValue();
        }

	    $userFields = $helper->processAttributes($authorizationSource, $attributes);

        if (!isset($userFields['username'][0]))
        {
            throw new InvalidArgumentException(Text::_('COM_SSO_USER_MISSING_USERNAME'));
        }

		// Get the credentials
		$credentials = $this->processUser($userFields);

		// Log the user in
		$result = Factory::getApplication()->login($credentials,
			['profile' => $authorizationSource]
		);

        if (!$result)
        {
            throw new InvalidArgumentException(Text::_('COM_SSO_USER_NOT_AUTHENTICATED'));
        }

        $username = $userFields['username'][0] ?? '';
        $email    = $userFields['email'][0] ?? '';

        if (trim($email) === '')
        {
            $email = $username;
        }

        $userGroups = $this->getUserGroups($userFields);

        PluginHelper::importPlugin('sso');

        // Add a trigger for allowing adding custom user groups
        $event = new Event('onCollectUserGroups', [$userFields, &$userGroups]);
        Factory::getApplication()->getDispatcher()->dispatch('onCollectUserGroups', $event);

        if (empty($userGroups))
        {
            throw new InvalidArgumentException(Text::_('COM_SSO_NO_USERGROUP'));
        }

        $this->assignUserGroups($email, $username, $userGroups);
	}

	/**
	 * Load SimpleSAMLphp.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function loadSimpleSaml(): void
	{
		if (!$this->instance)
		{
			$authorizationSource = $this->getState('authorizationSource', 'default-sp');
			$this->instance      = $this->loadSimpleSamlPhp($authorizationSource);
		}
	}

    /**
     * Process a logged in user. First verify if the user exists, otherwise create the user and finally login the user.
     *
     * @param   array  $userFields  The fields from the Identity Provider mapped to the user defined fields
     *
     * @return  array  List of credentials
     *
     * @throws  Exception
     * @since   1.0.0
     */
	public function processUser(array $userFields): array
	{
		$name     = $userFields['name'][0] ?? '';
		$username = $userFields['username'][0] ?? '';
		$email    = $userFields['email'][0] ?? '';

		if (trim($email) === '')
		{
			$email = $username;
		}

		PluginHelper::importPlugin('sso');

		if (!$this->doesUserExist($email, $username))
		{
            $parameters = $this->getParameters();

            if (!$parameters->get('joomla.createUser', 1))
            {
                throw new NotAllowed(Text::_('COM_SSO_NO_ACCESS_TO_SITE'), 403);
            }

            $userGroups = $this->getUserGroups($userFields);

			// Add a trigger for allowing adding custom user groups
            $event = new Event('onCollectUserGroups', [$userFields, &$userGroups]);
            Factory::getApplication()->getDispatcher()->dispatch('onCollectUserGroups', $event);

			if (empty($userGroups))
			{
				throw new InvalidArgumentException(Text::_('COM_SSO_NO_USERGROUP'));
			}

			$this->createUser($name, $username, $email, $userGroups);
		}

		// Trigger any plugins that want to do something with the logged-in user
        $event = new Event('onAfterProcessUser', [$userFields]);
        Factory::getApplication()->getDispatcher()->dispatch('onAfterProcessUser', $event);

        $options                     = [];
        $options['name']             = $name;
        $options['username']         = $username;
        $options['email']            = $email;
        $options['responseType']     = 'saml';
        $options['ssoSessionReused'] = $this->ssoSessionReused;

		return $options;
	}

	/**
	 * Find a user in the database.
	 *
	 * @param   string  $email     The email to verify
	 * @param   string  $username  The username to verify
	 *
	 * @return  boolean True if exists | False otherwise.
	 *
	 * @since   1.0.0
	 */
	private function doesUserExist(string $email, string $username): bool
	{
		if (!$this->findUserId($email, $username))
		{
			return false;
		}

		return true;
	}

	/**
	 * Get the user ID in the database.
	 *
	 * @param   string  $email     The email to verify
	 * @param   string  $username  The username to verify
	 *
	 * @return  integer|null True if exists | False otherwise.
	 *
	 * @since   1.0.0
	 */
	private function findUserId(string $email, string $username): ?int
	{
		$db    = $this->getDatabase();
		$query = $db->createQuery()
			->select($db->quoteName('id'))
			->from($db->quoteName('#__users'))
			->where($db->quoteName('email') . ' = ' . $db->quote($email), 'OR')
			->where($db->quoteName('username') . ' = ' . $db->quote($username));
		$db->setQuery($query);

		return $db->loadResult();
	}

	/**
	 * Create a user that doesn't exist.
	 *
	 * @param   string  $name        The full name
	 * @param   string  $username    The username
	 * @param   string  $email       The email address
	 * @param   array   $userGroups  The user group to assign the user to
	 *
	 * @return  boolean  True on success | False on failure
	 *
	 * @since   1.0.0
	 *
	 * @throws  RuntimeException
	 */
	private function createUser(string $name, string $username, string $email, array $userGroups = [2]): bool
	{
		$userGroups = ArrayHelper::toInteger($userGroups);

		$db   = $this->getDatabase();
		$date = new Date;
		$user = new User($db);
		$user->set('name', $name);
		$user->set('username', $username);
		$user->set('email', $email ?: $username);
		$user->set('registerDate', $date->toSql());
		$user->set('groups', $userGroups);
		$user->set('params', '');

		if (!$user->store())
		{
			throw new RuntimeException(Text::_('COM_SSO_CANNOT_CREATE_JOOMLA_USER'));
		}

		// Trigger any plugins that want to do something with the new user
		PluginHelper::importPlugin('sso');


        $event = new Event('onAfterCreateUser', [$user]);
        Factory::getApplication()->getDispatcher()->dispatch('onAfterCreateUser', $event);

		return true;
	}

	/**
	 * Process the user logout.
	 *
	 * @param   bool  $adminUrl  Set if the redirect should go to the administrator side
	 *
	 * @return  string  The SAML logout URL
	 *
	 * @since   1.0.0
	 * @throws  Exception
	 */
	public function processLogout(bool $adminUrl = false): string
	{
		$this->loadSimpleSaml();

		Factory::getApplication()->logout();

		$redirectUrl = $this->instance->getLogoutURL();

		$uri = new Uri($redirectUrl);
		$url = Uri::root();

		if ($adminUrl)
		{
			$url .= '/administrator';
		}

		$uri->setVar('ReturnTo', urlencode($url));

		return $uri->toString();
	}

	/**
	 * Check if a user has already logged-in via SSO.
	 *
	 * @return  boolean  True if logged-in | False otherwise.
	 *
	 * @since   1.4.0
	 */
	public function isAuthenticated(): bool
	{
		$this->loadSimpleSaml();

		return $this->instance->isAuthenticated();
	}

	/**
	 * Load the user groups based on their mapping.
	 *
	 * @return  array  List of user groups.
	 *
	 * @since   1.5.0
	 */
	private function getUserGroups(array $userFields): array
	{
		$parameters = $this->getParameters();
		$userGroups = [];

		// Get the group mapping and apply if needed
		$userGroupMapping = (array) $parameters->get('joomla.userGroupMapping', []);

		array_walk($userGroupMapping,
			static function ($group) use (&$userGroups, $userFields) {
				if (isset($userFields[$group->mapName])
					&& ($userFields[$group->mapName] === $group->mapValue
						|| in_array($group->mapValue, $userFields[$group->mapName])))
				{
					$userGroups = array_merge($userGroups, $group->groupName);
				}
			}
		);

		// If the user groups are empty, we apply the default
		if (empty($userGroups))
		{
			$userGroups = $parameters->get('joomla.userGroup', []);
		}

		return $userGroups;
	}

	/**
	 * Assign user groups to a given user.
	 *
	 * @param   string  $email
	 * @param   string  $username
	 * @param   array   $userGroups
	 *
	 * @return  void
	 *
	 * @since   1.5.0
	 */
	private function assignUserGroups(string $email, string $username, array $userGroups): void
	{
		$userId = $this->findUserId($email, $username);

		if (!$userId)
		{
			return;
		}

		$parameters = $this->getParameters();

		if (!$parameters->get('joomla.syncUserGroups', 0))
		{
			return;
		}

		$user = Factory::getUser($userId);

		if ($parameters->get('joomla.keepUserGroups', 0))
		{
			$userGroups = array_merge($user->groups, $userGroups);
		}

		$userGroups = ArrayHelper::toInteger($userGroups);

		$user->set('groups', $userGroups);

		if (!$user->save(true))
		{
			throw new RuntimeException(Text::sprintf('COM_SSO_CANNOT_UPDATE_JOOMLA_USER_GROUPS', $user->getError()));
		}
	}

	/**
	 * Load the profile parameters.
	 *
	 * @return  Registry  The loaded parameters.
	 *
	 * @since   1.5.0
	 */
	public function getParameters(): Registry
	{
		$authorizationSource = $this->getState('authorizationSource', 'default-sp');
		$helper              = new Sso;

		return $helper->getParams($authorizationSource);
	}

	/**
	 * Check if authentication is required.
	 *
	 * Additional parameters are needed for LDAP
	 *
	 * @param   string  $authorizationSource The profile to use
	 *
	 * @return  void
	 *
	 * @throws Exception
	 * @since   1.5.0
	 */
	private function requireAuth(string $authorizationSource): void
	{
		$helper    = new Sso;
		$profileId = $helper->getProfileId($authorizationSource);

		/** @var ProfileModel $profileModel */
        $profileModel = $this->getMVCFactory()->createModel('Profile', 'Administrator', ['ignore_request' => true]);
		$profile      = $profileModel->getItem($profileId);
		$params = [
			'model' => 'from model'
		];

		if (isset($profile->protocol) && $profile->protocol === 'ldap')
		{
            /**
             * $_SERVER['LOGON_USER']
             * $_SERVER['AUTH_USER']
             * $_SERVER['REDIRECT_LOGON_USER']
             * $_ENV['REDIRECT_LOGON_USER']
             * $_SERVER['REDIRECT_REMOTE_USER']
             * @see \SimpleSAML\Module\core\Auth\UserPassBase::authenticate;
             */
            if (empty($_SERVER['PHP_AUTH_USER']))
            {
                $url = Route::_('index.php?option=com_users&view=login');
                Factory::getApplication()->redirect($url);
            }

            $_SERVER['PHP_AUTH_USER'] = strtolower(str_ireplace(explode(',', $profile->usernameCleanup), '', $_SERVER['PHP_AUTH_USER']));

            /**
             * @todo Password must always be filled otherwise automatic login without password does not work
             */
            if (!isset($_SERVER['PHP_AUTH_PW']))
            {
                $_SERVER['PHP_AUTH_PW'] = (string) mt_rand();
            }

			$params['KeepPost'] = true;
			$params['saml:Binding'] = Constants::BINDING_PAOS;
		}

		$session = Factory::getApplication()->getSession();

		if (!$this->instance->isAuthenticated())
		{
			// About to redirect to the IdP for an actual interactive login. This request ends
			// here (requireAuth() below exits via redirect); the marker survives in the session
			// for the request that handles the return leg of this same round trip.
			$session->set(self::PENDING_LOGIN_SESSION_KEY, true);
		}

		$this->instance->requireAuth($params);

		// A session is now known to be valid - either because it was just established by the
		// redirect round trip kicked off above, or because it already existed before this
		// request started. The marker (only ever set moments before a redirect we ourselves
		// triggered) is what tells the two apart.
		$this->ssoSessionReused = !$session->get(self::PENDING_LOGIN_SESSION_KEY, false);
		$session->set(self::PENDING_LOGIN_SESSION_KEY, null);
	}
}
