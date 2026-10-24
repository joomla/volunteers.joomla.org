<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Extension\AkeebaBackupComponent;
use Akeeba\Component\AkeebaBackup\Administrator\Helper\PushMessages;
use Akeeba\Component\AkeebaBackup\Administrator\Helper\SecretWord;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Joomla\Application\ApplicationInterface;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Database\DatabaseInterface;

/**
 * A trait to initialise the Akeeba Engine for use outside the front- and backend site application.
 *
 * @since  9.2.0
 */
trait InitialiseEngine
{
	private $componentObject;

	protected function getComponentObject(ApplicationInterface $app): AkeebaBackupComponent
	{
		if (empty($this->componentObject))
		{
			$this->componentObject = $app->bootComponent('com_akeebabackup');
		}

		return $this->componentObject;
	}

	/**
	 * Initialise the Akeeba Backup engine.
	 *
	 * @param   ApplicationInterface  $app
	 *
	 * @return  void
	 * @throws  \Exception
	 * @since   9.2.0
	 */
	protected function initialiseComponent(ApplicationInterface $app): void
	{
		if (!defined('AKEEBA_CACERT_PEM'))
		{
			$caCertPath = class_exists('\\Composer\\CaBundle\\CaBundle')
				? \Composer\CaBundle\CaBundle::getBundledCaBundlePath()
				: JPATH_LIBRARIES . '/src/Http/Transport/cacert.pem';

			define('AKEEBA_CACERT_PEM', $caCertPath);
		}


		// Load the Akeeba Backup language files
		$lang = $this->getApplication()->getLanguage();
		$lang->load('com_akeebabackup', JPATH_SITE, 'en-GB', true, true);
		$lang->load('com_akeebabackup', JPATH_SITE, null, true, false);
		$lang->load('com_akeebabackup', JPATH_ADMINISTRATOR, 'en-GB', true, true);
		$lang->load('com_akeebabackup', JPATH_ADMINISTRATOR, null, true, false);

		$componentObject = $this->getComponentObject($this->getApplication());
		$dbo = $componentObject->getContainer()->get(DatabaseInterface::class);

		// Load Akeeba Engine
		$this->loadAkeebaEngine($app, $dbo);

		// Load the Akeeba Engine configuration
		$this->loadAkeebaEngineConfiguration();


		// Prevents the "SQLSTATE[HY000]: General error: 2014" due to resource sharing with Akeeba Engine
		// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
		// !!!!! WARNING: ALWAYS GO THROUGH JFactory; DO NOT GO THROUGH $this->container->db !!!!!
		// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

		if (version_compare(PHP_VERSION, '7.999.999', 'le'))
		{
			if ($dbo->getName() == 'pdomysql')
			{
				@$dbo->disconnect();
			}
		}

		// Make sure the front-end backup Secret Word is stored encrypted
		$params = ComponentHelper::getParams('com_akeebabackup');
		SecretWord::enforceEncryption($params, 'frontend_secret_word');

		// Make sure we have a version loaded
		@include_once(JPATH_ADMINISTRATOR . '/components/com_akeebabackup/version.php');

		if (!defined('AKEEBABACKUP_VERSION'))
		{
			define('AKEEBABACKUP_VERSION', 'dev');
			define('AKEEBABACKUP_DATE', date('Y-m-d'));
		}
	}

	/**
	 * Load enough of the Akeeba Backup engine and set up the origin and profile
	 *
	 * @param   ApplicationInterface  $app
	 * @param   DatabaseInterface     $dbo
	 *
	 * @return  void
	 * @since   9.2.0
	 */
	private function loadAkeebaEngine(ApplicationInterface $app, DatabaseInterface $dbo): void
	{
		// Load Composer dependencies
		$autoloader = require_once JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/autoload.php';

		// Necessary defines for Akeeba Engine
		if (!defined('AKEEBAENGINE'))
		{
			define('AKEEBAENGINE', 1);
			define('AKEEBAROOT', JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine');
		}

		if (!defined('AKEEBA_BACKUP_ORIGIN'))
		{
			$origin = 'cli';

			if ($app instanceof CMSApplication)
			{
				$origin = $app->isClient('api') ? 'json' : $origin;
				$origin = $app->isClient('site') ? 'frontend' : $origin;
				$origin = $app->isClient('administrator') ? 'backend' : $origin;
			}

			define('AKEEBA_BACKUP_ORIGIN', $origin);
		}

		// Make sure we have a profile set throughout the component's lifetime
		$profile_id = $app->getSession()->get('akeebabackup.profile', null);

		if (is_null($profile_id))
		{
			$app->getSession()->set('akeebabackup.profile', 1);
		}

		// Load Akeeba Engine
		require_once AKEEBAROOT . '/Factory.php';

		// Tell the Akeeba Engine where to load the platform from
		Platform::addPlatform('joomla', JPATH_ADMINISTRATOR . '/components/com_akeebabackup/platform/Joomla');

		// Apply a custom path for the encrypted settings key file
		Factory::getSecureSettings()->setKeyFilename(JPATH_ADMINISTRATOR . '/components/com_akeebabackup/serverkey.php');

		// Add our custom push notifications handler
		Factory::setPushClass(PushMessages::class);

		if (method_exists($this, 'getMVCFactory'))
		{
			PushMessages::$mvcFactory = $this->getMVCFactory();
		}
		else
		{
			// This part of the code executes in Joomla Scheduled Tasks
			$comAkeebaExtension = $app->bootComponent('com_akeeba');
			try
			{
				PushMessages::$mvcFactory = method_exists($comAkeebaExtension, 'getMVCFactory')
					? $comAkeebaExtension->getMVCFactory()
					: null;
			}
			catch (\Exception $e)
			{
				// Yeah, no MVCFactory set. Don't care about push messages.
			}
		}

		// !!! IMPORTANT !!! DO NOT REMOVE! This triggers Akeeba Engine's autoloader. Without it the next line fails!
		$DO_NOT_REMOVE = Platform::getInstance();

		// Set the DBO to the Akeeba Engine platform for Joomla
		Platform\Joomla::setDbDriver($dbo);
	}

	/**
	 * Load the backup profile configuration
	 *
	 * @return  void
	 * @since   9.2.0
	 */
	private function loadAkeebaEngineConfiguration(): void
	{
		$akeebaEngineConfig = Factory::getConfiguration();

		Platform::getInstance()->load_configuration();

		unset($akeebaEngineConfig);
	}

}