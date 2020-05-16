<?php

use SimpleSAML\Module\saml\Error\NoPassive;


/**
 * Consent Authentication Processing filter
 *
 * Filter for requesting the user to give consent before attributes are
 * released to the SP.
 *
 * @package SimpleSAMLphp
 *
 * @since  1.0.0
 */
class sspmod_joomlaconsent_Auth_Process_Joomlaconsent extends SimpleSAML_Auth_ProcessingFilter
{

	/**
	 * Button to receive focus
	 *
	 * @var string|null
	 *
	 * @since  1.0.0
	 */
	private $_focus = null;

	/**
	 * Consent backend storage configuration
	 *
	 * @var sspmod_joomlaconsent_Joomlaconsent_Store_Database
	 *
	 * @since  1.0.0
	 */
	private $_store = null;

	/**
	 * Attributes where the value should be hidden
	 *
	 * @var array
	 *
	 * @since  1.0.0
	 */
	private $_hiddenAttributes = array();

	/**
	 * Constructor for a processing filter.
	 *
	 * Any processing filter which implements its own constructor must call this
	 * constructor first.
	 *
	 * @param   array  $config    Configuration for this filter.
	 * @param   mixed  $reserved  For future use.
	 *
	 * @throws  Exception
	 *
	 * @since   1.0.0
	 */
	public function __construct(array $config, $reserved)
	{

		parent::__construct($config, $reserved);

		try
		{
			$this->_store = sspmod_joomlaconsent_Store::parseStoreConfig('joomlaconsent:Database');
		}
		catch (Exception $e)
		{
			SimpleSAML\Logger::error(
				'Consent: Could not create consent storage: ' .
				$e->getMessage()
			);
		}
	}


	/**
	 * Process a authentication response
	 *
	 * This function saves the state, and redirects the user to the page where the user can authorize the release of
	 * the attributes. If storage is used and the consent has already been given the user is passed on.
	 *
	 * @param   array  $state  The state of the response.
	 *
	 * @return void
	 *
	 * @throws Exception
	 * @throws NoPassive if the request was passive and consent is needed.
	 *
	 * @since  1.0.0
	 */
	public function process(&$state)
	{
		$spEntityId  = $state['Destination']['entityid'];
		$idpEntityId = $state['Source']['entityid'];

		if ($this->_store !== null)
		{
			// Check if the data has already been stored
			$source      = $state['Source']['metadata-set'] . '|' . $idpEntityId;
			$destination = $state['Destination']['metadata-set'] . '|' . $spEntityId;
			$attributes  = $state['Attributes'];
			$userGuid    = isset($attributes['guid']) ? $attributes['guid'][0] : '';

			SimpleSAML\Logger::debug('Joomla Consent: user guid: ' . $userGuid);
			SimpleSAML\Logger::debug('Joomla Consent: source: ' . $source);
			SimpleSAML\Logger::debug('Joomla Consent: destination: ' . $destination);

			try
			{
				if ($this->_store->hasConsent($userGuid, $spEntityId))
				{
					// Consent already given
					return;
				}

				$state['joomlaconsent:store']              = $this->_store;
				$state['joomlaconsent:store.userGuid']     = $userGuid;
				$state['joomlaconsent:store.destination']  = $destination;
				$state['joomlaconsent:store.attributeSet'] = $attributes;
			}
			catch (Exception $e)
			{
				SimpleSAML\Logger::error('Joomla Consent: Error reading from storage: ' . $e->getMessage());
			}
		}

		// Set state
		$state['joomlaconsent:focus'] = $this->_focus;
		$state['joomlaconsent:hiddenAttributes'] = $this->_hiddenAttributes;

		// User interaction necessary. Throw exception on isPassive request
		if (isset($state['isPassive']) && $state['isPassive'] === true)
		{
			throw new NoPassive('Unable to give consent on passive request.');
		}

		// Save state and redirect
		$id  = SimpleSAML_Auth_State::saveState($state, 'joomlaconsent:request');
		$url = SimpleSAML\Module::getModuleURL('joomlaconsent/getconsent.php');
		\SimpleSAML\Utils\HTTP::redirectTrustedURL($url, array('StateId' => $id));
	}
}
