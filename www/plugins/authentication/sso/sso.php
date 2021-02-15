<?php
/**
 * @package     SSO.Plugin
 * @subpackage  Authentication.sso
 *
 * @copyright   Copyright (C) 2017 - 2021 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use SimpleSAML\Auth\Simple;

defined('_JEXEC') or die;

/**
 * SSO Authentication Plugin
 *
 * @since  1.0
 */
class PlgAuthenticationSso extends CMSPlugin
{
	/**
	 * A SimpleSAML instance.
	 *
	 * @var   Simple
	 *
	 * @since 1.0
	 */
	private $instance;

	/**
	 * Database driver
	 *
	 * @var    JDatabaseDriverMysqli
	 * @since  1.0
	 */
	protected $db;

	/**
	 * Get the application
	 *
	 * @var    CMSApplication
	 * @since  1.0.0
	 */
	protected $app;

	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  1.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Constructor
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An optional associative array of configuration settings.
	 *                             Recognized key values include 'name', 'group', 'params', 'language'
	 *                             (this list is not meant to be comprehensive).
	 *
	 * @since   1.0
	 */
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);

		$this->checkLibrary();
	}

	/**
	 * Load SimpleSAMLphp.
	 *
	 * @param   string  $profile  The authorization profile to use
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function loadSimpleSaml($profile = 'default-sp')
	{
		if (!$this->instance)
		{
			$this->instance = new Simple($profile);
		}
	}

	/**
	 * This method should handle any authentication and report back to the subject
	 *
	 * @param   array   $credentials  Array holding the user credentials
	 * @param   array   $options      Array of extra options
	 * @param   object  &$response    Authentication response object
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onUserAuthenticate($credentials, $options, &$response)
	{
		if ($this->app->input->getCmd('option') !== 'com_sso')
		{
			return;
		}

		if (!array_key_exists('profile', $options))
		{
			$options['profile'] = $this->params->get('profile', false);
		}

		if ($options['profile'] === false)
		{
			throw new InvalidArgumentException(Text::_('PLG_AUTHENTICATION_SSO_NO_PROFILE_SUPPLIED'));
		}

		$authorizationSource = $options['profile'] ?: 'default-sp';
		$this->loadSimpleSaml($authorizationSource);

		$this->instance->requireAuth();

		if (!$this->instance->isAuthenticated())
		{
			$response->status        = Authentication::STATUS_FAILURE;
			$response->error_message = Text::sprintf('JGLOBAL_AUTH_FAILED', Text::_('PLG_AUTHENTICATION_SSO_ERROR_USER_NOT_AUTHENTICATED'));

			return;
		}

		// Clean up the attributes
		JLoader::register('SsoHelper', JPATH_ADMINISTRATOR . '/components/com_sso/helpers/sso.php');
		$helper     = new SsoHelper;
		$userFields = $helper->processAttributes($authorizationSource, $this->instance->getAttributes());

		if (empty($userFields['name']))
		{
			throw new InvalidArgumentException(Text::_('PLG_AUTHENTICATION_SSO_NO_USER_FIELDMAP_NAME'));
		}

		if (empty($userFields['username']))
		{
			throw new InvalidArgumentException(Text::_('PLG_AUTHENTICATION_SSO_NO_USER_FIELDMAP_USERNAME'));
		}

		if (empty($userFields['email']))
		{
			throw new InvalidArgumentException(Text::_('PLG_AUTHENTICATION_SSO_NO_USER_FIELDMAP_EMAIL'));
		}

		$response->status         = Authentication::STATUS_SUCCESS;
		$response->error_message  = '';
		$response->password_clear = '';
		$response->email          = $userFields['email'];
		$response->username       = $userFields['username'];
		$response->fullname       = $userFields['name'];
		$response->type           = 'SAML';
	}

	/**
	 * Check if the SimpleSAMLphp library can be found.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function checkLibrary(): void
	{
		if (!class_exists('SimpleSAML_Configuration'))
		{
			include_once JPATH_LIBRARIES . '/simplesamlphp/lib/_autoload.php';
		}
	}
}
