<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Postproc\Connector;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Postproc\Connector\Dropbox2\Exception\APIError;
use Akeeba\Engine\Postproc\Connector\Dropbox2\Exception\cURLError;
use Akeeba\Engine\Postproc\Connector\Dropbox2\Exception\InvalidJSON;
use Akeeba\Engine\Postproc\Connector\Dropbox2\Exception\UnexpectedHTTPStatus;
use Akeeba\Engine\Postproc\ProxyAware;
use Akeeba\Engine\Util\FileCloseAware;
use Exception;
use RuntimeException;

/**
 * Dropbox (API v2) post-processing engine for Akeeba Engine
 *
 * @package Akeeba\Engine\Postproc\Connector
 */
class Dropbox2
{
	use FileCloseAware;
	use ProxyAware;

	/**
	 * The root URL for the Dropbox RPC API, ref https://www.dropbox.com/developers/documentation/http
	 */
	public const rootUrl = 'https://api.dropboxapi.com/2/';

	/**
	 * The root URL for the Dropbox Content API, ref https://www.dropbox.com/developers/documentation/http
	 */
	public const contentRootUrl = 'https://content.dropboxapi.com/2/';

	/**
	 * The URL of the helper script which is used to authenticate you with Dropbox
	 */
	public const helperUrl = 'https://www.akeeba.com/oauth2/dropbox2.php';

	/**
	 * The refresh token used to get a new access token for OneDrive
	 *
	 * @var string
	 */
	protected $refreshToken = '';

	/**
	 * The access token for connecting to Dropbox
	 *
	 * @var   string
	 */
	private $accessToken = '';

	/**
	 * Download ID to use with the helper URL
	 *
	 * @var string
	 */
	private $dlid = '';

	/**
	 * Default cURL options
	 *
	 * @var array
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

	private $namespaceId = '';

	/**
	 * Public constructor
	 *
	 * @param   string  $accessToken  The access token for accessing Dropbox
	 * @param   string  $dlid         The akeeba.com Download ID, used whenever you try to refresh the token
	 */
	public function __construct($accessToken, $refreshToken, $dlid)
	{
		$this->accessToken  = $accessToken;
		$this->refreshToken = $refreshToken;
		$this->dlid         = $dlid;
	}

	/**
	 * Return information about the current user's account
	 *
	 * @return  array  See https://www.dropbox.com/developers/documentation/http#documentation-users-get_current_account
	 */
	public function getCurrentAccount()
	{
		$relativeUrl = 'users/get_current_account';

		$result = $this->fetch('POST', self::rootUrl, $relativeUrl, [
			'headers' => [
				'Content-Type: application/json; charset=utf-8',
			],
		], 'null');

		return $result;
	}

	/**
	 * Get the raw listing of a folder
	 *
	 * @param   string  $path              The relative path of the folder to list its contents
	 * @param   bool    $recursive         Produce a recursive listing? [false]
	 * @param   bool    $includeMediaInfo  Include media information in the metadata? [false]
	 *
	 * @return  array  See https://www.dropbox.com/developers/documentation/http#documentation-files-list_folder
	 */
	public function getRawContents($path = '/', $recursive = false, $includeMediaInfo = false)
	{
		$relativeUrl = 'files/list_folder';

		$path = $this->normalizePath($path);

		$params = [
			'path'               => $path,
			'recursive'          => $recursive ? true : false,
			'include_media_info' => $includeMediaInfo ? true : false,
		];

		$paramsForPost = json_encode($params);

		$result = $this->fetch('POST', self::rootUrl, $relativeUrl, [
			'headers' => [
				'Content-Type: application/json; charset=utf-8',
			],
		], $paramsForPost);

		return $result;
	}

	/**
	 * Get the raw listing of a folder
	 *
	 * @param   string  $cursor  The cursor recieved from getRawContents
	 *
	 * @return  array  See
	 *                 https://www.dropbox.com/developers/documentation/http#documentation-files-list_folder-continue
	 */
	public function getRawContentsContinue($cursor)
	{
		$relativeUrl = 'files/list_folder/continue';

		$params = [
			'cursor' => $cursor,
		];

		$paramsForPost = json_encode($params);

		$result = $this->fetch('POST', self::rootUrl, $relativeUrl, [
			'headers' => [
				'Content-Type: application/json; charset=utf-8',
			],
		], $paramsForPost);

		return $result;
	}

	/**
	 * Get the processed listing of a folder
	 *
	 * @param   string  $path  The relative path of the folder to list its contents
	 *
	 * @return  array  Two arrays under keys folders and files. Each array's key is the file/folder name, the value is
	 *                 the Dropbox Folder ID (folder) or size in bytes (file)
	 */
	public function listContents($path = '/')
	{
		$result = [];

		$rawContents = $this->getRawContents($path);
		$result      = $rawContents['entries'];

		while ($rawContents['has_more'])
		{
			$cursor      = $rawContents['cursor'];
			$rawContents = $this->getRawContentsContinue($cursor);
			$result      = array_merge($result, $rawContents['entries']);
		}

		$return = [
			'files'   => [],
			'folders' => [],
		];

		foreach ($result as $item)
		{
			if ($item['.tag'] == 'folder')
			{
				$return['folders'][$item['name']] = $item['id'];

				continue;
			}

			$return['files'][$item['name']] = $item['size'];
		}

		return $return;
	}

	/**
	 * Return the metadata for a file or folder
	 *
	 * @param   string  $path              The relative path of the file/folder to fetch the metadata for
	 * @param   bool    $includeMediaInfo  Include media information in the metadata? [false]
	 *
	 * @return  array  See https://www.dropbox.com/developers/documentation/http#documentation-files-get_metadata
	 */
	public function getMetadata($path, $includeMediaInfo = false)
	{
		$relativeUrl = 'files/get_metadata';

		$path = $this->normalizePath($path);

		$params = [
			'path'               => $path,
			'include_media_info' => $includeMediaInfo ? true : false,
		];

		$paramsForPost = json_encode($params);

		$result = $this->fetch('POST', self::rootUrl, $relativeUrl, [
			'headers' => [
				'Content-Type: application/json; charset=utf-8',
			],
		], $paramsForPost);

		return $result;
	}

	/**
	 * Delete a file. See https://www.dropbox.com/developers/documentation/http#documentation-files-delete
	 *
	 * @param   string  $path         The relative path to the file to delete
	 * @param   bool    $failOnError  Throw exception if the deletion fails? Default true.
	 *
	 * @return  bool  True on success
	 *
	 * @throws  Exception
	 */
	public function delete($path, $failOnError = true)
	{
		$relativeUrl = 'files/delete_v2';
		$path        = $this->normalizePath($path);

		$params        = [
			'path' => $path,
		];
		$paramsForPost = json_encode($params);

		try
		{
			$result = $this->fetch('POST', self::rootUrl, $relativeUrl, [
				'headers' => [
					'Content-Type: application/json; charset=utf-8',
				],
			], $paramsForPost);
		}
		catch (Exception $e)
		{
			if (!$failOnError)
			{
				return false;
			}

			throw $e;
		}

		return true;
	}

	/**
	 * Download a remote file
	 *
	 * @param   string  $path       The path of the file in Dropbox
	 * @param   string  $localFile  The absolute filesystem path where the file will be downloaded to
	 */
	public function download($path, $localFile)
	{
		$relativeUrl = 'files/download';
		$path        = $this->normalizePath($path);

		$params        = [
			'path' => $path,
		];
		$paramsForPost = json_encode($params);

		$this->fetch('GET', self::contentRootUrl, $relativeUrl, [
			'headers'   => [
				'Content-Type:', // WARNING: Content-Type MUST be empty!
				'Dropbox-API-Arg: ' . $paramsForPost,
			],
			'file'      => $localFile,
			'file_mode' => 'wb',
		]);
	}

	/**
	 * Get a shared download URL for the remote file with the specified path to Dropbox root. This kind of URL is
	 * suitable for sharing with third parties. It doesn't the (secret) authentication token.
	 *
	 * @param   string  $path  Relative path to Dropbox root
	 *
	 * @return  string  Shared URL to download the file's contents
	 *
	 * @see     https://www.dropbox.com/developers/documentation/http#documentation-sharing-create_shared_link
	 */
	public function getSharedUrl($path, $expires = null)
	{
		$relativeUrl = 'sharing/create_shared_link_with_settings';
		$path        = $this->normalizePath($path);

		$settings = [
			'requested_visibility' => 'public',
		];

		$params        = [
			'path'     => $path,
			'settings' => $settings,
		];
		$paramsForPost = json_encode($params);

		$result = $this->fetch('POST', self::rootUrl, $relativeUrl, [
			'headers' => [
				'Content-Type: application/json; charset=utf-8',
			],
		], $paramsForPost);

		return $result['url'];
	}

	/**
	 * Returns an fully qualified, authenticated URL from a relative URL. This URL is NOT meant for sharing! It contains
	 * the (secret) authentication token!
	 *
	 * @param   string  $path  The URL to apply
	 *
	 * @return  string
	 */
	public function getAuthenticatedUrl($path)
	{
		$path = $this->normalizePath($path);

		$params = [
			'path' => $path,
		];

		$paramsForURL = json_encode($params);

		$url = self::contentRootUrl . 'files/download';
		$url .= '?authorization=Bearer%20' . urlencode($this->accessToken);
		$url .= '&arg=' . urlencode($paramsForURL);

		return $url;
	}

	/**
	 * Creates a new multipart upload session and returns its upload URL
	 *
	 * @return  string  The upload session ID
	 *
	 * @see     https://www.dropbox.com/developers/documentation/http#documentation-files-upload_session-start
	 */
	public function createUploadSession()
	{
		$relativeUrl = 'files/upload_session/start';

		$info = $this->fetch('POST', self::contentRootUrl, $relativeUrl, [
			'headers' => [
				'Content-Type: application/octet-stream',
			],
		]);

		return $info['session_id'];
	}

	/**
	 * Finish an already started upload session and commits the file to a specific location in Dropbox
	 *
	 * @param   string  $sessionId  The upload session ID
	 * @param   string  $path       Relative path of the file in Dropbox
	 * @param   int     $offset     The file size that's been already uploaded
	 * @param   bool    $mute       If true, the Dropbox desktop/mobile app will NOT notify users of the uploaded file
	 *
	 * @return  array  See
	 *                 https://www.dropbox.com/developers/documentation/http#documentation-files-upload_session-finish
	 */
	public function finishUploadSession($sessionId, $path, $offset, $mute = false)
	{
		$relativeUrl = 'files/upload_session/finish';
		$path        = $this->normalizePath($path);

		$params        = [
			'cursor' => [
				'session_id' => $sessionId,
				'offset'     => $offset,
			],
			'commit' => [
				'path'       => $path,
				'mode'       => 'overwrite',
				'autorename' => false,
				'mute'       => $mute ? true : false,
			],
		];
		$paramsForPost = json_encode($params);

		return $this->fetch('POST', self::contentRootUrl, $relativeUrl, [
			'headers' => [
				'Content-Type: application/octet-stream',
				'Dropbox-API-Arg: ' . $paramsForPost,
			],
		]);
	}

	/**
	 * Upload a part
	 *
	 * @param   string  $sessionId  The upload session URL, see createUploadSession
	 * @param   string  $localFile  Absolute filesystem path of the source file
	 * @param   int     $from       Starting byte to begin uploading, default is 0 (start of file)
	 * @param   int     $length     Chunk size in bytes, default 10Mb, must NOT be over 60Mb!  MUST be a multiple of
	 *                              320Kb.
	 *
	 * @return  void
	 */
	public function uploadPart($sessionId, $localFile, $from = 0, $length = 10485760)
	{
		$relativeUrl = 'files/upload_session/append_v2';

		clearstatcache();
		$totalSize = filesize($localFile);
		$to        = $from + $length - 1;

		if ($to > ($totalSize - 1))
		{
			$to = $totalSize - 1;
		}

		$contentLength = $to - $from + 1;

		$params        = [
			'cursor' => [
				'session_id' => $sessionId,
				'offset'     => $from,
			],
			'close'  => false,
		];
		$paramsForPost = json_encode($params);

		$additional = [
			'headers'  => [
				'Content-Type: application/octet-stream',
				'Dropbox-API-Arg: ' . $paramsForPost,
			],
			'no-parse' => true,
		];

		$fp = @fopen($localFile, 'r');

		if ($fp === false)
		{
			throw new RuntimeException("Could not open $localFile for reading", 500);
		}

		fseek($fp, $from);
		$data = fread($fp, $contentLength);
		$this->conditionalFileClose($fp);

		$this->fetch('POST', self::contentRootUrl, $relativeUrl, $additional, $data);
	}

	/**
	 * Upload a file using multipart uploads. Useful for files over 100Mb and up to 2Gb.
	 *
	 * @param   string  $path       Relative path in Dropbox
	 * @param   string  $localFile  Absolute filesystem path of the source file
	 * @param   int     $partSize   Part size in bytes, default 10Mb.
	 * @param   bool    $mute       If true, the Dropbox desktop/mobile app will NOT notify users of the uploaded file
	 *
	 * @return  array  See
	 *                 https://www.dropbox.com/developers/documentation/http#documentation-files-upload_session-finish
	 */
	public function resumableUpload($path, $localFile, $partSize = 10485760, $mute = false)
	{
		clearstatcache();
		$totalSize = filesize($localFile);

		$sessionId = $this->createUploadSession();
		$from      = 0;

		while (true)
		{
			$this->uploadPart($sessionId, $localFile, $from, $partSize);

			$from += $partSize;

			if ($from >= $totalSize)
			{
				break;
			}
		}

		return $this->finishUploadSession($sessionId, $path, $totalSize, $mute);
	}

	/**
	 * Automatically decides which upload method to use to upload a file to Dropbox. This method will return when the
	 * entire file has been uploaded. If you want to implement staggered uploads use the createUploadSession and
	 * uploadPart methods.
	 *
	 * @param   string  $path       The remote path relative to Dropbox root
	 * @param   string  $localFile  The absolute local filesystem path
	 *
	 * @return  array  See
	 *                 https://www.dropbox.com/developers/documentation/http#documentation-files-upload_session-finish
	 */
	public function upload($path, $localFile)
	{
		clearstatcache();
		$filesize = @filesize($localFile);

		// Use resumable uploads with up to 1Mb parts
		return $this->resumableUpload($path, $localFile, 1048576);
	}

	/**
	 * Make a directory (including all of its parent directories) if the directory doesn't exist. If it already exists
	 * nothing happens. If it doesn't exist and cannot be created an exception is raised.
	 *
	 * @param   string  $path  The path to create
	 *
	 * @return  array  See https://www.dropbox.com/developers/documentation/http#documentation-files-create_folder
	 */
	public function makeDirectory($path)
	{
		$path = $this->normalizePath($path);

		try
		{
			$ownMetaData = $this->getMetadata($path);
		}
		catch (Exception $e)
		{
			$ownMetaData = null;
		}

		// Empty path means that it already exists (it's the root)
		if (empty($path))
		{
			return [$ownMetaData];
		}

		// Get the parent path and the directory components of the path
		$parentPath = '/';
		$folder     = $path;

		if (strpos($path, '/') !== false)
		{
			$pathParts  = explode('/', $path);
			$folder     = array_pop($pathParts);
			$parentPath = implode('/', $pathParts);
		}

		// Does this path exist in the parent path?
		$mustCreate = false;

		try
		{
			if (is_null($ownMetaData))
			{
				$parentMetaData = $this->getMetadata($parentPath);
			}
		}
		catch (Exception $e)
		{
			// The parent folder doesn't exist. Create it!
			$this->makeDirectory($parentPath);
		}

		if (!is_null($ownMetaData))
		{
			return $ownMetaData;
		}

		// We have to create a new folder $folder in parent folder $parentPath.
		$relativeUrl   = 'files/create_folder_v2';
		$params        = [
			'path'       => $path,
			'autorename' => false,
		];
		$paramsForPost = json_encode($params);

		return $this->fetch('POST', self::rootUrl, $relativeUrl, [
			'headers' => [
				'Content-Type: application/json',
			],
		], $paramsForPost);
	}

	/**
	 * Get the namespace ID.
	 *
	 * This is used for Dropbox for Business only.
	 *
	 * @see     https://www.dropbox.com/developers/reference/namespace-guide
	 *
	 * @return  string
	 */
	public function getNamespaceId()
	{
		return $this->namespaceId;
	}

	/**
	 * Set the namespace ID. Set to empty to use the user's personal space (default behavior).
	 *
	 * This is used for Dropbox for Business only.
	 *
	 * @param   string  $namespaceId  The namespace ID. Get it with $this->getCurrentAccount
	 *
	 * @return  void
	 * @see     https://www.dropbox.com/developers/reference/namespace-guide
	 *
	 */
	public function setNamespaceId($namespaceId)
	{
		$this->namespaceId = $namespaceId;
	}

	/**
	 * Try to ping Dropbox, refresh the token if it's expired and return the refresh results.
	 *
	 * If no refresh was required 'needs_refresh' will be false.
	 *
	 * If refresh was required 'needs_refresh' will be true and the rest of the keys will be as returned by Dropbox.
	 *
	 * If the refresh failed you'll get a RuntimeException.
	 *
	 * @param   bool  $forceRefresh  Set to true to forcibly refresh the tokens
	 *
	 * @return  array
	 *
	 * @throws  RuntimeException
	 */
	public function ping($forceRefresh = false)
	{
		// Initialization
		$response = [
			'needs_refresh' => false,
		];

		// If we're not force refreshing the tokens try to get the drive information. It's our test to see if the token
		// works.
		if (!$forceRefresh)
		{
			try
			{
				$dummy = $this->getCurrentAccount();
			}
			catch (RuntimeException $e)
			{
				// If it failed we need to refresh the token
				$response['needs_refresh'] = true;
			}
		}

		// If there is no need to refresh the tokens, return
		if (!$response['needs_refresh'] && !$forceRefresh)
		{
			return $response;
		}

		$refreshResponse = $this->refreshToken();

		return array_merge($response, $refreshResponse);
	}

	/**
	 * Refresh the access token.
	 *
	 * @return array|string  The result coming from OneDrive
	 */
	public function refreshToken()
	{
		$refreshUrl = $this->getRefreshUrl();

		$refreshResponse = $this->fetch('GET', '', $refreshUrl);

		$this->refreshToken = $refreshResponse['refresh_token'] ?? $this->refreshToken;
		$this->accessToken  = $refreshResponse['access_token'] ?? $this->accessToken;

		$refreshResponse['refresh_token'] = $this->refreshToken;
		$refreshResponse['access_token']  = $this->accessToken;

		return $refreshResponse;
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
		$expectHttpStatus = false;

		if (isset($additional['expect-status']))
		{
			$expectHttpStatus = $additional['expect-status'];
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

		// Try to use at least TLS 1.2. Requires cURL 7.34.0 or later.
		if (defined('CURLOPT_SSLVERSION') && defined('CURL_SSLVERSION_TLSv1_2'))
		{
			$options[CURLOPT_SSLVERSION] = CURL_SSLVERSION_TLSv1_2;
		}

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
		$headers = [];

		if (isset($additional['headers']))
		{
			$headers = $additional['headers'];
			unset ($additional['headers']);
		}

		// Add the authorization header
		$headers[] = 'Authorization: Bearer ' . $this->accessToken;

		// Add the Dropbox-API-Path-Root header
		$apiRootHeader = '{".tag": "home"}';

		if (!empty($this->namespaceId))
		{
			$apiRootHeader = sprintf('{".tag": "namespace_id", "namespace_id": "%s"}', $this->namespaceId);
		}

		$headers[] = 'Dropbox-API-Path-Root: ' . $apiRootHeader;

		// Apply the headers
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

			if (!$expectHttpStatus)
			{
				$expectHttpStatus = 200;
			}
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
		elseif ($method == 'PUT' && $fp)
		{
			$options[CURLOPT_PUT]    = true;
			$options[CURLOPT_INFILE] = $fp;

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
		if ($fp)
		{
			$this->conditionalFileClose($fp);

			if ($expectHttpStatus && ($expectHttpStatus != $lastHttpCode))
			{
				if ($file && ($method == 'GET'))
				{
					@unlink($file);
				}

				throw new UnexpectedHTTPStatus($lastHttpCode);
			}
		}

		// Did we have a cURL error?
		if ($errNo)
		{
			throw new cURLError($errNo, $error);
		}

		if ($expectHttpStatus)
		{
			if ($expectHttpStatus == $lastHttpCode)
			{
				return [];
			}
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

		// Did we get an error response?
		if (isset($response['error']) && is_array($response['error']))
		{
			$decodedError = $this->decodeError($response['error']);

			throw new APIError($decodedError['code'], $decodedError['description'], 500);
		}

		// Did we get an error response (from the helper script)?
		if (isset($response['error']))
		{
			$error            = $response['error'];
			$errorDescription = $response['error_description'] ?? 'No error description provided';

			throw new APIError($error, $errorDescription, 500);
		}

		return $response;
	}

	/**
	 * Normalize the path of a resource inside the Dropbox account
	 *
	 * @param   string  $relativePath  The relative path to the Dropbox root
	 *
	 * @return  string
	 */
	protected function normalizePath($relativePath)
	{
		/**
		 * Some users enter the base path as /foo/bar/ instead of /foo/bar. This results in relative paths in the form
		 * of /foo/bar//baz.bat instead of /foo/bar/baz.bat. While the former doesn't cause a problem uploading(!) it
		 * causes the download to fail with a 400 error and the signed URL to fail entirely with a Dropbox-side error
		 * message. Therefore we need to replace // with / in the $relativePath.
		 */
		$relativePath = str_replace('//', '/', $relativePath);

		// Remove trailing slashes from the relative path
		$relativePath = trim($relativePath, '/');

		// An empty path is normalized to an empty string.
		if (empty($relativePath))
		{
			$path = '';

			return $path;
		}

		// The path MUST start with a forward slash
		$path = '/' . $relativePath;

		/**
		 * If the path is just a forward slash OR a double forward slash then it's the root which MUST be normalized to
		 * an empty string. Normally the check for the double forward slash should always be false (unless someone
		 * screwed up the code above).
		 */
		if (($path == '/') || $path == '//')
		{
			$path = '';
		}

		return $path;
	}

	/**
	 * Decodes the error messages returned by Dropbox
	 *
	 * @param   array  $error  The error structure returned by Dropbox
	 *
	 * @return  array  Error code and description
	 */
	protected function decodeError($error)
	{
		// Initialise
		$ret = [
			'code'        => 'unknown',
			'description' => 'No error description provided. Raw error: ' . print_r($error, true),
		];

		// Make sure there's an error tag
		if (!isset($error['.tag']))
		{
			$error['.tag'] = 'other';
		}

		$ret['code'] = $error['.tag'];

		switch ($error['.tag'])
		{
			case 'path':
			case 'path_lookup':
				$tag = $error['.tag'];

				if (!isset($error[$tag]['.tag']))
				{
					$error[$tag]['.tag'] = 'other';
				}

				$ret['code'] = $error[$tag]['.tag'];

				switch ($ret['code'])
				{
					case 'malformed_path':
						$ret['description'] = 'This field is optional.';
						break;

					case 'not_found':
						$ret['description'] = 'There is nothing at the given path.';
						break;

					case 'not_file':
						$ret['description'] = 'Dropbox was expecting a file, but the given path refers to something that isn\'t a file.';
						break;

					case 'not_folder':
						$ret['description'] = 'Dropbox was expecting a folder, but the given path refers to something that isn\'t a folder.';
						break;

					case 'restricted_content':
						$ret['description'] = 'The file cannot be transferred because the content is restricted. For example, sometimes there are legal restrictions due to copyright claims.';
						break;
				}
				break;

			case 'path_write':
				if (!isset($error['path_write']['.tag']))
				{
					$error['path_write']['.tag'] = 'other';
				}

				$ret['code'] = $error['path_write']['.tag'];

				switch ($ret['code'])
				{
					case 'malformed_path':
						$ret['description'] = 'This field is optional.';
						break;

					case 'conflict':
						$ret['description'] = 'Couldn\'t write to the target path because of a conflict.';
						break;

					case 'no_write_permission':
						$ret['description'] = 'You do not have permissions to write to the target location.';
						break;

					case 'insufficient_space':
						$ret['description'] = 'You do not have enough available space (bytes) to write more data.';
						break;

					case 'disallowed_name':
						$ret['description'] = 'Dropbox will not save the file or folder because its name contains characters that are not allowed.';
						break;
				}
				break;

			case 'reset':
				$ret['description'] = 'The folder listing cursor has been invalidated. Try getting a new folder list.';
				break;
		}

		return $ret;
	}

	protected function getRefreshUrl()
	{
		return static::helperUrl . '?refresh_token=' . urlencode($this->refreshToken) . '&dlid=' . urlencode($this->dlid);
	}

}
