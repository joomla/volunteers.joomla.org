<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Mixin;

defined('_JEXEC') || die();

use Akeeba\Component\AkeebaBackup\Administrator\Helper\PushMessages;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\DatabaseInterface;

trait AkeebaEngineTrait
{
	public function loadAkeebaEngine(?DatabaseInterface $dbo = null, ?MVCFactoryInterface $factory = null)
	{
		$app = property_exists($this, 'app') ? $this->app : JoomlaFactory::getApplication();

		if (empty($dbo) || empty($factory))
		{
			$componentExtension = $app->bootComponent('com_akeebabackup');
		}

		$factory = $factory ?? $componentExtension->getMVCFactory();
		$dbo = $dbo ?? $componentExtension->getContainer()->get(DatabaseInterface::class);

		// Load Composer dependencies
		$autoloader = require_once JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/autoload.php';

		// Necessary defines for Akeeba Engine
		if (!defined('AKEEBAENGINE'))
		{
			define('AKEEBAENGINE', 1);
		}

		if (!defined('AKEEBAROOT'))
		{
			define('AKEEBAROOT', realpath(__DIR__ . '/../../vendor/akeeba/engine/engine'));
		}

		if (!defined('AKEEBA_CACERT_PEM'))
		{
			$caCertPath = class_exists('\\Composer\\CaBundle\\CaBundle')
				? \Composer\CaBundle\CaBundle::getBundledCaBundlePath()
				: JPATH_LIBRARIES . '/src/Http/Transport/cacert.pem';

			define('AKEEBA_CACERT_PEM', $caCertPath);
		}

		// Make sure we have a profile set throughout the component's lifetime
		$profile_id = $app->getSession()->get('akeebabackup.profile', null);

		if (is_null($profile_id))
		{
			$app->getSession()->set('akeebabackup.profile', 1);
		}

		// Tell the Akeeba Engine where to load the platform from
		Platform::addPlatform('joomla', __DIR__ . '/../../platform/Joomla');

		// Apply a custom path for the encrypted settings key file
		Factory::getSecureSettings()->setKeyFilename(JPATH_ADMINISTRATOR . '/components/com_akeebabackup/serverkey.php');

		// Add our custom push notifications handler
		Factory::setPushClass(PushMessages::class);
		PushMessages::$mvcFactory = $factory;

		// !!! IMPORTANT !!! DO NOT REMOVE! This triggers Akeeba Engine's autoloader. Without it the next line fails!
		$DO_NOT_REMOVE = Platform::getInstance();

		// Set the DBO to the Akeeba Engine platform for Joomla
		Platform\Joomla::setDbDriver($dbo);
	}

	public function loadAkeebaEngineConfiguration()
	{
		$akeebaEngineConfig = Factory::getConfiguration();

		Platform::getInstance()->load_configuration();

		unset($akeebaEngineConfig);
	}
}