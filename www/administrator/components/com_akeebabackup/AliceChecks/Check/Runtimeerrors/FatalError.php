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
 * Checks if a fatal error occurred during the backup process
 */
class FatalError extends Base
{
	public function __construct(string $logFile, DatabaseInterface $dbo)
	{
		$this->priority         = 110;
		$this->checkLanguageKey = 'COM_AKEEBABACKUP_ALICE_ANALYZE_RUNTIME_ERRORS_FATALERROR';

		parent::__construct($logFile, $dbo);
	}

	public function check()
	{
		$this->scanLines(function ($data) {
			preg_match('#ERROR   \|.*?\|(.*)#', $data, $tmp_matches);

			if (!isset($tmp_matches[1]))
			{
				return;
			}

			$error = $tmp_matches[1];

			if (empty($error))
			{
				return;
			}

			$this->setResult(-1);
			$this->setErrorLanguageKey(['COM_AKEEBABACKUP_ALICE_ANALYZE_RUNTIME_ERRORS_FATALERROR_ERROR', $error]);

			throw new StopScanningEarly();
		});
	}

	public function getSolution()
	{
		return JText::_('COM_AKEEBABACKUP_ALICE_ANALYZE_RUNTIME_ERRORS_FATALERROR_SOLUTION');
	}
}
