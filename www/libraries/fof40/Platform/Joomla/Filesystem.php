<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Platform\Joomla;

defined('_JEXEC') || die;

use FOF40\Platform\Base\Filesystem as BaseFilesystem;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\Path;

/**
 * Abstraction for Joomla! filesystem API
 */
class Filesystem extends BaseFilesystem
{
	/**
	 * Does the file exists?
	 *
	 * @param   $path  string   Path to the file to test
	 *
	 * @return  bool
	 */
	public function fileExists(string $path): bool
	{
		return File::exists($path);
	}

	/**
	 * Delete a file or array of files
	 *
	 * @param   mixed  $file  The file name or an array of file names
	 *
	 * @return  bool  True on success
	 *
	 */
	public function fileDelete($file): bool
	{
		if (!is_string($file) && !is_array($file))
		{
			throw new \InvalidArgumentException(sprintf('%s::%s -- $file expects a string or an array', __CLASS__, __METHOD__));
		}

		return File::delete($file);
	}

	/**
	 * Copies a file
	 *
	 * @param   string  $src          The path to the source file
	 * @param   string  $dest         The path to the destination file
	 * @param   string  $path         An optional base path to prefix to the file names
	 * @param   bool    $use_streams  True to use streams
	 *
	 * @return  bool  True on success
	 */
	public function fileCopy(string $src, string $dest, ?string $path = null, bool $use_streams = false): bool
	{
		return File::copy($src, $dest, $path, $use_streams);
	}

	/**
	 * Write contents to a file
	 *
	 * @param   string    $file         The full file path
	 * @param   string   &$buffer       The buffer to write
	 * @param   bool      $use_streams  Use streams
	 *
	 * @return  bool  True on success
	 */
	public function fileWrite(string $file, string &$buffer, bool $use_streams = false): bool
	{
		return File::write($file, $buffer, $use_streams);
	}

	/**
	 * Checks for snooping outside of the file system root.
	 *
	 * @param   string  $path  A file system path to check.
	 *
	 * @return  string  A cleaned version of the path or exit on error.
	 *
	 * @throws  \Exception
	 */
	public function pathCheck(string $path): string
	{
		return Path::check($path);
	}

	/**
	 * Function to strip additional / or \ in a path name.
	 *
	 * @param   string  $path  The path to clean.
	 * @param   string  $ds    Directory separator (optional).
	 *
	 * @return  string  The cleaned path.
	 *
	 * @throws  \UnexpectedValueException
	 */
	public function pathClean(string $path, string $ds = DIRECTORY_SEPARATOR): string
	{
		return Path::clean($path, $ds);
	}

	/**
	 * Searches the directory paths for a given file.
	 *
	 * @param   mixed   $paths  An path string or array of path strings to search in
	 * @param   string  $file   The file name to look for.
	 *
	 * @return  string|null   The full path and file name for the target file, or bool false if the file is not found
	 *                        in any of the paths.
	 */
	public function pathFind($paths, string $file): ?string
	{
		if (!is_string($paths) && !is_array($paths))
		{
			throw new \InvalidArgumentException(sprintf('%s::%s -- $paths expects a string or an array', __CLASS__, __METHOD__));
		}

		$ret = Path::find($paths, $file);

		if (($ret === false) || ($ret === ''))
		{
			return null;
		}

		return $ret;
	}

	/**
	 * Wrapper for the standard file_exists function
	 *
	 * @param   string  $path  Folder name relative to installation dir
	 *
	 * @return  bool  True if path is a folder
	 */
	public function folderExists(string $path): bool
	{
		try
		{
			return Folder::exists($path);
		}
		catch (\Exception $e)
		{
			return false;
		}
	}

	/**
	 * Utility function to read the files in a folder.
	 *
	 * @param   string  $path           The path of the folder to read.
	 * @param   string  $filter         A filter for file names.
	 * @param   mixed   $recurse        True to recursively search into sub-folders, or an integer to specify the
	 *                                  maximum depth.
	 * @param   bool    $full           True to return the full path to the file.
	 * @param   array   $exclude        Array with names of files which should not be shown in the result.
	 * @param   array   $excludefilter  Array of filter to exclude
	 * @param   bool    $naturalSort    False for asort, true for natsort
	 * @param   bool    $naturalSort    False for asort, true for natsort
	 *
	 * @return  array  Files in the given folder.
	 */
	public function folderFiles(string $path, string $filter = '.', bool $recurse = false, bool $full = false,
	                            array $exclude = [
		                            '.svn', 'CVS', '.DS_Store', '__MACOSX',
	                            ], array $excludefilter = ['^\..*', '.*~'], bool $naturalSort = false): array
	{
		// JFolder throws nonsense errors if the path is not a folder
		try
		{
			$path = Path::clean($path);
		}
		catch (\Exception $e)
		{
			return [];
		}

		if (!@is_dir($path))
		{
			return [];
		}

		// Now call JFolder
		return Folder::files($path, $filter, $recurse, $full, $exclude, $excludefilter, $naturalSort);
	}

	/**
	 * Utility function to read the folders in a folder.
	 *
	 * @param   string  $path           The path of the folder to read.
	 * @param   string  $filter         A filter for folder names.
	 * @param   mixed   $recurse        True to recursively search into sub-folders, or an integer to specify the
	 *                                  maximum depth.
	 * @param   bool    $full           True to return the full path to the folders.
	 * @param   array   $exclude        Array with names of folders which should not be shown in the result.
	 * @param   array   $excludefilter  Array with regular expressions matching folders which should not be shown in
	 *                                  the result.
	 *
	 * @return  array  Folders in the given folder.
	 */
	public function folderFolders(string $path, string $filter = '.', bool $recurse = false, bool $full = false, array $exclude = [
		'.svn', 'CVS', '.DS_Store', '__MACOSX',
	], array $excludefilter = ['^\..*']): array
	{
		// JFolder throws idiotic errors if the path is not a folder
		try
		{
			$path = Path::clean($path);
		}
		catch (\Exception $e)
		{
			return [];
		}

		if (!@is_dir($path))
		{
			return [];
		}

		// Now call JFolder
		return Folder::folders($path, $filter, $recurse, $full, $exclude, $excludefilter);
	}

	/**
	 * Create a folder -- and all necessary parent folders.
	 *
	 * @param   string   $path  A path to create from the base path.
	 * @param   integer  $mode  Directory permissions to set for folders created. 0755 by default.
	 *
	 * @return  bool  True if successful.
	 */
	public function folderCreate(string $path = '', int $mode = 0755): bool
	{
		return Folder::create($path, $mode);
	}
}
