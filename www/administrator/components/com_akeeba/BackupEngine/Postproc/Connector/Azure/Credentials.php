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

use Akeeba\Engine\Postproc\Connector\Azure\Http\Transport;

/**
 * @category   Microsoft
 * @package    Microsoft_WindowsAzure
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 */
abstract class Credentials
{
	/**
	 * Development storage account and key
	 */
	const DEVSTORE_ACCOUNT = "devstoreaccount1";

	const DEVSTORE_KEY = "Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==";

	/**
	 * HTTP header prefixes
	 */
	const PREFIX_PROPERTIES = "x-ms-prop-";

	const PREFIX_METADATA = "x-ms-meta-";

	const PREFIX_STORAGE_HEADER = "x-ms-";

	/**
	 * Permissions
	 */
	const PERMISSION_READ = "r";

	const PERMISSION_WRITE = "w";

	const PERMISSION_DELETE = "d";

	const PERMISSION_LIST = "l";

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
	 * Creates a new Credentials instance
	 *
	 * @param   string   $accountName      Account name for Windows Azure
	 * @param   string   $accountKey       Account key for Windows Azure
	 * @param   boolean  $usePathStyleUri  Use path-style URI's
	 */
	public function __construct($accountName = Credentials::DEVSTORE_ACCOUNT, $accountKey = Credentials::DEVSTORE_KEY, $usePathStyleUri = false)
	{
		$this->_accountName     = $accountName;
		$this->_accountKey      = base64_decode($accountKey);
		$this->_usePathStyleUri = $usePathStyleUri;
	}

	/**
	 * Set account name for Windows Azure
	 *
	 * @param   string  $value
	 */
	public function setAccountName($value = Credentials::DEVSTORE_ACCOUNT)
	{
		$this->_accountName = $value;
	}

	/**
	 * Set account key for Windows Azure
	 *
	 * @param   string  $value
	 */
	public function setAccountkey($value = Credentials::DEVSTORE_KEY)
	{
		$this->_accountKey = base64_decode($value);
	}

	/**
	 * Set use path-style URI's
	 *
	 * @param   boolean  $value
	 */
	public function setUsePathStyleUri($value = false)
	{
		$this->_usePathStyleUri = $value;
	}

	/**
	 * Sign request URL with credentials
	 *
	 * @param   string  $requestUrl          Request URL
	 * @param   string  $resourceType        Resource type
	 * @param   string  $requiredPermission  Required permission
	 *
	 * @return string Signed request URL
	 */
	public abstract function signRequestUrl($requestUrl = '');

	/**
	 * Sign request headers with credentials
	 *
	 * @param   string   $httpVerb            HTTP verb the request will use
	 * @param   string   $path                Path for the request
	 * @param   string   $queryString         Query string for the request
	 * @param   array    $headers             x-ms headers to add
	 * @param   boolean  $forTableStorage     Is the request for table storage?
	 * @param   string   $resourceType        Resource type
	 * @param   string   $requiredPermission  Required permission
	 *
	 * @return array Array of headers
	 */
	public abstract function signRequestHeaders($httpVerb = Transport::VERB_GET, $path = '/', $queryString = '', $headers = null, $forTableStorage = false, $resourceType = AzureStorage::RESOURCE_UNKNOWN, $requiredPermission = Credentials::PERMISSION_READ);


	/**
	 * Prepare query string for signing
	 *
	 * @param   string  $value  Original query string
	 *
	 * @return string        Query string for signing
	 */
	protected function prepareQueryStringForSigning($value)
	{
		// Check for 'comp='
		if (strpos($value, 'comp=') === false)
		{
			// If not found, no query string needed
			return '';
		}
		else
		{
			// If found, make sure it is the only parameter being used
			if (strlen($value) > 0 && strpos($value, '?') === 0)
			{
				$value = substr($value, 1);
			}

			// Split parts
			$queryParts = explode('&', $value);
			foreach ($queryParts as $queryPart)
			{
				if (strpos($queryPart, 'comp=') !== false)
				{
					return '?' . $queryPart;
				}
			}

			// Should never happen...
			return '';
		}
	}
}