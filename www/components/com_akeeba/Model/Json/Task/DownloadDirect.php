<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Site\Model\Json\Task;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;

/**
 * Download an entire backup archive directly over HTTP
 */
class DownloadDirect extends AbstractTask
{
	/**
	 * Execute the JSON API task
	 *
	 * @param   array $parameters The parameters to this task
	 *
	 * @return  mixed
	 *
	 * @throws  \RuntimeException  In case of an error
	 */
	public function execute(array $parameters = array())
	{
		// Get the passed configuration values
		$defConfig = array(
			'backup_id' => 0,
			'part_id'   => 1,
		);

		$defConfig = array_merge($defConfig, $parameters);

		$backup_id = (int)$defConfig['backup_id'];
		$part_id   = (int)$defConfig['part_id'];

		$backup_stats = Platform::getInstance()->get_statistics($backup_id);

		if (empty($backup_stats))
		{
			// Backup record doesn't exist
			@ob_end_clean();
			header('HTTP/1.1 500 Invalid backup record identifier');
			flush();

			$this->container->platform->closeApplication();
		}

		$files = Factory::getStatistics()->get_all_filenames($backup_stats);

		if ((count($files) < $part_id) || ($part_id <= 0))
		{
			// Invalid part
			@ob_end_clean();
			header('HTTP/1.1 500 Invalid backup part');
			flush();

			$this->container->platform->closeApplication();
		}

		$filename = $files[ $part_id - 1 ];
		@clearstatcache();

		// For a certain unmentionable browser
		if (function_exists('ini_get') && function_exists('ini_set'))
		{
			if (ini_get('zlib.output_compression'))
			{
				ini_set('zlib.output_compression', 'Off');
			}
		}

		// Remove php's time limit
		if (function_exists('ini_get') && function_exists('set_time_limit'))
		{
			if (!ini_get('safe_mode'))
			{
				@set_time_limit(0);
			}
		}

		$basename  = @basename($filename);
		$fileSize  = @filesize($filename);
		$extension = strtolower(str_replace(".", "", strrchr($filename, ".")));

		while (@ob_end_clean())
		{
			;
		}
		@clearstatcache();
		// Send MIME headers
		header('MIME-Version: 1.0');
		header('Content-Disposition: attachment; filename="' . $basename . '"');
		header('Content-Transfer-Encoding: binary');
		header('Accept-Ranges: bytes');

		switch ($extension)
		{
			case 'zip':
				// ZIP MIME type
				header('Content-Type: application/zip');
				break;

			default:
				// Generic binary data MIME type
				header('Content-Type: application/octet-stream');
				break;
		}

		// Notify of file size, if this info is available
		if ($fileSize > 0)
		{
			header('Content-Length: ' . @filesize($filename));
		}

		// Disable caching
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Expires: 0");
		header('Pragma: no-cache');
		flush();

		if ($fileSize > 0)
		{
			// If the filesize is reported, use 1M chunks for echoing the data to the browser
			$blockSize = 1048576; //1M chunks
			$handle    = @fopen($filename, "r");

			// Now we need to loop through the file and echo out chunks of file data
			if ($handle !== false)
			{
				while (!@feof($handle))
				{
					echo @fread($handle, $blockSize);
					@ob_flush();
					flush();
				}
			}

			if ($handle !== false)
			{
				@fclose($handle);
			}
		}
		else
		{
			// If the filesize is not reported, hope that readfile works
			@readfile($filename);
		}

		flush();

		$this->container->platform->closeApplication();

	}
}
