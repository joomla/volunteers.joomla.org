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

/**
 * Describes a database entity to be backed up.
 *
 * @property string      $type
 * @property string|null $name
 * @property string|null $abstractName
 * @property bool        $dumpContents
 */
class Entity
{
	private string $type = 'table';

	private ?string $name = null;

	private ?string $abstractName = null;

	private bool $dumpContents = true;

	public function __construct(
		string $type, string $name, ?string $abstractName = null, bool $dumpContents = true
	)
	{
		$this->setType($type);
		$this->setName($name);
		$this->setAbstractName($abstractName);
		$this->setDumpContents($dumpContents);
	}

	public function __get($name)
	{
		$method = 'get' . ucfirst($name);

		if (!method_exists($this, $method))
		{
			throw new \DomainException(
				sprintf("Invalid property “%s”", $name)
			);
		}

		return $this->{$method}();
	}

	public function __set($name, $value)
	{
		$method = 'set' . ucfirst($name);

		if (!method_exists($this, $method))
		{
			throw new \DomainException(
				sprintf("Invalid property “%s”", $name)
			);
		}

		$this->{$method}($value);
	}


	public function getType(): string
	{
		return $this->type;
	}

	public function setType(string $type): self
	{
		$type = strtolower($type);

		if (in_array($type, ['table', 'view', 'procedure', 'function', 'trigger', 'event']))
		{
			$this->type = $type;

			return $this;
		}

		if (strpos($type, 'table') !== false)
		{
			$type = 'table';
		}
		elseif (strpos($type, 'view') !== false)
		{
			$type = 'view';
		}
		else
		{
			throw new \InvalidArgumentException(
				sprintf("Invalid database entity type “%s”", $type)
			);
		}

		$this->type = $type;

		return $this;
	}

	public function getDumpContents(): bool
	{
		return $this->dumpContents;
	}

	public function setDumpContents(bool $dumpContents): self
	{
		$this->dumpContents = $dumpContents;

		return $this;
	}

	public function getName(): ?string
	{
		return $this->name;
	}

	public function setName(?string $name): self
	{
		$this->name         = $name;
		$this->abstractName = null;

		return $this;
	}

	public function getAbstractName(): ?string
	{
		return $this->abstractName;
	}

	public function setAbstractName(?string $abstractName): self
	{
		$this->abstractName = $abstractName;

		return $this;
	}

	public function isRoutine(): bool
	{
		return !in_array($this->type, ['table', 'view']);
	}

	public function isTable()
	{
		return $this->type === 'table';
	}

	public function isView()
	{
		return $this->type === 'view';
	}

}