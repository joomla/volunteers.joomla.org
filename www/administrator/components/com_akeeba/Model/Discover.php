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
use FOF30\Model\Model;
use JText;

/**
 * Model for the discover and import page
 */
class Discover extends Model
{
	/**
	 * Returns a list of the archive files in a directory which do not already belong to a backup record
	 *
	 * @return  array
	 */
	public function getFiles()
	{
		$ret = array();

		$directory = $this->getState('directory', '');
		$directory = Factory::getFilesystemTools()->translateStockDirs($directory);

		// Get all archive files
		$allFiles = Factory::getFileLister()->getFiles($directory, true);
		$files    = array();

		if (!empty($allFiles))
		{
			foreach ($allFiles as $file)
			{
				$ext = strtoupper(substr($file, -3));

				if (in_array($ext, array('JPA', 'JPS', 'ZIP')))
				{
					$files[] = $file;
				}
			}
		}

		// If nothing found, bail out
		if (empty($files))
		{
			return $ret;
		}

		// Make sure these files do not already exist in another backup record
		$db  = $this->container->db;

		$sql = $db->getQuery(true)
				  ->select($db->qn('absolute_path'))
				  ->from($db->qn('#__ak_stats'))
				  ->where($db->qn('absolute_path') . ' LIKE ' . $db->q($directory . '%'))
				  ->where($db->qn('filesexist') . ' = ' . $db->q('1'));

		try
		{
			$existingFiles = $db->setQuery($sql)->loadColumn();
		}
		catch (\Exception $e)
		{
			$existingFiles = [];
		}

		foreach ($files as $file)
		{
			if (!in_array($file, $existingFiles))
			{
				$ret[] = $file;
			}
		}

		// Finally sort the resulting array for easier reading
		sort($ret);

		return $ret;
	}

	/**
	 * Imports an archive file as a new backup record
	 *
	 * @param   string  $file  The full path to the archive to import
	 *
	 * @return  int  The new backup record ID
	 */
	public function import($file)
	{
		$directory = $this->getState('directory', '');
		$directory = Factory::getFilesystemTools()->translateStockDirs($directory);

		// Find out how many parts there are
		$multipart = 0;
		$base      = substr($file, 0, -4);
		$ext       = substr($file, -3);
		$found     = true;

		$total_size = @filesize($directory . '/' . $file);

		while ($found)
		{
			$multipart++;
			$newExtension = substr($ext, 0, 1) . sprintf('%02u', $multipart);
			$newFile      = $directory . '/' . $base . '.' . $newExtension;
			$found        = file_exists($newFile);

			if ($found)
			{
				$total_size += @filesize($newFile);
			}
		}

		$fileModificationTime = @filemtime($directory . '/' . $file);

		if (empty($fileModificationTime))
		{
			$fileModificationTime = time();
		}

		// Create a new backup record
		$record = array(
			'description'     => JText::_('COM_AKEEBA_DISCOVER_LABEL_IMPORTEDDESCRIPTION'),
			'comment'         => '',
			'backupstart'     => date('Y-m-d H:i:s', $fileModificationTime),
			'backupend'       => date('Y-m-d H:i:s', $fileModificationTime + 1),
			'status'          => 'complete',
			'origin'          => 'backend',
			'type'            => 'full',
			'profile_id'      => 1,
			'archivename'     => $file,
			'absolute_path'   => $directory . '/' . $file,
			'multipart'       => $multipart,
			'tag'             => 'backend',
			'filesexist'      => 1,
			'remote_filename' => '',
			'total_size'      => $total_size
		);

		$id    = null;
		$id    = Platform::getInstance()->set_or_update_statistics($id, $record);

		return $id;
	}

}
