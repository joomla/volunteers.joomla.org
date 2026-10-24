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

namespace Akeeba\Engine\Util\Log;

defined('AKEEBAENGINE') || die();

interface WarningsLoggerInterface
{
	/**
	 * Returns an array with all warnings logged since the last time warnings were reset. The maximum number of warnings
	 * returned is controlled by setWarningsQueueSize().
	 *
	 * @return array
	 */
	public function getWarnings();

	/**
	 * Resets the warnings queue.
	 *
	 * @return void
	 */
	public function resetWarnings();

	/**
	 * A combination of getWarnings() and resetWarnings(). Returns the warnings and immediately resets the warnings
	 * queue.
	 *
	 * @return array
	 */
	public function getAndResetWarnings();

	/**
	 * Set the warnings queue size. A size of 0 means "no limit".
	 *
	 * @param   int  $queueSize  The size of the warnings queue (in number of warnings items)
	 *
	 * @return void
	 */
	public function setWarningsQueueSize($queueSize = 0);

	/**
	 * Returns the warnings queue size.
	 *
	 * @return int
	 */
	public function getWarningsQueueSize();
}
