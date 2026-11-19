<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AKWarn\Extension;

defined('_JEXEC') || die;

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\Filesystem\Folder;
use Throwable;

class AKWarn extends CMSPlugin implements SubscriberInterface
{
	private const SAFE_FOLDERS = [
		'administrator',
		'api',
		'cli',
		'components',
		'files',
		'images',
		'includes',
		'language',
		'layouts',
		'libraries',
		'media',
		'modules',
		'plugins',
		'templates',
	];

	/**
	 * @inheritDoc
	 * @since 10.2.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onAfterInitialise' => 'onAfterInitialise',
			'onAfterDispatch'   => 'showMessage',
		];
	}

	/**
	 * Handles the onAfterInitialise event. Implements the file deletion action handler.
	 *
	 * @param   Event  $event  The Joomla! event we are handling.
	 *
	 * @return  void
	 * @since   10.2.0
	 */
	public function onAfterInitialise(Event $event): void
	{
		if (!$this->isAdminPanel())
		{
			return;
		}

		$jInput = $this->getApplication()->getInput();

		if ($jInput->getMethod() === 'POST' && $jInput->post->getCmd('_akeeba_backup_delete_leftovers') === '1'
		    && Session::checkToken('post'))
		{
			$this->deleteFiles();

			$this->getApplication()->redirect(Uri::getInstance()->toString());
		}
	}

	/**
	 * Handles the onAfterDispatch event. Displays a message if leftover files are found.
	 *
	 * @param   Event  $event  The Joomla! event we are handling.
	 *
	 * @return  void
	 * @since   10.2.0
	 */
	public function showMessage(Event $event): void
	{
		if (!$this->isAdminPanel())
		{
			return;
		}

		$dash = $this->getApplication()->getInput()->get('dashboard');

		if (!empty($dash))
		{
			return;
		}

		try
		{
			$leftoverFiles = $this->getLeftoverFiles();
		}
		catch (\Throwable $e)
		{
			$leftoverFiles = [];
		}

		if (empty($leftoverFiles))
		{
			return;
		}

		$this->loadLanguage();

		asort($leftoverFiles);

		$path = PluginHelper::getLayoutPath('system', 'akwarn', 'leftovers');
		@ob_start();
		include $path;
		$text = @ob_get_clean();

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
	 * Are we a Super User logged into Joomla's administrator, accessing the Control Panel page?
	 *
	 * @return  bool
	 * @since   10.2.0
	 */
	private function isAdminPanel(): bool
	{
		// Make sure this is the back-end
		try
		{
			$app = $this->getApplication();
		}
		catch (Throwable $e)
		{
			return false;
		}

		if (!$app->isClient('administrator'))
		{
			return false;
		}

		// Make sure a user is logged in
		$user = $this->getApplication()->getIdentity();

		if (!is_object($user) || $user->guest)
		{
			return false;
		}

		// Make sure the user is a Super User
		if (!$user->authorise('core.admin'))
		{
			return false;
		}

		// Make sure this is the Joomla control panel (com_cpanel)
		$option = $app->getInput()->get('option', 'com_cpanel');

		if ($option !== 'com_cpanel' && $option !== '')
		{
			return false;
		}

		return true;
	}

	/**
	 * Returns the absolute path to the site's public folder.
	 *
	 * Joomla 5.0 introduced the JPATH_PUBLIC constant; Joomla 4 has no separate public folder, so its web root is
	 * JPATH_ROOT.
	 *
	 * @return  string
	 * @since   10.5.0
	 */
	private function getPublicFolder(): string
	{
		if (version_compare(JVERSION, '5.0', 'lt'))
		{
			return JPATH_ROOT;
		}

		return JPATH_PUBLIC;
	}

	/**
	 * Return the detected leftover files.
	 *
	 * @return  array
	 * @since   10.2.0
	 */
	private function getLeftoverFiles(): array
	{
		$files = [];

		try
		{
			$di = new \DirectoryIterator($this->getPublicFolder());
		}
		catch (Throwable $e)
		{
			return [];
		}

		/** @var \DirectoryIterator $file */
		foreach ($di as $file)
		{
			try
			{
				$isDot    = $file->isDot();
				$isDir    = $file->isDir();
				$isFile   = $file->isFile();
				$pathname = $file->getPathname();
				$basename = $file->getBasename();
				$ext      = $file->getExtension();
			}
			catch (\Throwable $e)
			{
				continue;
			}

			if ($isDot)
			{
				continue;
			}

			if ($isDir)
			{

				if ($this->isInstallationFolder($pathname))
				{
					$files[] = $basename;
				}

				continue;
			}

			if (!$isFile)
			{
				continue;
			}

			$isArchive     = in_array($ext, ['zip', 'jpa', 'jps']);
			// Split archive parts: .zNN (ZIP) and .jNN (JPA, JPS); .pNN is kept for backwards compatibility
			$isArchivePart = (bool) preg_match('/^[pzj]\d{2,}$/', $ext);
			$isKickstart   = $basename === 'kickstart.php'
			                 || ($ext === 'php' && str_contains($basename, 'kickstart'));

			if ($isArchive || $isArchivePart || $isKickstart)
			{
				$files[] = $basename;
			}
		}

		return $files;
	}

	/**
	 * Delete leftover files.
	 *
	 * @return  void
	 * @since   10.2.0
	 */
	private function deleteFiles(): void
	{
		$leftoverFiles = $this->getLeftoverFiles();
		$fails         = 0;
		$deleted       = 0;

		if (empty($leftoverFiles))
		{
			return;
		}

		foreach ($leftoverFiles as $file)
		{
			$filePath = $this->getPublicFolder() . '/' . $file;

			if (!file_exists($filePath))
			{
				continue;
			}

			if (is_dir($filePath))
			{
				if (!Folder::delete($filePath))
				{
					$fails++;
				}
				else
				{
					$deleted++;
				}

				continue;
			}

			if (!@unlink($filePath))
			{
				$fails++;
			}
			else
			{
				$deleted++;
			}
		}

		if ($deleted > 0)
		{
			$this->loadLanguage();

			$this->getApplication()->enqueueMessage(
				Text::plural('PLG_SYSTEM_AKWARN_SUCCESS_DELETING_N_FILES', $deleted),
				'success'
			);
		}

		if ($fails > 0)
		{
			$this->loadLanguage();

			$this->getApplication()->enqueueMessage(
				Text::plural('PLG_SYSTEM_AKWARN_ERR_DELETING_N_FILES', $fails),
				AdministratorApplication::MSG_ERROR
			);
		}
	}

	/**
	 * Does this look like a leftover installation folder?
	 *
	 * @param   string  $folder
	 *
	 * @return  bool
	 * @since   10.2.0
	 */
	private function isInstallationFolder(string $folder): bool
	{
		/**
		 * Check for the existence of the following files and folders (covers ANGIE and BRS):
		 * index.php
		 * version.php
		 * src/Controller/AbstractSetup.php OR angie/controllers/base/main.php
		 */

		if (!@is_file($folder . '/index.php'))
		{
			return false;
		}

		if (!@is_file($folder . '/version.php'))
		{
			return false;
		}

		if (!@is_file($folder . '/src/Controller/AbstractSetup.php') && !@is_file($folder . '/angie/controllers/base/main.php'))
		{
			return false;
		}

		return true;
	}

}