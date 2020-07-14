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
 * Checks if the user is using a too old or too new PHP version
 */
class PHPVersion extends Base
{
	public function __construct($logFile = null)
	{
		$this->priority         = 10;
		$this->checkLanguageKey = 'COM_AKEEBA_ALICE_ANALYZE_REQUIREMENTS_PHP_VERSION';

		parent::__construct($logFile);
	}

	public function check()
	{
		$this->scanLines(function ($line) {
			$pos = strpos($line, '|PHP Version');

			if ($pos === false)
			{
				return;
			}

			$version = trim(substr($line, strpos($line, ':', $pos) + 1));

			// PHP too old (well, this should never happen)
			if (version_compare($version, '5.6', 'lt'))
			{
				$this->setResult(-1);
				$this->setErrorLanguageKey([
					'COM_AKEEBA_ALICE_ANALYZE_REQUIREMENTS_PHP_VERSION_ERR_TOO_OLD',
				]);
			}

			throw new StopScanningEarly();
		});
	}

	public function getSolution()
	{
		return JText::_('COM_AKEEBA_ALICE_ANALYZE_REQUIREMENTS_PHP_VERSION_SOLUTION');
	}
}
