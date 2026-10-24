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

trait WarningsLoggerAware
{
	/**
	 * The warnings in the current queue
	 *
	 * @var string[]
	 */
	private $warningsQueue = [];

	/**
	 * The maximum length of the warnings queue
	 *
	 * @var int
	 */
	private $warningsQueueSize = 0;

	/**
	 * A combination of getWarnings() and resetWarnings(). Returns the warnings and immediately resets the warnings
	 * queue.
	 *
	 * @return array
	 */
	final public function getAndResetWarnings()
	{
		$ret = $this->getWarnings();

		$this->resetWarnings();

		return $ret;
	}

	/**
	 * Returns an array with all warnings logged since the last time warnings were reset. The maximum number of warnings
	 * returned is controlled by setWarningsQueueSize().
	 *
	 * @return array
	 */
	final public function getWarnings()
	{
		return $this->warningsQueue;
	}

	/**
	 * Resets the warnings queue.
	 *
	 * @return void
	 */
	final public function resetWarnings()
	{
		$this->warningsQueue = [];
	}

	/**
	 * Returns the warnings queue size.
	 *
	 * @return int
	 */
	final public function getWarningsQueueSize()
	{
		return $this->warningsQueueSize;
	}

	/**
	 * Set the warnings queue size. A size of 0 means "no limit".
	 *
	 * @param   int  $queueSize  The size of the warnings queue (in number of warnings items)
	 *
	 * @return void
	 */
	final public function setWarningsQueueSize($queueSize = 0)
	{
		if (!is_numeric($queueSize) || empty($queueSize) || ($queueSize < 0))
		{
			$queueSize = 0;
		}

		$this->warningsQueueSize = $queueSize;
	}

	/**
	 * Adds a warning to the warnings queue.
	 *
	 * @param   string  $warning
	 */
	final protected function enqueueWarning($warning)
	{
		$this->warningsQueue[] = $warning;

		// If there is no queue size limit there's nothing else to be done.
		if ($this->warningsQueueSize <= 0)
		{
			return;
		}

		// If the queue size is exceeded remove as many of the earliest elements as required
		if (count($this->warningsQueue) > $this->warningsQueueSize)
		{
			$this->warningsQueueSize = array_slice($this->warningsQueue, -$this->warningsQueueSize);
		}
	}
}
