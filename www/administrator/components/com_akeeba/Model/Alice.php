<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Admin\Model;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\Alice\Check\Base;
use Akeeba\Engine\Factory;
use DirectoryIterator;
use FOF30\Autoloader\Autoloader;
use FOF30\Container\Container;
use FOF30\Model\Model;
use FOF30\Timer\Timer;
use Joomla\CMS\Language\Text;

/**
 * ALICE model, the log analuzer
 */
class Alice extends Model
{
	public function __construct(Container $container, array $config = [])
	{
		parent::__construct($container, $config);

		// Register ALICE classes in the FOF autoloader
		$autoloader = Autoloader::getInstance();
		$aliceRoot  = $container->backEndPath . '/AliceChecks';
		$autoloader->addMap('Akeeba\\Alice\\', $aliceRoot);
	}

	/**
	 * Resets the ALICE engine.
	 *
	 * Stores the absolute filesystem path to the log being analyzed and the list of checks to perform in the model
	 * state. Moreover, it resets the list of errors and warnings. This information persists in the user session.
	 *
	 * @param   string  $log  The tag of the log being analyzed
	 */
	public function reset($log)
	{
		// Absolute filesystem path to the log being analyzed
		$logFile = Factory::getLog()->getLogFilename($log);

		if (!@is_file($logFile) && @file_exists(substr($logFile, 0, -4)))
		{
			/**
			 * Transitional period: the log file akeeba.tag.log.php may not exist but the akeeba.tag.log does. This
			 * addresses this transition.
			 */
			$logFile = substr($logFile, 0, -4);
		}

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
		// Reset progress information

		// Reset errors and warnings
		$this->setState('aliceError', []);
		$this->setState('aliceWarnings', []);
		$this->container->platform->setSessionVar('aliceException', null, 'akeeba');
	}

	public function analyze(Timer $timer)
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

			$this->setState('currentSection', Text::_('COM_AKEEBA_ALICE_ANALYZE_' . $currentSection));

			// Get and run the next check
			$checkClass = array_shift($checks[$currentSection]);

			$this->setState('checks', $checks);

			/** @var Base $check */
			$check = new $checkClass($logPath);
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

	/**
	 * Returns an array of fully qualified class names for the checks for a given section.
	 *
	 * The checks are ordered by their priority ascending. A list of classnames is returned.
	 *
	 * @param   string  $section  The section you want to get the checks for
	 *
	 * @return  string[]
	 */
	private function getChecksFor($section)
	{
		$path      = $this->container->backEndPath . '/AliceChecks/Check/' . $section;
		$namespace = 'Akeeba\\Alice\\Check\\' . $section;
		$checks    = [];

		$di = new DirectoryIterator($path);

		foreach ($di as $file)
		{
			if (!$di->isFile())
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
			$checkObject        = new $classname('');
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
	private function countChecks(array $checks)
	{
		$count = 0;

		foreach ($checks as $section => $sectionChecks)
		{
			$count += count($sectionChecks);
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
	private function translate(array $errorDefinition)
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
