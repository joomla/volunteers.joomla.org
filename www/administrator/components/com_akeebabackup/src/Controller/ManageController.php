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
use Akeeba\Component\AkeebaBackup\Administrator\Model\StatisticModel;
use Akeeba\Component\AkeebaBackup\Administrator\Model\StatisticsModel;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Uri\Uri;
use Joomla\Input\Input;

class ManageController extends AdminController
{
	use ControllerEventsTrait;
	use ControllerCustomACLTrait;

	protected $text_prefix = 'COM_AKEEBABACKUP_BUADMIN';

	private bool $noFlush = false;

	public function __construct(
		$config = [], ?MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null
	)
	{
		parent::__construct($config, $factory, $app, $input);

		$this->noFlush = ComponentHelper::getParams('com_akeebabackup')->get('no_flush', 0) == 1;

		$this->registerTask('download', 'download');
		$this->registerTask('remove', 'remove');
		$this->registerTask('deletefiles', 'deletefiles');
		$this->registerTask('hidemodal', 'hidemodal');
	}

	public function getModel($name = '', $prefix = '', $config = [])
	{
		$name = $name ?: 'Statistic';

		return parent::getModel($name, $prefix, $config);
	}

	public function display($cachable = false, $urlparams = [])
	{
		$document   = $this->app->getDocument();
		$viewType   = $document->getType();
		$viewName   = $this->input->get('view', $this->default_view);
		$viewLayout = $this->input->get('layout', 'default', 'string');

		$view = $this->getView($viewName, $viewType, '', ['base_path' => $this->basePath, 'layout' => $viewLayout]);

		// Push the models
		$view->setModel($this->getModel('Statistics', 'Administrator'), true);
		$view->setModel($this->getModel('Profiles', 'Administrator'));

		// Push the document
		$view->document = $document;

		// Display the view
		$view->display();

		return $this;
	}


	/**
	 * Downloads the backup archive of the specified backup record
	 *
	 * @return  void
	 */
	public function download()
	{
		$this->checkToken('get');

		// Get items to publish from the request.
		$id   = $this->input->getInt('id', 0);
		$part = $this->input->get('part', -1, 'int');

		if ($id <= 0)
		{
			$this->setRedirect(
				Uri::base() . 'index.php?option=com_akeebabackup&view=Manage',
				Text::_('COM_AKEEBABACKUP_BUADMIN_ERROR_INVALIDID'), 'error'
			);

			return;
		}

		$stat         = Platform::getInstance()->get_statistics($id);
		$allFilenames = Factory::getStatistics()->get_all_filenames($stat);

		$filename = null;

		// Check single part files
		$countAllFilenames = $allFilenames === null ? 0 : count($allFilenames);

		if (($countAllFilenames == 1) && ($part == -1))
		{
			$filename = array_shift($allFilenames);
		}
		elseif (($countAllFilenames > 0) && ($countAllFilenames > $part) && ($part >= 0))
		{
			$filename = $allFilenames[$part];
		}

		if (is_null($filename) || empty($filename) || !@file_exists($filename))
		{
			$this->setRedirect(
				Uri::base() . 'index.php?option=com_akeebabackup&view=Manage',
				Text::_('COM_AKEEBABACKUP_BUADMIN_ERROR_INVALIDDOWNLOAD'), 'error'
			);

			return;
		}

		$this->triggerEvent('onBeforeDownload', [$id, $part ?: 1]);

		// Remove php's time limit
		if (function_exists('ini_get') && function_exists('set_time_limit'))
		{
			if (!ini_get('safe_mode'))
			{
				@set_time_limit(0);
			}
		}

		$basename  = @basename($filename);
		$filesize  = @filesize($filename);
		$extension = strtolower(str_replace(".", "", strrchr($filename, ".")));

		/** @noinspection PhpStatementHasEmptyBodyInspection */
		while (@ob_end_clean())
		{
		}

		@clearstatcache();

		// Send MIME headers
		header('MIME-Version: 1.0');
		header('Content-Disposition: attachment; filename="' . $basename . '"');
		header('Content-Transfer-Encoding: binary');
		header('Accept-Ranges: bytes');

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

		// Notify of filesize, if this info is available
		if ($filesize > 0)
		{
			header('Content-Length: ' . @filesize($filename));
		}

		// Disable caching
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Expires: 0");
		header('Pragma: no-cache');

		if (!$this->noFlush)
		{
			flush();
		}

		if (!$filesize)
		{
			// If the filesize is not reported, hope that readfile works
			@readfile($filename);

			$this->app->close();
		}

		// If the filesize is reported, use 1M chunks for echoing the data to the browser
		$blocksize = 1048576; //1M chunks
		$handle    = @fopen($filename, "r");

		// Now we need to loop through the file and echo out chunks of file data
		if ($handle !== false)
		{
			while (!@feof($handle))
			{
				echo @fread($handle, $blocksize);

				@ob_flush();

				if (!$this->noFlush)
				{
					flush();
				}
			}
		}

		if ($handle !== false)
		{
			@fclose($handle);
		}

		$this->app->close();
	}

	public function delete()
	{
		$this->checkToken();

		// Get items to publish from the request.
		$ids = $this->input->get('cid', [], 'array');

		if (empty($ids))
		{
			$this->setRedirect(
				Uri::base() . 'index.php?option=com_akeebabackup&view=Manage',
				Text::_('COM_AKEEBABACKUP_BUADMIN_ERROR_INVALIDID'), 'error'
			);

			return;
		}

		foreach ($ids as $id)
		{
			try
			{
				$msg    = Text::_('COM_AKEEBABACKUP_BUADMIN_ERROR_INVALIDID');
				$result = false;

				if ($id > 0)
				{
					/** @var StatisticModel $model */
					$model  = $this->getModel('Statistic', 'Administrator');
					$result = $model->delete($id);
				}

			}
			catch (\RuntimeException $e)
			{
				$result = false;
				$msg    = $e->getMessage();
			}

			if (!$result)
			{
				$this->setRedirect(Uri::base() . 'index.php?option=com_akeebabackup&view=Manage', $msg, 'error');

				return;
			}
		}

		$this->setRedirect(
			Uri::base() . 'index.php?option=com_akeebabackup&view=Manage',
			Text::_('COM_AKEEBABACKUP_BUADMIN_MSG_DELETED')
		);
	}

	public function deletefiles()
	{
		$this->checkToken();

		// Get items to publish from the request.
		$ids = $this->input->get('cid', [], 'array');

		if (empty($ids))
		{
			$this->setRedirect(
				Uri::base() . 'index.php?option=com_akeebabackup&view=Manage',
				Text::_('COM_AKEEBABACKUP_BUADMIN_ERROR_INVALIDID'), 'error'
			);

			return;
		}

		foreach ($ids as $id)
		{
			try
			{
				$msg    = Text::_('COM_AKEEBABACKUP_BUADMIN_ERROR_INVALIDID');
				$result = false;

				if ($id > 0)
				{
					/** @var StatisticModel $model */
					$model  = $this->getModel('Statistic', 'Administrator');
					$result = $model->deleteFiles($id);
				}
			}
			catch (\RuntimeException $e)
			{
				$result = false;
				$msg    = $e->getMessage();
			}

			if (!$result)
			{
				$this->setRedirect(Uri::base() . 'index.php?option=com_akeebabackup&view=Manage', $msg, 'error');

				return;
			}
		}

		$this->setRedirect(
			Uri::base() . 'index.php?option=com_akeebabackup&view=Manage',
			Text::_('COM_AKEEBABACKUP_BUADMIN_MSG_DELETEDFILE')
		);
	}

	public function hidemodal()
	{
		// Anti-CSRF protection. The token is appended to the "hide modal" link.
		$this->checkToken('get');

		/** @var StatisticsModel $model */
		$model = $this->getModel('Statistics', 'Administrator');
		$model->hideRestorationInstructionsModal(
			$this->app->bootComponent('com_akeebabackup')->getComponentParametersService()
		);

		$this->setRedirect(Uri::base() . 'index.php?option=com_akeebabackup&view=Manage');
	}

}