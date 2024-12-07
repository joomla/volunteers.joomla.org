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

use Akeeba\Engine\Postproc\ProxyAware;
use Akeeba\Engine\Util\FileCloseAware;
use Exception;
use RuntimeException;

class OneDrive
{
	use FileCloseAware;
	use ProxyAware;

	/**
	 * The URL of the helper script which is used to get fresh API tokens
	 */
	public const helperUrl = 'https://www.akeeba.com/oauth2/onedrive.php';

	/**
	 * Size limit for single part uploads
	 */
	public const simpleUploadSizeLimit = 104857600;

	/**
	 * Item property to set the name conflict behavior
	 */
	public const nameConflictBehavior = '@name.conflictBehavior';

	/**
	 * The access token for connecting to OneDrive
	 *
	 * @var string
	 */
	protected $accessToken = '';

	/**
	 * The refresh token used to get a new access token for OneDrive
	 *
	 * @var string
	 */
	protected $refreshToken = '';

	/**
	 * The root URL for the OneDrive API, ref http://onedrive.github.io/README.htm
	 */
	protected $rootUrl = 'https://api.onedrive.com/v1.0/';

	/**
	 * Default cURL options
	 *
	 * @var array
	 */
	protected $defaultOptions = [
		CURLOPT_SSL_VERIFYPEER => true,
		CURLOPT_SSL_VERIFYHOST => true,
		CURLOPT_VERBOSE        => false,
		CURLOPT_HEADER         => false,
		CURLINFO_HEADER_OUT    => false,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_CAINFO         => AKEEBA_CACERT_PEM,
	];

	/**
	 * Download ID to use with the helper URL
	 *
	 * @var string
	 */
	protected $dlid = '';

	/**
	 * Public constructor
	 *
	 * @param   string  $accessToken   The access token for accessing OneDrive
	 * @param   string  $refreshToken  The refresh token for getting new access tokens for OneDrive
	 * @param   string  $dlid          The akeeba.com Download ID, used whenever you try to refresh the token
	 */
	public function __construct($accessToken, $refreshToken, $dlid)
	{
		$this->accessToken  = $accessToken;
		$this->refreshToken = $refreshToken;
		$this->dlid         = $dlid;
	}

	/**
	 * Try to ping OneDrive, refresh the token if it's expired and return the refresh results.
	 *
	 * If no refresh was required 'needs_refresh' will be false.
	 *
	 * If refresh was required 'needs_refresh' will be true and the rest of the keys will be as returned by OneDrive.
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
				$dummy = $this->getDriveInformation();
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
	 * Return information about the default Drive in the account
	 *
	 * @return  array  See http://onedrive.github.io/resources/drive.htm
	 */
	public function getDriveInformation()
	{
		$relativeUrl = 'drive';

		$result = $this->fetch('GET', $relativeUrl);

		return $result;
	}

	/**
	 * Get the raw listing of a folder
	 *
	 * @param   string  $path          The relative path of the folder to list its contents
	 * @param   string  $searchString  If set returns only items matching the search criteria
	 *
	 * @return  array  See http://onedrive.github.io/items/list.htm
	 */
	public function getRawContents($path, $searchString = null)
	{
		$relativeUrl = $this->normalizeDrivePath($path, 'children');

		if ($searchString)
		{
			$relativeUrl = $this->normalizeDrivePath($path, 'view.search');
		}

		$relativeUrl .= '?orderby=name%20asc';

		if ($searchString)
		{
			$relativeUrl .= '&q=' . urlencode($searchString);
		}

		$result = $this->fetch('GET', $relativeUrl);

		return $result;
	}

	/**
	 * Get the processed listing of a folder
	 *
	 * @param   string  $path          The relative path of the folder to list its contents
	 * @param   string  $searchString  If set returns only items matching the search criteria
	 *
	 * @return  array  Two arrays under keys folders and files. Each array's key is the file/folder name, the value is
	 *                 number of children (folder) or size in bytes (file)
	 */
	public function listContents($path = '/', $searchString = null)
	{
		$result = $this->getRawContents($path, $searchString);

		$return = [
			'files'   => [],
			'folders' => [],
		];

		if (!isset($result['value']) || !count($result['value']))
		{
			return $return;
		}

		foreach ($result['value'] as $item)
		{
			if (isset($item['folder']) && isset($item['folder']['childCount']))
			{
				$return['folders'][$item['name']] = $item['folder']['childCount'];

				continue;
			}

			$return['files'][$item['name']] = $item['size'];
		}

		return $return;
	}

	/**
	 * Delete a file
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
		$relativeUrl = $this->normalizeDrivePath($path);

		try
		{
			$result = $this->fetch('DELETE', $relativeUrl, ['expect-status' => '204']);
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
	 * @param   string  $path       The path of the file in OneDrive
	 * @param   string  $localFile  The absolute filesystem path where the file will be downloaded to
	 */
	public function download($path, $localFile)
	{
		$relativeUrl = $this->normalizeDrivePath($path, 'content');

		$this->fetch('GET', $relativeUrl, [
			'file' => $localFile,
		]);
	}

	/**
	 * Get a signed download URL for the remote file with the specified relative path to Drive's root
	 *
	 * @param   string  $path   Relative path to Drive's root
	 * @param   bool    $retry  Should I try to refresh the token and retry getting a URL if getting the URL fails?
	 *
	 * @return  string  Signed URL to download the file's contents
	 */
	public function getSignedUrl($path, $retry = true)
	{
		$relativeUrl = $this->normalizeDrivePath($path, 'content');

		$additional = [
			'curl-options'    => [
				CURLOPT_HEADER => 1,
			],
			'no-parse'        => true,
			'follow-redirect' => false,
		];

		$response = $this->fetch('GET', $relativeUrl, $additional);
		$lines    = explode("\r\n", $response);

		foreach ($lines as $line)
		{
			if (stripos($line, 'Location: ') === 0)
			{
				[$header, $location] = explode(': ', $line, 2);

				return $location . '?access_token=' . $this->accessToken;
			}
		}

		// Hm, we seem to have failed. This probably means that we need to refresh the tokens. Should I?
		if ($retry)
		{
			$this->refreshToken();

			return $this->getSignedUrl($path, false);
		}

		throw new RuntimeException('Could not get the download URL', 500);
	}

	/**
	 * Uploads a file of up to 100Mb in size.
	 *
	 * @param   string  $path       The remote path relative to Drive root
	 * @param   string  $localFile  The absolute local filesystem path
	 *
	 * @return  array  See http://onedrive.github.io/items/upload_put.htm
	 */
	public function simpleUpload($path, $localFile)
	{
		// Make sure this file is 100Mb or smaller
		clearstatcache();
		$filesize = @filesize($localFile);

		if ($filesize > static::simpleUploadSizeLimit)
		{
			throw new RuntimeException(sprintf("File size too big for simpleUpload (%s bigger than %u bytes).", $filesize, static::simpleUploadSizeLimit), 500);
		}

		// Get the relative URL
		$relativeUrl = $this->normalizeDrivePath($path, 'content') . '?' . urlencode(static::nameConflictBehavior) . '=replace';

		$additional = [
			'file'    => $localFile,
			'headers' => [
				'Content-Type: application/octet-stream',
			],
		];

		$response = $this->fetch('PUT', $relativeUrl, $additional);

		return $response;
	}

	/**
	 * Creates a new multipart upload session and returns its upload URL
	 *
	 * @param   string  $path  Relative path in the Drive
	 *
	 * @return  string  The upload URL for the session
	 */
	public function createUploadSession($path)
	{
		$relativeUrl = $this->normalizeDrivePath($path, 'upload.createSession');

		$explicitPost = (object) [
			'item' => [
				static::nameConflictBehavior => 'replace',
				'name'                       => basename($path),
			],
		];

		$explicitPost = json_encode($explicitPost);

		$info = $this->fetch('POST', $relativeUrl, [
			'headers' => [
				'Content-Type: application/json',
			],
		], $explicitPost);

		return $info['uploadUrl'];
	}

	/**
	 * Destroy an already started upload session
	 *
	 * @param   string  $url  The URL of the upload session
	 *
	 * @return  void
	 */
	public function destroyUploadSession($url)
	{
		$this->fetch('DELETE', $url, [
			'expect-status' => 204,
		]);
	}

	/**
	 * Upload a part
	 *
	 * @param   string  $sessionUrl  The upload session URL, see createUploadSession
	 * @param   string  $localFile   Absolute filesystem path of the source file
	 * @param   int     $from        Starting byte to begin uploading, default is 0 (start of file)
	 * @param   int     $length      Chunk size in bytes, default 10Mb, must NOT be over 60Mb!  MUST be a multiple of
	 *                               320Kb.
	 *
	 * @return  array  The upload information, see http://onedrive.github.io/items/upload_large_files.htm
	 */
	public function uploadPart($sessionUrl, $localFile, $from = 0, $length = 10485760)
	{
		clearstatcache();
		$totalSize = filesize($localFile);
		$to        = $from + $length - 1;

		if ($to > ($totalSize - 1))
		{
			$to = $totalSize - 1;
		}

		$contentLength = $to - $from + 1;

		$range = "$from-$to/$totalSize";

		$additional = [
			'headers' => [
				'Content-Length: ' . $contentLength,
				'Content-Range: bytes ' . $range,
			],
		];

		$fp = @fopen($localFile, 'r');

		if ($fp === false)
		{
			throw new RuntimeException("Could not open $localFile for reading", 500);
		}

		fseek($fp, $from);
		$data = fread($fp, $contentLength);
		$this->conditionalFileClose($fp);

		return $this->fetch('PUT', $sessionUrl, $additional, $data);
	}

	/**
	 * Upload a file using multipart uploads. Useful for large files.
	 *
	 * @param   string  $path       Relative path in the Drive
	 * @param   string  $localFile  Absolute filesystem path of the source file
	 * @param   int     $partSize   Part size in bytes, default 10Mb, must NOT be over 60Mb! MUST be a multiple of
	 *                              320Kb.
	 *
	 * @return  array  See http://onedrive.github.io/items/upload_large_files.htm
	 */
	public function resumableUpload($path, $localFile, $partSize = 10485760)
	{
		$sessionUrl = $this->createUploadSession($path);
		$from       = 0;

		while (true)
		{
			try
			{
				$result = $this->uploadPart($sessionUrl, $localFile, $from, $partSize);
			}
			catch (RuntimeException $e)
			{
				try
				{
					$this->destroyUploadSession($sessionUrl);
				}
				catch (RuntimeException $ex)
				{
				}

				throw $e;
			}

			$from += $partSize;

			// If the result doesn't have nextExpectedRanges we have finished uploading.
			if (isset($result['name']))
			{
				return $result;
			}
		}
	}

	/**
	 * Automatically decides which upload method to use to upload a file to OneDrive. This method will return when the
	 * entire file has been uploaded. If you want to implement staggered uploads use the createUploadSession and
	 * uploadPart methods.
	 *
	 * @param   string  $path       The remote path relative to Drive root
	 * @param   string  $localFile  The absolute local filesystem path
	 *
	 * @return  array  See http://onedrive.github.io/items/upload_put.htm
	 */
	public function upload($path, $localFile)
	{
		clearstatcache();
		$filesize = @filesize($localFile);

		// Bigger than the single part upload limit: use resumable uploads with default size (10Mb) parts
		if ($filesize > static::simpleUploadSizeLimit)
		{
			return $this->resumableUpload($path, $localFile);
		}

		// Smaller files, use simple upload
		return $this->simpleUpload($path, $localFile);
	}

	/**
	 * Make a directory (including all of its parent directories) if the directory doesn't exist. If it already exists
	 * nothing happens. If it doesn't exist and cannot be created an exception is raised.
	 *
	 * @param   string  $path  The path to create
	 *
	 * @throws Exception
	 */
	public function makeDirectory($path)
	{
		$path = trim($path, '/');

		// Empty path means that it already exists (it's the Drive's root)
		if (empty($path))
		{
			return;
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

		// Try to list parent contents. If an error occurs, it means the folder doesn't exist
		try
		{
			$this->listContents($parentPath, $folder);
		}
		catch (Exception $e)
		{
			// The parent folder doesn't exist. Create it!
			$this->makeDirectory($parentPath);
		}

		// We have to create a new folder $folder in parent folder $parentPath.
		$relativeUrl = $this->normalizeDrivePath($parentPath, 'children');
		$request     = (object) [
			'name'   => $folder,
			'folder' => (object) [],
		];
		$requestJSON = json_encode($request);

		// We always try to create the directory and handle the exception. We have to do that since OneDrive
		// has a kind of cache: this means that if we create a directory and then try to list the parent folder
		// it *may* be not listed. So the only workaround is to always try to create it
		// and ignore "nameAlreadyExists" exceptions
		try
		{
			$this->fetch('POST', $relativeUrl, [
				'headers' => [
					'Content-Type: application/json',
				],
			], $requestJSON);
		}
			// Seems OneDrive has no named exceptions, so I have to catch everything and re-throw it
		catch (Exception $e)
		{
			// If it's not an "already exist" error, re-throw it
			if (stripos($e->getMessage(), 'nameAlreadyExists') === false)
			{
				throw $e;
			}
		}
	}

	/**
	 * Refresh the access token.
	 *
	 * @return array|string  The result coming from OneDrive
	 */
	public function refreshToken()
	{
		$refreshUrl = $this->getRefreshUrl();

		$refreshResponse = $this->fetch('GET', $refreshUrl);

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
	 * @param   string  $relativeUrl   The relative URL to ping
	 * @param   array   $additional    Additional parameters
	 * @param   mixed   $explicitPost  Passed explicitly to POST requests if set, otherwise $additional is passed.
	 *
	 * @return  array|string
	 * @throws  RuntimeException
	 *
	 */
	protected function fetch($method, $relativeUrl, array $additional = [], $explicitPost = null)
	{
		// Get full URL, if required
		$url = $relativeUrl;

		if (substr($relativeUrl, 0, 6) != 'https:')
		{
			$url = $this->rootUrl . ltrim($relativeUrl, '/');
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
		$headers[] = 'Authorization: bearer ' . $this->accessToken;

		$options[CURLOPT_HTTPHEADER] = $headers;

		// Handle files
		$file = null;
		$fp   = null;

		if (isset($additional['file']))
		{
			$file = $additional['file'];
			unset ($additional['file']);
		}

		if (!isset($additional['fp']) && !empty($file))
		{
			$mode = ($method == 'GET') ? 'w' : 'r';
			$fp   = @fopen($file, $mode);
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
				if ($file)
				{
					@unlink($file);
				}

				throw new RuntimeException("Unexpected HTTP status $lastHttpCode", $lastHttpCode);
			}
		}

		// Did we have a cURL error?
		if ($errNo)
		{
			throw new RuntimeException("cURL error $errNo: $error", 500);
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
		$response = json_decode($response, true);

		// Did we get invalid JSON data?
		if (!$response)
		{
			throw new RuntimeException("Invalid JSON data received", 500);
		}

		// Did we get an error response?
		if (isset($response['error']) && is_array($response['error']))
		{
			$error            = $response['error']['code'];
			$errorDescription = $response['error']['message'] ?? 'No error description provided';

			throw new RuntimeException("Error $error: $errorDescription", 500);
		}

		// Did we get an error response (from the helper script)?
		if (isset($response['error']))
		{
			$error            = $response['error'];
			$errorDescription = $response['error_description'] ?? 'No error description provided';

			throw new RuntimeException("Error $error: $errorDescription", 500);
		}

		return $response;
	}

	/**
	 * Normalize the path of a resource inside the Drive
	 *
	 * @param   string  $relativePath  The relative path to the Drive's root
	 * @param   string  $collection    The collection of the path you want to access or an action, e.g. 'children',
	 *                                 'content', 'action.copy' etc
	 *
	 * @return string
	 */
	protected function normalizeDrivePath($relativePath, $collection = '')
	{
		$relativePath = trim($relativePath, '/');

		if (empty($relativePath))
		{
			$path = '/drive/root';

			if ($collection)
			{
				$path .= '/' . $collection;
			}

			return $path;
		}

		$path = '/drive/root:/' . $relativePath;

		if ($collection)
		{
			$path .= ':/' . $collection;
		}

		$path = str_replace(' ', '%20', $path);

		return $path;
	}

	/**
	 * @return string
	 */
	protected function getRefreshUrl()
	{
		return static::helperUrl . '?refresh_token=' . urlencode($this->refreshToken) . '&dlid=' . urlencode($this->dlid);
	}
}
