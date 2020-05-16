<?php
/**
 * @package     SAML.Plugin
 * @subpackage  Authentication.Joomla
 *
 * @copyright   Copyright (C) 2017 - 2018 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;

defined('_JEXEC') or die;

/**
 * Joomla Plugin for RO SSO
 *
 * @since  1.0.0
 */
class PlgSsoJoomla extends CMSPlugin
{
	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  1.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Creates a mapping for the fields used to create a user
	 *
	 * @param   string  $authorizationSource  The alias of the IDP profile configured in RO SSO
	 * @param   array   $attributes           The attributes received from the IDP
	 * @param   array   &$userFields          The list of user fields to fill
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function onProcessUserGetFieldMap($authorizationSource, $attributes, &$userFields = array())
	{
		// Get the field maps
		$userFields = array(
			'name'     => '',
			'username' => '',
			'email'    => ''
		);

		// Clean up the attributes
		$helper     = new SsoHelper;
		$userFields = $helper->processAttributes($authorizationSource, $attributes);

		if (empty($userFields['name']))
		{
			throw new InvalidArgumentException(Text::_('PLG_SSO_JOOMLA_NO_USER_FIELDMAP_NAME'));
		}

		if (empty($userFields['username']))
		{
			throw new InvalidArgumentException(Text::_('PLG_SSO_JOOMLA_NO_USER_FIELDMAP_USERNAME'));
		}

		if (empty($userFields['email']))
		{
			throw new InvalidArgumentException(Text::_('PLG_SSO_JOOMLA_NO_USER_FIELDMAP_EMAIL'));
		}
	}
}
