<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die();

use FOF40\Container\Container;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;

// Old PHP version detected. EJECT! EJECT! EJECT!
if (!version_compare(PHP_VERSION, '7.2.0', '>='))
{
	return;
}

// Make sure Akeeba Backup is installed
if (!file_exists(JPATH_ADMINISTRATOR . '/components/com_akeeba'))
{
	return;
}

// Load FOF if not already loaded
if (!defined('FOF40_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof40/include.php'))
{
	return;
}

/*
 * Hopefully, if we are still here, the site is running on at least PHP5. This means that
 * including the Akeeba Backup factory class will not throw a White Screen of Death, locking
 * the administrator out of the back-end.
 */

// Make sure Akeeba Backup is installed, or quit
$akeeba_installed = @file_exists(JPATH_ADMINISTRATOR . '/components/com_akeeba/BackupEngine/Factory.php');

if (!$akeeba_installed)
{
	return;
}

// Make sure Akeeba Backup is enabled
if (!ComponentHelper::isEnabled('com_akeeba'))
{
	return;
}

class plgSystemBackuponupdate extends CMSPlugin
{
	/** @var \Joomla\CMS\Application\AdministratorApplication */
	public $app;

	private $isEnabled;

	/**
	 * Constructor
	 *
	 * @param   object  $subject  The object to observe
	 * @param   array   $config   An array that holds the plugin configuration
	 *
	 * @since   3.8.0
	 */
	public function __construct(&$subject, $config)
	{
		/**
		 * I know that this piece of code cannot possibly be executed since I have already returned BEFORE declaring
		 * the class when eAccelerator is detected. However, eAccelerator is a GINORMOUS, STINKY PILE OF BULL CRAP. The
		 * stupid thing will return above BUT it will also declare the class EVEN THOUGH according to how PHP works
		 * this part of the code should be unreachable o_O Therefore I have to define this constant and exit the
		 * constructor when we have already determined that this class MUST NOT be defined. Because screw you
		 * eAccelerator, that's why.
		 */
		if (defined('AKEEBA_EACCELERATOR_IS_SO_BORKED_IT_DOES_NOT_EVEN_RETURN'))
		{
			return;
		}

		parent::__construct($subject, $config);
	}

	/**
	 * Runs on application initialization. Implements the functionality of this plugin.
	 *
	 * @return  void
	 * @since   3.8.0
	 */
	public function onAfterInitialise()
	{
		// Make sure this is the back-end
		try
		{
			$app = Factory::getApplication();
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
		$user = JFactory::getUser();

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
		$ji          = Factory::getApplication()->input;
		$toggleParam = $ji->getCmd('_akeeba_backup_on_update_toggle');

		if ($toggleParam && ($toggleParam == Factory::getSession()->getToken()))
		{
			$this->toggleBoUFlag();

			$uri = Uri::getInstance();
			$uri->delVar('_akeeba_backup_on_update_toggle');

			$this->app->redirect($uri->toString());

			return;
		}

		// Get the input variables
		$component = $ji->getCmd('option', '');
		$task      = $ji->getCmd('task', '');
		$backedup  = ((int) $ji->getInt('is_backed_up', 0)) === 1;

		// Conditionally display the Backup on Update message
		$this->conditionallyEnqueueMessage($component, $task);

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

			$jtoken = Factory::getSession()->getFormToken();

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
				'option'      => 'com_akeeba',
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

	/**
	 * Load a plugin layout file. These files can be overridden with standard Joomla! template overrides.
	 *
	 * @param   string  $layout  The layout file to load
	 * @param   array   $params  An array passed verbatim to the layout file as the `$params` variable
	 *
	 * @return  string  The rendered contents of the file
	 *
	 * @since   5.4.1
	 */
	private function loadTemplate($layout, array $params = []): string
	{
		$file = PluginHelper::getLayoutPath('system', 'backuponupdate', $layout);

		ob_start();

		require_once $file;

		$ret = ob_get_clean();

		return $ret;
	}

	/**
	 * Get the Backup on Update flag
	 *
	 * @return  int
	 * @since   5.5.0
	 */
	private function getBoUFlag(): int
	{
		$container = Container::getInstance('com_akeeba', ['tempInstance' => 1]);

		return (int) $container->platform->getSessionVar('active', 1, 'plg_system_backuponupdate');
	}

	/**
	 * Toggle the Backup on Update flag
	 *
	 * @return  void
	 * @since   5.5.0
	 */
	private function toggleBoUFlag(): void
	{
		$container = Container::getInstance('com_akeeba', ['tempInstance' => 1]);
		$status    = 1 - $this->getBoUFlag();

		$container->platform->setSessionVar('active', $status, 'plg_system_backuponupdate');
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

		$this->isEnabled = false;

		if (!version_compare(PHP_VERSION, '7.2.0', '>='))
		{
			return false;
		}

		// Make sure Akeeba Backup is installed
		if (!file_exists(JPATH_ADMINISTRATOR . '/components/com_akeeba'))
		{
			return false;
		}

		// Is Akeeba Backup enabled?
		try
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select($db->qn('enabled'))
				->from($db->qn('#__extensions'))
				->where($db->qn('element') . ' = ' . $db->q('com_akeeba'))
				->where($db->qn('type') . ' = ' . $db->q('component'));
			$db->setQuery($query);
			$enabled         = $db->loadResult();
			$this->isEnabled = is_null($enabled) ? false : ((bool) $enabled);
		}
		catch (Exception $e)
		{
			$this->isEnabled = false;
		}

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
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select($db->qn('extension_id'))
				->from($db->qn('#__extensions'))
				->where($db->qn('name') . ' = ' . $db->q('files_joomla'));

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
			$db           = Factory::getDbo();
			$query        = $db->getQuery(true)
				->select('*')
				->from($db->quoteName('#__updates'))
				->where($db->quoteName('extension_id') . ' = ' . $db->quote($jEid));
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
	 * Pre
	 *
	 * @param $description
	 *
	 * @return string|string[]
	 */
	private function preprocessDescription(string $description): string
	{
		$replacements = [
			'[VERSION_FROM]' => JVERSION,
			'[VERSION_TO]'   => $this->getLatestJoomlaVersion(),
		];

		return str_replace(array_keys($replacements), array_values($replacements), $description);
	}

	private function conditionallyEnqueueMessage(string $component, string $task): void
	{
		// Only show the message in Joomla! Update's main view
		if (($component !== 'com_joomlaupdate') || (!empty($task) && (strpos($task, 'update.') === 0)))
		{
			return;
		}

		$this->loadLanguage('plg_system_backuponupdate');

		$willBackup  = $this->getBoUFlag() === 1;
		$infoType    = version_compare(JVERSION, '3.999.999', 'gt') ? 'success' : 'info';
		$messageType = $willBackup ? $infoType : 'warning';

		$uri = Uri::getInstance();
		$uri->setVar('_akeeba_backup_on_update_toggle', $this->app->getSession()->getToken());

		$message =
			'<h3>' .
			Text::_('PLG_SYSTEM_BACKUPONUPDATE_LBL_TITLE') .
			'</h3>' .
			'<p>' .
			Text::_('PLG_SYSTEM_BACKUPONUPDATE_LBL_CONTENT_' . ($willBackup ? 'ACTIVE' : 'INACTIVE')) .
			'</p>' .
			sprintf(
				'<p><a href="%s" class="btn btn-%s">%s</a></p>',
				$uri->toString(),
				$willBackup ? 'danger' : 'primary',
				Text::_('PLG_SYSTEM_BACKUPONUPDATE_LBL_TOGGLE_' . ($willBackup ? 'DEACTIVATE' : 'ACTIVATE'))) .
			'<p class="text-muted"><em>' .
			Text::_('PLG_SYSTEM_BACKUPONUPDATE_LBL_CONTENT_TIP') .
			'</em></p>';

		$this->app->enqueueMessage($message, $messageType);
	}
}
