<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2014 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Filesystem\Wrapper;

use Joomla\Filesystem\File;

defined('JPATH_PLATFORM') or die;

/**
 * Wrapper class for File
 *
 * @since       3.4
 * @deprecated  4.0 Use \Joomla\CMS\Filesystem\File instead
 */
class FileWrapper
{
	/**
	 * Helper wrapper method for getExt
	 *
	 * @param   string  $file  The file name.
	 *
	 * @return  string  The file extension.
	 *
	 * @see         File::getExt()
	 * @since       3.4
	 * @deprecated  4.0 Use \Joomla\CMS\Filesystem\File instead
	 */
	public function getExt($file)
	{
		return File::getExt($file);
	}

	/**
	 * Helper wrapper method for stripExt
	 *
	 * @param   string  $file  The file name.
	 *
	 * @return  string  The file name without the extension.
	 *
	 * @see         File::stripExt()
	 * @since       3.4
	 * @deprecated  4.0 Use \Joomla\CMS\Filesystem\File instead
	 */
	public function stripExt($file)
	{
		return File::stripExt($file);
	}

	/**
	 * Helper wrapper method for makeSafe
	 *
	 * @param   string  $file  The name of the file [not full path].
	 *
	 * @return  string  The sanitised string.
	 *
	 * @see         File::makeSafe()
	 * @since       3.4
	 * @deprecated  4.0 Use \Joomla\CMS\Filesystem\File instead
	 */
	public function makeSafe($file)
	{
		return File::makeSafe($file);
	}

	/**
	 * Helper wrapper method for copy
	 *
	 * @param   string   $src         The path to the source file.
	 * @param   string   $dest        The path to the destination file.
	 * @param   string   $path        An optional base path to prefix to the file names.
	 * @param   boolean  $useStreams  True to use streams.
	 *
	 * @return  boolean  True on success.
	 *
	 * @see         File::copy()
	 * @since       3.4
	 * @deprecated  4.0 Use \Joomla\CMS\Filesystem\File instead
	 */
	public function copy($src, $dest, $path = null, $useStreams = false)
	{
		return File::copy($src, $dest, $path, $useStreams);
	}

	/**
	 * Helper wrapper method for delete
	 *
	 * @param   mixed  $file  The file name or an array of file names
	 *
	 * @return  boolean  True on success.
	 *
	 * @see         File::delete()
	 * @since       3.4
	 * @deprecated  4.0 Use \Joomla\CMS\Filesystem\File instead
	 */
	public function delete($file)
	{
		return File::delete($file);
	}

	/**
	 * Helper wrapper method for move
	 *
	 * @param   string   $src         The path to the source file.
	 * @param   string   $dest        The path to the destination file.
	 * @param   string   $path        An optional base path to prefix to the file names.
	 * @param   boolean  $useStreams  True to use streams.
	 *
	 * @return  boolean  True on success.
	 *
	 * @see         File::move()
	 * @since       3.4
	 * @deprecated  4.0 Use \Joomla\CMS\Filesystem\File instead
	 */
	public function move($src, $dest, $path = '', $useStreams = false)
	{
		return File::move($src, $dest, $path, $useStreams);
	}

	/**
	 * Helper wrapper method for read
	 *
	 * @param   string   $filename   The full file path.
	 * @param   boolean  $incpath    Use include path.
	 * @param   integer  $amount     Amount of file to read.
	 * @param   integer  $chunksize  Size of chunks to read.
	 * @param   integer  $offset     Offset of the file.
	 *
	 * @return mixed  Returns file contents or boolean False if failed.
	 *
	 * @see         File::read()
	 * @since       3.4
	 * @deprecated  4.0 Use \Joomla\CMS\Filesystem\File instead
	 */
	public function read($filename, $incpath = false, $amount = 0, $chunksize = 8192, $offset = 0)
	{
		return File::read($filename, $incpath, $amount, $chunksize, $offset);
	}

	/**
	 * Helper wrapper method for write
	 *
	 * @param   string   $file        The full file path.
	 * @param   string   &$buffer     The buffer to write.
	 * @param   boolean  $useStreams  Use streams.
	 *
	 * @return boolean  True on success.
	 *
	 * @see         File::write()
	 * @since       3.4
	 * @deprecated  4.0 Use \Joomla\CMS\Filesystem\File instead
	 */
	public function write($file, &$buffer, $useStreams = false)
	{
		return File::write($file, $buffer, $useStreams);
	}

	/**
	 * Helper wrapper method for upload
	 *
	 * @param   string   $src         The name of the php (temporary) uploaded file.
	 * @param   string   $dest        The path (including filename) to move the uploaded file to.
	 * @param   boolean  $useStreams  True to use streams.
	 *
	 * @return boolean  True on success.
	 *
	 * @see         File::upload()
	 * @since       3.4
	 * @deprecated  4.0 Use \Joomla\CMS\Filesystem\File instead
	 */
	public function upload($src, $dest, $useStreams = false)
	{
		return File::upload($src, $dest, $useStreams);
	}

	/**
	 * Helper wrapper method for exists
	 *
	 * @param   string  $file  File path.
	 *
	 * @return boolean  True if path is a file.
	 *
	 * @see         File::exists()
	 * @since       3.4
	 * @deprecated  4.0 Use \Joomla\CMS\Filesystem\File instead
	 */
	public function exists($file)
	{
		return File::exists($file);
	}

	/**
	 * Helper wrapper method for getName
	 *
	 * @param   string  $file  File path.
	 *
	 * @return string  filename.
	 *
	 * @see         File::getName()
	 * @since       3.4
	 * @deprecated  4.0 Use \Joomla\CMS\Filesystem\File instead
	 */
	public function getName($file)
	{
		return File::getName($file);
	}
}
