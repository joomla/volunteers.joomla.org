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
use Akeeba\Backup\Admin\Controller\Mixin\PredefinedTaskList;
use Akeeba\Backup\Admin\Model\Exceptions\TransferFatalError;
use Akeeba\Backup\Admin\Model\Exceptions\TransferIgnorableError;
use Exception;
use FOF30\Container\Container;
use FOF30\Controller\Controller;

/**
 * Controller for the Site Transfer Wizard
 */
class Transfer extends Controller
{
	use CustomACL, PredefinedTaskList;

	public function __construct(Container $container, array $config)
	{
		parent::__construct($container, $config);

		$this->setPredefinedTaskList(['wizard', 'checkUrl', 'applyConnection', 'initialiseUpload', 'upload', 'reset']);
	}

	/**
	 * Reset the wizard
	 *
	 * @return  void
	 */
	public function reset()
	{
		$this->container->platform->setSessionVar('transfer', null, 'akeeba');
		$this->container->platform->setSessionVar('transfer.url', null, 'akeeba');
		$this->container->platform->setSessionVar('transfer.url_status', null, 'akeeba');
		$this->container->platform->setSessionVar('transfer.ftpsupport', null, 'akeeba');

		/** @var \Akeeba\Backup\Admin\Model\Transfer $model */
		$model = $this->getModel();
		$model->resetUpload();

		$this->setRedirect('index.php?option=com_akeeba&view=Transfer');
	}

	/**
	 * Cleans and checks the validity of the new site's URL
	 *
	 * @return  void
	 */
	public function checkUrl()
	{
		$url = $this->input->get('url', '', 'raw');

		/** @var \Akeeba\Backup\Admin\Model\Transfer $model */
		$model = $this->getModel();
		$model->savestate(true);
		$result = $model->checkAndCleanUrl($url);

		$this->container->platform->setSessionVar('transfer.url', $result['url'], 'akeeba');
		$this->container->platform->setSessionVar('transfer.url_status', $result['status'], 'akeeba');

		@ob_end_clean();
		echo '###' . json_encode($result) . '###';
		$this->container->platform->closeApplication();
	}

	/**
	 * Applies the FTP/SFTP connection information and makes some preliminary validation
	 *
	 * @return  void
	 */
	public function applyConnection()
	{
		$result = (object) [
			'status'    => true,
			'message'   => '',
			'ignorable' => false,
		];

		// Get the parameters from the request
		$transferOption = $this->input->getCmd('method', 'ftp');
		$force          = $this->input->getInt('force', 0);
		$ftpHost        = $this->input->get('host', '', 'raw', 2);
		$ftpPort        = $this->input->getInt('port', null);
		$ftpUsername    = $this->input->get('username', '', 'raw', 2);
		$ftpPassword    = $this->input->get('password', '', 'raw', 2);
		$ftpPubKey      = $this->input->get('public', '', 'raw', 2);
		$ftpPrivateKey  = $this->input->get('private', '', 'raw', 2);
		$ftpPassive     = $this->input->getInt('passive', 1);
		$ftpPassiveFix  = $this->input->getInt('passive_fix', 1);
		$ftpDirectory   = $this->input->get('directory', '', 'raw', 2);
		$chunkMode      = $this->input->getCmd('chunkMode', 'chunked');
		$chunkSize      = $this->input->getInt('chunkSize', '5242880');

		// Fix the port if it's missing
		if (empty($ftpPort))
		{
			switch ($transferOption)
			{
				case 'ftp':
				case 'ftpcurl':
					$ftpPort = 21;
					break;

				case 'ftps':
				case 'ftpscurl':
					$ftpPort = 990;
					break;

				case 'sftp':
				case 'sftpcurl':
					$ftpPort = 22;
					break;
			}
		}

		// Store everything in the session
		$this->container->platform->setSessionVar('transfer.transferOption', $transferOption, 'akeeba');
		$this->container->platform->setSessionVar('transfer.force', $force, 'akeeba');
		$this->container->platform->setSessionVar('transfer.ftpHost', $ftpHost, 'akeeba');
		$this->container->platform->setSessionVar('transfer.ftpPort', $ftpPort, 'akeeba');
		$this->container->platform->setSessionVar('transfer.ftpUsername', $ftpUsername, 'akeeba');
		$this->container->platform->setSessionVar('transfer.ftpPassword', $ftpPassword, 'akeeba');
		$this->container->platform->setSessionVar('transfer.ftpPubKey', $ftpPubKey, 'akeeba');
		$this->container->platform->setSessionVar('transfer.ftpPrivateKey', $ftpPrivateKey, 'akeeba');
		$this->container->platform->setSessionVar('transfer.ftpDirectory', $ftpDirectory, 'akeeba');
		$this->container->platform->setSessionVar('transfer.ftpPassive', $ftpPassive ? 1 : 0, 'akeeba');
		$this->container->platform->setSessionVar('transfer.ftpPassiveFix', $ftpPassiveFix ? 1 : 0, 'akeeba');
		$this->container->platform->setSessionVar('transfer.chunkMode', $chunkMode, 'akeeba');
		$this->container->platform->setSessionVar('transfer.chunkSize', $chunkSize, 'akeeba');

		/** @var \Akeeba\Backup\Admin\Model\Transfer $model */
		$model = $this->getModel();

		try
		{
			$config = $model->getFtpConfig();
			$model->testConnection($config);
		}
		catch (TransferIgnorableError $e)
		{
			$result = (object) [
				'status'    => false,
				'ignorable' => true,
				'message'   => $e->getMessage(),
			];
		}
		catch (Exception $e)
		{
			$result = (object) [
				'status'    => false,
				'message'   => $e->getMessage(),
				'ignorable' => false,
			];
		}

		@ob_end_clean();
		echo '###' . json_encode($result) . '###';
		$this->container->platform->closeApplication();
	}

	/**
	 * Initialise the upload: sends Kickstart and our add-on script to the remote server
	 *
	 * @return  void
	 */
	public function initialiseUpload()
	{
		$result = (object)[
			'status'    => true,
			'message'   => '',
			'ignorable' => false,
		];

		/** @var \Akeeba\Backup\Admin\Model\Transfer $model */
		$model = $this->getModel();

		try
		{
			$config = $model->getFtpConfig();
			$model->initialiseUpload($config);
		}
		catch (TransferIgnorableError $e)
		{
			$result = (object) [
				'status'    => false,
				'message'   => $e->getMessage(),
				'ignorable' => true,
			];
		}
		catch (Exception $e)
		{
			$result = (object)[
				'status'    => false,
				'message'   => $e->getMessage(),
				'ignorable' => false,
			];
		}

		@ob_end_clean();
		echo '###' . json_encode($result) . '###';
		$this->container->platform->closeApplication();
	}

	/**
	 * Perform an upload step. Pass start=1 to reset the upload and start over.
	 *
	 * @return  void
	 */
	public function upload()
	{
		/** @var \Akeeba\Backup\Admin\Model\Transfer $model */
		$model = $this->getModel();

		if ($this->input->getBool('start', false))
		{
			$model->resetUpload();
		}

		try
		{
			$config = $model->getFtpConfig();
			$uploadResult = $model->uploadChunk($config);
		}
		catch (Exception $e)
		{
			$uploadResult = (object)[
				'status'    => false,
				'message'   => $e->getMessage(),
				'totalSize' => 0,
				'doneSize'  => 0,
				'done'      => false
			];
		}

		$result = (object)$uploadResult;

		@ob_end_clean();
		echo '###' . json_encode($result) . '###';
		$this->container->platform->closeApplication();
	}
}
