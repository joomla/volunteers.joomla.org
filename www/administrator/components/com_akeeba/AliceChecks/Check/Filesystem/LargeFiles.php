<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Alice\Check\Filesystem;

use Akeeba\Alice\Check\Base;
use Joomla\CMS\Language\Text;

/**
 * Checks if the user is trying to backup too big files
 */
class LargeFiles extends Base
{
	public function __construct($logFile = null)
	{
		$this->priority         = 20;
		$this->checkLanguageKey = 'COM_AKEEBA_ALICE_ANALYZE_FILESYSTEM_LARGE_FILES';

		parent::__construct($logFile);
	}

	public function check()
	{
		$bigfiles = [];

		$this->scanLines(function ($data) use (&$bigfiles) {
			preg_match_all('#(_before_|\*after\*) large file: (<root>.*?) \- size: (\d+)#i', $data, $tmp_matches);

			// Record valid matches only (i.e. with a filesize)
			if (!isset($tmp_matches[3]) || empty($tmp_matches[3]))
			{
				return;
			}

			for ($i = 0; $i < count($tmp_matches[2]); $i++)
			{
				// Get flagged files only once; I could have a breaking step after, before or BOTH a large file
				$key = md5($tmp_matches[2][$i]);

				if (!isset($bigfiles[$key]))
				{
					$bigfiles[$key] = [
						'filename' => $tmp_matches[2][$i],
						'size'     => round($tmp_matches[3][$i] / 1024 / 1024, 2),
					];
				}
			}
		});

		if (empty($bigfiles))
		{
			return;
		}

		/**
		 * Depending on the size of the detected files this could be a success, warning or error condition.
		 *
		 * Files over 10MB : error
		 * Files 2 to 10MB : warning
		 * Files < 2MB     : success (user not warned)
		 */
		foreach ($bigfiles as $file)
		{
			// More than 10 Mb? Always set the result to error, no matter what
			if ($file['size'] >= 10)
			{
				$this->setResult(-1);

				break;
			}

			// Warning for "smaller" files, set the warn only if we don't already have a failure state
			if ($file['size'] > 2)
			{
				$this->setResult(0);
			}
		}

		// If all files were too small to report just go away.
		if ($this->getResult() == 1)
		{
			return;
		}

		$errorMsg = [];

		foreach ($bigfiles as $bad)
		{
			$errorMsg[] = 'File: ' . $bad['filename'] . ' ' . $bad['size'] . ' Mb';
		}

		$this->setErrorLanguageKey([
			'COM_AKEEBA_ALICE_ANALYZE_FILESYSTEM_LARGE_FILES_ERROR', implode("\n", $errorMsg),
		]);
	}

	public function getSolution()
	{
		return Text::_('COM_AKEEBA_ALICE_ANALYZE_FILESYSTEM_LARGE_FILES_SOLUTION');
	}
}
