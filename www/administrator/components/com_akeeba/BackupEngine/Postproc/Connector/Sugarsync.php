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

use Akeeba\Engine\Postproc\Connector\Sugarsync\Exception\Base as SugarsyncException;
use Akeeba\Engine\Postproc\ProxyAware;
use Akeeba\Engine\Util\FileCloseAware;
use Akeeba\Engine\Util\Utf8;
use DOMDocument;
use DOMElement;

/**
 * SugarSync PHP API class for Akeeba Engine
 */
class Sugarsync
{
	use FileCloseAware;
	use ProxyAware;

	/** @var string The URL to the SugarSync API endpoint */
	private $apiURL = 'https://api.sugarsync.com';

	private $userAgent = 'AkeebaEngine/7.0.0.dev';

	/** @var string The developer's access key */
	private $accessKey = '';

	/** @var string The developer's private key */
	private $privateKey = '';

	/** @var string The user's email address */
	private $userEmail = '';

	/** @var string The user's password */
	private $userPassword = '';

	/** @var string The API access token */
	private $accessToken = null;

	/** @var string The ID of the authenticated SugarSync user */
	private $userID = null;

	/**
	 * Public constructor. Remember to pass a configuration array with the keys
	 * access, private, email and password. Read the code for more info.
	 *
	 * @param   array  $config  The configuration array
	 *
	 * @throws SugarsyncException
	 */
	public function __construct($config = [])
	{
		// Fetch the configuration parameters
		$this->accessKey    = array_key_exists('access', $config) ? $config['access'] : '';
		$this->privateKey   = array_key_exists('private', $config) ? $config['private'] : '';
		$this->userEmail    = array_key_exists('email', $config) ? $config['email'] : '';
		$this->userPassword = array_key_exists('password', $config) ? $config['password'] : '';

		// Update the user agent with the version of the engine
		$this->userAgent = 'AkeebaEngine/' . (defined('AKEEBABACKUP_VERSION') ? AKEEBABACKUP_VERSION : AKEEBA_VERSION);
	}

	/**
	 * Is this object connected (authenticated) to SugarSync yet?
	 *
	 * @return bool
	 */
	public function isConnected()
	{
		return !empty($this->accessToken) && !empty($this->userID);
	}

	/**
	 * (Re-)Connect to SugarSync
	 *
	 * @param   array  $config  Optional override for configuration parameters
	 *
	 * @throws SugarsyncException
	 */
	public function connect($config = [])
	{
		// Apply configuration overrides
		if (array_key_exists('access', $config))
		{
			$this->accessKey = $config['access'];
		}
		if (array_key_exists('private', $config))
		{
			$this->privateKey = $config['private'];
		}
		if (array_key_exists('email', $config))
		{
			$this->userEmail = $config['email'];
		}
		if (array_key_exists('password', $config))
		{
			$this->userPassword = $config['password'];
		}

		// Check that all configuration parameters are in place
		if (empty($this->accessKey))
		{
			throw new SugarsyncException('You must set the developer access key');
		}
		if (empty($this->privateKey))
		{
			throw new SugarsyncException('You must set the developer private key');
		}
		if (empty($this->userEmail))
		{
			throw new SugarsyncException('You must set the user\'s email address');
		}
		if (empty($this->userPassword))
		{
			throw new SugarsyncException('You must set the user\'s password');
		}

		$xml = '<?xml version="1.0" encoding="UTF-8" ?>' . "\n";
		$xml .= '<authRequest>' . "\n";
		$xml .= '<username>' . Utf8::utf8_encode($this->userEmail) . '</username>' . "\n";
		$xml .= '<password>' . Utf8::utf8_encode($this->userPassword) . '</password>' . "\n";
		$xml .= '<accessKeyId>' . Utf8::utf8_encode($this->accessKey) . '</accessKeyId>' . "\n";
		$xml .= '<privateAccessKey>' . Utf8::utf8_encode($this->privateKey) . '</privateAccessKey>' . "\n";
		$xml .= '</authRequest>';

		$descriptor = [
			'method'         => 'authorization',
			'verb'           => 'POST',
			'data'           => $xml,
			'auth'           => false,
			'return_headers' => true,
		];

		$this->accessToken = null;
		$this->userID      = null;

		$ret = $this->apiCall($descriptor);

		$result = $ret['result'];

		// Extract the token
		if (preg_match('/Location:(.*?)\r/i', $result, $m))
		{
			$this->accessToken = $m[1];
		}

		// Extract the user ID
		$userStart    = strpos($result, '<user>') + 6;
		$userEnd      = strpos($result, '</user>');
		$userURL      = substr($result, $userStart, $userEnd - $userStart);
		$userParts    = explode('/', $userURL);
		$this->userID = array_pop($userParts);
	}

	/**
	 * Get a list of the top-level sync folders of the user's account
	 *
	 * @staticvar array|null $folders Caches the sync folders list
	 * @return array Sync folders as a display_name => internal_ID hash array
	 */
	public function getSyncFolders()
	{
		static $folders = null;

		if (is_null($folders))
		{
			if (!$this->isConnected())
			{
				$this->connect();
			}

			$descriptor = [
				'method' => 'user/' . $this->userID . '/folders/contents',
				'verb'   => 'GET',
			];

			$ret = $this->apiCall($descriptor);

			$xml = $ret['result'];
			$dom = new DOMDocument('1.0', 'UTF-8');
			$dom->loadXML($xml);
			$collections = $dom->getElementsByTagName('collection');
			$folders     = [];
			for ($i = 0; $i < $collections->length; $i++)
			{
				/** @var DOMElement $item */
				$item           = $collections->item($i);
				$name           = $item->getElementsByTagName('displayName')->item(0)->nodeValue;
				$ref            = $item->getElementsByTagName('ref')->item(0)->nodeValue;
				$refParts       = explode('/', $ref);
				$id             = array_pop($refParts);
				$folders[$name] = $id;
			}
			unset($dom);
		}

		return $folders;
	}

	/**
	 * Creates a new folder and returns its ID
	 *
	 * @param   string  $container      Container folder's ID or path
	 * @param   string  $newFoldername  The display name of the new folder
	 *
	 * @return string The ID of the created folder
	 *
	 * @throws SugarsyncException
	 */
	public function createFolder($container, $newFoldername)
	{
		if (substr($container, 0, 4) != ':sc:')
		{
			$container = $this->resolveFolder($container);
		}

		$xml = '<?xml version="1.0" encoding="UTF-8" ?>';
		$xml .= '<folder><displayName>' . Utf8::utf8_encode($newFoldername) . '</displayName></folder>';

		$descriptor = [
			'method'         => 'folder/' . $container,
			'verb'           => 'POST',
			'data'           => $xml,
			'return_headers' => true,
		];

		$ret    = $this->apiCall($descriptor);
		$result = $ret['result'];

		// Extract the URL
		if (preg_match('/Location:(.*?)\r/i', $result, $m))
		{
			$url = $m[1];
		}
		else
		{
			$url = '/';
		}

		$urlParts = explode('/', $url);

		return array_pop($urlParts);
	}

	/**
	 * Lists all subfolders of a folder
	 *
	 * @param   string  $container  Folder ID or path to list
	 *
	 * @return array Hashed array, folder name => folder ID
	 *
	 * @throws SugarsyncException
	 */
	public function getFolders($container)
	{
		if (substr($container, 0, 4) != ':sc:')
		{
			$container = $this->resolveFolder($container);
		}

		$descriptor = [
			'method' => 'folder/' . $container . '/contents?type=folder',
			'verb'   => 'GET',
		];
		$ret        = $this->apiCall($descriptor);

		$xml = $ret['result'];
		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->loadXML($xml);
		$collections = $dom->getElementsByTagName('collection');
		$folders     = [];
		for ($i = 0; $i < $collections->length; $i++)
		{
			/** @var DOMElement $item */
			$item           = $collections->item($i);
			$name           = $item->getElementsByTagName('displayName')->item(0)->nodeValue;
			$ref            = $item->getElementsByTagName('ref')->item(0)->nodeValue;
			$refParts       = explode('/', $ref);
			$xid            = array_pop($refParts);
			$folders[$name] = $xid;
		}
		unset($dom);

		return $folders;
	}

	/**
	 * Lists all files of a folder
	 *
	 * @param   string  $container  Folder ID or path to list
	 *
	 * @return array Hashed array, file name => file ID
	 *
	 * @throws SugarsyncException
	 */
	public function getFiles($container)
	{
		if (substr($container, 0, 4) != ':sc:')
		{
			$container = $this->resolveFolder($container);
		}

		$descriptor = [
			'method' => 'folder/' . $container . '/contents?type=file',
			'verb'   => 'GET',
		];
		$ret        = $this->apiCall($descriptor);

		$xml = $ret['result'];

		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->loadXML($xml);
		$collections = $dom->getElementsByTagName('file');
		$files       = [];
		for ($i = 0; $i < $collections->length; $i++)
		{
			/** @var DOMElement $item */
			$item         = $collections->item($i);
			$name         = $item->getElementsByTagName('displayName')->item(0)->nodeValue;
			$ref          = $item->getElementsByTagName('ref')->item(0)->nodeValue;
			$refParts     = explode('/', $ref);
			$xid          = array_pop($refParts);
			$files[$name] = $xid;
		}
		unset($dom);

		return $files;
	}

	/**
	 * Uploads a file, overwriting a file by the same name if one exists.
	 *
	 * @param   string       $container  Folder ID, or path to the folder, or full path to the file
	 * @param   string|null  $fileName   Name of the remote file, or null if a full path is provided in $container
	 * @param   string       $localFile  Full path to the local file to upload
	 *
	 * @return boolean True on success
	 *
	 * @throws SugarsyncException
	 */
	public function uploadFile($container, $fileName = null, $localFile = null)
	{
		if (substr($container, 0, 4) != ':sc:')
		{
			if (empty($fileName))
			{
				$pathParts = explode('/', $container);
				$fileName  = array_pop($pathParts);
				$container = implode('/', $pathParts);
			}

			$container = $this->resolveFolder($container, true);
		}

		// First check if the file already exists
		$files = $this->getFiles($container);

		if (!array_key_exists($fileName, $files))
		{
			$fileID = $this->createFile($container, $fileName);
		}
		else
		{
			$fileID = $files[$fileName];
		}

		$descriptor = [
			'method' => 'file/' . $fileID . '/data',
			'verb'   => 'PUT',
			'data'   => $localFile,
		];
		$ret        = $this->apiCall($descriptor);

		return true;
	}

	/**
	 * Creates an (empty) file
	 *
	 * @param   string       $container  Folder ID, or path to the folder, or full path to the file
	 * @param   string|null  $fileName   Name of the remote file, or null if a full path is provided in $container
	 *
	 * @return string The file ID
	 *
	 * @throws SugarsyncException
	 */
	public function createFile($container, $fileName = null, $mimeType = 'application/octet-stream')
	{
		if (substr($container, 0, 4) != ':sc:')
		{
			if (empty($fileName))
			{
				$pathParts = explode('/', $container);
				$fileName  = array_pop($pathParts);
				$container = implode('/', $pathParts);
			}
			$container = $this->resolveFolder($container, true);
		}

		// First check if the file already exists
		$files = $this->getFiles($container);

		if (array_key_exists($fileName, $files))
		{
			return $files[$fileName];
		}

		$xml = '<?xml version="1.0" encoding="UTF-8" ?>';
		$xml .= '<file>';
		$xml .= '<displayName>' . Utf8::utf8_encode($fileName) . '</displayName>';
		$xml .= '<mediaType>' . $mimeType . '</mediaType>';
		$xml .= '</file>';

		$descriptor = [
			'method'         => 'folder/' . $container,
			'verb'           => 'POST',
			'data'           => $xml,
			'return_headers' => true,
		];
		$ret        = $this->apiCall($descriptor);

		$result = $ret['result'];

		// Extract the URL
		if (preg_match('/Location:(.*?)\r/i', $result, $m))
		{
			$url = $m[1];
		}
		else
		{
			$url = '/';
		}

		$urlParts = explode('/', $url);

		return array_pop($urlParts);
	}

	/**
	 * Downloads a file
	 *
	 * @param   string       $container  A folder ID, or a folder path or a full path to the file to download
	 * @param   string|null  $file       Remote filename or null if $container is a full path
	 * @param   string|null  $localFile  Full path to the local file to write the data. If null, the raw file data will
	 *                                   be returned by this method.
	 */
	public function downloadFile($container, $file, $localFile = null)
	{
		if (substr($container, 0, 4) != ':sc:')
		{
			if (empty($file))
			{
				$pathParts = explode('/', $container);
				$file      = array_pop($pathParts);
				$container = implode('/', $pathParts);
			}
			$container = $this->resolveFolder($container);
		}

		// First check if the file already exists
		$files = $this->getFiles($container);

		if (array_key_exists($file, $files))
		{
			$fileID = $files[$file];
		}
		else
		{
			throw new SugarsyncException("File not found");
		}

		$descriptor = [
			'method' => 'file/' . $fileID . '/data',
			'verb'   => 'GET',
			'data'   => $localFile,
		];
		$ret        = $this->apiCall($descriptor);

		if (empty($localFile))
		{
			return $ret['result'];
		}
	}

	public function deleteFile($container, $file = null)
	{
		if (substr($container, 0, 4) != ':sc:')
		{
			if (empty($file))
			{
				$pathParts = explode('/', $container);
				$file      = array_pop($pathParts);
				$container = implode('/', $pathParts);
			}
			$container = $this->resolveFolder($container);
		}

		// First check if the file already exists
		$files = $this->getFiles($container);

		if (array_key_exists($file, $files))
		{
			$fileID = $files[$file];
		}
		else
		{
			throw new SugarsyncException("File not found");
		}

		$descriptor = [
			'method' => 'file/' . $fileID,
			'verb'   => 'DELETE',
		];
		$ret        = $this->apiCall($descriptor);

		return true;
	}

	/**
	 * Resolves a folder path to a folder ID
	 *
	 * @staticvar array $mappedFolders Cache of folder names to folder IDs
	 *
	 * @param   string  $folder         The path to the folder
	 * @param   bool    $createMissing  Should I create any folders which do not exist along the way?
	 *
	 * @return string The folder ID
	 */
	protected function resolveFolder($folder, $createMissing = false)
	{
		static $mappedFolders = [];

		if (!array_key_exists($folder, $mappedFolders))
		{
			// Break the folder into bits and pieces
			$folderParts = explode('/', $folder);

			// First, let's fetch a list of top-level sync folders
			$syncFolders = $this->getSyncFolders();

			// Is our top-level folder really a top-level folder?
			if (!array_key_exists($folderParts[0], $syncFolders))
			{
				// Treason! The user did not use a top-level folder!
				if (array_key_exists('Magic Briefcase', $syncFolders))
				{
					// OK, let's use the user's "Magic Briefcase"
					array_unshift($folderParts, 'Magic Briefcase');
				}
				else
				{
					// This should normally never, ever be executed
					$randomFolder = array_shift($syncFolders);
					array_unshift($syncFolders, $randomFolder);
					array_unshift($folderParts, 'Magic Briefcase');
				}
			}

			// Get the ID of the top-level folder
			$toplevelFolder = array_shift($folderParts);
			$toplevelID     = $syncFolders[$toplevelFolder];

			$folderID = $this->folderReduce($folderParts, $toplevelID, $createMissing);

			$mappedFolders[$folder] = $folderID;
		}

		return $mappedFolders[$folder];
	}

	/**
	 * Recursive internal function to reduce a stack of path parts to an ID.
	 * Used by resolveFolder().
	 *
	 * @param   array   $stack          Stack of path parts to resolve
	 * @param   string  $id             Folder ID relative to which I should resolve the stack
	 * @param   bool    $createMissing  Should I create missing folders along the way
	 *
	 * @return string The folder ID to which the stack resolves
	 *
	 * @throws SugarsyncException
	 */
	protected function folderReduce($stack, $id, $createMissing = false)
	{
		// Is the path fully reduced?
		if (empty($stack))
		{
			return $id;
		}

		// No? Get the next path fragment
		$search = array_shift($stack);

		// If the fragment is empty the path is, in fact, fully reduced.
		if (empty($search))
		{
			return $id;
		}

		$descriptor = [
			'method' => 'folder/' . $id . '/contents?type=folder',
			'verb'   => 'GET',
		];
		$ret        = $this->apiCall($descriptor);

		$xml = $ret['result'];
		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->loadXML($xml);
		$collections = $dom->getElementsByTagName('collection');
		$folders     = [];
		for ($i = 0; $i < $collections->length; $i++)
		{
			/** @var DOMElement $item */
			$item           = $collections->item($i);
			$name           = $item->getElementsByTagName('displayName')->item(0)->nodeValue;
			$ref            = $item->getElementsByTagName('ref')->item(0)->nodeValue;
			$refParts       = explode('/', $ref);
			$xid            = array_pop($refParts);
			$folders[$name] = $xid;
		}
		unset($dom);

		if (array_key_exists($search, $folders))
		{
			// Folder found; recurse
			return $this->folderReduce($stack, $folders[$search], $createMissing);
		}
		else
		{
			// The folder was not found
			if ($createMissing)
			{
				$newId = $this->createFolder($id, $search);

				return $this->folderReduce($stack, $newId, $createMissing);
			}
			else
			{
				throw new SugarsyncException("The requested folder could not be located in your SugarSync account");
			}
		}
	}

	/**
	 * Calls SugarSync's API and returns the results
	 *
	 * @param   array  $descriptor  An array describing the API call you want to make
	 *
	 * @return array
	 * @throws SugarsyncException
	 */
	protected function apiCall($descriptor = [])
	{
		// Get data from descriptor
		$suffix        = array_key_exists('method', $descriptor) ? $descriptor['method'] : '';
		$data          = array_key_exists('data', $descriptor) ? $descriptor['data'] : '';
		$verb          = array_key_exists('verb', $descriptor) ? $descriptor['verb'] : 'GET';
		$auth          = array_key_exists('auth', $descriptor) ? $descriptor['auth'] : true;
		$heads         = array_key_exists('headers', $descriptor) ? $descriptor['headers'] : [];
		$returnHeaders = array_key_exists('return_headers', $descriptor) ? $descriptor['return_headers'] : false;
		$silenceErrors = array_key_exists('shutup', $descriptor) ? $descriptor['shutup'] : false;

		// Make sure the HTTP verb is a supported one
		if (!in_array($verb, ['GET', 'POST', 'PUT', 'DELETE']))
		{
			$verb = 'GET';
		}

		// Calculate the URL
		$url = $this->apiURL . '/' . $suffix;

		// Create the HTTP headers array
		$headers = [
			'Expect:',
		];
		$headers = array_merge($headers, $heads);

		// Handle extra headers for authorised API calls
		if ($auth && !$this->isConnected())
		{
			$this->connect();
		}
		if ($auth)
		{
			$headers[] = 'Authorization: ' . $this->accessToken;
		}

		$ch = curl_init($url);

		$this->applyProxySettingsToCurl($ch);

		curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
		@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		@curl_setopt($ch, CURLOPT_CAINFO, AKEEBA_CACERT_PEM);

		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

		$fp = null;
		switch ($verb)
		{
			case 'POST':
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
				curl_setopt($ch, CURLOPT_POST, true);
				$headers[] = 'Content-Type: application/xml; charset=UTF-8';
				$headers[] = 'Content-Length: ' . strlen($data);
				break;

			case 'PUT':
				if (is_file($data) && is_readable($data))
				{
					$headers[] = 'Content-Length: ' . filesize($data);
					$fp        = fopen($data, 'r');
					curl_setopt($ch, CURLOPT_PUT, true);
					curl_setopt($ch, CURLOPT_INFILE, $fp);
					curl_setopt($ch, CURLOPT_INFILESIZE, filesize($data));
				}
				else
				{
					throw new SugarsyncException("$data is not readable; can not upload to SugarSync");
				}
				break;

			case 'DELETE':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
				break;

			case 'GET':
				if (!empty($data))
				{
					$fp = fopen($data, 'w');
					curl_setopt($ch, CURLOPT_FILE, $fp);
				}
				curl_setopt($ch, CURLOPT_POST, false);
				break;
		}

		curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		if ($returnHeaders)
		{
			curl_setopt($ch, CURLOPT_HEADER, true);
		}

		$result = curl_exec($ch);
		$info   = curl_getinfo($ch);
		$errno  = curl_errno($ch);
		$error  = curl_error($ch);

		@curl_close($ch);

		if (!is_null($fp))
		{
			$this->conditionalFileClose($fp);
		}

		if (!$silenceErrors && ($errno != 0))
		{
			throw new SugarsyncException("Network error [$errno]: $error");
		}

		$ret = [
			'result' => $result,
			'info'   => $info,
			'errno'  => $errno,
			'error'  => $error,
		];

		$http_code = $info['http_code'];

		if ($silenceErrors || (($http_code >= 200) && ($http_code <= 299)))
		{
			return $ret;
		}

		if ($http_code == 400)
		{
			throw new SugarsyncException("HTTP Error [$http_code]: Required information was not provided to SugarSync");
		}

		if ($http_code == 401)
		{
			throw new SugarsyncException("HTTP Error [$http_code]: The credentials were rejected by SugarSync. Check the Access Key ID, Private Access Key, Email and Password in your configuration.");
		}

		if ($http_code == 403)
		{
			throw new SugarsyncException("HTTP Error [$http_code]: Failed authentication.");
		}

		if ($http_code == 404)
		{
			throw new SugarsyncException("HTTP Error [$http_code]: Not found.");
		}

		throw new SugarsyncException("HTTP Error [$http_code]: Server Error; SugarSync's API service may be down or experiencing a technical problem");
	}
}
