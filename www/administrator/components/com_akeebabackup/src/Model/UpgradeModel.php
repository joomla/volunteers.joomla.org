<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Model;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Helper\DbQuery;
use DirectoryIterator;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Adapter\PackageAdapter;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\MVC\Model\BaseModel;
use Joomla\CMS\Table\Extension;
use Joomla\CMS\User\UserHelper;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use RuntimeException;
use SimpleXMLElement;
use Throwable;

/**
 * Handles post-installation and upgrade tasks.
 *
 * This model centralises code we previously had scattered around our installation script files. It handles the
 * following tasks that Joomla can't reasonably do by itself:
 *
 * - Migrating form one package name to another when some / all of the included extensions have the same names.
 * - Migrating from FOF-based to Joomla-core-MVC-based extensions.
 * - Enabling plugins and modules upon new installation to provide a better UX without any non-obvious steps.
 * - Downgrading from a Pro to a Core version of an extension.
 * - Executing custom, extension-specific upgrade tasks.
 */
#[\AllowDynamicProperties]
class UpgradeModel extends BaseModel implements DatabaseAwareInterface
{
	use DatabaseAwareTrait;

	/**
	 * Name of the package being replaced
	 *
	 * @var   string
	 */
	private const OLD_PACKAGE_NAME = 'pkg_akeeba';

	/**
	 * Name of the new package this component belongs to
	 *
	 * @var   string
	 */
	private const PACKAGE_NAME = 'pkg_akeebabackup';

	/**
	 * Criteria for determining this is the Pro version by inspecting the filesystem.
	 *
	 * Each array element is an array in itself with two elements:
	 * * 0: const|file|folder
	 * * 1: constant name; or path to the file or folder to check for existence
	 *
	 * Matching any criterion means we have the Pro version
	 *
	 * @var   array
	 */
	private const PRO_CRITERIA = [
		['const', 'AKEEBABACKUP_PRO'],
		['const', 'AKEEBABACKUP_INSTALLATION_PRO'],
	];

	/**
	 * Files and folders to remove from both Core and Pro versions
	 *
	 * @var array[]
	 */
	private const REMOVE_FROM_ALL_VERSIONS = [
		'files'   => [
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/Model/UsageStatisticsModel.php',

			// Remove iDriveSync — the service has been discontinued
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/idrivesync.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/Idrivesync.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/Connector/Idrivesync.php',

			// Remove Piecon
			JPATH_ROOT . '/media/com_akeebabackup/js/piecon.js',
			JPATH_ROOT . '/media/com_akeebabackup/js/piecon.min.js',
			JPATH_ROOT . '/media/com_akeebabackup/js/piecon.min.js.map',

			// Legacy helpers
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/Helper/CacheCleaner.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/Helper/ComponentParams.php',

			// Legacy filters
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/platform/Joomla/Filter/Stack/StackMyjoomla.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/platform/Joomla/Filter/Stack/myjoomla.json',

			// Legacy usage stats collection
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/Helper/usagestats.php',

			// Old ANGIE restoration script
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/installers/angie-generic.jpa',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/installers/angie-generic.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/installers/angie-joomla.jpa',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/installers/angie-joomla.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/installers/angie.jpa',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/installers/angie.json',

			// Old copy of Kickstart
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/installers/kickstart.txt',

			// Development cruft shipped by older builds of greenlion/php-sql-parser
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/greenlion/php-sql-parser/.buildpath',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/greenlion/php-sql-parser/.eclipse-PHP-formatter.xml',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/greenlion/php-sql-parser/.gitmodules',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/greenlion/php-sql-parser/.project',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/greenlion/php-sql-parser/.travis.yml',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/greenlion/php-sql-parser/phpunit.xml',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/greenlion/php-sql-parser/README.md',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/greenlion/php-sql-parser/runtest.sh',

			// Legacy error handler
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/tmpl/commontemplates/errorhandler.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/tmpl/commontemplates/hhvm.php',

		],
		'folders' => [
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/platform/Joomla/Finalization',

			// Legacy traits
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/Controller/Mixin',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/Dispatcher/Mixin',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/Model/Mixin',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/Table/Mixin',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/View/Mixin',

			// Legacy folders (these dependencies are now imported through Composer)
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/engine',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/webpush',

			// Development cruft shipped by older builds of greenlion/php-sql-parser
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/greenlion/php-sql-parser/.github',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/greenlion/php-sql-parser/.settings',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/greenlion/php-sql-parser/examples',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/greenlion/php-sql-parser/libs',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/greenlion/php-sql-parser/tests',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/greenlion/php-sql-parser/wiki',
		],
	];

	/**
	 * Files and folders to remove ONLY from the Core version
	 *
	 * @var array[]
	 */
	private const REMOVE_FROM_CORE = [
		'files'   => [
			// Pro engine features
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Archiver/Directftp.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Archiver/directftp.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Archiver/Directftpcurl.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Archiver/directftpcurl.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Archiver/Directsftp.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Archiver/directsftp.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Archiver/Directsftpcurl.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Archiver/directsftpcurl.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Archiver/Jps.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Archiver/jps.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Archiver/Zipnative.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Archiver/zipnative.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/amazons3.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/Amazons3.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/azure.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/Azure.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/backblaze.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/Backblaze.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/box.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/Box.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/cloudfiles.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/Cloudfiles.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/cloudme.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/Cloudme.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/dreamobjects.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/Dreamobjects.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/dropbox.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/Dropbox.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/dropbox2.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/Dropbox2.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/ftp.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/Ftp.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/ftpcurl.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/Ftpcurl.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/googledrive.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/Googledrive.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/googlestorage.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/Googlestorage.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/googlestoragejson.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/Googlestoragejson.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/idrivesync.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/Idrivesync.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/onedrive.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/Onedrive.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/onedriveapp.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/Onedriveapp.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/onedrivebusiness.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/Onedrivebusiness.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/ovh.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/Ovh.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/pcloud.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/Pcloud.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/s3.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/S3.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/sftp.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/Sftp.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/sftpcurl.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/Sftpcurl.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/sugarsync.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/Sugarsync.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/swift.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/Swift.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/webdav.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/Webdav.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Scan/large.json',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Scan/Large.php',

			// Kickstart, used for Site Transfer Wizard
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/installers/kickstart.txt',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/installers/kickstart.dat',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/installers/kickstart.transfer.php',

			// Generic (non-CMS) restoration script; this extension only ever selects the Joomla-specific `brs` one
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/installers/brs-generic.jpa',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/installers/brs-generic.json',

			// Pro features: Controllers
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/Controller/AliceController.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/Controller/DiscoverController.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/Controller/IncludefoldersController.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/Controller/MultipledatabasesController.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/Controller/RegexdatabasefiltersController.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/Controller/RegexfilefiltersController.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/Controller/RemotefilesController.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/Controller/RestoreController.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/Controller/ScheduleController.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/Controller/S3importController.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/Controller/TransferController.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/Controller/UploadController.php',

			// Pro features: Models
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/Model/AliceModel.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/Model/DiscoverModel.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/Model/IncludefoldersModel.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/Model/MultipledatabasesModel.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/Model/RegexdatabasefiltersModel.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/Model/RegexfilefiltersModel.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/Model/RemotefilesModel.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/Model/RestoreModel.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/Model/ScheduleModel.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/Model/S3importModel.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/Model/TransferModel.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/Model/UploadModel.php',

			// Pro features: Platform files
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/platform/Joomla/Filter/Components.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/platform/Joomla/Filter/Extensiondirs.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/platform/Joomla/Filter/Extensionfiles.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/platform/Joomla/Filter/Languages.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/platform/Joomla/Filter/Modules.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/platform/Joomla/Filter/Plugins.php',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/platform/Joomla/Filter/Templates.php',

			/**
			 * Pro features: integrated restoration.
			 *
			 * Joomla removes this one by itself, so we must not. `restore.php` is a top-level <filename> in the Pro
			 * manifest's <administration><files> and is absent from the Core manifest, so Installer::parseFiles()
			 * — via findDeletedFiles() — deletes it on the Pro→Core update. Verified on Joomla 6.1.2.
			 */
			// JPATH_ADMINISTRATOR . '/components/com_akeebabackup/restore.php',
		],
		'folders' => [
			/**
			 * Pro features: API application.
			 *
			 * Joomla does NOT remove this, contrary to what this entry assumed for a long time. Deleting the whole
			 * <api> section from the Core manifest is precisely what prevents its removal: ComponentAdapter::
			 * copyBaseFiles() guards the API client with `if ($this->getManifest()->api->files)`, so when the *new*
			 * manifest has no <api> element, parseFiles() is never called for client 3 and findDeletedFiles() never
			 * runs for it. The Pro API application therefore survives a Pro→Core downgrade untouched, and a Core
			 * site is left serving Pro API endpoints. Verified on Joomla 6.1.2.
			 */
			JPATH_API . '/components/com_akeebabackup',

			/**
			 * Pro features: ALICE.
			 *
			 * Joomla removes this one by itself, so we must not — same mechanism as restore.php above: a top-level
			 * <folder> present in the Pro manifest and absent from the Core one. Verified on Joomla 6.1.2.
			 */
			// JPATH_ADMINISTRATOR . '/components/com_akeebabackup/AliceChecks',

			// Pro features: cloud storage connectors
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/akeeba/engine/engine/Postproc/Connector',

			// Pro features: Joomla CLI integration
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/CliCommands',

			// Pro features: Views
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/View/Alice',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/View/Discover',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/View/Includefolders',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/View/Multipledatabases',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/View/Regexdatabasefilters',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/View/Regexfilefilters',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/View/Remotefiles',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/View/Restore',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/View/Schedule',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/View/S3import',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/View/Transfer',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/View/Upload',

			// Pro features: View templates
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/tmpl/alice',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/tmpl/discover',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/tmpl/includefolders',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/tmpl/multipledatabases',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/tmpl/regexdatabasefilters',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/tmpl/regexfilefilters',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/tmpl/remotefiles',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/tmpl/restore',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/tmpl/schedule',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/tmpl/s3import',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/tmpl/transfer',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/tmpl/upload',

			// Pro features: Platform folders
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/platform/Joomla/Config/Pro',
			JPATH_ADMINISTRATOR . '/components/com_akeebabackup/platform/Joomla/Finalization',

			/**
			 * Pro features: front-end MVC.
			 *
			 * Joomla does NOT remove these. findDeletedFiles() only diffs *top-level* manifest entries, and `src` is
			 * a <folder> in both the Core and the Pro manifest, so nothing nested inside it is ever considered —
			 * it is overwritten, never pruned. (The front-end `tmpl` folder IS removed by Joomla, because that one
			 * is a top-level entry present in the Pro manifest and absent from Core, which is why it is not listed
			 * here.) Verified on Joomla 6.1.2.
			 *
			 * These previously carried a JPATH_SITE . '/src/...' path, which is missing the
			 * /components/com_akeebabackup segment and pointed at a directory that does not exist.
			 */
			JPATH_SITE . '/components/com_akeebabackup/src/Controller',
			JPATH_SITE . '/components/com_akeebabackup/src/Model',
			JPATH_SITE . '/components/com_akeebabackup/src/View',
		],
	];

	/** @var string[] Included extensions to automatically publish on a NEW INSTALLATION */
	private const ENABLE_EXTENSIONS = [
		'plg_quickicon_akeebabackup',
		'plg_system_backuponupdate',
	];

	/** @var string[] Included extensions to automatically publish on NEW INSTALLATION OR UPGRADE */
	private const ALWAYS_ENABLE_EXTENSIONS = [
		'plg_console_akeebabackup',
		'plg_pagecache_akeebabackup',
		'plg_system_akwarn',
		'plg_task_akeebabackup',
		'plg_webservices_akeebabackup',
	];

	/** @var string[] Extensions to always uninstall if they are still installed (runs on install and upgrade) */
	private const REMOVE_EXTENSIONS = [];

	/** @var string[] Included extensions to be uninstalled when installing the Core version */
	private const PRO_ONLY_EXTENSIONS = [
		'plg_console_akeebabackup',
		'plg_system_backuponupdate',
		'plg_actionlog_akeebabackup',
		'plg_task_akeebabackup',
		'plg_webservices_akeebabackup',
		'plg_pagecache_akeebabackup',
	];

	/** @var string Relative directory to the custom handlers */
	private const CUSTOM_HANDLERS_DIRECTORY = 'UpgradeHandler';

	/**
	 * The database driver.
	 *
	 * @var    DatabaseInterface
	 * @since  9.3.0
	 */
	protected $_db;

	/**
	 * List of extensions included in both old and new packages (if applicable)
	 *
	 * @var   array
	 */
	private $extensionsList;

	/**
	 * Caches the extension names to IDs so we don't query the database too many times.
	 *
	 * @var   array
	 */
	private $extensionIds = [];

	/**
	 * UpgradeModel custom handlers, implementing custom logic for each extension.
	 *
	 * @var object[]
	 */
	private $customHandlers = [];

	public function init()
	{
		// Find out the common extensions
		if ($this->isSamePackage())
		{
			$this->extensionsList = $this->getExtensionsFromPackage(self::PACKAGE_NAME);
		}
		else
		{
			$oldExtensions        = $this->getExtensionsFromPackage(self::OLD_PACKAGE_NAME);
			$newExtensions        = $this->getExtensionsFromPackage(self::PACKAGE_NAME);
			$this->extensionsList = array_intersect($newExtensions, $oldExtensions);
		}

		// Load extension-specific adapters
		$this->loadCustomHandlers();
	}

	/**
	 * Handles the package's post-flight routine
	 *
	 * @param   string               $type    Which action is happening (install|uninstall|discover_install|update)
	 * @param   PackageAdapter|null  $parent  The object responsible for running this script. NULL if running outside
	 *                                        of the package's script.
	 *
	 * @return  bool
	 */
	public function postflight(string $type, ?PackageAdapter $parent = null): bool
	{
		switch ($type)
		{
			// Brand new installation (regular or through Discover)
			case 'install':
			case 'discover_install':
				$this->runIsolated([
					'upgradeFromOldPackage',
					'uninstallExtensions',
					'publishExtensionsOnInstall',
					'publishExtensionsAlways',
					'removeObsoleteFiles',
					'adoptMyExtensions',
				]);

				$this->runCustomHandlerEvent('onInstall', $type, $parent);
				break;

			// Update to a new version
			case 'update':
			default:
				$this->runIsolated([
					'removeObsoleteFiles',
					'publishExtensionsAlways',
					'uninstallExtensions',
					'uninstallProExtensions',
					'adoptMyExtensions',
				]);

				$this->runCustomHandlerEvent('onUpdate', $type, $parent);
				break;

			// Uninstallation
			case 'uninstall':
				$this->runCustomHandlerEvent('onUninstall', $type, $parent);
				break;
		}

		return true;
	}

	/**
	 * Runs an event across all custom handler objects.
	 *
	 * @param   string  $eventName     The name of the event to run
	 * @param   mixed   ...$arguments  Arguments to the event
	 *
	 * @return  array  The results of the custom handler events.
	 */
	public function runCustomHandlerEvent(string $eventName, ...$arguments): array
	{
		$result = [];

		foreach ($this->customHandlers as $adapter)
		{
			if (!method_exists($adapter, $eventName))
			{
				continue;
			}

			try
			{
				$result[] = $adapter->{$eventName}(...$arguments);
			}
			catch (Throwable $e)
			{
				if (defined('JDEBUG') && JDEBUG)
				{
					Factory::getApplication()->enqueueMessage($e->getMessage());
				}
			}
		}

		return $result;
	}

	/**
	 * Returns the extension ID for a Joomla extension given its name.
	 *
	 * This is deliberately public so that custom handlers can use it without having to reimplement it.
	 *
	 * @param   string  $extension  The extension name, e.g. `plg_system_example`.
	 *
	 * @return  int|null  The extension ID or null if no such extension exists
	 */
	public function getExtensionId(string $extension): ?int
	{
		if (isset($this->extensionIds[$extension]))
		{
			return $this->extensionIds[$extension];
		}

		$this->extensionIds[$extension] = null;

		$criteria = $this->extensionNameToCriteria($extension);

		if (empty($criteria))
		{
			return $this->extensionIds[$extension];
		}

		$db    = $this->getDatabase();
		$query = DbQuery::create($db)
		            ->select($db->quoteName('extension_id'))
		            ->from($db->quoteName('#__extensions'));

		foreach ($criteria as $key => $value)
		{
			$type = is_numeric($value) ? ParameterType::INTEGER : ParameterType::STRING;
			$type = is_bool($value) ? ParameterType::BOOLEAN : $type;
			$type = is_null($value) ? ParameterType::NULL : $type;

			/**
			 * This is required since $value is passed by reference in bind(). If we do not do this unholy trick the
			 * $value variable is overwritten in the next foreach() iteration, therefore all criteria values will be
			 * equal to the last value iterated. Groan...
			 */
			$varName    = 'queryParam' . ucfirst($key);
			${$varName} = $value;

			$query->where($db->qn($key) . ' = :' . $key)
			      ->bind(':' . $key, ${$varName}, $type);
		}

		try
		{
			$this->extensionIds[$extension] = (int) $db->setQuery($query)->loadResult();
		}
		catch (RuntimeException $e)
		{
			return null;
		}

		return $this->extensionIds[$extension];
	}

	/**
	 * Adopt the extensions by new package.
	 *
	 * This modifies the package_id column of the #__extensions table for the records of the extensions declared in the
	 * new package's manifest. This allows you to use Discover to install new extensions without leaving them “orphan”
	 * of a package in the #__extensions table, something which could cause problems when running Joomla! Update.
	 *
	 * @return  void
	 */
	public function adoptMyExtensions(): void
	{
		// Get the extension ID of the new package
		$newPackageId = $this->getExtensionId(self::PACKAGE_NAME);

		if (empty($newPackageId))
		{
			return;
		}

		// Get the extension IDs
		$extensionIDs = array_map([$this, 'getExtensionId'], $this->getExtensionsFromPackage(self::PACKAGE_NAME));
		$extensionIDs = array_filter($extensionIDs, function ($x) {
			return !empty($x);
		});

		if (empty($extensionIDs))
		{
			return;
		}

		/**
		 * Looks stupid? This realigns the integer keys because whereIn() expects 0-based, monotonically increasing
		 * array keys. Otherwise it ends up emitting null values. GROAN!
		 */
		$extensionIDs = array_merge($extensionIDs);

		// Reassign all extensions
		$db    = $this->getDatabase();
		$query = DbQuery::create($db)
		            ->update($db->quoteName('#__extensions'))
		            ->set($db->qn('package_id') . ' = :package_id')
		            ->whereIn($db->qn('extension_id'), $extensionIDs, ParameterType::INTEGER)
		            ->bind(':package_id', $newPackageId, ParameterType::INTEGER);
		$db->setQuery($query)->execute();
	}

	/**
	 * Handle the package upgrade from the old to the new package.
	 *
	 * These versions would also run on Joomla 4 but are replaced with this new package. Since the package name is
	 * different but some of the included extensions are under the same name we need to deal with them. Namely, we need
	 * to:
	 *
	 * * Change the `package_id` in the `#__extensions` table to that of the new `pkg_akeebabackup` package. This is
	 *   currently not used anywhere(?) but it might be the case that Joomla finalyl decides to prevent standalone
	 *   uninstallation of extensions which are part of a package.
	 * * Remove the extensions from the `#__akeeba_common` entries which mark them as dependent on FOF 3.x or 4.x. This
	 *   is so that FOF 3.x / 4.x can be uninstalled when the old package (`pkg_akeeba`) is being uninstalled, since
	 *   these extensions will NOT be removed with it, per the item below.
	 * * Edit the cached XML manifest file of the old `pkg_akeeba` package so that it doesn't try to uninstall the
	 *   extensions it has in common with the new `pkg_akeebabackup` package. Joomla SHOULD figure this out by means of
	 *   the recorded `package_id` in the `#__extensions` table but it currently doesn't seem to have any code to do
	 *   that. Therefore editing the cached XML manifest is the only reasonable way to do this.
	 *
	 * @return  void
	 * @noinspection PhpUnused
	 */
	protected function upgradeFromOldPackage(): void
	{
		if ($this->isSamePackage())
		{
			$this->unregisterFromFOF('3');
			$this->unregisterFromFOF('4');

			return;
		}

		if (!$this->hasOldPackage())
		{
			return;
		}

		$this->reassignExtensions();
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->unregisterFromFOF('3');
		$this->unregisterFromFOF('4');
		$this->removeExtensionsFromPackageManifest();
	}

	/**
	 * Publish a list of extensions.
	 *
	 * Used to publish various plugins when you install the package.
	 *
	 * @return  void
	 */
	protected function publishExtensionsOnInstall(?array $extensionsList = null): void
	{
		$extensionsList = $extensionsList ?? self::ENABLE_EXTENSIONS;
		$extensionIDs   = array_map([$this, 'getExtensionId'], $extensionsList);
		$extensionIDs   = array_filter($extensionIDs, function ($x) {
			return !empty($x);
		});

		if (empty($extensionIDs))
		{
			return;
		}

		$db    = $this->getDatabase();
		$query = DbQuery::create($db)
		            ->update($db->quoteName('#__extensions'))
		            ->set($db->qn('enabled') . ' = 1')
		            ->whereIn($db->quoteName('extension_id'), $extensionIDs);
		try
		{
			$db->setQuery($query)->execute();
		}
		catch (RuntimeException $e)
		{
			return;
		}
	}

	protected function publishExtensionsAlways()
	{
		$this->publishExtensionsOnInstall(self::ALWAYS_ENABLE_EXTENSIONS);
	}

	/**
	 * Removes obsolete files and folders.
	 *
	 * This is required because Joomla's extensions installer will only check for the top-level files and directories
	 * listed in the XML manifest. Any folders and files deeper than that will not be removed automatically.
	 *
	 * @return  void
	 * @noinspection PhpUnused
	 */
	protected function removeObsoleteFiles(): void
	{
		// We will definitely remove REMOVE_FROM_ALL_VERSIONS in all versions
		$removeSource = self::REMOVE_FROM_ALL_VERSIONS;
		$isPro        = $this->isPro();

		if (!$isPro)
		{
			$removeSource['files']   = array_merge($removeSource['files'], self::REMOVE_FROM_CORE['files']);
			$removeSource['folders'] = array_merge($removeSource['folders'], self::REMOVE_FROM_CORE['folders']);
		}

		// Remove files
		foreach ($removeSource['files'] as $file)
		{
			if (!is_file($file))
			{
				continue;
			}

			try
			{
				File::delete($file);
			}
			catch (\Exception $e)
			{
				// Swallow.
			}
		}

		// Remove folders
		foreach ($removeSource['folders'] as $folder)
		{
			$this->deleteFolder($folder);
		}
	}

	/**
	 * Uninstalls the extensions which are marked as always to be uninstalled.
	 *
	 * @return  void
	 * @noinspection PhpUnused
	 */
	protected function uninstallExtensions(): void
	{
		// Tell Joomla to uninstall the extensions always meant to be removed.
		foreach (self::REMOVE_EXTENSIONS as $extension)
		{
			$this->uninstallExtension($extension);
		}
	}

	/**
	 * Uninstalls Pro-only extensions from the Core version of the package.
	 *
	 * @return  void
	 * @noinspection PhpUnused
	 */
	protected function uninstallProExtensions(): void
	{
		// If it's the Pro version we don't uninstall anything.
		if ($this->isPro())
		{
			return;
		}

		// Tell Joomla to uninstall the Pro-only extensions.
		foreach (self::PRO_ONLY_EXTENSIONS as $extension)
		{
			$this->uninstallExtension($extension);
		}
	}

	private function deleteFolder(string $path): bool
	{
		// If the folder does not exist in the requested form return early.
		$hasMixedCase = is_dir($path);

		if (!$hasMixedCase)
		{
			return false;
		}

		// If the folder is all lowercase return early.
		$baseName          = basename($path);
		$lowercaseBaseName = strtolower($baseName);

		if ($baseName === $lowercaseBaseName)
		{
			try
			{
				return $hasMixedCase && Folder::delete($path);
			}
			catch (\Exception $e)
			{
				return false;
			}
		}

		// We have a mixed case folder. Further investigation necessary.
		$altPath      = dirname($path) . '/' . $lowercaseBaseName;
		$hasLowercase = is_dir($altPath);

		// If the lowercase path does not exist we have a case-sensitive filesystem. Return early.
		if (!$hasLowercase)
		{
			try
			{
				return $hasMixedCase && Folder::delete($path);
			}
			catch (\Exception $e)
			{
				return false;
			}
		}

		// Both folders exist. Are they the same?
		$testBasename      = UserHelper::genRandomPassword(8) . '.dat';
		$data              = UserHelper::genRandomPassword(32);
		$lowercaseTestFile = $altPath . '/' . $testBasename;
		$uppercaseTestFile = $path . '/' . $testBasename;

		try
		{
			File::write($lowercaseTestFile, $data);
		}
		catch (\Exception $e)
		{
			// Swallow.
		}

		$readData = file_get_contents($uppercaseTestFile);

		try
		{
			File::delete($lowercaseTestFile);
		}
		catch (\Exception $e)
		{
			// Swallow.
		}

		// The two folders are different. We have a case-sensitive filesystem. Proceed with deletion.
		if ($readData !== $data)
		{
			try
			{
				return Folder::delete($path);
			}
			catch (\Exception $e)
			{
				return false;
			}
		}

		/**
		 * The two folders are identical: we are on a case-insensitive filesystem, and $path and $altPath are two
		 * spellings of one directory. The probe above proves it — a file written through $altPath was read back
		 * through $path — so deleting it removes exactly the folder we were asked to remove, and nothing else.
		 *
		 * We cannot simply delete $path, because we do not know how the entry is actually spelled on disk, and
		 * deleting through the wrong spelling is what the original Windows breakage was about. So we first rename
		 * the folder to a name we chose ourselves, whose spelling is therefore known exactly, and delete that.
		 *
		 * The rename is two-step because renaming Foo to foo is a no-op (or an error) on a case-insensitive
		 * filesystem: the source and destination are the same entry. Going through a uniquely named intermediate
		 * avoids that collision.
		 *
		 * If the deletion fails we still land the folder on its all-lowercase name rather than leaving it under the
		 * intermediate one, so that a failure here leaves the tree tidy — and correctly cased, which matters if the
		 * site is later moved to a case-sensitive filesystem.
		 */
		$intermediateBasename = $lowercaseBaseName . '_' . UserHelper::genRandomPassword(8);
		$intermediatePath     = dirname($path) . '/' . $intermediateBasename;

		try
		{
			Folder::move($path, $intermediatePath);
		}
		catch (\Exception $e)
		{
			// Swallow; the is_dir() check below is what actually decides whether the rename landed.
		}

		/**
		 * Folder::move() reports failure by RETURNING a string ('Rename failed', 'Folder already exists', …) rather
		 * than throwing, so the catch above cannot be relied upon. Check the filesystem instead: if the folder is not
		 * where we just tried to put it, the rename did not happen and there is nothing safe to delete.
		 */
		if (!is_dir($intermediatePath))
		{
			return false;
		}

		try
		{
			return Folder::delete($intermediatePath);
		}
		catch (\Exception $e)
		{
			// Deletion failed. Fall through and at least leave the folder correctly cased.
		}

		try
		{
			Folder::move($intermediatePath, $altPath);
		}
		catch (\Exception $e)
		{
			// Swallow: nothing further we can do.
		}

		return false;
	}

	/**
	 * Runs a method inside a try/catch block to suppress any errors
	 *
	 * @param   string[]  $methodNames  The method name to run
	 *
	 * @return  void
	 */
	private function runIsolated(array $methodNames): void
	{
		foreach ($methodNames as $methodName)
		{
			try
			{
				$this->{$methodName}();
			}
			catch (Throwable $e)
			{
				// No problem, let's move on.
			}
		}
	}

	/**
	 * Does the old package even exist?
	 *
	 * @return   bool
	 */
	private function hasOldPackage(): bool
	{
		if (empty(self::OLD_PACKAGE_NAME))
		{
			return false;
		}

		$eid = $this->getExtensionId(self::OLD_PACKAGE_NAME);

		return !empty($eid);
	}

	/**
	 * Reassign the extensions to the new package.
	 *
	 * This modifies the package_id column of the #__extensions table for the records of the records defined in
	 * $this->extensionsList. Since these are shared between the old and new packages we need to change their package ID
	 * to the new package's ID. Otherwise Joomla might be confused as to which package "owns" them.
	 *
	 * @return  void
	 */
	private function reassignExtensions(): void
	{
		// Get the extension ID of the new package
		$newPackageId = $this->getExtensionId(self::PACKAGE_NAME);

		if (empty($newPackageId))
		{
			return;
		}

		// Get the extension IDs
		$extensionIDs = array_map([$this, 'getExtensionId'], $this->extensionsList);
		$extensionIDs = array_filter($extensionIDs, function ($x) {
			return !empty($x);
		});

		if (empty($extensionIDs))
		{
			return;
		}

		/**
		 * Looks stupid? This realigns the integer keys because whereIn() expects 0-based, monotonically increasing
		 * array keys. Otherwise it ends up emitting null values. GROAN!
		 */
		$extensionIDs = array_merge($extensionIDs);

		// Reassign all extensions
		$db    = $this->getDatabase();
		$query = DbQuery::create($db)
		            ->update($db->quoteName('#__extensions'))
		            ->set($db->qn('package_id') . ' = :package_id')
		            ->whereIn($db->qn('extension_id'), $extensionIDs, ParameterType::INTEGER)
		            ->bind(':package_id', $newPackageId, ParameterType::INTEGER);
		$db->setQuery($query)->execute();
	}

	/**
	 * Unregisters a list of extensions from being marked as dependent on the specified FOF version.
	 *
	 * @param   string  $fofVersion  PHP version to unregister the extensions from
	 *
	 * @return  void
	 */
	private function unregisterFromFOF($fofVersion = '3')
	{
		// Make sure we have an extensions list and it's canonical (admin modules have mod_ prefix, not amod_).
		$extensions = $this->extensionsList;
		$extensions = array_map(function ($name) {
			if (substr($name, 0, 5) == 'amod_')
			{
				$name = 'mod_' . substr($name, 5);
			}

			return $name;
		}, $extensions);

		// Get the existing list of extensions dependent on the specified version of FOF.
		$keyName = 'fof' . $fofVersion . '0';
		$db      = $this->getDatabase();
		$query   = DbQuery::create($db)
		              ->select($db->quoteName('value'))
		              ->from($db->quoteName('#__akeeba_common'))
		              ->where($db->quoteName('key') . ' = :keyName')
		              ->bind(':keyName', $keyName);
		try
		{
			$json = $db->setQuery($query)->loadResult();
			$list = ($json === null) ? [] : json_decode($json, true);
		}
		catch (RuntimeException $e)
		{
			return;
		}

		// If the list is empty I am already done.
		if (is_null($list) || !is_array($list))
		{
			return;
		}

		// Remove the common extensions which no longer depend on FOF.
		$list = array_diff($list, $extensions);
		$json = json_encode($list);

		// Update the #__akeeba_common table.
		$query = DbQuery::create($db)
		            ->update($db->quoteName('#__akeeba_common'))
		            ->set($db->quoteName('value') . ' = :json')
		            ->where($db->quoteName('key') . ' = :keyName')
		            ->bind(':json', $json)
		            ->bind(':keyName', $keyName);

		try
		{
			$db->setQuery($query)->execute();
		}
		catch (RuntimeException $e)
		{
			return;
		}
	}

	/**
	 * Removes the common extensions from old package's cached manifest.
	 *
	 * This prevents Joomla from uninstalling modules, plugins etc which are nominally included in both packages when
	 * you uninstall the old package.
	 *
	 * @return  void
	 */
	private function removeExtensionsFromPackageManifest(): void
	{
		// Make sure we have an old package and a list of extensions
		$oldPackage = self::OLD_PACKAGE_NAME;
		$extensions = $this->extensionsList;

		if (empty($oldPackage) || empty($extensions))
		{
			return;
		}

		// Get the cached manifest as a SimpleXMLElement node
		$xml = $this->getPackageXMLManifest($oldPackage);

		if (is_null($xml))
		{
			return;
		}

		// Walk through all the <file> tags and remove the extensions in the $extensions list
		foreach ($xml->xpath('//files/file') as $fileField)
		{
			$extension = $this->xmlNodeToExtensionName($fileField);

			if (is_null($extension) || !in_array($extension, $extensions))
			{
				continue;
			}

			unset($fileField[0][0]);
		}

		// Save the modified manifest back to the package manifests cache.
		$filePath = $this->getCachedManifestPath($oldPackage);
		$contents = $xml->asXML();

		@file_put_contents($filePath, $contents);
	}

	/**
	 * Gets a SimpleXMLElement representation of the cached manifest of the extension.
	 *
	 * @param   string  $package
	 *
	 * @return  SimpleXMLElement|null
	 */
	private function getPackageXMLManifest(string $package): ?SimpleXMLElement
	{
		$filePath = $this->getCachedManifestPath($package);

		if (!@file_exists($filePath) || !@is_readable($filePath))
		{
			return null;
		}

		$xmlContent = @file_get_contents($filePath);

		if (empty($xmlContent))
		{
			return null;
		}

		return new SimpleXMLElement($xmlContent);
	}

	/**
	 * Get the list of extensions included in a package
	 *
	 * @param   string  $package
	 *
	 * @return  array
	 */
	private function getExtensionsFromPackage(string $package): array
	{
		$extensions = [];
		$xml        = $this->getPackageXMLManifest($package);

		if (is_null($xml))
		{
			return $extensions;
		}

		foreach ($xml->xpath('//files/file') as $fileField)
		{
			$extension = $this->xmlNodeToExtensionName($fileField);

			if (is_null($extension))
			{
				continue;
			}

			$extensions[] = $extension;
		}

		return $extensions;
	}

	/**
	 * Take a SimpleXMLElement `<file>` node of the package manifest and return the corresponding Joomla extension name
	 *
	 * @param   SimpleXMLElement  $fileField  The `<file>` node of the package manifest
	 *
	 * @return  string|null  The extension name, null if it cannot be determined.
	 */
	private function xmlNodeToExtensionName(SimpleXMLElement $fileField): ?string
	{
		$type = (string) $fileField->attributes()->type;
		$id   = (string) $fileField->attributes()->id;

		switch ($type)
		{
			case 'component':
			case 'file':
			case 'library':
				$extension = $id;
				break;

			case 'plugin':
				$group     = (string) ($fileField->attributes()->group ?? '') ?: 'system';
				$extension = 'plg_' . $group . '_' . $id;
				break;

			case 'module':
				$client    = (string) ($fileField->attributes()->client ?? '') ?: 'site';
				$extension = (($client != 'site') ? 'a' : '') . $id;
				break;

			default:
				$extension = null;
				break;
		}

		return $extension;
	}

	/**
	 * Convert a Joomla extension name to `#__extensions` table query criteria.
	 *
	 * The following kinds of extensions are supported:
	 * * `pkg_something` Package type extension
	 * * `com_something` Component
	 * * `plg_folder_something` Plugins
	 * * `mod_something` Site modules
	 * * `amod_something` Administrator modules. THIS IS CUSTOM.
	 * * `file_something` File type extension
	 * * `lib_something` Library type extension
	 *
	 * @param   string  $extensionName
	 *
	 * @return  string[]
	 */
	private function extensionNameToCriteria(string $extensionName): array
	{
		$parts = explode('_', $extensionName, 3);

		switch ($parts[0])
		{
			case 'pkg':
				return [
					'type'    => 'package',
					'element' => $extensionName,
				];

			case 'com':
				return [
					'type'    => 'component',
					'element' => $extensionName,
				];

			case 'plg':
				return [
					'type'    => 'plugin',
					'folder'  => $parts[1],
					'element' => $parts[2],
				];

			case 'mod':
				return [
					'type'      => 'module',
					'element'   => $extensionName,
					'client_id' => 0,
				];

			// That's how we note admin modules
			case 'amod':
				return [
					'type'      => 'module',
					'element'   => substr($extensionName, 1),
					'client_id' => 1,
				];

			case 'file':
				return [
					'type'    => 'file',
					'element' => $extensionName,
				];

			case 'lib':
				return [
					'type'    => 'library',
					'element' => $parts[1],
				];
		}

		return [];
	}

	/**
	 * Get the absolute filesystem path
	 *
	 * @param   string  $package
	 *
	 * @return  string
	 */
	private function getCachedManifestPath(string $package): string
	{
		return JPATH_MANIFESTS . '/packages/' . $package . '.xml';
	}

	/**
	 * Is this the Pro version?
	 *
	 * This is determined by examining the constants, files and folders defined in self::PRO_CRITERIA
	 *
	 * @return  bool
	 * @see     self::PRO_CRITERIA
	 */
	private function isPro(): bool
	{
		if (empty(self::PRO_CRITERIA))
		{
			return false;
		}

		foreach (self::PRO_CRITERIA as $criterion)
		{
			[$type, $value] = $criterion;

			switch ($type)
			{
				case 'const':
				case 'constant':
					if (!defined($value))
					{
						continue 2;
					}

					if (constant($value))
					{
						return true;
					}

					break;

				case 'folder':
					if (@file_exists($value) && @is_dir($value))
					{
						return true;
					}
					break;

				case 'file':
					if (@file_exists($value) && @is_file($value))
					{
						return true;
					}
					break;

				default:
					continue 2;
			}
		}

		return false;
	}

	/**
	 * Uninstall an extension by name.
	 *
	 * @param   string  $extension
	 *
	 * @return  bool
	 */
	private function uninstallExtension(string $extension): bool
	{
		// Let's get the extension ID. If it's not there we can't uninstall this extension, right..?
		$eid = $this->getExtensionId($extension);

		if (empty($eid))
		{
			return false;
		}

		// Extensions must be marked as not belonging to the package before they can be removed
		$this->removeExtensionPackageLink($eid);

		// Get an Extension table object and Installer object.
		$row       = new Extension($this->getDatabase());
		$installer = Installer::getInstance();

		// Load the extension row or fail the uninstallation immediately.
		try
		{
			if (!$row->load($eid))
			{
				return false;
			}
		}
		catch (Throwable $e)
		{
			// If the database query fails or Joomla experiences an unplanned rapid deconstruction let's bail out.
			return false;
		}

		// Can't uninstalled protected extensions
		/** @noinspection PhpUndefinedFieldInspection */
		if ((int) $row->locked === 1)
		{
			return false;
		}

		// An extension row without a type? What have you done to your database, you MONSTER?!
		if (empty($row->type))
		{
			return false;
		}

		// Do the actual uninstallation. Try to trap any errors, just in case...
		try
		{
			return $installer->uninstall($row->type, $eid);
		}
		catch (Throwable $e)
		{
			return false;
		}
	}

	/**
	 * Loads any custom handlers.
	 *
	 * @return  void
	 */
	private function loadCustomHandlers(): void
	{
		$handlerNamespace = __NAMESPACE__ . '\\' . self::CUSTOM_HANDLERS_DIRECTORY;

		$this->customHandlers = [];

		// Scan the directory and load the custom handlers
		$targetDirectory = __DIR__ . '/' . self::CUSTOM_HANDLERS_DIRECTORY;

		if (!@file_exists($targetDirectory) || !@is_dir($targetDirectory))
		{
			return;
		}

		$di = new DirectoryIterator($targetDirectory);

		/** @var DirectoryIterator $entry */
		foreach ($di as $entry)
		{
			// Ignore folders
			if ($entry->isDot() || $entry->isDir())
			{
				continue;
			}

			// Ignore non-PHP directories
			if ($entry->getExtension() != 'php')
			{
				continue;
			}

			// Get the class name
			$bareName          = basename($entry->getFilename(), '.php');
			$bareNameCanonical = preg_replace('/[^A-Z_]/i', '', $bareName);

			/**
			 * Some hosts rename files with numeric suffixes, e.g. FooBar.php is renamed to FooBar.01.php. In both cases
			 * the bare class name would be "FooBar" but the canonical would be "FooBar" vs "FooBar.01". This check
			 * makes sure that renamed files will NOT be loaded. Ever.
			 */
			if ($bareName != $bareNameCanonical)
			{
				continue;
			}

			// Have I already loaded an object this class? Yeah, sometimes hosts do weird(er) things.
			if (array_key_exists($bareNameCanonical, $this->customHandlers))
			{
				continue;
			}

			// Try to load the file
			require_once $entry->getPathname();

			// Make sure we actually loaded a class I can use
			$classFQN = $handlerNamespace . '\\' . $bareNameCanonical;

			if (!class_exists($classFQN, false))
			{
				continue;
			}

			// Add the custom handler, passing a reference to ourselves
			$this->customHandlers[$bareNameCanonical] = new $classFQN($this, $this->getDatabase());
		}
	}

	/**
	 * Are the old and new packages identical?
	 *
	 * Also returns true if no OLD_PACKAGE_NAME has been specified.
	 *
	 * @return  bool
	 */
	private function isSamePackage(): bool
	{
		return empty(self::OLD_PACKAGE_NAME) || (self::OLD_PACKAGE_NAME === self::PACKAGE_NAME);
	}

	private function removeExtensionPackageLink(int $eid): void
	{
		$db    = $this->getDatabase();
		$query = DbQuery::create($db)
			->update($db->quoteName('#__extensions'))
			->set($db->quoteName('package_id') . ' = 0')
			->where($db->quoteName('extension_id') . ' = :eid')
			->bind(':eid', $eid, ParameterType::INTEGER);
		$db->setQuery($query)->execute();
	}
}