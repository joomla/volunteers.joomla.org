<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Platform;

defined('_JEXEC') || die;

use FOF40\Container\Container;

interface FilesystemInterface
{
	/**
	 * Public constructor.
	 *
	 * @param \FOF40\Container\Container $c The component container
	 */
	public function __construct(Container $c);

	/**
	 * Does the file exists?
	 *
	 * @param   $path  string   Path to the file to test
	 *
	 * @return  bool
	 */
	public function fileExists(string $path): bool;

	/**
	 * Delete a file or array of files
	 *
	 * @param string|array $file The file name or an array of file names
	 *
	 * @return  bool  True on success
	 *
	 */
	public function fileDelete($file): bool;

	/**
	 * Copies a file
	 *
	 * @param string $src         The path to the source file
	 * @param string $dest        The path to the destination file
	 * @param string $path        An optional base path to prefix to the file names
	 * @param bool   $use_streams True to use streams
	 *
	 * @return  bool  True on success
	 */
	public function fileCopy(string $src, string $dest, ?string $path = null, bool $use_streams = false): bool;

	/**
	 * Write contents to a file
	 *
	 * @param string    $file        The full file path
	 * @param string   &$buffer      The buffer to write
	 * @param bool      $use_streams Use streams
	 *
	 * @return  bool  True on success
	 */
	public function fileWrite(string $file, string &$buffer, bool $use_streams = false): bool;

	/**
	 * Checks for snooping outside of the file system root.
	 *
	 * @param string $path A file system path to check.
	 *
	 * @return  string  A cleaned version of the path or exit on error.
	 *
	 * @throws  \Exception
	 */
	public function pathCheck(string $path): string;

	/**
	 * Function to strip additional / or \ in a path name.
	 *
	 * @param string $path The path to clean.
	 * @param string $ds   Directory separator (optional).
	 *
	 * @return  string  The cleaned path.
	 *
	 * @throws  \UnexpectedValueException
	 */
	public function pathClean(string $path, string $ds = DIRECTORY_SEPARATOR): string;

	/**
	 * Searches the directory paths for a given file.
	 *
	 * @param string|array $paths An path string or array of path strings to search in
	 * @param string       $file  The file name to look for.
	 *
	 * @return  string|null   The full path and file name for the target file; null if the file is not found in any of the paths.
	 */
	public function pathFind($paths, string $file): ?string;

	/**
	 * Wrapper for the standard file_exists function
	 *
	 * @param string $path Folder name relative to installation dir
	 *
	 * @return  bool  True if path is a folder
	 */
	public function folderExists(string $path): bool;

	/**
	 * Utility function to read the files in a folder.
	 *
	 * @param string $path          The path of the folder to read.
	 * @param string $filter        A filter for file names.
	 * @param mixed  $recurse       True to recursively search into sub-folders, or an integer to specify the maximum depth.
	 * @param bool   $full          True to return the full path to the file.
	 * @param array  $exclude       Array with names of files which should not be shown in the result.
	 * @param array  $excludefilter Array of filter to exclude
	 *
	 * @return  array  Files in the given folder.
	 */
	public function folderFiles(string $path, string $filter = '.', bool $recurse = false, bool $full = false,
	                            array $exclude = [
		                            '.svn', 'CVS', '.DS_Store', '__MACOSX',
	                            ], array $excludefilter = ['^\..*', '.*~'], bool $naturalSort = false): array;

	/**
	 * Utility function to read the folders in a folder.
	 *
	 * @param string $path          The path of the folder to read.
	 * @param string $filter        A filter for folder names.
	 * @param mixed  $recurse       True to recursively search into sub-folders, or an integer to specify the maximum depth.
	 * @param bool   $full          True to return the full path to the folders.
	 * @param array  $exclude       Array with names of folders which should not be shown in the result.
	 * @param array  $excludefilter Array with regular expressions matching folders which should not be shown in the result.
	 *
	 * @return  array  Folders in the given folder.
	 */
	public function folderFolders(string $path, string $filter = '.', bool $recurse = false, bool $full = false, array $exclude = [
		'.svn', 'CVS', '.DS_Store', '__MACOSX',
	], array $excludefilter = ['^\..*']): array;

	/**
	 * Create a folder -- and all necessary parent folders.
	 *
	 * @param string  $path A path to create from the base path.
	 * @param integer $mode Directory permissions to set for folders created. 0755 by default.
	 *
	 * @return  bool  True if successful.
	 */
	public function folderCreate(string $path = '', int $mode = 0755): bool;

	/**
	 * Gets the extension of a file name
	 *
	 * @param string $file The file name
	 *
	 * @return  string  The file extension
	 */
	public function getExt(string $file): string;

	/**
	 * Strips the last extension off of a file name
	 *
	 * @param string $file The file name
	 *
	 * @return  string  The file name without the extension
	 */
	public function stripExt(string $file): string;
}
