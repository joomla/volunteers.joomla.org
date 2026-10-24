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

use Akeeba\Engine\Dump\Base;
use Akeeba\Engine\Factory;

/**
 * Dump class for the "None" database driver (ie no database used by the application)
 */
#[\AllowDynamicProperties]
class None extends Base
{
	public function __construct()
	{
		parent::__construct();
	}

	/** @inheritDoc */
	protected function getTablesToBackup(): void
	{
	}

	/** @inheritDoc */
	protected function stepDatabaseDump(): void
	{
		Factory::getLog()->info("Reminder: database definitions using the 'None' driver result in no data being backed up.");

		$this->setState(self::STATE_FINISHED);
	}

	/** @inheritDoc */
	protected function getDatabaseNameFromConnection(): string
	{
		return '';
	}

	/** @inheritDoc */
	protected function getAllTables(): array
	{
		return [];
	}

	protected function _run()
	{
		Factory::getLog()->info("Reminder: database definitions using the 'None' driver result in no data being backed up.");

		$this->setState(self::STATE_POSTRUN);
	}

	protected function _finalize()
	{
		Factory::getLog()->info("Reminder: database definitions using the 'None' driver result in no data being backed up.");

		$this->setState(self::STATE_FINISHED);
	}
}
