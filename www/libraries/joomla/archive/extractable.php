<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Archive
 *
 * @copyright   (C) 2012 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * Archieve class interface
 *
 * @since       3.0.0
 * @deprecated  4.0 use the Joomla\Archive\ExtractableInterface interface instead
 */
interface JArchiveExtractable
{
	/**
	 * Extract a compressed file to a given path
	 *
	 * @param   string  $archive      Path to archive to extract
	 * @param   string  $destination  Path to extract archive to
	 * @param   array   $options      Extraction options [may be unused]
	 *
	 * @return  boolean  True if successful
	 *
	 * @since   3.0.0
	 */
	public function extract($archive, $destination, array $options = array());

	/**
	 * Tests whether this adapter can unpack files on this computer.
	 *
	 * @return  boolean  True if supported
	 *
	 * @since   3.0.0
	 */
	public static function isSupported();
}
