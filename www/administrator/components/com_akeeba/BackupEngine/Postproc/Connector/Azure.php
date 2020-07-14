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
 *
 * This file and all of its associated library files have been modified by Akeeba Ltd for use in Akeeba Engine.
 * Furthermore we added supported for the newer API versions.
 */

namespace Akeeba\Engine\Postproc\Connector;


use Akeeba\Engine\Postproc\Connector\Azure\AzureStorage;
use Akeeba\Engine\Postproc\Connector\Azure\Blob\Container;
use Akeeba\Engine\Postproc\Connector\Azure\Blob\Instance;
use Akeeba\Engine\Postproc\Connector\Azure\Credentials;
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
class Azure extends AzureStorage
{
	/**
	 * ACL - Private access
	 */
	const ACL_PRIVATE = false;

	/**
	 * ACL - Public access
	 */
	const ACL_PUBLIC = true;

	/**
	 * Create resource name
	 *
	 * @param   string  $containerName  Container name
	 * @param   string  $blobName       Blob name
	 *
	 * @return  string
	 */
	public static function createResourceName($containerName = '', $blobName = '')
	{
		// Resource name
		$resourceName = $containerName . '/' . $blobName;

		if ($containerName === '' || $containerName === '$root')
		{
			$resourceName = $blobName;
		}

		if ($blobName === '')
		{
			$resourceName = $containerName;
		}

		return $resourceName;
	}

	/**
	 * Is valid container name?
	 *
	 * @param   string  $containerName  Container name
	 *
	 * @return  boolean
	 */
	public static function isValidContainerName($containerName = '')
	{
		if ($containerName == '$root')
		{
			return true;
		}

		if (!preg_match('/^[a-z0-9][a-z0-9-]*$/', $containerName))
		{
			return false;
		}

		if (strpos($containerName, '--') !== false)
		{
			return false;
		}

		if (strtolower($containerName) != $containerName)
		{
			return false;
		}

		if (strlen($containerName) < 3 || strlen($containerName) > 63)
		{
			return false;
		}

		if (substr($containerName, -1) == '-')
		{
			return false;
		}

		return true;
	}

	/**
	 * Get container
	 *
	 * @param   string  $containerName  Container name
	 *
	 * @return  Container
	 *
	 * @throws  Api
	 */
	public function getContainer($containerName = '')
	{
		if ($containerName === '')
		{
			throw new Api('Container name is not specified.');
		}

		if (!self::isValidContainerName($containerName))
		{
			throw new Api('Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.');
		}

		// Perform request
		$response =
			$this->performRequest($containerName, '?restype=container', Transport::VERB_GET, [], false, null, AzureStorage::RESOURCE_CONTAINER, Credentials::PERMISSION_READ);

		if (!$response->isSuccessful())
		{
			throw new Api($this->getErrorMessage($response, 'Resource could not be accessed.'));
		}

		// Parse metadata
		$metadata = [];

		foreach ($response->getHeaders() as $key => $value)
		{
			if (substr(strtolower($key), 0, 10) == "x-ms-meta-")
			{
				$metadata[str_replace("x-ms-meta-", '', strtolower($key))] = $value;
			}
		}

		// Return container
		return new Container(
			$containerName,
			$response->getHeader('Etag'),
			$response->getHeader('Last-modified'),
			$metadata
		);
	}

	/**
	 * Put blob
	 *
	 * @param   string  $containerName       Container name
	 * @param   string  $blobName            Blob name
	 * @param   string  $localFileName       Local file name to be uploaded
	 * @param   array   $metadata            Key/value pairs of meta data
	 * @param   array   $additionalHeaders   Additional headers. See
	 *                                       http://msdn.microsoft.com/en-us/library/dd179371.aspx for more information.
	 *
	 * @return  Instance  Partial blob properties
	 * @throws  Api
	 */
	public function putBlob($containerName = '', $blobName = '', $localFileName = '', $metadata = [], $additionalHeaders = [])
	{
		if ($containerName === '')
		{
			throw new Api('Container name is not specified.');
		}

		if (!self::isValidContainerName($containerName))
		{
			throw new Api('Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.');
		}

		if ($blobName === '')
		{
			throw new Api('Blob name is not specified.');
		}

		if ($localFileName === '')
		{
			throw new Api('Local file name is not specified.');
		}

		if (!file_exists($localFileName))
		{
			throw new Api('Local file not found.');
		}

		if (($containerName === '$root') && strpos($blobName, '/') !== false)
		{
			throw new Api('Blobs stored in the root container can not have a name containing a forward slash (/).');
		}

		// Mandatory headers for this API version, see https://msdn.microsoft.com/en-us/library/azure/dd179451.aspx
		$headers = [
			'x-ms-blob-type' => 'BlockBlob',
		];

		// Create metadata headers
		foreach ($metadata as $key => $value)
		{
			$headers["x-ms-meta-" . strtolower($key)] = $value;
		}

		// Additional headers?
		foreach ($additionalHeaders as $key => $value)
		{
			$headers[$key] = $value;
		}

		// File contents
		$inputObject = Input::createFromFile($localFileName);

		// Resource name
		$resourceName = self::createResourceName($containerName, $blobName);

		// Perform request
		$response = $this->performRequest($resourceName, '', Transport::VERB_PUT, $headers, false, $inputObject, AzureStorage::RESOURCE_BLOB, Credentials::PERMISSION_WRITE);

		if (!$response->isSuccessful())
		{
			throw new Api($this->getErrorMessage($response, 'Resource could not be accessed.'));
		}

		return new Instance(
			$containerName,
			$blobName,
			$response->getHeader('Etag'),
			$response->getHeader('Last-modified'),
			$this->getBaseUrl() . '/' . $containerName . '/' . $blobName,
			$inputObject->getSize(),
			'',
			'',
			'',
			false,
			$metadata
		);
	}

	/**
	 * Set blob metadata
	 *
	 * Calling the Set Blob Metadata operation overwrites all existing metadata that is associated with the blob. It's
	 * not possible to modify an individual name/value pair.
	 *
	 * @param   string  $containerName      Container name
	 * @param   string  $blobName           Blob name
	 * @param   array   $metadata           Key/value pairs of meta data
	 * @param   array   $additionalHeaders  Additional headers. See
	 *                                      http://msdn.microsoft.com/en-us/library/dd179371.aspx for more information.
	 *
	 * @throws  Api
	 */
	public function setBlobMetadata($containerName = '', $blobName = '', $metadata = [], $additionalHeaders = [])
	{
		if ($containerName === '')
		{
			throw new Api('Container name is not specified.');
		}

		if (!self::isValidContainerName($containerName))
		{
			throw new Api('Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.');
		}

		if ($blobName === '')
		{
			throw new Api('Blob name is not specified.');
		}

		if ($containerName === '$root' && strpos($blobName, '/') !== false)
		{
			throw new Api('Blobs stored in the root container can not have a name containing a forward slash (/).');
		}

		if (count($metadata) == 0)
		{
			return;
		}

		// Create metadata headers
		$headers = [];

		foreach ($metadata as $key => $value)
		{
			$headers["x-ms-meta-" . strtolower($key)] = $value;
		}

		// Additional headers?
		foreach ($additionalHeaders as $key => $value)
		{
			$headers[$key] = $value;
		}

		// Perform request
		$response =
			$this->performRequest($containerName . '/' . $blobName, '?comp=metadata', Transport::VERB_PUT, $headers, false, null, AzureStorage::RESOURCE_BLOB, Credentials::PERMISSION_WRITE);

		if (!$response->isSuccessful())
		{
			throw new Api($this->getErrorMessage($response, 'Resource could not be accessed.'));
		}
	}

	/**
	 * Get blob
	 *
	 * @param   string  $containerName      Container name
	 * @param   string  $blobName           Blob name
	 * @param   string  $localFileName      Local file name to store downloaded blob
	 * @param   string  $snapshotId         Snapshot identifier
	 * @param   string  $leaseId            Lease identifier
	 * @param   array   $additionalHeaders  Additional headers. See
	 *                                      http://msdn.microsoft.com/en-us/library/dd179371.aspx for more information.
	 *
	 * @throws  Api
	 */
	public function getBlob($containerName = '', $blobName = '', $localFileName = '', $snapshotId = null, $leaseId = null, $additionalHeaders = [])
	{
		if ($containerName === '')
		{
			throw new Api('Container name is not specified.');
		}

		if (!self::isValidContainerName($containerName))
		{
			throw new Api('Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.');
		}

		if ($blobName === '')
		{
			throw new Api('Blob name is not specified.');
		}

		if ($localFileName === '')
		{
			throw new Api('Local file name is not specified.');
		}

		// Fetch data
		file_put_contents($localFileName, $this->getBlobData($containerName, $blobName, $snapshotId, $leaseId, $additionalHeaders));
	}

	/**
	 * Get blob data
	 *
	 * @param   string  $containerName      Container name
	 * @param   string  $blobName           Blob name
	 * @param   string  $snapshotId         Snapshot identifier
	 * @param   string  $leaseId            Lease identifier
	 * @param   array   $additionalHeaders  Additional headers. See
	 *                                      http://msdn.microsoft.com/en-us/library/dd179371.aspx for more information.
	 *
	 * @return  mixed  Blob contents
	 *
	 * @throws  Api
	 */
	public function getBlobData($containerName = '', $blobName = '', $snapshotId = null, $leaseId = null, $additionalHeaders = [])
	{
		if ($containerName === '')
		{
			throw new Api('Container name is not specified.');
		}

		if (!self::isValidContainerName($containerName))
		{
			throw new Api('Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.');
		}

		if ($blobName === '')
		{
			throw new Api('Blob name is not specified.');
		}

		// Build query string
		$queryString = [];

		if (!is_null($snapshotId))
		{
			$queryString[] = 'snapshot=' . $snapshotId;
		}

		$queryString = self::createQueryStringFromArray($queryString);

		// Additional headers?
		$headers = [];

		if (!is_null($leaseId))
		{
			$headers['x-ms-lease-id'] = $leaseId;
		}

		foreach ($additionalHeaders as $key => $value)
		{
			$headers[$key] = $value;
		}

		// Resource name
		$resourceName = self::createResourceName($containerName, $blobName);

		// Perform request
		$response = $this->performRequest($resourceName, $queryString, 'GET', $headers, false, null, self::RESOURCE_BLOB, Credentials::PERMISSION_READ);

		if (!$response->isSuccessful())
		{
			throw new Api($this->getErrorMessage($response, 'Resource could not be accessed.'));
		}

		return $response->getBody();
	}

	/**
	 * Delete blob
	 *
	 * @param   string  $containerName      Container name
	 * @param   string  $blobName           Blob name
	 * @param   string  $snapshotId         Snapshot identifier
	 * @param   string  $leaseId            Lease identifier
	 * @param   array   $additionalHeaders  Additional headers. See
	 *                                      http://msdn.microsoft.com/en-us/library/dd179371.aspx for more information.
	 *
	 * @throws  Api
	 */
	public function deleteBlob($containerName = '', $blobName = '', $snapshotId = null, $leaseId = null, $additionalHeaders = [])
	{
		if ($containerName === '')
		{
			throw new Api('Container name is not specified.');
		}

		if (!self::isValidContainerName($containerName))
		{
			throw new Api('Container name does not adhere to container naming conventions. See http://msdn.microsoft.com/en-us/library/dd135715.aspx for more information.');
		}

		if ($blobName === '')
		{
			throw new Api('Blob name is not specified.');
		}

		if ($containerName === '$root' && strpos($blobName, '/') !== false)
		{
			throw new Api('Blobs stored in the root container can not have a name containing a forward slash (/).');
		}

		// Build query string
		$queryString = [];

		if (!is_null($snapshotId))
		{
			$queryString[] = 'snapshot=' . $snapshotId;
		}

		$queryString = self::createQueryStringFromArray($queryString);

		// Additional headers?
		$headers = [];

		if (!is_null($leaseId))
		{
			$headers['x-ms-lease-id'] = $leaseId;
		}

		foreach ($additionalHeaders as $key => $value)
		{
			$headers[$key] = $value;
		}

		// Resource name
		$resourceName = self::createResourceName($containerName, $blobName);

		// Perform request
		$response =
			$this->performRequest($resourceName, $queryString, Transport::VERB_DELETE, $headers, false, null, self::RESOURCE_BLOB, Credentials::PERMISSION_WRITE);

		if (!$response->isSuccessful())
		{
			throw new Api($this->getErrorMessage($response, 'Resource could not be accessed.'));
		}
	}

	/**
	 * Returns a signed download (GET) URL for a specific blob
	 *
	 * @param   string  $container         The name of the container where the Blob is in
	 * @param   string  $remotePath        Remote path to the Blob, relative to the container's root
	 * @param   int     $expiresInSeconds  How many seconds from now does the link expire (default: 900 seconds)
	 *
	 * @return  string  Signed download URL
	 */
	public function getSignedURL($container, $remotePath, $expiresInSeconds = 900)
	{
		$account      = $this->_accountName;
		$canonicalURL = '/' . $account . '/' . $container . '/' . ltrim($remotePath, '/');

		// Signing API version
		$signedversion = '2012-02-12';
		// Signature resource type (Blob)
		$signedresource = 'b';
		// Signed start
		$signedstart = $this->isoDate(time());
		// Signed expiration
		$signedexpiry = $this->isoDate(time() + $expiresInSeconds);
		// Signed permissions (read only)
		$signedpermissions = 'r';

		/**
		 * Calculate the string to sign
		 *
		 * @see https://docs.microsoft.com/en-us/rest/api/storageservices/create-service-sas#version-2015-04-05-and-later
		 */
		// Signed Permissions
		$stringToSign = $signedpermissions . "\n";
		// Signed Start
		$stringToSign .= $signedstart . "\n";
		// Signed Expiry
		$stringToSign .= $signedexpiry . "\n";
		// Canonicalized resource
		$stringToSign .= $canonicalURL . "\n";
		// Signed Identifier
		$stringToSign .= "\n";
		// Signed Version
		$stringToSign .= $signedversion;

		$sig = base64_encode(hash_hmac('sha256', $stringToSign, base64_decode($this->_accountKey), true));

		$query = http_build_query([
			'sv'  => $signedversion,
			'st'  => $signedstart,
			'se'  => $signedexpiry,
			'sr'  => $signedresource,
			'sp'  => $signedpermissions,
			'sig' => $sig,
		]);

		return $this->getBaseUrl() . '/' . $container . '/' . ltrim($remotePath, '/') . '?' . $query;
	}

	/**
	 * Get error message from Response
	 *
	 * @param   Response  $rawResponse       Response
	 * @param   string    $alternativeError  Alternative error message
	 *
	 * @return  string
	 *
	 * @throws  Api
	 */
	protected function getErrorMessage(Response $rawResponse, $alternativeError = 'Unknown error.')
	{
		$response = $this->parseResponse($rawResponse);

		if ($response && $response->Message)
		{
			$error = (string) $response->Message;

			// And add some debug information
			$error .= "\n\nRAW REPLY (FOR DEBUGGING):\n\n" . $rawResponse->getBody();

			return $error;
		}

		return $alternativeError;
	}

}
