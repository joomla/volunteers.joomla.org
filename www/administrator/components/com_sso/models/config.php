<?php
/**
 * @package     SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2018 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;

/**
 * SSO
 *
 * @since  1.0.0
 */
class SsoModelConfig extends AdminModel
{
	/**
	 * Get the form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A JForm object on success | False on failure.
	 *
	 * @since   1.0.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_sso.config', 'config', array('control' => 'jform', 'load_data' => $loadData));

		if (!$form)
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  array  The data for the form..
	 *
	 * @since   1.0.0
	 *
	 * @throws  Exception
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_sso.edit.config.data', array());

		if (0 === count($data))
		{
			$data                = new stdClass;
			$data->sso          = new stdClass;

			// Require the SimpleSAMLphp configuration
			require JPATH_LIBRARIES . '/simplesamlphp/config/config.php';

			/** @var array $config */
			$data->sso->adminpassword          = $config['auth.adminpassword'];
			$data->sso->secretsalt             = $config['secretsalt'];
			$data->sso->technicalcontact_name  = $config['technicalcontact_name'];
			$data->sso->technicalcontact_email = $config['technicalcontact_email'];
			$data->sso->idp                    = (int) $config['enable.saml20-idp'];
			$data->sso->theme                  = $config['theme.use'];
			$data->sso->consent                = isset($config['authproc.idp'][90]) ? $config['authproc.idp'][90]['class'] : '';
			$data->sso->debug                  = (int) $config['showerrors'];

			// Get the certificate information
			$metadata = array();
			$metadataFile = JPATH_LIBRARIES . '/simplesamlphp/metadata-generated/saml20-idp-hosted.php';

			if (!file_exists($metadataFile))
			{
				$metadataFile = JPATH_LIBRARIES . '/simplesamlphp/metadata-generated/saml20-idp-hosted.dist';
			}

			require $metadataFile;
			$data->sso->privatekey  = $metadata['__DYNAMIC:1__']['privatekey'];
			$data->sso->certificate = $metadata['__DYNAMIC:1__']['certificate'];
		}

		return $data;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success, False on error.
	 *
	 * @since   1.0.0
	 */
	public function save($data)
	{
		// Create the metadata-generated folder
		jimport('filesystem.folder');
		JFolder::create(JPATH_LIBRARIES . '/simplesamlphp/metadata-generated');

		// Save the config.php
		$this->saveSamlConfig($data['sso']);

		// Check if we need to setup the Identity Provider settings
		if ($data['sso']['idp'])
		{
			if (empty($data['sso']['privatekey']))
			{
				$this->setError(Text::_('COM_SSO_EMPTY_PRIVATEKEY'));

				return false;
			}

			if (empty($data['sso']['certificate']))
			{
				$this->setError(Text::_('COM_SSO_EMPTY_CERTIFICATE'));

				return false;
			}

			try
			{
				$this->setupIdentityProvider($data['sso']['privatekey'], $data['sso']['certificate']);
			}
			catch (Exception $exception)
			{
				$this->setError($exception->getMessage(), 'error');

				return false;
			}
		}
		else
		{
			$this->removeIdentityProvider();
		}

		return true;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function saveSamlConfig($data)
	{
		$config = Factory::getConfig();

		// Get the subfolder where the site lives
		$uri       = Uri::getInstance();
		$path      = $uri->getScheme() . '://' . $uri->getHost() . '/';
		$subFolder = str_replace($path, '', Uri::root());

		// Default values
		$debug = (bool) $data['debug'];

		// Check if the theme exists
		$folder = '';

		if (strpos($data['theme'], ':'))
		{
			list($folder, $theme) = explode(':', $data['theme']);
		}

		if (!$folder || !file_exists(JPATH_LIBRARIES . '/simplesamlphp/modules/' . $folder . '/themes/' . $theme))
		{
			$data['theme'] = '';
		}

		$default = array(
			'baseurlpath'                   => $subFolder . 'libraries/simplesamlphp/www/',
			'certdir'                       => 'cert/',
			'loggingdir'                    => $config->get('log_path') . '/',
			'datadir'                       => 'data/',
			'tempdir'                       => $config->get('tmp_path') . '/',
			'technicalcontact_name'         => 'Administrator',
			'technicalcontact_email'        => 'na@example.org',
			'timezone'                      => $config->get('offset'),
			'secretsalt'                    => uniqid(),
			'auth.adminpassword'            => uniqid(),
			'admin.protectindexpage'        => true,
			'admin.protectmetadata'         => false,
			'admin.checkforupdates'         => false,
			'trusted.url.domains'           => array(),
			'trusted.url.regex'             => false,
			'enable.http_post'              => false,
			'debug'                         => array('saml' => $debug, 'backtraces' => $debug, 'validatexml' => $debug),
			'showerrors'                    => $debug,
			'errorreporting'                => $debug,
			'logging.level'                 => SimpleSAML\Logger::DEBUG,
			'logging.handler'               => 'file',
			'logging.format'                => '%date{%b %d %H:%M:%S} %process %level %stat[%trackid] %msg',
			'logging.facility'              => defined('LOG_LOCAL5') ? constant('LOG_LOCAL5') : LOG_USER,
			'logging.processname'           => 'simplesamlphp',
			'logging.logfile'               => 'simplesamlphp.log',
			'statistics.out'                => array(),
			'proxy'                         => null,
			'proxy.auth'                    => false,
			'enable.saml20-idp'             => (bool) $data['idp'],
			'enable.shib13-idp'             => false,
			'enable.adfs-idp'               => false,
			'enable.wsfed-sp'               => false,
			'enable.authmemcookie'          => false,
			'default-wsfed-idp'             => 'urn:federation:pingfederate:localhost',
			'shib13.signresponse'           => true,
			'session.duration'              => 8 * (60 * 60),
			'session.datastore.timeout'     => (4 * 60 * 60),
			'session.state.timeout'         => (60 * 60),
			'session.cookie.name'           => 'SimpleSAMLSessionID',
			'session.cookie.lifetime'       => 0,
			'session.cookie.path'           => '/',
			'session.cookie.domain'         => null,
			'session.cookie.secure'         => false,
			'session.phpsession.cookiename' => 'SimpleSAML',
			'session.phpsession.savepath'   => null,
			'session.phpsession.httponly'   => true,
			'session.authtoken.cookiename'  => 'SimpleSAMLAuthToken',
			'session.rememberme.enable'     => false,
			'session.rememberme.checked'    => false,
			'session.rememberme.lifetime'   => (14 * 86400),
			'memcache_store.servers'        => array(
				array(
					array('hostname' => 'localhost'),
				),
			),
			'memcache_store.prefix'         => '',
			'memcache_store.expires'        => 36 * (60 * 60),
			'language.available'            => array(
				'en', 'no', 'nn', 'se', 'da', 'de', 'sv', 'fi', 'es', 'fr', 'it', 'nl', 'lb', 'cs',
				'sl', 'lt', 'hr', 'hu', 'pl', 'pt', 'pt-br', 'tr', 'ja', 'zh', 'zh-tw', 'ru', 'et',
				'he', 'id', 'sr', 'lv', 'ro', 'eu', 'el', 'af'
			),
			'language.rtl'                  => array('ar', 'dv', 'fa', 'ur', 'he'),
			'language.default'              => 'en',
			'language.parameter.name'       => 'language',
			'language.parameter.setcookie'  => true,
			'language.cookie.name'          => 'language',
			'language.cookie.domain'        => null,
			'language.cookie.path'          => '/',
			'language.cookie.lifetime'      => (60 * 60 * 24 * 900),
			'language.i18n.backend'         => 'SimpleSAMLphp',
			'attributes.extradictionary'    => null,
			'theme.use'                     => $data['theme'] ?: 'default',
			'template.auto_reload'          => false,
			'idpdisco.enableremember'       => true,
			'idpdisco.rememberchecked'      => true,
			'idpdisco.validate'             => true,
			'idpdisco.extDiscoveryStorage'  => null,
			'idpdisco.layout'               => 'dropdown',
			'authproc.idp'                  => array(
				30 => 'core:LanguageAdaptor',
				45 => array(
					'class'         => 'core:StatisticsWithAttribute',
					'attributename' => 'realm',
					'type'          => 'saml20-idp-SSO',
				),
				50 => 'core:AttributeLimit',
				99 => 'core:LanguageAdaptor',
			),
			'authproc.sp'                   => array(
				90 => 'core:LanguageAdaptor',
			),
			'metadata.sources'              => array(
				array('type' => 'flatfile'),
				array('type' => 'flatfile', 'directory' => 'metadata-generated'),
			),
			'metadata.sign.enable'          => false,
			'metadata.sign.privatekey'      => null,
			'metadata.sign.privatekey_pass' => null,
			'metadata.sign.certificate'     => null,
			'store.type'                    => 'sql',
			'store.sql.dsn'                 => str_replace('mysqli', 'mysql', $config->get('dbtype')) . ':dbname=' . $config->get('db') . ';host=' . $config->get('host'),
			'store.sql.username'            => $config->get('user'),
			'store.sql.password'            => $config->get('password'),
			'store.sql.prefix'              => substr($config->get('dbprefix'), 0, -1),
			'store.redis.host'              => 'localhost',
			'store.redis.port'              => 6379,
			'store.redis.prefix'            => 'SimpleSAMLphp',
		);

		// Check if the consent exists
		$folder = '';

		if (strpos($data['consent'], ':'))
		{
			list($folder) = explode(':', $data['consent']);
		}

		if ($folder && file_exists(JPATH_LIBRARIES . '/simplesamlphp/modules/' . $folder))
		{
			$default['authproc.idp']['90'] = array('class' => $data['consent']);
		}

		// Re-map fields
		$data['auth.adminpassword'] = $data['adminpassword'];

		// Remove tags that Joomla! adds or are no longer needed
		unset($data['adminpassword']);
		unset($data['debug']);
		unset($data['tags']);
		unset($data['theme']);

		// Merge the data settings
		$data = array_merge($default, $data);

		// Write the data to the configuration file
		$config = var_export($data, true);
		$filename = JPATH_LIBRARIES . '/simplesamlphp/config/config.php';

		file_put_contents($filename, '<?php' . "\r\n" . '$config = ' . $config . ';');
	}

	/**
	 * Setup the identity provider settings.
	 *
	 * @param   string  $privateKey   The private key to use
	 * @param   string  $certificate  The certificate file to use
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function setupIdentityProvider($privateKey, $certificate)
	{
		// Check if the privateKey and certificate exist
		$certFolder = JPATH_LIBRARIES . '/simplesamlphp/cert/';

		if (!file_exists($certFolder . $privateKey))
		{
			throw new InvalidArgumentException(Text::sprintf('COM_SSO_PRIVATEKEY_NOT_EXIST', $privateKey, $certFolder));
		}

		if (!file_exists($certFolder . $certificate))
		{
			throw new InvalidArgumentException(Text::sprintf('COM_SSO_CERTIFICATE_NOT_EXIST', $certificate, $certFolder));
		}

		// Update the libraries\simplesamlphp\config\authsources.php
		$config = array();
		require JPATH_LIBRARIES . '/simplesamlphp/config/authsources.php';

		// Make sure there is an admin entry
		if (!array_key_exists('admin', $config))
		{
			$config['admin'] = array(
				0 => 'core:AdminPassword',
			);
		}

		$config['joomla-idp'] = array(
			'joomla:JOOMLA',
		);

		// Write the data to the configuration file
		$config = var_export($config, true);
		$filename = JPATH_LIBRARIES . '/simplesamlphp/config/authsources.php';

		file_put_contents($filename, '<?php' . "\r\n" . '$config = ' . $config . ';');

		// Update the libraries\simplesamlphp\metadata-generated\saml20-idp-hosted.php
		$default = array(
			'host'        => '__DEFAULT__',
			'privatekey'  => $privateKey,
			'certificate' => $certificate,
			'auth'        => 'joomla-idp'
		);

		// Write the data to the configuration file
		$config = var_export($default, true);
		$filename = JPATH_LIBRARIES . '/simplesamlphp/metadata-generated/saml20-idp-hosted.php';

		file_put_contents($filename, '<?php' . "\r\n" . '$metadata[\'__DYNAMIC:1__\'] = ' . $config . ';');
	}

	/**
	 * Remove the identity provider settings.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function removeIdentityProvider()
	{
		// Update the libraries\simplesamlphp\config\authsources.php
		$config = array();
		require JPATH_LIBRARIES . '/simplesamlphp/config/authsources.php';

		unset($config['joomla-idp']);

		// Write the data to the configuration file
		$config = var_export($config, true);
		$filename = JPATH_LIBRARIES . '/simplesamlphp/config/authsources.php';

		file_put_contents($filename, '<?php' . "\r\n" . '$config = ' . $config . ';');

		// Update the libraries\simplesamlphp\metadata\saml20-idp-hosted.php
		$default = array(
			'host'        => '__DEFAULT__',
			'privatekey'  => 'server.pem',
			'certificate' => 'server.crt',
			'auth'        => 'example-userpass'
		);

		// Write the data to the configuration file
		$config = var_export($default, true);
		$filename = JPATH_LIBRARIES . '/simplesamlphp/metadata-generated/saml20-idp-hosted.php';

		file_put_contents($filename, '<?php' . "\r\n" . '$metadata[\'__DYNAMIC:1__\'] = ' . $config . ';');
	}
}
