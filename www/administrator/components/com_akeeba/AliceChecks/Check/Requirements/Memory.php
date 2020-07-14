<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Alice\Check\Requirements;

use Akeeba\Alice\Check\Base;
use Akeeba\Alice\Exception\StopScanningEarly;
use Joomla\CMS\Language\Text as JText;

/**
 * Checks if we have enough memory to perform backup; at least 16Mb
 */
class Memory extends Base
{
	public function __construct($logFile = null)
	{
		$this->priority         = 30;
		$this->checkLanguageKey = 'COM_AKEEBA_ALICE_ANALYZE_REQUIREMENTS_MEMORY';

		parent::__construct($logFile);
	}

	public function check()
	{
		$limit = null;
		$usage = false;

		$this->scanLines(function ($line) use (&$limit, &$usage) {
			if (is_null($limit))
			{
				$pos = strpos($line, '|Memory limit');

				if ($pos !== false)
				{
					$limit = trim(substr($line, strpos($line, ':', $pos) + 1));
					$limit = str_ireplace('M', '', $limit);

					// Convert to integer for better handling and checks
					$limit = (int) $limit;
				}
			}

			if (!$usage)
			{
				$pos = strpos($line, '|Current mem. usage');

				if ($pos !== false)
				{
					$usage = trim(substr($line, strpos($line, ':', $pos) + 1));
					// Converting to Mb for better handling
					$usage = round($usage / 1024 / 1024, 2);
				}
			}

			throw new StopScanningEarly();
		});

		if (empty($limit) || empty($usage))
		{
			// Inconclusive check. Cannot get the memory information.
			return;
		}

		$available = $limit - $usage;

		if ($limit < 0)
		{
			// Stupid host uses a negative memory limit. This is the same as setting no memory limit. Bleh.
			return;
		}

		if ($available >= 16)
		{
			// We have enough memory.
			return;
		}

		$this->setResult(-1);
		$this->setErrorLanguageKey(['COM_AKEEBA_ALICE_ANALYZE_REQUIREMENTS_MEMORY_TOO_FEW', $available]);
	}

	public function getSolution()
	{
		return JText::_('COM_AKEEBA_ALICE_ANALYZE_REQUIREMENTS_MEMORY_SOLUTION');
	}
}
