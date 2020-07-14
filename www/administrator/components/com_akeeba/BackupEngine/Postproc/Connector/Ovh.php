<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Postproc\Connector;


use Akeeba\Engine\Postproc\Connector\Cloudfiles\Exception\Http;
use Akeeba\Engine\Postproc\Connector\Cloudfiles\Exception\Missing\Apikey;
use Akeeba\Engine\Postproc\Connector\Cloudfiles\Exception\Missing\Tenantid;
use Akeeba\Engine\Postproc\Connector\Cloudfiles\Exception\Missing\Username;
use Akeeba\Engine\Postproc\Connector\Cloudfiles\Request;
use DateTime;

class Ovh extends Swift
{
	/**
	 * Ovh constructor.
	 *
	 * @param   string  $tenantId  The OpenStack tenant ID.
	 * @param   string  $username  The OpenStack username.
	 * @param   string  $password  The OpenStack password.
	 *
	 * @throws  Apikey
	 * @throws  Tenantid
	 * @throws  Username
	 * @since   6.1.0
	 *
	 */
	public function __construct($tenantId, $username, $password)
	{
		// OVH is now using Keystone v3
		$authEndpoint = 'https://auth.cloud.ovh.net/v3';

		// Data validation
		if (empty($tenantId))
		{
			throw new TenantId('You have not specified your OVH OpenStack Project ID');
		}

		if (empty($username))
		{
			throw new Username('You have not specified your OVH OpenStack Username');
		}

		if (empty($username))
		{
			throw new Apikey('You have not specified your OVH OpenStack Password');
		}

		parent::__construct($authEndpoint, $tenantId, $username, $password);
	}


	/**
	 * Returns the authentication token.
	 *
	 * Note: OVH uses Keystone 3 which is MUCH different than OpenStack's Keystone v2
	 *
	 * @see     https://docs.ovh.com/gb/en/storage/pca/dev/#authentication
	 *
	 * @return  string
	 * @throws  Http
	 *
	 * @since   7.0.0
	 */
	protected function authenticate()
	{
		// Send the scoped token request to Keystone
		$message = [
			'auth' => [
				'identity' => [
					'methods'  => [
						'password',
					],
					'password' => [
						'user' => [
							'name'     => $this->username,
							'domain'   => [
								'name' => "Default",
							],
							'password' => $this->password,
						],
					],
				],
				'scope' => [
					'project' => [
						'id' => $this->tenantId,
						'domain' => [
							'name' => 'Default'
						]
					]
				]
			],
		];
		$json    = json_encode($message);
		$url     = rtrim($this->authEndpoint, '/') . '/auth/tokens';

		$request       = new Request('POST', $url);
		$request->data = $json;
		$request->setHeader('Accept', 'application/json');
		$request->setHeader('Content-Type', 'application/json');
		$request->setHeader('Content-Length', strlen($request->data));

		$response = $request->getResponse();

		// Get the tenant (project) ID
		$this->tenantId = $response->body->token->project->id;

		// Get the token and its expiration
		$this->token = $response->headers['X-Subject-Token'];
		$date                  = new DateTime($response->body->token->expires_at);
		$this->tokenExpiration = $date->getTimestamp();

		// Loop through the serviceCatalog and index the Swift endpoints
		if (isset($response->body->token->catalog))
		{
			foreach ($response->body->token->catalog as $service)
			{
				if ($service->type != 'object-store')
				{
					continue;
				}

				if (!isset($service->endpoints))
				{
					continue;
				}

				foreach ($service->endpoints as $endpoint)
				{
					$this->endPoints[$endpoint->region_id] = $endpoint->url;
				}
			}
		}

		// Callback
		if (is_callable($this->authenticationCallback))
		{
			call_user_func_array($this->authenticationCallback, [&$this, $response]);
		}

		return $this->token;
	}
}
