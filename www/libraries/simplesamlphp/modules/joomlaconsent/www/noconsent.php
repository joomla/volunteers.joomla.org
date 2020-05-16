<?php
/**
 * This is the page the user lands on when choosing "no" in the consent form.
 *
 * @package SimpleSAMLphp
 */
if (!array_key_exists('StateId', $_REQUEST))
{
	throw new SimpleSAML_Error_BadRequest(
		'Missing required StateId query parameter.'
	);
}

$id    = $_REQUEST['StateId'];
$state = SimpleSAML_Auth_State::loadState($id, 'joomlaconsent:request');

$resumeFrom = SimpleSAML\Module::getModuleURL(
	'joomlaconsent/getconsent.php',
	array('StateId' => $id)
);

$logoutLink = SimpleSAML\Module::getModuleURL(
	'joomlaconsent/logout.php',
	array('StateId' => $id)
);

$aboutService = null;

if (!isset($state['joomlaconsent:showNoConsentAboutService']) || $state['joomlaconsent:showNoConsentAboutService'])
{
	if (isset($state['Destination']['url.about']))
	{
		$aboutService = $state['Destination']['url.about'];
	}
}

$statsInfo = array();

if (isset($state['Destination']['entityid']))
{
	$statsInfo['spEntityID'] = $state['Destination']['entityid'];
}

SimpleSAML_Stats::log('joomlaconsent:reject', $statsInfo);

$globalConfig = SimpleSAML_Configuration::getInstance();

$t                       = new SimpleSAML_XHTML_Template($globalConfig, 'joomlaconsent:noconsent.php');
$t->data['dstMetadata']  = $state['Destination'];
$t->data['resumeFrom']   = $resumeFrom;
$t->data['aboutService'] = $aboutService;
$t->data['logoutLink']   = $logoutLink;
$t->show();
