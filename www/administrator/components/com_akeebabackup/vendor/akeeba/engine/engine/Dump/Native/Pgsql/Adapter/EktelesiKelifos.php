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

namespace Akeeba\Engine\Dump\Native\Pgsql\Adapter;

defined('AKEEBAENGINE') || die();

/**
 * @since  10.3
 */
class EktelesiKelifos implements AdapterInterface
{
	public function diathesimo(): bool
	{
		return function_exists('shell_exec');
	}

	public function ektelesi(string $command, array &$output): int
	{
		$suffix = PHP_OS_FAMILY === 'Windows' ? '' : ' 2>&1';
		$result = @shell_exec($command . $suffix);

		if ($result !== null)
		{
			$output = explode("\n", rtrim($result));

			return 0;
		}

		$output = [];

		return -1;
	}
}
