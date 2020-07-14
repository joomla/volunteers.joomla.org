<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Alice\Check\Runtimeerrors;

use Akeeba\Alice\Check\Base;
use Akeeba\Alice\Exception\StopScanningEarly;
use Akeeba\Engine\Factory;
use Exception;
use FOF30\Container\Container;
use JDatabaseDriver;
use Joomla\CMS\Language\Text as JText;

/**
 * Check if the user add one or more additional database, but the connection details are wrong
 * In such cases Akeeba Backup will receive an error, halting the whole backup process
 */
class ExtraDatabaseCannotConnect extends Base
{
	public function __construct($logFile = null)
	{
		$this->priority         = 90;
		$this->checkLanguageKey = 'COM_AKEEBA_ALICE_ANALYZE_RUNTIME_ERRORS_DBADD_WRONG';

		parent::__construct($logFile);
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
		$container   = Container::getInstance('com_akeeba');
		$cur_profile = $container->platform->getSessionVar('profile', null, 'akeeba');

		if ($cur_profile != $profile)
		{
			$container->platform->setSessionVar('profile', $profile, 'akeeba');
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
				$db = JDatabaseDriver::getInstance($options);
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
			$container->platform->setSessionVar('profile', $cur_profile, 'akeeba');
		}

		if ($error)
		{
			$this->setResult(-1);
			$this->setErrorLanguageKey([
				'COM_AKEEBA_ALICE_ANALYZE_RUNTIME_ERRORS_DBADD_WRONG_ERROR',
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

		return JText::_('COM_AKEEBA_ALICE_ANALYZE_RUNTIME_ERRORS_DBADD_WRONG_SOLUTION');
	}
}
