<?php

namespace JoomlaCore;

use IdentityHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use SimpleSAML_Auth_State;

/**
 * The Joomla Theme helper.
 *
 * @package     JoomlaCore
 * @since       1.0.0
 */
class JoomlaCore
{
	/**
	 * Database driver
	 *
	 * @var    object
	 * @since  1.0.0
	 */
	private $db;

	/**
	 * The hostname the user is logging into
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	private $hostname;

	/**
	 * Construct the class.
	 *
	 * @throws  \Exception
	 *
	 * @since     1.0.0
	 */
	public function __construct()
	{
		$this->loadJoomla();

		$this->db = Factory::getDbo();
	}

	/**
	 * Minimal configuration to load Joomla.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function loadJoomla()
	{
		// Tell Joomla we can be trusted
		if (!defined('_JEXEC'))
		{
			define('_JEXEC', 1);
		}

		// Set the base folder
		$base = dirname(dirname(dirname(dirname(dirname(__FILE__)))));

		// Load the Joomla Framework
		require_once $base . '/import.php';

		// Require the classmap file so Joomla can match the old names
		require $base . '/classmap.php';

		// Load system defines
		if (!defined('JPATH_BASE'))
		{
			define('JPATH_BASE', dirname($base));
		}

		require_once dirname($base) . '/includes/defines.php';

		// Register the library base path for CMS libraries.
		\JLoader::registerPrefix('J', JPATH_PLATFORM . '/cms', false, true);

		\JLoader::registerNamespace('Joomla\\CMS', JPATH_PLATFORM . '/src', false, false, 'psr4');
		$loader = require $base . '/vendor/autoload.php';
		$loader->unregister();

		// Decorate Composer autoloader
		spl_autoload_register(array(new \JClassLoader($loader), 'loadClass'), true, true);

		// Load the language file
		$language = Factory::getLanguage();
		$language->load('com_identity', JPATH_ADMINISTRATOR . '/components/com_identity');

		// Load the identity helper
		require_once JPATH_ADMINISTRATOR . '/components/com_identity/helpers/identity.php';
	}

	/**
	 * Load the domain the user is logging into.
	 *
	 * @param   string  $url  The URL to use for the domain name
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 *
	 * @since   1.0.0
	 */
	public function loadDomain($url = '')
	{
		if (!$url)
		{
			$state = SimpleSAML_Auth_State::loadState($_REQUEST['AuthState'],
				'sspmod_core_Auth_UserPassBase.state'
			);
			$url = $state['saml:RelayState'];
		}

		$uriParts       = parse_url($url);
		$this->hostname = $uriParts['scheme'] . '://' . $uriParts['host'];
	}

	/**
	 * Return the hostname
	 *
	 * @return string
	 *
	 * @since  1.0.0
	 */
	public function getHostname()
	{
		$hostname = $this->hostname;

		// Check if hostname ends with a slash
		if (substr($hostname, -1) !== '/')
		{
			$hostname .= '/';
		}

		return $hostname;
	}

	/**
	 * Get the site ID.
	 *
	 * @return  integer  The site ID.
	 *
	 * @since   1.0.0
	 */
	public function getSiteId()
	{
		$db = $this->db;

		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__identity_sites'))
			->where($db->quoteName('hostname') . ' = ' . $db->quote($this->getHostname()));

		$db->setQuery($query);

		return $db->loadResult();
	}

	/**
	 * Get the user details.
	 *
	 * @param   string  $username  The username to get the details for
	 *
	 * @return  object  The user details.
	 *
	 * @since   1.0.0
	 */
	public function getUserDetails($username)
	{
		$db = $this->db;
		$query = $db->getQuery(true)
			->select(
				$db->quoteName(
					array(
						'users.id',
						'users.name',
						'users.email',
						'users.username',
						'users.password',
						'profiles.guid'
					)
				)
			)
			->from($db->quoteName('#__users', 'users'))
			->leftJoin(
				$db->quoteName('#__identity_profiles', 'profiles')
				. ' ON ' . $db->quoteName('profiles.user_id') . ' = ' . $db->quoteName('users.id')
			)
			->where($db->quoteName('users.username') . ' = ' . $db->quote($username));

		$db->setQuery($query);

		return $db->loadObject();
	}

	/**
	 * Load the consents for a given site.
	 *
	 * @return  array  List of consents to validate.
	 *
	 * @since   1.0.0
	 */
	public function loadSiteConsents()
	{
		$db = $this->db;

		$query = $db->getQuery(true)
			->select(
				$db->quoteName(
					array(
						'datas.id',
						'datas.title',
						'datas.description',
						'datas.allowedfields'
					)
				)
			)
			->from($db->quoteName('#__identity_datas', 'datas'))
			->leftJoin(
				$db->quoteName('#__identity_sites', 'sites')
				. ' ON ' . $db->quoteName('sites.id') . ' = ' . $db->quoteName('datas.site_id')
			)
			->where($db->quoteName('sites.hostname') . ' = ' . $db->quote($this->hostname))
			->andWhere($db->quoteName('datas.locked') . ' = 0')
			->orWhere($db->quoteName('datas.general') . ' = 1')
			->andWhere($db->quoteName('datas.published') . ' = 1');
		$db->setQuery($query);

		return $db->loadObjectList('id');
	}

	/**
	 * Load the consents that needs to be met for the given site.
	 *
	 * @param   string  $userGuid  The user GUID to get the given consents for
	 *
	 * @return  array  List of consents.
	 *
	 * @since   1.0.0
	 */
	public function getUserConsents($userGuid)
	{
		$db = $this->db;

		$query = $db->getQuery(true)
			->select(
				$db->quoteName(
					array(
						'consents.data_id',
						'consents.agreed',
						'consents.expiration',
						'datas.title',
						'datas.allowedfields'
					)
				)
			)
			->from($db->quoteName('#__identity_consents', 'consents'))
			->leftJoin(
				$db->quoteName('#__identity_datas', 'datas')
				. ' ON ' . $db->quoteName('datas.id') . ' = ' . $db->quoteName('consents.data_id')
			)
			->leftJoin(
				$db->quoteName('#__identity_sites', 'sites')
				. ' ON ' . $db->quoteName('sites.id') . ' = ' . $db->quoteName('datas.site_id')
			)
			->where($db->quoteName('consents.guid') . ' = ' . $db->quote($userGuid))
			->andWhere($db->quoteName('consents.expiration') . ' > ' . $db->quote((new Date)->toSql()))
			->andWhere($db->quoteName('datas.published') . ' = 1')
			->andWhere(
				'(' . $db->quoteName('sites.hostname') . ' = ' . $db->quote($this->getHostname()) . ' OR ' . $db->quoteName('datas.general') . ' = 1)'
			);
		$db->setQuery($query);

		return $db->loadObjectList('data_id');
	}

	/**
	 * Handle the user during login.
	 *
	 * @param   string  $guid  The user GUID
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function onAfterLogin(string $guid): void
	{
		// Ping the remote sites
		$helper = new IdentityHelper;
		$helper->pingSites($guid, 'user.login', 1);
	}
}
