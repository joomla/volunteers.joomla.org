<?php
/**
 * @package     SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2018 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;
?>
    <h3>The Identity Provider metadata URL is:</h3>
<?php
	echo HTMLHelper::_(
			'link',
			Uri::root() . 'libraries/simplesamlphp/www/saml2/idp/metadata.php?output=xhtml',
			Uri::root() . 'libraries/simplesamlphp/www/saml2/idp/metadata.php?output=xhtml',
			Text::_('COM_SSO_IDP_METADATA_LINK')
	);
?>
<?php if (count($displayData['identityProviders']) > 0) : ?>
	<h3>The Service Provider metadata URL is:</h3>
	<?php
	foreach ($displayData['identityProviders'] as $identityProvider)
	{
		echo HTMLHelper::_(
				'link',
				Uri::root() . 'libraries/simplesamlphp/www/module.php/saml/sp/metadata.php/' . $identityProvider . '?output=xhtml',
				Uri::root() . 'libraries/simplesamlphp/www/module.php/saml/sp/metadata.php/' . $identityProvider . '?output=xhtml',
				Text::_('COM_SSO_SP_METADATA_LINK')
		);
	}
	?>
<?php endif; ?>
    <h3>SimpleSAMLphp Dashboard</h3>
<?php
echo HTMLHelper::_(
		'link',
		Uri::root() . 'libraries/simplesamlphp/www/module.php/core/frontpage_welcome.php',
		Text::_('COM_SSO_SIMPLESAMLPHP'),
		'target="_blank"'
);
