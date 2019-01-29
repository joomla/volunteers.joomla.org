<?php
/**
 * Consent script
 *
 * This script displays a page to the user, which requests that the user
 * authorizes the release of attributes.
 *
 * @package SimpleSAMLphp
 */

use JoomlaCore\JoomlaCore;

/**
 * Explicit instruct consent page to send no-cache header to browsers to make
 * sure the users attribute information are not store on client disk.
 *
 * In an vanilla apache-php installation is the php variables set to:
 *
 * session.cache_limiter = nocache
 *
 * so this is just to make sure.
 */
session_cache_limiter('nocache');

$globalConfig = SimpleSAML_Configuration::getInstance();

SimpleSAML\Logger::info('Joomla Consent - getconsent: Accessing Joomla consent interface');

if (!array_key_exists('StateId', $_REQUEST)) {
	throw new SimpleSAML_Error_BadRequest(
		'Missing required StateId query parameter.'
	);
}

$id = $_REQUEST['StateId'];
$state = SimpleSAML_Auth_State::loadState($id, 'joomlaconsent:request');
$message = '';

if (array_key_exists('core:SP', $state)) {
	$spentityid = $state['core:SP'];
} else if (array_key_exists('saml:sp:State', $state)) {
	$spentityid = $state['saml:sp:State']['core:SP'];
} else {
	$spentityid = 'UNKNOWN';
}

// Load Joomla
require_once __DIR__ . '/../../joomla/core/joomla.php';
$joomla = new JoomlaCore;

// The user has pressed the yes-button
if (array_key_exists('yes', $_REQUEST))
{
	// Save consent
	/** @var sspmod_joomlaconsent_Joomlaconsent_Store_Database $store */
	$store        = $state['joomlaconsent:store'];
	$userGuid     = $state['joomlaconsent:store.userGuid'];
	$targetedId   = $state['joomlaconsent:store.destination'];
	$attributeSet = $state['joomlaconsent:store.attributeSet'];
	$continue     = true;
	SimpleSAML\Logger::debug('Consent - saveConsent() : [' . $userGuid . ']');

	try
	{
		$store->saveConsent($userGuid, $spentityid, $_REQUEST['consent']);
	}
	catch (Exception $e)
	{
		echo $e->getMessage();
		SimpleSAML\Logger::error('Consent: Error writing to storage: ' . $e->getMessage());
		$message = $e->getMessage();
		$continue = false;
	}

	if ($continue)
	{
		SimpleSAML_Auth_ProcessingChain::resumeProcessing($state);
	}
}

// Load the domain
$joomla->loadDomain($state['saml:RelayState']);

// Prepare attributes for presentation
$attributes = $state['Attributes'];

// Make, populate and layout consent form
$t = new SimpleSAML_XHTML_Template($globalConfig, 'joomlaconsent:consentform.php');
$t->data['yesTarget'] = SimpleSAML\Module::getModuleURL('joomlaconsent/getconsent.php');
$t->data['yesData'] = array('StateId' => $id);
$t->data['noTarget'] = SimpleSAML\Module::getModuleURL('joomlaconsent/noconsent.php');
$t->data['noData'] = array('StateId' => $id);
$t->data['attributes'] = $attributes;

// Set focus on No button
$t->data['autofocus'] = 'nobutton';

// Find the site the user is logging in for
$t->data['message'] = $message;
$userConsents = $joomla->getUserConsents($attributes['guid'][0]);
$siteConsents = $joomla->loadSiteConsents();

$t->data['consents'] = array_diff_key($siteConsents, $userConsents);

// If there are no consents, just continue
if (empty($t->data['consents']))
{
	SimpleSAML_Auth_ProcessingChain::resumeProcessing($state);
}

$t->data['site'] = $joomla->getHostname();

// Show the template
$t->show();
