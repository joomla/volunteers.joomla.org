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
use Akeeba\Engine\Platform;
use Akeeba\Engine\Postproc\Connector\S3v4\Configuration;
use Akeeba\Engine\Postproc\Connector\S3v4\Connector as Amazons3;
use FOF30\Model\Model;
use JHtml;
use JText;
use RuntimeException;

class S3Import extends Model
{
	/**
	 * Maximum time to spend downloading files per request, in seconds
	 *
	 * @var  int
	 */
	protected $maxTimeAllowance = 10;

	/**
	 * Populate the S3 connection credentials
	 *
	 * @return  void
	 */
	public function getS3Credentials()
	{
		$config         = Factory::getConfiguration();
		$defS3AccessKey = $config->get('engine.postproc.s3.accesskey', '');
		$defS3SecretKey = $config->get('engine.postproc.s3.privatekey', '');

		$platform = $this->container->platform;
		$input    = $this->input;

		$accessKey = $platform->getUserStateFromRequest('com_akeeba.s3access', 's3access', $input, $defS3AccessKey, 'raw');
		$secretKey = $platform->getUserStateFromRequest('com_akeeba.s3secret', 's3secret', $input, $defS3SecretKey, 'raw');
		$bucket    = $platform->getUserStateFromRequest('com_akeeba.bucket', 's3bucket', $input, '', 'raw');
		$folder    = $platform->getUserStateFromRequest('com_akeeba.folder', 'folder', $input, '', 'raw');
		$file      = $platform->getUserStateFromRequest('com_akeeba.file', 'file', $input, '', 'raw');
		$part      = $platform->getUserStateFromRequest('com_akeeba.s3import.part', 'part', $input, -1, 'int');
		$frag      = $platform->getUserStateFromRequest('com_akeeba.s3import.frag', 'frag', $input, -1, 'int');

		$this->setState('s3access', $accessKey);
		$this->setState('s3secret', $secretKey);
		$this->setState('s3bucket', $bucket);
		$this->setState('folder', $folder);
		$this->setState('file', $file);
		$this->setState('part', $part);
		$this->setState('frag', $frag);

		$region = $this->getBucketRegion($bucket);
	}

	/**
	 * Set the S3 connection credentials
	 *
	 * @param   string $accessKey Access key
	 * @param   string $secretKey Private key
	 *
	 * @return  void
	 */
	public function setS3Credentials($accessKey, $secretKey)
	{
		$this->setState('s3access', $accessKey);
		$this->setState('s3secret', $secretKey);
	}

	/**
	 * Gets an S3 connector object
	 *
	 * @return  Amazons3
	 */
	private function &getS3Connector()
	{
		static $s3 = null;

		if (!is_object($s3))
		{
			$config = $this->getS3Configuration();
			$s3     = new Amazons3($config);
		}

		return $s3;
	}

	private function &getS3Configuration()
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
	 * @param   boolean  $checkBucket  Should I also check that a bucket name is set?
	 *
	 * @return  boolean
	 */
	private function _hasAdequateInformation($checkBucket = true)
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

	/**
	 * Get a list of Amazon S3 buckets
	 *
	 * @return  array|false|null
	 */
	public function getBuckets()
	{
		$buckets = null;

		if (!is_array($buckets))
		{
			$buckets = array();

			if ($this->_hasAdequateInformation(false))
			{
				$config = $this->getS3Configuration();
				$config->setRegion('us-east-1');

				$s3 = $this->getS3Connector();

				try
				{
					$buckets = $s3->listBuckets(false);
				}
				catch (\Exception $e)
				{
					// Swallow the exception
				}
			}
		}

		return $buckets;
	}

	public function getContents()
	{
		$folders = null;
		$files   = null;
		$root    = $this->getState('folder', '/');

		if (!is_array($folders) || !is_array($files))
		{
			$folders = array();

			if ($this->_hasAdequateInformation())
			{
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
						if (substr($name, - 8) == '$folder$')
						{
							continue;
						}

						if (array_key_exists('name', $record))
						{
							$extension = substr($name, - 4);

							if (!in_array($extension, array('.zip', '.jpa')))
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
				catch (\Exception $e)
				{
					$files   = [];
					$folders = [];
				}
			}
		}

		return array(
			'files'   => $files,
			'folders' => $folders,
		);
	}

	public function getBucketsDropdown()
	{
		$options   = array();
		$buckets   = $this->getBuckets();
		$options[] = JHtml::_('select.option', '', JText::_('COM_AKEEBA_S3IMPORT_LABEL_SELECTBUCKET'));

		if (!empty($buckets))
		{
			foreach ($buckets as $b)
			{
				$options[] = JHtml::_('select.option', $b, $b);
			}
		}

		$selected = $this->getState('s3bucket', '');

		return JHtml::_('select.genericlist', $options, 's3bucket', array(), 'value', 'text', $selected);
	}

	/**
	 * Get the breadcrumbs you'll be using in the S3 import view
	 *
	 * @return  array
	 */
	public function getCrumbs()
	{
		$folder = $this->container->platform->getUserStateFromRequest('com_akeeba.folder', 'folder', $this->input, '', 'raw');
		$crumbs = array();

		if (!empty($folder))
		{
			$folder = rtrim($folder, '/');
			$crumbs = explode('/', $folder);
		}

		return $crumbs;
	}

	public function downloadToServer()
	{
		if (!$this->_hasAdequateInformation())
		{
			throw new RuntimeException(JText::_('COM_AKEEBA_S3IMPORT_ERR_NOTENOUGHINFO'));
		}

		// Gather the necessary information to perform the download
		$part           = $this->container->platform->getSessionVar('s3import.part', -1, 'com_akeeba');
		$frag           = $this->container->platform->getSessionVar('s3import.frag', -1, 'com_akeeba');
		$remoteFilename = $this->getState('file', '');

		$bucket = $this->getState('s3bucket');
		$region = $this->getBucketRegion($bucket);
		$config = $this->getS3Configuration();
		$config->setRegion($region);

		$s3 = $this->getS3Connector();

		// Get the number of parts and total size from the session, or –if not there– fetch it
		$totalparts = $this->container->platform->getSessionVar('s3import.totalparts', -1, 'com_akeeba');
		$totalsize  = $this->container->platform->getSessionVar('s3import.totalsize', -1, 'com_akeeba');

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

			$this->container->platform->setSessionVar('s3import.totalparts', count($allFiles), 'com_akeeba');
			$this->container->platform->setSessionVar('s3import.totalsize', $totalsize, 'com_akeeba');
			$this->container->platform->setSessionVar('s3import.donesize', 0, 'com_akeeba');

			$totalparts = $this->container->platform->getSessionVar('s3import.totalparts', -1, 'com_akeeba');
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
				$this->container->platform->setSessionVar('s3import.donesize', 0, 'com_akeeba');

				// Init
				$part = 0;
			}

			// Do we have to initialize the file?
			if ($frag == -1)
			{
				// Delete and touch the output file
				Platform::getInstance()->unlink($local_file);
				$fp = @fopen($local_file, 'wb');

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
			catch (\Exception $e)
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
					throw new RuntimeException(JText::_('COM_AKEEBA_S3IMPORT_ERR_NOTFOUND'));
				}
				elseif ($part >= $totalparts)
				{
					// Just finished! Create a stats record.
					$multipart = $totalparts;
					$multipart--;

					$filetime = time();
					// Create a new backup record
					$record = array(
						'description'     => JText::_('COM_AKEEBA_DISCOVER_LABEL_IMPORTEDDESCRIPTION'),
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
						'total_size'      => $totalsize
					);

					$id    = null;
					Platform::getInstance()->set_or_update_statistics($id, $record);

					return null;
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
				$filesize = (int)@filesize($temp_file);
				$total    = $this->container->platform->getSessionVar('s3import.donesize', 0, 'com_akeeba');
				$total += $filesize;
				$this->container->platform->setSessionVar('s3import.donesize', $total, 'com_akeeba');
			}

			// Successful download, or have to move to the next part.
			if ($result)
			{
				// Append the file
				$fp = @fopen($local_file, 'ab');

				if ($fp === false)
				{
					// Can't open the file for writing
					@unlink($temp_file);

					throw new RuntimeException(JText::_('COM_AKEEBA_S3IMPORT_ERR_CANTWRITE'));
				}

				$tf = fopen($temp_file, 'rb');

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
		$this->container->platform->setSessionVar('s3import.part', $part, 'com_akeeba');
		$this->container->platform->setSessionVar('s3import.frag', $frag, 'com_akeeba');

		if ($part >= $totalparts)
		{
			// Just finished! Create a new backup record
			$record = array(
				'description'     => JText::_('COM_AKEEBA_DISCOVER_LABEL_IMPORTEDDESCRIPTION'),
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
				'total_size'      => $totalsize
			);

			$id    = null;
			Platform::getInstance()->set_or_update_statistics($id, $record);

			return null;
		}

		return true;
	}

	/**
	 * Returns the region for the bucket
	 *
	 * @param   string  $bucket
	 *
	 * @return  string
	 */
	protected function getBucketRegion($bucket)
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
}
