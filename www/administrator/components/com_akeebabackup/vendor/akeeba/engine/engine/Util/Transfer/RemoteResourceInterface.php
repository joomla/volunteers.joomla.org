<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License version 3, or later
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program. If not, see
 * <https://www.gnu.org/licenses/>.
 */

namespace Akeeba\Engine\Util\Transfer;

defined('AKEEBAENGINE') || die();

/**
 * An interface for Transfer adapters which support remote resources, allowing us to efficient read from / write to
 * remote locations as if they were local files.
 */
interface RemoteResourceInterface
{
	/**
	 * Return a string with the appropriate stream wrapper protocol for $path. You can use the result with all PHP
	 * functions / classes which accept file paths such as DirectoryIterator, file_get_contents, file_put_contents,
	 * fopen etc.
	 *
	 * @param   string  $path
	 *
	 * @return  string
	 */
	public function getWrapperStringFor($path);

	/**
	 * Return the raw server listing for the requested folder.
	 *
	 * @param   string  $folder  The path name to list
	 *
	 * @return  string
	 */
	public function getRawList($folder);
}
