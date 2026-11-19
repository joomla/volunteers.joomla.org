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

use Akeeba\Engine\Driver\Query\Base as QueryBase;

/**
 * Database query factory, isolating the createQuery() compatibility branch.
 *
 * `DatabaseDriver::createQuery()` was added in Joomla 5.1. On older versions the equivalent is
 * `getQuery(true)`, which is deprecated on newer ones. Rather than repeat that choice at every
 * call site, every query built against a database driver is built through here.
 *
 * There is a second reason to funnel through one method even once the floor is high enough that
 * the branch below is dead: `createQuery()` is only declared on `DatabaseInterface` from Joomla
 * 6.0. Most callers hold a driver resolved from the DI container, so calling it directly is
 * correct at runtime but unprovable to a static analyser on Joomla 5.x. Confining that to one
 * method confines the analyser's complaint to one method.
 *
 * When the minimum supported Joomla version reaches 5.1, this becomes a one-line method. That is
 * the point: raising the floor is then a single edit, not several dozen.
 *
 * @since  10.4.1
 */
final class DbQuery
{
	/**
	 * Create a new, empty query object.
	 *
	 * The argument is deliberately untyped. Not every object we are handed here is a `Driver\Base`
	 * descendant: the Joomla platform passes `Akeeba\Engine\Driver\Joomla`, a decorator which
	 * holds a `Driver\Base` privately and proxies to it through `__call()` without extending it.
	 * Type-hinting `Base` here would make every query built by the Joomla platform a fatal
	 * TypeError.
	 *
	 * @param   Base|object  $db  The database driver — or driver decorator — to create the query for.
	 *
	 * @return  QueryBase
	 * @since   10.4.1
	 */
	public static function create($db)
	{
		return method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true);
	}
}
