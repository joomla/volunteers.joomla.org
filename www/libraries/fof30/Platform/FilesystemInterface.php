<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Platform;

defined('_JEXEC') || die;

use Exception;
use FOF30\Container\Container;
use UnexpectedValueException;

interface FilesystemInterface
{
	/**
	 * Public constructor.
	 *
	 * @param   Container  $c  The component container
	 */
	public function __construct(Container $c);

	/**
	 * Does the file exists?
	 *
	 * @param   $path  string   Path to the file to test
	 *
	 * @return  bool
	 */
	public function fileExists($path);

	/**
	 * Delete a file or array of files
	 *
	 * @param   mixed  $file  The file name or an array of file names
	 *
	 * @return  boolean  True on success
	 *
	 */
	public function fileDelete($file);

	/**
	 * Copies a file
	 *
	 * @param   string  $src   The path to the source file
	 * @param   string  $dest  The path to the destination file
	 *
	 * @return  boolean  True on success
	 */
	public function fileCopy($src, $dest);

	/**
	 * Write contents to a file
	 *
	 * @param   string    $file    The full file path
	 * @param   string   &$buffer  The buffer to write
	 *
	 * @return  boolean  True on success
	 */
	public function fileWrite($file, &$buffer);

	/**
	 * Checks for snooping outside of the file system root.
	 *
	 * @param   string  $path  A file system path to check.
	 *
	 * @return  string  A cleaned version of the path or exit on error.
	 *
	 * @throws  Exception
	 */
	public function pathCheck($path);

	/**
	 * Function to strip additional / or \ in a path name.
	 *
	 * @param   string  $path  The path to clean.
	 * @param   string  $ds    Directory separator (optional).
	 *
	 * @return  string  The cleaned path.
	 *
	 * @throws  UnexpectedValueException
	 */
	public function pathClean($path, $ds = DIRECTORY_SEPARATOR);

	/**
	 * Searches the directory paths for a given file.
	 *
	 * @param   mixed   $paths  An path string or array of path strings to search in
	 * @param   string  $file   The file name to look for.
	 *
	 * @return  mixed   The full path and file name for the target file, or boolean false if the file is not found in
	 *                  any of the paths.
	 */
	public function pathFind($paths, $file);

	/**
	 * Wrapper for the standard file_exists function
	 *
	 * @param   string  $path  Folder name relative to installation dir
	 *
	 * @return  boolean  True if path is a folder
	 */
	public function folderExists($path);

	/**
	 * Utility function to read the files in a folder.
	 *
	 * @param   string   $path           The path of the folder to read.
	 * @param   string   $filter         A filter for file names.
	 * @param   mixed    $recurse        True to recursively search into sub-folders, or an integer to specify the
	 *                                   maximum depth.
	 * @param   boolean  $full           True to return the full path to the file.
	 * @param   array    $exclude        Array with names of files which should not be shown in the result.
	 * @param   array    $excludefilter  Array of filter to exclude
	 *
	 * @return  array  Files in the given folder.
	 */
	public function folderFiles($path, $filter = '.', $recurse = false, $full = false, $exclude = [
		'.svn', 'CVS', '.DS_Store', '__MACOSX',
	],
	                            $excludefilter = ['^\..*', '.*~']);

	/**
	 * Utility function to read the folders in a folder.
	 *
	 * @param   string   $path           The path of the folder to read.
	 * @param   string   $filter         A filter for folder names.
	 * @param   mixed    $recurse        True to recursively search into sub-folders, or an integer to specify the
	 *                                   maximum depth.
	 * @param   boolean  $full           True to return the full path to the folders.
	 * @param   array    $exclude        Array with names of folders which should not be shown in the result.
	 * @param   array    $excludefilter  Array with regular expressions matching folders which should not be shown in
	 *                                   the result.
	 *
	 * @return  array  Folders in the given folder.
	 */
	public function folderFolders($path, $filter = '.', $recurse = false, $full = false, $exclude = [
		'.svn', 'CVS', '.DS_Store', '__MACOSX',
	],
	                              $excludefilter = ['^\..*']);

	/**
	 * Create a folder -- and all necessary parent folders.
	 *
	 * @param   string   $path  A path to create from the base path.
	 * @param   integer  $mode  Directory permissions to set for folders created. 0755 by default.
	 *
	 * @return  boolean  True if successful.
	 */
	public function folderCreate($path = '', $mode = 0755);

	/**
	 * Gets the extension of a file name
	 *
	 * @param   string  $file  The file name
	 *
	 * @return  string  The file extension
	 */
	public function getExt($file);

	/**
	 * Strips the last extension off of a file name
	 *
	 * @param   string  $file  The file name
	 *
	 * @return  string  The file name without the extension
	 */
	public function stripExt($file);
}
