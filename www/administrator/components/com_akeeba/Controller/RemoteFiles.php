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
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Exception;
use FOF30\Container\Container;
use FOF30\Controller\Controller;
use JText;
use RuntimeException;

class RemoteFiles extends Controller
{
	use CustomACL;

	/**
	 * Public constructor. We set the default task to invalidTask so that any accessing this view without a task will
	 * result in an error.
	 *
	 * @param   Container  $container  Component container
	 * @param   array      $config     Configuration overrides
	 */
	public function __construct(Container $container, array $config)
	{
		if (!is_array($config))
		{
			$config = [];
		}

		$config['default_task'] = 'invalidTask';

		parent::__construct($container, $config);
	}

	/**
	 * When someone calls this controller without a task we have to show an error message. This is implemented by
	 * having this task throw a runtime exception and set it as the default task.
	 *
	 * @return  void
	 */
	public function invalidTask()
	{
		throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
	}

	/**
	 * Lists the available remote storage actions for a specific backup entry
	 *
	 * @return  void
	 */
	public function listactions()
	{
		// List available actions
		$id = $this->getAndCheckId();

		/** @var \Akeeba\Backup\Admin\Model\RemoteFiles $model */
		$model = $this->getModel();
		$model->setState('id', $id);

		if ($id === false)
		{
			throw new RuntimeException(JText::_('JGLOBAL_RESOURCE_NOT_FOUND'), 404);
		}

		$this->display(false);
	}


	/**
	 * Fetches a complete backup set from a remote storage location to the local (server)
	 * storage so that the user can download or restore it.
	 *
	 * @return  void
	 */
	public function dltoserver()
	{
		// Get the parameters
		$id   = $this->getAndCheckId();
		$part = $this->input->get('part', -1, 'int');
		$frag = $this->input->get('frag', -1, 'int');

		// Check the ID
		if ($id === false)
		{
			$url = 'index.php?option=com_akeeba&view=RemoteFiles&tmpl=component&task=listactions&id=' . $id;
			$this->setRedirect($url, JText::_('COM_AKEEBA_REMOTEFILES_ERR_INVALIDID'), 'error');

			return;
		}

		/** @var \Akeeba\Backup\Admin\Model\RemoteFiles $model */
		$model = $this->getModel();

		try
		{
			$result = $model->downloadToServer($id, $part, $frag);
		}
		catch (Exception $e)
		{
			$allErrors = $model->getErrorsFromExceptions($e);
			$url       = 'index.php?option=com_akeeba&view=RemoteFiles&tmpl=component&task=listactions&id=' . $id;

			$this->setRedirect($url, implode('<br/>', $allErrors), 'error');

			return;
		}

		if ($result === true)
		{
			$url = 'index.php?option=com_akeeba&view=RemoteFiles&tmpl=component&task=listactions&id=' . $id;
			$this->setRedirect($url, JText::_('COM_AKEEBA_REMOTEFILES_LBL_JUSTFINISHED'));

			return;
		}

		$this->display(false);
	}

	/**
	 * Downloads a file from the remote storage to the user's browser
	 *
	 * @return  void
	 */
	public function dlfromremote()
	{
		$id   = $this->getAndCheckId();
		$part = $this->input->get('part', 0, 'int');

		if ($id === false)
		{
			$url = 'index.php?option=com_akeeba&view=RemoteFiles&tmpl=component&task=listactions&id=' . $id;
			$this->setRedirect($url, JText::_('COM_AKEEBA_REMOTEFILES_ERR_INVALIDID'), 'error');

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
			// Failed to download. Get the messages from the engine.
			$errors          = [];
			$parentException = $e;
			while ($parentException)
			{
				$errors[]        = $e->getMessage();
				$parentException = $e->getPrevious();
			}

			// Redirect and convey the errors to the user
			$url = 'index.php?option=com_akeeba&view=RemoteFiles&tmpl=component&task=listactions&id=' . $id;
			$this->setRedirect($url, implode('<br/>', $errors), 'error');
		}

		if (!is_null($result))
		{
			// We have to redirect
			$result = str_replace('://%2F', '://', $result);
			@ob_end_clean();
			header('Location: ' . $result);
			flush();

			$this->container->platform->closeApplication();
		}
	}

	/**
	 * Deletes a file from the remote storage
	 *
	 * @return  void
	 */
	public function delete()
	{
		// Get the parameters
		$id   = $this->getAndCheckId();
		$part = $this->input->get('part', -1, 'int');

		// Check the ID
		if ($id === false)
		{
			$url = 'index.php?option=com_akeeba&view=RemoteFiles&tmpl=component&task=listactions&id=' . $id;
			$this->setRedirect($url, JText::_('COM_AKEEBA_REMOTEFILES_ERR_INVALIDID'), 'error');

			return;
		}

		/** @var \Akeeba\Backup\Admin\Model\RemoteFiles $model */
		$model = $this->getModel();
		$model->setState('id', $id);
		$model->setState('part', $part);

		try
		{
			$result = $model->deleteRemoteFiles($id, $part);
		}
		catch (Exception $e)
		{
			$allErrors = $model->getErrorsFromExceptions($e);
			$url       = 'index.php?option=com_akeeba&view=RemoteFiles&tmpl=component&task=listactions&id=' . $id;

			$this->setRedirect($url, implode('<br/>', $allErrors), 'error');

			return;
		}

		if ($result['finished'])
		{
			$url = 'index.php?option=com_akeeba&view=RemoteFiles&tmpl=component&task=listactions&id=' . $id;
			$this->setRedirect($url, JText::_('COM_AKEEBA_REMOTEFILES_LBL_JUSTFINISHEDELETING'));

			return;
		}

		$url = 'index.php?option=com_akeeba&view=RemoteFiles&tmpl=component&task=delete&id=' . $result['id'] .
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
