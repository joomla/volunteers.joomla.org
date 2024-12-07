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

use Akeeba\Engine\Postproc\Connector\Cloudfiles\Exception\Http;
use Akeeba\Engine\Postproc\Connector\Cloudfiles\Request;
use Akeeba\Engine\Util\FileCloseAware;
use DateTime;
use stdClass;

/**
 * Generic OpenStack SWIFT object storage API implementation
 */
class Swift
{
	use FileCloseAware;

	/**
	 * The Keystone version to use for authenticating. One of v2, v3.
	 *
	 * @var    string
	 * @since  9.3.0
	 */
	protected $keystoneVersion = 'v2';

	/**
	 * The authentication server (Keystone) endpoint, e.g. https://auth.cloud.ovh.net/v2.0
	 *
	 * @var    string
	 * @since  6.1.0
	 */
	protected $authEndpoint = '';

	/**
	 * The endpoint for accessing the storage container, e.g. https://storage.de1.cloud.ovh.net/v1/AUTH_abcdef0123456789abcdef0123456789/my-container
	 *
	 * @var    string
	 * @since  6.1.0
	 */
	protected $storageEndpoint = '';

	/**
	 * The token retrieved from the authentication service (Keystone)
	 *
	 * @var    string
	 * @since  6.1.0
	 */
	protected $token;

	/**
	 * The expiration timestamp of the token
	 *
	 * @var    int
	 * @since  6.1.0
	 */
	protected $tokenExpiration;

	/**
	 * The tenant ID of the OpenStack cloud
	 *
	 * @var    string
	 * @since  6.1.0
	 */
	protected $tenantId;

	/**
	 * The Keystone v3 authentication domain
	 *
	 * @var    string
	 * @since  9.3.0
	 */
	protected $domain;

	/**
	 * The OpenStack username
	 *
	 * @var    string
	 * @since  6.1.0
	 */
	protected $username;

	/**
	 * The OpenStack password
	 *
	 * @var    string
	 * @since  6.1.0
	 */
	protected $password;

	/**
	 * The endpoints of the SWIFT service, as returned by the Keystone service, indexed by region. Each endpoint is
	 * raw object.
	 *
	 * @var    stdClass[]
	 * @since  6.1.0
	 */
	protected $endPoints = [];

	/**
	 * A callable which is passed the authentication information and result to cater for non-standard SWIFT
	 * implementations.
	 *
	 * @var    callable
	 * @since  6.1.0
	 */
	protected $authenticationCallback;

	/**
	 * Swift constructor.
	 *
	 * @param   string  $authEndpoint  The authentication endpoint URL.
	 * @param   string  $tenantId      The OpenStack tenant ID.
	 * @param   string  $username      The OpenStack username.
	 * @param   string  $password      The OpenStack password.
	 *
	 *
	 * @since   6.1.0
	 */
	public function __construct($keystoneVersion, $authEndpoint, $tenantId, $username, $password, $keystoneDomain = 'default')
	{
		$this->keystoneVersion = $keystoneVersion;
		$this->authEndpoint    = $authEndpoint;
		$this->tenantId        = $tenantId;
		$this->username        = $username;
		$this->password        = $password;
		$this->domain          = $keystoneDomain;
	}

	/**
	 * Get the authentication endpoint URL
	 *
	 * @return  string
	 * @since   6.1.0
	 */
	public function getAuthEndpoint()
	{
		return $this->authEndpoint;
	}

	/**
	 * Set the authentication endpoint
	 *
	 * @param   string  $authEndpoint  The new authentication endpoint URL
	 *
	 * @return  Swift
	 * @since   6.1.0
	 */
	public function setAuthEndpoint($authEndpoint)
	{
		$this->authEndpoint = $authEndpoint;

		return $this;
	}

	/**
	 * Get the storage container's endpoint URL
	 *
	 * @return  string
	 * @since   6.1.0
	 */
	public function getStorageEndpoint()
	{
		return $this->storageEndpoint;
	}

	/**
	 * Set the storage container's endpoint URL
	 *
	 * @param   string  $storageEndpoint  The storage container's endpoint URL
	 *
	 * @return  Swift
	 * @since   6.1.0
	 */
	public function setStorageEndpoint($storageEndpoint)
	{
		$this->storageEndpoint = $storageEndpoint;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getTenantId()
	{
		return $this->tenantId;
	}

	/**
	 * @param   string  $tenantId
	 *
	 * @return Swift
	 */
	public function setTenantId($tenantId)
	{
		$this->tenantId = $tenantId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getUsername()
	{
		return $this->username;
	}

	/**
	 * @param   string  $username
	 *
	 * @return Swift
	 */
	public function setUsername($username)
	{
		$this->username = $username;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getPassword()
	{
		return $this->password;
	}

	/**
	 * @param   string  $password
	 *
	 * @return Swift
	 */
	public function setPassword($password)
	{
		$this->password = $password;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getTokenExpiration()
	{
		return $this->tokenExpiration;
	}

	/**
	 * @return stdClass[]
	 */
	public function getEndPoints()
	{
		return $this->endPoints;
	}

	/**
	 * Get the token. If the token is expired or missing, or if the force parameter is set, we will re-authenticate
	 * against the OpenStack cloud.
	 *
	 * @param   bool  $force  Tru to force re-authentication
	 *
	 * @return  string
	 * @throws  Http
	 * @since   6.1.0
	 *
	 */
	public function getToken($force = false)
	{
		// Am I forced to get a fresh token?
		if ($force)
		{
			$this->authenticate();
		}

		// If I don't have a token I must authenticate
		if (empty($this->token))
		{
			$this->authenticate();
		}

		// The expiration time of the token is less than an hour away. Let's re-authenticate.
		if ($this->tokenExpiration < (time() + 3600))
		{
			$this->authenticate();
		}

		return $this->token;
	}

	/**
	 * Lists the containers in the SWIFT account
	 *
	 * @param   bool    $assoc          Should I return an associative array, where the key is the container name? (def: no)
	 * @param   string  $lastContainer  Start listing AFTER this last container (pagination)
	 * @param   int     $limit          How many containers to list
	 *
	 * @return  stdClass[]  Array or objects. Internal objects have keys count, bytes, name
	 *
	 * @throws Http
	 * @since  6.1.0
	 */
	public function listContainers($assoc = false, $lastContainer = null, $limit = 10000)
	{
		// Re-authenticate if necessary
		$token = $this->getToken();

		// Get the URL to list containers. It's the container endpoint minus the actual container name.
		$url       = $this->getStorageEndpoint();
		$lastSlash = strrpos($url, '/');
		$url       = substr($url, 0, $lastSlash);

		// Get the request object
		$request = new Request('GET', $url);
		$request->setHeader('X-Auth-Token', $token);
		$request->setHeader('Accept', 'application/json');
		$request->setParameter('format', 'json');

		if (!empty($lastContainer))
		{
			$request->setParameter('marker', $lastContainer);
		}

		if (!is_numeric($limit))
		{
			$limit = 10000;
		}

		if ($limit <= 0)
		{
			$limit = 10000;
		}

		$request->setParameter('limit', $limit);

		$response = $request->getResponse();

		if (!$assoc)
		{
			return $response->body;
		}

		$ret = [];

		if (!empty($response->body))
		{
			foreach ($response->body as $container)
			{
				$ret[$container->name] = $container;
			}
		}

		return $ret;
	}

	/**
	 * Lists the contents of a directory inside the container
	 *
	 * @param   string  $path       The path to the directory you want to list, '' for the root.
	 * @param   bool    $assoc      Should I return an associative array with filenames as keys?
	 * @param   null    $lastEntry  The entry AFTER which to start listing
	 * @param   int     $limit      How many files to show (1000 by default)
	 * @param   string  $prefix     The common prefix of files to list
	 *
	 * @return  array   Array of objects. Object keys: hash, last_modified, bytes, name, content_type
	 * @throws  Http
	 * @since   6.1.0
	 *
	 */
	public function listContents($path = '', $assoc = false, $lastEntry = null, $limit = 1000, $prefix = '')
	{
		// Re-authenticate if necessary
		$token = $this->getToken();

		// Get the URL to list containers
		$url  = $this->getStorageEndpoint();
		$url  = rtrim($url, '\\/');
		$path = ltrim($path, '\\/');
		$url  .= '/' . $path;

		// Get the request object
		$request = new Request('GET', $url);
		$request->setHeader('X-Auth-Token', $token);
		$request->setHeader('Accept', 'application/json');
		$request->setParameter('format', 'json');

		if (!empty($lastEntry))
		{
			$request->setParameter('marker', $lastEntry);
		}

		if (!empty($prefix))
		{
			$request->setParameter('prefix', $prefix);
		}

		if (!is_numeric($limit))
		{
			$limit = 1000;
		}

		if ($limit <= 0)
		{
			$limit = 1000;
		}

		$request->setParameter('limit', $limit);
		$request->setParameter('delimiter', '/');

		$response = $request->getResponse();

		if (!$assoc)
		{
			return $response->body;
		}

		$ret = [];

		if (!empty($response->body))
		{
			foreach ($response->body as $file)
			{
				$ret[$file->name] = $file;
			}
		}

		return $ret;
	}

	/**
	 * Uploads a file. The $input array can have one of the following formats:
	 *
	 * 1. A string with the contents of the file to be put to CloudFiles
	 *
	 * 2. An array('fp' => $fp) containing a file pointer, open in read binary mode, to the file to upload
	 *
	 * 3. An array('file' => $pathToFile) containing the path to the file to upload
	 *
	 * 4. An array('data' => $rawData) which is the same as passing a string (case 1)
	 *
	 * When using an array you can also pass the following optional parameters in the array:
	 * size        The size of the uploaded content in bytes
	 *
	 * @param   string|array  $input        See the method description
	 * @param   string        $path         The path inside the container of the uploaded file
	 * @param   string        $contentType  The content type of the uploaded file
	 *
	 * @throws  Http
	 * @since   6.1.0
	 *
	 */
	public function putObject($input, $path, $contentType = null)
	{
		// Re-authenticate if necessary
		$token = $this->getToken();

		// Get the URL to list containers
		$url  = $this->getStorageEndpoint();
		$url  = rtrim($url, '\\/');
		$path = ltrim($path, '\\/');
		$url  .= '/' . $path;

		// Get the request object
		$request = new Request('PUT', $url);
		$request->setHeader('X-Auth-Token', $token);

		// Decide what to do based on the $input format
		if (is_string($input))
		{
			$input = [
				'data' => $input,
				'size' => strlen($input),
			];
		}

		// Data
		if (isset($input['fp']))
		{
			$request->fp = $input['fp'];
		}
		elseif (isset($input['file']))
		{
			$request->fp = @fopen($input['file'], 'r');
		}
		elseif (isset($input['data']))
		{
			$request->data = $input['data'];
		}

		// Content-Length (required)
		if (isset($input['size']) && $input['size'] >= 0)
		{
			$request->size = $input['size'];
		}
		else
		{
			if (isset($input['file']))
			{
				clearstatcache(false, $input['file']);
				$request->size = @filesize($input['file']);
			}
			elseif (isset($input['data']))
			{
				$request->size = strlen($input['data']);
			}
		}

		if (empty($contentType))
		{
			$contentType = 'application/octet-stream';
		}

		$request->setParameter('Content-Type', $contentType);
		$request->setParameter('Content-Length', $request->size);

		$request->getResponse();

		if (isset($input['file']))
		{
			$this->conditionalFileClose($request->fp);
		}
	}

	/**
	 * Downloads a file from CloudFiles back to your server
	 *
	 * @param   string    $path     The path to the file to download
	 * @param   resource  $fp       A file pointer, opened in write binary mode, to write out the downloaded file
	 * @param   array     $headers  An array of headers to send during the download, e.g. ['Range' => '1-100']
	 *
	 * @return  void
	 *
	 * @throws  Http
	 * @since   6.1.0
	 *
	 */
	public function downloadObject($path, &$fp, $headers = [])
	{
		// Re-authenticate if necessary
		$token = $this->getToken();

		// Get the URL to list containers
		$url  = $this->getStorageEndpoint();
		$url  = rtrim($url, '\\/');
		$path = ltrim($path, '\\/');
		$url  .= '/' . $path;

		// Get the request object
		$request = new Request('GET', $url);
		$request->setHeader('X-Auth-Token', $token);

		if (!empty($headers))
		{
			foreach ($headers as $k => $v)
			{
				$request->setHeader($k, $v);
			}
		}

		$request->fp = $fp;

		$request->getResponse();
	}

	/**
	 * Delete a file from CloudFiles
	 *
	 * @param   string  $path  The path to the file to download
	 *
	 * @return  void
	 *
	 * @throws  Http
	 * @since   6.1.0
	 *
	 */
	public function deleteObject($path)
	{
		// Re-authenticate if necessary
		$token = $this->getToken();

		// Get the URL to list containers
		$url  = $this->getStorageEndpoint();
		$url  = rtrim($url, '\\/');
		$path = ltrim($path, '\\/');
		$url  .= '/' . $path;

		// Get the request object
		$request = new Request('DELETE', $url);
		$request->setHeader('X-Auth-Token', $token);

		$request->getResponse();
	}

	/**
	 * Authenticate to the OpenStack cloud and retrieve a fresh token
	 *
	 * @return  string
	 * @throws  Http
	 *
	 * @since   6.1.0
	 */
	protected function authenticate()
	{
		switch ($this->keystoneVersion)
		{
			case 'v2':
				return $this->authenticateV2();
				break;

			case 'v3':
			default:
				return $this->authenticateV3();
				break;
		}
	}

	/**
	 * Authenticate to the OpenStack cloud using Keystone v2 and retrieve a fresh token
	 *
	 * @return  string
	 * @throws  Http
	 *
	 * @since   6.1.0
	 */
	protected function authenticateV2()
	{
		// Send the token request to Keystone
		$message = [
			'auth' => [
				'tenantId'            => $this->tenantId,
				'passwordCredentials' => [
					'username' => $this->username,
					'password' => $this->password,
				],
			],
		];
		$json    = json_encode($message);
		$url     = rtrim($this->authEndpoint, '/') . '/tokens';

		$request       = new Request('POST', $url);
		$request->data = $json;
		$request->setHeader('Accept', 'application/json');
		$request->setHeader('Content-Type', 'application/json');
		$request->setHeader('Content-Length', strlen($request->data));

		$response = $request->getResponse();

		// Get the tenant ID
		$this->tenantId = $response->body->access->token->tenant->id;

		// Get the token and its expiration
		$this->token           = $response->body->access->token->id;
		$date                  = new DateTime($response->body->access->token->expires);
		$this->tokenExpiration = $date->getTimestamp();

		// Loop through the serviceCatalog and index the Swift endpoints
		if (isset($request->body) && isset($request->body->serviceCatalog))
		{
			foreach ($request->body->serviceCatalog as $service)
			{
				if ($service->name != 'swift')
				{
					continue;
				}

				if (!isset($service->endpoints))
				{
					continue;
				}

				foreach ($service->endpoints as $endpoint)
				{
					$this->endPoints[$endpoint->region] = $endpoint->publicURL;
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

	/**
	 * Returns the authentication token using Keystone v3 authentication.
	 *
	 * @return  string
	 * @throws  Http
	 *
	 * @since   9.3.0
	 */
	protected function authenticateV3()
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
								'name' => $this->domain,
							],
							'password' => $this->password,
						],
					],
				],
				'scope'    => [
					'project' => [
						'id'     => $this->tenantId,
						'domain' => [
							'name' => $this->domain,
						],
					],
				],
			],
		];
		$json    = json_encode($message);
		$url     = rtrim($this->authEndpoint, '/') . '/v3/auth/tokens';

		$request       = new Request('POST', $url);
		$request->data = $json;
		$request->setHeader('Accept', 'application/json');
		$request->setHeader('Content-Type', 'application/json');
		$request->setHeader('Content-Length', strlen($request->data));

		$response = $request->getResponse();

		// Get the tenant (project) ID
		$this->tenantId = $response->body->token->project->id;

		// Get the token and its expiration
		$this->token           = $response->headers['x-subject-token'];
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
