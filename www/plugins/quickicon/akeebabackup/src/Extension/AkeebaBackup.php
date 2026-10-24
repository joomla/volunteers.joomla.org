<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\QuickIcon\AkeebaBackup\Extension;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Model\StatisticsModel;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Document\Document;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Module\Quickicon\Administrator\Event\QuickIconsEvent;

class AkeebaBackup extends CMSPlugin implements SubscriberInterface, DatabaseAwareInterface
{
	use DatabaseAwareTrait;
	use MVCFactoryAwareTrait;

	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * The document.
	 *
	 * @var Document
	 *
	 * @since  4.0.0
	 */
	private $document;

	public function setDocument(Document $document)
	{
		$this->document = $document;
	}

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since   9.0.0
	 */
	public static function getSubscribedEvents(): array
	{
		// Only subscribe events if the component is installed and enabled
		if (!ComponentHelper::isEnabled('com_akeebabackup'))
		{
			return [];
		}

		return [
			'onGetIcons' => 'getAkeebaBackupStatus',
		];
	}

	/**
	 * This method is called when the Quick Icons module is constructing its set
	 * of icons. You can return an array which defines a single icon and it will
	 * be rendered right after the stock Quick Icons.
	 *
	 * @param   QuickIconsEvent  $event  The event object
	 *
	 * @return  void
	 *
	 * @since   9.0.0
	 */
	public function getAkeebaBackupStatus(QuickIconsEvent $event)
	{
		$context = $event->getContext();

		$user = $this->getApplication()->getIdentity();
		if ($context !== 'update_quickicon' || !$user->authorise('core.manage', 'com_installer'))
		{
			return;
		}

		// Load the Akeeba Engine, if required
		if (!defined('AKEEBAENGINE'))
		{
			// Load Composer dependencies
			$autoloader = require_once JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/autoload.php';

			// Necessary defines for Akeeba Engine
			define('AKEEBAENGINE', 1);
			define('AKEEBAROOT', JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine');

			// Make sure we have a profile set throughout the component's lifetime
			$profile_id = $this->getApplication()->getSession()->get('akeebebackup.profile');

			if (is_null($profile_id))
			{
				$this->getApplication()->getSession()->set('akeebabackup.profile', 1);
			}

			// Is Akeeba Engine available?
			$engineFactoryFile = AKEEBAROOT . '/Factory.php';

			if (!file_exists($engineFactoryFile) || !is_readable($engineFactoryFile))
			{
				return;
			}

			// Try to load the Akeeba Engine
			@include_once $engineFactoryFile;

			if (!class_exists('Akeeba\Engine\Factory'))
			{
				return;
			}

			Platform::addPlatform('joomla', JPATH_ADMINISTRATOR . '/components/com_akeebabackup/platform/Joomla');

			// Apply a custom path for the encrypted settings key file
			Factory::getSecureSettings()->setKeyFilename(JPATH_ADMINISTRATOR . '/components/com_akeebabackup/serverkey.php');

			// !!! IMPORTANT !!! DO NOT REMOVE! This triggers Akeeba Engine's autoloader. Without it the next line fails!
			$DO_NOT_REMOVE = Platform::getInstance();

			// Set the DBO to the Akeeba Engine platform for Joomla
			Platform\Joomla::setDbDriver($this->getDatabase());
		}

		// Set up the default icon
		$url = Uri::base();
		$url = rtrim($url, '/');

		$profileId = (int) $this->params->get('profileid', 1);
		$token     = $this->getApplication()->getSession()->getToken();

		if ($profileId <= 0)
		{
			$profileId = 1;
		}

		$ret = [
			'link'  => Route::_('index.php?option=com_akeebabackup&view=Backup&autostart=1&returnurl=' . base64_encode($url) . '&profileid=' . $profileId . "&$token=1"),
			'image' => 'icon-akeebabackup',
			'icon'  => '',
			'text'  => Text::_('PLG_QUICKICON_AKEEBABACKUP_OK'),
			'class' => 'success',
			'id'    => 'plg_quickicon_akeebabackup',
			'group' => 'MOD_QUICKICON_MAINTENANCE',
		];

		// Do I need to parse backup warnings?
		if ($this->params->get('enablewarning', 0) == 1)
		{
			// Do not remove; required to load the Akeeba Engine configuration
			$engineConfig = Factory::getConfiguration();
			Platform::getInstance()->load_configuration(1);

			// Get the latest backup ID
			$filters  = [
				[
					'field'   => 'tag',
					'operand' => '<>',
					'value'   => 'restorepoint',
				],
			];
			$ordering = [
				'by'    => 'backupstart',
				'order' => 'DESC',
			];

			/** @var StatisticsModel $model */
			$model  = $this->getMVCFactory()->createModel('Statistics', 'Administrator');
			$list   = $model->getStatisticsListWithMeta(false, $filters, $ordering);
			$record = null;

			if (!empty($list))
			{
				$record = (object) array_shift($list);
			}

			// Warn if there is no backup whatsoever
			$warning = is_null($record);

			// Process "failed backup" warnings, if specified
			if ((!is_null($record) && $this->params->get('warnfailed', 0) == 1))
			{
				$warning = (($record->status == 'fail') || ($record->status == 'run'));
			}

			// Process "stale backup" warnings, if necessary
			if (!$warning && !is_null($record))
			{
				$maxperiod        = $this->params->get('maxbackupperiod', 24);
				$lastBackupRaw    = $record->backupstart;
				$lastBackupObject = clone JoomlaFactory::getDate($lastBackupRaw);
				$lastBackup       = $lastBackupObject->toUnix();
				$maxBackup        = time() - $maxperiod * 3600;
				$warning          = ($lastBackup < $maxBackup);
			}

			// If we have a warning we need to update the quick icon class and text
			if ($warning)
			{
				$ret['text']  = Text::_('PLG_QUICKICON_AKEEBABACKUP_BACKUPREQUIRED');
				$ret['class'] = 'danger';
			}
		}

		// Load the CSS
		$this->document->getWebAssetManager()
			->getRegistry()->addExtensionRegistryFile('plg_quickicon_akeebabackup');
		$this->document->getWebAssetManager()
			->useStyle('plg_quickicon_akeebabackup.icons');

		// Add the icon to the result array
		$result = $event->getArgument('result', []);

		$result[] = [
			$ret,
		];

		$event->setArgument('result', $result);
	}
}