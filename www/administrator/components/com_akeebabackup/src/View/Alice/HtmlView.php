<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\View\Alice;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewLoadAnyTemplateTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewTaskBasedEventsTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewToolbarTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Model\AliceModel;
use Akeeba\Component\AkeebaBackup\Administrator\Model\LogModel;
use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

#[\AllowDynamicProperties]
class HtmlView extends BaseHtmlView
{
	use ViewLoadAnyTemplateTrait;
	use ViewTaskBasedEventsTrait;
	use ViewToolbarTrait;

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

	public function onBeforeMain($tpl = null)
	{
		$this->addToolbar();

		/** @var LogModel $logModel */
		$logModel = $this->getModel('Log');

		// Get a list of log names
		$this->logs = $logModel->getLogList(true);
		$this->log  = $this->getModel()->getState('log', null);
	}

	public function onBeforeStart($tpl = null)
	{
		$this->onBeforeStep();
	}

	public function onBeforeStep($tpl = null)
	{
		$this->addToolbar(false);

		/** @var AliceModel $model */
		$model                = $this->getModel();
		$this->totalChecks    = $model->getState('totalChecks');
		$this->doneChecks     = $model->getState('doneChecks');
		$this->currentSection = $model->getState('currentSection');
		$this->currentCheck   = $model->getState('currentCheck');
		$this->percentage     = min(100, ceil(100.0 * ($this->doneChecks / max($this->totalChecks, 1))));
	}

	public function onBeforeResult($tpl = null)
	{
		$this->addToolbar();

		/** @var AliceModel $model */
		$model               = $this->getModel();
		$this->totalChecks   = $model->getState('totalChecks');
		$this->doneChecks    = $model->getState('doneChecks');
		$this->aliceError    = $model->getState('aliceError');
		$this->aliceWarnings = $model->getState('aliceWarnings');
		$this->aliceStatus   = empty($this->aliceWarnings) ? 'success' : 'warnings';
		$this->aliceStatus   = empty($this->aliceError) ? $this->aliceStatus : 'error';
	}

	public function onBeforeError($tpl = null)
	{
		$this->addToolbar();

		$this->errorException = Factory::getApplication()->getSession()->get('akeebabackup.aliceException');
	}

	private function addToolbar(bool $showMenu = true)
	{
		$toolbar = $this->getToolbarCompat();
		ToolbarHelper::title(Text::_('COM_AKEEBABACKUP_TITLE_ALICES'), 'icon-akeeba');

		if (!$showMenu)
		{
			JoomlaFactory::getApplication()->getInput()->set('hidemainmenu', true);

			return;
		}

		$toolbar->back()
			->text('COM_AKEEBABACKUP_CONTROLPANEL')
			->icon('fa fa-' . (JoomlaFactory::getApplication()->getLanguage()->isRtl() ? 'arrow-right' : 'arrow-left'))
			->url('index.php?option=com_akeebabackup');
	}
}