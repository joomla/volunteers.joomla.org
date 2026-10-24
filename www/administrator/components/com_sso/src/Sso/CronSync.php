<?php
/**
 * @package     SSO.Component
 *
 * @author      RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright   Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://rolandd.com
 */

namespace Rolandd\Component\Sso\Administrator\Sso;

use Joomla\Database\DatabaseInterface;
use stdClass;

defined('_JEXEC') or die;

/**
 * Keeps the cron-driven SimpleSAMLphp config files in sync with the clients and profiles
 * tables together.
 *
 * A single site can be an Identity Provider (with trusted Service Provider Clients) and a
 * Service Provider (with trusted Identity Provider Profiles) at the same time, and both sides
 * share the same module_metarefresh.php 'sets' tree and config.php 'metadata.sources' array.
 * Rebuilding either file from only one side's data would silently wipe out the other side's
 * entries, so this always recomputes both files from both tables together - called after any
 * save or delete on either side.
 *
 * @package  SSO.Component
 * @since    2.4.0
 */
class CronSync
{
	/**
	 * Constructor.
	 *
	 * @param   DatabaseInterface  $db  The database driver.
	 *
	 * @since   2.4.0
	 */
	public function __construct(private DatabaseInterface $db)
	{
	}

	/**
	 * Rebuild module_metarefresh.php, config.php's metadata.sources, and module_cron.php.
	 *
	 * @return  void
	 *
	 * @since   2.4.0
	 */
	public function sync(): void
	{
		$clients  = $this->getClients();
		$profiles = $this->getProfiles();

		$this->syncMetarefreshSets($clients, $profiles);
		$this->syncMetadataSources($clients, $profiles);
		$this->syncModuleCron();
	}

	/**
	 * Get the outputDir/outputFormat/source/expireAfter of every published client.
	 *
	 * Unpublished clients are excluded, so unpublishing one stops it being scheduled for
	 * cron refresh and trusted via metadata.sources, without deleting anything - publishing
	 * it again just needs another sync to bring it straight back.
	 *
	 * @return  array
	 *
	 * @since   2.4.0
	 */
	private function getClients(): array
	{
		$query = $this->db->createQuery()
			->select($this->db->quoteName(['outputDir', 'outputFormat', 'source', 'expireAfter']))
			->from($this->db->quoteName('#__sso_clients'))
			->where($this->db->quoteName('published') . ' = 1');
		$this->db->setQuery($query);

		return $this->db->loadObjectList();
	}

	/**
	 * Get the alias/metadata/metadataXml of every published SAML profile.
	 *
	 * LDAP profiles have no metadata to refresh, so they are excluded here. Unpublished
	 * profiles are excluded too, for the same reason unpublished clients are - see getClients().
	 *
	 * @return  array
	 *
	 * @since   2.4.0
	 */
	private function getProfiles(): array
	{
		$query = $this->db->createQuery()
			->select($this->db->quoteName(['alias', 'metadata', 'metadataXml']))
			->from($this->db->quoteName('#__sso_profiles'))
			->where($this->db->quoteName('protocol') . ' = ' . $this->db->quote('saml'))
			->where($this->db->quoteName('published') . ' = 1');
		$this->db->setQuery($query);

		return $this->db->loadObjectList();
	}

	/**
	 * Rebuild the metarefresh module's 'sets' tree from scratch.
	 *
	 * @param   array  $clients   Clients, as returned by getClients().
	 * @param   array  $profiles  Profiles, as returned by getProfiles().
	 *
	 * @return  void
	 *
	 * @since   2.4.0
	 */
	private function syncMetarefreshSets(array $clients, array $profiles): void
	{
		$metaRefresh = new Metarefresh;
		$metaRefresh->remove('sets');

		foreach ($clients as $client)
		{
			$base = 'sets~' . $client->outputDir;
			$metaRefresh->set($base . '~cron', ['hourly']);
			$metaRefresh->set($base . '~sources', [['src' => $client->source]]);
			$metaRefresh->set($base . '~expireAfter', 60 * 60 * 24 * (int) $client->expireAfter);
			$metaRefresh->set($base . '~outputDir', 'metadata-generated/' . $client->outputDir);
			$metaRefresh->set($base . '~outputFormat', $client->outputFormat);
		}

		foreach ($profiles as $profile)
		{
			$source = $this->resolveProfileSource($profile);

			if ($source === null)
			{
				continue;
			}

			$base = 'sets~' . $profile->alias;
			$metaRefresh->set($base . '~cron', ['hourly']);
			$metaRefresh->set($base . '~sources', [['src' => $source]]);
			$metaRefresh->set($base . '~expireAfter', 60 * 60 * 24 * 7);
			$metaRefresh->set($base . '~outputDir', 'metadata-generated/' . $profile->alias);
			$metaRefresh->set($base . '~outputFormat', 'flatfile');
		}

		$metaRefresh->write();
	}

	/**
	 * Rebuild config.php's metadata.sources from scratch.
	 *
	 * The first existing entry is always preserved as-is - that is SimpleSAMLphp's own "read
	 * from the local metadata/ folder" source, not something this component manages.
	 *
	 * @param   array  $clients   Clients, as returned by getClients().
	 * @param   array  $profiles  Profiles, as returned by getProfiles().
	 *
	 * @return  void
	 *
	 * @since   2.4.0
	 */
	private function syncMetadataSources(array $clients, array $profiles): void
	{
		$config          = new Config;
		$existingSources = (array) $config->get('metadata.sources', [['type' => 'flatfile']]);
		$metadataSources = [$existingSources[0] ?? ['type' => 'flatfile']];

		foreach ($clients as $client)
		{
			$metadataSources[] = [
				'type'      => $client->outputFormat,
				'directory' => 'metadata-generated/' . $client->outputDir,
			];
		}

		foreach ($profiles as $profile)
		{
			$metadataSources[] = [
				'type'      => 'flatfile',
				'directory' => 'metadata-generated/' . $profile->alias,
			];
		}

		$config->set('metadata.sources', $metadataSources);
		$config->write();
	}

	/**
	 * Keep the cron module configuration in sync.
	 *
	 * The 'hourly' tag is what the metarefresh sets above are scheduled under, so it must
	 * always be allowed. debug_message follows the site's own "Enable Debug" setting rather
	 * than having its own toggle. sendemail is intentionally left untouched here - it is only
	 * managed from Options (view=config), so an admin's choice there is never overwritten by
	 * saving a client or profile.
	 *
	 * @return  void
	 *
	 * @since   2.4.0
	 */
	private function syncModuleCron(): void
	{
		$moduleCron = new ModuleCron;
		$moduleCron->set('allowed_tags', ['hourly', 'daily', 'frequent']);
		$moduleCron->set('debug_message', (bool) (new Config)->get('showerrors', false));
		$moduleCron->write();
	}

	/**
	 * Work out what the metarefresh cron should fetch a profile's IdP metadata from.
	 *
	 * Only a live Metadata URL is schedulable. A pasted-in Metadata XML blob takes priority
	 * over the URL in ProfileModel::metarefresh() (an explicit pin, not something that should
	 * silently be superseded by periodically re-fetching the URL instead), and even if it
	 * weren't, ProfileModel::metarefresh() writes it to a scratch metadata.xml file only to
	 * feed the synchronous MetaLoader call and deletes that file again immediately after - so
	 * there is no stable path here for the cron module to point at later.
	 *
	 * @param   stdClass  $profile  A profile, as returned by getProfiles().
	 *
	 * @return  string|null  The URL to fetch from, or null if the profile has none configured.
	 *
	 * @since   2.4.0
	 */
	private function resolveProfileSource(stdClass $profile): ?string
	{
		if (($profile->metadataXml ?? '') !== '')
		{
			return null;
		}

		$metadata = $profile->metadata ?? '';

		return str_starts_with($metadata, 'http') ? $metadata : null;
	}
}
