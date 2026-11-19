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

use Akeeba\Engine\Postproc\ProxyAware;
use Akeeba\Engine\Util\FileCloseAware;
use Exception;
use RuntimeException;

/**
 * Microsoft OneDrive connector using the Microsoft Graph API.
 *
 * Handles OneDrive Personal, OneDrive for Business and SharePoint document libraries (the latter two through an
 * explicit Drive ID, see setDriveId()).
 */
class OneDriveBusiness
{
	use FileCloseAware;
	use ProxyAware;

	/**
	 * The URL of the helper script which is used to get fresh API tokens
	 */
	public const helperUrl = 'https://www.akeeba.com/oauth2/onedrivebusiness.php';

	/**
	 * Size limit for single part uploads.
	 *
	 * This is 4MB per https://docs.microsoft.com/en-us/graph/api/driveitem-put-content?view=graph-rest-1.0&tabs=http
	 */
	public const simpleUploadSizeLimit = 4194304;

	/**
	 * Item property to set the name conflict behavior
	 *
	 * @see https://docs.microsoft.com/en-us/onedrive/developer/rest-api/concepts/direct-endpoint-differences?view=odsp-graph-online#instance-annotations
	 */
	public const nameConflictBehavior = '@microsoft.graph.conflictBehavior';

	/**
	 * Refresh the access token proactively when it is within this many seconds of expiring. OneDrive / Microsoft Graph
	 * access tokens are short-lived (typically one hour); refreshing a little before expiry prevents the token from
	 * lapsing in the middle of a long-running backup, which is the cause of intermittent, hard-to-reproduce
	 * upload/download failures.
	 */
	private const tokenExpirationThreshold = 300;

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
	 * The root URL for the MS Graph API
	 *
	 * @see  https://docs.microsoft.com/en-us/graph/api/resources/onedrive?view=graph-rest-1.0
	 */
	protected $rootUrl = 'https://graph.microsoft.com/v1.0/me/';

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
	 * UNIX timestamp at which the current access token expires. 0 means "unknown" — e.g. a token supplied from saved
	 * configuration whose lifetime we have not learned yet. It is populated from the `expires_in` value OneDrive returns
	 * whenever the token is refreshed.
	 *
	 * @var int
	 */
	private $tokenExpiration = 0;

	/**
	 * The Drive ID we are connecting to.
	 *
	 * @var   string|null
	 * @since 9.2.2
	 */
	private $driveId;

	private $refreshUrl = '';

	public function __construct(?string $accessToken, ?string $refreshToken, ?string $dlid = null, ?string $driveId = null, $refreshUrl = '')
	{
		$this->accessToken  = $accessToken;
		$this->refreshToken = $refreshToken;
		$this->dlid         = $dlid;

		$this->setDriveId($driveId);
		$this->refreshUrl   = $refreshUrl ?: self::helperUrl;
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
					$this->getDriveInformation();
				}
				catch (RuntimeException $e)
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
	 *
	 * @see https://docs.microsoft.com/en-us/graph/api/driveitem-list-children?view=graph-rest-1.0&tabs=http
	 * @see https://docs.microsoft.com/en-us/graph/api/driveitem-search?view=graph-rest-1.0&tabs=http
	 */
	public function getRawContents($path, $searchString = null)
	{
		$collection  = empty($searchString) ? 'children' : 'search';
		$relativeUrl = $this->normalizeDrivePath($path, $collection);

		/**
		 * Search for items?
		 *
		 * @see https://docs.microsoft.com/en-us/graph/api/driveitem-search?view=graph-rest-1.0&tabs=http
		 */
		if ($searchString)
		{
			$relativeUrl .= sprintf('(q=\'%s\')', urlencode($searchString));
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
	 * Get a signed (pre-authenticated) download URL for the remote file.
	 *
	 * Unlike the legacy consumer OneDrive API, the Microsoft Graph API does not expose a usable download URL through the
	 * redirect Location of the `/content` endpoint with an appended `access_token`: that produces a short-lived,
	 * download.aspx URL that breaks when query parameters are tacked on, returning an HTML error page instead of the
	 * file. Graph instead surfaces a fully pre-authenticated, time-limited URL as the `@microsoft.graph.downloadUrl`
	 * property of the item's metadata. We use that directly.
	 *
	 * @param   string  $path   Relative path to the Drive's root
	 * @param   bool    $retry  Should I refresh the token and retry once if the URL cannot be retrieved?
	 *
	 * @return  string  Pre-authenticated URL to download the file's contents
	 *
	 * @see https://docs.microsoft.com/en-us/graph/api/driveitem-get?view=graph-rest-1.0&tabs=http#download-a-file
	 */
	public function getSignedUrl($path, $retry = true)
	{
		$relativeUrl = $this->normalizeDrivePath($path);

		$metadata = $this->fetch('GET', $relativeUrl);

		$downloadUrl = $metadata['@microsoft.graph.downloadUrl'] ?? $metadata['@content.downloadUrl'] ?? null;

		if (!empty($downloadUrl))
		{
			return $downloadUrl;
		}

		// We failed to get a download URL. This usually means the access token has expired. Refresh it and retry once.
		if ($retry)
		{
			$this->refreshToken();

			return $this->getSignedUrl($path, false);
		}

		throw new \RuntimeException('Could not get the download URL', 500);
	}

	/**
	 * Uploads a file of up to 4Mb in size.
	 *
	 * @param   string  $path       The remote path relative to Drive root
	 * @param   string  $localFile  The absolute local filesystem path
	 *
	 * @return  array  See http://onedrive.github.io/items/upload_put.htm
	 */
	public function simpleUpload($path, $localFile)
	{
		// Make sure this file is 4Mb or smaller
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
		$relativeUrl = $this->normalizeDrivePath($path, 'createUploadSession');

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
	 * Get a list of Drives accessible to this user's account.
	 *
	 * @return  array  String indexed array of Drive ID => User readable name.
	 *
	 * @since   9.2.2
	 */
	public function getDrives()
	{
		$relativeUrl = 'drives';

		$result = $this->fetch('GET', $relativeUrl);

		$translate = function ($string)
		{
			$translation = $string;

			if (class_exists(\Joomla\CMS\Language\Text::class))
			{
				$translation = \Joomla\CMS\Language\Text::_($string);
			}

			if (class_exists(\Awf\Text\Text::class))
			{
				$translation = \Awf\Text\Text::_($string);
			}

			if ($translation === $string && substr($string, 0, 11) === 'COM_AKEEBA_')
			{
				$string = 'COM_AKEEBABACKUP_' . substr($string, 11);
			}

			if (class_exists(\Joomla\CMS\Language\Text::class))
			{
				$translation = \Joomla\CMS\Language\Text::_($string);
			}

			if (class_exists(\Awf\Text\Text::class))
			{
				$translation = \Awf\Text\Text::_($string);
			}

			return $translation;
		};

		if (empty($result) || !is_array($result) || !isset($result['value']) || !is_array($result['value']) || empty($result['value']))
		{
			return [
				'' => 'Drive (OneDrive Personal)',
			];
		}

		$keys   = array_map(function ($driveArray) {
			return $driveArray['id'] ?? '';
		}, $result['value']);
		$values = array_map(function ($driveArray) {
			$description = $driveArray['description'] ?? 'Drive';

			switch ($driveArray['driveType'] ?? 'personal')
			{
				case 'personal':
					$type = 'OneDrive Personal';
					break;

				case 'business':
					$type = 'OneDrive for Business';
					break;

				case 'documentLibrary':
					$type = 'SharePoint';
					break;
			}

			return sprintf('%s (%s)', $description, $type);
		}, $result['value']);

		return array_combine($keys, $values);
	}

	/**
	 * Set the effective Drive ID
	 *
	 * @param   string|null  $driveId
	 *
	 * @return  void
	 */
	public function setDriveId(?string $driveId)
	{
		$this->driveId = $driveId;

		$this->rootUrl = empty($this->driveId) ? 'https://graph.microsoft.com/v1.0/me/' : 'https://graph.microsoft.com/v1.0/';
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

		// A rejected refresh can come back as an error payload carrying an HTTP *200* status, which fetch() hands to us
		// instead of throwing. Silently keeping the old, dead access token would report the refresh as a success and
		// the caller would learn of it only through an opaque "not authorised" error from whatever API call it made
		// next. Say what actually went wrong instead.
		if (!isset($refreshResponse['access_token']))
		{
			$reason = $refreshResponse['error_description']
				?? $refreshResponse['user_message']
				?? 'OneDrive did not return a new access token';

			throw new RuntimeException(
				rtrim($reason, " \t\n\r\0\x0B.")
				. '. Your OneDrive authorisation is no longer valid. Check your Download ID and reconnect your '
				. 'Microsoft account to this backup profile to obtain a new pair of tokens.',
				500
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
		$url     = $relativeUrl;
		$useAuth = true;

		if (substr($relativeUrl, 0, 6) != 'https:')
		{
			$url = $this->rootUrl . ltrim($relativeUrl, '/');
		}
		else
		{
			$useAuth = false;
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
		if ($useAuth)
		{
			$headers[] = 'Authorization: bearer ' . $this->accessToken;
		}

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
			unset ($additional['fp']);
		}

		// Set up additional options
		if ($method == 'GET' && $fp)
		{
			$options[CURLOPT_RETURNTRANSFER] = false;
			$options[CURLOPT_HEADER]         = false;
			$options[CURLOPT_FILE]           = $fp;

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
		if (empty($this->driveId))
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

		$relativePath = trim($relativePath, '/');

		if (empty($relativePath))
		{
			$path = '/drives/' . $this->driveId . '/items/root';

			if ($collection)
			{
				$path .= '/' . $collection;
			}

			return $path;
		}

		$path = '/drives/' . $this->driveId . '/items/root:/' . $relativePath;

		if ($collection)
		{
			$path = sprintf("/drives/%s/root:/%s:/%s", $this->driveId, $relativePath, $collection);
		}

		$path = str_replace(' ', '%20', $path);

		return $path;
	}

	/**
	 * @return string
	 */
	protected function getRefreshUrl()
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

		return $refreshUrl;
	}
}
