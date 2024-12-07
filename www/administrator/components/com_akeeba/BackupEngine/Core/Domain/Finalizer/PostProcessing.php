<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * @package     Akeeba\Engine\Core\Domain\Finalizer
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Akeeba\Engine\Core\Domain\Finalizer;

use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Akeeba\Engine\Postproc\Base;
use Akeeba\Engine\Postproc\PostProcInterface;
use Exception;
use Akeeba\Engine\Psr\Log\LogLevel;

/**
 * Performs any necessary post-processing (remote file uploading) still pending.
 *
 * @since       9.3.1
 * @package     Akeeba\Engine\Core\Domain\Finalizer
 *
 */
final class PostProcessing extends AbstractFinalizer
{
	/** @var array A list of all backup parts to process */
	private $backupParts = [];

	/** @var int The backup part we are currently processing */
	private $backupPartsIndex = -1;

	/** @var int How many finalisation substeps I have already done */
	private $subStepsDone = 0;

	/** @var int How many finalisation substeps I have in total */
	private $subStepsTotal = 0;

	/**
	 * @inheritDoc
	 */
	public function __invoke()
	{
		$this->setStep('Post-processing');
		$this->setSubstep('');

		// Do not run if the archive engine doesn't produce archives
		$configuration = Factory::getConfiguration();
		$engineName    = $configuration->get('akeeba.advanced.postproc_engine');

		Factory::getLog()->debug("Loading post-processing engine object ($engineName)");

		$postProcEngine = Factory::getPostprocEngine($engineName);

		if (!is_object($postProcEngine) || !($postProcEngine instanceof Base))
		{
			Factory::getLog()->debug(
				sprintf(
					'Post-processing engine “%s” not found.',
					$engineName
				)
			);
			Factory::getLog()->debug("The post-processing engine has either been removed or you are trying to use a profile created with the Professional version of the backup software in the Core version which doesn't have this post-processing engine.");

			return true;
		}

		// Initialize the archive part list if required
		if (empty($this->backupParts))
		{
			$ret = $this->initialiseBackupParts($postProcEngine);

			if ($ret !== null)
			{
				return $ret;
			}
		}

		// Make sure we don't accidentally break the step when not required to do so
		$configuration->set('volatile.breakflag', false);

		// Do we have a filename from the previous run of the post-proc engine?
		$filename = $configuration->get('volatile.postproc.filename', null);

		if (empty($filename))
		{
			$filename = $this->backupParts[$this->backupPartsIndex];
			Factory::getLog()->info('Beginning post processing file ' . $filename);
		}
		else
		{
			Factory::getLog()->info('Continuing post processing file ' . $filename);
		}

		$this->setStep('Post-processing');
		$this->setSubstep(basename($filename));
		$timer               = Factory::getTimer();
		$startTime           = $timer->getRunningTime();
		$processingException = null;

		try
		{
			$finishedProcessing = $postProcEngine->processPart($filename);
		}
		catch (Exception $e)
		{
			$finishedProcessing  = false;
			$processingException = $e;
		}

		if (!is_null($processingException))
		{
			Factory::getLog()->warning('Failed to process file ' . $filename);
			Factory::getLog()->warning('Error received from the post-processing engine:');
			$this->logErrorsFromException($processingException, LogLevel::WARNING);
		}
		elseif ($finishedProcessing === true)
		{
			// The post-processing of this file ended successfully
			Factory::getLog()->info('Finished post-processing file ' . $filename);
			$configuration->set('volatile.postproc.filename', null);
		}
		else
		{
			// More work required
			Factory::getLog()->info('More post-processing steps required for file ' . $filename);
			$configuration->set('volatile.postproc.filename', $filename);

			// Do we need to break the step?
			$endTime  = $timer->getRunningTime();
			$stepTime = $endTime - $startTime;
			$timeLeft = $timer->getTimeLeft();

			// By default, we assume that we have enough time to run yet another step
			$configuration->set('volatile.breakflag', false);

			/**
			 * However, if the last step took longer than the time we already have left on the timer we can predict
			 * that we are running out of time, therefore we need to break the step.
			 */
			if ($timeLeft < $stepTime)
			{
				$configuration->set('volatile.breakflag', true);
			}
		}

		// Should we delete the file afterwards?
		$canAndShouldDeleteFileAfterwards =
			$configuration->get('engine.postproc.common.delete_after', false)
			&& $postProcEngine->isFileDeletionAfterProcessingAdvisable();

		if ($canAndShouldDeleteFileAfterwards && $finishedProcessing)
		{
			Factory::getLog()->debug('Deleting already processed file ' . $filename);
			Platform::getInstance()->unlink($filename);
		}
		elseif ($canAndShouldDeleteFileAfterwards && !$finishedProcessing)
		{
			Factory::getLog()->debug('Not removing the non-processed file ' . $filename);
		}
		else
		{
			Factory::getLog()->debug('Not removing processed file ' . $filename);
		}

		if ($finishedProcessing === true)
		{
			// Move the index forward if the part finished processing
			$this->backupPartsIndex++;

			// Mark substep done
			$this->subStepsDone++;

			// Break step after processing?
			if (
				$postProcEngine->recommendsBreakAfter()
				&& !Factory::getConfiguration()->get('akeeba.tuning.nobreak.finalization', 0)
			)
			{
				$configuration->set('volatile.breakflag', true);
			}

			// If we just finished processing the first archive part, save its remote path in the statistics.
			if (($this->subStepsDone == 1) || ($this->subStepsTotal == 0))
			{
				$this->updateStatistics($postProcEngine, $engineName);
			}

			// Are we past the end of the array (i.e. we're finished)?
			if ($this->backupPartsIndex >= count($this->backupParts))
			{
				Factory::getLog()->info('Post-processing has finished for all files');

				return true;
			}
		}

		if (!is_null($processingException))
		{
			// If the post-processing failed, make sure we don't process anything else
			$this->backupPartsIndex = count($this->backupParts);
			Factory::getLog()->warning('Post-processing interrupted -- no more files will be transferred');

			return true;
		}

		// Indicate we're not done yet
		return false;
	}

	/**
	 * Update the backup record upon post-processing the first part.
	 *
	 * @param   PostProcInterface  $postProcEngine  The post-processing engine we're using
	 * @param   string             $engineName      The name of the post-processing engine we're using
	 *
	 * @throws  Exception
	 * @since   9.3.1
	 */
	public function updateStatistics(PostProcInterface $postProcEngine, string $engineName): void
	{
		if (empty($postProcEngine->getRemotePath()))
		{
			return;
		}

		$configuration   = Factory::getConfiguration();
		$statistics      = Factory::getStatistics();
		$remote_filename = $engineName . '://';
		$remote_filename .= $postProcEngine->getRemotePath();
		$data            = [
			'remote_filename' => $remote_filename,
		];
		$remove_after    = $configuration->get('engine.postproc.common.delete_after', false);

		if ($remove_after)
		{
			$data['filesexist'] = 0;
		}

		$statistics->setStatistics($data);
	}

	/**
	 * Initialise the backup parts information
	 *
	 * @param   PostProcInterface  $postProcEngine  The post-processing engine we're using
	 *
	 * @return  bool|null
	 *
	 * @since   9.3.1
	 */
	private function initialiseBackupParts(PostProcInterface $postProcEngine): ?bool
	{
		$configuration = Factory::getConfiguration();

		Factory::getLog()->info('Initializing post-processing engine');

		// Initialize the flag for multistep post-processing of parts
		$configuration->set('volatile.postproc.filename', null);
		$configuration->set('volatile.postproc.directory', null);

		// Populate array w/ absolute names of backup parts
		$statistics        = Factory::getStatistics();
		$stat              = $statistics->getRecord();
		$this->backupParts = Factory::getStatistics()->get_all_filenames($stat, false);

		if (is_null($this->backupParts))
		{
			// No archive produced, or they are all already post-processed
			Factory::getLog()->info('No archive files found to post-process');

			return true;
		}

		Factory::getLog()->debug(count($this->backupParts) . ' files to process found');

		$this->subStepsTotal = count($this->backupParts);
		$this->subStepsDone  = 0;

		$this->backupPartsIndex = 0;

		// If we have an empty array, do not run
		if (empty($this->backupParts))
		{
			return true;
		}

		// Break step before processing?
		if (
			$postProcEngine->recommendsBreakBefore()
			&& !$configuration->get(
				'akeeba.tuning.nobreak.finalization', 0
			)
		)
		{
			Factory::getLog()->debug('Breaking step before post-processing run');
			$configuration->set('volatile.breakflag', true);

			return false;
		}

		return null;
	}
}