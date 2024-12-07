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

use Akeeba\Engine\Postproc\Connector\Backblaze\AccountInformation;
use Akeeba\Engine\Postproc\Connector\Backblaze\BucketInformation;
use Akeeba\Engine\Postproc\Connector\Backblaze\Exception\APIError;
use Akeeba\Engine\Postproc\Connector\Backblaze\Exception\cURLError;
use Akeeba\Engine\Postproc\Connector\Backblaze\Exception\InvalidJSON;
use Akeeba\Engine\Postproc\Connector\Backblaze\Exception\NotAllowed;
use Akeeba\Engine\Postproc\Connector\Backblaze\Exception\UnexpectedHTTPStatus;
use Akeeba\Engine\Postproc\Connector\Backblaze\FileInformation;
use Akeeba\Engine\Postproc\Connector\Backblaze\UploadURL;
use Akeeba\Engine\Postproc\ProxyAware;
use Akeeba\Engine\Util\FileCloseAware;
use DomainException;
use OutOfBoundsException;
use RuntimeException;

/**
 * Backblaze B2 API connector
 */
class Backblaze
{
	use FileCloseAware;
	use ProxyAware;

	/** The API entry point URL, only used to retrieve the authorization token */
	public const apiURL = "https://api.backblazeb2.com/b2api/v1/";

	/** @var  string  The Backblaze B2 Account ID */
	private $accountId;

	/** @var  AccountInformation  Account information returned from authorizeAccount */
	private $accountInformation;

	/** @var  string  The Backblaze B2 Application Key */
	private $applicationKey;

	/** @var  BucketInformation[]  A list of the buckets in this account, used by getBucketId */
	private $buckets;

	/**
	 * Default cURL options
	 *
	 * @var  array
	 */
	private $defaultOptions = [
		CURLOPT_SSL_VERIFYPEER => true,
		CURLOPT_SSL_VERIFYHOST => 2,
		CURLOPT_VERBOSE        => false,
		CURLOPT_HEADER         => false,
		CURLINFO_HEADER_OUT    => false,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_CAINFO         => AKEEBA_CACERT_PEM,
	];

	/**
	 * Creates a new Backblaze B2 API Connector object.
	 *
	 * @param   string  $accountId       The Backblaze B2 Account ID
	 * @param   string  $applicationKey  The Backblaze B2 Application Key
	 */
	public function __construct($accountId, $applicationKey)
	{
		$this->accountId      = $accountId;
		$this->applicationKey = $applicationKey;
	}

	/**
	 * Cancel a multipart upload
	 *
	 * @param   string  $fileId  The fileId returned by startUpload
	 *
	 * @return  FileInformation  The information of the file being canceled
	 */
	public function cancelUpload($fileId)
	{
		if (!$this->getAccountInformation()->allowed->canWriteFiles())
		{
			throw new NotAllowed('Writing to files');
		}

		$apiUrl       = $this->getApiUrl();
		$explicitPost = [
			'fileId' => $fileId,
		];
		$additional   = [
			'headers' => [
				'Accept: application/json',
			],
		];
		$apiReturn    = $this->fetch('POST', $apiUrl, 'b2api/v1/b2_cancel_large_file', $additional, $explicitPost);

		return new FileInformation($apiReturn);
	}

	/**
	 * Deletes a specific version of a file, given a file ID.
	 *
	 * @param   string  $fileName  The filename to delete all versions of. Can be a path.
	 * @param   string  $fileId    The file ID to delete.
	 *
	 * @return  void
	 */
	public function deleteByFileId($fileName, $fileId)
	{
		if (!$this->getAccountInformation()->allowed->canDeleteFiles())
		{
			throw new NotAllowed('Deleting files');
		}

		$apiUrl     = $this->getApiUrl();
		$body       = [
			'fileName' => $fileName,
			'fileId'   => $fileId,
		];
		$additional = [
			'headers' => [
				'Accept: application/json',
			],
		];

		$this->fetch('POST', $apiUrl, '/b2api/v1/b2_delete_file_version', $additional, json_encode($body));
	}

	/**
	 * Deletes all versions of a file, given a filename. Uses getFileVersions internally.
	 *
	 * @param   string  $bucketId  The bucket ID. Use getBucketId.
	 * @param   string  $fileName  The filename to delete all versions of. Can be a path.
	 *
	 * @return  void
	 */
	public function deleteByFileName($bucketId, $fileName)
	{
		if (!$this->getAccountInformation()->allowed->canDeleteFiles())
		{
			throw new NotAllowed('Deleting files');
		}

		$files = $this->getFileVersions($bucketId, $fileName);

		if (empty($files))
		{
			return;
		}

		foreach ($files as $file)
		{
			$this->deleteByFileId($file->fileName, $file->fileId);
		}
	}

	/**
	 * Download a file when you know its name
	 *
	 * @param   string  $bucketName  The name of the bucket you are downloading from
	 * @param   string  $fileName    The path of the file in the bucket you are downloading
	 * @param   string  $localFile   The path of the file in your local filesystem (download target)
	 * @param   array   $headers     HTTP headers to pass, see the link below
	 *
	 * @return  void
	 *
	 * @see https://www.backblaze.com/b2/docs/b2_download_file_by_name.html
	 */
	public function downloadFile($bucketName, $fileName, $localFile, $headers = [])
	{
		if (!$this->getAccountInformation()->allowed->canReadFiles())
		{
			throw new NotAllowed('Reading files');
		}

		if (!$this->getAccountInformation()->allowed->isBucketAllowed($bucketName))
		{
			throw new NotAllowed("Accessing the $bucketName bucket");
		}

		if (!$this->getAccountInformation()->allowed->isPrefixAllowed($fileName))
		{
			throw new NotAllowed("Accessing the $fileName file; the prefix (directory) is not allowed)");
		}

		$accountInfo = $this->getAccountInformation();
		$url         = rtrim($accountInfo->downloadUrl, '/') . '/';
		$relativeUrl = 'file/' . $bucketName . '/' . ltrim($fileName, '/');

		$this->fetch('GET', $url, $relativeUrl, [
			'headers'   => $headers,
			'file'      => $localFile,
			'file_mode' => 'wb',
		]);
	}

	/**
	 * Download a file when you know its ID
	 *
	 * @param   string  $fileId     The file ID returned when uploading or by getFileVersions
	 * @param   string  $localFile  The path of the file in your local filesystem (download target)
	 * @param   array   $headers    HTTP headers to pass, see the link below
	 *
	 * @return  void
	 *
	 * @see https://www.backblaze.com/b2/docs/b2_download_file_by_id.html
	 */
	public function downloadFileById($fileId, $localFile, $headers = [])
	{
		if (!$this->getAccountInformation()->allowed->canReadFiles())
		{
			throw new NotAllowed('Reading files');
		}

		$accountInfo = $this->getAccountInformation();
		$url         = rtrim($accountInfo->downloadUrl, '/') . '/';
		$relativeUrl = 'api/b2_download_file_by_id?fileId=' . $fileId;

		$this->fetch('GET', $url, $relativeUrl, [
			'headers'   => $headers,
			'file'      => $localFile,
			'file_mode' => 'wb',
		]);
	}

	/**
	 * Finishes a multipart file upload.
	 *
	 * @param   string    $fileId       The ID of the file being uploaded
	 * @param   string[]  $sha1PerPart  List of SHA-1 sums per uploaded part. First part at array index 0.
	 *
	 * @return  FileInformation  Note that you should not expect to get the SHA-1 of the entire file, per API docs.
	 */
	public function finishUpload($fileId, $sha1PerPart)
	{
		if (!$this->getAccountInformation()->allowed->canWriteFiles())
		{
			throw new NotAllowed('Writing to files');
		}

		$apiUrl       = $this->getApiUrl();
		$explicitPost = json_encode([
			'fileId'        => $fileId,
			'partSha1Array' => $sha1PerPart,
		]);
		$additional   = [
			'headers' => [
				'Accept: application/json',
			],
		];
		$apiReturn    = $this->fetch('POST', $apiUrl, 'b2api/v1/b2_finish_large_file', $additional, $explicitPost);

		return new FileInformation($apiReturn);
	}

	/**
	 * Get the account information stored in this connector API object
	 *
	 * @return  AccountInformation
	 */
	public function getAccountInformation()
	{
		if (empty($this->accountInformation))
		{
			$this->authorizeAccount();
		}

		if (!$this->accountInformation->isValid())
		{
			$this->authorizeAccount();
		}

		return $this->accountInformation;
	}

	/**
	 * Reinitialize the connector API object with a stored account information object. If the object is out of date or
	 * you pass null we will retrieve a new AccountInformation object through the B2 API.
	 *
	 * @param   AccountInformation  $accountInformation
	 */
	public function setAccountInformation($accountInformation)
	{
		if (is_null($accountInformation) || (!$accountInformation->isValid()))
		{
			$this->authorizeAccount();

			return;
		}

		$this->accountInformation = $accountInformation;
	}

	/**
	 * Get the bucket ID, required for use in the API, from the unique bucket name which the end user knows and sees in
	 * the BackBlaze B2 interface.
	 *
	 * @param   string  $name          The name of the bucket you want the ID for
	 * @param   bool    $forceRefresh  This method caches the list of buckets. Pass true to force reloading this list
	 *                                 from BackBlaze B2 (useful if you just added a bucket)
	 *
	 * @return  string  The bucket ID
	 */
	public function getBucketId($name, $forceRefresh = false)
	{
		if ($this->getAccountInformation()->allowed->bucketName == $name)
		{
			return $this->getAccountInformation()->allowed->bucketId;
		}

		if (empty($this->buckets) || $forceRefresh)
		{
			$this->buckets = $this->listBuckets();
		}

		foreach ($this->buckets as $bucket)
		{
			if ($bucket->bucketName == $name)
			{
				return $bucket->bucketId;
			}
		}

		throw new DomainException(sprintf('Bucket %s not found under this BackBlaze B2 account', $name));
	}

	/**
	 * Returns the available file versions for a given file path.
	 *
	 * @param   string  $bucketId  The bucket ID. Use getBucketId.
	 * @param   string  $fileName  The filename to return versions for. Can be a path.
	 *
	 * @return  FileInformation[]  The FileInformation objects for each file version.
	 */
	public function getFileVersions($bucketId, $fileName)
	{
		if (!$this->getAccountInformation()->allowed->canReadFiles())
		{
			throw new NotAllowed('Reading files');
		}

		$apiUrl = $this->getApiUrl();
		$body   = [
			'bucketId' => $bucketId,
			'prefix'   => $fileName,
		];

		$additional = [
			'headers' => [
				'Accept: application/json',
			],
		];

		$apiReturn = $this->fetch('POST', $apiUrl, '/b2api/v1/b2_list_file_versions', $additional, json_encode($body));
		$return    = [];

		foreach ($apiReturn['files'] as $file)
		{
			// If no fileName is returned that's a folder
			if (empty($file['fileName']))
			{
				continue;
			}

			// The API returns versions for all files, starting with the one we requested. Hence the filtering here.
			if ($file['fileName'] != $fileName)
			{
				continue;
			}

			$return[] = new FileInformation($file);
		}

		return $return;
	}

	/**
	 * Get the upload URL, including the special upload authorization token, for multipart file uploads.
	 *
	 * This is the second step. The next steps are uploadPart and either finishUpload to finalize the upload, or
	 * cancelUpload to abort the multipart upload process.
	 *
	 * @param   string  $fileId
	 *
	 * @return  UploadURL
	 */
	public function getPartUploadUrl($fileId)
	{
		if (!$this->getAccountInformation()->allowed->canWriteFiles())
		{
			throw new NotAllowed('Writing to files');
		}

		$apiUrl       = $this->getApiUrl();
		$explicitPost = json_encode([
			'fileId' => $fileId,
		]);
		$additional   = [
			'headers' => [
				'Accept: application/json',
			],
		];

		$apiReturn = $this->fetch('POST', $apiUrl, 'b2api/v1/b2_get_upload_part_url', $additional, $explicitPost);

		return new UploadURL($apiReturn);
	}

	/**
	 * Create a singed URL which allows anyone to download a file for a specific period of time.
	 *
	 * @param   string  $bucketName  The name of the bucket you are downloading from
	 * @param   string  $fileName    The path of the file in the bucket you are downloading
	 * @param   int     $expiresIn   How many seconds will the URL be valid for. Default is the maximum, one week.
	 *
	 * @return  string  The signed download URL which can be shared
	 */
	public function getSignedUrl($bucketName, $fileName, $expiresIn = 604800)
	{
		if (!$this->getAccountInformation()->allowed->canReadFiles())
		{
			throw new NotAllowed('Reading files');
		}

		// Let's get the bucket ID
		$bucketId = $this->getBucketId($bucketName);

		// $expiresIn must be between 1 and 604800 seconds
		$expiresIn = min($expiresIn, 604800);
		$expiresIn = max(1, $expiresIn);

		// Use b2_get_download_authorization to get a temporary authorization code
		$apiUrl     = $this->getApiUrl();
		$body       = [
			'bucketId'               => $bucketId,
			'fileNamePrefix'         => $fileName,
			'validDurationInSeconds' => $expiresIn,
		];
		$additional = [
			'headers' => [
				'Accept: application/json',
			],
		];

		$authResponse = $this->fetch('POST', $apiUrl, '/b2api/v1/b2_get_download_authorization', $additional, json_encode($body));

		// Construct the signed URL
		$accountInfo = $this->getAccountInformation();
		$url         = rtrim($accountInfo->downloadUrl, '/');
		$url         .= '/file/' . $bucketName . '/' . ltrim($fileName, '/');
		$url         .= '?Authorization=' . $authResponse['authorizationToken'];

		return $url;
	}

	/**
	 * Gets the URL for the upload API of the given bucket
	 *
	 * @param   string  $bucketId  The ID of the bucket (NOT the name!). Use getBucketId to retrieve it.
	 *
	 * @return  UploadURL  The Upload URL object
	 */
	public function getUploadUrl($bucketId)
	{
		if (!$this->getAccountInformation()->allowed->canWriteFiles())
		{
			throw new NotAllowed('Writing to files');
		}

		$apiUrl       = $this->getApiUrl();
		$explicitPost = json_encode([
			'bucketId' => $bucketId,
		]);
		$additional   = [
			'headers' => [
				'Accept: application/json',
			],
		];

		$apiReturn = $this->fetch('POST', $apiUrl, 'b2api/v1/b2_get_upload_url', $additional, $explicitPost);

		return new UploadURL($apiReturn);
	}

	/**
	 * List all of the buckets in the account
	 *
	 * @return  BucketInformation[]
	 *
	 * @see  https://www.backblaze.com/b2/docs/b2_list_buckets.html
	 */
	public function listBuckets()
	{
		if (!$this->getAccountInformation()->allowed->canListBuckets())
		{
			throw new NotAllowed('Retrieving a list of your BackBlaze B2 buckets');
		}

		$apiUrl       = $this->getApiUrl();
		$explicitPost = json_encode([
			'accountId' => $this->getAccountInformation()->accountId,
		]);
		$additional   = [
			'headers' => [
				'Accept: application/json',
			],
		];

		$apiReturn = $this->fetch('POST', $apiUrl, 'b2api/v1/b2_list_buckets', $additional, $explicitPost);
		$return    = [];

		foreach ($apiReturn['buckets'] as $bucket)
		{
			$return[] = new BucketInformation($bucket);
		}

		return $return;
	}

	/**
	 * Start a multipart upload of a large file.
	 *
	 * This is the first step. The next step is getPartUploadUrl.
	 *
	 * @param   string  $bucketId     The ID of the bucket where the file is being uploaded to
	 * @param   string  $remoteFile   The path of the file in Backblaze
	 * @param   string  $contentType  MIME content type of the file
	 *
	 * @return  FileInformation
	 */
	public function startUpload($bucketId, $remoteFile, $contentType = 'application/octet-stream')
	{
		if (!$this->getAccountInformation()->allowed->canWriteFiles())
		{
			throw new NotAllowed('Writing to files');
		}

		if (!$this->getAccountInformation()->allowed->isPrefixAllowed($remoteFile))
		{
			throw new NotAllowed("Accessing the $remoteFile file; the prefix (directory) is not allowed)");
		}

		$apiUrl       = $this->getApiUrl();
		$explicitPost = json_encode([
			'bucketId'    => $bucketId,
			'fileName'    => $remoteFile,
			'contentType' => $contentType,
		]);
		$additional   = [
			'headers' => [
				'Accept: application/json',
			],
		];

		$apiReturn = $this->fetch('POST', $apiUrl, 'b2api/v1/b2_start_large_file', $additional, $explicitPost);

		return new FileInformation($apiReturn);
	}

	/**
	 * Upload a file. Automatically determines single- or multi-part upload based on the size of the file, the part
	 * size and the absolute minimum part size that's possible.
	 *
	 * @param   string  $bucketId     The ID of the bucket. Use getBucketId.
	 * @param   string  $remoteFile   The path of the uploaded file in B2
	 * @param   string  $localFile    Path to the file to upload
	 * @param   int     $partSize     Part size for multipart uploads, default 5M
	 * @param   string  $contentType  MIME content type of the uploaded file
	 *
	 * @return  FileInformation
	 */
	public function uploadFile($bucketId, $remoteFile, $localFile, $partSize = 5242880, $contentType = 'application/octet-stream')
	{
		if (!$this->getAccountInformation()->allowed->canWriteFiles())
		{
			throw new NotAllowed('Writing to files');
		}

		clearstatcache(false, $localFile);
		$fileSize = @filesize($localFile);

		$minPartSize = $this->getAccountInformation()->absoluteMinimumPartSize;

		if (($fileSize < $minPartSize) || ($partSize < $minPartSize))
		{
			return $this->uploadSingleFile($bucketId, $remoteFile, $localFile, $contentType);
		}

		/**
		 * Backblaze supports up to 10000 parts. We have to try increasing the part size until we're sure our  file
		 * will upload in a number of parts that's less than that.
		 */
		while ($fileSize / $partSize > 10000)
		{
			// Increase by 5M in each step
			$partSize += 5242880;
		}

		return $this->uploadLargeFile($bucketId, $remoteFile, $localFile, $partSize, $contentType);
	}

	/**
	 * Upload a file using a multipart upload.
	 *
	 * @param   string  $bucketId     The ID of the bucket. Use getBucketId.
	 * @param   string  $remoteFile   The path of the uploaded file in B2
	 * @param   string  $localFile    Path to the file to upload
	 * @param   int     $partSize     Part size for multipart uploads, default 5M
	 * @param   string  $contentType  MIME content type of the uploaded file
	 *
	 * @return  FileInformation
	 */
	public function uploadLargeFile($bucketId, $remoteFile, $localFile, $partSize = 5242880, $contentType = 'application/octet-stream')
	{
		if (!$this->getAccountInformation()->allowed->canWriteFiles())
		{
			throw new NotAllowed('Writing to files');
		}

		if (!$this->getAccountInformation()->allowed->isPrefixAllowed($remoteFile))
		{
			throw new NotAllowed("Accessing the $remoteFile file; the prefix (directory) is not allowed)");
		}

		$fileStartInfo = $this->startUpload($bucketId, $remoteFile, $contentType);
		$uploadUrl     = $this->getPartUploadUrl($fileStartInfo->fileId);
		$partNumber    = 0;
		$sha1Array     = [];

		while (true)
		{
			try
			{
				$partInfo    = $this->uploadPart($uploadUrl, $localFile, ++$partNumber, $partSize);
				$sha1Array[] = $partInfo->contentSha1;
			}
			catch (OutOfBoundsException $e)
			{
				// I am done uploading parts
				break;
			}
		}

		return $this->finishUpload($fileStartInfo->fileId, $sha1Array);
	}

	/**
	 * Upload a part of a large file.
	 *
	 * @param   UploadURL  $uploadUrl   The upload URL information returned by getPartUploadUrl
	 * @param   string     $localFile   The local file to read from. Must be readable.
	 * @param   int        $partNumber  The part number, 1 to 10,000.
	 * @param   int        $partSize    The part size in bytes.
	 *
	 * @return  FileInformation  Keep that in an array. You'll need to pass that to finishUpload.
	 */
	public function uploadPart(UploadURL $uploadUrl, $localFile, $partNumber, $partSize = 5242880)
	{
		if (!$this->getAccountInformation()->allowed->canWriteFiles())
		{
			throw new NotAllowed('Writing to files');
		}

		clearstatcache(false, $localFile);
		$filesize = filesize($localFile);


		// The offset to read from the file. Remember: part numbers start at 1, file offsets start at 0.
		$offset = ($partNumber - 1) * $partSize;

		// Sanity check: does this part even exist?
		if ($filesize <= $offset)
		{
			throw new OutOfBoundsException(sprintf("Cannot read part %d of file %s. The file only has %d bytes, requested offset is at %d bytes.", $partNumber, $localFile, $filesize, $offset));
		}

		// Read the part off the file and calculate the information required by Backblaze
		$fp = @fopen($localFile, 'r');

		if ($fp === false)
		{
			throw new RuntimeException(sprintf('Failed to multipart upload file %s to BackBlaze: cannot open file for reading', $localFile));
		}

		if (fseek($fp, $offset) == -1)
		{
			$this->conditionalFileClose($fp);

			throw new RuntimeException(sprintf('Failed to multipart upload file %s to BackBlaze: cannot seek to offset %d', $localFile, $offset));
		}

		$data = fread($fp, $partSize);

		if ($data === false)
		{
			$this->conditionalFileClose($fp);

			throw new RuntimeException(sprintf('Failed to multipart upload file %s to BackBlaze: cannot read from file at offset %d, data length %d', $localFile, $offset, $partSize));
		}

		$this->conditionalFileClose($fp);

		$sha1          = sha1($data);
		$contentLength = strlen($data);
		$additional    = [
			'headers' => [
				'Accept: application/json',
				sprintf('Content-Length: %s', $contentLength),
				sprintf('X-Bz-Part-Number: %d', $partNumber),
				sprintf('X-Bz-Content-Sha1: %s', $sha1),
				// WARNING: Uploads use a different authorization token than the API itself!
				sprintf('Authorization: %s', $uploadUrl->authorizationToken),
			],
		];

		$apiReturn = $this->fetch('POST', $uploadUrl->uploadUrl, '', $additional, $data);

		return new FileInformation($apiReturn);
	}

	/**
	 * Single part upload of a file to BackBlaze B2
	 *
	 * @param   string  $bucketId     The ID of the bucket to upload to. Use getBucketId to fetch it.
	 * @param   string  $remoteFile   The path of the file in the bucket.
	 * @param   string  $localFile    The path to the local file to upload.
	 * @param   string  $contentType  The MIME content type of the uploaded file.
	 *
	 * @return  FileInformation
	 */
	public function uploadSingleFile($bucketId, $remoteFile, $localFile, $contentType = 'application/octet-stream')
	{
		if (!$this->getAccountInformation()->allowed->canWriteFiles())
		{
			throw new NotAllowed('Writing to files');
		}

		if (!$this->getAccountInformation()->allowed->isPrefixAllowed($remoteFile))
		{
			throw new NotAllowed("Accessing the $remoteFile file; the prefix (directory) is not allowed)");
		}

		$uploadUrl = $this->getUploadUrl($bucketId);

		clearstatcache(false, $localFile);
		$sha1          = sha1_file($localFile);
		$contentLength = filesize($localFile);

		$additional = [
			'headers' => [
				'Accept: application/json',
				sprintf('X-Bz-File-Name: %s', $remoteFile),
				sprintf('Content-Type: %s', $contentType),
				sprintf('Content-Length: %s', $contentLength),
				sprintf('X-Bz-Content-Sha1: %s', $sha1),
				// WARNING: Uploads use a different authorization token than the API itself!
				sprintf('Authorization: %s', $uploadUrl->authorizationToken),
			],
			'file'    => $localFile,
		];

		$apiReturn = $this->fetch('POST', $uploadUrl->uploadUrl, '', $additional);

		return new FileInformation($apiReturn);
	}

	/**
	 * Uses the account ID and application key to retrieve a (temporary) authorization token which will be used in all
	 * subsequent operations. Furthermore, it will retrieve information regarding the account-specific API URLs, the
	 * account-specific download URLs and the supported part sizes. The information is retrieved as an immutable
	 * AccountInformation object which you can retrieve through getAccountInformation.
	 *
	 * If you have already run this and have an AccountInformation object you can use the applyAccountInformation
	 * method to re-initialize this object
	 *
	 * @return void
	 */
	protected function authorizeAccount()
	{
		$additional = [
			'headers' => [
				sprintf('Authorization: Basic %s', base64_encode($this->accountId . ':' . $this->applicationKey)),
				'Accept: application/json',
			],
		];

		$apiReturn = $this->fetch('GET', static::apiURL, 'b2_authorize_account', $additional);

		$this->accountInformation = new AccountInformation($apiReturn);
	}

	/**
	 * Execute an API call
	 *
	 * @param   string  $method        The HTTP method
	 * @param   string  $baseUrl       The base URL. Use one of self::rootUrl or self::contentRootUrl
	 * @param   string  $relativeUrl   The relative URL to ping
	 * @param   array   $additional    Additional parameters
	 * @param   mixed   $explicitPost  Passed explicitly to POST requests if set, otherwise $additional is passed.
	 *
	 * @return  array
	 * @throws  RuntimeException
	 *
	 */
	protected function fetch($method, $baseUrl, $relativeUrl, array $additional = [], $explicitPost = null)
	{
		// Get full URL, if required
		$url = $relativeUrl;

		if (substr($relativeUrl, 0, 6) != 'https:')
		{
			$url = $baseUrl . ltrim($relativeUrl, '/');
		}

		// Should I expect a specific header?
		$expectHttpStatus = [];

		if (isset($additional['expect-status']))
		{
			$expectHttpStatus = $additional['expect-status'];

			if (!is_array($expectHttpStatus))
			{
				$expectHttpStatus = [$expectHttpStatus];
			}

			unset($additional['expect-status']);
		}

		// Am I told to not parse the result?
		$noParse = false;

		if (isset($additional['no-parse']))
		{
			$noParse = $additional['no-parse'];
			unset ($additional['no-parse']);
		}

		// Am I told not to follow redirections?
		$followRedirect = true;

		if (isset($additional['follow-redirect']))
		{
			$followRedirect = $additional['follow-redirect'];
			unset ($additional['follow-redirect']);
		}

		// Initialise and execute a cURL request
		$ch = curl_init($url);

		$this->applyProxySettingsToCurl($ch);

		// Get the default options array
		$options = $this->defaultOptions;

		// Do I have explicit cURL options to add?
		if (isset($additional['curl-options']) && is_array($additional['curl-options']))
		{
			// We can't use array_merge since we have integer keys and array_merge reassigns them :(
			foreach ($additional['curl-options'] as $k => $v)
			{
				$options[$k] = $v;
			}
		}

		// Set up custom headers
		$headers                = [];
		$hasAuthorizationHeader = false;

		if (isset($additional['headers']))
		{
			$headers = $additional['headers'];
			unset ($additional['headers']);
		}

		// Add the authorization header, if it doesn't exist
		array_walk($headers, function ($header) use (&$hasAuthorizationHeader) {
			if (substr($header, 0, 15) == 'Authorization: ')
			{
				$hasAuthorizationHeader = true;
			}
		});

		if (!$hasAuthorizationHeader)
		{
			$headers[] = 'Authorization: ' . $this->getAccountInformation()->authorizationToken;
		}

		$options[CURLOPT_HTTPHEADER] = $headers;

		// Handle files
		$file     = null;
		$fp       = null;
		$fileMode = null;

		if (isset($additional['file']))
		{
			$file = $additional['file'];
			unset ($additional['file']);
		}

		if (isset($additional['file_mode']))
		{
			$fileMode = $additional['file_mode'];
			unset ($additional['file_mode']);
		}

		if (!isset($additional['fp']) && !empty($file))
		{
			if (is_null($fileMode))
			{
				$fileMode = ($method == 'GET') ? 'w' : 'r';
			}

			$fp = @fopen($file, $fileMode);
		}
		elseif (isset($additional['fp']))
		{
			$fp = $additional['fp'];
			unset($additional['fp']);
		}

		// Set up additional options
		if ($method == 'GET' && $fp)
		{
			$options[CURLOPT_RETURNTRANSFER] = false;
			$options[CURLOPT_HEADER]         = false;
			$options[CURLOPT_FILE]           = $fp;
			$options[CURLOPT_BINARYTRANSFER] = true;

			if (empty($expectHttpStatus))
			{
				$expectHttpStatus = [200, 206];
			}
		}
		elseif (in_array($method, ['POST', 'PUT']) && $fp)
		{
			$options[CURLOPT_PUT]   = true;
			$options[CURLOPT_CUSTOMREQUEST] = $method;
			$options[CURLOPT_INFILE]        = $fp;

			if ($file)
			{
				clearstatcache();
				$options[CURLOPT_INFILESIZE] = @filesize($file);
			}
			else
			{
				$options[CURLOPT_INFILESIZE] = strlen(stream_get_contents($fp));
			}

			fseek($fp, 0);
		}
		elseif ($method == 'POST')
		{
			$options[CURLOPT_POST] = true;

			if ($explicitPost)
			{
				$options[CURLOPT_POSTFIELDS] = $explicitPost;
			}
			elseif (!empty($additional))
			{
				$options[CURLOPT_POSTFIELDS] = $additional;
			}
			// This is required for some broken servers, e.g. SiteGround
			else
			{
				$options[CURLOPT_POSTFIELDS] = '';
			}
		}
		else // Any other HTTP method, e.g. DELETE
		{
			$options[CURLOPT_CUSTOMREQUEST] = $method;

			if ($explicitPost)
			{
				$options[CURLOPT_POSTFIELDS] = $explicitPost;
			}
			elseif (!empty($additional))
			{
				$options[CURLOPT_POSTFIELDS] = $additional;
			}
		}

		// Set the cURL options at once
		@curl_setopt_array($ch, $options);

		// Set the follow location flag
		if ($followRedirect)
		{
			@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		}

		// Execute and parse the response
		$response     = curl_exec($ch);
		$errNo        = curl_errno($ch);
		$error        = curl_error($ch);
		$lastHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		// Close open file pointers
		$hadFile = false;

		if ($fp)
		{
			$hadFile = true;

			$this->conditionalFileClose($fp);
		}

		// Did we have a cURL error?
		if ($errNo)
		{
			throw new cURLError($errNo, $error);
		}

		// Close open file pointers
		if ($hadFile && !empty($expectHttpStatus) && !in_array($lastHttpCode, $expectHttpStatus))
		{
			if ($file && ($method == 'GET'))
			{
				@unlink($file);
			}

			throw new UnexpectedHTTPStatus($lastHttpCode);
		}

		if (!empty($expectHttpStatus) && in_array($lastHttpCode, $expectHttpStatus))
		{
			return [];
		}

		if ($noParse)
		{
			return $response;
		}

		// Parse the response
		$originalResponse = $response;
		$response         = json_decode($response, true);

		// Did we get invalid JSON data?
		if (!$response)
		{
			throw new InvalidJSON("Invalid JSON Data: $originalResponse");
		}

		unset($originalResponse);

		if (!empty($response) && ($lastHttpCode != 200))
		{
			$response = ['error' => $response];
		}

		// Did we get an error response?
		if (isset($response['error']) && is_array($response['error']))
		{
			$decodedError    = $response['error'];
			$actualException = new APIError($decodedError['code'], $decodedError['message'], 500);

			/**
			 * BackBlaze's blog post (see below) states that we need to retry getting an upload URL upon HTTP error 500
			 * or 503. However, the reality is different. We get an HTTP 200 with an error document that contains the
			 * error code literal 'service_unavailable'. In this case we'd simply throw an APIError exception and stop
			 * the upload attempt. In an effort to keep the error handling code simple (since PHP doesn't allow catching
			 * two disparate exception types in a single catch) we will simulate an HTTP exception here, attaching the
			 * actual APIException as its previous exception to facilitate troubleshooting. The UnexpectedHTTPStatus
			 * exception bubbles up to Akeeba\Engine\Postproc\Backblaze where it's intercepted, allowing us to ask for a
			 * new upload URL and continue the upload.
			 *
			 * @see https://www.backblaze.com/blog/b2-503-500-server-error/
			 * @see https://www.akeeba.com/support/32973
			 */
			if ($decodedError['code'] == 'service_unavailable')
			{
				throw new UnexpectedHTTPStatus('503 (Simulated – received API error code ‘service_unavailable’ per parent exception)', 503, $actualException);
			}

			throw $actualException;
		}

		return $response;
	}

	/**
	 * Return the API URL for all operations except file downloads. It includes the all important trailing slash which
	 * fetch() expects to be present.
	 *
	 * @return  string
	 */
	protected function getApiUrl()
	{
		$apiUrl = $this->getAccountInformation()->apiUrl;
		$apiUrl = rtrim($apiUrl, '/');

		return $apiUrl . '/';
	}

	/**
	 * Return the API URL for file download operations only. It includes the all important trailing slash which fetch()
	 * expects to be present.
	 *
	 * @return  string
	 */
	protected function getDownloadUrl()
	{
		$downloadUrl = $this->getAccountInformation()->downloadUrl;
		$downloadUrl = rtrim($downloadUrl, '/');

		return $downloadUrl . '/';
	}
}
