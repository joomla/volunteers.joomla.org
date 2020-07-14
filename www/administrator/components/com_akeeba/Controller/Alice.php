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
use Exception;
use FOF30\Container\Container;
use FOF30\Controller\Controller;
use FOF30\Timer\Timer;
use Joomla\CMS\Language\Text;
use RuntimeException;

/**
 * ALICE log analyzer controller
 */
class Alice extends Controller
{
	use CustomACL, PredefinedTaskList;

	public function __construct(Container $container, array $config)
	{
		parent::__construct($container, $config);

		$this->setPredefinedTaskList([
			'main', 'start', 'step', 'result', 'error',
		]);
	}

	/**
	 * Start scanning the log file. Calls step().
	 *
	 * @throws Exception
	 * @see  step()
	 *
	 */
	public function start()
	{
		// Make sure we have an anti-CSRF token
		$this->csrfProtection = 3;
		$this->csrfProtection();

		// Reset the model state and tell which log file we'll be scanning
		/** @var \Akeeba\Backup\Admin\Model\Alice $model */
		$model = $this->getModel();
		$model->reset($this->input->get('log', '', 'cmd'));

		// Run the first step.
		$this->step();
	}

	public function step()
	{
		// Make sure we have an anti-CSRF token
		$this->csrfProtection = 3;
		$this->csrfProtection();

		// Run a scanner step
		/** @var \Akeeba\Backup\Admin\Model\Alice $model */
		$model = $this->getModel();
		$timer = new Timer(4, 75);

		try
		{
			$finished = $model->analyze($timer);
		}
		catch (Exception $e)
		{
			// Error in the scanner: show the error page
			$this->container->platform->setSessionVar('aliceException', $e, 'akeeba');
			$this->setRedirect('index.php?option=com_akeeba&view=Alice&task=error');

			return;
		}

		if ($finished)
		{
			$this->getView()->setLayout('result');
			$this->doTask = 'result';
			$this->display(false, false);

			return;
		}

		$this->getView()->setLayout('step');
		$this->display(false, false);
	}

	public function result()
	{
		$this->getView()->setLayout('result');
		$this->display(false, false);
	}

	public function error()
	{
		// Don't use CRSF protection here. We check whether we have an error exception to display.
		$exception = $this->container->platform->getSessionVar('aliceException', null, 'akeeba');

		if (!is_object($exception) || !($exception instanceof Exception))
		{
			throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$this->getView()->setLayout('error');
		$this->display(false, false);
	}
}
