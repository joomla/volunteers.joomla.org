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

namespace Akeeba\Engine\Dump\Dependencies;

class Resolver
{
	private array $tree;

	private array $resolved;

	private array $unresolved;

	public function __construct(array $tree = [])
	{
		$this->tree = $tree;
	}

	public function add(string $dependent, string $dependency = ''): void
	{
		$this->tree[$dependent] ??= [];

		if ($dependency !== '')
		{
			$this->tree[$dependent][] = $dependency;
		}
	}

	public function remove(string $dependent, string $dependency): void
	{
		if (!$this->tree[$dependent])
		{
			return;
		}

		if (!in_array($dependency, $this->tree[$dependent]))
		{
			return;
		}

		$this->tree[$dependent] = array_values(array_diff($this->tree[$dependent], [$dependency]));
	}

	public function snip(string $dependent): void
	{
		if (!$this->tree[$dependent])
		{
			return;
		}

		unset($this->tree[$dependent]);
	}

	public function resolve(): array
	{
		$this->resolved   = [];
		$this->unresolved = [];

		// Resolve dependencies for each table
		foreach (array_keys($this->tree) as $table)
		{
			$this->resolver($table);
		}

		return $this->resolved;
	}

	private function resolver($item): void
	{
		$this->unresolved[] = $item;

		foreach ($this->tree[$item] as $dep)
		{
			if (!array_key_exists($dep, $this->tree))
			{
				continue;
			}

			if (in_array($dep, $this->resolved, true))
			{
				continue;
			}

			if (in_array($dep, $this->unresolved, true))
			{
				continue;
			}

			$this->unresolved[] = $dep;
			$this->resolver($dep);
		}

		if (!in_array($item, $this->resolved, true))
		{
			$this->resolved[] = $item;
		}

		while (($index = array_search($item, $this->unresolved, true)) !== false)
		{
			unset($this->unresolved[$index]);
		}
	}
}