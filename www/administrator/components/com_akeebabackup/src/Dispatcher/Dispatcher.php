<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Dispatcher;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Helper\SecretWord;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\AkeebaEngineTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\TriggerEventTrait;
use Akeeba\Engine\Platform;
use Exception;
use JLoader;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Dispatcher\ComponentDispatcher;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\WebAsset\WebAssetItem;
use Joomla\Database\DatabaseAwareTrait;
use ReflectionObject;
use Throwable;

class Dispatcher extends ComponentDispatcher
{
	use TriggerEventTrait;
	use AkeebaEngineTrait;
	use DatabaseAwareTrait;

	/**
	 * The application instance
	 *
	 * @var    CMSApplication
	 * @since  4.0.0
	 */
	protected $app;

	/**
	 * The URL option for the component.
	 *
	 * @var    string
	 * @since  9.0.0
	 */
	protected $option = 'com_akeebabackup';

	protected $defaultController = 'controlpanel';

	public function dispatch()
	{
		// Check the minimum supported PHP version
		$minPHPVersion = '7.4.0';

		if (version_compare(PHP_VERSION, $minPHPVersion, 'lt'))
		{
			throw new \RuntimeException(
				sprintf(
					'Akeeba Backup requires at least PHP version %s. Your server currently uses PHP version %s. Please upgrade your PHP version.',
					$minPHPVersion, PHP_VERSION
				)
			);
		}

		// HHVM made sense in 2013. PHP 7 and later versions are a way better solution than a hybrid PHP interpreter.
		if (defined('HHVM_VERSION'))
		{
			(include_once __DIR__ . '/../../tmpl/commontemplates/hhvm.php')
			|| die('We have detected that you are running HHVM instead of PHP. This software WILL NOT WORK properly on HHVM. Please use PHP 7 or later instead.');

			return;
		}

		try
		{
			$this->triggerEvent('onBeforeDispatch');

			parent::dispatch();

			// This will only execute if there is no redirection set by the Controller
			$this->triggerEvent('onAfterDispatch');
		}
		catch (Throwable $e)
		{
			$title = 'Akeeba Backup';
			$isPro = defined(AKEEBABACKUP_PRO) ? AKEEBABACKUP_PRO : @is_dir(__DIR__ . '/../../AliceChecks');

			if (!(include_once __DIR__ . '/../../tmpl/commontemplates/errorhandler.php'))
			{
				throw $e;
			}
		}
	}

	protected function onBeforeDispatch()
	{
		// Make sure we have a version loaded
		@include_once(__DIR__ . '/../../version.php');

		if (!defined('AKEEBABACKUP_VERSION'))
		{
			define('AKEEBABACKUP_VERSION', 'dev');
			define('AKEEBABACKUP_DATE', date('Y-m-d'));
		}

		if (!defined('AKEEBABACKUP_PRO'))
		{
			define('AKEEBABACKUP_PRO', @is_dir(__DIR__ . '/../../AliceChecks'));
		}

		// Load the languages
		$this->loadLanguage();

		// Apply the view and controller from the request, falling back to the default view/controller if necessary
		$this->applyViewAndController();

		// Access control
		$this->checkAccess();

		// Load Akeeba Engine
		$this->loadAkeebaEngine($this->getDatabase(), $this->mvcFactory);

		// Load the Akeeba Engine configuration
		try
		{
			$this->loadAkeebaEngineConfiguration();
		}
		catch (Exception $e)
		{
			// Maybe the tables are not installed?
			$msg = Text::_('COM_AKEEBABACKUP_CONTROLPANEL_MSG_REBUILTTABLES');
			$this->app->enqueueMessage($msg, 'warning');
			$this->app->redirect(Uri::base(), 307);
		}

		// Prevents the "SQLSTATE[HY000]: General error: 2014" due to resource sharing with Akeeba Engine
		$this->fixPDOMySQLResourceSharing();

		// Load the utils helper library
		Platform::getInstance()->load_version_defines();
		Platform::getInstance()->apply_quirk_definitions();

		// Set up Alice's autoloader
		if (defined('AKEEBABACKUP_PRO') && AKEEBABACKUP_PRO)
		{
			JLoader::registerNamespace('Akeeba\Alice', __DIR__ . '/../../AliceChecks');
		}

		// Make sure the front-end backup Secret Word is stored encrypted
		$params = ComponentHelper::getParams($this->option);
		SecretWord::enforceEncryption($params, 'frontend_secret_word');

		$this->loadCommonStaticMedia();
	}

	/**
	 * Prevents the "SQLSTATE[HY000]: General error: 2014" due to resource sharing with Akeeba Engine.
	 *
	 * @since 7.5.2
	 */
	protected function fixPDOMySQLResourceSharing(): void
	{
		// This fix only applies to PHP 7.x, not 8.x
		if (version_compare(PHP_VERSION, '8.0', 'ge'))
		{
			return;
		}

		$dbDriver = $this->getDatabase()->getName() ?? $this->getDatabase()->name ?? 'mysql';

		if ($dbDriver !== 'pdomysql')
		{
			return;
		}

		$this->getDatabase()->disconnect();
	}

	/**
	 * Load the language.
	 *
	 * Automatically loads en-GB and the site's fallback language (if different), then merges it with the language of
	 * the current user. First tries loading languages from the site's main folders before falling back to the ones
	 * shipped with the component itself.
	 *
	 * @return  void
	 *
	 * @since   9.0.0
	 */
	protected function loadLanguage()
	{
		$jLang = $this->app->getLanguage();

		$jLang->load($this->option, JPATH_BASE, 'en-GB', true, true)
		|| $jLang->load(
			$this->option, JPATH_ADMINISTRATOR . '/components/com_akeebabackup', 'en-GB', true, true
		);

		$jLang->load($this->option, JPATH_BASE, null, true)
		|| $jLang->load(
			$this->option, JPATH_ADMINISTRATOR . '/components/com_akeebabackup', null, true
		);
	}

	protected function loadCommonStaticMedia()
	{
		// Make sure we run under a CMS application
		if (!($this->app instanceof CMSApplication))
		{
			return;
		}

		// Make sure the document is HTML
		$document = $this->app->getDocument();

		if (!($document instanceof HtmlDocument))
		{
			return;
		}

		/**
		 * Joomla applies its own version string in OUR assets which is totally dumb. It inherently assumes that third
		 * party extensions assets are only updated when Joomla itself is updated. Typical Joomla core development,
		 * can't see past its own nose. We will work around that.
		 */
		$versionModifier    = $this->app->get('debug') ? microtime() : '';
		$akeebaMediaVersion = ApplicationHelper::getHash(AKEEBABACKUP_VERSION . AKEEBABACKUP_DATE . $versionModifier);

		$webAssetManager = $document->getWebAssetManager();
		$waRegistry      = $webAssetManager->getRegistry();
		$waRegistry->get('preset', 'com_akeebabackup.common');

		$refObj  = new ReflectionObject($waRegistry);
		$refProp = $refObj->getProperty('assets');

		if (version_compare(PHP_VERSION, '8.1.0', 'lt'))
		{
			$refProp->setAccessible(true);
		}

		$registeredAssets = $refProp->getValue($waRegistry);


		foreach ($registeredAssets as $area => $assets)
		{
			$temp = [];

			/** @var WebAssetItem $assetItem */
			foreach ($assets as $key => $assetItem)
			{
				if (substr($key, 0, 17) != 'com_akeebabackup.')
				{
					$temp[$key] = $assetItem;

					continue;
				}

				$refAI      = new ReflectionObject($assetItem);
				$refVersion = $refAI->getProperty('version');

				if (version_compare(PHP_VERSION, '8.1.0', 'lt'))
				{
					$refVersion->setAccessible(true);
				}

				$refVersion->setValue($assetItem, $akeebaMediaVersion);

				$temp[$key] = $assetItem;
			}

			$registeredAssets[$area] = $temp;
			unset($temp);
		}

		$refProp->setValue($waRegistry, $registeredAssets);

		// Finally, load our 'common' preset
		$webAssetManager
			->usePreset('com_akeebabackup.common');

		if (version_compare(JVERSION, '4.999.999', 'gt'))
		{
			$webAssetManager
				->useStyle('com_akeebabackup.j5')
				->useStyle('com_akeebabackup.j5dark');
		}
	}

	private function applyViewAndController(): void
	{
		// Handle a custom default controller name
		$view       = $this->input->getCmd('view', $this->defaultController);
		$controller = $this->input->getCmd('controller', $view);
		$task       = $this->input->getCmd('task', 'main');

		// Check for a controller.task command.
		if (strpos($task, '.') !== false)
		{
			// Explode the controller.task command.
			[$controller, $task] = explode('.', $task);
		}

		$this->input->set('view', $controller);
		$this->input->set('controller', $controller);
		$this->input->set('task', $task);
	}
}