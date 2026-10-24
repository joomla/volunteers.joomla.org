<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Site\Controller;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerEventsTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerRegisterTasksTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerReusableModelsTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Model\StatisticsModel;
use Akeeba\Component\AkeebaBackup\Site\Mixin\ControllerFrontEndPermissionsTrait;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Input\Input;

class UploadcheckController extends BaseController
{
	use ControllerEventsTrait;
	use ControllerRegisterTasksTrait;
	use ControllerReusableModelsTrait;
	use ControllerFrontEndPermissionsTrait;

	public function __construct($config = [], ?MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

		$this->registerControllerTasks('main');
	}

	/**
	 * Checks for failed backups and sends out any notification emails
	 */
	public function main()
	{
		// Check permissions
		$this->checkPermissions();

		/** @var StatisticsModel $model */
		$model  = $this->getModel('Statistics', 'Administrator');
		$result = $model->notifyFailedUploads();

		$message = $result['result'] ? '200 ' : '500 ';
		$message .= implode(', ', $result['message']);

		@ob_end_clean();
		header('Content-type: text/plain');
		header('Connection: close');
		echo $message;
		flush();

		$this->app->close();
	}

}