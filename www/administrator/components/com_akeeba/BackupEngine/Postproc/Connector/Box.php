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

use Akeeba\Engine\Postproc\Connector\Box\Exception\APIError;
use Akeeba\Engine\Postproc\Connector\Box\Exception\cURLError;
use Akeeba\Engine\Postproc\Connector\Box\Exception\InvalidJSON;
use Akeeba\Engine\Postproc\Connector\Box\Exception\UnexpectedHTTPStatus;
use Akeeba\Engine\Postproc\ProxyAware;
use Akeeba\Engine\Util\FileCloseAware;
use CURLFile;
use RuntimeException;

/**
 * Box.com API connector
 *
 * @see https://developer.box.com/v2.0/reference
 *
 * WARNING! WE DO NOT SUPPORT CHUNKED UPLOADS FOR A VERY GOOD REASON.
 *
 * The Box.com API does not allow us to select the chunk size when uploading files. The chunked upload methods returns
 * a part size when you create a chunked upload session and you MUST use EXACTLY this part size. This tends to be rather
 * high, defeating the purpose of using it to work around server timeout limitations. Therefore we only implement single
 * part uploads. You can prevent timeouts by setting the "Part size for archive splitting" option in the archiver to
 * create smaller files which will upload without timing out.
 */
class Box
{
	use FileCloseAware;
	use ProxyAware;

	/**
	 * The root URL for the Box API
	 */
	public const rootUrl = 'https://api.box.com/2.0/';

	/**
	 * The root URL for the Box API
	 */
	public const uploadUrl = 'https://upload.box.com/api/2.0/';

	/**
	 * The URL of the helper script which is used to get fresh API tokens
	 */
	public const helperUrl = 'https://www.akeeba.com/oauth2/box.php';

	/**
	 * The access token for connecting to Box.com
	 *
	 * @var   string
	 */
	private $accessToken = '';

	/**
	 * The refresh token for connecting to Box.com
	 *
	 * @var   string
	 */
	private $refreshToken = '';

	/**
	 * Download ID to use with the helper URL
	 *
	 * @var string
	 */
	private $dlid = '';

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
	 * Box constructor.
	 *
	 * @param   string  $accessToken   The Box access token
	 * @param   string  $refreshToken  The Box refresh token
	 * @param   string  $dlid          The akeeba.com Download ID, used whenever you try to refresh the token
	 */
	public function __construct($accessToken, $refreshToken, $dlid)
	{
		$this->accessToken  = $accessToken;
		$this->refreshToken = $refreshToken;
		$this->dlid         = $dlid;
	}

	/**
	 * Try to ping Box.com, refresh the token if it's expired and return the refresh results.
	 *
	 * If no refresh was required 'needs_refresh' will be false.
	 *
	 * If refresh was required 'needs_refresh' will be true and the rest of the keys will be as returned by Box.com.
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
				$dummy = $this->getCurrentUser();
			}
			catch (UnexpectedHTTPStatus $e)
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
	 * @return array|string  The result coming from Box
	 */
	public function refreshToken()
	{
		$refreshQuery = '?refresh_token=' . urlencode($this->refreshToken) . '&dlid=' . urlencode($this->dlid);

		$refreshResponse = $this->fetch('GET', self::helperUrl, $refreshQuery);

		$this->refreshToken = $refreshResponse['refresh_token'] ?? $this->refreshToken;
		$this->accessToken  = $refreshResponse['access_token'] ?? $this->accessToken;

		$refreshResponse['refresh_token'] = $this->refreshToken;
		$refreshResponse['access_token']  = $this->accessToken;

		return $refreshResponse;

	}

	/**
	 * Returns information about the current user. The most important fields are:
	 * * space_amount:    how much total space is available to you (in bytes)
	 * * space_user:      how much space you have already used (in bytes)
	 * * max_upload_size: maximum uploaded file size
	 * You need to issue an error when the uploaded file is larger than EITHER space_amount - space_user OR
	 * max_upload_size because either would cause the upload to fail. In the first case the Box account is running out
	 * of free space. In the latter case you would be trying to exceed the maximum upload size limitation (as low as
	 * 250MB on free / personal accounts).
	 *
	 * @return  array
	 */
	public function getCurrentUser()
	{
		return $this->fetch('GET', self::rootUrl, 'users/me');
	}

	/**
	 * Lists the contents of a folder.
	 *
	 * @param   int  $parentId  The ID of the folder to list. 0 is the root folder.
	 * @param   int  $offset    Offset of the pagianted listing, 0 to start from the very beginning.
	 *
	 * @return  array  An array with keys files and folders. Each sub-array is keyed on file/folder name and the value
	 *                 is the file's/folder's numeric ID.
	 */
	public function listFolder($parentId = 0, $offset = 0)
	{
		$parentId = (int) $parentId;
		$offset   = min((int) $offset, 0);
		$url      = "folders/$parentId/items?limit=1000";

		if ($offset > 0)
		{
			$url .= '&offset=' . $offset;
		}

		$ret = [
			'files'   => [],
			'folders' => [],
		];

		$apiReturn = $this->fetch('GET', self::rootUrl, $url);

		if (!is_array($apiReturn))
		{
			return $ret;
		}

		$totalCount = $apiReturn['total_count'] ?? 0;

		if ($totalCount === 0)
		{
			return $ret;
		}

		if (!isset($apiReturn['entries']) || empty($apiReturn['entries']))
		{
			return $ret;
		}

		foreach ($apiReturn['entries'] as $entry)
		{
			if (!in_array($entry['type'], ['file', 'folder']))
			{
				continue;
			}

			$target              = ($entry['type'] == 'file') ? 'files' : 'folders';
			$name                = $entry['name'];
			$id                  = $entry['id'];
			$ret[$target][$name] = $id;
		}

		/**
		 * The API paginates at $limit (default: 1000) items. If we get exactly $limit items we probably have more
		 * pages of information to fetch. This will recurse through all the pages.
		 */
		$limit  = $apiReturn['limit'] ?? 1000;
		$offset = $apiReturn['offset'] ?? $offset;

		if ($totalCount == $limit)
		{
			$offset    += $limit;
			$moreItems = $this->listFolder($parentId, $offset);
			$ret       = array_merge_recursive($ret, $moreItems);
		}

		return $ret;
	}

	/**
	 * Create a folder
	 *
	 * @param   string  $name      The name of the folder to create
	 * @param   int     $parentId  The ID of the parent folder. 0 is always the root folder
	 *
	 * @return  int  The ID of the created folder
	 */
	public function createFolder($name, $parentId = 0)
	{
		$apiReturn  = [];
		$parentId   = (int) $parentId;
		$url        = "folders";
		$postData   = json_encode([
			'name'   => $name,
			'parent' => [
				'id' => $parentId,
			],
		]);
		$additional = [];

		try
		{
			$apiReturn = $this->fetch('POST', self::rootUrl, $url, $additional, $postData);
		}
		catch (UnexpectedHTTPStatus $e)
		{
			$httpResponse = $e->getCode();

			if ($httpResponse == '409')
			{
				throw new APIError("already_exists", "Subfolder “{$name}” already exists under folder ID $parentId", 409, $e);
			}
		}

		if (!isset($apiReturn['id']))
		{
			throw new APIError("", "Cannot create subfolder “{$name}” under folder ID $parentId");
		}

		return $apiReturn['id'];
	}

	/**
	 * Look for a folder relative to the $parentId folder and return its ID
	 *
	 * @param   string  $path           The folder path to look for
	 * @param   int     $parentId       The parent folder to start searching in (0 = root)
	 * @param   bool    $createMissing  Should I create any missing folders? Default: true.
	 *
	 * @return  int  The folder ID
	 *
	 * @throws  APIError  When the folder cannot be found and/or we can't create a new folder by that name
	 */
	public function getFolderId($path, $parentId = 0, $createMissing = true)
	{
		$created = false;

		$path = trim($path, '/');

		while (strpos($path, '//') !== false)
		{
			$path = str_replace('//', '/', $path);
		}

		$path = trim($path, '/');

		if (empty($path))
		{
			return $parentId;
		}

		$parts = explode('/', $path);

		foreach ($parts as $folderName)
		{
			$foundId = false;

			// Only search for folders if the current parent folder was not just created by us (saves us some useless requests)
			if (!$created)
			{
				// Get the parent folder's contents
				$contents = $this->listFolder($parentId);

				// Trawl the folder list for the one that matches our folder name
				foreach ($contents['folders'] as $key => $id)
				{
					if ($key == $folderName)
					{
						$foundId = $id;

						break;
					}
				}
			}

			if (($foundId === false) && !$createMissing)
			{
				throw new APIError("not_found", "Folder $path does not exist", 404);
			}
			elseif ($foundId === false)
			{
				$created = true;
				$foundId = $this->createFolder($folderName, $parentId);
			}

			$parentId = $foundId;
		}

		return $parentId;
	}

	/**
	 * Delete a file stored in Box given its path
	 *
	 * @param   string  $remoteFile  The path of the file to delete
	 *
	 * @return  bool
	 */
	public function deleteFileByName($remoteFile)
	{
		$fileId = $this->findFileId($remoteFile);

		if ($fileId === false)
		{
			return false;
		}

		// Delete the file
		$this->fetch('DELETE', self::rootUrl, "files/$fileId", [
			'expect-status' => 204,
		]);

		return true;
	}

	/**
	 * Preflight check. Makes sure that the file can be uploaded to the selected path.
	 *
	 * @param   string  $remoteFile  Path of the file in the Box.com account
	 * @param   string  $localFile   Local filesystem path of the file to upload
	 */
	public function preflight($remoteFile, $localFile)
	{
		$remotePath     = dirname($remoteFile);
		$parentFolderId = empty($remotePath) ? 0 : $this->getFolderId($remotePath);

		$sha1       = @sha1_file($localFile);
		$postfields = json_encode([
			'name'   => basename($remoteFile),
			'parent' => [
				'id' => $parentFolderId,
			],
			'size'   => @filesize($localFile),
		]);

		$additional = [
			'headers' => [
				// Yes, the header is named wrong. It's actually the SHA1 hash, not the MD5.
				'Content-MD5' => $sha1,
			],
		];

		if (empty($sha1))
		{
			unset($additional['headers']['Content-MD5']);
		}

		try
		{
			$this->fetch('OPTIONS', self::rootUrl, 'files/content', $additional, $postfields);
		}
		catch (UnexpectedHTTPStatus $e)
		{
			switch ($e->getCode())
			{
				case 409:
					$error     = 'file_exists';
					$errorDesc = 'File already exists';
					break;

				case 403:
					$error     = 'permissions_error';
					$errorDesc = 'Permissions error. The file is too big for your account type, you do not have enough space or the permissions of the target directory do not allow uploading this file.';
					break;

				default:
					$error     = 'unexpected_status';
					$errorDesc = 'Unexpected HTTP status ' . $e->getCode();
			}

			throw new APIError($error, $errorDesc, $e->getCode(), $e);
		}
	}

	/**
	 * Single part upload of a file
	 *
	 * @param   string  $remoteFile  Path of the file in the Box.com account
	 * @param   string  $localFile   Local filesystem path of the file to upload
	 *
	 * @return  string  The ID of the uploaded file
	 */
	public function uploadSingleFile($remoteFile, $localFile)
	{
		clearstatcache(false, $localFile);

		// Find the parent folder ID if other than root
		$remotePath     = dirname($remoteFile);
		$parentFolderId = empty($remotePath) ? 0 : $this->getFolderId($remotePath);

		$sha1       = @sha1_file($localFile);
		$postfields = [
			"attributes" => json_encode([
				'name'   => basename($remoteFile),
				'parent' => [
					'id' => $parentFolderId,
				],
			]),
			"file"       => "@$localFile",
		];

		/**
		 * PHP 5.6 and later, using CURLFile instead of the at-path notation for file uploads.
		 *
		 * This is the recommended method in PHP 5.6 and the only supported method in PHP 7.0 and later.
		 */
		if (class_exists('\CURLFile'))
		{
			$postfields['file'] = new CURLFile($localFile);
		}

		$additional = [
			'headers' => [
				// Yes, the header is named wrong. It's actually the SHA1 hash, not the MD5.
				'Content-MD5' => $sha1,
			],
		];

		if (empty($sha1))
		{
			unset($additional['headers']['Content-MD5']);
		}

		$apiReturn = $this->fetch('POST', self::uploadUrl, 'files/content', $additional, $postfields);

		if (
			is_array($apiReturn) &&
			isset($apiReturn['entries']) &&
			is_array($apiReturn['entries']) &&
			isset($apiReturn['entries'][0]) &&
			isset($apiReturn['entries'][0]['id'])
		)
		{
			return $apiReturn['entries'][0]['id'];
		}

		throw new APIError('cannot_create', "Cannot create file $remoteFile on Box.com");
	}

	public function download($remoteFile, $localFile)
	{
		$fileId = $this->findFileId($remoteFile);

		if ($fileId === false)
		{
			throw new RuntimeException("The file $remoteFile does not exist in your Box.com account");
		}

		$fp = @fopen($localFile, 'w');

		if ($fp === false)
		{
			throw new RuntimeException("Cannot open local file $localFile for writing");
		}

		$this->fetch('GET', self::rootUrl, "files/$fileId/content", [
			'fp'       => $fp,
			'no-parse' => true,
		]);

		return true;
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
			$headers[] = 'Authorization: Bearer ' . $this->accessToken;
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
			$options[CURLOPT_POST]   = true;
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

		if (empty($expectHttpStatus) && ($lastHttpCode >= 400))
		{
			throw new UnexpectedHTTPStatus($lastHttpCode);
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

		if (!empty($response) && ($lastHttpCode > 201))
		{
			$response = ['error' => $response];
		}

		// Did we get an error response?
		if (isset($response['error']) && is_array($response['error']))
		{
			$decodedError = $response['error'];

			throw new APIError($decodedError['code'], $decodedError['message'], 500);
		}

		return $response;
	}

	/**
	 * @param $remoteFile
	 *
	 * @return bool|int
	 */
	private function findFileId($remoteFile)
	{
		$remoteName     = basename($remoteFile);
		$remotePath     = dirname($remoteFile);
		$parentFolderId = empty($remotePath) ? 0 : $this->getFolderId($remotePath);
		$contents       = $this->listFolder($parentFolderId);
		$fileId         = 0;

		// Get the file ID
		foreach ($contents['files'] as $name => $id)
		{
			if ($name == $remoteName)
			{
				$fileId = $id;
			}
		}

		// File not found
		if (empty($fileId))
		{
			return false;
		}

		return $fileId;
	}
}
