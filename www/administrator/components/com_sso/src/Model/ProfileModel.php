<?php
/**
 * @package    SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

namespace Rolandd\Component\Sso\Administrator\Model;

defined('_JEXEC') or die;

use Exception;
use InvalidArgumentException;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Uri\Uri;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Rolandd\Component\Sso\Administrator\Sso\CronSync;
use Rolandd\Component\Sso\Administrator\Table\ProfileTable;
use SimpleSAML\Module\metarefresh\MetaLoader;
use stdClass;

/**
 * Profile model.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class ProfileModel extends AdminModel
{
	/**
	 * Form context
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	private $context = 'com_sso.profile';

	/**
	 * Get the form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A Form object on success | False on failure.
	 *
	 * @since   1.0.0
	 *
	 * @throws  Exception
	 */
	public function getForm($data = [], $loadData = true)
	{
		$form = $this->loadForm($this->context, 'profile', ['control' => 'jform', 'load_data' => $loadData]);

		if (!is_object($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to validate the form data.
	 *
	 * @param   Form    $form   The form to validate against.
	 * @param   array   $data   The data to validate.
	 * @param   string  $group  The name of the field group to validate.
	 *
	 * @return  array|boolean  Array of filtered data if valid, false otherwise.
	 *
	 * @since   1.0.0
	 *
	 * @throws  Exception
	 *
	 * @see     \JFormRule
	 * @see     \JFilterInput
	 */
	public function validate($form, $data, $group = null)
	{
		// Filter and validate the form data.
		$profileData = $form->filter($data);
		$return      = $form->validate($profileData, $group);

		// Check for an error.
		if ($return instanceof Exception)
		{
			$this->setError($return->getMessage());

			return false;
		}

		// Check the validation results.
		if ($return === false)
		{
			// Get the validation messages from the form.
			foreach ($form->getErrors() as $message)
			{
				$this->setError($message);
			}

			return false;
		}

		return $profileData;
	}

	/**
	 * Load the settings for a service provider profile.
	 *
	 * @param   string  $alias  The provider alias to load the settings for
	 *
	 * @return  object  List of settings.
	 *
	 * @since   1.0.0
	 *
	 * @throws  Exception
	 */
	private function loadServiceProviderSettings(string $alias = 'default-sp', string $protocol = 'saml'): stdClass
	{
		$this->populateState();

        return match ($protocol) {
            'saml' => $this->loadSamlConfiguration($alias),
            'ldap' => $this->loadLdapConfiguration($alias),
            default => new stdClass,
        };
	}

    /**
     * Load the SAML configuration.
     *
     * @return  stdClass  Config read from authsources.php.
     *
     * @since   1.5.0
     */
    private function loadSamlConfiguration(string $alias): stdClass
    {
        $data = new stdClass;
        $data->authorization_saml = new stdClass;

        // Require the SimpleSAMLphp authorization sources
        require PATH_SIMPLESAMLPHP . '/config/authsources.php';

        /** @var $config array */
        if (!array_key_exists($alias, $config))
        {
            $config[$alias] = [];
        }

        $data->authorization_saml->entityID                      = $config[$alias]['entityID'] ?? Uri::getInstance()->toString(['scheme', 'user', 'pass', 'host', 'port']);
        $data->authorization_saml->privatekey                    = $config[$alias]['privatekey'] ?? '';
        $data->authorization_saml->privatekey_pass               = $config[$alias]['privatekey_pass'] ?? '';
        $data->authorization_saml->certificate                   = $config[$alias]['certificate'] ?? '';
        $data->authorization_saml->metadata_sign_enable          = (int) ($config[$alias]['metadata.sign.enable'] ?? '');
        $data->authorization_saml->metadata_sign_privatekey      = $config[$alias]['metadata.sign.privatekey'] ?? '';
        $data->authorization_saml->metadata_sign_privatekey_pass = $config[$alias]['metadata.sign.privatekey_pass'] ?? '';
        $data->authorization_saml->metadata_sign_certificate     = $config[$alias]['metadata.sign.certificate'] ?? '';
        $data->authorization_saml->sign_logout                   = (int) ($config[$alias]['sign.logout'] ?? '');

        // Load the Joomla config
        $data->joomla = [];
        $db           = $this->getDatabase();
        $query        = $db->createQuery()
            ->select($db->quoteName(['params', 'metadata', 'metadataXml']))
            ->from($db->quoteName('#__sso_profiles'))
            ->where($db->quoteName('alias') . ' = ' . $db->quote($alias));
        $db->setQuery($query);
        $joomlaConfig                  = $db->loadObject();

        if (is_null($joomlaConfig))
        {
            $joomlaConfig = new stdClass;
            $joomlaConfig->metadata = '';
            $joomlaConfig->metadataXml = '';
            $joomlaConfig->params = '{}';
        }

        $data->authorization_saml->idpMetadata = $joomlaConfig->metadata !== '' ? $joomlaConfig->metadata : ($config[$alias]['idp'] ?? '');
        $data->authorization_saml->metadataXml = $joomlaConfig->metadataXml ?? '';

        $idpLogin  = $config[$alias]['idp'] ?? '';
        $idpRemote = PATH_SIMPLESAMLPHP . '/metadata-generated/' . $alias . '/saml20-idp-remote.php';

        if (!is_file($idpRemote))
        {
            $idpRemote = PATH_SIMPLESAMLPHP . '/metadata-generated/saml20-idp-remote.php';
        }

        if (is_file($idpRemote))
        {
            require $idpRemote;
            $idpLogin = '';

            /** @var $metadata array */
            if (isset($metadata) && is_array($metadata))
            {
                $idpLogin = key($metadata) ?? '';
            }
        }

        $data->authorization_saml->idp = $idpLogin;
        $params                             = (new Registry($joomlaConfig->params))->toArray();

        if (array_key_exists('joomla', $params))
        {
            $data->joomla = $params['joomla'];
        }

        return $data;
    }

    /**
     * Load the LDAP configuration.
     *
     * @return  stdClass  Config read from authsources.php.
     *
     * @since   1.5.0
     */
    private function loadLdapConfiguration(string $alias): stdClass
    {
        $data = new stdClass;
        $data->authorization_ldap = new stdClass;

        // Require the SimpleSAMLphp authorization sources
        require PATH_SIMPLESAMLPHP . '/config/authsources.php';

        /** @var $config array */
        if (!array_key_exists($alias, $config))
        {
            $config[$alias] = [];
        }

        $markerPosition = strripos($config[$alias]['connection_string'], ':');
        $hostname = substr($config[$alias]['connection_string'], 0, $markerPosition);
        $port = substr($config[$alias]['connection_string'], ++$markerPosition);

        $data->authorization_ldap->hostname = $hostname;
        $data->authorization_ldap->port = (int) $port;
        $data->authorization_ldap->encryption = ($config[$alias]['encryption'] ?? 'none');
        $data->authorization_ldap->version = (int) ($config[$alias]['version'] ?? 3);
        $data->authorization_ldap->debug = (int) ($config[$alias]['debug'] ?? 0);
        $data->authorization_ldap->referrals = ($config[$alias]['options']['referrals'] ?? '0x00');
        $data->authorization_ldap->timeout = (int) ($config[$alias]['options']['network_timeout'] ?? 3);
        $data->authorization_ldap->attributes = null;

        if (isset($config[$alias]['attributes']) && is_array($config[$alias]['attributes']))
        {
            foreach ($config[$alias]['attributes'] as $key => $value)
            {
                $data->authorization_ldap->attributes['attributes' . $key]['attribute'] = $value;
            }
        }

        $data->authorization_ldap->dnpattern = $config[$alias]['dnpattern'] ?? '';
        $data->authorization_ldap->search_enable = (int) ($config[$alias]['search.enable'] ?? 0);


        if (isset($config[$alias]['search.base']) && is_array($config[$alias]['search.base']))
        {
            foreach ($config[$alias]['search.base'] as $key => $value)
            {
                $data->authorization_ldap->search_base['search_base' . $key]['base'] = $value;
            }
        }

        $data->authorization_ldap->search_scope = $config[$alias]['search.scope'] ?? 'subtree';

        $data->authorization_ldap->search_attributes = [];

        if (isset($config[$alias]['search.attributes']) && is_array($config[$alias]['search.attributes']))
        {
            foreach ($config[$alias]['search.attributes'] as $key => $value)
            {
                $data->authorization_ldap->search_attributes['search_attributes' . $key]['attribute'] = $value;
            }
        }

        $data->authorization_ldap->search_filter = $config[$alias]['search.filter'] ?? '';
        $data->authorization_ldap->search_username = $config[$alias]['search.username'] ?? '';
        $data->authorization_ldap->search_password = $config[$alias]['search.password'] ?? '';

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
	 *
	 * @throws  Exception
	 */
	public function save($data): bool
	{
		$data['params'] = json_encode(['joomla' => $data['joomla']]);

		$data['fieldmap'] = json_encode($data['fields']);

		$previousAlias = ((int) $data['id'] !== 0) ? ($this->getAliases([$data['id']])[0] ?? '') : '';

		if ((int) $data['id'] === 0)
		{
			$data['alias'] = ApplicationHelper::stringURLSafe($data['name']);
		}

		try
		{
			$this->checkAliasNotUsedByClient($data['alias']);
		}
		catch (InvalidArgumentException $exception)
		{
			// Don't also enqueueMessage() here - FormController::save() already turns
			// getError() into a "Save failed" message once this returns false.
			$this->setError($exception->getMessage());

			return false;
		}

		$data['metadata'] = $data['authorization_saml']['idpMetadata'] ?? '';
		$data['metadataXml'] = $data['authorization_saml']['metadataXml'] ?? '';

		if ($data['protocol'] === 'ldap')
		{
				$data['usernameCleanup'] = $data['authorization_' . $data['protocol']]['usernameCleanup'];
		}

		/** @var ProfileTable $table */
		$table = $this->getTable();

		if (!$table->save($data))
        {
            $this->setError($table->getError());

            return false;
        }

		$this->setState($this->getName() . '.id', $table->id);

		$this->saveServiceProviderAuthsources($data['authorization_' . $data['protocol']], $data['alias'], $data['protocol']);

		(new CronSync($this->getDatabase()))->sync();

		if ($previousAlias !== '' && $previousAlias !== $data['alias'])
		{
			$this->removeGeneratedDir($previousAlias);
		}

		$idpOverride = ($data['protocol'] === 'saml') ? trim((string) ($data['authorization_saml']['idp'] ?? '')) : '';

		try
		{
			$this->metarefresh([$table->id], $idpOverride !== '' ? [$data['alias'] => $idpOverride] : []);
		}
		catch (Exception $exception)
		{
			Factory::getApplication()->enqueueMessage($exception->getMessage(), 'warning');
		}

		return true;
	}

	/**
	 * Guard against a Profile alias colliding with an existing Client's outputDir.
	 *
	 * Profiles and Clients both write their generated metadata into
	 * metadata-generated/<alias-or-outputDir>/, and SimpleSAMLphp's
	 * MetaLoader::writeMetadataFiles() treats that directory as exclusive to a single metadata
	 * source: any metadata type it didn't just (re)generate there is deleted as "stale". Sharing
	 * a name between a Profile and a Client means whichever one refreshes second silently wipes
	 * out the other's generated metadata file, breaking it until it is refreshed again.
	 *
	 * @param   string  $alias  The alias being saved.
	 *
	 * @return  void
	 *
	 * @throws  InvalidArgumentException
	 * @since   2.3.0
	 */
	private function checkAliasNotUsedByClient(string $alias): void
	{
		$db    = $this->getDatabase();
		$query = $db->createQuery()
			->select($db->quoteName('name'))
			->from($db->quoteName('#__sso_clients'))
			->where($db->quoteName('outputDir') . ' = ' . $db->quote($alias));
		$db->setQuery($query);

		$clientName = $db->loadResult();

		if ($clientName !== null)
		{
			throw new InvalidArgumentException(Text::sprintf('COM_SSO_ALIAS_IN_USE_BY_CLIENT', $alias, $clientName));
		}
	}

	/**
	 * Method to change the published state of one or more records.
	 *
	 * An unpublished profile should stop being scheduled for cron refresh and stop being
	 * trusted via metadata.sources, same as a deleted one - but without touching its
	 * generated metadata files, so re-publishing it needs nothing more than another sync.
	 *
	 * @param   array    &$pks   A list of the primary keys to change.
	 * @param   integer   $value The value of the published state.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   2.4.0
	 */
	public function publish(&$pks, $value = 1)
	{
		if (!parent::publish($pks, $value))
		{
			return false;
		}

		(new CronSync($this->getDatabase()))->sync();

		return true;
	}

	/**
	 * Method to delete one or more records.
	 *
	 * Deleting a profile removes it from the profiles table, but module_metarefresh.php and
	 * config.php's metadata.sources are generated from that table, so they need to be rebuilt
	 * afterwards too - otherwise the deleted profile's entry keeps being scheduled for refresh
	 * by the metarefresh cron job indefinitely, and SimpleSAMLphp keeps trusting metadata from
	 * a directory Joomla no longer knows about. The generated metadata directory itself is also
	 * removed, since nothing else will ever clean it up once the profile is gone.
	 *
	 * @param   array  &$pks  An array of record primary keys.
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 *
	 * @since   2.4.0
	 */
	public function delete(&$pks)
	{
		$aliases = $this->getAliases((array) $pks);

		if (!parent::delete($pks))
		{
			return false;
		}

		(new CronSync($this->getDatabase()))->sync();

		foreach ($aliases as $alias)
		{
			$this->removeGeneratedDir($alias);
		}

		return true;
	}

	/**
	 * Remove a metadata-generated/<alias>/ directory, if it exists.
	 *
	 * @param   string  $alias  The alias whose generated directory should be removed.
	 *
	 * @return  void
	 *
	 * @since   2.4.0
	 */
	private function removeGeneratedDir(string $alias): void
	{
		$path = PATH_SIMPLESAMLPHP . '/metadata-generated/' . $alias;

		if (is_dir($path))
		{
			Folder::delete($path);
		}
	}

	/**
	 * Get the alias of each of the given profiles.
	 *
	 * Must be called before the rows are deleted, since it reads them from the profiles table.
	 *
	 * @param   array  $pks  An array of record primary keys.
	 *
	 * @return  array  The alias of each profile.
	 *
	 * @since   2.4.0
	 */
	private function getAliases(array $pks): array
	{
		$pks = ArrayHelper::toInteger($pks);

		if (!$pks)
		{
			return [];
		}

		$db    = $this->getDatabase();
		$query = $db->createQuery()
			->select($db->quoteName('alias'))
			->from($db->quoteName('#__sso_profiles'))
			->where($db->quoteName('id') . ' IN (' . implode(',', $pks) . ')');
		$db->setQuery($query);

		return $db->loadColumn();
	}

	/**
	 * Method to save the authorization data.
	 *
	 * @param   array   $data     The form data
	 * @param   string  $alias    The provider alias
	 * @param   string  $protocol The protocol the configuration is for
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function saveServiceProviderAuthsources(array $data, string $alias = 'default-sp', string $protocol = 'saml'): void
	{
		// Require the SimpleSAMLphp authorization sources
		require PATH_SIMPLESAMLPHP . '/config/authsources.php';

		/** @var $config array */
		if (!array_key_exists('admin', $config))
		{
			$config['admin'] = ['core:AdminPassword'];
		}

        switch ($protocol)
        {
            case 'saml':
                $config = $this->saveSamlConfiguration($config, $data, $alias);
                break;
            case 'ldap':
                $config = $this->saveLdapConfiguration($config, $data, $alias);
                break;
        }

		// Write the data to the configuration file
		$config   = var_export($config, true);
		$filename = PATH_SIMPLESAMLPHP . '/config/authsources.php';

		file_put_contents($filename, '<?php $config = ' . $config . ';');
	}

    /**
     * Save the SAML configuration.
     *
     * @return  array  Config to store in authsources.php.
     *
     * @since   1.5.0
     */
    private function saveSamlConfiguration(array $config, array $data, string $alias): array
    {
        if (!array_key_exists($alias, $config))
        {
            $config[$alias] = [
                'saml:SP',
                'entityID'                      => Uri::getInstance()->toString(['scheme', 'user', 'pass', 'host', 'port']),
                'discoURL'                      => null,
                'NameIDPolicy'                  => ['Format' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified', 'AllowCreate' => false],
                'privatekey'                    => '',
                'privatekey_pass'               => '',
                'certificate'                   => '',
                'metadata.sign.enable'          => false,
                'metadata.sign.privatekey'      => '',
                'metadata.sign.privatekey_pass' => '',
                'metadata.sign.certificate'     => '',
                'sign.logout'                   => false,
                'signature.algorithm'           => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
                'SingleLogoutServiceBinding'    => [
                    'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                    'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                    'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
                ],
                'attributes'                    => [
                    'Name'                  => 'name',
                    'E-Mail Address'        => 'emailaddress',
                    'Username'              => 'upn',
                    'Authentication Method' => 'authnmethod',
                ],
                'attributes.NameFormat'         => 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri',
                'attributes.required'           => [
                    'name',
                    'emailaddress',
                    'upn',
                ],
            ];
        }

        // Set the user settings
        $config[$alias]['idp'] = $data['idp'] !== '' ? $data['idp'] : $data['idpMetadata'];

        $config[$alias]['entityID']                      = $data['entityID'] ?? Uri::getInstance()->toString(['scheme', 'user', 'pass', 'host', 'port']);
        $config[$alias]['sign.logout']                   = false;
        $config[$alias]['metadata.sign.enable']          = false;
        $config[$alias]['metadata.sign.privatekey']      = '';
        $config[$alias]['metadata.sign.privatekey_pass'] = '';
        $config[$alias]['metadata.sign.certificate']     = '';
        $config[$alias]['privatekey']                    = '';
        $config[$alias]['privatekey_pass']               = '';
        $config[$alias]['certificate']                   = '';

        if ((bool) $data['idp'] === true && (string) $data['privatekey'] !== '-1')
        {
            $config[$alias]['sign.logout']     = (bool) $data['sign_logout'];
            $config[$alias]['privatekey']      = $data['privatekey'];
            $config[$alias]['privatekey_pass'] = $data['privatekey_pass'];
            $config[$alias]['certificate']     = $data['certificate'];
        }

        if ((bool) $data['idp'] === true && (string) $data['metadata_sign_privatekey'] !== '-1')
        {
            $config[$alias]['metadata.sign.enable']          = (bool) $data['metadata_sign_enable'];
            $config[$alias]['metadata.sign.privatekey']      = $data['metadata_sign_privatekey'];
            $config[$alias]['metadata.sign.privatekey_pass'] = $data['metadata_sign_privatekey_pass'];
            $config[$alias]['metadata.sign.certificate']     = $data['metadata_sign_certificate'];
        }

        if (empty($config[$alias]['certificate']))
        {
            unset($config[$alias]['certificate']);
        }

        if (!is_array($config[$alias]['NameIDPolicy']))
        {
            $config[$alias]['NameIDPolicy'] = ['Format' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified', 'AllowCreate' => false];
        }

        return $config;
    }

    /**
     * Save the LDAP configuration.
     *
     * @return  array  Config to store in authsources.php.
     *
     * @since   1.5.0
     */
    private function saveLdapConfiguration(array $config, array $data, string $alias): array
    {
        $config[$alias] = ['ldap:Ldap'];
        $config[$alias]['connection_string'] = $data['hostname'] . ':' . $data['port'] ?? 389;
        $config[$alias]['encryption'] = $data['encryption'] ?? 'none';
        $config[$alias]['version'] = (int) $data['version'] ?? 3;
        $config[$alias]['debug'] = (bool) $data['debug'] ?? false;
        $config[$alias]['options']['referrals'] = $data['referrals'] ?? '0x00';
        $config[$alias]['options']['network_timeout'] = (int) $data['timeout'] ?? 3;

        foreach ($data['attributes'] as $attributes)
        {
            foreach ($attributes as $attribute)
            {
                $config[$alias]['attributes'][] = $attribute;
            }
        }

        $config[$alias]['dnpattern'] = $data['dnpattern'];
        $config[$alias]['search.enable'] = (bool) $data['search_enable'] ?? false;

        foreach ($data['search_base'] as $attributes)
        {
            foreach ($attributes as $attribute)
            {
                $config[$alias]['search.base'][] = $attribute;
            }
        }
        $config[$alias]['search.scope'] = $data['search_scope'] ?? 'sub';

        foreach ($data['search_attributes'] as $attributes)
        {
            foreach ($attributes as $attribute)
            {
                $config[$alias]['search.attributes'][] = $attribute;
            }
        }

        if ($data['search_filter'])
        {
            $config[$alias]['search.filter'] = $data['search_filter'] ?? false;
        }

        if ($data['search_username'])
        {
            $config[$alias]['search.username'] = $data['search_username'] ?? null;
        }

        if ($data['search_password'])
        {
            $config[$alias]['search.password'] = $data['search_password'] ?? null;
        }

        return $config;
    }

	/**
	 * Refresh the metadata from selected clients.
     *
     * Back up whatever is already on disk so a fetch/parse failure below can be detected and
     * the previously working file restored, instead of silently leaving the folder empty.
     * MetaLoader's own cache fallback only reuses an entry when its recorded source URL
     * exactly matches the one we're fetching now, so it doesn't help when the source itself
     * (e.g. the metadata URL) was just changed to something invalid - we handle that ourselves.
     *
     * loadSource() swallows fetch/parse failures internally (logs and returns) rather than
     * throwing, and writeMetadataFiles() deletes the output file entirely when nothing could
     * be loaded for it - so the only reliable way to notice a failed refresh is to check
     * whether we still have a file afterwards.
	 *
	 * @param   array  $clientIds    The list of clients to refresh
	 * @param   array  $idpOverrides Optional map of profile alias => explicit IdP entityID override. When set for a
	 *                               client, the generated saml20-idp-remote.php entry is rekeyed to that entityID
	 *                               instead of using whatever entityid the fetched metadata source declares.
	 *
	 * @return  void
	 *
	 * @throws Exception
	 * @since   1.0.0
	 */
	public function metarefresh(array $clientIds, array $idpOverrides = []): void
	{
		$clientIds = ArrayHelper::toInteger($clientIds);
		$db        = $this->getDatabase();
		$query     = $db->createQuery()
			->select($db->quoteName('alias'))
			->from($db->quoteName('#__sso_profiles'))
			->where($db->quoteName('id') . ' IN (' . implode(',', $clientIds) . ')')
			->where($db->quoteName('published') . ' = 1');
		$db->setQuery($query);
		$clients = $db->loadColumn();

		if (!$clients)
		{
			return;
		}

		$outputDir = PATH_SIMPLESAMLPHP . '/metadata-generated';

		foreach ($clients as $client)
		{
			$idpData = $this->loadServiceProviderSettings($client);

			$clientOutputDir  = $outputDir . '/' . $client;
			$remoteIdpFile    = $clientOutputDir . '/saml20-idp-remote.php';
			$existingContent  = is_file($remoteIdpFile) ? file_get_contents($remoteIdpFile) : null;

			// The metadata global variable will be filled with the metadata we extract
			$metaLoader = new MetaLoader;

            if (!$idpData->authorization_saml->idpMetadata && !$idpData->authorization_saml->metadataXml)
            {
                continue;
            }

            $data = $idpData->authorization_saml->metadataXml ?? $idpData->authorization_saml->idpMetadata;

            if (empty($data))
            {
                $data = $idpData->authorization_saml->idpMetadata;
            }

            if (!str_starts_with($data, 'http'))
            {
                $file = $outputDir . '/' . $client . '/metadata.xml';

                try {
                    if (File::write($file, $data))
                    {
                        $data = $file;
                    }
                } catch (\Exception $e) {
                    Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                    continue;
                }
            }

			$source = ['src' => $data];

			$metaLoader->loadSource($source);

            if (isset($file) && !str_starts_with($data, 'http'))
            {
                try {
                    File::delete($file);
                } catch (\Exception $e) {
                    Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                }
            }

			$metaLoader->writeMetadataFiles($clientOutputDir);

			if (!is_file($remoteIdpFile))
			{
				if ($existingContent !== null)
				{
					file_put_contents($remoteIdpFile, $existingContent);
				}

				Factory::getApplication()->enqueueMessage(
					Text::sprintf('COM_SSO_METADATA_REFRESH_FAILED', $client),
					'warning'
				);
			}
			else
			{
				if (!empty($idpOverrides[$client]))
				{
					$this->rekeyRemoteIdpMetadata($remoteIdpFile, $idpOverrides[$client]);
				}

				$this->syncAuthsourceIdp($client, $this->readRemoteIdpEntityId($remoteIdpFile));
			}
		}
	}

	/**
	 * Read the entityID a generated saml20-idp-remote.php file is currently keyed under.
	 *
	 * @param   string  $file  Full path to the generated saml20-idp-remote.php file.
	 *
	 * @return  string  The entityID, or an empty string if it could not be determined.
	 *
	 * @since   2.3.0
	 */
	private function readRemoteIdpEntityId(string $file): string
	{
		require $file;

		/** @var $metadata array */
		return isset($metadata) && is_array($metadata) ? (string) (key($metadata) ?? '') : '';
	}

	/**
	 * Keep authsources.php's 'idp' target in sync with the entityID the generated
	 * saml20-idp-remote.php file is actually keyed under.
	 *
	 * metarefresh() can end up keying the generated file under a different entityID than what
	 * is currently stored in authsources.php - e.g. the remote IdP's metadata declares a new
	 * entityID, or a stale entityID was saved before the remote metadata was first fetched.
	 * Without this, the SP authsource keeps pointing 'idp' at an entityID that no longer exists
	 * in the generated file, and login fails with METADATANOTFOUND.
	 *
	 * @param   string  $alias     The profile alias / authsource name.
	 * @param   string  $entityId  The entityID the generated remote IdP metadata is keyed under.
	 *
	 * @return  void
	 *
	 * @since   2.3.0
	 */
	private function syncAuthsourceIdp(string $alias, string $entityId): void
	{
		if ($entityId === '')
		{
			return;
		}

		$filename = PATH_SIMPLESAMLPHP . '/config/authsources.php';

		require $filename;

		/** @var $config array */
		if (!isset($config[$alias]) || ($config[$alias]['idp'] ?? '') === $entityId)
		{
			return;
		}

		$config[$alias]['idp'] = $entityId;

		file_put_contents($filename, '<?php $config = ' . var_export($config, true) . ';');
	}

	/**
	 * Rekey a generated saml20-idp-remote.php entry to an explicit entityID override.
	 *
	 * The metarefresh module always keys the generated metadata by whatever entityid the fetched
	 * source declares, so a manual IdP entityID override in the profile (used e.g. for ADFS setups
	 * where the login/entityID differs from the metadata source) would otherwise never be reflected
	 * in the generated file, leaving it out of sync with what authsources.php's 'idp' key points at.
	 *
	 * @param   string  $file         Full path to the generated saml20-idp-remote.php file.
	 * @param   string  $idpOverride  The entityID the file should be keyed under.
	 *
	 * @return  void
	 *
	 * @since   2.3.0
	 */
	private function rekeyRemoteIdpMetadata(string $file, string $idpOverride): void
	{
		if (!is_file($file))
		{
			return;
		}

		require $file;

		/** @var $metadata array */
		if (!isset($metadata) || !is_array($metadata) || $metadata === [])
		{
			return;
		}

		$currentKey = key($metadata);

		if ($currentKey === $idpOverride)
		{
			return;
		}

		$entry               = $metadata[$currentKey];
		$entry['entityid']   = $idpOverride;

		$content  = '<?php' . "\n" . '/* This file was generated by the metarefresh module at ';
		$content .= gmdate('Y-m-d\TH:i:s\Z') . "\nDo not update it manually as it will get overwritten\n" . '*/' . "\n";
		$content .= "\n" . '$metadata[' . var_export($idpOverride, true) . '] = ' . var_export($entry, true) . ';' . "\n";

		file_put_contents($file, $content);
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  stdClass  The data for the form.
	 *
	 * @since   1.0.0
	 *
	 * @throws  Exception
	 */
	protected function loadFormData()
	{
		$data = Factory::getApplication()->getUserState('com_sso.edit.profile.data', (new stdClass));

		if (is_array($data))
		{
            $item = new stdClass();

            foreach ($data as $key => $value)
            {
                $item->{$key} = $value;
            }

			$data = $item;
		}

		if (($data->id ?? false) === false)
		{
			$data         = $this->getItem();
			$data->joomla = null;

			if (is_array($data->params) && array_key_exists('joomla', $data->params))
			{
				$data->joomla = $data->params['joomla'];
			}
		}

		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  stdClass
	 *
	 * @since   1.0.0
	 *
	 * @throws  Exception
	 */
	public function getItem($pk = null)
	{
		$item = parent::getItem($pk);
		$data = $this->loadServiceProviderSettings($item->alias ?? '', $item->protocol ?? 'saml');

        foreach ($data as $name => $values)
        {
            $item->{$name} = $values;
        }

		if (($item->protocol ?? 'saml') === 'ldap')
		{
			$item->authorization_ldap->usernameCleanup =  $item->usernameCleanup ?? '';
		}

		$item->fields = json_decode($item->fieldmap ?? '{}', false);

		if ((int) ($item->id ?? 0) === 0)
		{
			$item->fields = json_decode('{"fieldMap":{"fieldMap0":{"fields":{"idpName":"upn","localName":"username"}},"fieldMap1":{"fields":{"idpName":"name","localName":"name"}},"fieldMap2":{"fields":{"idpName":"emailaddress","localName":"email"}}}}', false);
		}

		return $item;
	}
}
