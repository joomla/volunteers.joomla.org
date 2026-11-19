<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Helper;

defined('_JEXEC') || die();

class ArchivePartName
{
	/**
	 * Get the filename of a part of a multi-part archive, given the base file and the part number.
	 *
	 * The first part has the base file's own name (.jpa, .jps, .zip). Part N, for N >= 1, replaces the extension with
	 * the first letter of the base file's extension followed by N as two digits, e.g. archive.jpa -> archive.j01.
	 *
	 * @param   string  $baseFile  Full path to the base file (.jpa, .jps, .zip)
	 * @param   int     $part      Part number; 0 is the base file
	 *
	 * @return  string
	 */
	public static function forPart(string $baseFile, int $part = 0): string
	{
		if ($part == 0)
		{
			return $baseFile;
		}

		$dirname  = dirname($baseFile);
		$basename = basename($baseFile);

		$pos       = strrpos($basename, '.');
		$extension = substr($basename, $pos + 1);

		$newExtension = substr($extension, 0, 1) . sprintf('%02u', $part);

		return $dirname . '/' . basename($basename, '.' . $extension) . '.' . $newExtension;
	}
}
