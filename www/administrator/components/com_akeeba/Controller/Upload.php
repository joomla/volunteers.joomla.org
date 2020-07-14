<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Admin\Controller;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\Backup\Admin\Controller\Mixin\CustomACL;
use Akeeba\Backup\Admin\Model\Mixin\GetErrorsFromExceptions;
use Akeeba\Backup\Admin\View\Upload\Html as UploadView;
use Akeeba\Engine\Platform;
use Exception;
use FOF30\Controller\Controller;
use JText;

class Upload extends Controller
{
	use CustomACL;
	use GetErrorsFromExceptions;

	/**
	 *
	 * @return  void
	 */
	public function upload()
	{
		// Get the parameters from the URL
		$id   = $this->getAndCheckId();
		$part = $this->input->get('part', 0, 'int');
		$frag = $this->input->get('frag', 0, 'int');

		// Check the backup stat ID
		if ($id === false)
		{
			$url = 'index.php?option=com_akeeba&view=Upload&tmpl=component&task=cancelled&id=' . $id;
			$this->setRedirect($url, JText::_('COM_AKEEBA_TRANSFER_ERR_INVALIDID'), 'error');

			return;
		}

		/**
		 * Get the View and initialize its layout
		 * @var UploadView $view
		 */
		$view        = $this->getView();
		$view->done  = 0;
		$view->error = 0;

		$view->setLayout('uploading');

		try
		{
			/** @var \Akeeba\Backup\Admin\Model\Upload $model */
			$model  = $this->getModel();
			$result = $model->upload($id, $part, $frag);
		}
		catch (Exception $e)
		{
			// If we have an error we have to display it and stop the upload
			$view->done         = 0;
			$view->error        = 1;
			$view->errorMessage = implode("\n", $this->getErrorsFromExceptions($e));

			$view->setLayout('error');

			// Also reset the saved post-processing engine
			$this->container->platform->setSessionVar('upload_factory', null, 'akeeba');

			$this->display(false, false);

			return;
		}
		finally
		{
			// Get the modified model state
			$part = $model->getState('part');
			$stat = $model->getState('stat');

			// Push the state to the view. We assume we have to continue uploading. We only change that if we detect an
			// upload completion or error condition in the if-blocks further below.
			$view->parts = $stat['multipart'];
			$view->part  = $part;
			$view->frag  = $model->getState('frag');
			$view->id    = $model->getState('id');
		}

		if (($part >= 0) && ($result === true))
		{
			// If we are told the upload finished successfully we can display the "done" page
			$view->setLayout('done');
			$view->done  = 1;
			$view->error = 0;

			// Also reset the saved post-processing engine
			$this->container->platform->setSessionVar('upload_factory', null, 'akeeba');
		}

		$this->display(false, false);
	}

	/**
	 * This task is called when we have to cancel the upload
	 *
	 * @param   bool  $cachable
	 * @param   bool  $urlparams
	 */
	public function cancelled($cachable = false, $urlparams = false)
	{
		/** @var UploadView $view */
		$view = $this->getView();
		$view->setLayout('error');

		$this->display(false, false);
	}

	/**
	 * Start uploading
	 *
	 * @return  void
	 */
	public function start($cachable = false, $urlparams = false)
	{
		$id = $this->getAndCheckId();

		// Check the backup stat ID
		if ($id === false)
		{
			$url = 'index.php?option=com_akeeba&view=Upload&tmpl=component&task=cancelled&id=' . $id;
			$this->setRedirect($url, JText::_('COM_AKEEBA_TRANSFER_ERR_INVALIDID'), 'error');

			return;
		}

		// Start by resetting the saved post-processing engine
		$this->container->platform->setSessionVar('upload_factory', null, 'akeeba');

		// Initialise the view
		/** @var UploadView $view */
		$view = $this->getView();

		$view->done  = 0;
		$view->error = 0;

		$view->id = $id;
		$view->setLayout('default');

		$this->display(false, false);
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
