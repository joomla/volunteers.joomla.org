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

namespace Akeeba\Engine\Driver;

defined('AKEEBAENGINE') || die();

/**
 * MySQL classic driver for Akeeba Engine — compatibility stub.
 *
 * The PHP mysql extension was removed in PHP 7.0. Since the minimum supported PHP version is 7.4 the mysql extension
 * is never available. This class is a compatibility stub that extends the MySQLi driver, which uses the mysqli
 * extension supported since PHP 5.0.
 */
#[\AllowDynamicProperties]
class Mysql extends Mysqli
{
	/**
	 * The name of the database driver.
	 *
	 * @var    string
	 * @since  11.1
	 */
	public $name = 'mysql';
}
