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
use Akeeba\Backup\Admin\Model\Restore as RestoreModel;
use FOF30\Container\Container;
use FOF30\Controller\Controller;
use JText;

/**
 * Controller for the restoration page
 */
class Restore extends Controller
{
	use CustomACL, PredefinedTaskList;

	/**
	 * Public constructor of the Controller class.
	 *
	 * @param   Container  $container  The application container
	 * @param   array      $config     The configuration array
	 */
	public function __construct(Container $container, array $config)
	{
		parent::__construct($container, $config);

		$this->setPredefinedTaskList(['main', 'start', 'ajax']);
	}

	/**
	 * Main task, displays the main page
	 */
	public function main()
	{
		/** @var RestoreModel $model */
		$model   = $this->getModel();

		$message = $model->validateRequest();

		if ($message !== true)
		{
			$this->setRedirect('index.php?option=com_akeeba&view=Manage', $message, 'error');
			$this->redirect();

			return;
		}

		$model->setState('restorationstep', 0);

		$this->display(false, false);
	}

	/**
	 * Start the restoration
	 */
	public function start()
	{
		$this->csrfProtection();

		/** @var RestoreModel $model */
		$model   = $this->getModel();

		$model->setState('restorationstep', 1);
		$message = $model->validateRequest();

		if ($message !== true)
		{
			$this->setRedirect('index.php?option=com_akeeba&view=Manage', $message, 'error');
			$this->redirect();

			return;
		}

		$model->setState('jps_key', $this->input->get('jps_key', '', 'cmd'));
		$model->setState('procengine', $this->input->get('procengine', 'direct', 'cmd'));
		$model->setState('zapbefore', $this->input->get('zapbefore', 0, 'int'));
		$model->setState('min_exec', $this->input->get('min_exec', 0, 'int'));
		$model->setState('max_exec', $this->input->get('max_exec', 5, 'int'));
		$model->setState('ftp_host', $this->input->get('ftp_host', '', 'none', 2));
		$model->setState('ftp_port', $this->input->get('ftp_port', 21, 'int'));
		$model->setState('ftp_user', $this->input->get('ftp_user', '', 'none', 2));
		$model->setState('ftp_pass', $this->input->get('ftp_pass', '', 'none', 2));
		$model->setState('ftp_root', $this->input->get('ftp_root', '', 'none', 2));
		$model->setState('tmp_path', $this->input->get('tmp_path', '', 'none', 2));
		$model->setState('ftp_ssl', $this->input->get('usessl', 'false', 'cmd') == 'true');
		$model->setState('ftp_pasv', $this->input->get('passive', 'true', 'cmd') == 'true');

		$status = $model->createRestorationINI();

		if ($status === false)
		{
			$this->setRedirect('index.php?option=com_akeeba&view=Manage', JText::_('COM_AKEEBA_RESTORE_ERROR_CANT_WRITE'), 'error');
			$this->redirect();

			return;
		}

		$this->display(false, false);
	}

	/**
	 * Perform a step through AJAX
	 */
	public function ajax()
	{
		/** @var RestoreModel $model */
		$model   = $this->getModel();

		$ajax  = $this->input->get('ajax', '', 'cmd');
		$model->setState('ajax', $ajax);

		$ret = $model->doAjax();

		@ob_end_clean();
		echo '###' . json_encode($ret) . '###';
		flush();

		$this->container->platform->closeApplication();
	}
}
