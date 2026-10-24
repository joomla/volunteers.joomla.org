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
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerReusableModelsTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\GetErrorsFromExceptionsTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Model\UploadModel;
use Akeeba\Component\AkeebaBackup\Administrator\View\Upload\HtmlView as UploadView;
use Akeeba\Engine\Platform;
use Exception;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Uri\Uri;

class UploadController extends BaseController
{
	use ControllerEventsTrait;
	use ControllerCustomACLTrait;
	use ControllerReusableModelsTrait;
	use GetErrorsFromExceptionsTrait;

	/**
	 * Start the upload to remtoe storage
	 *
	 * @return  void
	 * @throws  Exception
	 */
	public function upload()
	{
		// Anti-CSRF protection. The token is submitted as a hidden field in the upload form.
		$this->checkToken();

		// Get the parameters from the URL
		$id   = $this->getAndCheckId();
		$part = $this->input->get('part', 0, 'int');
		$frag = $this->input->get('frag', 0, 'int');

		// Check the backup stat ID
		if ($id === false)
		{
			$url = Uri::base() . 'index.php?option=com_akeebabackup&view=Upload&tmpl=component&task=cancelled&id=' . $id;
			$this->setRedirect($url, Text::_('COM_AKEEBABACKUP_TRANSFER_ERR_INVALIDID'), 'error');

			return;
		}

		if (($part == -1) && ($frag == -1))
		{
			$this->triggerEvent('onStart', [$id]);
		}

		$part = max($part, 0);
		$frag = max($frag, 0);

		/**
		 * Get the View and initialize its layout
		 * @var UploadView $view
		 */
		$view        = $this->getView('upload', 'html');
		$view->done  = 0;
		$view->error = 0;
		$result      = false;

		$view->setLayout('uploading');

		$hasError = false;

		try
		{
			/** @var UploadModel $model */
			$model  = $this->getModel('Upload', 'Administrator');
			$result = $model->upload($id, $part, $frag);
		}
		catch (Exception $e)
		{
			$hasError = true;
		}

		// Get the modified model state
		$part = $model->getState('part');
		$stat = $model->getState('stat');
		$frag = $model->getState('frag');

		// Push the state to the view. We assume we have to continue uploading. We only change that if we detect an
		// upload completion or error condition in the if-blocks further below.
		$view->parts = $stat['multipart'];
		$view->part  = $part;
		$view->frag  = $frag;
		$view->id    = $id;

		if ($hasError)
		{
			// If we have an error we have to display it and stop the upload
			$view->done         = 0;
			$view->error        = 1;
			$view->errorMessage = implode("\n", $this->getErrorsFromExceptions($e));

			$view->setLayout('error');

			// Also reset the saved post-processing engine
			$this->app->getSession()->remove('akeebabackup.upload_factory');
		}
		elseif (($part >= 0) && ($result === true))
		{
			// If we are told the upload finished successfully we can display the "done" page
			$view->setLayout('done');
			$view->done  = 1;
			$view->error = 0;

			// Also reset the saved post-processing engine
			$this->app->getSession()->remove('akeebabackup.upload_factory');
		}

		$view->setDocument($this->app->getDocument());
		$view->setModel($model, true);
		$view->display();
	}

	/**
	 * This task is called when we have to cancel the upload
	 *
	 * @return  void
	 * @throws  Exception
	 */
	public function cancelled()
	{
		/** @var UploadView $view */
		$view = $this->getView('upload', 'html');
		$view->setLayout('error');

		$view->setDocument($this->app->getDocument());
		$view->setModel($this->getModel('Upload', 'Administrator'), true);
		$view->display();
	}

	/**
	 * Start uploading
	 *
	 * @return  void
	 * @throws  Exception
	 */
	public function start()
	{
		/**
		 * Anti-CSRF protection. The token is appended to the upload button's URL in the Manage Backups page.
		 *
		 * This task looks like a mere display task, but it is not: it discards the saved post-processing engine and
		 * renders a page which immediately submits the token-protected upload task, i.e. it does kick off an actual
		 * upload to remote storage.
		 */
		$this->checkToken('get');

		$id = $this->getAndCheckId();

		// Check the backup stat ID
		if ($id === false)
		{
			$url = Uri::base() . 'index.php?option=com_akeebabackup&view=Upload&tmpl=component&task=cancelled&id=' . $id;
			$this->setRedirect($url, Text::_('COM_AKEEBABACKUP_TRANSFER_ERR_INVALIDID'), 'error');

			return;
		}

		// Start by resetting the saved post-processing engine
		$this->app->getSession()->remove('akeebabackup.upload_factory');

		// Initialise the view
		/** @var UploadView $view */
		$view = $this->getView('upload', 'html');

		$view->done  = 0;
		$view->error = 0;

		$view->id = $id;
		$view->setLayout('default');

		$view->setDocument($this->app->getDocument());
		$view->setModel($this->getModel('Upload', 'Administrator'), true);
		$view->display();
	}

	/**
	 * Gets the stats record ID from the request and checks that it does exist
	 *
	 * @return bool|int False if an invalid ID is found, the numeric ID if it's valid
	 */
	private function getAndCheckId()
	{
		$id = $this->input->get('id', 0, 'int');

		if ($id <= 0)
		{
			return false;
		}

		$statObject = Platform::getInstance()->get_statistics($id);

		if (empty($statObject) || !is_array($statObject))
		{
			return false;
		}

		return $id;
	}
}