<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Utils;


use FOF40\Container\Container;
use Joomla\CMS\Filesystem\File;
use Joomla\Filesystem\Folder;

class ViewManifestMigration
{
	/**
	 * Migrates Joomla 4 view XML manifests into their Joomla 3 locations
	 *
	 * @param   Container  $container  The FOF 4 container of the component we'll be migrating.
	 *
	 * @return  void
	 */
	public static function migrateJoomla4MenuXMLFiles(Container $container): void
	{
		self::migrateJoomla4MenuXMLFiles_real($container->frontEndPath, $container->backEndPath);
	}

	/**
	 * Migrates Joomla 4 view XML manifests into their Joomla 3 locations
	 *
	 * @param   string  $frontendPath  Component's frontend path
	 * @param   string  $backendPath   Component's backend path
	 *
	 * @return  void
	 * @noinspection PhpUnused
	 */
	public static function migrateJoomla4MenuXMLFiles_real($frontendPath, $backendPath): void
	{
		// This only applies to Joomla 3
		if (version_compare(JVERSION, '3.999.999', 'gt'))
		{
			return;
		}

		// Map modern to legacy locations
		$maps = [
			$frontendPath . '/tmpl'          => $frontendPath . '/views',
			$frontendPath . '/ViewTemplates' => $frontendPath . '/views',
			$backendPath . '/tmpl'           => $backendPath . '/views',
			$backendPath . '/ViewTemplates'  => $backendPath . '/views',
		];

		foreach ($maps as $source => $dest)
		{
			try
			{
				self::migrateViewXMLManifests($source, $dest);
			}
			catch (\UnexpectedValueException $e)
			{
				// This means the source folder doesn't exist. No problem!
			}
		}
	}

	/**
	 * Removes the legacy `views` paths from the front- and backend of the component on Joomla 4 and later versions.
	 *
	 * @param   Container  $container
	 *
	 * @return  void
	 */
	public static function removeJoomla3LegacyViews(Container $container): void
	{
		self::removeJoomla3LegacyViews_real($container->frontEndPath, $container->backEndPath);
	}

	/**
	 * Removes the legacy `views` paths from the front- and backend of the component on Joomla 4 and later versions.
	 *
	 * @param   string  $frontendPath  Component's frontend path
	 * @param   string  $backendPath   Component's backend path
	 *
	 * @return  void
	 * @noinspection PhpUnused
	 */
	public static function removeJoomla3LegacyViews_real($frontendPath, $backendPath): void
	{
		// This only applies to Joomla 4
		if (version_compare(JVERSION, '3.999.999', 'le'))
		{
			return;
		}

		$legacyLocations = [
			$frontendPath . '/views',
			$backendPath . '/views',
		];

		foreach ($legacyLocations as $path)
		{
			if (!is_dir($path))
			{
				continue;
			}

			Folder::delete($path);
		}
	}

	/**
	 * Migrates view manifest XML files from the source to the dest folder.
	 *
	 * @param   string  $source  Source folder to scan, i.e. the `tmpl` or `ViewTemplates` folder.
	 * @param   string  $dest    Target folder to copy the files to, i.e. the legacy `views` folder.
	 */
	private static function migrateViewXMLManifests(string $source, string $dest): void
	{
		$di = new \DirectoryIterator($source);

		/** @var \DirectoryIterator $folderItem */
		foreach ($di as $folderItem)
		{
			if ($folderItem->isDot() || !$folderItem->isDir())
			{
				continue;
			}

			// Delete the metadata.xml and tmpl/*.xml files in the corresponding `views` subfolder
			$killLegacyFile   = $dest . '/' . $folderItem->getFilename() . '/metadata.xml';
			$killLegacyFolder = $dest . '/' . $folderItem->getFilename() . '/tmpl';

			if (!@is_file($killLegacyFile))
			{
				File::delete($killLegacyFile);
			}

			if (@file_exists($killLegacyFolder) && @is_dir($killLegacyFolder))
			{
				$files = Folder::files($killLegacyFolder, '\.xml$', false, true);

				if (!empty($files))
				{
					File::delete($files);
				}
			}

			$filesIterator = new \DirectoryIterator($folderItem->getPathname());

			/** @var \DirectoryIterator $fileItem */
			foreach ($filesIterator as $fileItem)
			{
				if ($fileItem->isDir())
				{
					continue;
				}

				if ($fileItem->getExtension() != 'xml')
				{
					continue;
				}

				$destPath = $dest . '/' . $folderItem->getFilename() . (($fileItem->getFilename() == 'metadata.xml') ? '' : '/tmpl');

				$destPathName = $destPath . '/' . $fileItem->getFilename();

				Folder::create($destPath);
				File::copy($fileItem->getPathname(), $destPathName);
			}
		}
	}
}