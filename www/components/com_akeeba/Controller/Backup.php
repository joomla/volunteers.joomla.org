<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Site\Controller;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\Backup\Site\Controller\Mixin\ActivateProfile;
use Akeeba\Backup\Site\Controller\Mixin\CustomRedirection;
use Akeeba\Backup\Site\Controller\Mixin\FrontEndPermissions;
use Akeeba\Engine\Factory;
use FOF30\Container\Container;
use FOF30\Controller\Controller;
use FOF30\Controller\Mixin\PredefinedTaskList;
use FOF30\Date\Date;
use JLoader;
use JRoute;
use JText;
use JUri;

if (!defined('AKEEBA_BACKUP_ORIGIN'))
{
	define('AKEEBA_BACKUP_ORIGIN', 'frontend');
}

/**
 * Controller for the front-end backup feature.
 *
 * The Traits used by this class offer most of the features you don't see, especially those pertaining to security:
 * PredefinedTaskList   Only allows certain tasks to be called.
 * FrontEndPermissions  Validates the secret word before running a task through checkPermissions.
 * ActivateProfile      Finds the profile specified in the URL and loads it through setProfile.
 * CustomRedirection    Provides customRedirect for HTTP redirects without dealing with CMS inconsistencies.
 */
class Backup extends Controller
{
	use PredefinedTaskList, FrontEndPermissions, ActivateProfile, CustomRedirection;

	/**
	 * Overridden constructor
	 *
	 * @param   Container  $container  The application container
	 * @param   array      $config     The configuration array
	 */
	public function __construct(Container $container, array $config)
	{
		parent::__construct($container, $config);

		$this->setPredefinedTaskList(['main', 'step']);
	}

	/**
	 * Start a front-end legacy backup
	 *
	 * @return  void
	 */
	public function main()
	{
		$this->checkPermissions();
		$this->setProfile();

		// Get the backup ID
		$backupId = $this->input->get('backupid', null, 'cmd');

		if (empty($backupId))
		{
			$backupId = null;
		}

		/** @var \Akeeba\Backup\Site\Model\Backup $model */
		$model = $this->container->factory->model('Backup')->tmpInstance();

		JLoader::import('joomla.utilities.date');
		$dateNow = new Date();

		$model->setState('tag', AKEEBA_BACKUP_ORIGIN);
		$model->setState('backupid', $backupId);
		$model->setState('description', JText::_('COM_AKEEBA_BACKUP_DEFAULT_DESCRIPTION') . ' ' . $dateNow->format(JText::_('DATE_FORMAT_LC2'), true));
		$model->setState('comment', '');

		$array = $model->startBackup();

		$backupId = $model->getState('backupid', null, 'cmd');

		$this->processEngineReturnArray($array, $backupId);
	}

	/**
	 * Step through a front-end legacy backup
	 *
	 * @return  void
	 */
	public function step()
	{
		// Setup
		$this->checkPermissions();
		$this->setProfile();

		// Get the backup ID
		$backupId = $this->input->get('backupid', null, 'cmd');

		if (empty($backupId))
		{
			$backupId = null;
		}

		/** @var \Akeeba\Backup\Site\Model\Backup $model */
		$model = $this->container->factory->model('Backup')->tmpInstance();

		$model->setState('tag', AKEEBA_BACKUP_ORIGIN);
		$model->setState('backupid', $backupId);

		$array = $model->stepBackup();

		$backupId = $model->getState('backupid', null, 'cmd');

		$this->processEngineReturnArray($array, $backupId);
	}

	/**
	 * Used by the tasks to process Akeeba Engine's return array. Depending on the result and the component options we
	 * may throw text output or send an HTTP redirection header.
	 *
	 * @param   array   $array     The return array to process
	 * @param   string  $backupId  The backup ID (used to step the backup process)
	 */
	private function processEngineReturnArray($array, $backupId)
	{
		if ($array['Error'] != '')
		{
			@ob_end_clean();
			echo '500 ERROR -- ' . $array['Error'];
			flush();

			$this->container->platform->closeApplication();
		}

		if ($array['HasRun'] == 1)
		{
			// All done
			Factory::nuke();
			Factory::getFactoryStorage()->reset();
			@ob_end_clean();
			header('Content-type: text/plain');
			header('Connection: close');
			echo '200 OK';
			flush();

			$this->container->platform->closeApplication();
		}

		$noredirect = $this->input->get('noredirect', 0, 'int');

		if ($noredirect != 0)
		{
			@ob_end_clean();
			header('Content-type: text/plain');
			header('Connection: close');
			echo "301 More work required -- BACKUPID ###$backupId###";
			flush();

			$this->container->platform->closeApplication();
		}

		$curUri  = JUri::getInstance();
		$ssl     = $curUri->isSSL() ? 1 : 0;
		$tempURL = JRoute::_('index.php?option=com_akeeba', false, $ssl);
		$uri     = new JUri($tempURL);

		$uri->setVar('view', 'Backup');
		$uri->setVar('task', 'step');
		$uri->setVar('key', $this->input->get('key', '', 'none', 2));
		$uri->setVar('profile', $this->input->get('profile', 1, 'int'));

		if (!empty($backupId))
		{
			$uri->setVar('backupid', $backupId);
		}

		// Maybe we have a multilingual site?
		$language    = $this->container->platform->getLanguage();
		$languageTag = $language->getTag();

		$uri->setVar('lang', $languageTag);

		$redirectionUrl = $uri->toString();

		$this->customRedirect($redirectionUrl);
	}
}
