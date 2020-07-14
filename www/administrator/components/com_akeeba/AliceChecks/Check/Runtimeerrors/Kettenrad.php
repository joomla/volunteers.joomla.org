<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Alice\Check\Runtimeerrors;

use Akeeba\Alice\Check\Base;
use Joomla\CMS\Language\Text as JText;

/**
 * Checks that the Kettenrad instance is not dead; the number of "Starting step" and "Saving Kettenrad" instance
 * must be the same, plus none of the steps could be repeated (except the first one).
 */
class Kettenrad extends Base
{
	public function __construct($logFile = null)
	{
		$this->priority         = 10;
		$this->checkLanguageKey = 'COM_AKEEBA_ALICE_ANALYZE_RUNTIME_ERRORS_KETTENRAD';

		parent::__construct($logFile);
	}

	public function check()
	{
		$starting = [];
		$saving   = [];

		$this->scanLines(function ($data) use (&$starting, &$saving) {
			preg_match_all('#Starting Step number (\d+)#i', $data, $tmp_matches);

			if (isset($tmp_matches[1]))
			{
				$starting = array_merge($starting, $tmp_matches[1]);
			}

			preg_match_all('#Finished Step number (\d+)#i', $data, $tmp_matches);

			if (isset($tmp_matches[1]))
			{
				$saving = array_merge($saving, $tmp_matches[1]);
			}
		});

		/**
		 * Check that none of "Starting step" number is repeated, EXCEPT for the first one (it's ok).
		 * That could happen when some poorly configured server processes the same request twice
		 */
		foreach ($starting as $stepNumber)
		{
			if ($stepNumber == 1)
			{
				continue;
			}

			/**
			 * Did a step run more than once?
			 *
			 * It is OK if it started multiple times but was only logged as finished once. This means it failed and the
			 * user took advantage of our retry-on-error feature for backend backups.
			 *
			 * However, if we see that it was logged as *finished* multiple times then it means that the same step ran
			 * multiple times in parallel. This is where the real problem is.
			 */
			if (count(array_keys($starting, $stepNumber)) > 1)
			{
				if (count(array_keys($saving, $stepNumber)) > 1)
				{
					$this->setResult(-1);
					$this->setErrorLanguageKey([
						'COM_AKEEBA_ALICE_ANALYZE_RUNTIME_ERRORS_KETTENRAD_STARTING_MORE_ONCE', $stepNumber,
					]);

					return;
				}
			}
		}
	}

	public function getSolution()
	{
		return JText::_('COM_AKEEBA_ALICE_ANALYZE_RUNTIME_ERRORS_KETTENRAD_SOLUTION');
	}
}
