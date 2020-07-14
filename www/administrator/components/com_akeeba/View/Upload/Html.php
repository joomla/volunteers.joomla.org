<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Admin\View\Upload;

// Protect from unauthorized access
defined('_JEXEC') or die();

use FOF30\View\DataView\Html as BaseView;
use JHtml;
use Joomla\CMS\HTML\HTMLHelper;

class Html extends BaseView
{
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

	/**
	 * Runs before displaying the "upload" task's page
	 *
	 * @return  void
	 */
	public function onBeforeUpload()
	{
		$this->container->template->addJS('media://com_akeeba/js/Upload.min.js');

		$this->setLayout('uploading');

		if ($this->done)
		{
			HTMLHelper::_('behavior.modal');
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
		$this->container->template->addJS('media://com_akeeba/js/Upload.min.js');

		$this->setLayout('error');
	}

	/**
	 * Runs before displaying the "start" task's page
	 *
	 * @return  void
	 */
	public function onBeforeStart()
	{
		$this->container->template->addJS('media://com_akeeba/js/Upload.min.js');

		$this->setLayout('default');

		if ($this->done)
		{
			HTMLHelper::_('behavior.modal');
			$this->setLayout('done');
		}

		if ($this->error)
		{
			$this->setLayout('error');
		}
	}
}
