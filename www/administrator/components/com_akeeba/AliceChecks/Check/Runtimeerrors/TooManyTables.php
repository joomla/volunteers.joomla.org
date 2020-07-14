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
 * Checks if the user is trying to backup too many databases, causing the system to fail
 */
class TooManyTables extends Base
{
	public function __construct($logFile = null)
	{
		$this->priority         = 40;
		$this->checkLanguageKey = 'COM_AKEEBA_ALICE_ANALYZE_RUNTIME_ERRORS_TOOMANYDBS';

		parent::__construct($logFile);
	}

	public function check()
	{
		$tables    = [];
		$ex_tables = [];

		$this->scanLines(function ($data) use (&$tables, &$ex_tables) {
			// Let's save every scanned table
			preg_match_all('#Native\\[a-zA-Z]* :: Adding.*?\(internal name (.*?)\)#i', $data, $matches);

			if (!isset($matches[1]) || empty($matches[1]))
			{
				return;
			}

			$tables = array_merge($tables, $matches[1]);
		});

		if (empty($tables))
		{
			return;
		}

		// Let's loop on saved tables and look at their prefixes
		foreach ($tables as $table)
		{
			preg_match('/^(.*?_)/', $table, $matches);

			if ($matches[1] !== '#_' && !in_array($matches[1], $ex_tables))
			{
				$ex_tables[] = $matches[1];
			}
		}

		if (!count($ex_tables))
		{
			return;
		}

		$this->setResult(-1);

		if (count($ex_tables) > 0 && count($ex_tables) <= 3)
		{
			$this->setResult(0);
		}

		$this->setErrorLanguageKey([
			'COM_AKEEBA_ALICE_ANALYZE_RUNTIME_ERRORS_TOOMANYDBS_ERROR',
		]);
	}

	public function getSolution()
	{
		return JText::_('COM_AKEEBA_ALICE_ANALYZE_RUNTIME_ERRORS_TOOMANYDBS_SOLUTION');
	}
}
