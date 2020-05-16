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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;

/**
 * Configuration model
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
	 *
	 * @throws  Exception
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
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success, False on error.
	 *
	 * @since   1.0.0
	 *
	 * @throws  Exception
	 */
	public function save($data): bool
	{
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
				$this->setupIdentityProvider($data['sso']['privatekey'], $data['sso']['certificate'],
					$data['sso']['login']
				);
			}
			catch (Exception $exception)
			{
				$this->setError($exception->getMessage());

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
	 *
	 * @throws  Exception
	 */
	private function saveSamlConfig(array $data): void
	{
		// Load the SimpleSAMLphp config helper
		$config = new SsoConfig(true);

		// Set the base URL path
		$baseurlpath = trim($data['baseurlpath']);

		// Check if it ends with a forward slash
		if (substr($baseurlpath, -1) !== '/')
		{
			$baseurlpath .= '/';
		}

		$config->set('baseurlpath', $baseurlpath);

		// Set the administrator password
		$config->set('auth.adminpassword', $data['adminpassword']);

		// Set the secret salt
		$config->set('secretsalt', $data['secretsalt']);

		// Set the technical contact name
		$config->set('technicalcontact_name', $data['technicalcontact_name']);

		// Set the technical contact email
		$config->set('technicalcontact_email', $data['technicalcontact_email']);

		// Set the IDP setting
		$config->set('enable.saml20-idp', (bool) $data['idp']);

		// Set the private key
		$config->set('privatekey', $data['privatekey']);

		// Set the certificate
		$config->set('certificate', $data['certificate']);

		// Set the theme settings
		$folder = '';
		$theme  = '';

		if (strpos($data['theme'], ':'))
		{
			[$folder, $theme] = explode(':', $data['theme']);
		}

		if (!$folder || !file_exists(JPATH_LIBRARIES . '/simplesamlphp/modules/' . $folder . '/themes/' . $theme))
		{
			$data['theme'] = 'default';
		}

		$config->set('theme.use', $data['theme']);

		$config->set('theme.controller', $data['themeController']);

		// Check if the consent exists
		$folder = '';

		if (strpos($data['consent'], ':'))
		{
			[$folder] = explode(':', $data['consent']);
		}

		if (!$folder || !file_exists(JPATH_LIBRARIES . '/simplesamlphp/modules/' . $folder))
		{
			$data['consent'] = '';
		}

		// If the consent is empty, remove it
		if ($data['consent'] === '')
		{
			$config->remove('authproc.idp~90');
		}
		else
		{
			$config->set('authproc.idp~90~class', $data['consent']);
		}

		// Set the production setting
		$config->set('production', (bool) $data['production']);

		// Set the debug settings
		$debug = (bool) $data['debug'];
		$config->set('debug', ['saml' => $debug, 'backtraces' => $debug, 'validatexml' => $debug]);
		$config->set('showerrors', $debug);
		$config->set('errorreporting', $debug);

		$config->write();

		// Store the data for showing on the form
		Factory::getApplication()->setUserState('com_sso.data', $data);
	}

	/**
	 * Setup the identity provider settings.
	 *
	 * @param   string  $privateKey   The private key to use
	 * @param   string  $certificate  The certificate file to use
	 * @param   string  $loginModule  The name of the login module
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function setupIdentityProvider(
		string $privateKey,
		string $certificate,
		string $loginModule = 'joomla:Joomla'
	): void {
		// Check if the privateKey and certificate exist
		$certFolder = JPATH_LIBRARIES . '/simplesamlphp/cert/';

		if (!file_exists($certFolder . $privateKey))
		{
			throw new InvalidArgumentException(Text::sprintf('COM_SSO_PRIVATEKEY_NOT_EXIST', $privateKey, $certFolder));
		}

		if (!file_exists($certFolder . $certificate))
		{
			throw new InvalidArgumentException(
				Text::sprintf('COM_SSO_CERTIFICATE_NOT_EXIST', $certificate, $certFolder)
			);
		}

		// Update the libraries\simplesamlphp\config\authsources.php
		$config = new SsoAuthsources;

		$config->set('joomla-idp', [$loginModule]);

		// Write. the data to the configuration file
		$config->write();

		// Update the libraries\simplesamlphp\metadata\saml20-idp-hosted.php
		$default = array(
			'host'        => '__DEFAULT__',
			'privatekey'  => $privateKey,
			'certificate' => $certificate,
			'auth'        => 'joomla-idp'
		);

		// Write the data to the configuration file
		$config   = var_export($default, true);
		$filename = JPATH_LIBRARIES . '/simplesamlphp/metadata/saml20-idp-hosted.php';

		file_put_contents($filename, '<?php' . "\r\n" . '$metadata[\'__DYNAMIC:1__\'] = ' . $config . ';');
	}

	/**
	 * Remove the identity provider settings.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function removeIdentityProvider(): void
	{
		// Update the libraries\simplesamlphp\config\authsources.php
		$config = new SsoAuthsources;

		$config->remove('joomla-idp');
		$config->write();

		// Update the libraries\simplesamlphp\metadata\saml20-idp-hosted.php
		$default = array(
			'host'        => '__DEFAULT__',
			'privatekey'  => 'server.pem',
			'certificate' => 'server.crt',
			'auth'        => 'example-userpass'
		);

		// Write the data to the configuration file
		$config   = var_export($default, true);
		$filename = JPATH_LIBRARIES . '/simplesamlphp/metadata/saml20-idp-hosted.php';

		file_put_contents($filename, '<?php' . "\r\n" . '$metadata[\'__DYNAMIC:1__\'] = ' . $config . ';');
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  object  The data for the form.
	 *
	 * @since   1.0.0
	 *
	 * @throws  Exception
	 */
	protected function loadFormData()
	{
		$data      = new stdClass;
		$data->sso = new stdClass;

		// Require the SimpleSAMLphp configuration
		/** @var SsoConfig $config */
		$config      = new SsoConfig;
		$authsources = new SsoAuthsources;

		$data->sso->baseurlpath            = $config->get('baseurlpath');
		$data->sso->adminpassword          = $config->get('auth.adminpassword');
		$data->sso->secretsalt             = $config->get('secretsalt');
		$data->sso->technicalcontact_name  = $config->get('technicalcontact_name');
		$data->sso->technicalcontact_email = $config->get('technicalcontact_email');
		$data->sso->idp                    = (int) $config->get('enable.saml20-idp', 0);
		$data->sso->login                  = $authsources->get('joomla-idp~0', 'joomla:Joomla');
		$data->sso->theme                  = $config->get('theme.use', 'default');
		$data->sso->themeController        = $config->get('theme.controller', '');
		$data->sso->consent                = $config->get('authproc.idp~90~class', '');
		$data->sso->production             = (int) $config->get('production', 1);
		$data->sso->debug                  = (int) $config->get('showerrors', 0);

		// Load any data just saved, this is not loaded from the file
		$savedData = Factory::getApplication()->getUserState('com_sso.data', []);

		if (is_array($savedData))
		{
			array_walk($savedData,
				static function ($value, $name) use (&$data) {
					$data->sso->$name = $value;
				}
			);

			Factory::getApplication()->setUserState('com_sso.data', []);
		}

		// Get the certificate information
		$metadata     = array();
		$metadataFile = JPATH_LIBRARIES . '/simplesamlphp/metadata/saml20-idp-hosted.php';

		if (!file_exists($metadataFile))
		{
			$metadataFile = JPATH_LIBRARIES . '/simplesamlphp/metadata-templates/saml20-idp-hosted.php';
		}

		require $metadataFile;
		$data->sso->privatekey  = $metadata['__DYNAMIC:1__']['privatekey'];
		$data->sso->certificate = $metadata['__DYNAMIC:1__']['certificate'];

		return $data;
	}
}
