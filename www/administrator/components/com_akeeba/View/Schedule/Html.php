<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Admin\View\Schedule;

// Protect from unauthorized access
defined('_JEXEC') || die();

use Akeeba\Backup\Admin\Model\Schedule;
use Akeeba\Backup\Admin\View\ViewTraits\ProfileIdAndName;
use FOF30\View\DataView\Html as BaseView;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * View controller for the Scheduling Information page
 */
class Html extends BaseView
{
	use ProfileIdAndName;

	/**
	 * CRON information
	 *
	 * @var  object
	 */
	public $croninfo = null;

	/**
	 * Check for failed backups information
	 *
	 * @var  object
	 */
	public $checkinfo = null;

	protected function onBeforeMain()
	{
		$this->getProfileIdAndName();

		// Get the CRON paths
		/** @var Schedule $model */
		$model           = $this->getModel();
		$this->croninfo  = $model->getPaths();
		$this->checkinfo = $model->getCheckPaths();
	}
}
