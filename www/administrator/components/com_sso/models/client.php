<?php
/**
 * @package    SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2020 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Object\CMSObject;
use Joomla\Utilities\ArrayHelper;
use SimpleSAML\Module\metarefresh\MetaLoader;

/**
 * Client model.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class SsoModelClient extends AdminModel
{
	/**
	 * Form context
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	private $context = 'com_sso.client';

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
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm($this->context, 'client', array('control' => 'jform', 'load_data' => $loadData));

		if (!is_object($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Refresh the metadata from selected clients.
	 *
	 * @param   array  $clientIds  The list of clients to refresh
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 *
	 * @throws Exception
	 */
	public function metaRefresh(array $clientIds): void
	{
		// Load the clients
		$clientIds = ArrayHelper::toInteger($clientIds);
		$db        = $this->getDbo();
		$query     = $db->getQuery(true)
			->select(
				$db->quoteName(
					[
						'source',
						'validateFingerprint',
						'expireAfter',
						'outputDir',
						'outputFormat',
						'certificates'
					],
					[
						'src',
						'validateFingerprint',
						'expireAfter',
						'outputDir',
						'outputFormat',
						'certificates'
					]
				)
			)
			->from($db->quoteName('#__sso_clients'))
			->where($db->quoteName('id') . ' IN (' . implode(',', $clientIds) . ')')
			->where($db->quoteName('published') . ' = 1');
		$db->setQuery($query);
		$clients = $db->loadAssocList();

		// Process the clients
		if (!$clients)
		{
			return;
		}

		// Set the output dir
		$outputDir = JPATH_LIBRARIES . '/simplesamlphp/metadata-generated/';

		// Update the config
		$config          = new SsoConfig;
		$metadataSources = $config->get('metadata.sources');

		foreach ($clients as $source)
		{
			// Set the expiration
			$expire = $source['expireAfter'] ? time() + (60 * 60 * 24 * $source['expireAfter']) : null;

			// The metadata global variable will be filled with the metadata we extract
			$metaLoader = new MetaLoader($expire);

			// Convert certificates to array
			$source['certificates']        = $source['certificates'] ? json_decode($source['certificates'], false) : null;
			$source['validateFingerprint'] = $source['validateFingerprint'] ?: null;

			// Load the source
			$metaLoader->loadSource($source);

			// Write the file
			if ($source['outputFormat'] === 'serialize')
			{
				$metaLoader->writeMetadataSerialize($outputDir . $source['outputDir'] . '/');
			}
			else
			{
				$metaLoader->writeMetadataFiles($outputDir . $source['outputDir'] . '/');
			}

			// Check if the output directory already exists
			$metadataSources[] = [
				'type'      => $source['outputFormat'],
				'directory' => 'metadata-generated/' . $source['outputDir']
			];
		}

		// Clean up the array
		$metadataSources = ArrayHelper::arrayUnique($metadataSources);

		$config->set('metadata.sources', $metadataSources);
		$config->write();
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
	public function save($data): bool
	{
		// Save the data
		if (!parent::save($data))
		{
			return false;
		}

		// Generate the metarefresh config file
		$this->createMetarefreshConfig();

		return true;
	}

	/**
	 * Method to get the data that should be injected in the form..
	 *
	 * @return  CMSObject  The data for the form.
	 *
	 * @since   1.0.0
	 *
	 * @throws  Exception
	 */
	protected function loadFormData(): CMSObject
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_sso.edit.client.data', new CMSObject);

		if (is_array($data))
		{
			$data = new CMSObject($data);
		}

		if ($data->get('id', false) === false)
		{
			// Check which data we need to load
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Generate the metarefresh config file from the clients.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function createMetarefreshConfig(): void
	{
		// Get all clients
		/** @var SsoModelClients $clientsModel */
		$clientsModel = BaseDatabaseModel::getInstance('Clients', 'SsoModel');
		$clients = $clientsModel->getItems();
		$metaRefresh = new SsoMetarefresh;

		array_walk($clients,
			static function ($client) use (&$metaRefresh)
			{
				$base = 'sets~' . $client->outputDir;
				$metaRefresh->set($base . '~cron', ['hourly']);
				$metaRefresh->set($base . '~sources', [['src' => $client->source]]);
				$metaRefresh->set($base . '~expireAfter', 60 * 60 * 24 * (int) $client->expireAfter);
				$metaRefresh->set($base . '~outputDir', 'metadata-generated/' . $client->outputDir);
				$metaRefresh->set($base . '~outputFormat', $client->outputFormat);
			}
		);

		// Write out the metarefresh file
		$metaRefresh->write();
	}
}
