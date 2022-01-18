<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Admin\Dispatcher;

// Protect from unauthorized access
defined('_JEXEC') || die();

use Akeeba\Backup\Admin\Helper\SecretWord;
use Akeeba\Backup\Admin\Model\ControlPanel;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use AkeebaFEFHelper;
use FOF40\Container\Container;
use FOF40\Dispatcher\Dispatcher as BaseDispatcher;
use FOF40\Dispatcher\Mixin\ViewAliases;
use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Language\Text;

class Dispatcher extends BaseDispatcher
{
	/** @var   string  The name of the default view, in case none is specified */
	public $defaultView = 'ControlPanel';

	use ViewAliases
	{
		onBeforeDispatch as onBeforeDispatchViewAliases;
	}

	/** @var  \Akeeba\Backup\Admin\Container  The container we belong to */
	protected $container = null;

	public function __construct(Container $container, array $config)
	{
		parent::__construct($container, $config);

		$this->viewNameAliases = [
			'buadmin'        => 'Manage',
			'buadmins'       => 'Manage',
			'config'         => 'Configuration',
			'configs'        => 'Configuration',
			'confwiz'        => 'ConfigurationWizard',
			'confwizs'       => 'ConfigurationWizard',
			'confwizes'      => 'ConfigurationWizard',
			'cpanel'         => 'ControlPanel',
			'cpanels'        => 'ControlPanel',
			'dbef'           => 'DatabaseFilters',
			'dbefs'          => 'DatabaseFilters',
			'eff'            => 'IncludeFolders',
			'effs'           => 'IncludeFolders',
			'fsfilter'       => 'FileFilters',
			'fsfilters'      => 'FileFilters',
			'ftpbrowser'     => 'FTPBrowser',
			'ftpbrowsers'    => 'FTPBrowser',
			'sftpbrowser'    => 'SFTPBrowser',
			'sftpbrowsers'   => 'SFTPBrowser',
			'multidb'        => 'MultipleDatabases',
			'multidbs'       => 'MultipleDatabases',
			'regexdbfilter'  => 'RegExDatabaseFilters',
			'regexdbfilters' => 'RegExDatabaseFilters',
			'regexfsfilter'  => 'RegExFileFilters',
			'regexfsfilters' => 'RegExFileFilters',
			'remotefile'     => 'RemoteFiles',
			'remotefiles'    => 'RemoteFiles',
			's3import'       => 'S3Import',
			's3imports'      => 'S3Import',
		];

	}

	/**
	 * Executes before dispatching the request to the appropriate controller
	 */
	public function onBeforeDispatch()
	{
		$this->container->platform->importPlugin('akeebabackup');
		$this->container->platform->runPlugins('onComAkeebaDispatcherBeforeDispatch', []);

		$this->onBeforeDispatchViewAliases();

		// Load the FOF language
		$lang = $this->container->platform->getLanguage();
		$lang->load('lib_fof40', JPATH_ADMINISTRATOR, 'en-GB', true, true);
		$lang->load('lib_fof40', JPATH_ADMINISTRATOR, null, true, false);

		// Necessary for routing the Alice view
		$this->container->inflector->addWord('Alice', 'Alices');

		// Does the user have adequate permissions to access our component?
		if (!$this->container->platform->authorise('core.manage', 'com_akeeba'))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 404);
		}

		// FEF Renderer options. Used to load the common CSS file.
		$darkMode  = $this->container->params->get('dark_mode', -1);
		$customCss = 'media://com_akeeba/css/akeebaui.min.css';

		if ($darkMode != 0)
		{
			$customCss .= ', media://com_akeeba/css/dark.min.css';
		}

		$this->container->renderer->setOptions([
			'custom_css' => $customCss,
			'fef_dark'   => $darkMode,
		]);

		// Load Akeeba Engine
		$this->loadAkeebaEngine();

		// Load the Akeeba Engine configuration
		try
		{
			$this->loadAkeebaEngineConfiguration();
		}
		catch (\Exception $e)
		{
			// Maybe the tables are not installed?
			/** @var ControlPanel $cPanelModel */
			$cPanelModel = $this->container->factory->model('ControlPanel')->tmpInstance();

			try
			{
				$cPanelModel->checkAndFixDatabase();
			}
			catch (\RuntimeException $e)
			{
				// The update is stuck. We will display a warning in the Control Panel
			}

			$msg = Text::_('COM_AKEEBA_CONTROLPANEL_MSG_REBUILTTABLES');
			$this->container->platform->redirect('index.php', 307, $msg, 'warning');
		}

		// Prevents the "SQLSTATE[HY000]: General error: 2014" due to resource sharing with Akeeba Engine
		$this->fixPDOMySQLResourceSharing();

		// Load the utils helper library
		Platform::getInstance()->load_version_defines();
		Platform::getInstance()->apply_quirk_definitions();

		// Make sure the front-end backup Secret Word is stored encrypted
		$params = $this->container->params;
		SecretWord::enforceEncryption($params, 'frontend_secret_word');

		// Make sure we have a version loaded
		@include_once($this->container->backEndPath . '/version.php');

		if (!defined('AKEEBA_VERSION'))
		{
			define('AKEEBA_VERSION', 'dev');
			define('AKEEBA_DATE', date('Y-m-d'));
		}

		// Create a media file versioning tag
		$this->container->mediaVersion = md5(AKEEBA_VERSION . AKEEBA_DATE);

		// Perform certain functionality only in HTML tasks
		$format = $this->input->getCmd('format', 'html');

		if ($format == 'html')
		{
			// Load common Javascript files. NOTE: CSS and anything style-related is loaded by the FEF Renderer class.
			$this->loadCommonJavascript();

			// Perform common maintenance tasks
			$this->autoMaintenance();
		}

		// Set the linkbar style to Classic (Bootstrap tabs). The sidebar takes too much space and requires adding
		// manual HTML to render it...
		$this->container->renderer->setOption('linkbar_style', 'classic');
	}

	public function loadAkeebaEngine()
	{
		// Necessary defines for Akeeba Engine
		if (!defined('AKEEBAENGINE'))
		{
			define('AKEEBAENGINE', 1);
			define('AKEEBAROOT', $this->container->backEndPath . '/BackupEngine');
		}

		// Make sure we have a profile set throughout the component's lifetime
		$profile_id = $this->container->platform->getSessionVar('profile', null, 'akeeba');

		if (is_null($profile_id))
		{
			$this->container->platform->setSessionVar('profile', 1, 'akeeba');
		}

		// Load Akeeba Engine
		$basePath = $this->container->backEndPath;
		require_once $basePath . '/BackupEngine/Factory.php';
	}

	public function loadAkeebaEngineConfiguration()
	{
		Platform::addPlatform('joomla3x', $this->container->backEndPath . '/BackupPlatform/Joomla3x');
		$akeebaEngineConfig = Factory::getConfiguration();
		Platform::getInstance()->load_configuration();
		unset($akeebaEngineConfig);
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

		// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
		// !!!!! WARNING: ALWAYS GO THROUGH JFactory; DO NOT GO THROUGH $this->container->db !!!!!
		// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
		$jDbo     = JFactory::getDbo();
		$dbDriver = method_exists($jDbo, 'getName') ? ($jDbo->getName() ?? $jDbo->name ?? 'mysql') : 'mysql';

		if ($dbDriver !== 'pdomysql')
		{
			return;
		}

		/**
		 * If this Joomla 3 with Site Debug enabled I need to disable database debug. If it's enabled, Joomla sends
		 * `SET query_cache_type = 0`. However, the query_cache_type MySQL server variable has been deprecated in MySQL
		 * 5.7 abd removed in MySQL 8. The PDO driver receives the error from the MySQL database and turns it into an
		 * untrappable Fatal Error, meaning that a try/catch won't be able to catch it. Since the connection code that
		 * triggers the fatal error will be called AT THE LATEST when the request terminates and at the earliest within
		 * the Dispatcher's loading of common JavaScript (which goes through the Joomla API) this causes the Akeeba
		 * Backup component to not load. Because of the weird way PDO error handling works we don't even get a Fatal
		 * Error which would at least clue us in as to what the heck is going on! Instead we have the main Dispatcher's
		 * dispatch() method handle the fatal error exception (LOLWUT?! WHY ONLY THERE?! WHAT THE HELL PHP?!) being
		 * thrown by the PDO driver, converting the result of onBeforeDispatch to false which is interpreted as the user
		 * not having access to the component, which is the error that gets reported.
		 *
		 * Talking about running into edge cases, am I right?!
		 */
		$isJoomla3         = version_compare(JVERSION, '3.999.999', 'le');
		$isSiteDebug       = (bool) JFactory::getApplication()->get('debug', 0);
		$isMySQL8OrGreated = version_compare($jDbo->getVersion() ?? '8.0', '8', 'ge');

		if ($isJoomla3 && $isSiteDebug && $isMySQL8OrGreated)
		{
			if (!method_exists($jDbo, 'setDebug'))
			{
				return;
			}

			$jDbo->setDebug(false);
		}

		@JFactory::getDbo()->disconnect();
	}

	/**
	 * Loads the Javascript files which are common across many views of the component.
	 *
	 * @return  void
	 */
	private function loadCommonJavascript()
	{
		// Do not move: everything depends on UserInterfaceCommon
		$this->container->template->addJS('media://com_akeeba/js/UserInterfaceCommon.min.js', true, false, $this->container->mediaVersion);
	}

	/**
	 * Perform common maintenance tasks
	 *
	 * @return  void
	 */
	private function autoMaintenance()
	{
		/** @var \Akeeba\Backup\Admin\Model\ControlPanel $model */
		$model = $this->container->factory->model('ControlPanel')->tmpInstance();

		// Update the db structure if necessary (once per session at most)
		$lastVersion = $this->container->platform->getSessionVar('magicParamsUpdateVersion', null, 'akeeba');

		if ($lastVersion != AKEEBA_VERSION)
		{
			try
			{
				$model->checkAndFixDatabase();
				$this->container->platform->setSessionVar('magicParamsUpdateVersion', AKEEBA_VERSION, 'akeeba');
			}
			catch (\RuntimeException $e)
			{
				// The update is stuck. We will display a warning in the Control Panel
			}
		}

		// Update magic parameters if necessary
		$model->updateMagicParameters();
	}
}
