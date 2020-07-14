<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Admin\View\Alice;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\Backup\Admin\Model\Alice;
use Akeeba\Backup\Admin\Model\Log;
use Exception;
use FOF30\View\DataView\Html as BaseView;

/**
 * View controller for the Backup Now page
 */
class Html extends BaseView
{
	/**
	 * List of log entries to choose from, JHtml compatible
	 *
	 * @var  array
	 */
	public $logs;

	/**
	 * Currently selected log
	 *
	 * @var  string
	 */
	public $log;

	/**
	 * Should I autostart the log analysis? 0/1
	 *
	 * @var  int
	 */
	public $autorun;

	/**
	 * Total number of checks to perform
	 *
	 * @var  int
	 */
	public $totalChecks;

	/**
	 * Number of checks already performed
	 *
	 * @var  int
	 */
	public $doneChecks;

	/**
	 * Description of the current section of tests being run
	 *
	 * @var  string
	 */
	public $currentSection;

	/**
	 * Description of the last check that just finished
	 *
	 * @var  string
	 */
	public $currentCheck;

	/**
	 * Percentage of the process already done (0-100)
	 *
	 * @var  int
	 */
	public $percentage;

	/**
	 * The error ALICE detected
	 *
	 * @var  array
	 */
	public $aliceError;

	/**
	 * The warnings ALICE detected
	 *
	 * @var  array
	 */
	public $aliceWarnings;

	/**
	 * Overall status of the scan: 'success', 'warnings', 'error'
	 *
	 * @var  array
	 */
	public $aliceStatus;

	/**
	 * The exception to report to the user in the 'error' layout.
	 *
	 * @var  Exception
	 */
	public $errorException;

	public function onBeforeMain()
	{
		/** @var Log $logModel */
		$logModel = $this->container->factory->model('Log')->tmpInstance();

		// Get a list of log names
		$this->logs = $logModel->getLogList();
		$this->log  = $this->input->getCmd('log', null);
	}

	public function onBeforeStart()
	{
		$this->onBeforeStep();
	}

	public function onBeforeStep()
	{
		/** @var Alice $model */
		$model                = $this->getModel();
		$this->totalChecks    = $model->getState('totalChecks');
		$this->doneChecks     = $model->getState('doneChecks');
		$this->currentSection = $model->getState('currentSection');
		$this->currentCheck   = $model->getState('currentCheck');
		$this->percentage     = min(100, ceil(100.0 * ($this->doneChecks / max($this->totalChecks, 1))));
	}

	public function onBeforeResult()
	{
		/** @var Alice $model */
		$model               = $this->getModel();
		$this->totalChecks   = $model->getState('totalChecks');
		$this->doneChecks    = $model->getState('doneChecks');
		$this->aliceError    = $model->getState('aliceError');
		$this->aliceWarnings = $model->getState('aliceWarnings');
		$this->aliceStatus   = empty($this->aliceWarnings) ? 'success' : 'warnings';
		$this->aliceStatus   = empty($this->aliceError) ? $this->aliceStatus : 'error';
	}

	public function onBeforeError()
	{
		$this->errorException = $this->container->platform->getSessionVar('aliceException', null, 'akeeba');
	}
}
