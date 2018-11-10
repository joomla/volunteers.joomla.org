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
<ul>
    <li>Go to SSO Configuration</li>
    <li>Set "Enable Identity Provider" to Yes</li>
    <li>Get the metadata configuration from the Service Provider<br />
	    Check with the Service Provider what the URL for their metadata is. In case the Service Provider is a Joomla site using RO SSO
	    follow these steps:
	    <ol>
		    <li>Login on the Service Provider site</li>
		    <li>Go to Components -> RO SSO</li>
		    <li>Click on Profiles</li>
		    <li>Check the name of the profile you want to use</li>
		    <li>The URL of the metadata configuration is:
			    <strong>[domain]/libraries/simplesamlphp/www/module.php/saml/sp/metadata.php/&lt;PROFILE NAME&gt;?output=xhtml</strong>
		    </li>
		    <li>Open the URL in the browser</li>
		    <li>Copy the bottom section to the clipboard as this will be used in the next step</li>
	    </ol>

    </li>
    <li>Add the metadata from the Service provider to the file <strong><?php echo JPATH_SITE . '/libraries/simplesamlphp/metadata-generated/saml20-sp-remote.php'; ?></strong> Do not remove any existing entries unless you are sure.</li>
</ul>
