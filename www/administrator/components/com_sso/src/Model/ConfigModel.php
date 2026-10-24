<?php
/**
 * @package     SSO.Component
 *
 * @author      RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright   Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://rolandd.com
 */

namespace Rolandd\Component\Sso\Administrator\Model;

defined('_JEXEC') or die;

use Exception;
use InvalidArgumentException;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Uri\Uri;
use Rolandd\Component\Sso\Administrator\Sso\Authsources;
use Rolandd\Component\Sso\Administrator\Sso\Config;
use Rolandd\Component\Sso\Administrator\Sso\ModuleCron;
use SimpleSAML\Logger;
use stdClass;

/**
 * Configuration model
 *
 * @since  1.0.0
 */
class ConfigModel extends AdminModel
{
	/**
	 * Get the form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  Form|boolean  A Form object on success | False on failure.
	 *
	 * @since   1.0.0
	 *
	 * @throws  Exception
	 */
	public function getForm($data = array(), $loadData = true)
	{
		$form = $this->loadForm('com_sso.config', 'config', ['control' => 'jform', 'load_data' => $loadData]);

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
		$this->saveSamlConfig(array_merge($data['sso'], $data['modules'], $data['csp']));

		// Check if we need to setup the Identity Provider settings
		if (!$data['sso']['idp'])
		{
            $this->removeIdentityProvider();

            return true;
        }

        if (empty($data['sso']['privatekey']))
        {
            throw new InvalidArgumentException(Text::_('COM_SSO_EMPTY_PRIVATEKEY'));
        }

        if (empty($data['sso']['certificate']))
        {
            throw new InvalidArgumentException(Text::_('COM_SSO_EMPTY_CERTIFICATE'));
        }

        $this->setupIdentityProvider(
            $data['sso']['privatekey'],
            $data['sso']['certificate'],
            trim($data['sso']['baseurlpath'], '/') . '/',
            $data['sso']['privatekey_pass'],
            $data['sso']['login'],
            $data['authproc']['filters']
        );

		return true;
	}

    /**
     * Validate the form input.
     *
     * @return  bool  True on success | False on failure.
     *
     * @since   2.0.0
     */
    public function validate($form, $data, $group = null)
    {

        if (str_contains($data['sso']['secretsalt'], '%'))
        {
            throw new InvalidArgumentException(Text::_('COM_SSO_SECRETSALT_NO_PERCENT_SIGN'));
        }

        return parent::validate(
            $form,
            $data,
            $group
        );
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
		$config = new Config(true);

		$baseurlpath = trim($data['baseurlpath']);

		if (!str_ends_with($baseurlpath, '/'))
		{
			$baseurlpath .= '/';
		}

		$config->set('baseurlpath', $baseurlpath);
		$config->set('auth.adminpassword', $config->hashPassword($data['adminpassword']));
		$config->set('secretsalt', $data['secretsalt']);
		$config->set('technicalcontact_name', $data['technicalcontact_name']);
		$config->set('technicalcontact_email', $data['technicalcontact_email']);
		$config->set('enable.saml20-idp', (bool) $data['idp']);

        $data['adminpassword'] = '';

		if ((bool) $data['idp'] === true && (string) $data['privatekey'] !== '-1')
		{
			$config->set('metadata.sign.enable', true);
			$config->set('metadata.sign.privatekey', $data['privatekey']);
			$config->set('metadata.sign.privatekey_pass', $data['privatekey_pass']);
			$config->set('metadata.sign.certificate', $data['certificate']);
		}

		$folder = '';
		$theme  = '';

		if (strpos($data['theme'], ':'))
		{
			[$folder, $theme] = explode(':', $data['theme']);
		}

		if (!$folder || !file_exists(PATH_SIMPLESAMLPHP . '/modules/' . $folder . '/themes/' . $theme))
		{
			$data['theme'] = 'default';
		}

		$config->set('theme.use', $data['theme']);

        if ($data['themeController'] !== '')
        {
		    $config->set('theme.controller', $data['themeController']);
        }

		// Check if the consent exists
		$folder = '';

		if (strpos($data['consent'], ':'))
		{
			[$folder] = explode(':', $data['consent']);
		}

		if (!$folder || !file_exists(PATH_SIMPLESAMLPHP . '/modules/' . $folder))
		{
			$data['consent'] = '';
		}

		if ($data['consent'] === '')
		{
			$config->remove('authproc.idp~90');
		}
		else
		{
			$config->set('authproc.idp~90~class', $data['consent']);
		}

		$languageAvailable = (array) ($data['language_available'] ?? []);

		if (empty($languageAvailable))
		{
			$languageAvailable = ['en'];
		}

		$config->set('language.available', array_values($languageAvailable));
		$config->set('language.default', ($data['language_default'] ?? 'en'));

		$config->set('production', (bool) $data['production']);

        $loggingLevel = match ((int)$data['logging_level']) {
            0 => Logger::EMERG,
            1 => Logger::ALERT,
            2 => Logger::CRIT,
            3 => Logger::ERR,
            4 => Logger::WARNING,
            6 => Logger::INFO,
            7 => Logger::DEBUG,
            default => Logger::NOTICE,
        };

		$debug = (bool) $data['debug'];
		$config->set('debug', ['saml' => $debug, 'backtraces' => $debug, 'validatexml' => $debug]);
		$config->set('showerrors', $debug);
		$config->set('errorreporting', $debug);
		$config->set('logging.level', $loggingLevel);
		$config->set('logging.handler', ($data['logging_handler'] ?? 'syslog'));
		$config->set('logging.format',
			($data['logging_format'] ?? '%date{M j H:i:s} %process %level %stat[%trackid] %msg'));
		$config->set('logging.processname', ($data['logging_processname'] ?? 'simplesamlphp'));
		$config->set('logging.logfile', ($data['logging_logfile'] ?? 'simplesamlphp.log'));

		$moduleCron = new ModuleCron;
		$moduleCron->set('debug_message', $debug);
		$moduleCron->set('sendemail', (bool) ($data['cronemail'] ?? false));
		$moduleCron->write();

        if (isset($data['modules']))
        {
            $moduleEnable = [];

            foreach ($data['modules'] as $module)
            {
                $moduleEnable[$module['name']] = (bool)$module['enabled'];
            }

            $config->set('module.enable', $moduleEnable);
        }

        $cspHeaders = [];

        if (isset($data['contentsecuritypolicy_values'])) {
            $cspString = '';

            foreach ($data['contentsecuritypolicy_values'] as $policy) {
                $cspString .= $policy['directive'] . ' ' . $policy['value'] . '; ';
            }

            $cspString = rtrim($cspString, '; ');

            if ($cspString) {
                $cspHeaders['Content-Security-Policy'] = $cspString;
            }

            unset($data['contentsecuritypolicy_values']);
        }


        if (isset($data['additional_httpheader'])) {
            foreach ($data['additional_httpheader'] as $header) {
                $cspHeaders[$header['key']] = $header['value'];
            }

            if ($cspHeaders) {
                $config->set('headers.security', $cspHeaders);
            }
        }

		$config->write();

		Factory::getApplication()->setUserState('com_sso.data', $data);
	}

    /**
	 * Set up the identity provider settings.
     * Update the libraries/simplesamlphp/metadata/saml20-idp-hosted.php
	 *
	 * @param   string  $privateKey          The private key to use
	 * @param   string  $certificate         The certificate file to use
	 * @param   string  $baseurlpath         The SimpleSAMLphp baseurlpath (e.g. "sso/")
	 * @param   string  $privateKeyPassword  The password for the certificate file
	 * @param   string  $loginModule         The name of the login module
	 * @param   array   $authproc            A list of authentication processing filters
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function setupIdentityProvider(
		string $privateKey,
		string $certificate,
		string $baseurlpath,
		string $privateKeyPassword = '',
		string $loginModule = 'joomla:Joomla',
		array $authproc = []
	): void {
		$certFolder = PATH_SIMPLESAMLPHP . '/cert';

		if (!file_exists($certFolder . '/' . $privateKey))
		{
			throw new InvalidArgumentException(Text::sprintf('COM_SSO_PRIVATEKEY_NOT_EXIST', $privateKey, $certFolder));
		}

		if (!file_exists($certFolder . '/' . $certificate))
		{
			throw new InvalidArgumentException(
				Text::sprintf('COM_SSO_CERTIFICATE_NOT_EXIST', $certificate, $certFolder)
			);
		}

        $uri       = Uri::getInstance();
		$config = new Authsources;

		$config->set('joomla-idp', [$loginModule]);
		$config->write();

		$default = [
			'host'            => $uri->getHost(),
			'privatekey'      => $privateKey,
			'privatekey_pass' => $privateKeyPassword,
			'certificate'     => $certificate,
			'auth'            => 'joomla-idp',
		];

		if ($authproc)
		{
			$filters = [];

			foreach ($authproc as $filter)
			{
				$filters[$filter['authproc']['priority']] = [
					'class'     => $filter['authproc']['class'],
					'attribute' => $filter['authproc']['attribute'],
					'Format'    => $filter['authproc']['format'],
				];
			}

			$default['authproc'] = $filters;
		}

		// Write the data to the configuration file
		$config   = var_export($default, true);
		$filename = PATH_SIMPLESAMLPHP . '/metadata/saml20-idp-hosted.php';
        $entityId  = $uri->getScheme() . '://' . $uri->getHost() . '/' . $baseurlpath . 'saml2/idp/metadata.php';

		file_put_contents($filename, '<?php' . "\r\n" . '$metadata[\'' . $entityId . '\'] = ' . $config . ';');
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
		$config = new Authsources;

		$config->remove('joomla-idp');
		$config->write();

		// Update the libraries\simplesamlphp\metadata\saml20-idp-hosted.php
		$default = [
			'host'        => '__DEFAULT__',
			'privatekey'  => 'server.pem',
			'certificate' => 'server.crt',
			'auth'        => 'example-userpass',
        ];

		// Write the data to the configuration file
		$config   = var_export($default, true);
		$filename = PATH_SIMPLESAMLPHP . '/metadata/saml20-idp-hosted.php';

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

		$config      = new Config;
		$authsources = new Authsources;

		$data->sso->baseurlpath            = $config->get('baseurlpath');
		$data->sso->adminpassword          = '';
		$data->sso->secretsalt             = $config->get('secretsalt');
		$data->sso->technicalcontact_name  = $config->get('technicalcontact_name');
		$data->sso->technicalcontact_email = $config->get('technicalcontact_email');
		$data->sso->idp                    = (int) $config->get('enable.saml20-idp', 0);
		$data->sso->login                  = $authsources->get('joomla-idp~0', 'joomla:Joomla');
		$data->sso->theme                  = $config->get('theme.use', 'default');
		$data->sso->themeController        = $config->get('theme.controller', '');
		$data->sso->consent                = $config->get('authproc.idp~90~class', '');
		$data->sso->language_default       = $config->get('language.default', 'en');
		$data->sso->language_available     = $config->get('language.available', ['en']);
		$data->sso->production             = (int) $config->get('production', 1);
		$data->sso->debug                  = (int) $config->get('showerrors', 0);
		$data->sso->logging_level          = (int) $config->get('logging.level', 7);
		$data->sso->logging_handler        = $config->get('logging.handler', 'syslog');
		$data->sso->logging_format         = $config->get('logging.format', '%date{M j H:i:s} %process %level %stat[%trackid] %msg');
		$data->sso->logging_processname    = $config->get('logging.processname', 'simplesamlphp');
		$data->sso->logging_logfile        = $config->get('logging.logfile', 'simplesamlphp.log');
		$data->sso->cronemail              = (int) (new ModuleCron)->get('sendemail', false);

        $modules = $config->get('module.enable', []);
        $moduleCount = 0;

        foreach ($modules as $moduleName => $moduleEnabled)
        {
            $data->modules['modules']['modules' . $moduleCount]['name'] = $moduleName;
            $data->modules['modules']['modules' . $moduleCount]['enabled'] = $moduleEnabled ? 1 : 0;
            $moduleCount++;
        }

        $cspHeaders = $config->get('headers.security', []);
        $cspValuesCount = 0;
        $additionalHttpHeaderCount = 0;

        foreach ($cspHeaders as $headerName => $headerValue) {
            if ($headerName === 'Content-Security-Policy') {
                preg_match_all('/(\w[\w-]*)\s+([^;]+)/', $headerValue, $matches, PREG_SET_ORDER);

                foreach ($matches as $match) {
                    $data->csp['contentsecuritypolicy_values']['contentsecuritypolicy_values' . $cspValuesCount] = [
                        'directive' => $match[1],
                        'value'     => trim($match[2]),
                    ];

                    $cspValuesCount++;
                }
            }

            if ($headerName !== 'Content-Security-Policy') {
                $data->csp['additional_httpheader']['additional_httpheader' . $additionalHttpHeaderCount] = [
                        'key' => $headerName,
                        'value' => $headerValue,
                ];
                $additionalHttpHeaderCount++;
            }
        }

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
		$metadata     = [];
		$metadataFile = PATH_SIMPLESAMLPHP . '/metadata/saml20-idp-hosted.php';

		if (!file_exists($metadataFile))
		{
			$metadataFile = PATH_SIMPLESAMLPHP . '/metadata-templates/saml20-idp-hosted.php';
		}

        if (file_exists($metadataFile)) {
            require $metadataFile;
            $entityId                   = array_key_first($metadata) ?? '';
            $data->sso->privatekey      = $metadata[$entityId]['privatekey'] ?? '';
            $data->sso->certificate     = $metadata[$entityId]['certificate'] ?? '';
            $data->sso->privatekey_pass = $metadata[$entityId]['privatekey_pass'] ?? '';
        }

		$data->authproc         = [];
		$filterCount            = 0;

		if (isset($metadata[$entityId]['authproc']))
		{
			foreach ($metadata[$entityId]['authproc'] as $priority => $authproc)
			{
				$data->authproc['filters']['filters' . $filterCount]['authproc']['priority']  = $priority;
				$data->authproc['filters']['filters' . $filterCount]['authproc']['class']     = $authproc['class'];
				$data->authproc['filters']['filters' . $filterCount]['authproc']['attribute'] = $authproc['attribute'] ?? '';
				$data->authproc['filters']['filters' . $filterCount]['authproc']['format']    = $authproc['Format'] ?? '';
				$filterCount++;
			}
		}

		return $data;
	}
}
