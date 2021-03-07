<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Date;

defined('_JEXEC') || die;

use DateTime;
use DateTimeZone;
use JDatabaseDriver;

/**
 * This decorator will get any DateTime descendant and turn it into a FOF40\Date\Date compatible class. If the methods
 * specific to Date are available they will be used. Otherwise a new Date object will be spun from the information
 * in the decorated DateTime object and the results of a call to its method will be returned.
 */
class DateDecorator extends Date
{
	/**
	 * The decorated object
	 *
	 * @param   string               $date  String in a format accepted by strtotime(), defaults to "now".
	 * @param   string|DateTimeZone  $tz    Time zone to be used for the date. Might be a string or a DateTimeZone
	 *                                      object.
	 *
	 * @var   DateTime
	 */
	protected $decorated;

	public function __construct(string $date = 'now', $tz = null)
	{
		$this->decorated = (is_object($date) && ($date instanceof DateTime)) ? $date : new Date($date, $tz);

		$timestamp = $this->decorated->toISO8601(true);

		parent::__construct($timestamp);

		$this->setTimezone($this->decorated->getTimezone());
	}

	public static function getInstance(string $date = 'now', $tz = null): self
	{
		$coreObject = new Date($date, $tz);

		return new DateDecorator($coreObject);
	}

	/**
	 * Magic method to access properties of the date given by class to the format method.
	 *
	 * @param   string  $name  The name of the property.
	 *
	 * @return  mixed   A value if the property name is valid, null otherwise.
	 */
	public function __get(string $name)
	{
		return $this->decorated->$name;
	}

	// Note to self: ignore phpStorm; we must NOT use a typehint for $interval

	public function __call(string $name, array $arguments = [])
	{
		if (method_exists($this->decorated, $name))
		{
			return call_user_func_array([$this->decorated, $name], $arguments);
		}

		throw new \InvalidArgumentException("Date object does not have a $name method");
	}

	// Note to self: ignore phpStorm; we must NOT use a typehint for $interval

	public function sub($interval)
	{
		// Note to self: ignore phpStorm; we must NOT use a typehint for $interval
		return $this->decorated->sub($interval);
	}

	public function add($interval)
	{
		// Note to self: ignore phpStorm; we must NOT use a typehint for $interval
		return $this->decorated->add($interval);
	}

	public function modify($modify)
	{
		return $this->decorated->modify($modify);
	}

	public function __toString(): string
	{
		return (string) $this->decorated;
	}

	public function dayToString(int $day, bool $abbr = false): string
	{
		return $this->decorated->dayToString($day, $abbr);
	}

	public function calendar(string $format, bool $local = false, bool $translate = true): string
	{
		return $this->decorated->calendar($format, $local, $translate);
	}

	public function format($format, bool $local = false, bool $translate = true): string
	{
		if (($this->decorated instanceof Date) || ($this->decorated instanceof \Joomla\CMS\Date\Date))
		{
			return $this->decorated->format($format, $local, $translate);
		}

		return $this->decorated->format($format);
	}

	public function getOffsetFromGmt(bool $hours = false): float
	{
		return $this->decorated->getOffsetFromGMT($hours);
	}

	public function monthToString(int $month, bool $abbr = false)
	{
		return $this->decorated->monthToString($month, $abbr);
	}

	public function setTimezone($tz): Date
	{
		return $this->decorated->setTimezone($tz);
	}

	public function toISO8601(bool $local = false): string
	{
		return $this->decorated->toISO8601($local);
	}

	public function toSql(bool $local = false, JDatabaseDriver $db = null): string
	{
		return $this->decorated->toSql($local, $db);
	}

	public function toRFC822(bool $local = false): string
	{
		return $this->decorated->toRFC822($local);
	}

	public function toUnix(): int
	{
		return $this->decorated->toUnix();
	}
}
