<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Alice\Check\Runtimeerrors;

use Akeeba\Alice\Check\Base;
use Exception;
use Joomla\CMS\Language\Text as JText;

/**
 * Checks that every page load is not hitting the timeout limit.
 * Time diff is performed against the "Start step" and "Saving Kettenrad" timestamps.
 *
 * TODO This needs to be rewritten. It makes no sense. A backup CAN NOT POSSIBLY take longer than PHP's time limit!
 */
class Timeout extends Base
{
	public function __construct($logFile = null)
	{
		$this->priority         = 20;
		$this->checkLanguageKey = 'COM_AKEEBA_ALICE_ANALYZE_RUNTIME_ERRORS_TIMEOUT';

		parent::__construct($logFile);
	}

	public function check()
	{
		$starting = [];
		$saving   = [];
		$isCli    = false;

		$this->scanLines(function ($data) use (&$starting, &$saving, &$isCli) {
			if (preg_match('/PHP SAPI\s{1,}:\s*cli/', $data) == 1)
			{
				// This is CLI backup.
				$isCli = true;
			}

			preg_match_all('#(\d{6}\s\d{2}:\d{2}:\d{2})\|.*?Starting Step number#i', $data, $tmp_matches);

			if (isset($tmp_matches[1]))
			{
				$starting = array_merge($starting, $tmp_matches[1]);
			}

			preg_match_all('#(\d{6}\s\d{2}:\d{2}:\d{2})\|.*?Finished Step number#i', $data, $tmp_matches);

			if (isset($tmp_matches[1]))
			{
				$saving = array_merge($saving, $tmp_matches[1]);
			}
		});

		// If there is an issue with starting and saving instances, I can't go on, first of all fix that
		if (count($saving) != count($starting))
		{
			$this->setResult(-1);
			$this->setErrorLanguageKey([
				'COM_AKEEBA_ALICE_ANALYZE_RUNTIME_ERRORS_TIMEOUT_KETTENRAD_BROKEN',
			]);

			return;
		}

		$temp = [];

		// Let's expand the date part so I can safely work with that strings
		foreach ($starting as $item)
		{
			$temp[] = '20' . substr($item, 0, 2) . '-' . substr($item, 2, 2) . '-' . substr($item, 4, 2) . substr($item, 6);
		}

		$starting = $temp;
		$temp     = [];

		// Let's expand the date part so I can safely work with that strings
		foreach ($saving as $item)
		{
			$temp[] = '20' . substr($item, 0, 2) . '-' . substr($item, 2, 2) . '-' . substr($item, 4, 2) . substr($item, 6);
		}

		$saving       = $temp;
		$maxExecution = $this->detectMaxExec($isCli);

		/**
		 * If I detected a CLI backup without a max execution time limit (THIS IS THE ONLY WAY, PER PHP'S DOCUMENTATION)
		 * I immediately quit since we can't possibly time out.
		 */
		if ($maxExecution == -1)
		{
			return;
		}

		// Ok, did I have any timeout between the start and saving step (ie page loads)?
		for ($i = 0; $i < count($starting); $i++)
		{
			$duration = strtotime($saving[$i]) - strtotime($starting[$i]);

			if ($duration > $maxExecution)
			{
				$this->setResult(-1);
				$this->setErrorLanguageKey([
					'COM_AKEEBA_ALICE_ANALYZE_RUNTIME_ERRORS_TIMEOUT_MAX_EXECUTION', $duration,
				]);

				return;
			}
		}
	}

	public function getSolution()
	{
		return JText::_('COM_AKEEBA_ALICE_ANALYZE_RUNTIME_ERRORS_TIMEOUT_SOLUTION');
	}

	/**
	 * Detects max execution time, reading backup log. If the maximum execution time is set to 0 or it's bigger
	 * than 100, it gets the default value of 100.
	 *
	 * @return int
	 * @throws Exception
	 */
	private function detectMaxExec($isCli = false)
	{
		$time = 0;

		$this->scanLines(function ($line) use (&$time) {
			$pos = stripos($line, '|Max. exec. time');

			if ($pos === false)
			{
				return;
			}

			$time = (int) trim(substr($line, strpos($line, ':', $pos) + 1));
		});

		/**
		 * CLI backups.
		 * Negative, zero or no detected time: we return -1 (no limit).
		 */
		$time = ($time <= 0) ? -1 : $time;

		/**
		 * Over a web server backups.
		 * Negative, zero or no detected time: we consider it to be 100 seconds.
		 * Values over 100 seconds: we cap the to 100 seconds.
		 *
		 * The time limit cap has to do with Apache's internal timeout.
		 */
		$time = ($time <= 0) ? 100 : min($time, 100);

		return $time;
	}
}
