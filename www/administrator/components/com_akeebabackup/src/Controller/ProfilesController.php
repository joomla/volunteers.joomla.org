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
use Akeeba\Component\AkeebaBackup\Administrator\Model\ProfileModel;
use Akeeba\Component\AkeebaBackup\Administrator\Model\ProfilesModel;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Input\Input;
use Joomla\Utilities\ArrayHelper;
use RuntimeException;

class ProfilesController extends AdminController
{
	use ControllerEventsTrait;
	use ControllerCustomACLTrait;

	protected $text_prefix = 'COM_AKEEBABACKUP_PROFILES';

	/**
	 * Constructor.
	 *
	 * @param   array                $config   An optional associative array of configuration settings.
	 *                                         Recognized key values include 'name', 'default_task', 'model_path', and
	 *                                         'view_path' (this list is not meant to be comprehensive).
	 * @param   MVCFactoryInterface  $factory  The factory.
	 * @param   CMSApplication       $app      The JApplication for the dispatcher
	 * @param   Input                $input    Input
	 *
	 * @since   9.0.0
	 */
	public function __construct($config = [], ?MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

		$this->registerTask('import', 'import');
		$this->registerTask('copy', 'copy');
		$this->registerTask('reset', 'reset');
	}

	/**
	 * Method to get a model object, loading it if required.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  \Joomla\CMS\MVC\Model\BaseDatabaseModel|ProfileModel  The model.
	 *
	 * @since   9.0.0
	 */
	public function getModel($name = 'Profile', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

	/**
	 * Imports an exported profile .json file
	 */
	public function import()
	{
		$this->checkToken();

		if (!$this->app->getIdentity()->authorise('akeebabackup.configure', 'com_akeebabackup'))
		{
			throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		/** @var ProfilesModel $model */
		$model = $this->getModel('Profiles', 'Administrator');

		// Get some data from the request
		$file = $this->input->files->get('importfile', [], 'array');

		if (!isset($file['name']) || empty($file['name']) || ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK)
		{
			$this->setRedirect(Uri::base() . 'index.php?option=com_akeebabackup&view=Profiles', Text::_('MSG_UPLOAD_INVALID_REQUEST'), 'error');

			return;
		}

		// Load the file data
		$data = @file_get_contents($file['tmp_name']);
		@unlink($file['tmp_name']);

		// JSON decode
		$data = json_decode($data, true);

		// Import
		$message      = Text::_('COM_AKEEBABACKUP_PROFILES_MSG_IMPORT_COMPLETE');
		$messageType  = null;
		$newProfileId = null;

		try
		{
			$newProfileId = $model->import($data);
		}
		catch (RuntimeException $e)
		{
			$message     = $e->getMessage();
			$messageType = 'error';
		}

		$this->triggerEvent('onAfterImport', [$newProfileId]);

		// Redirect back to the main page
		$this->setRedirect(Uri::base() . 'index.php?option=com_akeebabackup&view=Profiles', $message, $messageType);
	}

	public function copy()
	{
		// Check for request forgeries.
		$this->checkToken();

		// Get the input
		$pks = $this->input->post->get('cid', [], 'array');

		// Sanitize the input
		$pks = ArrayHelper::toInteger($pks);

		// Get the Profile model
		/** @var ProfileModel $model */
		$model  = $this->getModel('Profile', 'Administrator');
		$result = null;

		foreach ($pks as $pk)
		{
			$item = $model->getItem($pk);

			if ($item === false)
			{
				continue;
			}

			$data = (array) $item;

			unset($data['id']);

			try
			{
				$result = $model->save($data);
				$error  = null;
			}
			catch (\Exception $e)
			{
				$result = false;
				$error  = $e->getMessage();
			}

			if ($result === false && $error === null)
			{
				/** @deprecated 10.1.0 Only for Joomla 4 b/c. Remove in 11. */
				/** @noinspection PhpDeprecationInspection */
				$error = $model->getError();
			}

			if ($result === false)
			{
				break;
			}
		}

		$redirect = Route::_('index.php?option=com_akeebabackup&view=Profiles' . $this->getRedirectToListAppend(), false);

		if ($result === false)
		{
			// Reorder failed
			$message = Text::sprintf('JLIB_APPLICATION_ERROR_SAVE_FAILED', $error);
			$this->setRedirect($redirect, $message, 'error');

			return false;
		}

		// Copying succeeded.
		$this->setMessage(Text::_('JLIB_APPLICATION_SUCCESS_BATCH'));
		$this->setRedirect($redirect);

		return true;
	}

	public function reset()
	{
		// Check for request forgeries.
		$this->checkToken();

		// Get the input
		$pks = $this->input->post->get('cid', [], 'array');

		// Sanitize the input
		$pks = ArrayHelper::toInteger($pks);

		// Get the Profile model
		/** @var ProfileModel $model */
		$model  = $this->getModel('Profile', 'Administrator');
		$result = null;
		$count = 0;

		foreach ($pks as $pk)
		{
			try
			{
				$result = $model->resetConfiguration($pk);
				$error  = null;
			}
			catch (\Exception $e)
			{
				$result = false;
				$error  = $e->getMessage();
			}

			if ($result === false && $error === null)
			{
				/** @deprecated 10.1.0 Only for Joomla 4 b/c. Remove in 11. */
				/** @noinspection PhpDeprecationInspection */
				$error = $model->getError();
			}

			if ($result === false)
			{
				break;
			}

			$count++;
		}

		$redirect = Route::_('index.php?option=com_akeebabackup&view=Profiles' . $this->getRedirectToListAppend(), false);

		if ($result === false)
		{
			// Reorder failed
			$message = Text::sprintf('COM_AKEEBABACKUP_PROFILES_ERROR_RESET_FAILED', $error);
			$this->setRedirect($redirect, $message, 'error');

			return false;
		}

		// Copying succeeded.
		$this->setMessage(Text::plural('COM_AKEEBABACKUP_PROFILES_N_ITEMS_RESET', $count));
		$this->setRedirect($redirect);

		return true;
	}

}