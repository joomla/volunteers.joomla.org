<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\View\Upload;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ViewTaskBasedEventsTrait;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

#[\AllowDynamicProperties]
class HtmlView extends BaseHtmlView
{
	use ViewTaskBasedEventsTrait;

	/**
	 * ID of the record to reupload to remote torage
	 *
	 * @var  int
	 */
	public $id = 0;

	/**
	 * Total number of parts which have to be uploaded
	 *
	 * @var  int
	 */
	public $parts = 0;

	/**
	 * Current part being uploaded
	 *
	 * @var  int
	 */
	public $part = 0;

	/**
	 * Current fragment of the part being uploaded
	 *
	 * @var  int
	 */
	public $frag = 0;

	/**
	 * Are we done? 0/1
	 *
	 * @var  int
	 */
	public $done = 0;

	/**
	 * Is there an error? 0/1
	 *
	 * @var  int
	 */
	public $error = 0;

	/**
	 * Error message to display
	 *
	 * @var  string
	 */
	public $errorMessage = '';

	public function display($tpl = null)
	{
		$this->getDocument()->getWebAssetManager()
			->useScript('com_akeebabackup.upload');

		parent::display($tpl);
	}

	/**
	 * Runs before displaying the "upload" task's page
	 *
	 * @return  void
	 */
	public function onBeforeUpload()
	{
		$this->setLayout('uploading');

		if ($this->done)
		{
			$this->setLayout('done');
		}

		if ($this->error)
		{
			$this->setLayout('error');
		}
	}

	/**
	 * Runs before displaying the "cancelled" task's page
	 *
	 * @return  void
	 */
	public function onBeforeCancelled()
	{
		$this->setLayout('error');
	}

	/**
	 * Runs before displaying the "start" task's page
	 *
	 * @return  void
	 */
	public function onBeforeStart()
	{
		$this->setLayout('default');

		if ($this->done)
		{
			$this->setLayout('done');
		}

		if ($this->error)
		{
			$this->setLayout('error');
		}
	}
}