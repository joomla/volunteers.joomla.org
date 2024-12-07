<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Postproc\Connector;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Postproc\Connector\Cloudfiles\Exception\Missing\Apikey as MissingApikey;
use Akeeba\Engine\Postproc\Connector\Cloudfiles\Exception\Missing\Username as MissingUsername;
use Akeeba\Engine\Postproc\Connector\Cloudfiles\Request;
use DateTime;

/**
 * Self-contained implementation of the RackSpace CloudFiles in PHP
 */
class Cloudfiles extends Swift
{
	/** @var string The user contract (MossoCloudFS_aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee) returned by CloudFiles */
	protected $userContract = '';

	/** @var string The authentication endpoint. This is a universal endpoint for all accounts now. */
	protected $authEndpoint = 'https://identity.api.rackspacecloud.com/v2.0';

	/** @var array List of storage endpoints per region */
	protected $storageEndpoints = [
		'ORD' => 'https://storage101.ord1.clouddrive.com',
		'DFW' => 'https://storage101.dfw1.clouddrive.com',
		'HKG' => 'https://storage101.hkg1.clouddrive.com',
		'LON' => 'https://storage101.lon3.clouddrive.com',
		'IAD' => 'https://storage101.iad3.clouddrive.com',
		'SYD' => 'https://storage101.syd2.clouddrive.com',
	];

	/** @var string The region of the account. It is kindly reported by the Swift API, no need to set it. */
	protected $region = 'LON';

	/** @var string The storage API version to use */
	protected $apiVersion = 'v1';

	protected $container = '';

	/**
	 * Public constructor
	 *
	 * @param   string  $username  The CloudFiles username
	 * @param   string  $apiKey    The CloudFiles API key
	 * @param   array   $options   Configuration options (authEndpoint, storageEndpoint, apiVersion, userContract, tenantId, tokenExpiration, token)
	 *
	 * @throws MissingUsername  You have not given me a username
	 * @throws MissingApikey    You have not given me an API key
	 */
	public function __construct($username, $apiKey, $options = [])
	{
		// Data validation
		if (empty($username))
		{
			throw new MissingUsername('You have not specified your CloudFiles username');
		}

		if (empty($username))
		{
			throw new MissingApikey('You have not specified your CloudFiles API key');
		}

		parent::__construct('v2', 'https://identity.api.rackspacecloud.com/v2.0', '', $username, $apiKey, 'default');

		// Very simplistic options parsing
		if (is_array($options) && count($options))
		{
			foreach ($options as $key => $value)
			{
				if (in_array($key, ['username', 'password', 'apiKey']))
				{
					continue;
				}

				if (isset($this->$key))
				{
					$this->$key = $value;
				}
			}
		}
	}

	/**
	 * Return the current options, useful to instantiate a new object without having to re-authenticate to CloudFiles
	 *
	 * @return array
	 */
	public function getCurrentOptions()
	{
		return [
			'token'           => $this->token,
			'tokenExpiration' => $this->tokenExpiration,
			'tenantId'        => $this->tenantId,
			'container'       => $this->container,
			'userContract'    => $this->userContract,
			'authEndpoint'    => $this->authEndpoint,
			'region'          => $this->region,
			'storageEndpoint' => $this->storageEndpoint,
			'apiVersion'      => $this->apiVersion,
		];
	}

	/**
	 * Authenticate the user and obtain a new token. If there is a token and it's not expired yet we will reuse it.
	 *
	 * @param   bool  $force  Force authentication?
	 */
	public function authenticate($force = false)
	{
		// Should I proceed?
		if (!$force)
		{
			if (!empty($this->token) && !empty($this->tokenExpiration))
			{
				if ($this->tokenExpiration > (time() + 3600))
				{
					// I have a token and its expiration time is more than one hour into the future. No need to re-auth.
					return;
				}
			}
		}

		$request = new Request('POST', $this->authEndpoint . '/tokens');

		$dataRaw = (object) [
			'auth' => [
				"RAX-KSKEY:apiKeyCredentials" => [
					'username' => $this->username,
					'apiKey'   => $this->password,
				],
			],
		];

		$dataForPost   = json_encode($dataRaw);
		$request->data = $dataForPost;
		$request->setHeader('Accept', 'application/json');
		$request->setHeader('Content-Type', 'application/json');
		$request->setHeader('Content-Length', strlen($request->data));

		$response = $request->getResponse();

		$this->token    = $response->body->access->token->id;
		$this->tenantId = $response->body->access->token->tenant->id;

		$date                  = new DateTime($response->body->access->token->expires);
		$this->tokenExpiration = $date->getTimestamp();

		$raxAuthRegionKey = 'RAX-AUTH:defaultRegion';
		$defaultRegion    = $response->body->access->user->$raxAuthRegionKey;

		$this->region = strtoupper($defaultRegion);

		$needsEndpoint = false;

		if (empty($this->storageEndpoint))
		{
			$this->storageEndpoint = $this->storageEndpoints[$this->region];

			$needsEndpoint = true;
		}

		foreach ($response->body->access->serviceCatalog as $service)
		{
			if ($service->name != 'cloudFiles')
			{
				continue;
			}

			foreach ($service->endpoints as $endpoint)
			{
				if ($endpoint->region != $defaultRegion)
				{
					continue;
				}

				$this->userContract = $endpoint->tenantId;
				break;
			}
		}

		// Finally, set up the storage endpoint in the way the API class expects it
		if ($needsEndpoint)
		{
			$this->storageEndpoint .= '/' . $this->apiVersion . '/' . $this->userContract . '/' . $this->container;
		}
	}
}
