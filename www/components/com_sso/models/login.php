<?php
/**
 * @package     SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2018 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\User;
use Joomla\CMS\Uri\Uri;
use SimpleSAML\Auth\Simple;

defined('_JEXEC') or die;

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
	 * @throws  Exception
	 *
	 * @since   1.0.0
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
	private function checkLibrary()
	{
		if (!class_exists('SimpleSAML_Configuration'))
		{
			include_once JPATH_LIBRARIES . '/simplesamlphp/lib/_autoload.php';
		}
	}

	/**
	 * Load SimpleSAMLphp.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function loadSimpleSaml()
	{
		if (!$this->instance)
		{
			// Take this from the config later
			$authorizationSource = $this->getState('authorizationSource', 'default-sp');
			$this->instance      = new Simple($authorizationSource);
		}
	}

	/**
	 * Process the user login.
	 *
	 * @return  void
	 *
	 * @throws  InvalidArgumentException
	 * @throws  Exception
	 *
	 * @since   1.0.0
	 */
	public function processLogin()
	{
		// Load the SimpleSAMLphp connector
		$this->loadSimpleSaml();

		// Validate authorization with SAML
		$this->instance->requireAuth(array('model' => 'from model'));

		if (!$this->instance->isAuthenticated())
		{
			throw new InvalidArgumentException(Text::_('COM_SSO_USER_NOT_AUTHENTICATED'));
		}

		// Load the event dispatcher
		PluginHelper::importPlugin('sso');
		$dispatcher = JEventDispatcher::getInstance();

		// Load the profile name
		$authorizationSource = $this->getState('authorizationSource', 'default-sp');

		// Get the user fields
		$userFields = array();
		$dispatcher->trigger('onProcessUserGetFieldMap', array($authorizationSource, $this->instance->getAttributes(), &$userFields));

		// Get the credentials
		$credentials = $this->processUser($userFields);

		// Log the user in
		Factory::getApplication()->login($credentials, array('profile' => $this->getState('authorizationSource', 'default-sp')));
	}

	/**
	 * Process the user logout.
	 *
	 * @return  string  The SAML logout URL
	 *
	 * @throws  Exception
	 *
	 * @since   1.0.0
	 */
	public function processLogout()
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

	/**
	 * Process a logged in user. First verify if the user exists, otherwise create the user and finally login the user.
	 *
	 * @param   array  $userFields  The user fields
	 *
	 * @return  array  List of credentials
	 *
	 * @since   1.0.0
	 */
	public function processUser($userFields)
	{
		// Map the attributes from the first array returned
		$name     = $userFields['name'];
		$username = $userFields['username'];
		$email    = $userFields['email'];

		// We must have an email address
		if (trim($email) === '')
		{
			$email = $username;
		}

		// Check if user exists
		if (!$this->doesUserExist($email, $username))
		{
			// Load the profile name
			$authorizationSource = $this->getState('authorizationSource', 'default-sp');

			// Get the user group
			$helper     = new SsoHelper;
			$parameters = $helper->getParams($authorizationSource);
			$userGroups = $parameters->get('joomla.userGroup', array());

			if (empty($userGroups))
			{
				throw new InvalidArgumentException(Text::_('COM_SSO_NO_USERGROUP'));
			}

			// Create the user
			$this->createUser($name, $username, $email, $userGroups);
		}

		// Trigger any plugins that want to do something with the logged-in user
		PluginHelper::importPlugin('sso');
		$dispatcher = JEventDispatcher::getInstance();
		$dispatcher->trigger('onAfterProcessUser', array($userFields));

		// Gather the response details
		$options                 = array();
		$options['responseType'] = 'saml';

		return $options;
	}

	/**
	 * Get the user ID in the database.
	 *
	 * @param   string  $email     The email to verify
	 * @param   string  $username  The username to verify
	 *
	 * @return  bool True if exists | False otherwise.
	 *
	 * @since   1.0.0
	 */
	private function findUserId($email, $username)
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
	 * Find a user in the database.
	 *
	 * @param   string  $email     The email to verify
	 * @param   string  $username  The username to verify
	 *
	 * @return  bool True if exists | False otherwise.
	 *
	 * @since   1.0.0
	 */
	private function doesUserExist($email, $username)
	{
		if (!$this->findUserId($email, $username))
		{
			return false;
		}

		return true;
	}

	/**
	 * Create a user that doesn't exist.
	 *
	 * @param   string  $name       The full name
	 * @param   string  $username   The username
	 * @param   string  $email      The email address
	 * @param   array   $userGroup  The user group to assign the user to
	 *
	 * @return  bool  True on success | False on failure
	 *
	 * @throws  RuntimeException
	 *
	 * @since   1.0.0
	 */
	private function createUser($name, $username, $email, $userGroup = array(2))
	{
		$db   = $this->getDbo();
		$date = new Date;
		$user = new User($db);
		$user->set('name', $name);
		$user->set('username', $username);
		$user->set('email', $email ?: $username);
		$user->set('registerDate', $date->toSql());
		$user->set('groups', $userGroup);

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
}
