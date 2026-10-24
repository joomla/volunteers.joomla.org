<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Model;

defined('_JEXEC') || die;

use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Akeeba\Engine\Postproc\Connector\S3v4\Configuration;
use Akeeba\Engine\Postproc\Connector\S3v4\Connector as Amazons3;
use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseModel;
use RuntimeException;

#[\AllowDynamicProperties]
class S3importModel extends BaseModel
{
	/**
	 * Cached list of S3 buckets
	 *
	 * @var   array|null
	 * @since 9.0.0
	 */
	protected static $buckets;

	protected static $folders;

	protected static $files;

	/**
	 * Maximum time to spend downloading files per request, in seconds
	 *
	 * @var  int
	 */
	protected $maxTimeAllowance = 10;

	/**
	 * Set the S3 connection credentials
	 *
	 * @param   string  $accessKey  Access key
	 * @param   string  $secretKey  Private key
	 *
	 * @return  void
	 */
	public function setS3Credentials(string $accessKey, string $secretKey)
	{
		$this->setState('s3access', $accessKey);
		$this->setState('s3secret', $secretKey);
	}

	/**
	 * Get a list of Amazon S3 buckets.
	 *
	 * @return  array
	 */
	public function getBuckets(): array
	{
		// Return cached data, if it exists
		if (is_array(self::$buckets))
		{
			return self::$buckets;
		}

		// Initialise
		self::$buckets = [];

		// Make sure we have enough information to collect the list of bucket
		if (!$this->hasAdequateInformation(false))
		{
			return self::$buckets;
		}

		// Try to retrieve the buckets
		try
		{
			$config = $this->getS3Configuration();

			$config->setRegion('us-east-1');

			$s3 = $this->getS3Connector();

			self::$buckets = $s3->listBuckets(false);
		}
		catch (Exception $e)
		{
			// Swallow the exception
		}

		// Thsi should never be triggered since listBuckets() always returns an array.
		if (!is_array(self::$buckets))
		{
			self::$buckets = [];
		}

		return self::$buckets;
	}

	/**
	 * Returns the folders and archive files in an S3 bucket
	 *
	 * @return array[]
	 */
	public function getContents(): array
	{
		if (is_array(self::$folders) && is_array(self::$files))
		{
			return [
				'files'   => self::$files,
				'folders' => self::$folders,
			];
		}

		self::$files   = [];
		self::$folders = [];

		if (!$this->hasAdequateInformation())
		{
			return [
				'files'   => self::$files,
				'folders' => self::$folders,
			];
		}

		$root   = $this->getState('folder', '/');
		$bucket = $this->getState('s3bucket');
		$region = $this->getBucketRegion($bucket);
		$config = $this->getS3Configuration();

		$config->setRegion($region);

		$s3 = $this->getS3Connector();

		try
		{
			$raw = $s3->getBucket($bucket, $root, null, null, '/', true);

			foreach ($raw as $name => $record)
			{
				if (substr($name, -8) == '$folder$')
				{
					continue;
				}

				if (array_key_exists('name', $record))
				{
					$extension = substr($name, -4);

					if (!in_array($extension, ['.zip', '.jpa', '.jps']))
					{
						continue;
					}

					$files[$name] = $record;
				}
				elseif (array_key_exists('prefix', $record))
				{
					$folders[$name] = $record;
				}
			}
		}
		catch (Exception $e)
		{
			// Swallow the exception
		}

		return [
			'files'   => $files,
			'folders' => $folders,
		];
	}

	/**
	 * Get the breadcrumbs you'll be using in the S3 import view
	 *
	 * @return  array
	 */
	public function getCrumbs(): array
	{
		$folder = $this->getState('folder', '');
		$crumbs = [];

		if (!empty($folder))
		{
			$folder = rtrim($folder, '/');
			$crumbs = explode('/', $folder);
		}

		return $crumbs;
	}

	/**
	 * Downloads a backup archive set to the server
	 *
	 * @return  bool  Have I finished the import?
	 * @throws  Exception
	 */
	public function downloadToServer(): bool
	{
		if (!$this->hasAdequateInformation())
		{
			throw new RuntimeException(Text::_('COM_AKEEBABACKUP_S3IMPORT_ERR_NOTENOUGHINFO'));
		}

		/** @var CMSApplication $app */
		$app     = \Joomla\CMS\Factory::getApplication();
		$session = $app->getSession();

		// Gather the necessary information to perform the download
		$part           = $session->get('com_akeebabackup.s3import.part', -1);
		$frag           = $session->get('com_akeebabackup.s3import.frag', -1);
		$remoteFilename = $this->getState('file', '');

		$bucket = $this->getState('s3bucket');
		$region = $this->getBucketRegion($bucket);
		$config = $this->getS3Configuration();

		$config->setRegion($region);

		$s3 = $this->getS3Connector();

		// Get the number of parts and total size from the session, or –if not there– fetch it
		$totalparts = $session->get('com_akeebabackup.s3import.totalparts', -1);
		$totalsize  = $session->get('com_akeebabackup.s3import.totalsize', -1);

		if (($totalparts < 0) || (($part < 0) && ($frag < 0)))
		{
			$filePrefix = substr($remoteFilename, 0, -3);
			$allFiles   = $s3->getBucket($bucket, $filePrefix);
			$totalsize  = 0;

			if (count($allFiles))
			{
				foreach ($allFiles as $name => $file)
				{
					$totalsize += $file['size'];
				}
			}

			$session->set('com_akeebabackup.s3import.totalparts', count($allFiles));
			$session->set('com_akeebabackup.s3import.totalsize', $totalsize);
			$session->set('com_akeebabackup.s3import.donesize', 0);

			$totalparts = $session->get('com_akeebabackup.s3import.totalparts', -1);
		}

		// Start timing ourselves
		$timer      = Factory::getTimer(); // The core timer object
		$start      = $timer->getRunningTime(); // Mark the start of this download
		$break      = false; // Don't break the step
		$local_file = null;

		while (($timer->getRunningTime() < $this->maxTimeAllowance) && !$break && ($part < $totalparts))
		{
			// Get the remote and local filenames
			$basename      = basename($remoteFilename);
			$extension     = strtolower(str_replace(".", "", strrchr($basename, ".")));
			$new_extension = $extension;

			if ($part > 0)
			{
				$new_extension = substr($extension, 0, 1) . sprintf('%02u', $part);
			}

			$remote_filename = substr($remoteFilename, 0, -strlen($extension)) . $new_extension;

			// Figure out where on Earth to put that file
			$local_file = Factory::getConfiguration()
					->get('akeeba.basic.output_directory') . '/' . basename($remote_filename);

			// Do we have to initialize the process?
			if ($part == -1)
			{
				// Currently downloaded size
				$session->set('com_akeebabackup.s3import.donesize', 0);

				// Init
				$part = 0;
			}

			// Do we have to initialize the file?
			if ($frag == -1)
			{
				// Delete and touch the output file
				Platform::getInstance()->unlink($local_file);
				$fp = @fopen($local_file, 'w');

				if ($fp !== false)
				{
					@fclose($fp);
				}

				// Init
				$frag = 0;
			}

			// Calculate from and length
			$length = 1048576;

			$from = $frag * $length;
			$to   = ($frag + 1) * $length - 1;

			// Try to download the first frag
			$temp_file = $local_file . '.tmp';
			@unlink($temp_file);

			$required_time = 1.0;

			try
			{
				$s3->getObject($this->getState('s3bucket', ''), $remote_filename, $temp_file, $from, $to);
				$result = true;
			}
			catch (Exception $e)
			{
				$result = false;
			}

			if (!$result)
			{
				// Failed download
				@unlink($temp_file);

				if (
				(
					(($part < $totalparts) || (($totalparts == 1) && ($part == 0))) &&
					($frag == 0)
				)
				)
				{
					// Failure to download the part's beginning = failure to download. Period.
					throw new RuntimeException(Text::_('COM_AKEEBABACKUP_S3IMPORT_ERR_NOTFOUND'));
				}
				elseif ($part >= $totalparts)
				{
					// Just finished! Create a stats record.
					$multipart = $totalparts;
					$multipart--;

					$filetime = time();
					// Create a new backup record
					$record = [
						'description'     => Text::_('COM_AKEEBABACKUP_DISCOVER_LABEL_IMPORTEDDESCRIPTION'),
						'comment'         => '',
						'backupstart'     => date('Y-m-d H:i:s', $filetime),
						'backupend'       => date('Y-m-d H:i:s', $filetime + 1),
						'status'          => 'complete',
						'origin'          => 'backend',
						'type'            => 'full',
						'profile_id'      => 1,
						'archivename'     => basename($remoteFilename),
						'absolute_path'   => dirname($local_file) . '/' . basename($remoteFilename),
						'multipart'       => $multipart,
						'tag'             => 'backend',
						'filesexist'      => 1,
						'remote_filename' => '',
						'total_size'      => $totalsize,
					];

					$id = null;
					Platform::getInstance()->set_or_update_statistics($id, $record);

					return true;
				}
				else
				{
					// Since this is a staggered download, consider this normal and go to the next part.
					$part++;
					$frag = -1;
				}
			}

			// Add the currently downloaded frag to the total size of downloaded files
			if ($result)
			{
				clearstatcache();
				$filesize = (int) @filesize($temp_file);
				$total    = $session->get('com_akeebabackup.s3import.donesize', 0);
				$total    += $filesize;
				$session->set('com_akeebabackup.s3import.donesize', $total);
			}

			// Successful download, or have to move to the next part.
			if ($result)
			{
				// Append the file
				$fp = @fopen($local_file, 'a');

				if ($fp === false)
				{
					// Can't open the file for writing
					@unlink($temp_file);

					throw new RuntimeException(Text::_('COM_AKEEBABACKUP_S3IMPORT_ERR_CANTWRITE'));
				}

				@clearstatcache();
				$tf = fopen($temp_file, 'r');

				if ($tf === false)
				{
					@unlink($temp_file);
					@fclose($fp);

					throw new RuntimeException(Text::_('COM_AKEEBABACKUP_S3IMPORT_ERR_CANTOPEN'));
				}

				while (!feof($tf))
				{
					$data = fread($tf, 262144);
					fwrite($fp, $data);
				}

				fclose($tf);
				fclose($fp);
				@unlink($temp_file);

				$frag++;
			}

			// Advance the frag pointer and mark the end
			$end = $timer->getRunningTime();

			// Do we predict that we have enough time?
			$required_time = max(1.1 * ($end - $start), $required_time);

			if ($required_time > ($this->maxTimeAllowance - $end + $start))
			{
				$break = true;
			}

			$start = $end;
		}

		// Pass the id, part, frag in the request so that the view can grab it
		$this->setState('part', $part);
		$this->setState('frag', $frag);
		$session->set('com_akeebabackup.s3import.part', $part);
		$session->set('com_akeebabackup.s3import.frag', $frag);

		if ($part >= $totalparts)
		{
			// Just finished! Create a new backup record
			$record = [
				'description'     => Text::_('COM_AKEEBABACKUP_DISCOVER_LABEL_IMPORTEDDESCRIPTION'),
				'comment'         => '',
				'backupstart'     => date('Y-m-d H:i:s'),
				'backupend'       => date('Y-m-d H:i:s', time() + 1),
				'status'          => 'complete',
				'origin'          => 'backend',
				'type'            => 'full',
				'profile_id'      => 1,
				'archivename'     => basename($remoteFilename),
				'absolute_path'   => dirname($local_file) . '/' . basename($remoteFilename),
				'multipart'       => $totalparts,
				'tag'             => 'backend',
				'filesexist'      => 1,
				'remote_filename' => '',
				'total_size'      => $totalsize,
			];

			$id = null;
			Platform::getInstance()->set_or_update_statistics($id, $record);

			return true;
		}

		return false;
	}

	/**
	 * Returns the region for the bucket
	 *
	 * @param   string  $bucket
	 *
	 * @return  string|null
	 */
	public function getBucketRegion(string $bucket): ?string
	{
		$bucketForRegion = $this->getState('bucketForRegion', null);
		$region          = $this->getState('region', null);

		if (!empty($bucket) && (($bucketForRegion != $bucket) || empty($region)))
		{
			$config = $this->getS3Configuration();
			$config->setRegion('us-east-1');

			$s3     = $this->getS3Connector();
			$region = $s3->getBucketLocation($bucket);
			$this->setState('bucketForRegion', $bucket);
			$this->setState('region', $region);
		}

		return $region;
	}

	/**
	 * Gets an S3 connector object
	 *
	 * @return  Amazons3
	 */
	private function getS3Connector(): Amazons3
	{
		static $s3 = null;

		if (!is_object($s3))
		{
			$config = $this->getS3Configuration();
			$s3     = new Amazons3($config);
		}

		return $s3;
	}

	private function getS3Configuration(): Configuration
	{
		static $s3Config = null;

		if (!is_object($s3Config))
		{
			$s3Access = $this->getState('s3access');
			$s3Secret = $this->getState('s3secret');
			$s3Config = new Configuration($s3Access, $s3Secret, 'v4', 'us-east-1');
		}

		return $s3Config;
	}

	/**
	 * Do I have enough information to connect to S3?
	 *
	 * @param   bool  $checkBucket  Should I also check that a bucket name is set?
	 *
	 * @return  bool
	 */
	private function hasAdequateInformation($checkBucket = true): bool
	{
		$s3access = $this->getState('s3access');
		$s3secret = $this->getState('s3secret');
		$s3bucket = $this->getState('s3bucket');

		$check = !empty($s3access) && !empty($s3secret);

		if ($checkBucket)
		{
			$check = $check && !empty($s3bucket);
		}

		return $check;
	}
}