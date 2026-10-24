<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\Alice\Check\Base;
use Akeeba\Component\AkeebaBackup\Administrator\Helper\Utils;
use Akeeba\Engine\Core\Timer;
use Akeeba\Engine\Factory;
use DirectoryIterator;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

#[\AllowDynamicProperties]
class AliceModel extends BaseDatabaseModel
{
	protected static $stateVars = [
		'log', 'logToAnalyze', 'checks', 'totalChecks', 'doneChecks', 'currentSection', 'currentCheck', 'aliceError',
		'aliceWarnings', 'aliceException',
	];

	/**
	 * Resets the ALICE engine.
	 *
	 * Stores the absolute filesystem path to the log being analyzed and the list of checks to perform in the model
	 * state. Moreover, it resets the list of errors and warnings. This information persists in the user session.
	 *
	 * @param   string  $log  The tag of the log being analyzed
	 */
	public function reset(string $log)
	{
		/**
		 * Do not remove. This is required to run populateState once before we reset the session. Otherwise no state
		 * variable will be saved in the session.
		 */
		$this->getState();

		// Absolute filesystem path to the log being analyzed
		$logFile = Utils::getLogFilePath($log) ?: Factory::getLog()->getLogFilename($log);

		$this->setState('log', $log);
		$this->setState('logToAnalyze', $logFile);
		// List of checks we need to run
		$checks = [
			'Requirements'  => $this->getChecksFor('Requirements'),
			'Filesystem'    => $this->getChecksFor('Filesystem'),
			'Runtimeerrors' => $this->getChecksFor('Runtimeerrors'),
		];
		$this->setState('checks', $checks);
		$this->setState('totalChecks', $this->countChecks($checks));
		$this->setState('doneChecks', 0);
		$this->setState('currentSection', '');
		$this->setState('currentCheck', '');
		$this->setState('aliceError', []);
		$this->setState('aliceWarnings', []);
		$this->setState('aliceException', null);
	}

	public function analyze(Timer $timer): bool
	{
		$logPath      = $this->getState('logToAnalyze');
		$requiredTime = 0.5;

		while ($timer->getTimeLeft() > $requiredTime)
		{
			// Mark the start of timing this check
			$start = microtime(true);

			/** @var array $checks */
			$checks = $this->getState('checks', []);

			// Are we all done?
			if (empty($checks))
			{
				return true;
			}

			$sections       = array_keys($checks);
			$currentSection = $sections[0];
			$this->setState('section', $currentSection);

			// All checks for this section are done. Go to the next section.
			if (empty($checks[$currentSection]))
			{
				unset($checks[$currentSection]);

				$this->setState('checks', $checks);

				continue;
			}

			$this->setState('currentSection', Text::_('COM_AKEEBABACKUP_ALICE_ANALYZE_' . $currentSection));

			// Get and run the next check
			$checkClass = array_shift($checks[$currentSection]);

			$this->setState('checks', $checks);

			/** @var Base $check */
			$check = new $checkClass($logPath, $this->getDatabase());
			$this->setState('currentCheck', Text::_($check->getCheckLanguageKey()));
			$this->setState('doneChecks', $this->getState('doneChecks') + 1);

			$check->check();

			switch ($check->getResult())
			{
				case 1:
					// Success. No action.
					break;

				case 0:
					// Warning.
					$warnings   = $this->getState('aliceWarnings', []);
					$warnings[] = [
						'message'  => $this->translate($check->getErrorLanguageKey()),
						'solution' => $check->getSolution(),
					];
					$this->setState('aliceWarnings', $warnings);
					break;

				case -1:
					// Error. Set the state and return immediately.
					$this->setState('aliceError', [
						'message'  => $this->translate($check->getErrorLanguageKey()),
						'solution' => $check->getSolution(),
					]);

					return true;
					break;
			}

			// Mark the end of timing this step
			$end          = microtime(true);
			$requiredTime = max($requiredTime, $end - $start);
		}

		return false;
	}

	public function saveStateToSession()
	{
		/** @var CMSApplication $app */
		$app     = JoomlaFactory::getApplication();
		$session = $app->getSession();

		foreach (self::$stateVars as $stateVar)
		{
			$v = $this->getState($stateVar);

			if (is_null($v))
			{
				$session->remove('akeebabackup.' . $stateVar);

				continue;
			}

			$session->set('akeebabackup.' . $stateVar, $v);
		}
	}

	protected function populateState()
	{
		/** @var CMSApplication $app */
		$app     = JoomlaFactory::getApplication();
		$session = $app->getSession();

		foreach (self::$stateVars as $stateVar)
		{
			$this->setState($stateVar, $session->get('akeebabackup.' . $stateVar));
		}
	}

	/**
	 * Returns an array of fully qualified class names for the checks for a given section.
	 *
	 * The checks are ordered by their priority ascending. A list of classnames is returned.
	 *
	 * @param   string  $section  The section you want to get the checks for
	 *
	 * @return  string[]
	 */
	private function getChecksFor(string $section): array
	{
		$path      = JPATH_ADMINISTRATOR . '/components/com_akeebabackup/AliceChecks/Check/' . $section;
		$namespace = 'Akeeba\\Alice\\Check\\' . $section;
		$checks    = [];

		$di = new DirectoryIterator($path);

		foreach ($di as $file)
		{
			if ($di->isDot() || !$di->isFile())
			{
				continue;
			}

			if ($di->getExtension() != 'php')
			{
				continue;
			}

			$basename  = $di->getBasename('.php');
			$classname = $namespace . '\\' . $basename;

			if (!class_exists($classname))
			{
				continue;
			}

			/** @var Base $checkObject */
			$checkObject        = new $classname('', $this->getDatabase());
			$checks[$classname] = $checkObject->getPriority();
		}

		asort($checks);

		return array_keys($checks);
	}

	/**
	 * Produces the total count of checks to perform
	 *
	 * @param   array  $checks  The list of checks to perform
	 *
	 * @return  int  How many checks there are
	 */
	private function countChecks(array $checks): int
	{
		$count = 0;

		foreach ($checks as $section => $sectionChecks)
		{
			$count += is_array($sectionChecks) || $sectionChecks instanceof \Countable ? count($sectionChecks) : 0;
		}

		return $count;
	}

	/**
	 * Translate an error definition.
	 *
	 * The error definition is an array. The first item (position 0) is the language key. Any further items are
	 * sprintf() parameters.
	 *
	 * @param   array  $errorDefinition
	 *
	 * @return  string
	 */
	private function translate(array $errorDefinition): string
	{
		if (empty($errorDefinition))
		{
			return '';
		}

		if (count($errorDefinition) == 1)
		{
			return nl2br(Text::_($errorDefinition[0]));
		}

		return nl2br(call_user_func_array([Text::class, 'sprintf'], $errorDefinition));
	}

}