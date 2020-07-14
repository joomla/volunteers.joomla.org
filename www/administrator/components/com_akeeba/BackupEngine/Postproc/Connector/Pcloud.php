<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Postproc\Connector;

use RuntimeException;

/**
 * Connector for pCloud, using the HTTP JSON Protocol and OAuth2 authentication
 *
 * @see  https://docs.pcloud.com/protocols/http_json_protocol/uploading_files.html
 */
class Pcloud
{
	/**
	 * The URL of the helper script which is used to get fresh API tokens
	 */
	const helperUrl = 'https://www.akeebabackup.com/oauth2/pcloud.php';

	/**
	 * The root URL for the pCloud API
	 */
	protected $rootUrl = 'https://api.pcloud.com/';

	/**
	 * The access token for connecting to pCloud
	 *
	 * @var string
	 */
	protected $accessToken = '';

	/**
	 * Default cURL options
	 *
	 * @var array
	 */
	protected $defaultOptions = [
		CURLOPT_SSL_VERIFYPEER => true,
		CURLOPT_SSL_VERIFYHOST => true,
		CURLOPT_VERBOSE        => true,
		CURLOPT_TCP_KEEPALIVE  => true,
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
	 * @param   string  $accessToken  The access token for accessing pCloud
	 * @param   string  $dlid         The AkeebaBackup.com Download ID, used whenever you try to refresh the token
	 */
	public function __construct($accessToken, $dlid)
	{
		$this->accessToken = $accessToken;
		$this->dlid        = $dlid;
	}

	/**
	 * Get information about the currently logged in user
	 *
	 * @return  array
	 */
	public function userInfo()
	{
		return $this->fetch('GET', 'userinfo');
	}

	/**
	 * Create a directory. Its parent directory must already exist.
	 *
	 * @param   string  $path          The path of the folder to create, relative to root
	 * @param   bool    $failIfExists  Should I get an error back if the folder already exists?
	 *
	 * @return  array
	 */
	public function makeDirectory($path, $failIfExists = false)
	{
		$relativeUrl = $failIfExists ? 'createfolder' : 'createfolderifnotexists';
		$path        = '/' . trim($path, '/');

		return $this->fetch('GET', $relativeUrl, [
			'path' => $path,
		]);
	}

	/**
	 * List the contents of a given path
	 *
	 * @param   string  $path
	 *
	 * @return  array
	 */
	public function listContents($path = '/')
	{
		$rawContents = $this->fetch('GET', 'listfolder', [
			'path' => $path,
		]);

		$ret = [
			'files'   => [],
			'folders' => [],
		];

		if (!isset($rawContents['metadata']) || !isset($rawContents['metadata']['contents']))
		{
			return $ret;
		}

		foreach ($rawContents['metadata']['contents'] as $entry)
		{
			if ($entry['isfolder'])
			{
				$ret['folders'][$entry['name']] = $entry['folderid'];

				continue;
			}

			$ret['files'][$entry['name']] = $entry['size'];
		}

		return $ret;
	}

	/**
	 * Upload a file in a single try. The folder you are uploading to must already exist.
	 *
	 * @param   string  $path       Path to the file, relative to root
	 * @param   string  $localFile  ABsolute filesystem path to the file being uploaded
	 *
	 * @return  array
	 */
	public function simpleUpload($path, $localFile)
	{
		$additional = [
			'path'      => dirname($path),
			'filename'  => basename($path),
			'nopartial' => 1,
			'file'      => $localFile,
		];

		$response = $this->fetch('PUT', 'uploadfile', $additional);

		return $response['metadata'][0];
	}

	/**
	 * Delete a file
	 *
	 * @param   string  $path         The relative path to the file to delete
	 * @param   bool    $failOnError  Throw exception if the deletion fails? Default true.
	 *
	 * @return  bool  True on success
	 *
	 * @throws  RuntimeException
	 */
	public function delete($path, $failOnError = true)
	{
		$path = '/' . trim($path, '/');

		$additional = [
			'path' => $path,
		];

		try
		{
			$response = $this->fetch('GET', 'deletefile', $additional);
		}
		catch (RuntimeException $e)
		{
			if ($failOnError)
			{
				throw $e;
			}

			return false;
		}

		return isset($response['metadata']) && $response['metadata']['isdeleted'];
	}

	/**
	 * Download a file to your server
	 *
	 * @param   string  $path       Absolute path of the file in pCloud
	 * @param   string  $localFile  Absolute local filesystem to save the file to
	 *
	 * @return  void
	 */
	public function download($path, $localFile)
	{
		$dlURL = $this->getSignedUrl($path);

		$this->fetch('GET', $dlURL, [
			'no-parse' => 1,
			'file'     => $localFile,
		]);
	}

	/**
	 * Get a download URL for a file in pCloud
	 *
	 * @param   string  $path  Absolute path of the file in pCloud
	 *
	 * @return  string  Download URL
	 */
	public function getSignedUrl($path)
	{
		$path = '/' . trim($path, '/');

		$dlInfo = $this->fetch('GET', 'getfilelink', [
			'path' => $path,
		]);

		return 'https://' . $dlInfo['hosts'][0] . $dlInfo['path'];
	}

	/**
	 * Execute an API call
	 *
	 * @param   string  $verb          The HTTP verb
	 * @param   string  $relativeUrl   The relative URL to ping
	 * @param   array   $urlParams     Additional URL parameters
	 * @param   mixed   $explicitPost  Passed explicitly to POST requests if set, otherwise $additional is passed.
	 *
	 * @return  array|string
	 * @throws  RuntimeException
	 */
	protected function fetch($verb, $relativeUrl, array $urlParams = [], $explicitPost = null)
	{
		// Add authentication to the URL parameters
		$urlParams['access_token'] = $this->accessToken;

		// Am I told to not parse the result?
		$noParse = false;

		if (isset($urlParams['no-parse']))
		{
			$noParse = boolval($urlParams['no-parse']);
			unset ($urlParams['no-parse']);
		}

		// Get absolute URL, if required
		if (substr($relativeUrl, 0, 6) != 'https:')
		{
			$relativeUrl = $this->rootUrl . ltrim($relativeUrl, '/');
		}

		// Get the default cURL options array
		$options = $this->defaultOptions;

		// Do I have explicit cURL options to add?
		if (isset($urlParams['curl-options']) && is_array($urlParams['curl-options']))
		{
			// We can't use array_merge since we have integer keys and array_merge reassigns them :(
			foreach ($urlParams['curl-options'] as $k => $v)
			{
				$options[$k] = $v;
			}
		}

		$closeConnection = true;

		if (isset($urlParams['no-close']))
		{
			$closeConnection = !boolval($urlParams['no-close']);

			unset($urlParams['no-close']);
		}

		$ch = null;

		if (isset($urlParams['curl_handle']))
		{
			$ch = $urlParams['curl_handle'];

			unset($urlParams['curl_handle']);
		}

		// Handle files
		$file = null;
		$fp   = null;

		if (isset($urlParams['file']))
		{
			$file = $urlParams['file'];
			unset ($urlParams['file']);
		}

		if (!isset($urlParams['fp']) && !empty($file))
		{
			$mode = ($verb == 'GET') ? 'wb' : 'rb';
			$fp   = @fopen($file, $mode);
		}
		elseif (isset($urlParams['fp']))
		{
			$fp = $urlParams['fp'];
			unset($urlParams['fp']);
		}

		// Set up additional options
		if ($verb == 'GET')
		{
			if ($fp)
			{
				$options[CURLOPT_RETURNTRANSFER] = false;
				$options[CURLOPT_HEADER]         = false;
				$options[CURLOPT_FILE]           = $fp;
				$options[CURLOPT_BINARYTRANSFER] = true;
			}
		}
		elseif ($verb == 'POST')
		{
			$options[CURLOPT_POST] = true;

			if ($explicitPost)
			{
				$options[CURLOPT_POSTFIELDS] = $explicitPost;
			}
			elseif (!empty($urlParams))
			{
				$options[CURLOPT_POSTFIELDS] = $urlParams;
			}
		}
		elseif ($verb == 'PUT' && $fp)
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
			$options[CURLOPT_CUSTOMREQUEST] = $verb;

			if ($explicitPost)
			{
				$options[CURLOPT_POSTFIELDS] = $explicitPost;
			}
			elseif (!empty($urlParams))
			{
				$options[CURLOPT_POSTFIELDS] = $urlParams;
			}
		}

		// Am I told not to follow redirections?
		if (isset($urlParams['follow-redirect']))
		{
			if (boolval($urlParams['follow-redirect']))
			{
				$options[CURLOPT_FOLLOWLOCATION] = 1;
			}

			unset ($urlParams['follow-redirect']);
		}

		// Initialise and execute a cURL request
		if (is_null($ch))
		{
			$ch = curl_init();
		}

		@curl_setopt_array($ch, $options);
		curl_setopt($ch, CURLOPT_URL, $relativeUrl . '?' . http_build_query($urlParams));

		// Execute and parse the response
		$response = curl_exec($ch);
		$errNo    = curl_errno($ch);
		$error    = curl_error($ch);

		if ($closeConnection)
		{
			curl_close($ch);
		}

		// Close open file pointers
		if ($fp)
		{
			@fclose($fp);
		}

		// Did we have a cURL error?
		if ($errNo)
		{
			if (!$closeConnection)
			{
				curl_close($ch);
			}

			throw new RuntimeException("cURL error $errNo: $error", 500);
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
			if (!$closeConnection)
			{
				curl_close($ch);
			}

			throw new RuntimeException("Invalid JSON data received", 500);
		}

		// Did we get an error response?
		if (!isset($response['result']) || ($response['result'] != 0))
		{
			$error            = $response['result'];
			$errorDescription = isset($response['error']) ? $response['error'] : sprintf('pCloud error %d', $response['result']);

			if (!$closeConnection)
			{
				curl_close($ch);
			}

			throw new RuntimeException("Error $error: $errorDescription", $response['result']);
		}

		if (!$closeConnection)
		{
			$response['curl_handle'] = $ch;
		}

		return $response;
	}

}