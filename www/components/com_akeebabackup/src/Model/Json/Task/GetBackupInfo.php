<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Site\Model\Json\Task;

// Protect from unauthorized access
defined('_JEXEC') || die();

use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use RuntimeException;

/**
 * Get information for a given backup record
 */
class GetBackupInfo extends AbstractTask
{
	/**
	 * The backend view.task key this API method is equivalent to.
	 *
	 * @var    string
	 * @since  10.4.0
	 */
	protected $aclKey = 'manage';

	/**
	 * Execute the JSON API task
	 *
	 * @param   array $parameters The parameters to this task
	 *
	 * @return  mixed
	 *
	 * @throws  RuntimeException  In case of an error
	 */
	public function execute(array $parameters = [])
	{
		// Get the passed configuration values
		$defConfig = ['backup_id' => 0];

		$defConfig = array_merge($defConfig, $parameters);

		$backup_id = (int)$defConfig['backup_id'];

		// Get the basic statistics
		$record = Platform::getInstance()->get_statistics($backup_id);

		// Backup record doesn't exist
		if (empty($record))
		{
			throw new RuntimeException('Invalid backup record identifier', 404);
		}

		// Get a list of filenames
		$filenames = Factory::getStatistics()->get_all_filenames($record);

		if (empty($filenames))
		{
			// Archives are not stored on the server or no files produced
			$record['filenames'] = [];
		}
		else
		{
			$filedata = [];
			$i        = 0;

			// Get file sizes per part
			foreach ($filenames as $file)
			{
				$i++;
				$size       = @filesize($file);
				$size       = is_numeric($size) ? $size : 0;
				$filedata[] = ['part' => $i, 'name' => basename($file), 'size' => $size];
			}

			// Add the file info to $record['filenames']
			$record['filenames'] = $filedata;
		}

		return $record;
	}
}
