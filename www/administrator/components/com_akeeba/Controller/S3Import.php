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
use Akeeba\Backup\Admin\Model\S3Import as S3ImportModel;
use FOF30\Container\Container;
use FOF30\Controller\Controller;
use JText;

class S3Import extends Controller
{
	use CustomACL, PredefinedTaskList;

	public function __construct(Container $container, array $config)
	{
		parent::__construct($container, $config);

		$this->setPredefinedTaskList(['main', 'dltoserver']);
	}

	public function main()
	{
		$s3bucket = $this->input->get('s3bucket', null, 'none', 2);

		/** @var S3ImportModel $model */
		$model = $this->getModel();

		if ($s3bucket)
		{
			$model->setState('s3bucket', $s3bucket);
		}

		$model->getS3Credentials();
		$model->setS3Credentials($model->getState('s3access'), $model->getState('s3secret'));

		$this->display(false, false);
	}

	/**
	 * Fetches a complete backup set from a remote storage location to the local (server)
	 * storage so that the user can download or restore it.
	 */
	public function dltoserver()
	{
		$s3bucket = $this->input->get('s3bucket', null, 'none', 2);

		// Get the parameters
		/** @var S3ImportModel $model */
		$model = $this->getModel();

		if ($s3bucket)
		{
			$model->setState('s3bucket', $s3bucket);
		}

		$model->getS3Credentials();
		$model->setS3Credentials($model->getState('s3access'), $model->getState('s3secret'));

		// Set up the model's state
		$part = $this->input->getInt('part', -999);

		if ($part >= -1)
		{
			$this->container->platform->setSessionVar('s3import.part', $part, 'com_akeeba');
		}

		$frag = $this->input->getInt('frag', -999);

		if ($frag >= -1)
		{
			$this->container->platform->setSessionVar('s3import.frag', $frag, 'com_akeeba');
		}

		$step = $this->input->getInt('step', -999);

		if ($step >= -1)
		{
			$this->container->platform->setSessionVar('s3import.step', $step, 'com_akeeba');
		}

		$errorMessage = '';

		try
		{
			$result = $model->downloadToServer();
		}
		catch (\RuntimeException $e)
		{
			$result = false;
			$errorMessage = $e->getMessage();
		}

		if ($result === true)
		{
			// Part(s) downloaded successfully. Render the view.
			$this->display(false, false);
		}
		elseif ($result === false)
		{
			// Part did not download. Redirect to initial page with an error.
			$this->setRedirect('index.php?option=com_akeeba&view=S3Import', $errorMessage, 'error');
		}
		else
		{
			// All done. Redirect to intial page with a success message.
			$this->setRedirect('index.php?option=com_akeeba&view=S3Import', JText::_('COM_AKEEBA_S3IMPORT_MSG_IMPORTCOMPLETE'));
		}
	}
}
