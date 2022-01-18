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

use Akeeba\Engine\Postproc\ProxyAware;
use Akeeba\Engine\Util\FileCloseAware;
use Exception;
use RuntimeException;

/**
 * Google Drive v3 API integration for Akeeba Engine
 *
 * @package Akeeba\Engine\Postproc\Connector
 */
class GoogleDrive
{
	use FileCloseAware;
	use ProxyAware;

	/**
	 * The root URL for the Google Drive v3 API
	 */
	public const rootUrl = 'https://www.googleapis.com/drive/v3/';
	/**
	 * The root URL for the Google Drive v3 upload API
	 */
	public const uploadUrl = 'https://www.googleapis.com/upload/drive/v3/';
	/**
	 * The URL of the helper script which is used to get fresh API tokens
	 */
	public const helperUrl = 'https://www.akeeba.com/oauth2/googledrive.php';
	/**
	 * The access token for connecting to Google Drive
	 *
	 * @var string
	 */
	private $accessToken = '';
	/**
	 * The refresh token used to get a new access token for Google Drive
	 *
	 * @var string
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
	 * @var array
	 */
	private $defaultOptions = [
		CURLOPT_SSL_VERIFYPEER => true,
		CURLOPT_SSL_VERIFYHOST => true,
		CURLOPT_VERBOSE        => false,
		CURLOPT_HEADER         => false,
		CURLINFO_HEADER_OUT    => false,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_CAINFO         => AKEEBA_CACERT_PEM,
	];

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
	 * Try to ping Google Drive, refresh the token if it's expired and return the refresh results.
	 *
	 * If no refresh was required 'needs_refresh' will be false.
	 *
	 * If refresh was required 'needs_refresh' will be true and the rest of the keys will be as returned by Google Drive.
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

		$refreshUrl = self::helperUrl . '?refresh_token=' . urlencode($this->refreshToken) . '&dlid=' . urlencode($this->dlid);

		$refreshResponse = $this->fetch('GET', $refreshUrl);

		$this->accessToken  = $refreshResponse['access_token'];

		return array_merge($response, $refreshResponse);
	}

	/**
	 * Return information about Google Drive
	 *
	 * @return  array  See https://developers.google.com/drive/v3/reference/about/get
	 */
	public function getDriveInformation()
	{
		$relativeUrl = 'about';

		$result = $this->fetch('GET', $relativeUrl, [
			'fields' => 'appInstalled,kind,maxUploadSize,storageQuota,user',
		]);

		return $result;
	}

	/**
	 * Get a list of Google Team Drives as an array of ID => Team Drive Name. If there are no team drives or the account
	 * does not support team drives you will receive an empty list.
	 *
	 * @return  array  See https://developers.google.com/drive/api/v3/reference/drives/list
	 */
	public function getTeamDrives()
	{
		$ret         = [];
		$relativeUrl = 'drives';
		$result      = $this->fetch('GET', $relativeUrl, [
			'pageSize' => 100
		]);

		if (!isset($result['drives']) || empty($result['drives']))
		{
			return $ret;
		}

		foreach ($result['drives'] as $drive)
		{
			$ret[$drive['id']] = $drive['name'];
		}

		return $ret;
	}

	/**
	 * Get the raw listing of a folder
	 *
	 * @param   string  $parentId     The parent folder Id (default: 'root')
	 * @param   string  $search       Additional search criteria to apply, see https://developers.google.com/drive/v3/web/search-parameters
	 * @param   int     $pageSize     The pagination size, default 100
	 * @param   string  $pageToken    The page continuation token from a previous request
	 * @param   string  $orderBy      Ordering for the results, defaults to "folder,name" (folders first, then sort by name ascending)
	 * @param   string  $teamDriveID  The ID of the Google Team Drive. Empty means we should use the personal Drive (default).
	 *
	 * @return  array  See https://developers.google.com/drive/v3/reference/files/list
	 */
	public function getRawContents($parentId = 'root', $search = null, $pageSize = 100, $pageToken = null, $orderBy = 'folder,name', $teamDriveID = '')
	{
		$params = [
			'supportsAllDrives' => 'true',
			'orderBy'           => $orderBy,
			'pageSize'          => $pageSize,
			'pageToken'         => $pageToken,
			'q'                 => '',
			'spaces'            => 'drive',
			'fields'            => 'files(fileExtension,id,kind,mimeType,name,parents,size,spaces,starred),nextPageToken',
		];

		if (!empty($teamDriveID))
		{
			$params = array_merge($params, [
				'corpora'                   => 'drive',
				'includeItemsFromAllDrives' => 'true',
				'driveId'                   => $teamDriveID,
			]);
		}

		if (empty($pageToken))
		{
			unset ($params['pageToken']);
		}

		$searchParam = '';

		if (!empty($parentId))
		{
			$parentIdQuoted = str_replace('\'', '\\\'', $parentId);
			$searchParam    = "'$parentIdQuoted' in parents";
		}

		if ($search)
		{
			if (!empty($searchParam))
			{
				$searchParam .= " and ($search)";
			}
			else
			{
				$searchParam = $search;
			}
		}

		$params['q'] = $searchParam;

		$result = $this->fetch('GET', 'files', $params);

		return $result;
	}

	/**
	 * Get the processed listing of a folder
	 *
	 * @param   string  $parentId     The parent folder Id (default: 'root')
	 * @param   string  $search       Additional search criteria to apply, see https://developers.google.com/drive/v3/web/search-parameters
	 * @param   int     $pageSize     The pagination size, default 100
	 * @param   string  $pageToken    The page continuation token from a previous request
	 * @param   string  $orderBy      Ordering for the results, defaults to "folder,name" (folders first, then sort by name ascending)
	 * @param   string  $teamDriveID  The ID of the Google Team Drive. Empty means we should use the personal Drive (default).
	 *
	 * @return  array  Two arrays under keys folders and files. Each array's key is the file/folder name. For the values see above.
	 */
	public function listContents($parentId = 'root', $search = null, $pageSize = 100, $pageToken = null, $orderBy = 'folder,name', $teamDriveID = '')
	{
		$result = $this->getRawContents($parentId, $search, $pageSize, $pageToken, $orderBy, $teamDriveID);

		$return = [
			'files'   => [],
			'folders' => [],
		];

		if (!isset($result['files']) || !count($result['files']))
		{
			return $return;
		}

		foreach ($result['files'] as $item)
		{
			if ($item['mimeType'] == 'application/vnd.google-apps.folder')
			{
				$return['folders'][$item['name']] = [
					'id'      => $item['id'],
					'parents' => $item['parents'],
				];

				continue;
			}

			$return['files'][$item['name']] = [
				'id'            => $item['id'],
				'parents'       => $item['parents'],
				'size'          => $item['size'],
				'fileExtension' => $item['fileExtension'],
			];
		}

		return $return;
	}

	/**
	 * Try to get the ID for a file.
	 *
	 * @param   string  $path           Human readable path to the file
	 * @param   bool    $createFolders  Should I create the enclosing folders if they do not exist?
	 * @param   string  $teamDriveID    The ID of the Google Team Drive. Empty means we should use the personal Drive (default).
	 *
	 * @return  null|string     Null if the file doesn't exist (but the path does), the ID otherwise
	 *
	 * @throws  RuntimeException  When the path does not exist
	 */
	public function getIdForFile($path, $createFolders = false, $teamDriveID = '')
	{
		$parentPath = dirname($path);
		$fileName   = basename($path);
		$parentPath = trim($parentPath, '/');
		$parentId   = empty($teamDriveID) ? 'root' : $teamDriveID;

		if (!empty($parentPath))
		{
			$parentId = $this->getIdForFolder($parentPath, $createFolders, $teamDriveID);
		}

		if (is_null($parentId))
		{
			throw new RuntimeException("The path $parentPath does not exist in your Google Drive");
		}

		// Try to find the last part
		$search  = 'name = \'' . str_replace('\'', '\\\'', $fileName) . '\'';
		$results = $this->getRawContents($parentId, $search, 1, null, 'folder,name', $teamDriveID);

		if (empty($results['files']))
		{
			return null;
		}

		return $results['files'][0]['id'];
	}

	/**
	 * Try to get the ID to a folder
	 *
	 * @param   string  $path           Human readable path to the folder
	 * @param   bool    $createFolders  Should I create any missing folders?
	 * @param   string  $teamDriveID    The ID of the Google Team Drive. Empty means we should use the personal Drive (default).
	 *
	 * @return  null|string  Null if the folder doesn't exist, the ID of the folder otherwise
	 */
	public function getIdForFolder($path, $createFolders = false, $teamDriveID = '')
	{
		if (empty($path))
		{
			return empty($teamDriveID) ? 'root' : $teamDriveID;
		}

		$folders  = explode('/', $path);
		$parentId = empty($teamDriveID) ? 'root' : $teamDriveID;

		foreach ($folders as $folder)
		{
			// Search for a folder by the name $folder that has a parent $parentId
			$search  = 'name = \'' . str_replace('\'', '\\\'', $folder) . '\'' .
				' and mimeType = \'application/vnd.google-apps.folder\'';
			$results = $this->getRawContents($parentId, $search, 1, null, 'folder,name', $teamDriveID);

			// If found, set $parentId to this folder's ID
			if (!empty($results['files']))
			{
				$parentId = $results['files'][0]['id'];

				continue;
			}

			// Not found and we're told to not create the missing folders. Return null.
			if (!$createFolders)
			{
				return null;
			}

			// Not found, but we're asked to create the missing folders
			$parentId = $this->createFolder($parentId, $folder);

			/**
			 * Welcome to Google Insanity. Let me be your guide.
			 *
			 * If the folder does not exist we ask the Google Drive API to create it. If we try to use files.list to
			 * check if the folder is there the API returns the folder we just created. However, if we try to upload a
			 * new file into it the folder is reported as being non-existent and Google Drive tries to create a new
			 * folder with the same name. The problem is that we reasonably expect that our file is uploaded in the
			 * folder we created, not some a different folder with the same name and different file ID. This discrepancy
			 * causes uploads to fail.
			 *
			 * If I wait one second the same things happens. Two seconds? Same story. Three seconds? Well, this is
			 * interesting. If I wait THREE (3) seconds after I've created the folder then everything works as expected.
			 * Google Drive sees the folder we created and does use it during upload. So please do NOT remove this sleep
			 * or everything breaks. Peace.
			 */
			sleep(3);
		}

		return $parentId;
	}

	/**
	 * Create a folder named $name under the parent folder with id $parentId.
	 *
	 * If you know the human-readable path to the folder you want to create but not its parentId and/or you are not sure
	 * if the folder exists but want its Id anyway use:
	 * $this->getIdForFolder('/human/readable/path', true).
	 *
	 * @param   string  $parentId  The ID of the enclosing folder
	 * @param   string  $name      The name of the folder to create
	 *
	 * @return  string  The ID of the created folder
	 */
	public function createFolder($parentId, $name)
	{
		$folderName   = str_replace('"', '\\"', $name);
		$jsonDocument = <<< JSON
{
 "name": "$folderName",
 "parents": [
  "$parentId"
 ],
 "mimeType": "application/vnd.google-apps.folder"
}
JSON;

		$contentLength = strlen($jsonDocument);
		$result        = $this->fetch('POST', 'files?supportsAllDrives=true&fields=id', [
			'headers' => [
				'Content-Type: application/json; charset="utf-8"',
				'Content-Length: ' . $contentLength,
			],
		], $jsonDocument);

		return $result['id'];
	}

	/**
	 * Delete a file
	 *
	 * @param   string  $fileId       The ID of the file to delete
	 * @param   bool    $failOnError  Throw exception if the deletion fails? Default true.
	 *
	 * @return  bool  True on success
	 *
	 * @throws  Exception
	 */
	public function delete($fileId, $failOnError = true)
	{
		try
		{
			$result = $this->fetch('DELETE', 'files/' . $fileId, [
				'supportsAllDrives' => 'true',
			]);
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
	 * @param   string  $fileId     The ID of the file in Google Drive
	 * @param   string  $localFile  The absolute filesystem path where the file will be downloaded to
	 */
	public function download($fileId, $localFile)
	{
		$this->fetch('GET', "files/$fileId?alt=media", [
			'supportsAllDrives' => 'true',
			'file'              => $localFile,
		]);
	}

	/**
	 * Uploads a file as a single part. Up to 5Mb uploads.
	 *
	 * @param   string  $folderId    The ID of the folder to upload to
	 * @param   string  $localFile   The absolute local filesystem path
	 * @param   string  $remoteName  The name of the file on the remote storage, null to derive from localFile
	 * @param   string  $mimeType    The MIME type of the file. Defaults to application/octet-stream.
	 *
	 * @return  array   See https://developers.google.com/drive/v3/reference/files#resource-representations
	 */
	public function simpleUpload($folderId, $localFile, $remoteName = null, $mimeType = 'application/octet-stream')
	{
		// Make sure this file is 5Mb or smaller
		clearstatcache();
		$filesize = @filesize($localFile);

		if ($filesize > 5242880)
		{
			throw new RuntimeException("File size too big for simpleUpload ($filesize bigger than 5Mb).", 500);
		}

		// Make sure we have a remote name
		if (empty($remoteName))
		{
			$remoteName = basename($localFile);
		}

		// First we need to upload the file and get its ID
		$additional = [
			'file'    => $localFile,
			'headers' => [
				'Content-Type: ' . $mimeType,
				'Content-Length: ' . $filesize,
			],
		];
		$response   = $this->fetch('POST', self::uploadUrl . 'files?uploadType=media&supportsAllDrives=true', $additional);

		if (!isset($response['id']))
		{
			throw new RuntimeException("Could not upload $localFile");
		}

		$fileId = $response['id'];

		// Now we need to add to the parents list
		$remoteName   = str_replace('"', '\\"', $remoteName);
		$jsonDocument = <<< JSON
{
	"name": "$remoteName"
}
JSON;
		$additional   = [
			'headers'           => [
				'Content-Type: application/json',
			],
			'supportsAllDrives' => 'true',
		];

		$patchResponse = $this->fetch('PATCH', 'files/' . $fileId . '?&supportsAllDrives=true&addParents=' . $folderId, $additional, $jsonDocument);

		return $patchResponse;
	}

	/**
	 * Creates a new multipart upload session and returns its upload URL
	 *
	 * @param   string  $folderId    The ID of the folder to upload to
	 * @param   string  $localFile   The absolute local filesystem path
	 * @param   string  $remoteName  The name of the file on the remote storage, null to derive from localFile
	 * @param   string  $mimeType    The MIME type of the file. Defaults to application/octet-stream.
	 *
	 * @return  string|null  The upload URL for the session, null if the upload session wasn't created
	 *
	 * @see https://developers.google.com/drive/api/v3/resumable-upload
	 */
	public function createUploadSession($folderId, $localFile, $remoteName = null, $mimeType = 'application/octet-stream')
	{
		clearstatcache();
		$filesize = @filesize($localFile);

		$explicitPost = (object) [
			'name'    => $remoteName,
			'parents' => [
				$folderId,
			],
		];

		$explicitPost = json_encode($explicitPost);

		$response = $this->fetch('POST', self::uploadUrl . 'files?supportsAllDrives=true&uploadType=resumable', [
			'headers'         => [
				'Content-Type: application/json',
				'Content-Length: ' . strlen($explicitPost),
				'X-Upload-Content-Type: ' . $mimeType,
				'X-Upload-Content-Length: ' . $filesize,
			],
			'follow-redirect' => false,
			'no-parse'        => true,
			'curl-options'    => [
				CURLOPT_HEADER => true,
			],
		], $explicitPost);

		$lines = explode("\r", $response);

		foreach ($lines as $line)
		{
			$line = trim($line);

			if (empty($line))
			{
				continue;
			}

			if (strpos($line, ':') === false)
			{
				continue;
			}

			[$header, $value] = explode(": ", $line);

			if (strtolower($header) != 'location')
			{
				continue;
			}

			return $value;
		}

		return null;
	}

	/**
	 * Upload a part
	 *
	 * @param   string  $sessionUrl  The upload session URL, see createUploadSession
	 * @param   string  $localFile   Absolute filesystem path of the source file
	 * @param   int     $from        Starting byte to begin uploading, default is 0 (start of file)
	 * @param   int     $length      Chunk size in bytes, default 10Mb, must NOT be over 60Mb!  MUST be a multiple of 320Kb.
	 *
	 * @return  array  The upload information, see https://developers.google.com/drive/v3/reference/files#resource-representations
	 *
	 * @see https://developers.google.com/drive/api/v3/resumable-upload
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
			'headers'           => [
				'Content-Length: ' . $contentLength,
				'Content-Range: bytes ' . $range,
			],
			'supportsAllDrives' => 'true',
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
	 * Upload a file using multipart uploads. Useful for files over 100Mb and up to 2Gb.
	 *
	 * @param   string  $path         Relative path in the Drive
	 * @param   string  $localFile    Absolute filesystem path of the source file
	 * @param   int     $partSize     Part size in bytes, default 10Mb
	 * @param   string  $mimeType     The MIME type of the uploaded file, defaults to application/octet-stream
	 * @param   string  $teamDriveID  The ID of the Google Team Drive. Empty means we should use the personal Drive (default).
	 *
	 * @return  array  See https://developers.google.com/drive/v3/reference/files#resource-representations
	 *
	 * @throws Exception
	 *
	 * @see https://developers.google.com/drive/api/v3/resumable-upload
	 */
	public function resumableUpload($path, $localFile, $partSize = 10485760, $mimeType = 'application/octet-stream', $teamDriveID = '')
	{
		[$fileName, $folderId] = $this->preprocessUploadPath($path, $teamDriveID);

		$sessionUrl = $this->createUploadSession($folderId, $localFile, $fileName, $mimeType);
		$from       = 0;

		while (true)
		{
			$result = $this->uploadPart($sessionUrl, $localFile, $from, $partSize);

			$from += $partSize;

			// If the result doesn't have nextExpectedRanges we have finished uploading.
			if (isset($result['name']))
			{
				return $result;
			}
		}
	}

	/**
	 * Automatically decides which upload method to use to upload a file to Google Drive. This method will return when
	 * the entire file has been uploaded. If you want to implement staggered uploads use the createUploadSession and
	 * uploadPart methods.
	 *
	 * @param   string  $path         The remote path relative to Drive root
	 * @param   string  $localFile    The absolute local filesystem path
	 * @param   int     $partSize     Part size in bytes, default 10Mb
	 * @param   string  $mimeType     The MIME type of the uploaded file, defaults to application/octet-stream
	 * @param   string  $teamDriveID  The ID of the Google Team Drive. Empty means we should use the personal Drive (default).
	 *
	 * @return  array  See https://developers.google.com/drive/v3/reference/files#resource-representations
	 *
	 * @throws Exception
	 */
	public function upload($path, $localFile, $partSize = 10485760, $mimeType = 'application/octet-stream', $teamDriveID = '')
	{
		clearstatcache();
		$filesize = @filesize($localFile);

		/**
		 * Google Drive has a hard limit of 5MB for simple uploads. If the file is bigger than 5MB **OR** bigger than
		 * the part size (whatever is smaller) **THEN** we have to use resumable uploads.
		 */
		$smallestSizeForSinglePartUpload = min(5242880, $partSize);
		if ($filesize > $smallestSizeForSinglePartUpload)
		{
			return $this->resumableUpload($path, $localFile, $partSize, $mimeType, $teamDriveID);
		}

		// Smaller files, use simple upload
		[$fileName, $folderId] = $this->preprocessUploadPath($path, $teamDriveID);

		return $this->simpleUpload($folderId, $localFile, $fileName, $mimeType);
	}

	/**
	 * Converts a human readable path into a folder ID and directory name. If any folder in the path does not exist it
	 * will be created. If a file by the same name already exists in the folder it will be deleted.
	 *
	 * @param   string  $path         The human readable path to the file
	 * @param   string  $teamDriveID  The ID of the Google Team Drive. Empty means we should use the personal Drive (default).
	 *
	 * @return  array  array($fileName, $folderId)
	 *
	 * @throws Exception
	 */
	public function preprocessUploadPath($path, $teamDriveID = '')
	{
		// Get the folder and file name
		$folderName = dirname($path);
		$fileName   = basename($path);
		$folderName = trim($folderName, '/');
		$folderId   = empty($teamDriveID) ? 'root' : $teamDriveID;

		// Find or create the folder
		if (!empty($folderName))
		{
			$folderId = $this->getIdForFolder($folderName, true, $teamDriveID);
		}

		// If I have a file by the same name in this directory, kill it
		$search  = 'name = \'' . str_replace('\'', '\\\'', $fileName) . '\'';
		$results = $this->getRawContents($folderId, $search, 1, null, 'folder,name', $teamDriveID);

		if (!empty($results['files']))
		{
			$fileId = $results['files'][0]['id'];
			$this->delete($fileId, false);

			return [$fileName, $folderId];
		}

		return [$fileName, $folderId];
	}

	/**
	 * Execute an API call
	 *
	 * @param   string  $method        The HTTP method
	 * @param   string  $relativeUrl   The relative URL to ping
	 * @param   array   $additional    Additional parameters
	 * @param   mixed   $explicitPost  Passed explicitly to POST requests if set, otherwise $additional is passed.
	 *
	 * @return  array
	 * @throws  RuntimeException
	 *
	 */
	protected function fetch($method, $relativeUrl, array $additional = [], $explicitPost = null)
	{
		// Get full URL, if required
		$url = $relativeUrl;

		if (substr($relativeUrl, 0, 6) != 'https:')
		{
			$url = self::rootUrl . ltrim($relativeUrl, '/');
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

		// Some broken cURL versions cause an error. Forcing HTTP/1.1 seems to be fixing it.
		if (defined('CURLOPT_HTTP_VERSION') && defined('CURL_HTTP_VERSION_1_1'))
		{
			$options[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;
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
		elseif (($method == 'POST' && !$fp))
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
		elseif ($method == 'POST' && $fp)
		{
			$options[CURLOPT_POST] = true;

			$data = '';

			while (!feof($fp))
			{
				$data .= fread($fp, 1024768);
			}

			$options[CURLOPT_POSTFIELDS] = $data;
		}
		elseif ($method == 'GET' && !empty($additional))
		{
			$extraQuery = http_build_query($additional);
			$glue       = (strpos($url, '?') === false) ? '?' : '&';
			$url        .= $glue . $extraQuery;

			curl_setopt($ch, CURLOPT_URL, $url);
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
		//@curl_setopt($ch, CURLOPT_VERBOSE, true);
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
		$originalResponse = $response;
		$response         = json_decode($response, true);

		// Did we get invalid JSON data?
		if (!empty($originalResponse) && !$response)
		{
			throw new RuntimeException("Invalid JSON data received: $originalResponse", 500);
		}
		elseif (empty($originalResponse))
		{
			$response = [];
		}

		unset($originalResponse);

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
}
