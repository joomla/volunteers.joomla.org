<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\View\Remotefiles;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewLoadAnyTemplateTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewTaskBasedEventsTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Model\RemotefilesModel;
use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

#[\AllowDynamicProperties]
class HtmlView extends BaseHtmlView
{
	use ViewLoadAnyTemplateTrait;
	use ViewTaskBasedEventsTrait;

	/** @var string The task of the controller */
	public $task;

	/** @var string The task actually being executed by the controller */
	public $doTask;

	/**
	 * The available remote file actions
	 *
	 * @var  array
	 */
	public $actions = [];

	/**
	 * The capabilities of the remote storage engine
	 *
	 * @var  array
	 */
	public $capabilities = [];

	/**
	 * Total size of the file(s) to download
	 *
	 * @var  int
	 */
	public $total;

	/**
	 * Total size of downloaded file(s) so far
	 *
	 * @var  int
	 */
	public $done;

	/**
	 * Percentage of the total download complete, rounded to the nearest whole number (0-100)
	 *
	 * @var  int
	 */
	public $percent;

	/**
	 * The backup record ID we are downloading back to the server
	 *
	 * @var  int
	 */
	public $id;

	/**
	 * The part number currently being downloaded
	 *
	 * @var  int
	 */
	public $part;

	/**
	 * The fragment of the part currently being downloaded
	 *
	 * @var  int
	 */
	public $frag;

	/**
	 * Runs on the "listactions" task: lists all
	 *
	 * @throws Exception
	 */
	public function onBeforeListactions()
	{
		$css = <<< CSS
dt.message { display: none; }
dd.message { list-style: none; }

CSS;

		$wa = $this->getDocument()->getWebAssetManager();

		$wa
			->useScript('com_akeebabackup.remotefiles')
			->addInlineStyle($css);

		/** @var RemotefilesModel $model */
		$model              = $this->getModel();
		$this->id           = $model->getState('id', -1);
		$this->actions      = $model->getActions($this->id);
		$this->capabilities = $model->getCapabilities($this->id);
	}

	public function onBeforeDltoserver()
	{
		$css = <<< CSS
dl { display: none; }

CSS;

		$wa = $this->getDocument()->getWebAssetManager();

		$wa
			->useScript('com_akeebabackup.remotefiles')
			->addInlineStyle($css);

		/** @var RemotefilesModel $model */
		$model = $this->getModel();

		$this->setLayout('dlprogress');

		// Get progress bar stats
		$app           = Factory::getApplication();
		$this->total   = $app->getSession()->get('akeebabackup.dl_totalsize', 0);
		$this->done    = $app->getSession()->get('akeebabackup.dl_donesize', 0);
		$this->percent = ($this->total > 0)
			? min(100, (int) (100 * (abs($this->done) / abs($this->total))))
			: 0;
		$this->id      = (int) $model->getState('id', 0);
		$this->part    = (int) $model->getState('part', 0);
		$this->frag    = (int) $model->getState('frag', 0);
	}
}