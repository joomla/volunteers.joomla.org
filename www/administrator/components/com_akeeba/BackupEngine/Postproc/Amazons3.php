<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Postproc;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Akeeba\Engine\Postproc\Connector\S3v4\Configuration;
use Akeeba\Engine\Postproc\Connector\S3v4\Connector;
use Akeeba\Engine\Postproc\Connector\S3v4\Input;
use Akeeba\Engine\Postproc\Exception\BadConfiguration;
use DateTime;
use Exception;
use RuntimeException;

/**
 * Amazon S3 post-processing engine
 */
class Amazons3 extends Base
{
	use ProxyAware;

	public const STORAGE_STANDARD = 0;
	public const STORAGE_REDUCED_REDUNDANCY = 1;
	public const STORAGE_STANDARD_IA = 2;
	public const STORAGE_ONEZONE_IA = 3;
	public const STORAGE_INTELLIGENT_TIERING = 4;
	public const STORAGE_GLACIER = 5;
	public const STORAGE_DEEP_ARCHIVE = 6;

	/**
	 * Used in log messages. Check out children classes to understand why we have this here.
	 *
	 * @var  string
	 */
	protected $engineLogName = 'Amazon S3';
	/**
	 * The prefix to use for volatile key storage
	 *
	 * @var  string
	 */
	protected $volatileKeyPrefix = 'volatile.postproc.amazons3.';
	/**
	 * HTTP headers. Used when trying to fetch the S3 credentials from an EC2 instance's attached role.
	 *
	 * @var  array
	 */
	protected $headers = [];
	/**
	 * Cached copy of the S3 credentials provisioned by the EC2 instance's attached role.
	 *
	 * @var  array|null
	 */
	protected $provisionedCredentials = null;
	/**
	 * The upload ID of the multipart upload in progress
	 *
	 * @var   string|null
	 */
	private $uploadId = null;
	/**
	 * The part number for the multipart upload in progress
	 *
	 * @var int|null
	 */
	private $partNumber = null;
	/**
	 * The ETags of the uploaded chunks, used to finalise the multipart upload
	 *
	 * @var  array
	 */
	private $eTags = [];

	/**
	 * Initialise the class, setting its capabilities
	 *
	 * @return  void
	 */
	public function __construct()
	{
		$this->supportsDelete            = true;
		$this->supportsDownloadToBrowser = true;
		$this->supportsDownloadToFile    = true;
	}

	final public function processPart($localFilepath, $remoteBaseName = null): bool
	{
		// Retrieve engine configuration data
		$akeebaConfig = Factory::getConfiguration();

		// Load multipart information from temporary storage
		$this->uploadId = $akeebaConfig->get($this->volatileKeyPrefix . 'uploadId', null);

		// Get the configuration parameters
		$engineConfig     = $this->getEngineConfiguration();
		$bucket           = $engineConfig['bucket'] ?? '';
		$disableMultipart = $engineConfig['disableMultipart'] ?? false;
		$storageType      = $engineConfig['rrs'] ?? self::STORAGE_STANDARD;

		// The directory is a special case. First try getting a cached directory
		$directory        = $akeebaConfig->get('volatile.postproc.directory', null);

		// If there is no cached directory, fetch it from the engine configuration
		if (is_null($directory))
		{
			$directory        = $engineConfig['directory'] ?? '';

			// The very first time we deal with the directory we need to process it.
			$directory = str_replace('\\', '/', $directory);
			$directory = rtrim($directory, '/');
			$directory = trim($directory);
			$directory = ltrim(Factory::getFilesystemTools()->TranslateWinPath($directory), '/');
			$directory = Factory::getFilesystemTools()->replace_archive_name_variables($directory);

			// Store the parsed directory in temporary storage
			$akeebaConfig->set('volatile.postproc.directory', $directory);
		}

		// Remove any slashes from the bucket
		$bucket = str_replace('/', '', $bucket);

		// Get the file size and disable multipart uploads for files shorter than 5Mb
		$fileSize = @filesize($localFilepath);

		if ($fileSize <= 5242880)
		{
			$disableMultipart = true;
		}

		// Calculate relative remote filename
		$remoteKey = empty($remoteBaseName) ? basename($localFilepath) : $remoteBaseName;

		if (!empty($directory) && ($directory != '/'))
		{
			$remoteKey = $directory . '/' . $remoteKey;
		}

		// Store the absolute remote path in the class property
		$this->remotePath = $remoteKey;

		// Create the S3 client instance
		/** @var Connector $connector */
		$connector = $this->getConnector();

		// Are we already processing a multipart upload or asked to perform a multipart upload?
		if (!empty($this->uploadId) || !$disableMultipart)
		{
			$this->partNumber = $akeebaConfig->get($this->volatileKeyPrefix . 'partNumber', null);
			$this->eTags      = $akeebaConfig->get($this->volatileKeyPrefix . 'eTags', '{}');
			$this->eTags      = json_decode($this->eTags, true);
			$this->eTags      = empty($this->eTags) ? [] : $this->eTags;

			return $this->multipartUpload($bucket, $remoteKey, $localFilepath, $connector, 'bucket-owner-full-control', $storageType);
		}

		return $this->simpleUpload($bucket, $remoteKey, $localFilepath, $connector, 'bucket-owner-full-control', $storageType);
	}

	final public function delete($path)
	{
		// Get the configuration parameters
		/** @var Connector $connector */
		$connector    = $this->getConnector();
		$engineConfig = $this->getEngineConfiguration();
		$bucket       = $engineConfig['bucket'];
		$bucket       = str_replace('/', '', $bucket);

		$connector->deleteObject($bucket, $path);
	}

	public function downloadToFile($remotePath, $localFile, $fromOffset = null, $length = null)
	{
		// Get the configuration parameters
		$engineConfig = $this->getEngineConfiguration();
		$bucket       = $engineConfig['bucket'];
		$bucket       = str_replace('/', '', $bucket);

		// Create the S3 client instance
		/** @var Connector $connector */
		$connector = $this->getConnector();
		$toOffset  = null;

		if (!is_null($fromOffset) && $length)
		{
			$toOffset = $fromOffset + $length - 1;
		}

		$connector->getObject($bucket, $remotePath, $localFile, $fromOffset, $toOffset);
	}

	public function downloadToBrowser($remotePath)
	{
		// Create the S3 client instance
		/** @var Connector $connector */
		$connector = $this->getConnector();

		// Get the configuration parameters
		$engineConfig = $this->getEngineConfiguration();
		$bucket       = $engineConfig['bucket'];
		$bucket       = str_replace('/', '', $bucket);

		// Add custom content headers so the file is always downloaded to the browser instead of read inline
		$queryParameters = [
			'response-content-type'        => 'application/octet-stream',
			'response-content-disposition' => sprintf('attachment; filename="%s"', basename($remotePath)),
		];
		$uri = $remotePath . '?' . http_build_query($queryParameters);

		return $connector->getAuthenticatedURL($bucket, $uri, 10, true);
	}

	/**
	 * Get the configuration information for this post-processing engine. Can be overridden by subclasses.
	 *
	 * @return  array
	 */
	protected function getEngineConfiguration(): array
	{
		$akeebaConfig = Factory::getConfiguration();

		$config = [
			'accessKey'           => $akeebaConfig->get('engine.postproc.amazons3.accesskey', ''),
			'secretKey'           => $akeebaConfig->get('engine.postproc.amazons3.secretkey', ''),
			'token'               => '',
			'useSSL'              => $akeebaConfig->get('engine.postproc.amazons3.usessl', 0) == 1,
			'dualStack'           => $akeebaConfig->get('engine.postproc.amazons3.dualstack', 0) == 1,
			'customEndpoint'      => $akeebaConfig->get('engine.postproc.amazons3.customendpoint', ''),
			'signatureMethod'     => $akeebaConfig->get('engine.postproc.amazons3.signature', 'v2'),
			'useLegacyPathAccess' => $akeebaConfig->get('engine.postproc.amazons3.pathaccess', '0') == 1,
			'region'              => $akeebaConfig->get('engine.postproc.amazons3.region', '')
				?: $akeebaConfig->get('engine.postproc.amazons3.custom_region', ''),
			'disableMultipart'    => $akeebaConfig->get('engine.postproc.amazons3.legacy', 0) == 1,
			'bucket'              => $akeebaConfig->get('engine.postproc.amazons3.bucket', null),
			'directory'           => $akeebaConfig->get('engine.postproc.amazons3.directory', null),
			'rrs'                 => (int) $akeebaConfig->get('engine.postproc.amazons3.rrs', self::STORAGE_STANDARD),
		];

		// No access and secret key? Try to fetch from the EC2 configuration
		if (empty($config['accessKey']) && empty($config['secretKey']))
		{
			Factory::getLog()->debug("There is no configured Access and Secret key. I will try to provision these credentials automatically. This only works when your site runs inside an EC2 instance and you have attached an IAM Role to it which allows access to the configured bucket.");
			$config = $this->provisionCredentials($config);
		}

		return $config;
	}

	final protected function makeConnector(): Connector
	{
		// Retrieve engine configuration data
		$config = $this->getEngineConfiguration();

		// Get the configuration parameters
		$accessKey           = $config['accessKey'];
		$secretKey           = $config['secretKey'];
		$useSSL              = $config['useSSL'];
		$dualStack           = $config['dualStack'];
		$customEndpoint      = $config['customEndpoint'];
		$signatureMethod     = $config['signatureMethod'];
		$region              = $config['region'];
		$useLegacyPathAccess = $config['useLegacyPathAccess'];
		$disableMultipart    = $config['disableMultipart'];
		$bucket              = $config['bucket'] ?? '';

		if ($signatureMethod == 's3')
		{
			$signatureMethod = 'v2';
		}

		Factory::getLog()->debug(sprintf(
			"%s -- Using signature method %s, %s uploads",
			$this->engineLogName, $signatureMethod, $disableMultipart ? 'single-part' : 'multipart'
		));

		// Makes sure the custom endpoint has no protocol and no trailing slash
		$customEndpoint = trim($customEndpoint);

		if (!empty($customEndpoint))
		{
			$protoPos = strpos($customEndpoint, ':\\');

			if ($protoPos !== false)
			{
				$customEndpoint = substr($customEndpoint, $protoPos + 3);
			}

			$customEndpoint = rtrim($customEndpoint, '/');

			Factory::getLog()->debug(sprintf(
				"%s -- Using custom endpoint %s", $this->engineLogName, $customEndpoint
			));
		}

		// Remove any slashes from the bucket
		$bucket = str_replace('/', '', $bucket);

		// Sanity checks
		if (!function_exists('curl_init'))
		{
			throw new BadConfiguration('cURL is not enabled, please enable it in order to post-process your archives');
		}

		if (empty($accessKey))
		{
			throw new BadConfiguration(sprintf("You have not set up your %s Access Key", $this->engineLogName));
		}

		if (empty($secretKey))
		{
			throw new BadConfiguration(sprintf("You have not set up your %s Secret Key", $this->engineLogName));
		}

		if (empty($bucket))
		{
			throw new BadConfiguration(sprintf("You have not set up your %s Bucket", $this->engineLogName));
		}

		// Prepare the configuration
		$configuration = new Configuration($accessKey ?? '', $secretKey ?? '', $signatureMethod ?? 'v2', $region ?? '');
		$configuration->setSSL($useSSL ?? true);
		$configuration->setUseDualstackUrl($dualStack ?? true);

		if (!empty($config['token']))
		{
			$configuration->setToken($config['token']);
		}

		if ($customEndpoint)
		{
			$configuration->setEndpoint($customEndpoint);
		}

		// Set path-style vs virtual hosting style access
		$configuration->setUseLegacyPathStyle($useLegacyPathAccess);

		// Return the new S3 client instance
		return new Connector($configuration);
	}

	/**
	 * Try to automatically provision the S3 credentials. The credentials are searched in the following places (the
	 * first one to be found wins):
	 *
	 * - The provisionedCredentials volatile key for this post-processing engine
	 * - The provisionedCredentials property
	 * - Querying the EC2 instance we are running under (assuming we run under an EC2 instance)
	 *
	 * If the cached provisionedCredentials have expired new ones will be fetched by querying the metadata of the
	 * underlying EC2 instance.
	 *
	 * If no provisioned credentials are found, the returned $config array is identical to the input, presumably lacking
	 * access and secret keys to connect to S3.
	 *
	 * @param   array  $config
	 *
	 * @return  array
	 */
	private function provisionCredentials(array $config): array
	{
		// First, try to fetch credentials from the volatile engine configuration
		$akeebaConfig                 = Factory::getConfiguration();
		$this->provisionedCredentials = $akeebaConfig->get($this->volatileKeyPrefix . 'provisionedCredentials', $this->provisionedCredentials);

		// I must fetch new credentials if I don't have any provisioned credentials
		$mustFetchCredentials = !is_array($this->provisionedCredentials) || empty($this->provisionedCredentials);

		if (!$mustFetchCredentials)
		{
			Factory::getLog()->debug('Cached S3 credentials were found');
		}

		// I must fetch new credentials if the provisioned credentials have already expired
		if (!$mustFetchCredentials && is_array($this->provisionedCredentials) && isset($this->provisionedCredentials['expires']) && !empty($this->provisionedCredentials['expires']))
		{
			$mustFetchCredentials = ($this->provisionedCredentials['expires'] + 30) < time();

			if ($mustFetchCredentials)
			{
				Factory::getLog()->debug('The cached S3 credentials are about to or have already expired.');
			}
		}

		if ($mustFetchCredentials)
		{
			Factory::getLog()->debug('Attempting to retrieve S3 credentials from the underlying EC2 instance (if the site is running inside an EC2 instance)');

			try
			{
				$this->provisionedCredentials = $this->getEC2RoleCredentials();
				$akeebaConfig->set($this->volatileKeyPrefix . 'provisionedCredentials', $this->provisionedCredentials);
			}
			catch (RuntimeException $e)
			{
				Factory::getLog()->debug("No Amazon S3 credentials found. Moreover, I got an error trying to detect whether this site is running inside an Amazon EC2 instance and possibly retrieve Amazon S3 credentials from the EC2 instance's role.");
				Factory::getLog()->debug($e->getMessage());

				return $config;
			}
		}

		Factory::getLog()->debug('Applying provisioned S3 credentials');

		$config['accessKey'] = $this->provisionedCredentials['access'];
		$config['secretKey'] = $this->provisionedCredentials['secret'];
		$config['token']     = $this->provisionedCredentials['token'];

		return $config;
	}

	/**
	 * Attempt to retrieve the Amazon S3 credentials from the attached Amazon EC2 instance role.
	 *
	 * This will only work if you are running Akeeba Engine in an Amazon EC2 instance with an attached role. The
	 * attached role must give access to the Amazon S3 bucket you have specified in the configuration of this post-
	 * processing engine.
	 *
	 * @return  array (access, secret, expiration)
	 *
	 * @throws  RuntimeException
	 */
	private function getEC2RoleCredentials(): array
	{
		$hasCurl = function_exists('curl_init') && function_exists('curl_exec') && function_exists('curl_close');

		if (!$hasCurl)
		{
			throw new RuntimeException('The PHP cURL module is not activated or installed on this server.');
		}

		$roleName = $this->getURL('http://169.254.169.254/latest/meta-data/iam/security-credentials/');

		if (empty($roleName))
		{
			throw new RuntimeException("Could not find an attached IAM Role on this EC2 instance or we are not running on an EC2 instance.");
		}

		Factory::getLog()->debug(sprintf("Getting S3 credentials from EC2 attached IAM Role ‘%s’.", $roleName));

		$credentialsDocument = $this->getURL('http://169.254.169.254/latest/meta-data/iam/security-credentials/' . $roleName);
		$result              = @json_decode($credentialsDocument, true);

		if (is_null($result) || empty($result))
		{
			throw new RuntimeException(sprintf("Cannot retrieve credentials from IAM role %s", $roleName));
		}

		if (!array_key_exists('Code', $result) || ($result['Code'] != 'Success'))
		{
			throw new RuntimeException("Querying the IAM role did not return a successful result.");
		}

		$keys = ['AccessKeyId', 'AccessKeyId', 'Expiration', 'Token'];

		foreach ($keys as $key)
		{
			if (!array_key_exists($key, $result))
			{
				throw new RuntimeException(sprintf("Cannot find key ‘%s’ in EC2 metadata document. Automatic provisioning of S3 credentials is not possible.", $key));
			}
		}

		try
		{
			$expiresOn = new DateTime($result['Expiration']);
			$expires   = $expiresOn->getTimestamp();
		}
		catch (Exception $e)
		{
			Factory::getLog()->debug('Could not determine the expiration time of the automatically provisioned credentials. Assuming an expiration period of 10 minutes (minimum expiration period).');

			$expires = time() + 600;
		}

		return [
			'access'  => $result['AccessKeyId'],
			'secret'  => $result['SecretAccessKey'],
			'token'   => $result['Token'],
			'expires' => $expires,
		];
	}

	/**
	 * Returns the contents of a URL. We use this internally to fetch the Amazon S3 credentials from the attached
	 * Amazon EC2 instance role.
	 *
	 * @param   string  $url  The URL to fetch
	 *
	 * @return  string  The contents of the URL
	 *
	 * @throws RuntimeException
	 */
	private function getURL(string $url): string
	{
		$ch = curl_init();

		$this->applyProxySettingsToCurl($ch);

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_SSLVERSION, 0);
		curl_setopt($ch, CURLOPT_CAINFO, AKEEBA_CACERT_PEM);
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, [$this, 'reponseHeaderCallback']);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 1);

		$result = curl_exec($ch);

		$errno       = curl_errno($ch);
		$errmsg      = curl_error($ch);
		$error       = '';
		$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if ($result === false)
		{
			$error = sprintf("(cURL Error %u) %s", $errno, $errmsg);
		}
		elseif (($http_status >= 300) && ($http_status <= 399) && isset($this->headers['location']) && !empty($this->headers['location']))
		{
			return $this->getURL($this->headers['location']);
		}
		elseif ($http_status > 399)
		{
			$errno = $http_status;
			$error = sprintf('HTTP %u error', $http_status);
		}

		curl_close($ch);

		if ($result === false)
		{
			throw new RuntimeException($error, $errno);
		}

		return $result;
	}

	/**
	 * Start a multipart upload
	 *
	 * @param   string     $bucket       The bucket to upload to
	 * @param   string     $remoteKey    The remote filename
	 * @param   string     $sourceFile   The full path to the local source file
	 * @param   Connector  $connector    The S3 client object instance
	 * @param   string     $acl          Canned ACL privileges to use
	 * @param   int        $storageType  The Amazon S3 storage type. See the constants in this class.
	 *
	 * @return  bool  True when we're done uploading, false if we have more parts.
	 *
	 * @throws Exception When something goes wrong.
	 */
	private function multipartUpload(string $bucket, string $remoteKey, string $sourceFile, Connector $connector, string $acl = 'bucket-owner-full-control', int $storageType = 0): bool
	{
		$endpoint                       = $connector->getConfiguration()->getEndpoint();
		$headers                        = $this->getStorageTypeHeaders($storageType, $endpoint);
		$input                          = Input::createFromFile($sourceFile, null, null);
		$headers['Content-Disposition'] = sprintf('attachment; filename="%s"', basename($sourceFile));

		if (empty($this->uploadId))
		{
			Factory::getLog()->debug(sprintf(
				"%s -- Beginning multipart upload of %s", $this->engineLogName, $sourceFile
			));

			// Initialise the multipart upload if necessary
			try
			{
				$this->uploadId   = $connector->startMultipart($input, $bucket, $remoteKey, $acl, $headers);
				$this->partNumber = 1;
				$this->eTags      = [];

				Factory::getLog()->debug(sprintf(
					"%s -- Got uploadID %s", $this->engineLogName, $this->uploadId
				));
			}
			catch (Exception $e)
			{
				Factory::getLog()->debug(sprintf(
					"%s -- Failed to initialize multipart upload of %s", $this->engineLogName, $sourceFile
				));

				throw new RuntimeException(sprintf(
					'Upload cannot be initialised. %s returned an error.', $this->engineLogName
				), 500, $e);
			}
		}
		else
		{
			Factory::getLog()->debug(sprintf(
				"%s -- Continuing multipart upload of %s (UploadId: %s –– Part number %d)",
				$this->engineLogName, $sourceFile, $this->uploadId, $this->partNumber
			));
		}

		// Upload a chunk
		try
		{
			//$input = Input::createFromFile($sourceFile, null, null);
			$input->setUploadID($this->uploadId);
			$input->setPartNumber($this->partNumber);
			$input->setEtags($this->eTags);

			// Do NOT send $headers when uploading parts. The RRS header MUST ONLY be sent when we're beginning the multipart upload.
			$eTag = $connector->uploadMultipart($input, $bucket, $remoteKey);

			if (!is_null($eTag))
			{
				$this->eTags[]    = $eTag;
				$this->partNumber = $input->getPartNumber();
				$this->partNumber++;
			}
			else
			{
				// We just finished. Let's finalise the upload
				$count = count($this->eTags);
				Factory::getLog()->debug(sprintf(
					"%s -- Finalising multipart upload of %s (UploadId: %s –– %d parts in total",
					$this->engineLogName, $sourceFile, $this->uploadId, $count
				));

				//$input = Input::createFromFile($sourceFile, null, null);
				$input->setUploadID($this->uploadId);
				$input->setPartNumber($this->partNumber);
				$input->setEtags($this->eTags);

				$connector->finalizeMultipart($input, $bucket, $remoteKey);

				$this->uploadId   = null;
				$this->partNumber = null;
				$this->eTags      = [];
			}
		}
		catch (Exception $e)
		{
			Factory::getLog()->debug(sprintf(
				"%s -- Multipart upload of %s has failed.", $this->engineLogName, $sourceFile
			));

			// Reset the multipart markers in temporary storage
			$akeebaConfig = Factory::getConfiguration();
			$akeebaConfig->set($this->volatileKeyPrefix . 'uploadId', null);
			$akeebaConfig->set($this->volatileKeyPrefix . 'partNumber', null);
			$akeebaConfig->set($this->volatileKeyPrefix . 'eTags', null);

			throw new RuntimeException(sprintf(
				"Upload cannot proceed. %s returned an error.", $this->engineLogName
			), 500, $e);
		}

		// Save the internal tracking variables
		$akeebaConfig = Factory::getConfiguration();
		$akeebaConfig->set($this->volatileKeyPrefix . 'uploadId', $this->uploadId);
		$akeebaConfig->set($this->volatileKeyPrefix . 'partNumber', $this->partNumber);
		$akeebaConfig->set($this->volatileKeyPrefix . 'eTags', json_encode($this->eTags));

		// If I have an upload ID I have to do more work
		if (is_string($this->uploadId) && !empty($this->uploadId))
		{
			return false;
		}

		// In any other case I'm done uploading the file
		return true;
	}

	/**
	 * Get the Amazon request headers required to set the storage type of an upload to the specified type.
	 *
	 * @param   int     $storageType  The storage type. See the constants in this class.
	 * @param   string  $endpoint     The API endpoint. Used to determine whether it's Amazon or a third party service.
	 *
	 * @return  array  The headers
	 */
	private function getStorageTypeHeaders($storageType = self::STORAGE_STANDARD, $endpoint = 's3.amazonaws.com')
	{
		$headers = [];

		if (!in_array($endpoint, ['s3.amazonaws.com', 'amazonaws.com.cn']))
		{
			return $headers;
		}

		switch ($storageType)
		{
			case self::STORAGE_STANDARD:
				$headers['X-Amz-Storage-Class'] = 'STANDARD';
				break;

			case self::STORAGE_REDUCED_REDUNDANCY:
				$headers['X-Amz-Storage-Class'] = 'REDUCED_REDUNDANCY';
				break;

			case self::STORAGE_STANDARD_IA:
				$headers['X-Amz-Storage-Class'] = 'STANDARD_IA';
				break;

			case self::STORAGE_ONEZONE_IA:
				$headers['X-Amz-Storage-Class'] = 'ONEZONE_IA';
				break;

			case self::STORAGE_INTELLIGENT_TIERING:
				$headers['X-Amz-Storage-Class'] = 'INTELLIGENT_TIERING';
				break;

			case self::STORAGE_GLACIER:
				$headers['X-Amz-Storage-Class'] = 'GLACIER';
				break;

			case self::STORAGE_DEEP_ARCHIVE:
				$headers['X-Amz-Storage-Class'] = 'DEEP_ARCHIVE';
				break;
		}

		return $headers;
	}

	/**
	 * Perform a single-step upload of a file
	 *
	 * @param   string     $bucket       The bucket to upload to
	 * @param   string     $remoteKey    The remote filename
	 * @param   string     $sourceFile   The full path to the local source file
	 * @param   Connector  $s3Client     The S3 client object instance
	 * @param   string     $acl          Canned ACL privileges to use
	 * @param   int        $storageType  The Amazon S3 storage type. See the constants in this class.
	 *
	 * @return  bool  True when we're done uploading, false if we have more parts
	 *
	 * @throws Exception When something goes wrong.
	 */
	private function simpleUpload(string $bucket, string $remoteKey, string $sourceFile, Connector $s3Client, string $acl = 'bucket-owner-full-control', int $storageType = 0): bool
	{
		Factory::getLog()->debug(sprintf(
			"%s -- Legacy (single part) upload of %s", $this->engineLogName, basename($sourceFile)
		));

		$endpoint                       = $s3Client->getConfiguration()->getEndpoint();
		$headers                        = $this->getStorageTypeHeaders($storageType, $endpoint);
		$input                          = Input::createFromFile($sourceFile, null, null);
		$headers['Content-Disposition'] = sprintf('attachment; filename="%s"', basename($sourceFile));

		$s3Client->putObject($input, $bucket, $remoteKey, $acl, $headers);

		return true;
	}

	/**
	 * Handles the HTTP headers returned by cURL.
	 *
	 * @param   resource  $ch    cURL resource handle (unused)
	 * @param   string    $data  Each header line, as returned by the server
	 *
	 * @return  int  The length of the $data string
	 */
	private function reponseHeaderCallback($ch, $data)
	{
		$strlen = strlen($data);

		if (($strlen) <= 2)
		{
			return $strlen;
		}

		$testForHTTP = substr($data, 0, 4);

		if (strtoupper($testForHTTP) == 'HTTP')
		{
			return $strlen;
		}

		[$header, $value] = explode(': ', trim($data), 2);

		$this->headers[strtolower($header)] = $value;

		return $strlen;
	}
}
