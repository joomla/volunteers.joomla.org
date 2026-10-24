<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Mixin;

defined('_JEXEC') || die();

use DateTimeZone;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

trait ViewBackupStartTimeTrait
{
	/**
	 * Should I use the user's local time zone for display?
	 *
	 * @var  boolean
	 */
	public $useLocalTime;

	/**
	 * Time format string to use for the time zone suffix
	 *
	 * @var  string
	 */
	public $timeZoneFormat;

	/**
	 * Date format for the backup start time
	 *
	 * @var  string
	 */
	public $dateFormat = '';

	protected function initTimeInformation()
	{
		$cParams = ComponentHelper::getParams('com_akeebabackup');

		// Date format
		$dateFormat       = $cParams->get('dateformat', '');
		$dateFormat       = trim($dateFormat);
		$this->dateFormat = !empty($dateFormat) ? $dateFormat : Text::_('DATE_FORMAT_LC4');

		// Time zone options
		$this->useLocalTime   = $cParams->get('localtime', '1') == 1;
		$this->timeZoneFormat = $cParams->get('timezonetext', 'T');

	}

	/**
	 * Get the start time and duration of a backup record
	 *
	 * @param   array  $record  A backup record
	 *
	 * @return  array  array(startTimeAsString, durationAsString)
	 */
	protected function getTimeInformation($record)
	{
		$utcTimeZone = new DateTimeZone('UTC');
		$startTime   = clone Factory::getDate($record['backupstart'], $utcTimeZone);
		$endTime     = clone Factory::getDate($record['backupend'], $utcTimeZone);

		$duration = $endTime->toUnix() - $startTime->toUnix();

		if ($duration > 0)
		{
			$seconds  = $duration % 60;
			$duration = $duration - $seconds;

			$minutes  = ($duration % 3600) / 60;
			$duration = $duration - $minutes * 60;

			$hours    = $duration / 3600;
			$duration = sprintf('%02d', $hours) . ':' . sprintf('%02d', $minutes) . ':' . sprintf('%02d', $seconds);
		}
		else
		{
			$duration = '';
		}

		$user   = Factory::getApplication()->getIdentity();
		$userTZ = $user->getParam('timezone', 'UTC');
		$tz     = new DateTimeZone($userTZ);
		$startTime->setTimezone($tz);

		$timeZoneSuffix = '';

		if (!empty($this->timeZoneFormat))
		{
			$timeZoneSuffix = $startTime->format($this->timeZoneFormat, $this->useLocalTime);
		}

		return [
			$startTime->format($this->dateFormat, $this->useLocalTime),
			$duration,
			$timeZoneSuffix,
		];
	}

}