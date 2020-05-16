<?php
/**
 * @package     SSO.Component
 *
 * @author      RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright   Copyright (C) 2017 - 2020 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://rolandd.com
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;

if ($this->config->get('enable.saml20-idp', false)) : ?>
	<h3>The Identity Provider metadata URL is:</h3>
<h4>View</h4>
<?php
echo HTMLHelper::_(
	'link',
	Uri::root() . $this->config->get('baseurlpath') . 'saml2/idp/metadata.php?output=xhtml',
	Uri::root() . $this->config->get('baseurlpath') . 'saml2/idp/metadata.php?output=xhtml',
	Text::_('COM_SSO_IDP_METADATA_LINK'),
	'target="_blank"'
);
?>
<h4>Import</h4>
<?php
echo HTMLHelper::_(
	'link',
	Uri::root() . $this->config->get('baseurlpath') . 'saml2/idp/metadata.php',
	Uri::root() . $this->config->get('baseurlpath') . 'saml2/idp/metadata.php',
	Text::_('COM_SSO_IDP_METADATA_LINK')
);
?>
<?php endif; ?>
<?php if (count($this->identityProviders) > 0) : ?>
	<h3>The Service Provider metadata URL is:</h3>
	<?php
	foreach ($this->identityProviders as $identityProvider)
	{
		$url = Uri::root() . $this->config->get('baseurlpath') . 'module.php/saml/sp/metadata.php/' . $identityProvider;

		?><h4>View</h4><?php
		echo HTMLHelper::_(
			'link',
			$url . '?output=xhtml',
			$url . '?output=xhtml',
			Text::_('COM_SSO_SP_METADATA_LINK'),
			'target="_blank"'
		);

		?><h4>Import</h4><?php

		echo HTMLHelper::_(
			'link',
			$url,
			$url,
			Text::_('COM_SSO_SP_METADATA_LINK')
		);
	}
	?>
<?php endif; ?>
<h3>SimpleSAMLphp Cron</h3>
<?php
echo HTMLHelper::_(
	'link',
	Uri::root() . $this->config->get('baseurlpath') . 'module.php/cron/croninfo.php',
	Text::_('COM_SSO_SIMPLESAMLPHP_CRON'),
	'target="_blank"'
);
?><div><?php
	echo HTMLHelper::_(
		'link',
		'https://simplesamlphp.org/docs/development/cron:cron',
		Text::_('COM_SSO_SIMPLESAMLPHP_CRON_DOCUMENTATION'),
		'target="_blank"'
	);
	?></div>
<br />
<div>
	The following command can be used to setup the cronjob for refreshing the metadata files:<br />
	<pre>&lt;path to PHP executable&gt;/php &lt;path to webroot&gt;/libraries/simplesamlphp/modules/cron/bin/cron.php -t hourly > /dev/null 2>&1</pre>
</div>

	<h3>SimpleSAMLphp Dashboard</h3>
<?php
echo HTMLHelper::_(
	'link',
	Uri::root() . $this->config->get('baseurlpath') . 'module.php/core/frontpage_welcome.php',
	Text::_('COM_SSO_SIMPLESAMLPHP'),
	'target="_blank"'
);
?>

<h3>SimpleSAMLphp Version</h3>
<div class="badge"><?php echo $this->samlVersion; ?></div>

<h3>RO Single Sign On Version</h3>
<div class="badge">1.2.1</div>
