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

?>
<h2>Adding an Identity Provider to this Service Provider</h2>
<p> Go to Identity Provider Profiles - Create new profile</p>
<p><strong>Profile - Joomla Tab</strong>
<ul>
	<li>Select the usergroup to assign new users to</li>
	<li>Select Redirect after login</li>
</ul>
</p>
<p><strong>Profile - Authorization Tab</strong>
<ul>
	<li>Enter the "Identity Provider Metadata URL"</li>
	<li>Enter the "Private Key" filename</li>
	<li>Enter the "Certificate" filename</li>
</ul>
</p>
<p><strong>Profile - Fields Tab</strong>
<ul>
	<li>Enter the "IDP field name" in Name</li>
	<li>Enter the "IDP field username" in Username</li>
	<li>Enter the "IDP field email address" in Email address</li>
</ul>
</p>
<p><strong>Login Module</strong>
	<ul>
		<li>Go to Modules</li>
		<li>Filter on SSO</li>
		<li>Open the RO SSO Module</li>
		<li>Choose the profile to use</li>
		<li>Enable the module</li>
		<li>Setup the Menu Assignment</li>
		<li>Save & Close the module</li>
	</ul>
</p>
<p><strong>SSO Authentication Plugin</strong>
<ul>
	<li>Go to Plugins</li>
	<li>Filter on SSO</li>
	<li>Enable the Authentication - RO SSO plugin</li>
</ul>
</p>
<p><strong>Read the metadata from the Identity Provider</strong></p>

<ol>
    <li>Go to folder libraries\simplesamlphp\modules\metarefresh\bin'</li>
    <li>Import the metadata using the command 'php metarefresh.php [URL to metadata file]'</li>
</ol>
<p><strong>SSO System Plugin</strong><br />
To automatically redirect users to the Identity Provider, you can enable the SSO System Plugin
<ul>
	<li>Go to Plugins</li>
	<li>Filter on SSO</li>
	<li>Enable the System - RO SSO plugin</li>
</ul>
</p>
