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

namespace Akeeba\Engine\Dump\Native\MySQL;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Factory;

/**
 * Trait to filter out entities which have a name that prevents us from backing them up.
 *
 * This is used to prevent security and functional issues during restoration.
 *
 * @since  9.10.0
 */
trait BadEntityNamesTrait
{
	/**
	 * Is the entity name “bad”, preventing us from backing it up?
	 *
	 * @param   string  $type  Entity type, e.g. 'table'.
	 * @param   string  $name  Entity name, as reported by the database server.
	 *
	 * @return  bool
	 */
	private function isBadEntityName(string $type, string $name): bool
	{
		return $this->isCVEBadDump($type, $name)
		       || $this->isBackupPrefix($type, $name)
		       || $this->isAbstractNamedInDatabase($type, $name);
	}

	/**
	 * Does the entity name include a newline or carriage return?
	 *
	 * These entity names are rarely, if ever, legitimate. They are likely to trigger the MySQL vulnerability
	 * CVE-2017-3600 (“Bad Dump”). As a result, they will be excluded from the database dump.
	 *
	 * @param   string  $type  The entity type: table, merge, view, procedure, event, function, or trigger.
	 * @param   string  $name  The name of the entity to process.
	 *
	 * @return  bool
	 */
	private function isCVEBadDump(string $type, string $name): bool
	{
		if ((strpos($name, "\r") === false) && (strpos($name, "\n") === false))
		{
			return false;
		}

		$name = str_replace(["\r", "\n"], ['\\r', '\\n'], $name);

		Factory::getLog()->warning(
			sprintf(
				"%s :: [SECURITY] %s %s includes newline characters. Skipping table to protect you against possible MySQL vulnerability CVE-2017-3600 (“Bad Dump”).",
				__CLASS__,
				ucfirst($type),
				$name
			)
		);

		return true;
	}

	/**
	 * Does the entity name start with the literal prefix `bak_`?
	 *
	 * These are backup copies of tables, views, etc created during a previous restoration. They will be automatically
	 * skipped from the backup to avoid restoration woes.
	 *
	 * @param   string  $type  The entity type: table, merge, view, procedure, event, function, or trigger.
	 * @param   string  $name  The name of the entity to process.
	 *
	 * @return  bool
	 */
	private function isBackupPrefix(string $type, string $name): bool
	{
		if (substr($name, 0, 4) != 'bak_')
		{
			return false;
		}

		Factory::getLog()->info(
			sprintf(
				"%s :: Backup %s %s automatically skipped.",
				__CLASS__,
				ucfirst($type),
				$name
			)
		);

		return true;
	}

	/**
	 * Does the entity name in the database start with the literal prefix `#__`?
	 *
	 * If this is the case, the entity will be automatically skipped from the backup as the literal prefix `#__` will
	 * clash with our abstract naming pattern during restoration.
	 *
	 * @param   string  $type  The entity type: table, merge, view, procedure, event, function, or trigger.
	 * @param   string  $name  The name of the entity to process.
	 *
	 * @return  bool
	 */
	private function isAbstractNamedInDatabase(string $type, string $name): bool
	{
		if (substr($name, 0, 3) !== '#__')
		{
			return false;
		}

		Factory::getLog()->warning(
			sprintf(
				"%s :: %s %s has a prefix of #__. This would cause restoration errors; table skipped.", __CLASS__,
				ucfirst($type),
				$name
			)
		);

		return true;
	}
}