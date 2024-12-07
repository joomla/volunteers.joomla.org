<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Core\Domain;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Base\Part;
use Akeeba\Engine\Core\Domain\Finalizer\LocalQuotas;
use Akeeba\Engine\Core\Domain\Finalizer\MailAdministrators;
use Akeeba\Engine\Core\Domain\Finalizer\ObsoleteRecordsQuotas;
use Akeeba\Engine\Core\Domain\Finalizer\PostProcessing;
use Akeeba\Engine\Core\Domain\Finalizer\RemoteQuotas;
use Akeeba\Engine\Core\Domain\Finalizer\RemoveTemporaryFiles;
use Akeeba\Engine\Core\Domain\Finalizer\UpdateFileSizes;
use Akeeba\Engine\Core\Domain\Finalizer\UpdateStatistics;
use Akeeba\Engine\Core\Domain\Finalizer\UploadKickstart;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use DateTime;
use Exception;
use Akeeba\Engine\Psr\Log\LogLevel;

/**
 * Backup finalization domain
 */
final class Finalization extends Part
{
	/** @var array The finalisation actions we have to execute (FIFO queue) */
	private $actionQueue = [];

	/** @var string The current method, shifted from the action queye */
	private $currentActionClass = '';

	private $currentActionObject = null;

	/** @var int How many finalisation steps I have already done */
	private $stepsDone = 0;

	/** @var int How many finalisation steps I have in total */
	private $stepsTotal = 0;

	/** @var int How many finalisation substeps I have already done */
	private $subStepsDone = 0;

	/** @var int How many finalisation substeps I have in total */
	private $subStepsTotal = 0;

	/**
	 * Get the percentage of finalization steps done
	 *
	 * @return  float
	 */
	public function getProgress()
	{
		if ($this->stepsTotal <= 0)
		{
			return 0;
		}

		$overall = $this->stepsDone / $this->stepsTotal;
		$local   = 0;

		if ($this->subStepsTotal > 0)
		{
			$local = $this->subStepsDone / $this->subStepsTotal;
		}

		return $overall + ($local / $this->stepsTotal);
	}

	/**
	 * Relays and logs an exception
	 *
	 * @param   \Throwable  $e         The exception or throwable to log
	 * @param   string      $logLevel  The log level to log it with
	 *
	 * @return  void
	 * @since   9.3.1
	 */
	public function relayException(\Throwable $e, string $logLevel = LogLevel::ERROR): void
	{
		self::logErrorsFromException($e, $logLevel);
	}

	/**
	 * Used by additional handler classes to relay their step to us
	 *
	 * @param   string  $step  The current step
	 */
	public function relayStep($step)
	{
		$this->setStep($step);
	}

	/**
	 * Used by additional handler classes to relay their substep to us
	 *
	 * @param   string  $substep  The current sub-step
	 */
	public function relaySubstep($substep)
	{
		$this->setSubstep($substep);
	}

	/**
	 * Implements the abstract method
	 *
	 * @return void
	 */
	protected function _finalize()
	{
		$this->setState(self::STATE_FINISHED);
	}

	/**
	 * Initialise the finalisation engine
	 */
	protected function _prepare()
	{
		// Make sure the break flag is not set
		$configuration = Factory::getConfiguration();
		$configuration->get('volatile.breakflag', false);

		// Get the quota actions
		$quotaActions = $configuration->get('volatile.core.finalization.quotaActions', null);

		$quotaActions = is_array($quotaActions) ? $quotaActions : [
			LocalQuotas::class,
			RemoteQuotas::class,
			ObsoleteRecordsQuotas::class,
		];

		// Get the default finalization actions
		$defaultActions = array_merge(
			[
				RemoveTemporaryFiles::class,
				UpdateStatistics::class,
				UpdateFileSizes::class,
				PostProcessing::class,
				UploadKickstart::class,
			],
			$quotaActions,
			[
				MailAdministrators::class,
				// Run it a second time to update the backup end time after post-processing, emails, etc
				UpdateStatistics::class,
			]
		);

		// Populate the actions queue, if it's not already set in a subclass
		$this->actionQueue = $this->actionQueue ?: $defaultActions;

		// Apply action queue customisations
		$customQueue       = $configuration->get('volatile.core.finalization.action_queue', null);
		$customQueueBefore = $configuration->get('volatile.core.finalization.action_queue_before', null);
		$customQueueAfter  = $configuration->get('volatile.core.finalization.action_queue_after', null);

		if (is_array($customQueue) && !empty($customQueue))
		{
			Factory::getLog()->debug('Overriding action queue');
			$this->actionQueue = $customQueue;
		}
		else
		{
			if (is_array($customQueueBefore) && !empty($customQueueBefore))
			{
				Factory::getLog()->debug('Adding finalization actions before post-processing');
				$before = array_slice($this->actionQueue, 0, 3);
				$after  = array_slice($this->actionQueue, 3);

				$this->actionQueue = array_merge($before, $customQueueBefore, $after);
			}

			if (is_array($customQueueAfter) && !empty($customQueueAfter))
			{
				Factory::getLog()->debug('Adding finalization actions at the end of the queue');
				$before = array_slice($this->actionQueue, 0, -1);
				$after  = array_slice($this->actionQueue, -1, 1);

				$this->actionQueue = array_merge($before, $customQueueAfter, $after);
			}
		}

		// Log the actions queue
		Factory::getLog()->debug('Finalization action queue: ' . implode(', ', $this->actionQueue));

		// Initialise actions processing
		$this->stepsTotal    = count($this->actionQueue);
		$this->stepsDone     = 0;
		$this->subStepsTotal = 0;
		$this->subStepsDone  = 0;

		// Seed the method
		$this->currentActionClass = array_shift($this->actionQueue);

		// Set ourselves to running state
		$this->setState(self::STATE_RUNNING);
	}

	/**
	 * Implements the abstract method
	 *
	 * @return  void
	 */
	protected function _run()
	{
		$configuration = Factory::getConfiguration();

		if ($this->getState() == self::STATE_POSTRUN)
		{
			return;
		}

		$finished = (empty($this->actionQueue)) && ($this->currentActionClass == '');

		if ($finished)
		{
			$this->setState(self::STATE_POSTRUN);

			return;
		}

		$this->setState(self::STATE_RUNNING);

		$timer = Factory::getTimer();

		// Continue processing while we have still enough time and stuff to do
		while (($timer->getTimeLeft() > 0) && (!$finished) && (!$configuration->get('volatile.breakflag', false)))
		{
			if (empty($this->currentActionObject))
			{
				$className = $this->currentActionClass;

				Factory::getLog()->debug(__CLASS__ . "::_run() Running new finalization object $className");

				$this->currentActionObject = new $className($this);
			}
			else
			{
				Factory::getLog()->debug(__CLASS__ . "::_run() Resuming finalization object $this->currentActionClass");
			}

			$finalizer = $this->currentActionObject;

			if ($finalizer() !== true)
			{
				continue;
			}

			$this->currentActionClass  = '';
			$this->currentActionObject = null;
			$this->stepsDone++;
			$finished = empty($this->actionQueue);

			if ($finished)
			{
				continue;
			}

			$this->currentActionClass = array_shift($this->actionQueue);
			$this->subStepsTotal      = 0;
			$this->subStepsDone       = 0;
		}

		if ($finished)
		{
			$this->setState(self::STATE_POSTRUN);
			$this->setStep('');
			$this->setSubstep('');
		}
	}
}
