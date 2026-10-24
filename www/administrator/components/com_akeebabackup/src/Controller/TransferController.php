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
use Akeeba\Component\AkeebaBackup\Administrator\Model\Exceptions\TransferIgnorableError;
use Akeeba\Component\AkeebaBackup\Administrator\Model\TransferModel;
use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Router\Route;
use Joomla\Input\Input;

class TransferController extends \Joomla\CMS\MVC\Controller\BaseController
{
	use ControllerEventsTrait;
	use ControllerCustomACLTrait;
	use ControllerReusableModelsTrait;
	use ControllerRegisterTasksTrait;

	public function __construct($config = [], ?MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

		$this->registerControllerTasks('main');
	}

	public function main()
	{
		$force   = $this->input->getInt('force', 0);
		$session = $this->app->getSession();

		/**
		 * Decide which backup record the wizard should transfer.
		 *
		 * - If a specific backup record is selected (e.g. the Transfer button in the Manage Backups page submits the
		 *   selected record as `cid[]`, or an `id` is passed directly) we use that backup record, regardless of its age.
		 * - If we are NOT forcing a reload and no record was selected (e.g. entering the wizard from the menu) we reset
		 *   to the latest backup.
		 * - When forcing a reload (the “force” links inside the wizard) we keep whatever backup was already selected.
		 */
		$backupId = $this->getSelectedBackupId();

		if ($backupId !== null)
		{
			$session->set('akeebabackup.transfer.backupId', max(0, $backupId));
		}
		elseif (!$force)
		{
			$session->set('akeebabackup.transfer.backupId', 0);
		}

		$view        = $this->getView();
		$view->force = $force;

		$this->display(false);
	}

	/**
	 * Get the backup record ID explicitly selected for transfer, or NULL if none was selected.
	 *
	 * It reads the record selected in the Manage Backups page (submitted as the `cid[]` array by the toolbar button)
	 * or, as a fallback, a backup record ID passed directly through the `id` request variable.
	 *
	 * @return  int|null
	 * @since   10.3.6
	 */
	private function getSelectedBackupId(): ?int
	{
		$cid = $this->input->get('cid', [], 'array');

		if (is_array($cid) && !empty($cid))
		{
			return (int) array_shift($cid);
		}

		$id = $this->input->get('id', null, 'int');

		return $id === null ? null : (int) $id;
	}

	/**
	 * Reset the wizard
	 *
	 * @return  void
	 */
	public function reset()
	{
		// Anti-CSRF protection. The token is appended to the wizard's reset link.
		$this->checkToken('get');

		$session = $this->app->getSession();

		$session->set('akeebabackup.transfer', null);
		$session->set('akeebabackup.transfer.backupId', null);
		$session->set('akeebabackup.transfer.url', null);
		$session->set('akeebabackup.transfer.url_status', null);
		$session->set('akeebabackup.transfer.ftpsupport', null);

		/** @var TransferModel $model */
		$model = $this->getModel();
		$model->resetUpload();

		$this->setRedirect(Route::_('index.php?option=com_akeebabackup&view=Transfer', false));
	}

	/**
	 * Cleans and checks the validity of the new site's URL
	 *
	 * @return  void
	 */
	public function checkUrl()
	{
		// Anti-CSRF protection. The token is sent by akeebabackup.System.doAjax() in the POST body.
		$this->checkToken();

		$session = $this->app->getSession();

		$url = $this->input->get('url', '', 'raw');

		/** @var TransferModel $model */
		$model  = $this->getModel();
		$result = $model->checkAndCleanUrl($url);

		$session->set('akeebabackup.transfer.url', $result['url']);
		$session->set('akeebabackup.transfer.url_status', $result['status']);

		@ob_end_clean();
		echo '###' . json_encode($result) . '###';

		$this->app->close();
	}

	/**
	 * Applies the FTP/SFTP connection information and makes some preliminary validation
	 *
	 * @return  void
	 */
	public function applyConnection()
	{
		// Anti-CSRF protection. The token is sent by akeebabackup.System.doAjax() in the POST body.
		$this->checkToken();

		$session = $this->app->getSession();

		$result = (object) [
			'status'    => true,
			'message'   => '',
			'ignorable' => false,
		];

		// Get the parameters from the request
		$transferOption = $this->input->getCmd('method', 'ftp');
		$force          = $this->input->getInt('force', 0);
		$ftpHost        = $this->input->get('host', '', 'raw');
		$ftpPort        = $this->input->getInt('port', null);
		$ftpUsername    = $this->input->get('username', '', 'raw');
		$ftpPassword    = $this->input->get('password', '', 'raw');
		$ftpPubKey      = $this->input->get('publicKey', '', 'raw');
		$ftpPrivateKey  = $this->input->get('privateKey', '', 'raw');
		$ftpPassive     = $this->input->getInt('passive', 1);
		$ftpPassiveFix  = $this->input->getInt('passive_fix', 1);
		$ftpDirectory   = $this->input->get('directory', '', 'raw');
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
		$session->set('akeebabackup.transfer.transferOption', $transferOption);
		$session->set('akeebabackup.transfer.force', $force);
		$session->set('akeebabackup.transfer.ftpHost', $ftpHost);
		$session->set('akeebabackup.transfer.ftpPort', $ftpPort);
		$session->set('akeebabackup.transfer.ftpUsername', $ftpUsername);
		$session->set('akeebabackup.transfer.ftpPassword', $ftpPassword);
		$session->set('akeebabackup.transfer.ftpPubKey', $ftpPubKey);
		$session->set('akeebabackup.transfer.ftpPrivateKey', $ftpPrivateKey);
		$session->set('akeebabackup.transfer.ftpDirectory', $ftpDirectory);
		$session->set('akeebabackup.transfer.ftpPassive', $ftpPassive ? 1 : 0);
		$session->set('akeebabackup.transfer.ftpPassiveFix', $ftpPassiveFix ? 1 : 0);
		$session->set('akeebabackup.transfer.chunkMode', $chunkMode);
		$session->set('akeebabackup.transfer.chunkSize', $chunkSize);

		/** @var TransferModel $model */
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

		$this->app->close();
	}

	/**
	 * Initialise the upload: sends Kickstart and our add-on script to the remote server
	 *
	 * @return  void
	 */
	public function initialiseUpload()
	{
		// Anti-CSRF protection. The token is sent by akeebabackup.System.doAjax() in the POST body.
		$this->checkToken();

		$result = (object) [
			'status'    => true,
			'message'   => '',
			'ignorable' => false,
		];

		/** @var TransferModel $model */
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
			$result = (object) [
				'status'    => false,
				'message'   => $e->getMessage(),
				'ignorable' => false,
			];
		}

		@ob_end_clean();

		echo '###' . json_encode($result) . '###';

		$this->app->close();
	}

	/**
	 * Perform an upload step. Pass start=1 to reset the upload and start over.
	 *
	 * @return  void
	 */
	public function upload()
	{
		// Anti-CSRF protection. The token is sent by akeebabackup.System.doAjax() in the POST body.
		$this->checkToken();

		/** @var TransferModel $model */
		$model = $this->getModel();

		if ($this->input->getBool('start', false))
		{
			$model->resetUpload();
		}

		try
		{
			$config       = $model->getFtpConfig();
			$uploadResult = $model->uploadChunk($config);
		}
		catch (Exception $e)
		{
			$uploadResult = (object) [
				'status'    => false,
				'message'   => $e->getMessage(),
				'totalSize' => 0,
				'doneSize'  => 0,
				'done'      => false,
			];
		}

		$result = (object) $uploadResult;

		@ob_end_clean();

		echo '###' . json_encode($result) . '###';

		$this->app->close();
	}
}