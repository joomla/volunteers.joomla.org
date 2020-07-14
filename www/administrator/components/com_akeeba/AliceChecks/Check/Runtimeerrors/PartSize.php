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
 * Checks if the user is post processing the archive but didn't set any part size.
 * Most likely this could lead to timeouts while uploading
 */
class PartSize extends Base
{
	public function __construct($logFile = null)
	{
		$this->priority         = 70;
		$this->checkLanguageKey = 'COM_AKEEBA_ALICE_ANALYZE_RUNTIME_ERRORS_PART_SIZE';

		parent::__construct($logFile);
	}

	public function check()
	{
		$partsize = 0;
		$postproc = '';

		$this->scanLines(function ($data) use (&$partsize, &$postproc) {
			if (empty($partsize))
			{
				preg_match('#\|Part size.*:(\d+)#i', $data, $match);

				if (isset($match[1]))
				{
					$partsize = $match[1];
				}
			}

			if (empty($postproc))
			{
				preg_match('#Loading.*post-processing.*?\((.*?)\)#i', $data, $match);

				if (isset($match[1]))
				{
					$postproc = trim($match[1]);
				}
			}

			// Wait until I have both pieces of data
			if (empty($partsize) || empty($postproc))
			{
				return;
			}

			if (($partsize > 2000000000) && ($postproc != 'none'))
			{
				$this->setResult(0);
				$this->setErrorLanguageKey([
					'COM_AKEEBA_ALICE_ANALYZE_RUNTIME_ERRORS_PART_SIZE_ERROR',
				]);
			}

			throw new StopScanningEarly();
		});
	}

	public function getSolution()
	{
		return JText::_('COM_AKEEBA_ALICE_ANALYZE_RUNTIME_ERRORS_PART_SIZE_SOLUTION');
	}
}
