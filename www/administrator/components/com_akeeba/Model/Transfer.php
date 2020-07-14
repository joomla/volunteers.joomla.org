<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Admin\Model;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\Engine\Factory;
use Akeeba\Engine\Util\RandomValue;
use Akeeba\Backup\Admin\Model\Exceptions\TransferFatalError;
use Akeeba\Backup\Admin\Model\Exceptions\TransferIgnorableError;
use Akeeba\Engine\Util\Transfer as EngineTransfer;
use Exception;
use FOF30\Download\Download;
use FOF30\Model\Model;
use JFile;
use Joomla\Uri\Uri;
use JText;
use JUri;
use RuntimeException;

class Transfer extends Model
{

	/**
	 * Get the information for the latest backup
	 *
	 * @param   $profileID  int|null  The profile ID for which to get the latest backup. Set to null to search all profiles.
	 *
	 * @return  array|null  An array of backup record information or null if there is no usable backup for site transfer
	 */
	public function getLatestBackupInformation($profileID = null)
	{
		// Initialise
		$ret = null;

		$db = $this->container->db;

		/** @var Statistics $model */
		$model = $this->container->factory->model('Statistics')->tmpInstance();
		$model->setState('limitstart', 0);
		$model->setState('limit', 1);

		if ($profileID > 0)
		{
			$model->setState('profile_id', $profileID);
		}

		$backups = $model->getStatisticsListWithMeta(false, null, $db->qn('id') . ' DESC');

		// No valid backups? No joy.
		if (empty($backups))
		{
			return $ret;
		}

		// Get the latest backup
		$backup = array_shift($backups);

		// If it's not stored on the server (e.g. remote backup), no joy.
		if ($backup['meta'] != 'ok')
		{
			return $ret;
		}

		// If it's not a full site backup, no joy.
		if ($backup['type'] != 'full')
		{
			return $ret;
		}

		return $backup;
	}

	/**
	 * Returns the amount of space required on the target server. The two array keys are
	 * size		In bytes
	 * string	Pretty formatted, user-friendly string
	 *
	 * @return  array
	 */
	public function getApproximateSpaceRequired()
	{
		$backup = $this->getLatestBackupInformation();

		if (is_null($backup))
		{
			return [
				'size'   => 0,
				'string' => '0.00 KB'
			];
		}

		$approximateSize = 2.5 * (float) $backup['size'];

		$unit	 = array('b', 'KB', 'MB', 'GB', 'TB', 'PB');

		if (version_compare(PHP_VERSION, '5.6.0', 'lt'))
		{
			return [
				'size'   => $approximateSize,
				'string' => @round($approximateSize / pow(1024, ($i = floor(log($approximateSize, 1024)))), 2) . ' ' . $unit[$i]
			];
		}

		return [
			'size'   => $approximateSize,
			'string' => @round($approximateSize / (1024 ** ($i = floor(log($approximateSize, 1024)))), 2) . ' ' . $unit[$i]
		];
	}

	/**
	 * Cleans up a URL and makes sure it is a valid-looking URL
	 *
	 * @param   string  $url  The URL to check
	 *
	 * @return  array  status [ok, invalid, same, notexists] (check status); url (the cleaned URL)
	 */
	public function checkAndCleanUrl($url)
	{
		// Initialise
		$result = [
			'status'	=> 'ok',
			'url'		=> $url
		];

		// Am I missing the protocol?
		if (strpos($url, '://') === false)
		{
			$url = 'http://' . $url;
		}

		$result['url'] = $url;

		// Verify that it is an HTTP or HTTPS URL.
		$uri = JUri::getInstance($url);
		$protocol = $uri->getScheme();

		if (!in_array($protocol, ['http', 'https']))
		{
			$result['status'] = 'invalid';

			return $result;
		}

		// Verify we are not restoring to the same site we are backing up from
		$path = $this->simplifyPath($uri->getPath());
		$uri->setPath('/' . $path);

		$siteUri = JUri::getInstance();

		if ($siteUri->getHost() == $uri->getHost())
		{
			$sitePath = $this->simplifyPath($siteUri->getPath());

			if ($sitePath == $path)
			{
				$result['status'] = 'same';

				return $result;
			}
		}

		$result['url'] = $uri->toString(['scheme', 'user', 'pass', 'host', 'port', 'path']);

		// Verify we can reach the domain. Since it can be an IP we check both name to IP and IP to name.
		$host = $uri->getHost();

		if (function_exists('idn_to_ascii'))
		{
			$host = idn_to_ascii($host);
		}

		$isValid = ($siteUri->getHost() == $uri->getHost()) || ($host == 'localhost') || ($host == '127.0.0.1') || (($host !== false) && checkdnsrr($host, 'A'));

		// Sometimes we have a domain name without a DNS record which *can* be accessed locally, e.g. through the hosts
		// file. We have to cater for that, just in case...
		if (!$isValid)
		{
			$download = new Download($this->container);
			$dummy = $download->getFromURL($uri->toString());

			$isValid = $dummy !== false;
		}

		if (!$isValid)
		{
			$result['status'] = 'notexists';

			return $result;
		}

		// All checks pass
		return $result;
	}

	/**
	 * Tries to simplify a server path to get the site's root. It can handle most forms on non-SEF and non-rewrite SEF
	 * URLs (as in index.php?foo=bar, something.php/this/is?completely=nuts#ok). It can't fix stupid but it tries really
	 * bloody hard to.
	 *
	 * @param   string  $path  The path to simplify. We *expect* this to contain nonsense.
	 *
	 * @return  string  The scrubbed clean URL, hopefully leading to the site's root.
	 */
	private function simplifyPath($path)
	{
		$path = ltrim($path, '/');

		if (empty($path))
		{
			return $path;
		}

		// Trim out anything after a .php file (including the .php file itself)
		if (substr($path, -1) != '/')
		{
			$parts = explode('/', $path);
			$newParts = [];

			foreach ($parts as $part)
			{
				if (substr($part, -4) == '.php')
				{
					break;
				}

				$newParts[] = $part;
			}

			$path = implode('/', $newParts);
		}

		if (substr($path, -13) == 'administrator')
		{
			$path = substr($path, 0, -13);
		}

		return $path;
	}

	/**
	 * Determines the status of FTP, FTPS and SFTP support. The returned array has two keys 'supported' and 'firewalled'
	 * each one being an array. You want the protocol to has its 'supported' value set to true and its 'firewalled'
	 * value set to false. This would mean that the server supports this protocol AND does not block outbound
	 * connections over this protocol.
	 *
	 * @return array
	 */
	public function getFTPSupport()
	{
		// Initialise
		$result = [
			'supported'  => [
				'ftpcurl'  => false,
				'ftpscurl' => false,
				'sftpcurl' => false,
				'ftp'      => false,
				'ftps'     => false,
				'sftp'     => false,
			],
			'firewalled' => [
				'ftpcurl'  => false,
				'ftpscurl' => false,
				'sftpcurl' => false,
				'ftp'      => false,
				'ftps'     => false,
				'sftp'     => false,
			]
		];

		// Necessary functions for each connection method
		$supportChecks = [
			'ftpcurl'	=> ['curl_init', 'curl_exec', 'curl_setopt', 'curl_errno', 'curl_error'],
			'ftpscurl'	=> ['curl_init', 'curl_exec', 'curl_setopt', 'curl_errno', 'curl_error'],
			'sftpcurl'	=> ['curl_init', 'curl_exec', 'curl_setopt', 'curl_errno', 'curl_error'],
			'ftp'	    => ['ftp_connect', 'ftp_login', 'ftp_close', 'ftp_chdir', 'ftp_mkdir', 'ftp_pasv', 'ftp_put', 'ftp_delete'],
			'ftps'	    => ['ftp_ssl_connect', 'ftp_login', 'ftp_close', 'ftp_chdir', 'ftp_mkdir', 'ftp_pasv', 'ftp_put', 'ftp_delete'],
			'sftp'	    => ['ssh2_connect', 'ssh2_auth_password', 'ssh2_auth_pubkey_file', 'ssh2_sftp', 'ssh2_exec', 'ssh2_sftp_unlink', 'ssh2_sftp_stat', 'ssh2_sftp_mkdir'],
		];

		// Determine which connection methods are supported
		$supported = [];

		foreach ($supportChecks as $protocol => $functions)
		{
			$supported[$protocol] = true;

			foreach ($functions as $function)
			{
				if (!function_exists($function))
				{
					$supported[$protocol] = false;

					break;
				}
			}
		}

		$result['supported'] = $supported;

		// Check firewall settings -- Disabled because the 3PD test server got clogged :(
		/**
		$result['firewalled'] = array(
			'ftp'      => !$result['supported']['ftp'] ? false : EngineTransfer\Ftp::isFirewalled(),
			'ftpcurl'  => !$result['supported']['ftp'] ? false : EngineTransfer\FtpCurl::isFirewalled(),
			'ftps'     => !$result['supported']['ftps'] ? false : EngineTransfer\Ftp::isFirewalled(['ssl' => true]),
			'ftpscurl' => !$result['supported']['ftp'] ? false : EngineTransfer\FtpCurl::isFirewalled(['ssl' => true]),
			'sftp'     => !$result['supported']['sftp'] ? false : EngineTransfer\Sftp::isFirewalled(),
			'sftpcurl' => !$result['supported']['sftp'] ? false : EngineTransfer\SftpCurl::isFirewalled(),
		);
		/**/

		return $result;
	}

	/**
	 * Checks the FTP connection parameters
	 *
	 * @param   array  $config  FTP/SFTP connection details
	 *
	 * @throws  RuntimeException
	 */
	public function testConnection(array $config)
	{
		/** @var EngineTransfer\TransferInterface $connector */
		$connector = $this->getConnector($config);

		// Is it the same site we are restoring from? It is if the configuration.php exists and has the same contents as
		// the one I read from our server.
		$this->checkIfSameSite($connector);

		// Only perform those checks if I'm not forcing the transfer
		if (!$config['force'])
		{
			// Check if there's a special file in this directory, e.g. .htaccess, php.ini, .user.ini or web.config.
			$this->checkIfHasSpecialFile($connector);

			// Check if there's another site present in this directory
			$this->checkIfExistingSite($connector);
		}

		// Does it match the URL to the site?
		$this->checkIfMatchesUrl($connector);
	}

	/**
	 * Upload Kickstart, our extra script and check that the target server fullfills our criteria
	 *
	 * @param   array  $config  FTP/SFTP connection details
	 *
	 * @throws  Exception
	 */
	public function initialiseUpload(array $config)
	{
		/** @var EngineTransfer\TransferInterface $connector */
		$connector = $this->getConnector($config);

		// Can I upload Kickstart and my extra script?
		$files = [
			JPATH_ADMINISTRATOR . '/components/com_akeeba/Master/Installers/kickstart.txt'  => 'kickstart.php',
			JPATH_ADMINISTRATOR . '/components/com_akeeba/Master/Installers/kickstart.transfer.php' => 'kickstart.transfer.php'
		];

		$createdFiles = [];
		$transferredSize = 0;
		$transferTime = 0;

		try
		{
			foreach ($files as $localFile => $remoteFile)
			{
				$start = microtime(true);
				$connector->upload($localFile, $connector->getPath($remoteFile));
				$end = microtime(true);
				$createdFiles[] = $remoteFile;
				$transferredSize += filesize($localFile);
				$transferTime += $end - $start;
			}
		}
		catch (Exception $e)
		{
			// An upload failed. Remove existing files.
			$this->removeRemoteFiles($connector, $createdFiles, true);

			throw new RuntimeException(JText::_('COM_AKEEBA_TRANSFER_ERR_CANNOTUPLOADKICKSTART'));
		}

		// Get the transfer speed between the two servers in bytes / second
		$transferSpeed = $transferredSize / $transferTime;

		try
		{
			$trustMeIKnowWhatImDoing = 500 + 10 + 1; // working around overzealous scanners written by bozos
			$connector->mkdir($connector->getPath('kicktemp'), $trustMeIKnowWhatImDoing);
		}
		catch (Exception $e)
		{
			// Don't sweat if we can't create our temporary directory.
		}

		// Can I run Kickstart and my extra script?
		try
		{
			$this->checkRemoteServerEnvironment($config['force']);
		}
		catch (Exception $e)
		{
			$this->removeRemoteFiles($connector, $createdFiles, true);

			throw $e;
		}

		// Get the lowest maximum execution time between our local and remote server
		$remoteTimeout = $this->container->platform->getSessionVar('transfer.remoteTimeLimit', 5, 'akeeba');
		$localTimeout = 5;

		if (function_exists('ini_get'))
		{
			$localTimeout = ini_get("max_execution_time");
		}

		$timeout = min($localTimeout, $remoteTimeout);

		if ($localTimeout == 0)
		{
			$timeout = $remoteTimeout;
		}
		elseif ($remoteTimeout == 0)
		{
			$timeout = $localTimeout;
		}

		if ($timeout == 0)
		{
			$timeout = 5;
		}

		// Get the maximimum transfer size, rounded down to 512K
		$maxTransferSize = $transferSpeed * $timeout;
		$maxTransferSize = floor($maxTransferSize / 524288) * 524288;

		if ($maxTransferSize == 0)
		{
			$maxTransferSize = 524288;
		}

		/**
		 * We never go above a maximum transfer size that depends on the server memory setting and the maximum remote
		 * upload size (minus 10Kb for overhead data)
		 */
		// Maximum chunk size determined by local server's memory constraints
		$chunkSizeLimit  = $this->getMaxChunkSize();
		// Chunk size selected by the user
		$userUploadLimit = $this->container->platform->getSessionVar('transfer.chunkSize', 5242880, 'akeeba') - 10240;
		// Maximum chunk size determined by the remote server
		$maxUploadLimit  = $this->container->platform->getSessionVar('transfer.uploadLimit', 5242880, 'akeeba') - 10240;
		// Calculated optimum chunk size (maxTransferSize is calculated by server-to-server speed limits)
		$maxTransferSize = min($maxUploadLimit, $userUploadLimit, $maxTransferSize, $chunkSizeLimit);

		/**
		 * A little explanation for "$maxUploadLimit / 4" below. We are uploading binary data which gets encoded as
		 * form data. The integer part is a rough estimation of the size discrepancy between raw and encoded data.
		 */
		if ($config['chunkMode'] == 'post')
		{
			$maxTransferSize = min(floor($maxUploadLimit / 4), $maxTransferSize, $chunkSizeLimit);
		}

		// Save the optimal transfer size in the session
		$this->container->platform->setSessionVar('transfer.fragSize', $maxTransferSize, 'akeeba');
	}

	/**
	 * Upload the next fragment
	 *
	 * @param   array  $config  FTP/SFTP connection details
	 *
	 * @throws  Exception
	 *
	 * @return  array
	 */
	public function uploadChunk(array $config)
	{
		$ret = [
			'result'    => true,
			'done'      => false,
			'message'   => '',
			'totalSize' => 0,
			'doneSize'  => 0
		];

		// Get information from the session
		$fragSize   = $this->container->platform->getSessionVar('transfer.fragSize', 5242880, 'akeeba');
		$backup     = $this->container->platform->getSessionVar('transfer.lastBackup', [], 'akeeba');
		$totalSize  = $this->container->platform->getSessionVar('transfer.totalSize', 0, 'akeeba');
		$doneSize   = $this->container->platform->getSessionVar('transfer.doneSize', 0, 'akeeba');
		$part       = $this->container->platform->getSessionVar('transfer.part', -1, 'akeeba');
		$frag       = $this->container->platform->getSessionVar('transfer.frag', -1, 'akeeba');

		// Do I need to update the total size?
		if (!$totalSize)
		{
			$totalSize = $backup['total_size'];
			$this->container->platform->setSessionVar('transfer.totalSize', $totalSize, 'akeeba');
		}

		$ret['totalSize'] = $totalSize;

		// First fragment of a new part
		if ($frag == -1)
		{
			$frag = 0;
			$part++;
		}

		/**
		 * If the backup is single part then $backup['multipart'] is 0. This means that the next if-block will report
		 * that the transfer is done. In these cases we have to convert $backup['multipart'] to 1 to let the upload
		 * actually run at all.
		 */
		if ($backup['multipart'] == 0)
		{
			$backup['multipart'] = 1;
		}

		// If I'm past the last part I'm done.
		if ($part >= $backup['multipart'])
		{

			// We are done
			$ret['done'] = true;
			return $ret;
		}

		// Get the information for this part
		$fileName = $this->getPartFilename($backup['absolute_path'], $part);
		$fileSize  = filesize($fileName);

		$intendedSeekPosition = $fragSize * $frag;

		// I am trying to seek past EOF. Oops. Upload the next part.
		if ($intendedSeekPosition >= $fileSize)
		{
			$this->container->platform->setSessionVar('transfer.frag', -1, 'akeeba');
			return $this->uploadChunk($config);
		}

		// Open the part
		$fp = @fopen($fileName, 'rb');

		if ($fp === false)
		{
			$ret['result'] = false;
			$ret['message'] = JText::sprintf('COM_AKEEBA_TRANSFER_ERR_CANNOTREADLOCALFILE', $fileName);

			return $ret;
		}

		// Seek to position
		if (fseek($fp, $intendedSeekPosition) == -1)
		{
			@fclose($fp);

			$ret['result'] = false;
			$ret['message'] = JText::sprintf('COM_AKEEBA_TRANSFER_ERR_CANNOTREADLOCALFILE', $fileName);

			return $ret;
		}

		// Read the data
		$data = fread($fp, $fragSize);
		$doneSize += strlen($data);
		$ret['doneSize'] = $doneSize;
		$this->container->platform->setSessionVar('transfer.doneSize', $doneSize, 'akeeba');

		// Upload the data
		$this->container->platform->setSessionVar('transfer.frag', $frag, 'akeeba');

		try
		{
			switch ($config['chunkMode'])
			{
				case 'post':
					$dataLength = $this->uploadUsingPost($fileName, $data);
					break;

				case 'chunked':
				default:
					$dataLength = $this->uploadUsingChunked($fileName, $data, $config);
					break;
			}
		}
		// A finally{} block is what we really need but it's not supported until PHP 5.5 and I'm stuck supporting 5.4 :(
		catch (\RuntimeException $e)
		{
			// Close the part
			fclose($fp);

			// Rethrow the exception
			throw $e;
		}

		// Close the part
		fclose($fp);

		// Update the session data
		$this->container->platform->setSessionVar('transfer.fragSize', $fragSize, 'akeeba');
		$this->container->platform->setSessionVar('transfer.totalSize', $totalSize, 'akeeba');
		$this->container->platform->setSessionVar('transfer.doneSize', $doneSize, 'akeeba');
		$this->container->platform->setSessionVar('transfer.part', $part, 'akeeba');
		$this->container->platform->setSessionVar('transfer.frag', ++$frag, 'akeeba');

		// Did I go past EOF? Then on to the next part
		$intendedSeekPosition += $dataLength;

		if ($intendedSeekPosition >= $fileSize)
		{
			$this->container->platform->setSessionVar('transfer.frag', -1, 'akeeba');
			$this->container->platform->setSessionVar('transfer.part', ++$part, 'akeeba');
		}

		// Did I reach the last part? Then I'm done
		if ($part >= $backup['multipart'])
		{
			// We are done
			$ret['done'] = true;
		}

		return $ret;
	}

	/**
	 * Reset the upload information. Required to start over.
	 *
	 * @return  void
	 */
	public function resetUpload()
	{
		$this->container->platform->setSessionVar('transfer.totalSize', 0, 'akeeba');
		$this->container->platform->setSessionVar('transfer.doneSize', 0, 'akeeba');
		$this->container->platform->setSessionVar('transfer.part', -1, 'akeeba');
		$this->container->platform->setSessionVar('transfer.frag', -1, 'akeeba');
	}

	/**
	 * Gets the TransferInterface connector object based on the $config configuration parameters array
	 *
	 * @param   array  $config  The configuration array with the FTP/SFTP connection information
	 *
	 * @return  EngineTransfer\TransferInterface
	 *
	 * @throws  RuntimeException
	 */
	private function getConnector(array $config)
	{
		switch ($config['method'])
		{
			case 'sftp':
				$connector = new EngineTransfer\Sftp($config);
				break;

			case 'sftpcurl':
				$connector = new EngineTransfer\SftpCurl($config);
				break;

			case 'ftpcurl':
			case 'ftpscurl':
				$connector = new EngineTransfer\FtpCurl($config);
				break;

			default:
				$connector = new EngineTransfer\Ftp($config);
				break;
		}

		return $connector;
	}

	/**
	 * Checks if the remote site is the same as the site we are running the wizard from.
	 *
	 * @param   EngineTransfer\TransferInterface  $connector
	 */
	private function checkIfSameSite(EngineTransfer\TransferInterface $connector)
	{
		$myConfiguration = @file_get_contents(JPATH_ROOT . '/configuration.php');

		if ($myConfiguration === false)
		{
			return;
		}

		try
		{
			$otherConfiguration = $connector->read($connector->getPath('configuration.php'));
		}
		catch (Exception $e)
		{
			// File not found. No harm done.

			return;
		}

		if ($otherConfiguration == $myConfiguration)
		{
			throw new RuntimeException(JText::_('COM_AKEEBA_TRANSFER_ERR_SAMESITE'));
		}
	}

	/**
	 * Check if there's a special file which might prevent site transfer from taking place.
	 *
	 * @param   EngineTransfer\TransferInterface  $connector
	 */
	private function checkIfHasSpecialFile(EngineTransfer\TransferInterface $connector)
	{
		$possibleFiles = ['.htaccess', 'web.config', 'php.ini', '.user.ini'];

		foreach ($possibleFiles as $file)
		{
			try
			{
				$fileContents = $connector->read($connector->getPath($file));
			}
			catch (Exception $e)
			{
				// File not found. No harm done.
				continue;
			}

			if (empty($fileContents))
			{
				continue;
			}

			throw new TransferIgnorableError(JText::sprintf('COM_AKEEBA_TRANSFER_ERR_HTACCESS', $file));
		}
	}

	/**
	 * Check if there's an existing site
	 *
	 * @param   EngineTransfer\TransferInterface  $connector
	 */
	private function checkIfExistingSite(EngineTransfer\TransferInterface $connector)
	{
		/**
		 * I run into a PHP bug. When we try to read 'wordpress/index.php' over FTP to determine if it exists we end up
		 * with the folder "wordpress" being created. I have only been able to reproduce with with VSFTPd. The VSFTPd
		 * log claims there is only an unsuccessful read operation. Why the folder is create is a mystery, but I have to
		 * remove it anyway. I know, right?
		 */
		// $possibleFiles = ['index.php', 'wordpress/index.php'];
		$possibleFiles = ['index.php'];

		foreach ($possibleFiles as $file)
		{
			try
			{
				$fileContents = $connector->read($connector->getPath($file));
			}
			catch (Exception $e)
			{
				// File not found. No harm done.
				continue;
			}

			if (empty($fileContents))
			{
				continue;
			}

			throw new TransferIgnorableError(JText::_('COM_AKEEBA_TRANSFER_ERR_EXISTINGSITE'));
		}
	}

	/**
	 * Check if the connection matches the site's stated URL
	 *
	 * @param   EngineTransfer\TransferInterface  $connector
	 */
	private function checkIfMatchesUrl(EngineTransfer\TransferInterface $connector)
	{
		$sourceFile = JPATH_SITE . '/media/com_akeeba/icons/akeeba-16.png';

		// Try to upload the file
		try
		{
			$connector->upload($sourceFile, $connector->getPath(basename($sourceFile)));
		}
		catch (Exception $e)
		{
			$errorMessage = JText::sprintf('COM_AKEEBA_TRANSFER_ERR_CANNOTUPLOADTESTFILE', basename($sourceFile));

			$errorMessage .= "  &mdash;  [ " . $e->getMessage() . ' ]';

			throw new RuntimeException($errorMessage);
		}

		// Try to fetch the file over HTTP
		$url = $this->container->platform->getSessionVar('transfer.url', '', 'akeeba');
		$url = rtrim($url, '/');

		$downloader = new Download($this->container);
		$wrongSSL   = false;
		$data       = $downloader->getFromURL($url . '/' . basename($sourceFile));

		/**
		 * The download of the test file failed. This can mean that the (S)FTP directory does not match the site URL we
		 * were given, DNS resolution does not work or we have an SSL issue. We are going to determine which one is it.
		 */
		if ($data === false)
		{
			$uri      = new Uri($url);
			$hostname = $uri->getHost();
			$results  = dns_get_record($hostname, DNS_A);

			// If there are no IPv4 records let's try to get IPv6 records
			if (count($results) == 0)
			{
				$results = dns_get_record($hostname, DNS_AAAA);
			}

			// No DNS records. So, that's why fetching data failed!
			if (count($results) == 0)
			{
				// Delete the temporary file
				$connector->delete($connector->getPath(basename($sourceFile)));

				// And now throw the error
				throw new TransferFatalError(JText::sprintf('COM_AKEEBA_TRANSFER_ERR_WRONGSSL', $hostname));
			}

			/**
			 * The DNS resolution worked. The next theory we have to test is that the SSL certificate is invalid or
			 * self-signed. The best way to do that without having to go through the OpenSSL extensions (which might not
			 * be installed or activated) is to do no SSL checking and retry the download. If that works we definitely
			 * have an SSL issue.
			 */
			$options = [
				CURLOPT_SSL_VERIFYPEER => 0,
				CURLOPT_SSL_VERIFYHOST => 0,
			];

			if ($downloader->getAdapterName() == 'fopen')
			{
				$options = [
					'ssl' => [
						'verify_peer' => false,
					],
				];
			}

			$downloader->setAdapterOptions($options);

			$wrongSSL = true;
			$data     = $downloader->getFromURL($url . '/' . basename($sourceFile));
		}

		// Delete the temporary file
		$connector->delete($connector->getPath(basename($sourceFile)));

		// Could we get it over HTTP?
		$originalData = file_get_contents($sourceFile);

		// Downloaded data is verified but the SSL certificate was bad: tell the user to fix the SSL certificate.
		if ($wrongSSL && ($originalData == $data))
		{
			throw new TransferFatalError(JText::_('COM_AKEEBA_TRANSFER_ERR_WRONGSSL'));
		}

		// Downloaded data did not match (no matter of the SSL verification): configuration error.
		if ($originalData != $data)
		{
			throw new TransferFatalError(JText::_('COM_AKEEBA_TRANSFER_ERR_CANNOTACCESSTESTFILE'));
		}
	}

	/**
	 * Gets the FTP configuration from the session
	 *
	 * @return  array
	 */
	public function getFtpConfig()
	{
		$transferOption = $this->container->platform->getSessionVar('transfer.transferOption', '', 'akeeba');

		return array(
			'method'      => $transferOption,
			'force'       => $this->container->platform->getSessionVar('transfer.force', 0, 'akeeba'),
			'host'        => $this->container->platform->getSessionVar('transfer.ftpHost', '', 'akeeba'),
			'port'        => $this->container->platform->getSessionVar('transfer.ftpPort', '', 'akeeba'),
			'username'    => $this->container->platform->getSessionVar('transfer.ftpUsername', '', 'akeeba'),
			'password'    => $this->container->platform->getSessionVar('transfer.ftpPassword', '', 'akeeba'),
			'directory'   => $this->container->platform->getSessionVar('transfer.ftpDirectory', '', 'akeeba'),
			'ssl'         => $transferOption == 'ftps',
			'passive'     => $this->container->platform->getSessionVar('transfer.ftpPassive', 1, 'akeeba'),
			'passive_fix' => $this->container->platform->getSessionVar('transfer.ftpPassiveFix', 1, 'akeeba'),
			'privateKey'  => $this->container->platform->getSessionVar('transfer.ftpPrivateKey', '', 'akeeba'),
			'publicKey'   => $this->container->platform->getSessionVar('transfer.ftpPubKey', '', 'akeeba'),
			'chunkMode'   => $this->container->platform->getSessionVar('transfer.chunkMode', 'chunked', 'akeeba'),
			'chunkSize'   => $this->container->platform->getSessionVar('transfer.chunkSize', '5242880', 'akeeba'),
		);
	}

	/**
	 * Removes files stored remotely
	 *
	 * @param   EngineTransfer\TransferInterface  $connector         The transfer object
	 * @param   array                       $files             The list of remote files to delete (relative paths)
	 * @param   bool|true                   $ignoreExceptions  Should I ignore exceptions thrown?
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 */
	private function removeRemoteFiles(EngineTransfer\TransferInterface $connector, array $files, $ignoreExceptions = true)
	{
		if (empty($files))
		{
			return;
		}

		foreach ($files as $file)
		{
			$remoteFile = $connector->getPath($file);

			try
			{
				$connector->delete($remoteFile);
			}
			catch (Exception $e)
			{
				// Only let the exception bubble up if we are told not to ignore exceptions
				if (!$ignoreExceptions)
				{
					throw $e;
				}
			}
		}
	}

	/**
	 * Check if the remote server environment matches our expectations.
	 *
	 * @param   bool    $forced     Are we forcing the transfer? If so some checks are ignored
	 *
	 * @throws  Exception
	 */
	private function checkRemoteServerEnvironment($forced)
	{
		$baseUrl = $this->container->platform->getSessionVar('transfer.url', '', 'akeeba');

		$baseUrl = rtrim($baseUrl, '/');

		$downloader = new Download($this->container);
		$rawData       = $downloader->getFromURL($baseUrl . '/kickstart.php?task=serverinfo');

		if ($rawData == false)
		{
			// Cannot access Kickstart on the remote server
			throw new RuntimeException(JText::_('COM_AKEEBA_TRANSFER_ERR_CANNOTRUNKICKSTART'));
		}

		// Try to get the raw JSON data
		$pos = strpos($rawData, '###');

		if ($pos === false)
		{
			// Invalid AJAX data, no leading ###
			throw new RuntimeException(JText::_('COM_AKEEBA_TRANSFER_ERR_CANNOTRUNKICKSTART'));
		}

		// Remove the leading ###
		$rawData = substr($rawData, $pos + 3);

		$pos = strpos($rawData, '###');

		if ($pos === false)
		{
			// Invalid AJAX data, no trailing ###
			throw new RuntimeException(JText::_('COM_AKEEBA_TRANSFER_ERR_CANNOTRUNKICKSTART'));
		}

		// Remove the trailing ###
		$rawData = substr($rawData, 0, $pos);

		// Get the JSON response
		$data = @json_decode($rawData, true);

		if (empty($data))
		{
			// Invalid AJAX data, can't decode this stuff
			throw new RuntimeException(JText::_('COM_AKEEBA_TRANSFER_ERR_CANNOTRUNKICKSTART'));
		}

		// Disk space check could be ignored since some hosts return the wrong value for the available disk space
		if (!$forced)
		{
			// Does the server have enough disk space?
			$freeSpace = $data['freeSpace'];

			$requiredSize = $this->getApproximateSpaceRequired();

			if ($requiredSize['size'] > $freeSpace)
			{
				$unit	 = array('b', 'KB', 'MB', 'GB', 'TB', 'PB');
				$freeSpaceString = @round($freeSpace / 1024 ** ($i = floor(log($freeSpace, 1024))), 2) . ' ' . $unit[$i];

				throw new TransferIgnorableError(JText::sprintf('COM_AKEEBA_TRANSFER_ERR_NOTENOUGHSPACE', $requiredSize['string'], $freeSpaceString));
			}
		}

		// Can I write to remote files?
		$canWrite = $data['canWrite'];
		$canWriteTemp = $data['canWriteTemp'];

		if (!$canWrite && !$canWriteTemp)
		{
			throw new RuntimeException(JText::_('COM_AKEEBA_TRANSFER_ERR_CANNOTWRITEREMOTEFILES'));
		}

		if ($canWrite)
		{
			$this->container->platform->setSessionVar('transfer.targetPath', '', 'akeeba');
		}
		else
		{
			$this->container->platform->setSessionVar('transfer.targetPath', 'kicktemp', 'akeeba');
		}

		$this->container->platform->setSessionVar('transfer.remoteTimeLimit', $data['maxExecTime'], 'akeeba');

		// What is my upload limit?
		$uploadLimit = min($data['maxPost'], $data['maxUpload']);

		if (empty($data['maxPost']))
		{
			$uploadLimit = $data['maxUpload'];
		}
		elseif (empty($data['maxUpload']))
		{
			$uploadLimit = $data['maxPost'];
		}

		if (empty($uploadLimit))
		{
			$uploadLimit = 1048576;
		}

		$this->container->platform->setSessionVar('transfer.uploadLimit', $uploadLimit, 'akeeba');
	}

	/**
	 * Get the filename for a backup part file, given the base file and the part number
	 *
	 * @param   string  $baseFile  Full path to the base file (.jpa, .jps, .zip)
	 * @param   int     $part      Part number
	 *
	 * @return  string
	 */
	private function getPartFilename($baseFile, $part = 0)
	{
		if ($part == 0)
		{
			return $baseFile;
		}

		$dirname = dirname($baseFile);
		$basename = basename($baseFile);

		$pos = strrpos($basename, '.');
		$extension = substr($basename, $pos + 1);

		$newExtension = substr($baseFile, 0, 1) . sprintf('%02u', $part);

		return $dirname . '/' . basename($basename, '.' . $extension) . '.'  .$newExtension;
	}

	/**
	 * Returns the PHP memory limit. If ini_get is not available it will assume 8Mb.
	 *
	 * @return  int
	 */
	private function getServerMemoryLimit()
	{
		// Default reported memory limit: 8Mb
		$memLimit = 8388608;

		// If we can't find out how much PHP memory we have available use 8Mb by default
		if (!function_exists('ini_get'))
		{
			return $memLimit;
		}

		$iniMemLimit = ini_get("memory_limit");
		$iniMemLimit = $this->convertMemoryLimitToBytes($iniMemLimit);

		$memLimit = ($iniMemLimit > 0) ? $iniMemLimit : $memLimit;

		return (int) $memLimit;
	}

	/**
	 * Gets the maximum chunk size the server can handle safely. It does so by finding the PHP memory limit, removing
	 * the current memory usage (or at least 2Mb) and rounding down to the closest 512Kb. It can never be lower than
	 * 512Kb.
	 */
	private function getMaxChunkSize()
	{
		$memoryLimit = $this->getServerMemoryLimit();
		$usedMemory = max(memory_get_usage(), memory_get_peak_usage(), 2048);

		$maxChunkSize = max(($memoryLimit - $usedMemory) / 2, 524288);

		return floor($maxChunkSize / 524288) * 524288;
	}

	/**
	 * Convert the textual representation of PHP memory limit to an integer, e.g. convert 8M to 8388608
	 *
	 * @param   string  $setting  The PHP memory limit
	 *
	 * @return  int  PHP memory limit as an integer
	 */
	private function convertMemoryLimitToBytes($setting)
	{
		$val  = trim($setting);
		$last = strtolower(substr($val, -1));

		if (is_numeric($last))
		{
			return $setting;
		}

		$val = substr($val, 0, -1);

		switch ($last)
		{
			case 't':
				$val *= 1024;
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}

		return (int) $val;
	}

	/**
	 * Uploads a chunk of a backup part file using a direct POST to Kickstart.
	 *
	 * This is the method supported by the Site Transfer Wizard since its inception. However, it may not work with hosts
	 * which have a sensitive server protection, e.g. the very tight mod_security2 rules on SiteGround servers. In those
	 * cases the remote server will respond with a 500 Internal Server Error, a 403 Forbidden or another server error.
	 *
	 * @param   string   $fileName     The filename to upload
	 * @param   string   $data         The data to upload
	 *
	 * @return  int      The length of the data we managed to upload
	 *
	 * @since   3.1.0
	 */
	private function uploadUsingPost($fileName, $data)
	{
		$frag      = $this->container->platform->getSessionVar('transfer.frag', -1, 'akeeba');
		$fragSize  = $this->container->platform->getSessionVar('transfer.fragSize', 5242880, 'akeeba');
		$url       = $this->container->platform->getSessionVar('transfer.url', '', 'akeeba');
		$directory = $this->container->platform->getSessionVar('transfer.targetPath', '', 'akeeba');

		$url = rtrim($url, '/') . '/kickstart.php';
		$uri = JUri::getInstance($url);
		$uri->setVar('task', 'uploadFile');
		$uri->setVar('file', basename($fileName));
		$uri->setVar('directory', $directory);
		$uri->setVar('frag', $frag);
		$uri->setVar('fragSize', $fragSize);

		$downloader = new Download($this->container);
		$downloader->setAdapterOptions([
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS    => [
				'data' => $data
			]
		]);
		$dataLength = strlen($data);
		unset($data);
		$rawData = $downloader->getFromURL($uri->toString());

		// Try to get the raw JSON data
		$pos = strpos($rawData, '###');

		if ($pos === false)
		{
			// Invalid AJAX data, no leading ###
			throw new RuntimeException(JText::sprintf('COM_AKEEBA_TRANSFER_ERR_CANNOTUPLOADARCHIVE', basename($fileName)));
		}

		// Remove the leading ###
		$rawData = substr($rawData, $pos + 3);

		$pos = strpos($rawData, '###');

		if ($pos === false)
		{
			// Invalid AJAX data, no trailing ###
			throw new RuntimeException(JText::sprintf('COM_AKEEBA_TRANSFER_ERR_CANNOTUPLOADARCHIVE', basename($fileName)));
		}

		// Remove the trailing ###
		$rawData = substr($rawData, 0, $pos);

		// Get the JSON response
		$data = @json_decode($rawData, true);

		if (empty($data))
		{
			// Invalid AJAX data, can't decode this stuff
			throw new RuntimeException(JText::sprintf('COM_AKEEBA_TRANSFER_ERR_CANNOTUPLOADARCHIVE', basename($fileName)));
		}

		if (!$data['status'])
		{
			throw new RuntimeException(JText::sprintf('COM_AKEEBA_TRANSFER_ERR_ERRORFROMREMOTE', $data['message']));
		}

		return $dataLength;
	}

	/**
	 * Uploads a chunk of a backup part file via FTP and then uses Kickstart to piece the file together.
	 *
	 * This is a new upload method which works better on servers with tighter security. The only downside is that we
	 * have to open many FTP/SFTP upload sessions which may result in the remote server eventually blocking our uploads.
	 *
	 * @param   string   $fileName     The filename to upload
	 * @param   string   $data         The data to upload
	 * @param   array    $config       The FTP/SFTP configuration
	 *
	 * @return  int      The length of the data we managed to upload
	 *
	 * @since   3.1.0
	 */
	private function uploadUsingChunked($fileName, $data, $config)
	{
		// ==== Initialize
		$frag      = $this->container->platform->getSessionVar('transfer.frag', -1, 'akeeba');
		$fragSize  = $this->container->platform->getSessionVar('transfer.fragSize', 5242880, 'akeeba');
		$url       = $this->container->platform->getSessionVar('transfer.url', '', 'akeeba');
		$directory = $this->container->platform->getSessionVar('transfer.targetPath', '', 'akeeba');

		// ==== Upload the data to the same folder as Kickstart, under a temporary name
		// Even though the connector has the write() method, it's not very good for over 1M files. So we create a temp file instead.
		$engineConfig = Factory::getConfiguration();
		$localTempFile = tempnam($this->container->platform->getConfig()->get('tmp_path', sys_get_temp_dir()), 'stw');
		$localTempFile = ($localTempFile === false) ? tempnam(sys_get_temp_dir(), 'stw') : $localTempFile;
		$localTempFile = ($localTempFile === false) ? tempnam($engineConfig->get('akeeba.basic.output_directory', '[DEFAULT_OUTPUT]'), 'stw') : $localTempFile;

		if ($localTempFile === false)
		{
			throw new \RuntimeException(JText::_('COM_AKEEBA_TRANSFER_ERR_CANTCREATETEMPCHUNK'));
		}

		if (!file_put_contents($localTempFile, $data))
		{
			if (!JFile::write($localTempFile, $data))
			{
				throw new \RuntimeException(JText::_('COM_AKEEBA_TRANSFER_ERR_CANTCREATETEMPCHUNK'));
			}
		}

		$random    = new RandomValue();
		$tempFile  = strtolower($random->generateString(8)) . '.dat';
		$connector = $this->getConnector($config);

		try
		{
			$remoteDirectory = $config['directory'] . (empty($directory) ? '' : ('/' . $directory));
			$remoteFile      = $remoteDirectory . '/' . $tempFile;

			$connector->upload($localTempFile, $remoteFile, true);
		}
		catch (\RuntimeException $e)
		{
			JFile::delete($localTempFile);

			throw $e;
		}

		// ==== Call Kickstart to piece together the file
		$url = rtrim($url, '/') . '/kickstart.php';
		$uri = JUri::getInstance($url);
		$uri->setVar('task', 'uploadFile');
		$uri->setVar('file', basename($fileName));
		$uri->setVar('directory', $directory);
		$uri->setVar('frag', $frag);
		$uri->setVar('fragSize', $fragSize);
		$uri->setVar('dataFile', $tempFile);

		$downloader = new Download($this->container);
		$dataLength = strlen($data);
		unset($data);
		$rawData = $downloader->getFromURL($uri->toString());

		// ==== Delete the temporary files
		if (!@unlink($localTempFile))
		{
			JFile::delete($localTempFile);
		}
		$connector->delete($remoteFile);

		// ==== Parse Kickstart's response

		// Try to get the raw JSON data
		$pos = strpos($rawData, '###');

		if ($pos === false)
		{
			// Invalid AJAX data, no leading ###
			throw new \RuntimeException(JText::sprintf('COM_AKEEBA_TRANSFER_ERR_CANNOTUPLOADARCHIVE', basename($fileName)));
		}

		// Remove the leading ###
		$rawData = substr($rawData, $pos + 3);

		$pos = strpos($rawData, '###');

		if ($pos === false)
		{
			// Invalid AJAX data, no trailing ###
			throw new \RuntimeException(JText::sprintf('COM_AKEEBA_TRANSFER_ERR_CANNOTUPLOADARCHIVE', basename($fileName)));
		}

		// Remove the trailing ###
		$rawData = substr($rawData, 0, $pos);

		// Get the JSON response
		$data = @json_decode($rawData, true);

		if (empty($data))
		{
			// Invalid AJAX data, can't decode this stuff
			throw new \RuntimeException(JText::sprintf('COM_AKEEBA_TRANSFER_ERR_CANNOTUPLOADARCHIVE', basename($fileName)));
		}

		if (!$data['status'])
		{
			throw new \RuntimeException(JText::sprintf('COM_AKEEBA_TRANSFER_ERR_ERRORFROMREMOTE', $data['message']));
		}

		return $dataLength;
	}
}
