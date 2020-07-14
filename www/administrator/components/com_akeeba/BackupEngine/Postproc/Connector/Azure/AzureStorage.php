<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Copyright (c) 2009, RealDolmen
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of RealDolmen nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY RealDolmen ''AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL RealDolmen BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   Microsoft
 * @package    Microsoft
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 */

namespace Akeeba\Engine\Postproc\Connector\Azure;



use Akeeba\Engine\Postproc\Connector\Azure\Credentials\Sharedkey;
use Akeeba\Engine\Postproc\Connector\Azure\Exception\Api;
use Akeeba\Engine\Postproc\Connector\Azure\Http\Response;
use Akeeba\Engine\Postproc\Connector\Azure\Http\Transport;
use Akeeba\Engine\Postproc\Connector\S3v4\Input;

/**
 * @category   Microsoft
 * @package    Microsoft_WindowsAzure
 * @subpackage Storage
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 */
class AzureStorage
{
	/**
	 * Development storage URLS
	 */
	const URL_DEV_BLOB = "127.0.0.1:10000";

	const URL_DEV_QUEUE = "127.0.0.1:10001";

	const URL_DEV_TABLE = "127.0.0.1:10002";

	/**
	 * Live storage URLS
	 */
	const URL_CLOUD_BLOB = "blob.core.windows.net";

	const URL_CLOUD_QUEUE = "queue.core.windows.net";

	const URL_CLOUD_TABLE = "table.core.windows.net";

	/**
	 * Resource types
	 */
	const RESOURCE_UNKNOWN = "unknown";

	const RESOURCE_CONTAINER = "c";

	const RESOURCE_BLOB = "b";

	const RESOURCE_TABLE = "t";

	const RESOURCE_ENTITY = "e";

	const RESOURCE_QUEUE = "q";

	/**
	 * Current API version
	 *
	 * @var string
	 */
	protected $_apiVersion = '2015-04-05';

	/**
	 * Storage host name
	 *
	 * @var string
	 */
	protected $_host = '';

	/**
	 * Account name for Windows Azure
	 *
	 * @var string
	 */
	protected $_accountName = '';

	/**
	 * Account key for Windows Azure
	 *
	 * @var string
	 */
	protected $_accountKey = '';

	/**
	 * Use path-style URI's
	 *
	 * @var boolean
	 */
	protected $_usePathStyleUri = false;

	/**
	 * Credentials instance
	 *
	 * @var Credentials
	 */
	protected $_credentials = null;

	/**
	 * Retrypolicy instance
	 *
	 * @var Retrypolicy
	 */
	protected $_retryPolicy = null;

	/**
	 * Use proxy?
	 *
	 * @var boolean
	 */
	protected $_useProxy = false;

	/**
	 * Proxy url
	 *
	 * @var string
	 */
	protected $_proxyUrl = '';

	/**
	 * Proxy port
	 *
	 * @var int
	 */
	protected $_proxyPort = 80;

	/**
	 * Proxy credentials
	 *
	 * @var string
	 */
	protected $_proxyCredentials = '';

	/**
	 * Should I use SSL (HTTPS) to communicate to Windows Azure?
	 *
	 * @var  bool
	 */
	protected $useSSL = true;

	/**
	 * Creates a new AzureStorage instance
	 *
	 * @param   string       $host             Storage host name
	 * @param   string       $accountName      Account name for Windows Azure
	 * @param   string       $accountKey       Account key for Windows Azure
	 * @param   boolean      $usePathStyleUri  Use path-style URI's
	 * @param   Retrypolicy  $retryPolicy      Retry policy to use when making requests
	 */
	public function __construct($host = self::URL_DEV_BLOB, $accountName = Credentials::DEVSTORE_ACCOUNT, $accountKey = Credentials::DEVSTORE_KEY, $usePathStyleUri = false, Retrypolicy $retryPolicy = null)
	{
		$this->_host            = $host;
		$this->_accountName     = $accountName;
		$this->_accountKey      = $accountKey;
		$this->_usePathStyleUri = $usePathStyleUri;

		// Using local storage?
		if (!$this->_usePathStyleUri && ($this->_host == self::URL_DEV_BLOB || $this->_host == self::URL_DEV_QUEUE || $this->_host == self::URL_DEV_TABLE)) // Local storage
		{
			$this->_usePathStyleUri = true;
		}

		if (is_null($this->_credentials))
		{
			$this->_credentials = new Sharedkey($this->_accountName, $this->_accountKey, $this->_usePathStyleUri);
		}

		$this->_retryPolicy = $retryPolicy;
		if (is_null($this->_retryPolicy))
		{
			$this->_retryPolicy = Retrypolicy::noRetry();
		}
	}

	/**
	 * Builds a query string from an array of elements
	 *
	 * @param   array     Array of elements
	 *
	 * @return string   Assembled query string
	 */
	public static function createQueryStringFromArray($queryString)
	{
		return count($queryString) > 0 ? '?' . implode('&', $queryString) : '';
	}

	/**
	 * URL encode function
	 *
	 * @param   string  $value  Value to encode
	 *
	 * @return string        Encoded value
	 */
	public static function urlencode($value)
	{
		return str_replace(' ', '%20', $value);
	}

	/**
	 * Set retry policy to use when making requests
	 *
	 * @param   Retrypolicy  $retryPolicy  Retry policy to use when making requests
	 */
	public function setRetryPolicy(Retrypolicy $retryPolicy = null)
	{
		$this->_retryPolicy = $retryPolicy;
		if (is_null($this->_retryPolicy))
		{
			$this->_retryPolicy = Retrypolicy::noRetry();
		}
	}

	/**
	 * Set proxy
	 *
	 * @param   boolean  $useProxy          Use proxy?
	 * @param   string   $proxyUrl          Proxy URL
	 * @param   int      $proxyPort         Proxy port
	 * @param   string   $proxyCredentials  Proxy credentials
	 */
	public function setProxy($useProxy = false, $proxyUrl = '', $proxyPort = 80, $proxyCredentials = '')
	{
		$this->_useProxy         = $useProxy;
		$this->_proxyUrl         = $proxyUrl;
		$this->_proxyPort        = $proxyPort;
		$this->_proxyCredentials = $proxyCredentials;
	}

	/**
	 * Returns the Windows Azure account name
	 *
	 * @return string
	 */
	public function getAccountName()
	{
		return $this->_accountName;
	}

	/**
	 * Set the connection SSL preference
	 *
	 * @param   boolean  $useSSL  True to use HTTPS
	 */
	public function setSSL($useSSL)
	{
		$this->useSSL = $useSSL ? true : false;
	}

	public function isSSL()
	{
		return $this->useSSL;
	}

	/**
	 * Get base URL for creating requests
	 *
	 * @return string
	 */
	public function getBaseUrl()
	{
		$schema = 'http://';

		if ($this->isSSL())
		{
			$schema = 'https://';
		}

		if ($this->_usePathStyleUri)
		{
			return $schema . $this->_host . '/' . $this->_accountName;
		}
		else
		{
			return $schema . $this->_accountName . '.' . $this->_host;
		}
	}

	/**
	 * Get Credentials instance
	 *
	 * @return Credentials
	 */
	public function getCredentials()
	{
		return $this->_credentials;
	}

	/**
	 * Set Credentials instance
	 *
	 * @param   Credentials  $credentials  Credentials instance to use for request signing.
	 */
	public function setCredentials(Credentials $credentials)
	{
		$this->_credentials = $credentials;
		$this->_credentials->setAccountName($this->_accountName);
		$this->_credentials->setAccountkey($this->_accountKey);
		$this->_credentials->setUsePathStyleUri($this->_usePathStyleUri);
	}

	/**
	 * Generate ISO 8601 compliant date string in UTC time zone
	 *
	 * @param   int  $timestamp
	 *
	 * @return string
	 */
	public function isoDate($timestamp = null)
	{
		if (is_null($timestamp))
		{
			$timestamp = time();
		}

		$returnValue = @gmdate('Y-m-d\TH:i:s', $timestamp) . 'Z';

		return $returnValue;
	}

	/**
	 * Perform request using Transport channel
	 *
	 * @param   string   $path                Path
	 * @param   string   $queryString         Query string
	 * @param   string   $httpVerb            HTTP verb the request will use
	 * @param   array    $headers             x-ms headers to add
	 * @param   boolean  $forTableStorage     Is the request for table storage?
	 * @param   mixed    $inputObject         Optional RAW HTTP data to be sent over the wire
	 * @param   string   $resourceType        Resource type
	 * @param   string   $requiredPermission  Required permission
	 *
	 * @return Response
	 */
	protected function performRequest($path = '/', $queryString = '', $httpVerb = Transport::VERB_GET, $headers = [], $forTableStorage = false, Input $inputObject = null, $resourceType = AzureStorage::RESOURCE_UNKNOWN, $requiredPermission = Credentials::PERMISSION_READ)
	{
		// Clean path
		if (strpos($path, '/') !== 0)
		{
			$path = '/' . $path;
		}

		// Clean headers
		if (is_null($headers))
		{
			$headers = [];
		}

		// Add version header
		$headers['x-ms-version'] = $this->_apiVersion;

		// URL encoding
		$path        = self::urlencode($path);
		$queryString = self::urlencode($queryString);

		// Get the content length used for signing
		$contentLength = 0;

		if (!is_null($inputObject))
		{
			$contentLength = $inputObject->getSize();
		}

		if (!isset($headers['Content-Length']))
		{
			$headers['Content-Length'] = $contentLength;
		}

		// Generate URL and sign request
		$requestUrl     = $this->_credentials->signRequestUrl($this->getBaseUrl() . $path . $queryString, $resourceType, $requiredPermission);
		$requestHeaders = $this->_credentials->signRequestHeaders($httpVerb, $path, $queryString, $headers, $forTableStorage, $resourceType, $requiredPermission);

		$requestClient = Transport::createChannel();

		if ($this->_useProxy)
		{
			$requestClient->setProxy($this->_useProxy, $this->_proxyUrl, $this->_proxyPort, $this->_proxyCredentials);
		}

		$response = $this->_retryPolicy->execute(
			[$requestClient, 'request'],
			[$httpVerb, $requestUrl, [], $requestHeaders, $inputObject]
		);

		$requestClient = null;
		unset($requestClient);

		return $response;
	}

	/**
	 * Parse result from Response
	 *
	 * @param   Response  $response  Response from HTTP call
	 *
	 * @return object
	 * @throws Api
	 */
	protected function parseResponse(Response $response = null)
	{
		if (is_null($response))
		{
			throw new Api('Response should not be null.');
		}

		$xml = @simplexml_load_string($response->getBody());

		if ($xml !== false)
		{
			// Fetch all namespaces
			$namespaces = array_merge($xml->getNamespaces(true), $xml->getDocNamespaces(true));

			// Register all namespace prefixes
			foreach ($namespaces as $prefix => $ns)
			{
				if ($prefix != '')
				{
					$xml->registerXPathNamespace($prefix, $ns);
				}
			}
		}

		return $xml;
	}
}
