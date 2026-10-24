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
use Akeeba\Engine\Factory;
use Exception;
use Joomla\CMS\Language\Text as JText;
use Joomla\Database\DatabaseInterface;

/**
 * Check if the user add one or more additional database, but the connection details are wrong
 * In such cases Akeeba Backup will receive an error, halting the whole backup process
 */
class ExtraDatabaseCannotConnect extends Base
{
	public function __construct(string $logFile, DatabaseInterface $dbo)
	{
		$this->priority         = 90;
		$this->checkLanguageKey = 'COM_AKEEBABACKUP_ALICE_ANALYZE_RUNTIME_ERRORS_DBADD_WRONG';

		parent::__construct($logFile, $dbo);
	}

	public function check()
	{
		$profile = 0;

		$this->scanLines(function ($line) use (&$profile) {
			$pos = strpos($line, '|Loaded profile');

			if ($pos === false)
			{
				return;
			}

			preg_match('/profile\s+#(\d+)/', $line, $matches);

			if (isset($matches[1]))
			{
				$profile = (int) $matches[1];
			}

			throw new StopScanningEarly();
		});

		// Mhm... no profile ID? Something weird happened better stop here and mark the test as skipped
		if ($profile <= 0)
		{
			return;
		}

		// Do I have to switch profile?
		$cur_profile = $this->session->get('akeebabackup.profile', null);

		if ($cur_profile != $profile)
		{
			$this->session->set('akeebabackup.profile', $profile);
		}

		$error   = false;
		$filters = Factory::getFilters();
		$multidb = $filters->getFilterData('multidb');

		foreach ($multidb as $addDb)
		{
			$options = [
				'driver'   => $addDb['driver'],
				'host'     => $addDb['host'],
				'port'     => $addDb['port'],
				'user'     => $addDb['username'],
				'password' => $addDb['password'],
				'database' => $addDb['database'],
				'prefix'   => $addDb['prefix'],
			];

			try
			{
				$db = $this->dbo;
				$db->connect();
				$db->disconnect();
			}
			catch (Exception $e)
			{
				$error = true;
			}
		}

		// If needed set the old profile again
		if ($cur_profile != $profile)
		{
			$this->session->set('akeebabackup.profile', $cur_profile);
		}

		if ($error)
		{
			$this->setResult(-1);
			$this->setErrorLanguageKey([
				'COM_AKEEBABACKUP_ALICE_ANALYZE_RUNTIME_ERRORS_DBADD_WRONG_ERROR',
			]);
		}
	}

	public function getSolution()
	{
		// Test skipped? No need to provide a solution
		if ($this->getResult() === 0)
		{
			return '';
		}

		return JText::_('COM_AKEEBABACKUP_ALICE_ANALYZE_RUNTIME_ERRORS_DBADD_WRONG_SOLUTION');
	}
}
