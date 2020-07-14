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
 * Download a chunk of a backup archive over HTTP
 */
class Download extends AbstractTask
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
			'backup_id'  => 0,
			'part_id'    => 1,
			'segment'    => 1,
			'chunk_size' => 1
		);

		$defConfig = array_merge($defConfig, $parameters);

		$backup_id  = (int)$defConfig['backup_id'];
		$part_id    = (int)$defConfig['part_id'];
		$segment    = (int)$defConfig['segment'];
		$chunk_size = (int)$defConfig['chunk_size'];

		$backup_stats = Platform::getInstance()->get_statistics($backup_id);

		if (empty($backup_stats))
		{
			// Backup record doesn't exist
			throw new \RuntimeException('Invalid backup record identifier', 404);
		}

		$files = Factory::getStatistics()->get_all_filenames($backup_stats);

		if ((count($files) < $part_id) || ($part_id <= 0))
		{
			// Invalid part
			throw new \RuntimeException('Invalid backup part', 404);
		}

		$file = $files[ $part_id - 1 ];

		$filesize = @filesize($file);
		$seekPos  = $chunk_size * 1048576 * ($segment - 1);

		if ($seekPos > $filesize)
		{
			// Trying to seek past end of file
			throw new \RuntimeException('Invalid segment', 404);
		}

		$fp = fopen($file, 'rb');

		if ($fp === false)
		{
			// Could not read file
			throw new \RuntimeException('Error reading backup archive', 500);
		}

		rewind($fp);
		if (fseek($fp, $seekPos, SEEK_SET) === -1)
		{
			// Could not seek to position
			throw new \RuntimeException('Error reading specified segment', 500);
		}

		$buffer = fread($fp, 1048576);

		if ($buffer === false)
		{
			throw new \RuntimeException('Error reading specified segment', 500);
		}

		fclose($fp);

		return base64_encode($buffer);

	}
}
