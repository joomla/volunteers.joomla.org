<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Site\Dispatcher;

// Protect from unauthorized access
defined('_JEXEC') || die();

use Akeeba\Backup\Admin\Dispatcher\Dispatcher as AdminDispatcher;
use Akeeba\Backup\Admin\Helper\SecretWord;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use FOF40\Container\Container;
use FOF40\Dispatcher\Exception\AccessForbidden;
use Joomla\CMS\Document\Document;
use Joomla\CMS\Document\JsonDocument as JDocumentJSON;
use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Language\Text;

class Dispatcher extends AdminDispatcher
{
	/** @var   string  The name of the default view, in case none is specified */
	public $defaultView = 'Backup';

	/**
	 * Dispatcher constructor. Overridden to set up a different default view and migrated views map than the back-end.
	 *
	 * @param   Container  $container  The component's container
	 * @param   array      $config     Optional configuration overrides
	 */
	public function __construct(Container $container, array $config)
	{
		parent::__construct($container, $config);

		$this->defaultView = 'Backup';

		$this->viewNameAliases = [
			'backup'  => 'Backup',
			'backups' => 'Backup',
			'check'   => 'Check',
			'checks'  => 'Check',
			'json'    => 'Json',
			'jsons'   => 'Json',
		];
	}


	/**
	 * Executes before dispatching the request to the appropriate controller
	 */
	public function onBeforeDispatch()
	{
		// Make sure we have a version loaded
		@include_once($this->container->backEndPath . '/version.php');

		if (!defined('AKEEBA_VERSION'))
		{
			define('AKEEBA_VERSION', 'dev');
			define('AKEEBA_DATE', date('Y-m-d'));
		}

		// Core version: there is no front-end, throw a 403
		if (!defined('AKEEBA_PRO') || !AKEEBA_PRO)
		{
			throw new AccessForbidden(Text::_('COM_AKEEBA_ERR_NO_FRONTEND_IN_CORE'));
		}

//		$this->container->platform->importPlugin('akeebabackup');
//		$this->container->platform->runPlugins('onComAkeebaDispatcherBeforeDispatch', []);

		$this->onBeforeDispatchViewAliases();

		// Load the FOF language
		$lang = $this->container->platform->getLanguage();
		$lang->load('lib_fof40', JPATH_SITE, 'en-GB', true, true);
		$lang->load('lib_fof40', JPATH_SITE, null, true, false);

		// Necessary defines for Akeeba Engine
		if (!defined('AKEEBAENGINE'))
		{
			define('AKEEBAENGINE', 1);
			define('AKEEBAROOT', $this->container->backEndPath . '/BackupEngine');
			define('ALICEROOT', $this->container->backEndPath . '/AliceEngine');
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

		// Load the Akeeba Engine configuration
		Platform::addPlatform('joomla3x', JPATH_COMPONENT_ADMINISTRATOR . '/BackupPlatform/Joomla3x');
		$akeebaEngineConfig = Factory::getConfiguration();
		Platform::getInstance()->load_configuration();
		unset($akeebaEngineConfig);

		// Prevents the "SQLSTATE[HY000]: General error: 2014" due to resource sharing with Akeeba Engine
		$this->fixPDOMySQLResourceSharing();

		// Load the utils helper library
		Platform::getInstance()->load_version_defines();

		// Make sure the front-end backup Secret Word is stored encrypted
		$params = $this->container->params;
		SecretWord::enforceEncryption($params, 'frontend_secret_word');

		// Create a media file versioning tag
		$this->container->mediaVersion = md5(AKEEBA_VERSION . AKEEBA_DATE);
	}

	public function onAfterDispatch()
	{
		// Make sure that Api and Json views forcibly get format=json
		if (in_array($this->view, ['Api', 'Json']))
		{
			$format = $this->input->getCmd('format', 'html');

			if ($format == 'json')
			{
				return;
			}

			$app     = JFactory::getApplication();
			$content = $app->getDocument()->getBuffer();

			var_dump($content);
			die;

			// Disable caching, disable offline, force use of index.php
			$app->set('caching', 0);
			$app->set('offline', 0);
			$app->set('themeFile', 'index.php');


			/** @var \Joomla\CMS\Document\JsonDocument $doc */
			$doc = Document::getInstance('json');

			$app->loadDocument($doc);

			if (property_exists(JFactory::class, 'document'))
			{
				JFactory::$document = $doc;
			}


			// Set a custom document name
			/** @var JDocumentJSON $document */
			$document = $this->container->platform->getDocument();
			$document->setName('akeeba_backup');

		}
	}
}
