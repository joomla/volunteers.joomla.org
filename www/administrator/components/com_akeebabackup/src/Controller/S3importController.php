<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Controller;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerCustomACLTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerEventsTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerRegisterTasksTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerReusableModelsTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Model\S3importModel;
use Akeeba\Engine\Factory;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Router\Route;
use Joomla\Input\Input;
use RuntimeException;

class S3importController extends BaseController
{
	use ControllerEventsTrait;
	use ControllerCustomACLTrait;
	use ControllerRegisterTasksTrait;
	use ControllerReusableModelsTrait;

	public function __construct($config = [], ?MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

		$this->registerControllerTasks('main');
	}

	public function main()
	{
		$s3bucket = $this->input->getRaw('s3bucket', null);

		/** @var S3importModel $model */
		$model = $this->getModel('S3import', 'Administrator');

		if ($s3bucket)
		{
			$model->setState('s3bucket', $s3bucket);
		}

		$this->getS3Credentials();
		$model->setS3Credentials($model->getState('s3access'), $model->getState('s3secret'));

		$this->display(false);
	}

	/**
	 * Fetches a complete backup set from a remote storage location to the local (server)
	 * storage so that the user can download or restore it.
	 */
	public function dltoserver()
	{
		// Anti-CSRF protection. The token is appended to the import and auto-redirect URLs which drive this task.
		$this->checkToken('get');

		$s3bucket = $this->input->getRaw('s3bucket', null);

		// Get the parameters
		/** @var S3importModel $model */
		$model = $this->getModel();

		if ($s3bucket)
		{
			$model->setState('s3bucket', $s3bucket);
		}

		$this->getS3Credentials();
		$model->setS3Credentials($model->getState('s3access'), $model->getState('s3secret'));

		$part    = $this->input->getInt('part', -999);
		$session = $this->app->getSession();

		if ($part >= -1)
		{
			$session->set('com_akeebabackup.s3import.part', $part);
		}

		$frag = $this->input->getInt('frag', -999);

		if ($frag >= -1)
		{
			$session->set('com_akeebabackup.s3import.frag', $frag);
		}

		$step = $this->input->getInt('step', -999);

		if ($step >= -1)
		{
			$session->set('com_akeebabackup.s3import.step', $step);
		}

		$errorMessage = '';

		// These are only used to trigger the event required for the actionlog plugin
		$bucket = $model->getState('s3bucket', '');
		$folder = $model->getState('folder', '');
		$file   = $model->getState('file', '');

		try
		{
			$result = $model->downloadToServer();
		}
		catch (RuntimeException $e)
		{
			$result       = -1;
			$errorMessage = $e->getMessage();
		}

		if ($result === false)
		{
			// Part(s) downloaded successfully. Render the view.
			$this->display(false);
		}
		elseif ($result === -1)
		{
			// Part did not download. Redirect to initial page with an error.
			$this->setRedirect(Route::_('index.php?option=com_akeebabackup&view=S3import', false), $errorMessage, 'error');
		}
		else
		{
			$this->triggerEvent('onSuccessfulImport', [
				$bucket,
				$folder,
				$file,
			]);

			// All done. Redirect to intial page with a success message.
			$this->setRedirect(Route::_('index.php?option=com_akeebabackup&view=S3import', false), Text::_('COM_AKEEBABACKUP_S3IMPORT_MSG_IMPORTCOMPLETE'));
		}
	}

	/**
	 * Populate the S3 connection credentials from the request
	 *
	 * This is an internal helper for the main() and dltoserver() tasks, NOT a task itself. It MUST remain non-public:
	 * registerControllerTasks() registers every public method as a controller task, which would expose it as the
	 * untokenised task `gets3credentials`, letting a third party site push arbitrary S3 credentials, bucket and path
	 * into the logged-in user's state and trigger an outbound bucket region lookup with them.
	 *
	 * @return  void
	 * @since   9.0.0
	 */
	protected function getS3Credentials()
	{
		$config         = Factory::getConfiguration();
		$defS3AccessKey = $config->get('engine.postproc.s3.accesskey', '');
		$defS3SecretKey = $config->get('engine.postproc.s3.privatekey', '');

		$accessKey = $this->app->getUserStateFromRequest('com_akeebabackup.s3access', 's3access', $defS3AccessKey, 'raw');
		$secretKey = $this->app->getUserStateFromRequest('com_akeebabackup.s3secret', 's3secret', $defS3SecretKey, 'raw');
		$bucket    = $this->app->getUserStateFromRequest('com_akeebabackup.bucket', 's3bucket', '', 'raw');
		$folder    = $this->app->getUserStateFromRequest('com_akeebabackup.folder', 'folder', '', 'raw');
		$file      = $this->app->getUserStateFromRequest('com_akeebabackup.file', 'file', '', 'raw');
		$part      = $this->app->getUserStateFromRequest('com_akeebabackup.s3import.part', 'part', -1, 'int');
		$frag      = $this->app->getUserStateFromRequest('com_akeebabackup.s3import.frag', 'frag', -1, 'int');

		/** @var S3importModel $model */
		$model = $this->getModel('S3import', 'Administrator');

		$model->setState('s3access', $accessKey);
		$model->setState('s3secret', $secretKey);
		$model->setState('s3bucket', $bucket);
		$model->setState('folder', $folder);
		$model->setState('file', $file);
		$model->setState('part', $part);
		$model->setState('frag', $frag);

		// We need to do that to prime the model state with the region of the requested S3 bucket
		/** @noinspection PhpUnusedLocalVariableInspection */
		$region = $model->getBucketRegion($bucket);
	}

}