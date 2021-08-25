<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Archive
 *
 * @copyright   (C) 2014 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * Wrapper class for JArchive
 *
 * @package     Joomla.Platform
 * @subpackage  Archive
 * @since       3.4
 * @deprecated  4.0 use the Joomla\Archive\Archive class instead
 */
class JArchiveWrapperArchive
{
	/**
	 * Helper wrapper method for extract
	 *
	 * @param   string  $archivename  The name of the archive file
	 * @param   string  $extractdir   Directory to unpack into
	 *
	 * @return  boolean  True for success
	 *
	 * @see     JArchive::extract()
	 * @since   3.4
	 * @throws InvalidArgumentException
	 * @deprecated 4.0 use the Joomla\Archive\Archive class instead
	 */
	public function extract($archivename, $extractdir)
	{
		return JArchive::extract($archivename, $extractdir);
	}

	/**
	 * Helper wrapper method for getAdapter
	 *
	 * @param   string  $type  The type of adapter (bzip2|gzip|tar|zip).
	 *
	 * @return  JArchiveExtractable  Adapter for the requested type
	 *
	 * @see     JUserHelper::getAdapter()
	 * @since   3.4
	 * @deprecated 4.0 use the Joomla\Archive\Archive class instead
	 */
	public function getAdapter($type)
	{
		return JArchive::getAdapter($type);
	}
}
