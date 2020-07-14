<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Postproc\Connector;



use Exception;
use SimpleXMLElement;

/**
 * iDriveSync EVS API implementation in PHP
 *
 * Based on the official connection library provided by http://www.idrivesync.com/evs
 *
 * @license GPLv3 or later
 *
 */
class Idrivesync
{
	/** @var array Holds the connection information data */
	private $data = [];

	/**
	 * Public constructor. Tries to connect to iDriveSync using the provided
	 * login credentials.
	 *
	 * @param   string  $uid          The username or email address
	 * @param   string  $pwd          The user's password
	 * @param   string  $pvtkey       The optional private key, if private encryption is being used in the account
	 * @param   bool    $newendpoint  Should I user the new endpoint?
	 *
	 * @throws  Exception
	 */
	public function __construct($uid, $pwd, $pvtkey = '', $newendpoint = false)
	{
		$this->data['uid']     = $uid;
		$this->data['pwd']     = $pwd;
		$this->data['pvtkey']  = $pvtkey;
		$this->data['crtpath'] = AKEEBA_CACERT_PEM;

		// Since somewhen in 2014 iDriveSynch started to register the users under
		// the idrive.com domain, so we have to use a different API endpoint
		if ($newendpoint)
		{
			$url = "https://evs.idrive.com/evs";
		}
		else
		{
			$url = "https://evs.idrivesync.com/evs";
		}


		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url . '/getServerAddress');

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);

		$body = 'uid=' . urlencode($this->data['uid']) . '&pwd=' . urlencode($this->data['pwd']);

		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded; charset=UTF-8']);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_CAINFO, $this->data['crtpath']);

		$output = curl_exec($ch);

		$errno = curl_errno($ch);
		$error = curl_error($ch);

		if ($errno)
		{
			throw new Exception($error, $errno);
		}

		curl_close($ch);

		$ServerInfo         = new SimpleXMLElement($output);
		$Message            = $ServerInfo['message'];
		$cmdUtilityServer   = $ServerInfo['cmdUtilityServer'];
		$cmdUtilityServerIP = $ServerInfo['cmdUtilityServerIP'];
		$webApiServer       = $ServerInfo['webApiServer'];
		$webApiServerIP     = $ServerInfo['webApiServerIP'];

		$this->data['weburl'] = $webApiServer;
		$this->data['webip']  = $webApiServerIP;
		$this->data['cmdurl'] = $cmdUtilityServer;
		$this->data['cmdip']  = $cmdUtilityServerIP;
	}

	/**
	 * Uploads a file to iDriveSync
	 *
	 * @param   string  $localFile   The full path to the local file to be uploaded
	 * @param   string  $remotePath  The path in iDriveSync
	 *
	 * @throws  Exception
	 */
	public function uploadFile($localFile, $remotePath = '/')
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->data['weburl'] . '/evs/uploadFile');

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);

		if (function_exists('curl_file_create'))
		{
			$postFileValue = curl_file_create($localFile, 'application/octet-stream', basename($localFile));
		}
		else
		{
			$postFileValue = '@' . str_replace('\\', '/', $localFile) . ';filename=' . basename($localFile);
		}

		$postFields = [
			'uid'  => $this->data['uid'],
			'pwd'  => $this->data['pwd'],
			'p'    => $remotePath,
			'file' => $postFileValue,
		];
		if (isset($this->data['pvtkey']))
		{
			$postFields['pvtkey'] = $this->data['pvtkey'];
		}

		curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['multipart/form-data']);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_CAINFO, $this->data['crtpath']);

		$output = curl_exec($ch);

		$errno = curl_errno($ch);
		$error = curl_error($ch);

		if ($errno)
		{
			throw new Exception($error, $errno);
		}

		curl_close($ch);

		$info    = new SimpleXMLElement($output);
		$message = $info['message'];

		if ($message != 'SUCCESS')
		{
			$error = $info['desc'];
			throw new Exception($error, 500);
		}
	}

	/**
	 * Does a file or folder exist in iDriveSync?
	 *
	 * @param   string  $path  The full path to the file/folder
	 *
	 * @return  boolean  True if it exists, false otherwise
	 *
	 * @throws  Exception
	 */
	public function isFileFolderExists($path = '/')
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->data['weburl'] . '/evs/isFileFolderExists');

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);

		$body = 'uid=' . urlencode($this->data['uid'])
			. '&pwd=' . urlencode($this->data['pwd'])
			. '&p=' . urlencode($path);

		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded; charset=UTF-8']);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_CAINFO, $this->data['crtpath']);

		$output = curl_exec($ch);

		$errno = curl_errno($ch);
		$error = curl_error($ch);

		if ($errno)
		{
			throw new Exception($error, $errno);
		}

		curl_close($ch);

		$info    = new SimpleXMLElement($output);
		$message = $info['message'];

		if ($message == 'SUCCESS')
		{
			return true;
		}
		elseif ($message == 'FAIL')
		{
			return false;
		}
		else
		{
			$error = $info['desc'];
			throw new Exception($error, 500);
		}
	}


	/**
	 * Download a file from iDriveSync
	 *
	 * @param   string  $remoteFile
	 * @param   string  $localFile
	 *
	 * @throws  Exception
	 */
	public function downloadFile($remoteFile, $localFile)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->data['weburl'] . '/evs/downloadFile');

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);

		$body = 'uid=' . urlencode($this->data['uid'])
			. '&pwd=' . urlencode($this->data['pwd'])
			. '&p=' . urlencode($remoteFile);

		if (!empty($this->data['pvtkey']))
		{
			$body .= '&pvtkey=' . urlencode($this->data['pvtkey']);
		}

		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['multipart/form-data']);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_CAINFO, $this->data['crtpath']);

		$fp = @fopen($localFile, 'wb');
		if ($fp === false)
		{
			throw new Exception("Cannot open $locaFile for writing", 500);
		}
		curl_setopt($ch, CURLOPT_FILE, $fp);

		$output = curl_exec($ch);
		fclose($fp);
	}


	/**
	 * Deletes a file or folder
	 *
	 * @param   string  $remoteFile  The full path to the remote file
	 *
	 * @return  boolean  True on success
	 *
	 * @throws  Exception
	 */
	public function deleteFile($remoteFile)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->data['weburl'] . '/evs/deleteFile');

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);

		$body = 'uid=' . urlencode($this->data['uid'])
			. '&pwd=' . urlencode($this->data['pwd'])
			. '&p=' . urlencode($remoteFile);

		if (!empty($this->data['pvtkey']))
		{
			$body .= '&pvtkey=' . urlencode($this->data['pvtkey']);
		}

		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded; charset=UTF-8']);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_CAINFO, $this->data['crtpath']);

		$output = curl_exec($ch);

		$errno = curl_errno($ch);
		$error = curl_error($ch);

		if ($errno)
		{
			throw new Exception($error, $errno);
		}

		curl_close($ch);

		$info    = new SimpleXMLElement($output);
		$message = $info['message'];

		if ($message == 'SUCCESS')
		{
			return true;
		}
		elseif ($message == 'FAIL')
		{
			return false;
		}
		else
		{
			$error = $info['desc'];
			throw new Exception($error, 500);
		}
	}

	/**
	 * Lists the contents of a folder
	 *
	 * @param   string  $path  The full path to the remote folder
	 *
	 * @return  array  The contents of the folder, both files and subdirectories
	 *
	 * @throws  Exception
	 */
	public function browseFolder($path)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->data['weburl'] . '/evs/browseFolder');

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);

		$body = 'uid=' . urlencode($this->data['uid'])
			. '&pwd=' . urlencode($this->data['pwd'])
			. '&p=' . urlencode($path);

		if (!empty($this->data['pvtkey']))
		{
			$body .= '&pvtkey=' . urlencode($this->data['pvtkey']);
		}

		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded; charset=UTF-8']);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_CAINFO, $this->data['crtpath']);

		$output = curl_exec($ch);

		$errno = curl_errno($ch);
		$error = curl_error($ch);

		if ($errno)
		{
			throw new Exception($error, $errno);
		}

		curl_close($ch);

		$info    = new SimpleXMLElement($output);
		$message = $info['message'];

		if ($message == 'SUCCESS')
		{
			$out = [];
			foreach ($info->item as $item)
			{
				$out[] = [
					'type' => $item['restype'] ? 'file' : 'folder',
					'name' => $item['resname'],
					'size' => $item['size'],
				];
			}

			return $out;
		}
		else
		{
			$error = $info['desc'];
			throw new Exception($error, 500);
		}
	}
}
