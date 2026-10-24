<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * @package     Akeeba\Component\AkeebaBackup\Administrator\Helper
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Helper;

use Akeeba\Engine\Factory;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;

defined('_JEXEC') || die;

/**
 * Workarounds for Joomla 5.0+ custom public folder
 *
 * @since       9.8.1
 */
class JoomlaPublicFolder
{
	/**
	 * Key in the `#__akeeba_common` table to use for the public folder storage.
	 *
	 * @since  9.8.1
	 */
	private const KEY = 'JPATH_PUBLIC';

	/**
	 * Internal cache of the public folder location
	 *
	 * @var    string|null
	 * @since  9.8.1
	 */
	private static ?string $publicPath = null;

	private static bool $hasCustomPublic = false;

	public static function init(): void
	{
		$app = JoomlaFactory::getApplication();

		if ($app->isClient('site') || $app->isClient('administrator') || $app->isClient('api'))
		{
			self::$hasCustomPublic = defined('JPATH_PUBLIC') && JPATH_PUBLIC !== JPATH_ROOT;
		}
		else
		{
			$public = self::getPublicFolder();

			self::$hasCustomPublic = $public !== JPATH_ROOT;
		}

		self::savePublicFolder();
		self::createPublicRootSymlinks();
	}

	/**
	 * Creates the symlinks in the public directory we need for restoration to work.
	 *
	 * It creates the symlinks:
	 * - `installation`. Allows the restoration script to execute after the initial extraction.
	 * - `administrator/components/com_akeebabackup/restore.php` (Pro version). Allows the integrated restoration to
	 * extract the backup archive.
	 *
	 * @since   9.8.1
	 */
	public static function createPublicRootSymlinks(): void
	{
		if (!self::$hasCustomPublic)
		{
			return;
		}

		$public = self::getPublicFolder();

		// Create a symlink to the installation directory, allowing the restoration to actually execute
		if (!@is_link($public . '/installation'))
		{
			@symlink(JPATH_INSTALLATION, $public . '/installation');
		}

		// Create a symlink to the restore.php file. Required for the extraction to work.
		if (
			!@file_exists($public . '/administrator/components/com_akeebabackup/restore.php')
			&& file_exists(JPATH_ADMINISTRATOR . '/components/com_akeebabackup/restore.php')
		)
		{
			@mkdir($public . '/administrator/components/com_akeebabackup', 0755, true);

			@symlink(
				JPATH_ADMINISTRATOR . '/components/com_akeebabackup/restore.php',
				$public . '/administrator/components/com_akeebabackup/restore.php',
			);
		}
	}

	/**
	 * Do we have automatic inclusion of the custom public folder under Joomla 5 or later?
	 *
	 * This returns false in the following cases:
	 * - Joomla 4, which does not have JPATH_PUBLIC
	 * - Custom site root override enabled (I don't know which site it is backing up!)
	 * - The public root is JPATH_ROOT
	 *
	 * @return  bool
	 *
	 * @since   9.8.1
	 */
	public static function hasCustomPublicFolderAutoIncluded(): bool
	{
		if (!self::$hasCustomPublic)
		{
			return false;
		}

		if (Factory::getConfiguration()->get('akeeba.platform.override_root', 0))
		{
			return false;
		}

		$publicDir = Factory::getFilesystemTools()->TranslateWinPath(JoomlaPublicFolder::getPublicFolder());
		$rootDir   = Factory::getFilesystemTools()->TranslateWinPath(JPATH_ROOT);

		return $publicDir !== $rootDir;
	}

	/**
	 * Store JPATH_PUBLIC in the database.
	 *
	 * We observed that JPATH_PUBLIC always returns JPATH_ROOT under CLI. Therefore, we need a solid way to get the
	 * correct JPATH_PUBLIC even if Joomla! core developers have forgotten about the _fifth_ official Joomla!
	 * application...
	 *
	 * @since   9.8.1
	 */
	public static function savePublicFolder(): void
	{
		// If it's Joomla! 4 (or a bad edit in defines.php) bail out fast.
		if (!self::$hasCustomPublic)
		{
			// We set this here to avoid doing an unnecessary database query.
			self::$publicPath = JPATH_ROOT;

			return;
		}

		// Bail out if we are under the CLI application. We can never have the correct JPATH_PUBLIC there.
		if (JoomlaFactory::getApplication()->isClient('cli'))
		{
			// DO NOT SET self::$publicPath HERE! We want to fall back to a database query as needed.
			return;
		}

		$currentPublic = self::getPublicFolder(true);

		// Nothing to update. Bail out.
		if ($currentPublic === JPATH_PUBLIC)
		{
			return;
		}

		// Update our internal variable to speed things up.
		self::$publicPath = JPATH_PUBLIC;

		// Remove an existing entry from the database.
		/** @var DatabaseDriver $db */
		$key   = self::KEY;
		$db    = JoomlaFactory::getContainer()->get(DatabaseInterface::class);
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->delete($db->quoteName('#__akeeba_common'))
			->where($db->quoteName('key') . ' = :key')
			->bind(':key', $key);
		try
		{
			$db->setQuery($query)->execute();
		}
		catch (\Exception $e)
		{
			return;
		}

		// Save the new entry to the database.
		$o = (object) [
			'key'   => self::KEY,
			'value' => JPATH_PUBLIC,
		];

		try
		{
			$db->insertObject('#__akeeba_common', $o);
		}
		catch (\Exception $e)
		{
			return;
		}
	}

	/**
	 * Returns the correct JPATH_PUBLIC folder, even under CLI.
	 *
	 * @param   bool  $nullIfUnset  Should I return NULL if the database key is not yet set?
	 *
	 * @return  string|null
	 *
	 * @since   9.8.1
	 */
	public static function getPublicFolder(bool $nullIfUnset = false): ?string
	{
		return self::$publicPath ??= call_user_func(
			function () use ($nullIfUnset) {
				$key = self::KEY;
				try
				{
					/** @var DatabaseDriver $db */
					$db     = JoomlaFactory::getContainer()->get(DatabaseInterface::class);
					$query  = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
						->select($db->quoteName('value'))
						->from($db->quoteName('#__akeeba_common'))
						->where($db->quoteName('key') . ' = :key')
						->bind(':key', $key);
					$result = $db->setQuery($query)->loadResult();
				}
				catch (\Exception $e)
				{
					$result = null;
				}

				return $result ?? ($nullIfUnset ? null : JPATH_ROOT);
			}
		);
	}
}