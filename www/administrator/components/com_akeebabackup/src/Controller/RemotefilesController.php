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
use Akeeba\Component\AkeebaBackup\Administrator\Model\RemotefilesModel;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Uri\Uri;
use Joomla\Input\Input;
use RuntimeException;

class RemotefilesController extends BaseController
{
	use ControllerEventsTrait;
	use ControllerCustomACLTrait;
	use ControllerRegisterTasksTrait;
	use ControllerReusableModelsTrait;

	private bool $noFlush = false;

	public function __construct(
		$config = [], ?MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null
	)
	{
		parent::__construct($config, $factory, $app, $input);

		$this->noFlush = ComponentHelper::getParams('com_akeebabackup')->get('no_flush', 0) == 1;

		$this->registerControllerTasks('invalidTask');
	}

	public function display($cachable = false, $urlparams = [])
	{
		$document   = $this->app->getDocument();
		$viewType   = $document->getType();
		$viewName   = $this->input->get('view', $this->default_view);
		$viewLayout = $this->input->get('layout', 'default', 'string');

		$view = $this->getView($viewName, $viewType, '', ['base_path' => $this->basePath, 'layout' => $viewLayout]);

		// Get/Create the model
		if ($model = $this->getModel($viewName, 'Administrator', ['base_path' => $this->basePath]))
		{
			// Push the model into the view (as default)
			$view->setModel($model, true);
		}

		$view->setDocument($document);
		$view->task     = $this->task;
		$view->doTask   = $this->doTask;

		// Display the view
		$view->display();

		return $this;
	}


	/**
	 * When someone calls this controller without a task we have to show an error message. This is implemented by
	 * having this task throw a runtime exception and set it as the default task.
	 *
	 * @return  void
	 *
	 * @noinspection PhpUnused
	 */
	public function invalidTask()
	{
		throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
	}

	/**
	 * Lists the available remote storage actions for a specific backup entry
	 *
	 * @return  void
	 * @throws  Exception
	 */
	public function listactions()
	{
		// List available actions
		$id = $this->getAndCheckId();

		/** @var RemotefilesModel $model */
		$model = $this->getModel('Remotefiles', 'Administrator');
		$model->setState('id', $id);

		if ($id === false)
		{
			throw new RuntimeException(Text::_('JGLOBAL_RESOURCE_NOT_FOUND'), 404);
		}

		$this->display(false);
	}


	/**
	 * Fetches a complete backup set from a remote storage location to the local (server)
	 * storage so that the user can download or restore it.
	 *
	 * @return  void
	 * @throws  Exception
	 *
	 * @noinspection PhpUnused
	 */
	public function dltoserver()
	{
		$this->checkToken($this->input->getMethod());

		// Get the parameters
		$id   = $this->getAndCheckId();
		$part = $this->input->get('part', -1, 'int');
		$frag = $this->input->get('frag', -1, 'int');

		// Check the ID
		if ($id === false)
		{
			$url = Uri::base()
			       . 'index.php?option=com_akeebabackup&view=Remotefiles&tmpl=component&task=listactions&id=' . $id;
			$this->setRedirect($url, Text::_('COM_AKEEBABACKUP_REMOTEFILES_ERR_INVALIDID'), 'error');

			return;
		}

		if (($part == -1) && ($frag == -1))
		{
			$this->triggerEvent('onFetch', [$id]);
		}

		/** @var RemotefilesModel $model */
		$model = $this->getModel('Remotefiles', 'Administrator');

		try
		{
			$result = $model->downloadToServer($id, $part, $frag);
		}
		catch (Exception $e)
		{
			$allErrors = $model->getErrorsFromExceptions($e);
			$url       = Uri::base()
			             . 'index.php?option=com_akeebabackup&view=Remotefiles&tmpl=component&task=listactions&id='
			             . $id;

			$this->setRedirect($url, implode('<br/>', $allErrors), 'error');

			return;
		}

		if ($result === true)
		{
			$url = Uri::base()
			       . 'index.php?option=com_akeebabackup&view=Remotefiles&tmpl=component&task=listactions&id=' . $id;
			$this->setRedirect($url, Text::_('COM_AKEEBABACKUP_REMOTEFILES_LBL_JUSTFINISHED'));

			return;
		}

		$this->display(false);
	}

	/**
	 * Downloads a file from the remote storage to the user's browser
	 *
	 * @return  void
	 *
	 * @noinspection PhpUnused
	 */
	public function dlfromremote()
	{
		$this->checkToken($this->input->getMethod());

		$id   = $this->getAndCheckId();
		$part = $this->input->get('part', 0, 'int');

		if ($id === false)
		{
			$url = Uri::base()
			       . 'index.php?option=com_akeebabackup&view=Remotefiles&tmpl=component&task=listactions&id=' . $id;
			$this->setRedirect($url, Text::_('COM_AKEEBABACKUP_REMOTEFILES_ERR_INVALIDID'), 'error');

			return;
		}

		$stat                = Platform::getInstance()->get_statistics($id);
		$remoteFilenameParts = explode('://', $stat['remote_filename']);
		$engine              = Factory::getPostprocEngine($remoteFilenameParts[0]);
		$remote_filename     = $remoteFilenameParts[1];

		$basename  = basename($remote_filename);
		$extension = strtolower(str_replace(".", "", strrchr($basename, ".")));

		$new_extension = $extension;

		if ($part > 0)
		{
			$new_extension = substr($extension, 0, 1) . sprintf('%02u', $part);
		}

		$this->triggerEvent('onDownload', [$id, $part + 1]);

		$filename        = $basename . '.' . $new_extension;
		$remote_filename = substr($remote_filename, 0, -strlen($extension)) . $new_extension;

		if ($engine->doesInlineDownloadToBrowser())
		{
			@ob_end_clean();
			@clearstatcache();

			// Send MIME headers
			header('MIME-Version: 1.0');
			header('Content-Disposition: attachment; filename="' . $filename . '"');
			header('Content-Transfer-Encoding: binary');

			switch ($extension)
			{
				case 'zip':
					// ZIP MIME type
					header('Content-Type: application/zip');
					break;

				default:
					// Generic binary data MIME type
					header('Content-Type: application/octet-stream');
					break;
			}

			// Disable caching
			header('Expires: Mon, 20 Dec 1998 01:00:00 GMT');
			header('Cache-Control: no-cache, must-revalidate');
			header('Pragma: no-cache');
		}

		try
		{
			$result = $engine->downloadToBrowser($remote_filename);
		}
		catch (Exception $e)
		{
			$result = null;

			// Failed to download. Get the messages from the engine.
			$errors          = [];
			$parentException = $e;
			while ($parentException)
			{
				$errors[]        = $e->getMessage();
				$parentException = $e->getPrevious();
			}

			// Redirect and convey the errors to the user
			$url = Uri::base()
			       . 'index.php?option=com_akeebabackup&view=Remotefiles&tmpl=component&task=listactions&id=' . $id;
			$this->setRedirect($url, implode('<br/>', $errors), 'error');
		}

		if (!is_null($result))
		{
			// We have to redirect
			$result = str_replace('://%2F', '://', $result);
			@ob_end_clean();
			header('Location: ' . $result);

			if (!$this->noFlush)
			{
				flush();
			}

			$this->app->close();
		}
	}

	/**
	 * Deletes a file from the remote storage
	 *
	 * @return  void
	 */
	public function delete()
	{
		$this->checkToken($this->input->getMethod());

		// Get the parameters
		$id   = $this->getAndCheckId();
		$part = $this->input->get('part', -1, 'int');

		// Check the ID
		if ($id === false)
		{
			$url = Uri::base()
			       . 'index.php?option=com_akeebabackup&view=Remotefiles&tmpl=component&task=listactions&id=' . $id;
			$this->setRedirect($url, Text::_('COM_AKEEBABACKUP_REMOTEFILES_ERR_INVALIDID'), 'error');

			return;
		}

		if ($part == -1)
		{
			$this->triggerEvent('onDelete', [$id]);
		}

		/** @var RemotefilesModel $model */
		$model = $this->getModel('Remotefiles', 'Administrator');
		$model->setState('id', $id);
		$model->setState('part', $part);

		try
		{
			$result = $model->deleteRemoteFiles($id, $part);
		}
		catch (Exception $e)
		{
			$allErrors = $model->getErrorsFromExceptions($e);
			$url       = Uri::base()
			             . 'index.php?option=com_akeebabackup&view=Remotefiles&tmpl=component&task=listactions&id='
			             . $id;

			$this->setRedirect($url, implode('<br/>', $allErrors), 'error');

			return;
		}

		if ($result['finished'])
		{
			$url = Uri::base()
			       . 'index.php?option=com_akeebabackup&view=Remotefiles&tmpl=component&task=listactions&id=' . $id;
			$this->setRedirect($url, Text::_('COM_AKEEBABACKUP_REMOTEFILES_LBL_JUSTFINISHEDELETING'));

			return;
		}

		$url = Uri::base() . 'index.php?option=com_akeebabackup&view=Remotefiles&tmpl=component&task=delete&id='
		       . $result['id'] .
		       '&part=' . $result['part'];
		$this->setRedirect($url);
	}

	/**
	 * Gets the stats record ID from the request and checks that it does exist
	 *
	 * @return  bool|int  False if an invalid ID is found, the numeric ID if it's valid
	 */
	private function getAndCheckId()
	{
		$id = $this->input->get('id', 0, 'int');

		if ($id <= 0)
		{
			return false;
		}

		$backupRecord = Platform::getInstance()->get_statistics($id);

		if (empty($backupRecord) || !is_array($backupRecord))
		{
			return false;
		}

		// Load the correct backup profile. The post-processing engine could rely on the active profile (ie OneDrive).
		define('AKEEBA_PROFILE', $backupRecord['profile_id']);
		Platform::getInstance()->load_configuration($backupRecord['profile_id']);

		return $id;
	}

}