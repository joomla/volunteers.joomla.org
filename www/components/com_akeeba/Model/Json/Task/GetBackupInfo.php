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
 * Get information for a given backup record
 */
class GetBackupInfo extends AbstractTask
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
		);

		$defConfig = array_merge($defConfig, $parameters);

		$backup_id = (int)$defConfig['backup_id'];

		// Get the basic statistics
		$record = Platform::getInstance()->get_statistics($backup_id);

		// Get a list of filenames
		$backup_stats = Platform::getInstance()->get_statistics($backup_id);

		// Backup record doesn't exist
		if (empty($backup_stats))
		{
			throw new \RuntimeException('Invalid backup record identifier', 404);
		}

		$filenames = Factory::getStatistics()->get_all_filenames($record);

		if (empty($filenames))
		{
			// Archives are not stored on the server or no files produced
			$record['filenames'] = array();
		}
		else
		{
			$filedata = array();
			$i        = 0;

			// Get file sizes per part
			foreach ($filenames as $file)
			{
				$i++;
				$size       = @filesize($file);
				$size       = is_numeric($size) ? $size : 0;
				$filedata[] = array(
					'part' => $i,
					'name' => basename($file),
					'size' => $size
				);
			}

			// Add the file info to $record['filenames']
			$record['filenames'] = $filedata;
		}

		return $record;
	}
}
