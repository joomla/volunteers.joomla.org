<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Postproc\Connector\AzureModern;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Postproc\Connector\Azure\Blob\Instance;
use Akeeba\Engine\Postproc\Connector\AzureModern\Blob\Container;
use Akeeba\Engine\Postproc\Connector\AzureModern\Exception\ApiException;
use Akeeba\Engine\Postproc\Connector\AzureModern\Exception\FileTooBigToChunk;
use Akeeba\Engine\Postproc\Connector\AzureModern\Exception\FileTooBigToSingleUpload;
use Akeeba\Engine\Postproc\Connector\AzureModern\Exception\ForwardSlashNotAllowed;
use Akeeba\Engine\Postproc\Connector\AzureModern\Exception\InvalidContainerName;
use Akeeba\Engine\Postproc\Connector\AzureModern\Exception\LocalFileNotFound;
use Akeeba\Engine\Postproc\Connector\AzureModern\Exception\MaxBlockSizeExceeded;
use Akeeba\Engine\Postproc\Connector\AzureModern\Exception\NoBlobName;
use Akeeba\Engine\Postproc\Connector\AzureModern\Exception\NoBlocks;
use Akeeba\Engine\Postproc\Connector\AzureModern\Exception\NoContainerName;
use Akeeba\Engine\Postproc\Connector\AzureModern\Exception\NoData;
use Akeeba\Engine\Postproc\Connector\AzureModern\Exception\NoLocalFileName;
use Akeeba\Engine\Postproc\Connector\AzureModern\Exception\TooManyBlocks;
use Akeeba\Engine\Postproc\Connector\AzureModern\Exception\UnexpectedHTTPStatus;
use Akeeba\Engine\Postproc\Connector\S3v4\Response;
use Akeeba\Engine\Postproc\ProxyAware;
use Akeeba\Engine\Util\FileCloseAware;
use SimpleXMLElement;

/**
 * Microsoft Azure connector, modernized
 */
class Connector
{
	use FileCloseAware;
	use ProxyAware;

	/**
	 * The version of the API we are using
	 *
	 * @const  string
	 * @since  9.2.1
	 */
	private const API_VERSION = '2019-12-12';

	/**
	 * Default cURL options
	 *
	 * @since  9.2.1
	 */
	private const DEFAULT_CURL_OPTIONS = [
		CURLOPT_USERAGENT      => 'AkeebaEngine/9',
		CURLOPT_SSL_VERIFYPEER => true,
		CURLOPT_SSL_VERIFYHOST => 2,
		CURLOPT_VERBOSE        => false,
		CURLOPT_HEADER         => false,
		CURLINFO_HEADER_OUT    => false,
		CURLOPT_RETURNTRANSFER => false,
		CURLOPT_CAINFO         => AKEEBA_CACERT_PEM,
		CURLOPT_FOLLOWLOCATION => true,
	];

	/**
	 * The credentials signing object
	 *
	 * @var   Credentials
	 * @since 9.2.1
	 */
	private $credentials;

	/**
	 * The endpoint domain name for Microsoft Azure
	 *
	 * @var   bool
	 * @since 9.2.1
	 */
	private $endPoint = 'core.windows.net';

	/**
	 * Should I use HTTPS for accessing Microsoft Azure?
	 *
	 * @var   bool
	 * @since 9.2.1
	 */
	private $useSSL;

	/**
	 * The response being worked on by performRequest()
	 *
	 * @var   Response|null
	 * @since 9.2.1
	 */
	private $response = null;

	/**
	 * The output file pointer used in performRequest();
	 *
	 * @var   resource|null
	 * @since 9.2.1
	 */
	private $fp = null;

	/**
	 * Creates a new Credentials instance
	 *
	 * @param   string  $accountName      Account name for Microsoft Azure
	 * @param   string  $accountKey       Account key for Microsoft Azure
	 * @param   bool    $usePathStyleUri  Use path-style URI's? Default false.
	 * @param   bool    $useSSL           Should I use HTTPS? Default true.
	 * @param   string  $endPoint         Endpoint domain name, default core.windows.net
	 *
	 * @since   9.2.1
	 */
	public function __construct(string $accountName, string $accountKey, bool $usePathStyleUri = false, bool $useSSL = true, string $endPoint = 'core.windows.net')
	{
		$this->credentials = new Credentials($accountName, $accountKey, $usePathStyleUri);
		$this->useSSL      = $useSSL;
		$this->endPoint    = $endPoint;
	}

	/**
	 * Get a connector object given a connection string.
	 *
	 * The connection string looks like this:
	 *   DefaultEndpointsProtocol=https;AccountName=foobar;AccountKey=AAAAAA=;EndpointSuffix=core.windows.net
	 *
	 * @param   string  $connectionString  The connection string to parse
	 *
	 * @return  static
	 * @since   9.2.1
	 */
	public static function fromConnectionString(string $connectionString): self
	{
		$lines = explode(';', $connectionString);
		$data  = [];

		foreach ($lines as $line)
		{
			$parts = explode('=', $line, 2);

			if (count($parts) != 2)
			{
				continue;
			}

			$data[strtolower($parts[0])] = $parts[1];
		}

		return new self(
			$data['accountname'] ?? '',
			$data['accountkey'] ?? '',
			false,
			strtolower($data['defaultendpointsprotocol'] ?? '') != 'http',
			$data['endpointsuffix'] ?? 'core.windows.net'
		);
	}

	/**
	 * Get the container properties
	 *
	 * @param   string  $containerName  Container name
	 *
	 * @return  Container
	 *
	 * @throws  ApiException
	 * @since   9.2.1
	 * @see     https://docs.microsoft.com/en-us/rest/api/storageservices/get-container-properties
	 */
	public function getContainer(string $containerName = ''): Container
	{
		if ($containerName === '')
		{
			throw new NoContainerName();
		}

		if (!self::isValidContainerName($containerName))
		{
			throw new InvalidContainerName();
		}

		// Perform request
		$response = $this->performRequest('GET', $containerName, '?restype=container');

		if ($response->getCode() > 399)
		{
			throw new UnexpectedHTTPStatus($response->getCode());
		}
		elseif ($response->error->isError())
		{
			throw new ApiException($response->error->getMessage(), $response->error->getCode());
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

		$headers = $response->getHeaders();

		return new Container(
			$containerName,
			$this->extractHeader($headers, 'Etag'),
			$this->extractHeader($headers, 'Last-modified'),
			$metadata
		);
	}

	/**
	 * Put blob
	 *
	 * @param   string  $containerName      Container name
	 * @param   string  $blobName           Blob name
	 * @param   string  $localFileName      Local file name to be uploaded
	 * @param   array   $metadata           Key/value pairs of meta data
	 * @param   array   $additionalHeaders  Additional headers.
	 *
	 * @return  Instance  Partial blob properties
	 * @throws  ApiException
	 * @see     https://docs.microsoft.com/en-us/rest/api/storageservices/put-blob
	 * @see     https://docs.microsoft.com/en-us/rest/api/storageservices/Specifying-Conditional-Headers-for-Blob-Service-Operations
	 *
	 * @since   9.2.1
	 */
	public function putBlob(
		string $containerName = '', string $blobName = '', string $localFileName = '', array $metadata = [],
		array  $additionalHeaders = []
	)
	{
		if ($containerName === '')
		{
			throw new NoContainerName();
		}

		if (!self::isValidContainerName($containerName))
		{
			throw new InvalidContainerName();
		}

		if ($blobName === '')
		{
			throw new NoBlobName();
		}

		if ($localFileName === '')
		{
			throw new NoLocalFileName();
		}

		if (!file_exists($localFileName))
		{
			throw new LocalFileNotFound();
		}

		if (($containerName === '$root') && strpos($blobName, '/') !== false)
		{
			throw new ForwardSlashNotAllowed();
		}

		/**
		 * Mandatory headers for this API version
		 * @see https://docs.microsoft.com/en-us/rest/api/storageservices/Put-Blob
		 */
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
		$contentLength             = (int) filesize($localFileName);
		$headers['file']           = $localFileName;
		$headers['Content-Length'] = $contentLength;

		// Is this file too big to upload?
		$chunkSize = $this->getBestBlockSize($localFileName, 0);

		if ($chunkSize > 0)
		{
			throw new FileTooBigToSingleUpload();
		}

		// Resource name
		$resourceName = self::createResourceName($containerName, $blobName);

		// Perform request
		$response = $this->performRequest('PUT', $resourceName, '', $headers);

		if ($response->error->isError())
		{
			throw new ApiException($response->error->getMessage(), $response->error->getCode());
		}

		if ($response->getCode() > 399)
		{
			throw new ApiException($this->getErrorMessage($response, 'Resource could not be accessed.'), $response->getCode());
		}

		$headers = $response->getHeaders();

		return new Instance(
			$containerName,
			$blobName,
			$this->extractHeader($headers, 'Etag'),
			$this->extractHeader($headers, 'Last-modified'),
			$this->getBaseUrl() . '/' . $containerName . '/' . $blobName,
			$contentLength,
			'',
			'',
			'',
			false,
			$metadata
		);
	}

	/**
	 * Put blob block.
	 *
	 * Used for chunked uploading.
	 *
	 * Make sure the $additionalHeaders array contains a `blockid` key. If it's missing, a random one will be created
	 * and returned by this method.
	 *
	 * @param   string  $containerName      Container name
	 * @param   string  $blobName           Blob name
	 * @param   string  $data               Binary data to upload into the blob object's block
	 * @param   array   $metadata           Key/value pairs of meta data
	 * @param   array   $additionalHeaders  Additional headers
	 *
	 * @return  string  The block ID which was sent to Azure
	 * @throws  ApiException
	 * @see     https://docs.microsoft.com/en-us/rest/api/storageservices/put-block
	 * @see     https://docs.microsoft.com/en-us/rest/api/storageservices/Specifying-Conditional-Headers-for-Blob-Service-Operations
	 *
	 * @since   9.2.1
	 */
	public function putBlock(
		string $containerName = '', string $blobName = '', string $data = '', array $metadata = [],
		array  $additionalHeaders = []
	): string
	{
		if ($containerName === '')
		{
			throw new NoContainerName();
		}

		if (!self::isValidContainerName($containerName))
		{
			throw new InvalidContainerName();
		}

		if ($blobName === '')
		{
			throw new NoBlobName();
		}

		if (($containerName === '$root') && strpos($blobName, '/') !== false)
		{
			throw new ForwardSlashNotAllowed();
		}

		if ($data === '')
		{
			throw new NoData();
		}

		// Blob block size check
		$currentSize   = function_exists('mb_strlen') ? mb_strlen($data, '8bit') : strlen($data);
		$utcTz         = new \DateTimeZone('utc');
		$versionAsTime = new \DateTime(self::API_VERSION, $utcTz);

		if ($versionAsTime->getTimestamp() >= (new \DateTime('2019-12-12', $utcTz))->getTimestamp())
		{
			$maxSize = 4000 * 1024 * 1024;
		}
		elseif ($versionAsTime->getTimestamp() >= (new \DateTime('2016-05-31', $utcTz))->getTimestamp())
		{
			$maxSize = 10 * 1024 * 1024;
		}
		else
		{
			$maxSize = 4 * 1024 * 1024;
		}

		if ($currentSize > $maxSize)
		{
			throw new MaxBlockSizeExceeded($currentSize, $maxSize);
		}

		// Make sure I have a block ID
		$encodedBlockId = $additionalHeaders['blockid'] ?? null;

		if (empty($encodedBlockId))
		{
			$additionalHeaders['blockid'] = '';
			$encodedBlockId               = base64_encode(sha1(microtime() . random_bytes(12)));
		}

		unset($additionalHeaders['blockid']);

		/**
		 * Mandatory headers for this API version
		 * @see https://docs.microsoft.com/en-us/rest/api/storageservices/Put-Blob
		 */
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
		$headers['explicit_post']  = &$data;
		$headers['Content-Length'] = $currentSize;
		$headers['Content-Type']   = 'application/x-www-form-urlencoded';

		// Resource name
		$resourceName = self::createResourceName($containerName, $blobName);

		// Perform request
		$queryParams = [
			'comp'    => 'block',
			'blockid' => $encodedBlockId,
		];
		$response    = $this->performRequest('PUT', $resourceName, '?' . http_build_query($queryParams), $headers);

		if ($response->error->isError())
		{
			throw new ApiException($response->error->getMessage(), $response->error->getCode());
		}

		if ($response->getCode() != 201)
		{
			throw new ApiException($this->getErrorMessage($response, 'Resource could not be accessed.'), $response->getCode());
		}

		return base64_decode($encodedBlockId);
	}

	/**
	 * Finalize a chunked upload
	 *
	 * @param   string  $containerName  Container name
	 * @param   string  $blobName       Blob name
	 * @param   array   $blockIds       List of the NON-encoded block IDs to commit
	 *
	 * @return  void
	 * @since   9.2.1
	 */
	public function putBlockList(string $containerName = '', string $blobName = '', array $blockIds = []): void
	{
		if ($containerName === '')
		{
			throw new NoContainerName();
		}

		if (!self::isValidContainerName($containerName))
		{
			throw new InvalidContainerName();
		}

		if ($blobName === '')
		{
			throw new NoBlobName();
		}

		if (($containerName === '$root') && strpos($blobName, '/') !== false)
		{
			throw new ForwardSlashNotAllowed();
		}

		if (empty($blockIds))
		{
			throw new NoBlocks();
		}

		if (count($blockIds) > 50000)
		{
			throw new TooManyBlocks();
		}

		// Construct the document to PUT.
		$data = '<?xml version="1.0" encoding="utf-8"?><BlockList>' . "\n" .
			array_reduce($blockIds, function (string $carry, string $item) {
				return sprintf("%s<Latest>%s</Latest>\n", $carry, base64_encode($item));
			}, '') . '</BlockList>';

		$headers = [
			'Content-Type'   => 'text/plain; charset=UTF-8',
			'Content-Length' => function_exists('mb_strlen') ? mb_strlen($data, '8bit') : strlen($data),
			'explicit_post'  => $data,
		];

		// Resource name
		$resourceName = self::createResourceName($containerName, $blobName);

		// Perform request
		$response = $this->performRequest('PUT', $resourceName, '?comp=blocklist', $headers);

		if ($response->error->isError())
		{
			throw new ApiException($response->error->getMessage(), $response->error->getCode());
		}

		if ($response->getCode() != 201)
		{
			throw new ApiException($this->getErrorMessage($response, 'Resource could not be accessed.'), $response->getCode());
		}
	}

	/**
	 * Find the best block size for putBlock for a given file.
	 *
	 * The best block size is one which does not cause the block count to exceed 50,000 (Azure's hard limit on the
	 * number of blocks a BLOB object can consist of) and is not lower than $desirableBlockSize.
	 *
	 * For example, a 100Gb file has a minimum block size of 2100Kb (just over 2Mb). If the $desirableBlockSize is 10Mb
	 * then this method will return 10Mb. If the $desirableBlockSize is 1Mb this method will return 2100Kb (higher than
	 * the desirable), otherwise you'd need more than 50,000 blocks which is not allowed.
	 *
	 * Each block size can also not be higher than 4000MiB, 100MiB or 4MiB depending on the API version. This is also
	 * taken into account. If the desirable block size is higher than that it is squashed down to the limit. If the
	 * calculated minimum block size exceeds that you will get an exception.
	 *
	 * In practical terms, each API version defines a maximum file size limit (max block size x 50,000):
	 * - < 2016-05-31: 195GiB (4MiB x 50,000 blocks)
	 * - 2016-05-31 to 2019-07-07: 4.75TiB (100MiB x 50,000 blocks)
	 * - >= 2019-12-12: 190.7TiB (4000MiB x 50,000 blocks)
	 *
	 * If the $desirableBlockSize is 0 this is understood as a query to whether the file can be uploaded with a putBlob
	 * in one go. In this case the value returned is either 0 (you can upload with putBlob) or the minimum block size.
	 *
	 * Each API version has a different maximum file size for direct file upload:
	 * - < 2016-05-31: 64MiB
	 * - 2016-05-31 to 2019-07-07: 256MiB
	 * - >= 2019-12-12: 5000MiB (in preview status as of April 2022 when this code was last updated)
	 *
	 * @param   string  $fileName
	 * @param   int     $desirableBlockSize
	 *
	 * @return  int
	 * @throws  ApiException
	 * @since   9.2.1
	 * @see     https://docs.microsoft.com/en-us/rest/api/storageservices/put-block-list
	 */
	public function getBestBlockSize(string $fileName, int $desirableBlockSize = 10485760): int
	{
		// Make sure the file exists and we can get its file size.
		@clearstatcache($fileName);

		if (!file_exists($fileName) || !is_file($fileName))
		{
			return $desirableBlockSize;
		}

		$fileSize = @filesize($fileName);

		if (!$fileSize)
		{
			return $desirableBlockSize;
		}

		// What is the minimum block size we have to use to upload this file?
		$minBlockSize = ceil($fileSize / 50000);

		// Find the limits for each API version
		$utcTz         = new \DateTimeZone('utc');
		$versionAsTime = new \DateTime(self::API_VERSION, $utcTz);

		if ($versionAsTime->getTimestamp() >= (new \DateTime('2019-12-12', $utcTz))->getTimestamp())
		{
			$maxBlockSize = 4000 * 1024 * 1024;
			$maxBlobSize  = 5000 * 1024 * 1024;
		}
		elseif ($versionAsTime->getTimestamp() >= (new \DateTime('2016-05-31', $utcTz))->getTimestamp())
		{
			$maxBlockSize = 10 * 1024 * 1024;
			$maxBlobSize  = 256 * 1024 * 1024;
		}
		else
		{
			$maxBlockSize = 4 * 1024 * 1024;
			$maxBlobSize  = 64 * 1024 * 1024;
		}

		// If the $desirableBlockSize is 0 we are querying whether the file can be uploaded in one go.
		if (empty($desirableBlockSize) && ($fileSize <= $maxBlobSize))
		{
			return 0;
		}

		// The $desirableBlockSize must be smaller than $maxBlockSize
		$desirableBlockSize = min($desirableBlockSize, $maxBlockSize);

		// The $minBlockSize must be lower than $maxBlockSize
		if ($minBlockSize > $maxBlockSize)
		{
			throw new FileTooBigToChunk();
		}

		// In any other case return the largest between the $minBlockSize and $desirableBlockSize.
		return max($minBlockSize, $desirableBlockSize);
	}

	/**
	 * Get the blob contents into a file
	 *
	 * @param   string       $containerName      Container name
	 * @param   string       $blobName           Blob name
	 * @param   string       $localFileName      The filename in the local filesystem to write to
	 * @param   string|null  $snapshotId         Snapshot identifier
	 * @param   string|null  $leaseId            Lease identifier
	 * @param   array        $additionalHeaders  Additional headers.
	 *
	 * @return void
	 * @throws  ApiException
	 * @see     https://docs.microsoft.com/en-us/rest/api/storageservices/get-blob
	 * @see     https://docs.microsoft.com/en-us/rest/api/storageservices/Specifying-Conditional-Headers-for-Blob-Service-Operations
	 *
	 */
	public function getBlob(
		string  $containerName = '', string $blobName = '', string $localFileName = '', ?string $snapshotId = null,
		?string $leaseId = null, array $additionalHeaders = []
	)
	{
		$additionalHeaders = array_merge($additionalHeaders, ['file' => $localFileName]);

		$this->getBlobData($containerName, $blobName, $snapshotId, $leaseId, $additionalHeaders);
	}

	/**
	 * Get blob data
	 *
	 * @param   string       $containerName      Container name
	 * @param   string       $blobName           Blob name
	 * @param   string|null  $snapshotId         Snapshot identifier
	 * @param   string|null  $leaseId            Lease identifier
	 * @param   array        $additionalHeaders  Additional headers.
	 *
	 * @return  mixed  Blob contents
	 *
	 * @throws  ApiException
	 * @see     https://docs.microsoft.com/en-us/rest/api/storageservices/get-blob
	 * @see     https://docs.microsoft.com/en-us/rest/api/storageservices/Specifying-Conditional-Headers-for-Blob-Service-Operations
	 *
	 * @since   9.2.1
	 */
	public function getBlobData(
		string $containerName = '', string $blobName = '', ?string $snapshotId = null, ?string $leaseId = null,
		array  $additionalHeaders = []
	)
	{
		if ($containerName === '')
		{
			throw new NoContainerName();
		}

		if (!self::isValidContainerName($containerName))
		{
			throw new InvalidContainerName();
		}

		if ($blobName === '')
		{
			throw new NoBlobName();
		}

		// Build query string
		$queryString = [];

		if (!is_null($snapshotId))
		{
			$queryString['snapshot'] = $snapshotId;
		}

		$queryString = '?' . http_build_query($queryString);

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
		$response = $this->performRequest('GET', $resourceName, $queryString, $headers);

		if ($response->error->isError())
		{
			throw new ApiException($response->error->getMessage(), $response->error->getCode());
		}

		if ($response->getCode() > 399)
		{
			throw new ApiException($this->getErrorMessage($response, 'Resource could not be accessed.'));
		}

		return $response->getBody();
	}

	/**
	 * Delete blob
	 *
	 * @param   string       $containerName      Container name
	 * @param   string       $blobName           Blob name
	 * @param   string|null  $snapshotId         Snapshot identifier
	 * @param   string|null  $leaseId            Lease identifier
	 * @param   array        $additionalHeaders  Additional headers.
	 *
	 * @throws  ApiException
	 * @see     https://docs.microsoft.com/en-us/rest/api/storageservices/delete-blob
	 * @see     https://docs.microsoft.com/en-us/rest/api/storageservices/Specifying-Conditional-Headers-for-Blob-Service-Operations
	 */
	public function deleteBlob(
		string $containerName = '', string $blobName = '', ?string $snapshotId = null, ?string $leaseId = null,
		array  $additionalHeaders = []
	)
	{
		if ($containerName === '')
		{
			throw new NoContainerName();
		}

		if (!self::isValidContainerName($containerName))
		{
			throw new InvalidContainerName();
		}

		if ($blobName === '')
		{
			throw new NoBlobName();
		}

		if (($containerName === '$root') && strpos($blobName, '/') !== false)
		{
			throw new ForwardSlashNotAllowed();
		}

		$queryString = [];

		if (!is_null($snapshotId))
		{
			$queryString['snapshot'] = $snapshotId;
		}

		$queryString = '?' . http_build_query($queryString);

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
		$response = $this->performRequest('DELETE', $resourceName, $queryString, $headers);

		if ($response->error->isError())
		{
			throw new ApiException($response->error->getMessage(), $response->error->getCode());
		}

		if ($response->getCode() > 399)
		{
			throw new ApiException($this->getErrorMessage($response, 'Resource could not be accessed.'));
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
	 * @since   9.2.1
	 */
	public function getSignedURL($container, $remotePath, $expiresInSeconds = 900)
	{
		$account      = $this->credentials->getAccountName();
		$canonicalURL = '/' . $account . '/' . $container . '/' . ltrim($remotePath, '/');

		// Signing API version
		$signedVersion = '2012-02-12';
		// Signature resource type (Blob)
		$signedresource = 'b';
		// Signed start
		$signedStart = gmdate('Y-m-d\TH:i:s', time()) . 'Z';
		// Signed expiration
		$signedExpiry = gmdate('Y-m-d\TH:i:s', time() + $expiresInSeconds) . 'Z';
		// Signed permissions (read only)
		$signedPermissions = 'r';

		/**
		 * Calculate the string to sign
		 *
		 * @see https://docs.microsoft.com/en-us/rest/api/storageservices/create-service-sas#version-2012-02-12
		 */
		// Signed Permissions
		$stringToSign = $signedPermissions . "\n";
		// Signed Start
		$stringToSign .= $signedStart . "\n";
		// Signed Expiry
		$stringToSign .= $signedExpiry . "\n";
		// Canonicalized resource
		$stringToSign .= $canonicalURL . "\n";
		// Signed Identifier
		$stringToSign .= "\n";
		// Signed Version
		$stringToSign .= $signedVersion;

		$sig = base64_encode(hash_hmac('sha256', $stringToSign, $this->credentials->getAccountKey(), true));

		$query = http_build_query([
			'sv'  => $signedVersion,
			'st'  => $signedStart,
			'se'  => $signedExpiry,
			'sr'  => $signedresource,
			'sp'  => $signedPermissions,
			'sig' => $sig,
		]);

		return $this->getBaseUrl() . '/' . $container . '/' . ltrim($remotePath, '/') . '?' . $query;
	}

	/**
	 * cURL write callback
	 *
	 * @param   resource  $curl  cURL resource
	 * @param   string    $data  Data
	 *
	 * @return  int  Length in bytes
	 * @since   9.2.1
	 */
	protected function __responseWriteCallback($curl, string $data): int
	{
		if (in_array($this->response->code, [200, 206]) && !is_null($this->fp) && is_resource($this->fp))
		{
			return fwrite($this->fp, $data);
		}

		$this->response->addToBody($data);

		return strlen($data);
	}

	/**
	 * cURL header callback
	 *
	 * @param   resource  $curl  cURL resource
	 * @param   string    $data  Data
	 *
	 * @return  int  Length in bytes
	 * @since   9.2.1
	 */
	protected function __responseHeaderCallback($curl, string $data): int
	{
		if (($strlen = strlen($data)) <= 2)
		{
			return $strlen;
		}

		if (substr($data, 0, 4) == 'HTTP')
		{
			$this->response->code = (int) substr($data, 9, 3);

			return $strlen;
		}

		[$header, $value] = explode(': ', trim($data), 2);

		if (is_string($value) && strlen($value) > 0 && substr($value, 0, 1) === '"' && substr($value, -1) === '"')
		{
			$value = trim($value, '"');
		}

		$this->response->setHeader($header, is_numeric($value) ? (int) $value : $value);

		return $strlen;
	}

	/**
	 * Parse result from Response
	 *
	 * @param   Response|null  $response  Response from HTTP call
	 *
	 * @return  SimpleXMLElement
	 * @since   9.2.1
	 */
	private function parseResponse(Response $response = null)
	{
		if (is_null($response))
		{
			throw new ApiException('Response should not be null.');
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

	/**
	 * Get error message from Response
	 *
	 * @param   Response  $rawResponse       Response
	 * @param   string    $alternativeError  Alternative error message
	 *
	 * @return  string
	 *
	 * @throws  ApiException
	 * @since   9.2.1
	 */
	private function getErrorMessage(Response $rawResponse, string $alternativeError = 'Unknown error.')
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

	/**
	 * Create resource name
	 *
	 * @param   string  $containerName  Container name
	 * @param   string  $blobName       Blob name
	 *
	 * @return  string
	 * @since   9.2.1
	 */
	private function createResourceName(string $containerName = '', string $blobName = ''): string
	{
		if ($blobName === '')
		{
			return $containerName;
		}

		if ($containerName === '' || $containerName === '$root')
		{
			return $blobName;
		}

		return $containerName . '/' . $blobName;
	}

	/**
	 * Is valid container name?
	 *
	 * @param   string  $containerName  Container name
	 *
	 * @return  boolean
	 * @since   9.2.1
	 *
	 * @see     https://docs.microsoft.com/en-us/rest/api/storageservices/Naming-and-Referencing-Containers--Blobs--and-Metadata
	 */
	private function isValidContainerName($containerName = '')
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
	 * Perform a request to Windows Azure.
	 *
	 * @param   string  $verb         The HTTP verb (GET, POST, PUT, DELETE, ...)
	 * @param   string  $path         The path to the BLOB object
	 * @param   string  $queryString  The query string to append to the path when constructing the URL
	 * @param   array   $headers      A dictionary of HTTP headers
	 *
	 * @return  Response
	 * @since   9.2.1
	 */
	private function performRequest(string $verb, string $path, string $queryString, array $headers = []): Response
	{
		$path        = '/' . ltrim($path, '/');
		$path        = $this->urlencode($path);
		$queryString = $this->urlencode($queryString);
		$url         = $this->getBaseUrl() . $path . $queryString;
		$ch          = curl_init($url);

		$this->applyProxySettingsToCurl($ch);

		$options                         = self::DEFAULT_CURL_OPTIONS;
		$options[CURLOPT_WRITEFUNCTION]  = [$this, '__responseWriteCallback'];
		$options[CURLOPT_HEADERFUNCTION] = [$this, '__responseHeaderCallback'];

		// Try to use at least TLS 1.2. Requires cURL 7.34.0 or later.
		if (defined('CURLOPT_SSLVERSION') && defined('CURL_SSLVERSION_TLSv1_2'))
		{
			$options[CURLOPT_SSLVERSION] = CURL_SSLVERSION_TLSv1_2;
		}

		// Do I have explicit cURL options to add?
		if (isset($headers['curl-options']) && is_array($headers['curl-options']))
		{
			// We can't use array_merge since we have integer keys and array_merge reassigns them :(
			foreach ($headers['curl-options'] as $k => $v)
			{
				$options[$k] = $v;
			}

			unset($headers['curl-options']);
		}

		// Handle files
		$file         = $headers['file'] ?? null;
		$this->fp     = $headers['fp'] ?? null;
		$fileMode     = $headers['file_mode'] ?? null;
		$explicitPost = $headers['explicit_post'] ?? null;

		foreach (['file', 'fp', 'file_mode', 'explicit_post'] as $k)
		{
			if (isset($headers[$k]))
			{
				unset($headers[$k]);
			}
		}

		if (($this->fp === null) && !empty($file))
		{
			$fileMode = $fileMode ?: ($verb == 'GET' ? 'w' : 'r');

			$this->fp = @fopen($file, $fileMode);
		}

		// Set up additional options
		if ($verb == 'GET' && $this->fp)
		{
			unset($options[CURLOPT_WRITEFUNCTION]);

			$options[CURLOPT_HTTPGET]        = true;
			$options[CURLOPT_HEADER]         = false;
			$options[CURLOPT_FILE]           = $this->fp;
			$options[CURLOPT_BINARYTRANSFER] = true;
		}
		elseif ($verb == 'GET')
		{
			$options[CURLOPT_HTTPGET] = true;
		}
		elseif ($verb == 'POST')
		{
			$options[CURLOPT_POST] = true;

			if ($explicitPost)
			{
				$options[CURLOPT_POSTFIELDS] = $explicitPost;
			}
			// This is required for some broken servers, e.g. SiteGround
			else
			{
				$options[CURLOPT_POSTFIELDS] = '';
			}
		}
		elseif ($verb == 'PUT' && $this->fp)
		{
			$options[CURLOPT_PUT]    = true;
			$options[CURLOPT_INFILE] = $this->fp;

			if ($file)
			{
				clearstatcache();
				$options[CURLOPT_INFILESIZE] = @filesize($file);
			}
			else
			{
				$options[CURLOPT_INFILESIZE] = strlen(stream_get_contents($this->fp));
			}

			fseek($this->fp, 0);
		}
		else
		{
			$options[CURLOPT_CUSTOMREQUEST] = $verb;

			if ($explicitPost)
			{
				$options[CURLOPT_POSTFIELDS] = $explicitPost;
			}
			elseif ($verb === 'HEAD')
			{
				/** @see http://stackoverflow.com/questions/770179/php-curl-head-request-takes-a-long-time-on-some-sites */
				$options[CURLOPT_NOBODY] = true;
			}
		}

		// Sign and apply headers
		$headers['x-ms-version'] = self::API_VERSION;
		$requestHeaders          = $this->credentials->signRequestHeaders($verb, $path, $queryString, $headers);

		$requestHeaders              = array_map(function ($k, $v) {
			return "$k:$v";
		}, array_keys($requestHeaders), array_values($requestHeaders));
		$options[CURLOPT_HTTPHEADER] = $requestHeaders;

		@curl_setopt_array($ch, $options);

		$this->response = new Response();

		if (curl_exec($ch))
		{
			$this->response->code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		}
		else
		{
			$this->response->error = new Response\Error(
				curl_errno($ch),
				curl_error($ch),
				$url
			);
		}

		@curl_close($ch);

		// Close open file pointers
		if (($this->fp !== false) && is_resource($this->fp))
		{
			$this->conditionalFileClose($this->fp);

			if ($this->response->getCode() > 399 && !empty($file) && ($verb == 'GET'))
			{
				@unlink($file);
			}
		}

		$this->fp = null;

		return $this->response;
	}

	/**
	 * (Partial) URL encode function
	 *
	 * @param   string  $value  Value to encode
	 *
	 * @return  string Encoded value
	 * @since   9.2.1
	 */
	private function urlencode(string $value): string
	{
		return str_replace(' ', '%20', $value);
	}

	/**
	 * Get base URL for creating requests
	 *
	 * @return  string
	 * @since   9.2.1
	 */
	private function getBaseUrl(): string
	{
		$schema = $this->useSSL ? 'https://' : 'http://';

		if ($this->credentials->isUsePathStyleUri())
		{
			return $schema . 'blob.' . $this->endPoint . '/' . $this->credentials->getAccountName();
		}

		return $schema . $this->credentials->getAccountName() . '.' . 'blob.' . $this->endPoint;
	}

	/**
	 * Extract a header value, case-insensitive
	 *
	 * @param   array        $headers  The dictionary of headers
	 * @param   string       $key      The key to extract, case-insensitive
	 * @param   string|null  $default  The default value to return if the key is missing
	 *
	 * @return  string|null
	 * @since   9.2.1
	 */
	private function extractHeader(array $headers, string $key, ?string $default = null): ?string
	{
		static $convertedHeaders = [];

		if (md5(serialize($convertedHeaders)) != md5(serialize($headers)))
		{
			$convertedHeaders = array_combine(
				array_map('strtolower', array_keys($headers)),
				array_values($headers)
			);
		}

		return $convertedHeaders[strtolower($key)] ?? $default;
	}
}