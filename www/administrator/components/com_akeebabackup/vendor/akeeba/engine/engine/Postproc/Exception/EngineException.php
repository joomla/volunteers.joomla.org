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

namespace Akeeba\Engine\Postproc\Exception;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Postproc\Base;
use Exception;
use RuntimeException;
use Throwable;

class EngineException extends RuntimeException
{
	protected $messagePrototype = 'The %s post-processing engine has experienced an unspecified error.';

	/**
	 * Construct the exception. If a message is not defined the default message for the exception will be used.
	 *
	 * @param   string               $message   [optional] The Exception message to throw.
	 * @param   int                  $code      [optional] The Exception code.
	 * @param   Exception|Throwable  $previous  [optional] The previous throwable used for the exception chaining.
	 */
	public function __construct($message = "", $code = 0, $previous = null)
	{
		if (empty($message))
		{
			$engineName = $this->getEngineKeyFromBacktrace();
			$message    = sprintf($this->messagePrototype, $engineName);
		}

		parent::__construct($message, $code, $previous);
	}

	/**
	 * Returns the engine name (class name without the namespace) from the PHP execution backtrace.
	 *
	 * @return mixed|string
	 */
	protected function getEngineKeyFromBacktrace()
	{
		// Make sure the backtrace is at least 3 levels deep
		$backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 5);

		// We need to be at least two levels deep
		if (count($backtrace) < 2)
		{
			return 'current';
		}

		for ($i = 1; $i < count($backtrace); $i++)
		{
			// Get the fully qualified class
			$object = $backtrace[$i]['object'];

			// We need a backtrace element with an object attached.
			if (!is_object($object))
			{
				continue;
			}

			// If the object is not a Postproc\Base object go to the next entry.
			if (!($object instanceof Base))
			{
				continue;
			}

			// Get the bare class name
			$fqnClass  = $backtrace[$i]['class'];
			$parts     = explode('\\', $fqnClass);
			$bareClass = array_pop($parts);

			// Do not return the base object!
			if ($bareClass == 'Base')
			{
				continue;
			}

			return $bareClass;
		}

		return 'current';
	}
}
