<?php
/**
 * @package     SSO.Component
 *
 * @author      RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright   Copyright (C) 2017 - 2020 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://rolandd.com
 */

defined('_JEXEC') or die;

use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\User;
use Joomla\CMS\Uri\Uri;
use Joomla\Utilities\ArrayHelper;
use SimpleSAML\Auth\Simple;

/**
 * Login model.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class SsoModelLogin extends BaseDatabaseModel
{
	/**
	 * A SimpleSAML instance.
	 *
	 * @var Simple
	 *
	 * @since 1.0.0
	 */
	private $instance;

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
	public function __construct($config = array())
	{
		parent::__construct($config = array());

		$this->checkLibrary();
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
			include_once JPATH_LIBRARIES . '/simplesamlphp/lib/_autoload.php';
		}
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
		// Load the SimpleSAMLphp connector
		$this->loadSimpleSaml();

		// Validate authorization with SAML
		$this->instance->requireAuth(array('model' => 'from model'));

		if (!$this->instance->isAuthenticated())
		{
			throw new InvalidArgumentException(Text::_('COM_SSO_USER_NOT_AUTHENTICATED'));
		}

		// Load the profile name
		$authorizationSource = $this->getState('authorizationSource', 'default-sp');

		// Get the user fields
		$helper     = new SsoHelper;
		$userFields = $helper->processAttributes($authorizationSource, $this->instance->getAttributes());

		// Get the credentials
		$credentials = $this->processUser($userFields);

		// Log the user in
		Factory::getApplication()->login($credentials,
			array('profile' => $this->getState('authorizationSource', 'default-sp'))
		);
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
			// Take this from the config later
			$authorizationSource = $this->getState('authorizationSource', 'default-sp');
			$this->instance      = new Simple($authorizationSource);
		}
	}

	/**
	 * Process a logged in user. First verify if the user exists, otherwise create the user and finally login the user.
	 *
	 * @param   array  $userFields  The fields from the Identity Provider mapped to the user defined fields
	 *
	 * @return  array  List of credentials
	 *
	 * @since   1.0.0
	 */
	public function processUser(array $userFields): array
	{
		// Map the attributes from the first array returned
		$name     = $userFields['name'];
		$username = $userFields['username'];
		$email    = $userFields['email'] ?? '';

		// We must have an email address
		if (trim($email) === '')
		{
			$email = $username;
		}

		// Load the dispatcher
		PluginHelper::importPlugin('sso');
		$dispatcher = JEventDispatcher::getInstance();

		// Check if user exists
		if (!$this->doesUserExist($email, $username))
		{
			// Load the profile name
			$authorizationSource = $this->getState('authorizationSource', 'default-sp');

			// Get the user group
			$helper     = new SsoHelper;
			$parameters = $helper->getParams($authorizationSource);
			$userGroups = array();

			// Get the group mapping and apply if needed
			$userGroupMapping = (array) $parameters->get('joomla.userGroupMapping', array());

			array_walk($userGroupMapping,
				static function ($group) use (&$userGroups, $userFields) {
					if (isset($userFields[$group->mapName])
						&& $userFields[$group->mapName] === $group->mapValue)
					{
						$userGroups = array_merge($userGroups, $group->groupName);
					}
				}
			);

			// If the user groups are empty, we apply the default
			if (empty($userGroups))
			{
				$userGroups = $parameters->get('joomla.userGroup', array());
			}

			// Add a trigger for allowing adding custom user groups
			$dispatcher->trigger('onCollectUserGroups', array($userFields, &$userGroups));

			if (empty($userGroups))
			{
				throw new InvalidArgumentException(Text::_('COM_SSO_NO_USERGROUP'));
			}

			// Create the user
			$this->createUser($name, $username, $email, $userGroups);
		}

		// Trigger any plugins that want to do something with the logged-in user
		$dispatcher->trigger('onAfterProcessUser', array($userFields));

		// Gather the response details
		$options                 = array();
		$options['responseType'] = 'saml';

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
	private function doesUserExist($email, $username): bool
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
	 * @return  boolean True if exists | False otherwise.
	 *
	 * @since   1.0.0
	 */
	private function findUserId($email, $username): ?bool
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
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
	private function createUser($name, $username, $email, $userGroups = array(2)): bool
	{
		$userGroups = ArrayHelper::toInteger($userGroups);

		$db   = $this->getDbo();
		$date = new Date;
		$user = new User($db);
		$user->set('name', $name);
		$user->set('username', $username);
		$user->set('email', $email ?: $username);
		$user->set('registerDate', $date->toSql());
		$user->set('groups', $userGroups);

		if (!$user->store())
		{
			throw new RuntimeException(Text::_('COM_SSO_CANNOT_CREATE_JOOMLA_USER'));
		}

		// Trigger any plugins that want to do something with the new user
		PluginHelper::importPlugin('sso');
		$dispatcher = JEventDispatcher::getInstance();
		$dispatcher->trigger('onAfterCreateUser', array($user));

		return true;
	}

	/**
	 * Process the user logout.
	 *
	 * @return  string  The SAML logout URL
	 *
	 * @since   1.0.0
	 *
	 * @throws  Exception
	 */
	public function processLogout(): string
	{
		// Load the SimpleSAMLphp connector
		$this->loadSimpleSaml();

		// Log the user out of Joomla
		Factory::getApplication()->logout();

		$redirectUrl = $this->instance->getLogoutURL();

		// Clean up the URL so we don't come back here and cause a loop
		$uri = new Uri($redirectUrl);

		// Redirect back to the homepage
		$uri->setVar('ReturnTo', urlencode(Uri::root()));

		return $uri->toString();
	}
}
