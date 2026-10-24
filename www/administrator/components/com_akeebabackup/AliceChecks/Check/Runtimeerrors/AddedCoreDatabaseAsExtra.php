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
use Joomla\CMS\Language\Text as JText;
use Joomla\Database\DatabaseInterface;

/**
 * Check if the user added the site database as additional database. Some servers won't allow more than one connection
 * to the same database, causing the backup process to fail
 */
class AddedCoreDatabaseAsExtra extends Base
{
	public function __construct(string $logFile, DatabaseInterface $dbo)
	{
		$this->priority         = 100;
		$this->checkLanguageKey = 'COM_AKEEBABACKUP_ALICE_ANALYZE_RUNTIME_ERRORS_DBADD_JSAME';

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

		$jdb = [
			'driver'   => $this->app->get('dbtype'),
			'host'     => $this->app->get('host'),
			'username' => $this->app->get('user'),
			'password' => $this->app->get('password'),
			'database' => $this->app->get('db'),
		];

		foreach ($multidb as $addDb)
		{
			$options = [
				'driver'   => $addDb['driver'],
				'host'     => $addDb['host'],
				'username' => $addDb['username'],
				'password' => $addDb['password'],
				'database' => $addDb['database'],
			];

			// It's the same database used by Joomla, this could led to errors
			if ($jdb == $options)
			{
				$error = true;
			}
		}

		// If needed set the old profile again
		if ($cur_profile != $profile)
		{
			$this->session->set('akeebabackup.profile', $cur_profile);
		}

		if (!$error)
		{
			return;
		}

		$this->setResult(-1);
		$this->setErrorLanguageKey([
			'COM_AKEEBABACKUP_ALICE_ANALYZE_RUNTIME_ERRORS_DBADD_JSAME_ERROR',
		]);
	}

	public function getSolution()
	{
		// Test skipped? No need to provide a solution
		if ($this->getResult() === 0)
		{
			return '';
		}

		return JText::_('COM_AKEEBABACKUP_ALICE_ANALYZE_RUNTIME_ERRORS_DBADD_JSAME_SOLUTION');
	}
}
