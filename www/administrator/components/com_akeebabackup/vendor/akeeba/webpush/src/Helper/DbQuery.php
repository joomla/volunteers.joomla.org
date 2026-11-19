<?php
/**
 * Akeeba WebPush
 *
 * An abstraction layer for easier implementation of WebPush in Joomla components.
 *
 * @copyright Copyright (c) 2022-2026 Akeeba Ltd
 * @license   GNU GPL v3 or later; see LICENSE.txt
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace Akeeba\WebPush\Helper;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;

/**
 * Database query factory, isolating the createQuery() compatibility branch.
 *
 * `DatabaseDriver::createQuery()` was added in Joomla 5.1. On older versions the equivalent is
 * `getQuery(true)`, which is deprecated on newer ones. Rather than repeat that choice at every
 * call site, every query in this library is built through here.
 *
 * There is a second reason to funnel through one method even once the floor is high enough that
 * the branch below is dead: `createQuery()` is only declared on `DatabaseInterface` from Joomla
 * 6.0. Most callers hold a `DatabaseInterface` — that is the key they resolve from the DI
 * container — so calling it directly is correct at runtime but unprovable to a static analyser on
 * Joomla 5.x. Confining that to one method confines the analyser's complaint to one method.
 *
 * When the minimum supported Joomla version reaches 5.1, this becomes a one-line method. That is
 * the point: raising the floor is then a single edit, not several dozen.
 */
final class DbQuery
{
	/**
	 * Create a new, empty query object.
	 *
	 * @param   DatabaseInterface  $db  The database driver to create the query for.
	 *
	 * @return  QueryInterface
	 */
	public static function create(DatabaseInterface $db): QueryInterface
	{
		return method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true);
	}
}
