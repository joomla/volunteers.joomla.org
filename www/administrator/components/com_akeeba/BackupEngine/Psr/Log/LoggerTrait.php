<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Psr\Log;

/**
 * This file is part of a privately namespaced copy of PSR-3 version 1.
 *
 * You can find the original PSR-3 in https://www.php-fig.org/psr/psr-3/ and the original code in
 * https://github.com/php-fig/log
 *
 * The license of the original code can be found below.
 *
 * Copyright (c) 2012 PHP Framework Interoperability Group
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

defined('AKEEBAENGINE') || die();

/**
 * This is a simple Logger trait that classes unable to extend AbstractLogger
 * (because they extend another class, etc) can include.
 *
 * It simply delegates all log-level-specific methods to the `log` method to
 * reduce boilerplate code that a simple Logger that does the same thing with
 * messages regardless of the error level has to implement.
 */
trait LoggerTrait
{
	/**
	 * System is unusable.
	 *
	 * @param   string  $message
	 * @param   array   $context
	 *
	 * @return null
	 */
	public function emergency($message, array $context = [])
	{
		$this->log(LogLevel::EMERGENCY, $message, $context);
	}

	/**
	 * Action must be taken immediately.
	 *
	 * Example: Entire website down, database unavailable, etc. This should
	 * trigger the SMS alerts and wake you up.
	 *
	 * @param   string  $message
	 * @param   array   $context
	 *
	 * @return null
	 */
	public function alert($message, array $context = [])
	{
		$this->log(LogLevel::ALERT, $message, $context);
	}

	/**
	 * Critical conditions.
	 *
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param   string  $message
	 * @param   array   $context
	 *
	 * @return null
	 */
	public function critical($message, array $context = [])
	{
		$this->log(LogLevel::CRITICAL, $message, $context);
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param   string  $message
	 * @param   array   $context
	 *
	 * @return null
	 */
	public function error($message, array $context = [])
	{
		$this->error($message, $context);
	}

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things
	 * that are not necessarily wrong.
	 *
	 * @param   string  $message
	 * @param   array   $context
	 *
	 * @return null
	 */
	public function warning($message, array $context = [])
	{
		$this->warning($message, $context);
	}

	/**
	 * Normal but significant events.
	 *
	 * @param   string  $message
	 * @param   array   $context
	 *
	 * @return null
	 */
	public function notice($message, array $context = [])
	{
		$this->log(LogLevel::NOTICE, $message, $context);
	}

	/**
	 * Interesting events.
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param   string  $message
	 * @param   array   $context
	 *
	 * @return null
	 */
	public function info($message, array $context = [])
	{
		$this->info($message, $context);
	}

	/**
	 * Detailed debug information.
	 *
	 * @param   string  $message
	 * @param   array   $context
	 *
	 * @return null
	 */
	public function debug($message, array $context = [])
	{
		$this->debug($message, $context);
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param   mixed   $level
	 * @param   string  $message
	 * @param   array   $context
	 *
	 * @return null
	 */
	abstract public function log($level, $message, array $context = []);
}
