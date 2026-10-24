<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\BackupOnUpdate\Extension;

defined('_JEXEC') || die;

use Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Document\Document;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

class BackupOnUpdate extends CMSPlugin implements SubscriberInterface, DatabaseAwareInterface
{
	use DatabaseAwareTrait;

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

	private $isEnabled;

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
			'onAfterInitialise' => 'handleBackupOnUpdate',
			'onAfterDispatch'   => 'showMessage',
		];
	}

	/**
	 * This method is called when the Joomla application has just finished initialising.
	 *
	 * @param   Event  $event  The event object
	 *
	 * @return  void
	 *
	 * @since        9.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function handleBackupOnUpdate(Event $event)
	{
		// Make sure this is the back-end
		try
		{
			$app = $this->getApplication();
		}
		catch (Exception $e)
		{
			return;
		}

		if (!$app->isClient('administrator'))
		{
			return;
		}

		// Make sure we are enabled
		if (!$this->isEnabled())
		{
			return;
		}

		// Make sure a user is logged in
		$user = $this->getApplication()->getIdentity();

		if (!is_object($user) || $user->guest)
		{
			return;
		}

		// Make sure the user is a Super User
		if (!$user->authorise('core.admin'))
		{
			return;
		}

		// Handle the flag toggle through AJAX
		$jInput      = $this->getApplication()->getInput();
		$toggleParam = $jInput->getCmd('_akeeba_backup_on_update_toggle', 'INVALID');

		if ($toggleParam && ($toggleParam == $this->getApplication()->getSession()->getToken()))
		{
			$this->toggleBoUFlag();

			$uri = Uri::getInstance();
			$uri->delVar('_akeeba_backup_on_update_toggle');

			$this->getApplication()->redirect($uri->toString());

			return;
		}

		// Get the input variables
		$component = $jInput->getCmd('option', '');
		$task      = $jInput->getCmd('task', '');
		$backedup  = ((int) $jInput->getInt('is_backed_up', 0)) === 1;

		// Make sure we are active
		if ($this->getBoUFlag() != 1)
		{
			return;
		}

		// Perform a redirection on Joomla! Update download or install task, unless we have already backed up the site
		$redirectCondition = ($component == 'com_joomlaupdate') && ($task == 'update.install') && !$backedup;

		if ($redirectCondition)
		{
			// Get the backup profile ID
			$profileId = (int) $this->params->get('profileid', 1);

			if ($profileId <= 0)
			{
				$profileId = 1;
			}

			// Get the description override
			$this->loadLanguage();
			$description = $this->preprocessDescription($this->params->get(
				'description',
				Text::_('PLG_SYSTEM_BACKUPONUPDATE_DEFAULT_DESCRIPTION')
			));

			$jtoken = $this->getApplication()->getSession()->getFormToken();

			// Get the return URL
			$returnUri = new Uri(Uri::base() . 'index.php');
			$params    = [
				'option'       => 'com_joomlaupdate',
				'task'         => 'update.install',
				'is_backed_up' => 1,
				$jtoken        => 1,
			];
			array_walk($params, function ($value, $key) use (&$returnUri) {
				$returnUri->setVar($key, $value);
			});

			// Get the redirect URL
			$redirectUri = new Uri(Uri::base() . 'index.php');
			$params      = [
				'option'      => 'com_akeebabackup',
				'view'        => 'Backup',
				'autostart'   => 1,
				'returnurl'   => base64_encode($returnUri->toString()),
				'description' => urlencode($description),
				'profileid'   => $profileId,
				$jtoken       => 1,
			];
			array_walk($params, function ($value, $key) use (&$redirectUri) {
				$redirectUri->setVar($key, $value);
			});

			// Perform the redirection
			$app->redirect($redirectUri->toString());
		}
	}

	public function showMessage(Event $event): void
	{
		$jInput    = $this->getApplication()->getInput();
		$component = $jInput->getCmd('option', '');
		$controller      = $jInput->getCmd('controller', '');
		$task      = $jInput->getCmd('task', '');
		$layout    = $jInput->getCmd('layout', '');

		// Only show the message in Joomla! Update
		if ($component !== 'com_joomlaupdate')
		{
			return;
		}

		// Do not show the message in intermediate pages (Joomla! 4)
		if (in_array($task, [
			'update.download', 'update.install', 'update.finalise', 'update.cleanup', 'update.purge',
			'update.upload', 'update.captive', 'update.confirm',
		]))
		{
			return;
		}

		// Do not show the message in intermediate pages (Joomla! 5)
		if ($controller === 'update')
		{
			return;
		}

		// Do not show the message in the completion page.
		if ($layout === 'complete')
		{
			return;
		}

		$willBackup = $this->getBoUFlag() === 1;

		$path = PluginHelper::getLayoutPath('system', 'backuponupdate', $willBackup ? 'enabled' : 'disabled');
		@ob_start();
		include $path;
		$text = @ob_get_clean();

		/** @var HtmlDocument $document */
		$document = $this->getApplication()->getDocument();

		if (!$document instanceof HtmlDocument)
		{
			return;
		}

		$buffer = $document->getBuffer('component');

		$document->setBuffer(
			$text . $buffer,
			['type' => 'component']
		);
	}

	/**
	 * Get the Backup on Update flag
	 *
	 * @return  int
	 * @since   5.5.0
	 */
	private function getBoUFlag(): int
	{
		return (int) $this->getApplication()->getSession()->get('plg_system_backuponupdate.active', 1);
	}

	/**
	 * Toggle the Backup on Update flag
	 *
	 * @return  void
	 * @since   5.5.0
	 */
	private function toggleBoUFlag(): void
	{
		$this->getApplication()->getSession()->set('plg_system_backuponupdate.active', 1 - $this->getBoUFlag());
	}

	/**
	 * Should this plugin be enabled at all?
	 *
	 * @return  bool
	 * @since   7.0.0
	 */
	private function isEnabled(): bool
	{
		if (!is_null($this->isEnabled))
		{
			return $this->isEnabled;
		}

		$this->isEnabled =
			version_compare(PHP_VERSION, '7.4.0', '>=') &&
			ComponentHelper::isEnabled('com_akeebabackup');

		return $this->isEnabled;
	}

	/**
	 * Returns the version number of the latest Joomla release.
	 *
	 * It will return the string "(???)" if no Joomla update is being listed
	 *
	 * @return  string
	 * @since   7.0.0
	 */
	private function getLatestJoomlaVersion(): string
	{
		$latestVersion = '(???)';

		// Get the extension ID for Joomla! itself (the files_joomla pseudo-extension)
		try
		{
			$db            = $this->getDatabase();
			$joomlaExtName = 'files_joomla';
			$query         = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
				->select($db->qn('extension_id'))
				->from($db->qn('#__extensions'))
				->where($db->qn('name') . ' = :name')
				->bind(':name', $joomlaExtName, ParameterType::STRING);

			$jEid = $db->setQuery($query)->loadResult();
		}
		catch (Exception $e)
		{
			$jEid = 700;
		}

		if (is_null($jEid) || ($jEid <= 0))
		{
			$jEid = 700;
		}

		// Fetch the Joomla update information from the database.
		try
		{
			$db           = $this->getDatabase();
			$query        = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
				->select('*')
				->from($db->quoteName('#__updates'))
				->where($db->quoteName('extension_id') . ' = :jEID')
				->bind(':jEID', $jEid, ParameterType::INTEGER);
			$updateObject = $db->setQuery($query)->loadObject();
		}
		catch (Exception $e)
		{
			return $latestVersion;
		}

		if (is_null($updateObject))
		{
			return $latestVersion;
		}

		return $updateObject->version ?? $latestVersion;
	}

	/**
	 * Pre-process the description for the automatic backup
	 *
	 * @param   string  $description
	 *
	 * @return  string
	 */
	private function preprocessDescription(string $description): string
	{
		$replacements = [
			'[VERSION_FROM]' => JVERSION,
			'[VERSION_TO]'   => $this->getLatestJoomlaVersion(),
		];

		return str_replace(array_keys($replacements), array_values($replacements), $description);
	}
}