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
use Exception;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;

#[\AllowDynamicProperties]
class DiscoverModel extends BaseDatabaseModel
{
	/**
	 * Returns a list of the archive files in a directory which do not already belong to a backup record
	 *
	 * @return  array
	 */
	public function getFiles(): array
	{
		$directory = Factory::getFilesystemTools()->translateStockDirs(
			$this->getState('directory', '')
		);

		// Get all archive files
		$files = array_filter(Factory::getFileLister()->getFiles($directory, true),
			function ($file) {
				$dotPos = strrpos($file, '.');

				if ($dotPos === false)
				{
					return $dotPos;
				}

				$ext = strtoupper(substr($file, $dotPos + 1));

				return in_array($ext, ['JPA', 'JPS', 'ZIP']);
			});

		// If nothing found, bail out
		if (empty($files))
		{
			return [];
		}

		// Make sure these files do not already exist in another backup record.
		//
		// The directory prefix is bound as a query parameter. Its LIKE wildcards ('%' and '_') — and the escape
		// character '\' itself — are escaped so that a literal '_' or '%' in the path (e.g. /home/my_site/) is
		// matched literally instead of acting as a wildcard.
		$db            = $this->getDatabase();
		$likeDirectory = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $directory) . '%';
		$sql           = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select($db->qn('absolute_path'))
			->from($db->qn('#__akeebabackup_backups'))
			->where($db->qn('absolute_path') . ' LIKE :directory ESCAPE ' . $db->quote('\\'))
			->where($db->qn('filesexist') . ' = ' . $db->q('1'))
			->bind(':directory', $likeDirectory, ParameterType::STRING);

		try
		{
			$existingFiles = $db->setQuery($sql)->loadColumn();
		}
		catch (Exception $e)
		{
			$existingFiles = [];
		}

		$files = array_filter($files, function ($file) use ($existingFiles) {
			return !in_array($file, $existingFiles);
		});

		// Finally sort the resulting array for easier reading
		sort($files);

		return $files;
	}

	/**
	 * Imports an archive file as a new backup record
	 *
	 * @param   string  $file  The full path to the archive to import
	 *
	 * @return  int|null  The new backup record ID; null if the save failed without an error message
	 *
	 * @throws  Exception Error when saving the backup record
	 */
	public function import(string $file): ?int
	{
		$directory = Factory::getFilesystemTools()->translateStockDirs(
			$this->getState('directory', '')
		);

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
		$record = [
			'description'     => Text::_('COM_AKEEBABACKUP_DISCOVER_LABEL_IMPORTEDDESCRIPTION'),
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
			'total_size'      => $total_size,
		];

		$id = null;
		$id = Platform::getInstance()->set_or_update_statistics($id, $record);

		return $id;
	}

}