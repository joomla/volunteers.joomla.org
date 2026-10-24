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

namespace Akeeba\Engine\Dump\Native;

defined('AKEEBAENGINE') || die();

use RuntimeException;

/**
 * Dump class for the "None" database driver (ie no database used by the application)
 */
#[\AllowDynamicProperties]
class Sqlite extends None
{
	public function __construct()
	{
		parent::__construct();

		throw new RuntimeException("Please do not add SQLite databases, they are files. If they are under your site's root they are backed up automatically. Otherwise use the Off-site Directories Inclusion to include them in the backup.");
	}

}
