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
use Akeeba\Component\AkeebaBackup\Administrator\Model\AliceModel;
use Akeeba\Engine\Core\Timer;
use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Uri\Uri;
use Joomla\Input\Input;
use RuntimeException;

class AliceController extends BaseController
{
	use ControllerEventsTrait;
	use ControllerCustomACLTrait;
	use ControllerRegisterTasksTrait;
	use ControllerReusableModelsTrait;

	public function __construct($config = [], ?MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

		$this->registerControllerTasks('main');
	}

	protected function onBeforeMain()
	{
		$this->getView()->setModel($this->getModel('Log', 'Administrator'), false);

		$this->getModel('Alice', 'Administrator')->setState('log', $this->input->getCmd('log', null));
	}

	/**
	 * Start scanning the log file. Calls step().
	 *
	 * @throws Exception
	 * @see    step()
	 *
	 */
	public function start()
	{
		// Make sure we have an anti-CSRF token
		$this->checkToken('request');

		// Reset the model state and tell which log file we'll be scanning
		/** @var AliceModel $model */
		$model = $this->getModel('Alice', 'Administrator');
		$log   = $this->input->getCmd('log', '');

		$model->reset($log);

		// Run the first step.
		$this->step();
	}

	public function step()
	{
		// Make sure we have an anti-CSRF token
		$this->checkToken('request');

		// Run a scanner step
		/** @var AliceModel $model */
		$model = $this->getModel('Alice', 'Administrator');
		$timer = new Timer(4, 75);

		try
		{
			$finished = $model->analyze($timer);
		}
		catch (Exception $e)
		{
			// Error in the scanner: show the error page
			$this->app->getSession()->set('akeebabackup.aliceException', $e);
			$this->setRedirect(Uri::base() . 'index.php?option=com_akeebabackup&view=Alice&task=error');

			return;
		}
		finally
		{
			$model->saveStateToSession();
		}

		if ($finished)
		{
			$this->setRedirect(Uri::base() . 'index.php?option=com_akeebabackup&task=Alice.result');

			return;
		}

		$this->getView()->setLayout('step');
		$this->display(false);
	}

	public function result()
	{
		$this->getView()->setLayout('result');
		$this->display(false);
	}

	public function error()
	{
		// Don't use CRSF protection here. We check whether we have an error exception to display.
		$exception = $this->app->getSession()->get('akeebabackup.aliceException', null);

		if (!is_object($exception) || !($exception instanceof Exception))
		{
			throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$this->getView()->setLayout('error');
		$this->display(false);
	}
}