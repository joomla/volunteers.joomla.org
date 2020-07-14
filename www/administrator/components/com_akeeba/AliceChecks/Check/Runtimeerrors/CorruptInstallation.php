<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Alice\Check\Runtimeerrors;

use Akeeba\Alice\Check\Base;
use Akeeba\Alice\Exception\StopScanningEarly;
use Joomla\CMS\Language\Text as JText;

/**
 * Checks if the user is using a too old or too new PHP version
 */
class CorruptInstallation extends Base
{
	public function __construct($logFile = null)
	{
		$this->priority         = 60;
		$this->checkLanguageKey = 'COM_AKEEBA_ALICE_ANALYZE_RUNTIME_ERRORS_CORRUPTED_INSTALL';

		parent::__construct($logFile);
	}

	public function check()
	{
		$error              = false;
		$foundLoadedProfile = false;

		$this->scanLines(function ($line) use (&$foundLoadedProfile) {
			// First we need to find the "Loaded profile" line
			if (!$foundLoadedProfile)
			{
				$pos = strpos($line, '|Loaded profile');

				if ($pos !== false)
				{
					// Mark the line as found. We are interested in the line AFTER this one.
					$foundLoadedProfile = true;
				}

				// Since at this point we are not past the "Loaded profile" we need to keep parsing the log file.
				return;
			}

			// Ok, we are just past the "Loaded profile" line. Let's see if it's a broken install.
			$logline = trim(substr($line, 24));

			// If it's not an empty line then it is definitely not a broken install
			if ($logline != '|')
			{
				throw new StopScanningEarly();
			}

			// Empty line?? Most likely it's a broken install
			$this->setResult(-1);
			$this->setErrorLanguageKey([
				'COM_AKEEBA_ALICE_ANALYZE_RUNTIME_ERRORS_CORRUPTED_INSTALL_ERROR',
			]);

			throw new StopScanningEarly();
		});
	}

	public function getSolution()
	{
		return JText::_('COM_AKEEBA_ALICE_ANALYZE_RUNTIME_ERRORS_CORRUPTED_INSTALL_SOLUTION');
	}
}
