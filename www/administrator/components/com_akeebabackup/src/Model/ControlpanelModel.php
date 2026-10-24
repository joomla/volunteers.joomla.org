<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Model;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Helper\SecretWord;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ModelChmodTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Service\ComponentParameters;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Akeeba\Engine\Util\Complexify;
use Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserHelper;
use Joomla\Database\ParameterType;
use Joomla\Filesystem\Folder;
use Joomla\Http\HttpFactory;
use Joomla\Http\Transport\Curl as CurlTransport;
use Joomla\Http\Transport\Stream as StreamTransport;
use RuntimeException;
use stdClass;

/**
 * ControlPanel model. Generic maintenance tasks used mainly from the ControlPanel page.
 */
#[\AllowDynamicProperties]
class ControlpanelModel extends BaseDatabaseModel
{
	use ModelChmodTrait;

	protected static $systemFolders = [
		'administrator',
		'administrator/cache/',
		'administrator/components/',
		'administrator/help/',
		'administrator/includes/',
		'administrator/language/',
		'administrator/logs/',
		'administrator/manifests/',
		'administrator/modules/',
		'administrator/templates/',
		'cache/',
		'cli/',
		'components/',
		'images/',
		'includes/',
		'language/',
		'layouts/',
		'libraries/',
		'media/',
		'modules/',
		'plugins/',
		'templates/',
		'tmp/',
	];

	/**
	 * Constructor.
	 *
	 * @param   array                $config   An array of configuration options (name, state, dbo, table_path, ignore_request).
	 * @param   MVCFactoryInterface  $factory  The factory.
	 *
	 * @since   9.0.0
	 * @throws  \Exception
	 */
	public function __construct($config = [], ?MVCFactoryInterface $factory = null)
	{
		parent::__construct($config, $factory);

		$this->option = 'com_akeebabackup';
	}

	/**
	 * Gets a list of profiles which will be displayed as quick icons in the interface
	 *
	 * @return  stdClass[]  Array of objects; each has the properties `id` and `description`
	 */
	public function getQuickIconProfiles()
	{
		$db            = $this->getDatabase();
		$access_levels = JoomlaFactory::getApplication()->getIdentity()->getAuthorisedViewLevels();

		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select([
				$db->qn('id'),
				$db->qn('description'),
			])->from($db->qn('#__akeebabackup_profiles'))
			->where($db->qn('quickicon') . ' = ' . $db->q(1))
			->whereIn($db->qn('access'), $access_levels)
			->order($db->qn('id') . " ASC");

		$db->setQuery($query);

		$ret = $db->loadObjectList();

		if (empty($ret))
		{
			$ret = [];
		}

		return $ret;
	}

	/**
	 * Was the last backup a failed one? Used to apply magic settings as a means of troubleshooting.
	 *
	 * @return  bool
	 */
	public function isLastBackupFailed()
	{
		// Get the last backup record ID
		$list = Platform::getInstance()->get_statistics_list(['limitstart' => 0, 'limit' => 1]);

		if (empty($list))
		{
			return false;
		}

		$id = $list[0];

		$record = Platform::getInstance()->get_statistics($id);

		return ($record['status'] == 'fail');
	}

	/**
	 * Checks that the media permissions are oh seven double five for directories and oh six double four for files and
	 * fixes them if they are incorrect.
	 *
	 * @param   bool  $force  Forcibly check subresources, even if the parent has correct permissions
	 *
	 * @return  bool  False if we couldn't figure out what's going on
	 */
	public function fixMediaPermissions($force = false)
	{
		// Are we on Windows?
		$isWindows = (DIRECTORY_SEPARATOR == '\\');

		if (function_exists('php_uname'))
		{
			$isWindows = stristr(php_uname(), 'windows');
		}

		// No point changing permissions on Windows, as they have ACLs
		if ($isWindows)
		{
			return true;
		}

		// Check the parent permissions
		$parent      = JPATH_ROOT . '/media/com_akeebabackup';
		$parentPerms = fileperms($parent);

		// If we can't determine the parent's permissions, bail out
		if ($parentPerms === false)
		{
			return false;
		}

		// Fooling some broken file scanners.
		$ohSevenFiveFive          = 500 - 7;
		$ohFourOhSevenFiveFive    = 16000 + 900 - 23;
		$ohSixFourFour            = 450 - 30;
		$ohOneDoubleOhSixFourFour = 33000 + 200 - 12;

		// Fix the parent's permissions if required
		if (($parentPerms != $ohSevenFiveFive) && ($parentPerms != $ohFourOhSevenFiveFive))
		{
			$this->chmod($parent, $ohSevenFiveFive);
		}
		elseif (!$force)
		{
			return true;
		}

		// During development we use symlinks and we don't wanna see that big fat warning
		if (@is_link($parent))
		{
			return true;
		}

		$result = true;

		// Loop through subdirectories
		try
		{
			$folders = Folder::folders($parent, '.', 3, true);
		}
		catch (Exception $e)
		{
			$folders = [];
		}

		foreach ($folders as $folder)
		{
			$perms = fileperms($folder);

			if (($perms != $ohSevenFiveFive) && ($perms != $ohFourOhSevenFiveFive))
			{
				$result &= $this->chmod($folder, $ohSevenFiveFive);
			}
		}

		// Loop through files
		try
		{
			$files = Folder::files($parent, '.', 3, true);
		}
		catch (Exception $e)
		{
			$files = [];
		}

		foreach ($files as $file)
		{
			$perms = fileperms($file);

			if (($perms != $ohSixFourFour) && ($perms != $ohOneDoubleOhSixFourFour))
			{
				$result &= $this->chmod($file, $ohSixFourFour);
			}
		}

		return $result;
	}

	/**
	 * Checks if we should enable settings encryption and applies the change
	 *
	 * @return  void
	 */
	public function checkSettingsEncryption()
	{
		$params = ComponentHelper::getParams('com_akeebabackup');

		// Do we have a key file?
		$filename = JPATH_ADMINISTRATOR . '/components/com_akeebabackup/serverkey.php';

		if (@file_exists($filename) && is_file($filename))
		{
			// We have a key file. Do we need to disable it?
			if ($params->get('useencryption', -1) == 0)
			{
				// User asked us to disable encryption. Let's do it.
				$this->disableSettingsEncryption();
			}

			return;
		}

		if (!Factory::getSecureSettings()->supportsEncryption())
		{
			return;
		}

		if ($params->get('useencryption', -1) != 0)
		{
			// User asked us to enable encryption (or he left us with the default setting!). Let's do it.
			$this->enableSettingsEncryption();
		}
	}

	/**
	 * Updates some internal settings:
	 *
	 * - The stored URL of the site, used for the front-end backup feature (altbackup.php)
	 * - The detected Joomla! libraries path
	 * - Marks all existing profiles as configured, if necessary
	 *
	 * @param   ComponentParameters  $componentParametersService
	 *
	 * @throws Exception
	 */
	public function updateMagicParameters(ComponentParameters $componentParametersService)
	{
		$params = ComponentHelper::getParams('com_akeebabackup');

		if (!$params->get('confwiz_upgrade', 0))
		{
			$this->markOldProfilesConfigured();
		}

		$params->set('confwiz_upgrade', 1);
		$params->set('siteurl', str_replace('/administrator', '', Uri::base()));
		$params->set('jlibrariesdir', Factory::getFilesystemTools()->TranslateWinPath(JPATH_LIBRARIES));

		$componentParametersService->save($params);
	}

	/**
	 * Akeeba Backup displays a popup if your profile is not already configured by Configuration Wizard, the
	 * Configuration page or imported from the Profiles page. This bit of code makes sure that existing profiles will
	 * be marked as already configured just the FIRST time you upgrade to the new version from an old version.
	 *
	 * @return  void
	 * @throws  Exception
	 */
	public function markOldProfilesConfigured()
	{
		// Get all profiles
		$db = $this->getDatabase();

		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select([
				$db->qn('id'),
			])->from($db->qn('#__akeebabackup_profiles'))
			->order($db->qn('id') . " ASC");
		$db->setQuery($query);
		$profiles = $db->loadColumn();

		// If there is only one profile it's the default, it's a new installation, and we want you to see the Configuration Wizard
		if (count($profiles) === 1)
		{
			return;
		}

		// Save the current profile number
		$oldProfile = JoomlaFactory::getApplication()->getSession()->get('akeebabackup.profile', 1);

		// Update all profiles
		foreach ($profiles as $profile_id)
		{
			Factory::nuke();
			Platform::getInstance()->load_configuration($profile_id);
			$config = Factory::getConfiguration();
			$config->set('akeeba.flag.confwiz', 1);
			Platform::getInstance()->save_configuration($profile_id);
		}

		// Restore the old profile
		Factory::nuke();
		Platform::getInstance()->load_configuration($oldProfile);
	}

	/**
	 * Check the strength of the Secret Word for front-end and remote backups. If it is insecure return the reason it
	 * is insecure as a string. If the Secret Word is secure return an empty string.
	 *
	 * @return  string
	 */
	public function getFrontendSecretWordError()
	{
		// Is frontend backup enabled?
		$febEnabled =
			(Platform::getInstance()->get_platform_configuration_option('legacyapi_enabled', 0) != 0) ||
			(Platform::getInstance()->get_platform_configuration_option('jsonapi_enabled', 0) != 0);

		if (!$febEnabled)
		{
			return '';
		}

		$secretWord = Platform::getInstance()->get_platform_configuration_option('frontend_secret_word', '');

		try
		{
			Complexify::isStrongEnough($secretWord);
		}
		catch (RuntimeException $e)
		{
			// Ah, the current Secret Word is bad. Create a new one if necessary.
			$newSecret = JoomlaFactory::getApplication()->getSession()->get('akeebabackup.cpanel.newSecretWord', null);

			if (empty($newSecret))
			{
				$newSecret = UserHelper::genRandomPassword(32);

				JoomlaFactory::getApplication()->getSession()->set('akeebabackup.cpanel.newSecretWord', $newSecret);
			}

			return $e->getMessage();
		}

		return '';
	}

	/**
	 * Checks if the mbstring extension is installed and enabled
	 *
	 * @return  bool
	 */
	public function checkMbstring()
	{
		return function_exists('mb_strlen') && function_exists('mb_convert_encoding') &&
			function_exists('mb_substr') && function_exists('mb_convert_case');
	}

	/**
	 * Is the output directory under the configured site root?
	 *
	 * @param   string|null  $outDir  The output directory to check. NULL for the currently configured one.
	 *
	 * @return  bool  True if the output directory is under the site's web root.
	 *
	 * @since   7.0.3
	 */
	public function isOutputDirectoryUnderSiteRoot($outDir = null)
	{
		// Make sure I have an output directory to check
		$outDir = is_null($outDir) ? $this->getOutputDirectory() : $outDir;
		$outDir = @realpath($outDir);

		// If I can't reliably determine the output directory I can't figure out where it's placed in.
		if ($outDir === false)
		{
			return false;
		}

		// Get the site's root
		$siteRoot = $this->getSiteRoot();
		$siteRoot = @realpath($siteRoot);

		// If I can't reliably determine the site's root I can't figure out its relation to the output directory
		if ($siteRoot === false)
		{
			return false;
		}

		return strpos($outDir, $siteRoot) === 0;
	}

	/**
	 * Did the user set up an output directory inside a folder intended for CMS files?
	 *
	 * The idea is that this will cause trouble for two reasons. First, you are mixing user-generated with system
	 * content which might be a REALLY BAD idea in and of itself. Second, some if not all of these folders are meant to
	 * be web-accessible. I cannot possibly protect them against web access without breaking anything.
	 *
	 * @param   string|null  $outDir  The output directory to check. NULL for the currently configured one.
	 *
	 * @return  bool  True if the output directory is inside a CMS system folder
	 *
	 * @since   7.0.3
	 */
	public function isOutputDirectoryInSystemFolder($outDir = null)
	{
		// Make sure I have an output directory to check
		$outDir = is_null($outDir) ? $this->getOutputDirectory() : $outDir;
		$outDir = @realpath($outDir);

		// If I can't reliably determine the output directory I can't figure out where it's placed in.
		if ($outDir === false)
		{
			return false;
		}

		// If the directory is not under the site's root it doesn't belong to the CMS. Simple, huh?
		if (!$this->isOutputDirectoryUnderSiteRoot($outDir))
		{
			return false;
		}

		// Check if we are using the default output directory. This is always allowed.
		$stockDirs     = Platform::getInstance()->get_stock_directories();
		$defaultOutDir = realpath($stockDirs['[DEFAULT_OUTPUT]']);

		// If I can't reliably determine the default output folder I can't figure out its relation to the output folder
		if ($defaultOutDir === false)
		{
			return false;
		}

		// Get the site's root
		$siteRoot = $this->getSiteRoot();
		$siteRoot = @realpath($siteRoot);

		// If I can't reliably determine the site's root I can't figure out its relation to the output directory
		if ($siteRoot === false)
		{
			return false;
		}

		foreach ($this->getSystemFolders() as $folder)
		{
			// Is this a partial or an absolute search?
			$partialSearch = substr($folder, -1) == '/';

			clearstatcache(true);

			$absolutePath = realpath($siteRoot . '/' . $folder);

			if ($absolutePath === false)
			{
				continue;
			}

			if (!$partialSearch)
			{
				if (trim($outDir, '/\\') == trim($absolutePath, '/\\'))
				{
					return true;
				}

				continue;
			}

			// Partial search
			if (strpos($outDir, $absolutePath . DIRECTORY_SEPARATOR) === 0)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Does the output directory contain the security-enhancing files?
	 *
	 * This only checks for the presence of .htaccess, web.config, index.php, index.html and index.html but not their
	 * contents. The idea is that an advanced user may want to customise them for some reason or another.
	 *
	 * @param   string|null  $outDir  The output directory to check. NULL for the currently configured one.
	 *
	 * @return  bool  True if all of the security-enhancing files are present.
	 *
	 * @since   7.0.3
	 */
	public function hasOutputDirectorySecurityFiles($outDir = null)
	{
		// Make sure I have an output directory to check
		$outDir = is_null($outDir) ? $this->getOutputDirectory() : $outDir;
		$outDir = @realpath($outDir);

		// If I can't reliably determine the output directory I can't figure out where it's placed in.
		if ($outDir === false)
		{
			return true;
		}

		$files = [
			'.htaccess',
			'web.config',
			'index.php',
			'index.html',
			'index.htm',
		];

		foreach ($files as $file)
		{
			$filePath = $outDir . '/' . $file;

			if (!@file_exists($filePath) || !is_file($filePath))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Checks whether the given output directory is directly accessible over the web.
	 *
	 * @param   string|null  $outDir  The output directory to check. NULL for the currently configured one.
	 *
	 * @return  array
	 *
	 * @since   7.0.3
	 */
	public function getOutputDirectoryWebAccessibleState($outDir = null)
	{
		$ret = [
			'readFile'   => false,
			'listFolder' => false,
			'isSystem'   => $this->isOutputDirectoryInSystemFolder(),
			'hasRandom'  => $this->backupFilenameHasRandom(),
		];

		// Make sure I have an output directory to check
		$outDir = is_null($outDir) ? $this->getOutputDirectory() : $outDir;
		$outDir = @realpath($outDir);

		// If I can't reliably determine the output directory I can't figure out its web path
		if ($outDir === false)
		{
			return $ret;
		}

		$checkFile     = $this->getAccessCheckFile($outDir);
		$checkFilePath = $outDir . '/' . $checkFile;

		if (is_null($checkFile))
		{
			return $ret;
		}

		$webPath = $this->getOutputDirectoryWebPath($outDir);

		if (is_null($webPath))
		{
			@unlink($checkFilePath);

			return $ret;
		}

		// Construct a URL for the check file
		$baseURL = rtrim(Uri::base(), '/');

		if (substr($baseURL, -14) == '/administrator')
		{
			$baseURL = substr($baseURL, 0, -14);
		}

		$baseURL  = rtrim($baseURL, '/');
		$checkURL = $baseURL . '/' . $webPath . '/' . $checkFile;

		// Try to download the file's contents
		$options = [
			'follow_location'  => true,
			'transport.curl'   => [
				CURLOPT_SSL_VERIFYPEER => 0,
				CURLOPT_SSL_VERIFYHOST => 0,
				CURLOPT_FOLLOWLOCATION => 1,
				CURLOPT_TIMEOUT        => 10,
			],
			'transport.stream' => [
				'timeout' => 10,
			],
		];

		$adapters = [];

		if (CurlTransport::isSupported())
		{
			$adapters[] = 'Curl';
		}

		if (StreamTransport::isSupported())
		{
			$adapters[] = 'Stream';
		}

		if (empty($adapters))
		{
			return $ret;
		}

		$downloader = (new HttpFactory())->getHttp($options, $adapters);

		if ($downloader === false)
		{
			return $ret;
		}

		$response = $downloader->get($checkURL);
		$body     = (string) $response->getBody();

		if ($body === 'AKEEBA BACKUP WEB ACCESS CHECK')
		{
			$ret['readFile'] = true;
		}

		// Can I list the directory contents?
		$folderURL     = $baseURL . '/' . $webPath . '/';
		$folderListing = $downloader->get($folderURL)->body;

		@unlink($checkFilePath);

		if (!is_null($folderListing) && (strpos($folderListing, basename($checkFile, '.txt')) !== false))
		{
			$ret['listFolder'] = true;
		}

		return $ret;
	}

	/**
	 * Get the web path, relative to the site's root, for the output directory.
	 *
	 * Returns the relative path or NULL if determining it was not possible.
	 *
	 * @param   string|null  $outDir  The output directory to check. NULL for the currently configured one.
	 *
	 * @return  string|null  The relative web path to the output directory
	 *
	 * @since   7.0.3
	 */
	public function getOutputDirectoryWebPath($outDir = null)
	{
		// Make sure I have an output directory to check
		$outDir = is_null($outDir) ? $this->getOutputDirectory() : $outDir;
		$outDir = @realpath($outDir);

		// If I can't reliably determine the output directory I can't figure out its web path
		if ($outDir === false)
		{
			return null;
		}

		// Get the site's root
		$siteRoot = $this->getSiteRoot();
		$siteRoot = @realpath($siteRoot);

		// If I can't reliably determine the site's root I can't figure out its relation to the output directory
		if ($siteRoot === false)
		{
			return null;
		}

		// The output directory is NOT under the site's root.
		if (strpos($outDir, $siteRoot) !== 0)
		{
			return null;
		}

		$relPath = trim(substr($outDir, strlen($siteRoot)), '/\\');
		$isWin   = DIRECTORY_SEPARATOR == '\\';

		if ($isWin)
		{
			$relPath = str_replace('\\', '/', $relPath);
		}

		return $relPath;
	}

	/**
	 * Get the semi-random name of a .txt file used to check the output folder's direct web access.
	 *
	 * If the file does not exist we will create it.
	 *
	 * Returns the file name or NULL if creating it was not possible.
	 *
	 * @param   string|null  $outDir  The output directory to check. NULL for the currently configured one.
	 *
	 * @return  string|null  The base name of the check file
	 *
	 * @since   7.0.3
	 */
	public function getAccessCheckFile($outDir = null)
	{
		// Make sure I have an output directory to check
		$outDir = is_null($outDir) ? $this->getOutputDirectory() : $outDir;
		$outDir = @realpath($outDir);

		// If I can't reliably determine the output directory I can't put a file in it
		if ($outDir === false)
		{
			return null;
		}

		$secureSettings = Factory::getSecureSettings();
		$something      = hash('md5', $outDir . $secureSettings->getKey());
		$fileName       = 'akaccesscheck_' . $something . '.txt';
		$filePath       = $outDir . '/' . $fileName;

		$result = @file_put_contents($filePath, 'AKEEBA BACKUP WEB ACCESS CHECK');

		return ($result === false) ? null : $fileName;
	}

	/**
	 * Does the backup filename contain the [RANDOM] variable?
	 *
	 * @return  bool
	 *
	 * @since   7.0.3
	 */
	public function backupFilenameHasRandom()
	{
		$registry     = Factory::getConfiguration();
		$templateName = $registry->get('akeeba.basic.archive_name');

		return strpos($templateName, '[RANDOM]') !== false;
	}

	/**
	 * Return the configured output directory for the currently loaded backup profile
	 *
	 * @return  string
	 * @since   7.0.3
	 */
	public function getOutputDirectory()
	{
		$registry = Factory::getConfiguration();

		return $registry->get('akeeba.basic.output_directory', '[DEFAULT_OUTPUT]', true);
	}

	/**
	 * Get the package ID of pkg_akeeba (Akeeba Backup 8), if it's still published.
	 *
	 * @return  int|null
	 *
	 * @throws  Exception
	 * @since   9.3.1
	 * @noinspection PhpUnused
	 */
	public function getAkeebaBackup8PackageId(): ?int
	{
		/** @var UpgradeModel $upgradeModel */
		$upgradeModel = $this->getMVCFactory()->createModel('Upgrade', 'Administrator');
		$upgradeModel->init();
		$id = $upgradeModel->getExtensionId('pkg_akeeba');

		if (empty($id))
		{
			return null;
		}

		try
		{
			$db    = $this->getDatabase();
			$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			            ->select($db->quoteName('enabled'))
			            ->from($db->quoteName('#__extensions'))
			            ->where($db->quoteName('extension_id') . ' = :eid')
			            ->bind(':eid', $id, ParameterType::INTEGER);

			return $db->setQuery($query)->loadResult() == 1 ? $id : null;
		}
		catch (Exception $e)
		{
			return null;
		}
	}

	/**
	 * Return the currently configured site root directory
	 *
	 * @return  string
	 * @since   7.0.3
	 */
	protected function getSiteRoot()
	{
		return Platform::getInstance()->get_site_root();
	}

	/**
	 * Return the list of system folders, relative to the site's root
	 *
	 * @return  array
	 * @since   7.0.3
	 */
	protected function getSystemFolders()
	{
		return self::$systemFolders;
	}

	/**
	 * Disables the encryption of profile settings. If the settings were already encrypted they are automatically
	 * decrypted.
	 *
	 * @return  void
	 */
	private function disableSettingsEncryption()
	{
		// Load the server key file if necessary

		$filename = JPATH_ADMINISTRATOR . '/components/com_akeebabackup/serverkey.php';
		$key      = Factory::getSecureSettings()->getKey();

		// Get the profiles information
		$db       = $this->getDatabase();
		$query    = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select([
				$db->qn('id'),
				$db->qn('configuration'),
			])
			->from($db->qn('#__akeebabackup_profiles'));
		$profiles = $db->setQuery($query)->loadObjectList();

		// Loop all profiles and decrypt their settings
		foreach ($profiles as $profile)
		{
			$id     = $profile->id;
			$config = Factory::getSecureSettings()->decryptSettings($profile->configuration, $key);
			$sql    = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
				->update($db->qn('#__akeebabackup_profiles'))
				->set($db->qn('configuration') . ' = :configuration')
				->where($db->qn('id') . ' = :id')
				->bind(':configuration', $config, ParameterType::STRING)
				->bind(':id', $id, ParameterType::INTEGER);
			$db->setQuery($sql);
			$db->execute();
		}

		// Decrypt the Secret Word settings in the database
		$params = ComponentHelper::getParams('com_akeebabackup');
		SecretWord::enforceDecrypted($params, 'frontend_secret_word', $key);

		// Finally, remove the key file
		if (!@unlink($filename))
		{
			// File::delete($filename);
		}
	}

	/**
	 * Enable the encryption of profile settings. Existing settings are automatically encrypted.
	 *
	 * @return  void
	 */
	private function enableSettingsEncryption()
	{
		$key = $this->createSettingsKey();

		if (empty($key) || ($key == false))
		{
			return;
		}

		// Get the profiles information
		$db       = $this->getDatabase();
		$query    = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select([
				$db->qn('id'),
				$db->qn('configuration'),
			])
			->from($db->qn('#__akeebabackup_profiles'));
		$profiles = $db->setQuery($query)->loadObjectList();

		if (empty($profiles))
		{
			return;
		}

		// Loop all profiles and encrypt their settings
		foreach ($profiles as $profile)
		{
			$id     = $profile->id;
			$config = Factory::getSecureSettings()->encryptSettings($profile->configuration, $key);
			$sql    = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
				->update($db->qn('#__akeebabackup_profiles'))
				->set($db->qn('configuration') . ' = :configuration')
				->where($db->qn('id') . ' = :id')
				->bind(':configuration', $config, ParameterType::STRING)
				->bind(':id', $id, ParameterType::INTEGER);
			$db->setQuery($sql);
			$db->execute();
		}
	}

	/**
	 * Creates an encryption key for the settings and saves it in the <component>/BackupEngine/serverkey.php path
	 *
	 * @return  bool|string  FALSE on failure, the encryptions key otherwise
	 */
	private function createSettingsKey()
	{
		$rawKey = random_bytes(64);
		$key    = base64_encode($rawKey);

		$filecontents = "<?php defined('AKEEBAENGINE') or die(); define('AKEEBA_SERVERKEY', '$key'); ?>";
		$filename     = JPATH_ADMINISTRATOR . '/components/com_akeebabackup/serverkey.php';

		$result = @file_put_contents($filename, $filecontents);

		if ($result === false)
		{
			// $result = File::write($filename, $filecontents);
		}

		if (!$result)
		{
			return false;
		}

		return $rawKey;
	}

	/**
	 * Do you have to issue a warning that setting the Download ID in the CORE edition has no effect?
	 *
	 * @return  bool  True if you need to show the warning
	 */
	public function mustWarnAboutDownloadIDInCore()
	{
		/** @var UpdatesModel $updateModel */
		$updateModel = $this->getMVCFactory()->createModel('Updates', 'Administrator');
		$isPro       = defined('AKEEBABACKUP_PRO') ? AKEEBABACKUP_PRO : 0;

		if ($isPro)
		{
			return false;
		}

		$dlid = $updateModel->sanitizeLicenseKey($updateModel->getLicenseKey());

		return $updateModel->isValidLicenseKey($dlid);
	}

	/**
	 * Does the user need to enter a Download ID in the component's Options page?
	 *
	 * @return  bool
	 */
	public function needsDownloadID()
	{
		/** @var UpdatesModel $updateModel */
		$updateModel = $this->getMVCFactory()->createModel('Updates', 'Administrator');

		// Do I need a Download ID?
		$isPro = defined('AKEEBABACKUP_PRO') ? AKEEBABACKUP_PRO : 0;

		if (!$isPro)
		{
			return false;
		}

		$dlid = $updateModel->sanitizeLicenseKey($updateModel->getLicenseKey());

		return !$updateModel->isValidLicenseKey($dlid);
	}
}