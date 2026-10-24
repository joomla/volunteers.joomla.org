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
use Akeeba\Component\AkeebaBackup\Administrator\Model\DiscoverModel;
use Akeeba\Engine\Factory;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Router\Route;
use Joomla\Input\Input;

class DiscoverController extends BaseController
{
	use ControllerEventsTrait;
	use ControllerCustomACLTrait;
	use ControllerReusableModelsTrait;
	use ControllerRegisterTasksTrait;

	public function __construct($config = [], ?MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

		$this->registerControllerTasks();
	}

	public function main()
	{
		$this->display(false);
	}

	/**
	 * Discovers JPA, JPS and ZIP files in the selected profile's directory and
	 * lets you select them for inclusion in the import process.
	 */
	public function discover()
	{
		$this->checkToken();

		$directory = $this->input->get('directory', '', 'string');

		if (empty($directory))
		{
			$url = Route::_('index.php?option=com_akeebabackup&view=Discover', false);
			$msg = Text::_('COM_AKEEBABACKUP_DISCOVER_ERROR_NODIRECTORY');

			$this->setRedirect($url, $msg, 'error');

			return;
		}

		$directory = Factory::getFilesystemTools()->translateStockDirs($directory);

		/** @var DiscoverModel $model */
		$model = $this->getModel('Discover', 'Administrator');
		$model->setState('directory', $directory);

		$this->display(false);
	}

	/**
	 * Performs the actual import and redirects to the appropriate page
	 */
	public function import()
	{
		$this->checkToken();

		$directory = $this->input->get('directory', '', 'string');
		$files     = $this->input->get('files', [], 'array');

		if (empty($files))
		{
			$url = Route::_('index.php?option=com_akeebabackup&view=Discover', false);
			$msg = Text::_('COM_AKEEBABACKUP_DISCOVER_ERROR_NOFILESSELECTED');

			$this->setRedirect($url, $msg, 'error');

			return;
		}

		$directory = Factory::getFilesystemTools()->translateStockDirs($directory);

		/** @var DiscoverModel $model */
		$model = $this->getModel('Discover', 'Administrator');
		$model->setState('directory', $directory);

		foreach ($files as $file)
		{
			$id = $model->import($file);

			if (!empty($id))
			{
				$this->triggerEvent('onSuccessfulImport', [$id]);
			}
		}

		$url = Route::_('index.php?option=com_akeebabackup&view=Manage', false);
		$msg = Text::_('COM_AKEEBABACKUP_DISCOVER_LABEL_IMPORTDONE');

		$this->setRedirect($url, $msg);
	}
}