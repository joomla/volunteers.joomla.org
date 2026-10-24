<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License version 3, or later
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program. If not, see
 * <https://www.gnu.org/licenses/>.
 */

namespace Akeeba\Engine\Postproc\Connector;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Postproc\Connector\Box\Exception\APIError;
use Akeeba\Engine\Postproc\Connector\Box\Exception\cURLError;
use Akeeba\Engine\Postproc\Connector\Box\Exception\InvalidJSON;
use Akeeba\Engine\Postproc\Connector\Box\Exception\UnexpectedHTTPStatus;
use Akeeba\Engine\Postproc\ProxyAware;
use Akeeba\Engine\Util\FileCloseAware;
use Akeeba\Engine\Util\HashTrait;
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
	use HashTrait;
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
	 * Refresh the access token proactively when it is within this many seconds of expiring. Box access tokens are
	 * short-lived (typically 1 hour); refreshing a little before expiry prevents the token from lapsing in the middle
	 * of a long-running backup, which is the cause of intermittent, hard-to-reproduce upload/download failures.
	 */
	private const tokenExpirationThreshold = 300;

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

	private $refreshUrl = '';

	/**
	 * UNIX timestamp at which the current access token expires. 0 means "unknown" — e.g. a token supplied from saved
	 * configuration whose lifetime we have not learned yet. It is populated from the `expires_in` value Box returns
	 * whenever the token is refreshed.
	 *
	 * @var int
	 */
	private $tokenExpiration = 0;

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
	public function __construct($accessToken, $refreshToken, $dlid, $refreshUrl = '')
	{
		$this->accessToken  = $accessToken;
		$this->refreshToken = $refreshToken;
		$this->dlid         = $dlid;
		$this->refreshUrl   = $refreshUrl ?: self::helperUrl;
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

		if (!$forceRefresh)
		{
			if (empty($this->accessToken))
			{
				// We have no access token at all. There is nothing to probe with, and a recorded expiry for a token
				// which is no longer there tells us nothing, so we must refresh. Without this the saved expiry would
				// convince us the missing token is still good and every request would fail with an opaque "not
				// authorised" error instead of the token being renewed transparently.
				$response['needs_refresh'] = true;
			}
			elseif (!empty($this->tokenExpiration))
			{
				// We know when the token expires: refresh PROACTIVELY once it is at — or within a safety margin of —
				// expiry, so it cannot lapse mid-operation. This avoids the race where a "test" call succeeds but the
				// token then expires moments later during the real request (the cause of intermittent failures).
				$response['needs_refresh'] = (time() + self::tokenExpirationThreshold) >= $this->tokenExpiration;
			}
			else
			{
				// We do NOT know this token's expiry (e.g. it was supplied from saved configuration). Fall back to
				// probing the API with it and refreshing only if that probe fails.
				try
				{
					$this->getCurrentUser();
				}
				catch (UnexpectedHTTPStatus $e)
				{
					$response['needs_refresh'] = true;
				}
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
		if (empty($this->refreshUrl))
		{
			throw new \RuntimeException('The OAuth2 Refresh URL is empty. Please check your backup profile\'s configuration.');
		}

		$refreshUrl = $this->refreshUrl .
		              (strpos($this->refreshUrl, '?') === false ? '?' : '&')
		              . 'refresh_token=' . urlencode($this->refreshToken);

		if (strpos($refreshUrl, self::helperUrl) === 0)
		{
			$refreshUrl .= '&dlid=' . urlencode($this->dlid);
		}

		[$baseUrl, $query] = explode('?', $refreshUrl, 2);

		$refreshResponse = $this->fetch('GET', $baseUrl, $refreshUrl);

		// A rejected refresh token comes back as an error payload carrying an HTTP *200* status, so fetch() hands it to
		// us instead of throwing. Left unchecked we would keep the old, dead access token, report the refresh as a
		// success, and the caller would learn of it only through an opaque "Unexpected HTTP status 401" from whatever
		// API call it made next. Say what actually went wrong instead.
		if (!isset($refreshResponse['access_token']))
		{
			// A rejected refresh token explains itself in error_description; a missing or lapsed Download ID may explain
			// itself in user_message instead. Read both, or the most useful half of the message is thrown away.
			$reason = $refreshResponse['error_description']
				?? $refreshResponse['user_message']
				?? 'Box did not return a new access token';

			throw new APIError(
				$refreshResponse['error'] ?? 'invalid_grant',
				rtrim($reason, " \t\n\r\0\x0B.")
				. '. Your Box authorisation is no longer valid. Box refresh tokens are single-use and expire after 60 '
				. 'days of disuse; check your Download ID and reconnect your Box account to this backup profile to '
				. 'obtain a new pair.'
			);
		}

		$this->refreshToken = $refreshResponse['refresh_token'] ?? $this->refreshToken;
		$this->accessToken  = $refreshResponse['access_token'];

		// Record when the freshly minted access token will expire so ping() can refresh it proactively next time.
		if (isset($refreshResponse['expires_in']))
		{
			$this->tokenExpiration = time() + (int) $refreshResponse['expires_in'];
		}

		$refreshResponse['refresh_token']    = $this->refreshToken;
		$refreshResponse['access_token']     = $this->accessToken;
		$refreshResponse['token_expiration'] = $this->tokenExpiration;

		return $refreshResponse;
	}

	/**
	 * Get the UNIX timestamp at which the current access token expires (0 if unknown).
	 *
	 * @return  int
	 */
	public function getTokenExpiration()
	{
		return $this->tokenExpiration;
	}

	/**
	 * Restore a previously persisted access-token expiry timestamp, so ping() can refresh proactively without first
	 * having to probe the API. Pass 0 to mark the expiry as unknown.
	 *
	 * @param   int  $tokenExpiration  UNIX timestamp at which the access token expires.
	 *
	 * @return  void
	 */
	public function setTokenExpiration($tokenExpiration)
	{
		$this->tokenExpiration = (int) $tokenExpiration;
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
		$offset   = max((int) $offset, 0);
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
		 * The API paginates at $limit (default: 1000) items. If the items returned so far (offset + items on this page)
		 * do not yet account for the folder's total_count, there are more pages to fetch. This recurses through them.
		 */
		$limit       = $apiReturn['limit'] ?? 1000;
		$offset      = $apiReturn['offset'] ?? $offset;
		$itemsSoFar  = $offset + count($apiReturn['entries']);

		if ($itemsSoFar < $totalCount)
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

		$sha1       = @self::sha1_file($localFile);
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

		$sha1       = @self::sha1_file($localFile);
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

			if (empty($expectHttpStatus))
			{
				$expectHttpStatus = [200, 206];
			}
		}
		elseif (in_array($method, ['POST', 'PUT']) && $fp)
		{
			$options[CURLOPT_POST]   = true;
			$options[CURLOPT_INFILE] = $fp;

			// Some broken cURL versions cause an error. Forcing HTTP/1.1 seems to be fixing it.
			if (defined('CURLOPT_HTTP_VERSION') && defined('CURL_HTTP_VERSION_1_1'))
			{
				$options[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;
			}

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

		if (version_compare(PHP_VERSION, '8.5.0', 'lt'))
		{
			curl_close($ch);
		}

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
