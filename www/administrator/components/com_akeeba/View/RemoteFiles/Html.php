<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Admin\View\RemoteFiles;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\Backup\Admin\Model\RemoteFiles;
use FOF30\View\DataView\Html as BaseView;

class Html extends BaseView
{
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
	 */
	public function onBeforeListactions()
	{
		$this->container->template->addJS('media://com_akeeba/js/RemoteFiles.min.js');

		/** @var RemoteFiles $model */
		$model              = $this->getModel();
		$this->id           = $model->getState('id', -1);
		$this->actions      = $model->getActions($this->id);
		$this->capabilities = $model->getCapabilities($this->id);

		$css = <<< CSS
dt.message { display: none; }
dd.message { list-style: none; }

CSS;

		$this->addCssInline($css);
	}

	public function onBeforeDltoserver()
	{
		$this->container->template->addJS('media://com_akeeba/js/RemoteFiles.min.js');

		/** @var RemoteFiles $model */
		$model = $this->getModel();

		$this->setLayout('dlprogress');

		// Get progress bar stats
		$this->total   = $this->container->platform->getSessionVar('dl_totalsize', 0, 'akeeba');
		$this->done    = $this->container->platform->getSessionVar('dl_donesize', 0, 'akeeba');
		$this->percent = ($this->total > 0) ? min(100, (int) (100 * (abs($this->done) / abs($this->total)))) : 0;
		$this->id      = $model->getState('id', 0, 'int');
		$this->part    = $model->getState('part', 0, 'int');
		$this->frag    = $model->getState('frag', 0, 'int');

		// Render the progress bar
		$css = <<< CSS
dl { display: none; }

CSS;

		$this->addCssInline($css);

	}
}
