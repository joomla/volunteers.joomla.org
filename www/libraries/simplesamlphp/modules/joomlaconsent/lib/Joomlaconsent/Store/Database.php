<?php

use Joomla\CMS\Date\Date;
use JoomlaCore\JoomlaCore;

/**
 * Store consent in database.
 *
 * This class implements a consent store which stores the consent information in a database. It is tested, and should
 * work against MySQL, PostgreSQL and SQLite.
 *
 * It has the following options:
 * - dsn: The DSN which should be used to connect to the database server. See the PHP Manual for supported drivers and
 *   DSN formats.
 * - username: The username used for database connection.
 * - password: The password used for database connection.
 * - table: The name of the table used. Optional, defaults to 'consent'.
 *
 * @author Olav Morken <olav.morken@uninett.no>
 * @package SimpleSAMLphp
 */
class sspmod_joomlaconsent_Joomlaconsent_Store_Database extends sspmod_joomlaconsent_Store
{
	/**
	 * Joomla core instance
	 *
	 * @var    JoomlaCore
	 * @since  1.0.0
	 */
	private $joomla;

	/**
	 * Parse configuration.
	 *
	 * This constructor parses the configuration.
	 *
	 * @param   array $config Configuration for database consent store.
	 *
	 * @throws  Exception in case of a configuration error.
	 *
	 * @since   1.0.0
	 */
	public function __construct($config)
	{
		parent::__construct($config);
	}

	/**
	 * Load Joomla.
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 *
	 * @since   1.0.0
	 */
	private function loadJoomla()
	{
		// Load Joomla
		require_once __DIR__ . '/../../../../joomla/core/joomla.php';

		// Load the core
		$this->joomla = new JoomlaCore;
	}

	/**
	 * Check for consent.
	 *
	 * This function checks whether a given user has authorized the release of
	 * the attributes identified by $attributeSet from $source to $destination.
	 *
	 * @param   string  $userGuid    The hash identifying the user at an IdP.
	 * @param   string  $spEntityId  A string which identifies the destination.
	 *
	 * @return boolean True if the user has given consent earlier, false if not
	 *                 (or on error).
	 *
	 * @throws Exception
	 *
	 * @since  1.0.0
	 */
	public function hasConsent($userGuid, $spEntityId)
	{
		$this->loadJoomla();
		$this->joomla->loadDomain($spEntityId);

		$userConsents = $this->joomla->getUserConsents($userGuid);

		// Check if we found a consent
		if (!$userConsents)
		{
			SimpleSAML\Logger::debug('Joomla Consent:Database - No consent found.');

			return false;
		}
		else
		{
			SimpleSAML\Logger::debug('Joomla Consent:Database - Consent found.');
		}

		// Get the site consents that must be given
		$siteConsents = $this->joomla->loadSiteConsents();

		// Check if there are any consents not given
		if (array_diff_key($siteConsents, $userConsents))
		{
			return false;
		}

		// Consents are given, now check if any are expired
		foreach ($userConsents as $consent)
		{
			// Check if the consent is still valid
			$agreedData = new Date($consent->agreed);
			$agreedData->add(new DateInterval('P1Y'));
			$today = new Date;

			// Check if the consent is still valid
			if ($agreedData < $today)
			{
				return false;
			}
		}

		// All good
		return true;
	}

	/**
	 * Save consent.
	 *
	 * Called when the user asks for the consent to be saved. If consent information
	 * for the given user and destination already exists, it should be overwritten.
	 *
	 * @param   string  $userGuid    The hash identifying the user at an IdP.
	 * @param   string  $spEntityId  A string which identifies the destination.
	 * @param   array   $consents    A list of consents to store
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 *
	 * @since   1.0.0
	 */
	public function saveConsent($userGuid, $spEntityId, $consents)
	{
		\JLoader::register('IdentityHelper', JPATH_ADMINISTRATOR . '/components/com_identity/helpers/identity.php');
		$this->loadJoomla();
		$this->joomla->loadDomain($spEntityId);
		$helper = new \IdentityHelper;

		foreach ($consents as $dataId => $consent)
		{
			if ((int) $consent !== 1)
			{
				throw new InvalidArgumentException('Consent is required');
			}

			if (!$helper->isConsentStored($dataId, $userGuid))
			{
				$helper->storeConsent($dataId, $userGuid);
			}
		}

		SimpleSAML\Logger::debug('consent:Database - Saved new consent.');
	}
}
