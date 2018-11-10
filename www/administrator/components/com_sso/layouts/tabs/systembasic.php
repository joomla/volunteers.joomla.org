<?php
/**
 * @package     SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2018 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

defined('_JEXEC') or die;

?>
<ol>
	<li>Go to Certificate
		<ol>
			<li>Fill in all the details</li>
			<li>Keep your password safe as you need it later on</li>
			<li>Generate the certificate</li>
		</ol>
		<br />
		In case this does not work on your system, please follow the directions below under System Preparation
	</li>
    <li>Go to SSO Configuration
	    <ol>
		    <li>Set "Administrator Password"</li>
		    <li>Set "Secret Salt"</li>
		    <li>Set "Technical Contact Name"</li>
		    <li>Set "Technical Contact Email"</li>
	    </ol>
    </li>
</ol>

<h3>System Preparation</h3>
<p>To be able to use this authentication system securely, certificates needs to be created. <br />
	This is only possible when you have SSH access to your hosting account.</p>
<ul>
	<li>Open the SSH connection.</li>
	<li>Browse to <strong><?php echo JPATH_SITE . '/libraries/simplesamlphp/cert'; ?></strong> folder</li>
	<li>Create certificate files<br />
		The following command can be used to generate the certificate files, change sso.crt and sso.pem to whichever name you like.
		<pre>openssl req -newkey rsa:4096 -new -x509 -days 3652 -nodes -out sso.crt -keyout sso.pem</pre><br />
		<strong>Tip:</strong> When using the system as "Identity Provider" and "Service Provider" generate two different certificate sets.</li>
</ul>