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
use Akeeba\Component\AkeebaBackup\Administrator\Model\ConfigurationwizardModel;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Input\Input;

class ConfigurationwizardController extends BaseController
{
	use ControllerEventsTrait;
	use ControllerCustomACLTrait;
	use ControllerRegisterTasksTrait;

	private bool $noFlush = false;

	public function __construct(
		$config = [], ?MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null
	)
	{
		parent::__construct($config, $factory, $app, $input);

		$this->noFlush = ComponentHelper::getParams('com_akeebabackup')->get('no_flush', 0) == 1;

		$this->registerControllerTasks('main');
	}

	public function main($cachable = false, $urlparams = [])
	{
		$this->display($cachable, $urlparams);
	}

	public function ajax($cachable = false, $urlparams = [])
	{
		// Anti-CSRF protection. The token is sent by akeebabackup.System.doAjax() in the POST body.
		$this->checkToken();

		/** @var ConfigurationwizardModel $model */
		$model = $this->getModel('Configurationwizard', 'Administrator');
		$model->setState('act', $this->input->getCmd('act', ''));
		$ret = $model->runAjax();

		@ob_end_clean();
		echo '###' . json_encode($ret) . '###';

		if (!$this->noFlush)
		{
			flush();
		}

		Factory::getApplication()->close();
	}
}