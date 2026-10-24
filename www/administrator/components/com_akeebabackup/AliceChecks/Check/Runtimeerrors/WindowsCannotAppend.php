<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Alice\Check\Runtimeerrors;

defined('_JEXEC') || die();

use Akeeba\Alice\Check\Base;
use Akeeba\Alice\Exception\StopScanningEarly;
use Joomla\CMS\Language\Text as JText;
use Joomla\Database\DatabaseInterface;

/**
 * Checks if Akeeba Backup failed to write data inside the archive (WIN hosts only)
 */
class WindowsCannotAppend extends Base
{
	public function __construct(string $logFile, DatabaseInterface $dbo)
	{
		$this->priority         = 30;
		$this->checkLanguageKey = 'COM_AKEEBABACKUP_ALICE_ANALYZE_RUNTIME_ERRORS_WINCANTAPPEND';

		parent::__construct($logFile, $dbo);
	}

	public function check()
	{
		// Customer is not on windows, this problem happened on Windows only
		if (!$this->isWin())
		{
			return;
		}

		$this->scanLines(function ($data) {
			if (preg_match('#Could not open archive file.*? for append#i', $data))
			{
				$this->setResult(-1);
				$this->setErrorLanguageKey([
					'COM_AKEEBABACKUP_ALICE_ANALYZE_RUNTIME_ERRORS_WINCANTAPPEND_ERROR',
				]);

				throw new StopScanningEarly();
			}
		});
	}

	public function getSolution()
	{
		return JText::_('COM_AKEEBABACKUP_ALICE_ANALYZE_RUNTIME_ERRORS_WINCANTAPPEND_SOLUTION');
	}

	private function isWin()
	{
		$OS = '';

		$this->scanLines(function ($line) use (&$OS) {
			$pos = stripos($line, '|OS Version');

			if ($pos !== false)
			{
				$OS = trim(substr($line, strpos($line, ':', $pos) + 1));

				throw new StopScanningEarly();
			}
		});

		if (stripos($OS, 'windows') !== false)
		{
			return true;
		}

		return false;
	}
}
