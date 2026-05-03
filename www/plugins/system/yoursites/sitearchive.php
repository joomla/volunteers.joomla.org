<?php

/**
 * @version    CVS: 1.65.0
 * @package    com_yoursites
 * @author     Geraint Edwards <via website>
 * @copyright  2016-YOURSITES_COPYRIGHT GWE Systems Ltd
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined( '_JEXEC' ) or die;

use Ifsnop\Mysqldump\Mysqldump;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\Session\SessionInterface;
use Joomla\Utilities\ArrayHelper;
use Joomla\Registry\Registry;

class YstsSiteArchive {

    const YSTS_ENCRYPT_NONE = 0;
    const YSTS_ENCRYPT_ALL = 1;
    const YSTS_ENCRYPT_CONFIG = 2;

    const ZIP_FOLDER = "/images/";

    static $zip_file = "";

    // Should subsites e.g. clones within sub-folders be skipped/excluded
    const skipSubSites  = true;

    // Skip cloning of softlinks
    const skipSoftLinks = true;

    public static function createArchive( $requestObject, &$returnData ) {

        //@ini_set("display_errors", 0);
        //@error_reporting(E_ALL | E_NOTICE);

        $tableGroupSize = 10;

        $diagnostics = true;

        $extraData = json_decode( $requestObject->extraData );

        $backupOnly      = isset($extraData->backuponly) ? (int) $extraData->backuponly : 0;
        $backupSelection = $extraData->backupselection ?? 'both';
        if (!$backupOnly) {
            $backupSelection = 'both';
        }

        /* ZIP File name and path etc. */
        $encrypt  = self::YSTS_ENCRYPT_CONFIG;
        $password = $extraData->secretkey;

        $maxSize      = 100; // Maximum size of each zip file in megabytes BEFORE compression
        //$newPrefix    = $extraData->database_prefix;
        // We now to the prefix replacement during restoration so that we can reuse the zip packages
        $newPrefix    = 'YSTS_PREFIX_';

        // Setup
        $rootPath = JPATH_SITE;

        $zipCount                  = $extraData->zipCount;
        $maxSize                   = $maxSize * 1024 * 1024;
        YstsSiteArchive::$zip_file = 'ysts_zipfile' . $zipCount . '.zip';

        $sqlZipCount               = $extraData->sqlZipCount;
        $sql_zip_file              = 'ysts_sql_zipfile' . $sqlZipCount . '.zip';

        // make sure we have enough time to handle slow tasks
        @ini_set( "max_execution_time", 900 );
        @ini_set( "default_socket_timeout", 900 );

        $db     = Factory::getDbo();
        $config = Factory::getConfig();
        $dbname = $config->get( 'db', '' );

        if ( ! empty ( $requestObject->cloned ) && ( $requestObject->cloned === 'databasedumped' || $requestObject->cloned === 'filesongoing' ) )
        {
            // if only backing up database then we are finished at this point
            if ($backupSelection === 'database')
            {
                // set a new request action using the cloned field - we don't set returnData since we are doing a direct request
                $requestObject->cloned = 'filescomplete';
                return self::createArchive($requestObject, $returnData);
            }

            $skipSoftLinks = YstsSiteArchive::skipSoftLinks;
            if (isset($requestObject->skipSoftLinks)){
                $skipSoftLinks = $requestObject->skipSoftLinks;
            }
            if ( $zipCount == 1 && ! $skipSoftLinks )
            {
                $iterator = new \RecursiveDirectoryIterator( $rootPath, FilesystemIterator::SKIP_DOTS );

                $filter = new \RecursiveCallbackFilterIterator( $iterator, function ( $current, $key, $iterator ) {

                    $skipSubSites  = YstsSiteArchive::skipSubSites;

                    // Just the links
                    if ( ! $current->isLink() && ! $current->isDir())
                    {
                        return false;
                    }

                    // we need to recurse!
                    if ( $current->isDir() )
                    {
                        // if recursing into a subsite then skip this i.e. do not map across clone sites etc
                        if ( $skipSubSites && file_exists( $key . "/configuration.php" ) && file_exists( $key . "/components/com_content" ) && file_exists( $key . "/plugins" ) )
                        {
                            return false;
                        }

                        return true;
                    }

                    return true;
                } );

                $iterator       = new \RecursiveIteratorIterator( $filter );
                $linksToProcess = array();
                foreach ( $iterator as $file )
                {
                    if ( $file->isLink() )
                    {
                        $linksToProcess[str_replace(JPATH_SITE, "{JPATH_ROOT}", $file->getPathname())] = readlink( $file );
                    }
                }
                $linksToProcessData = serialize( $linksToProcess );
            }


            // Now on to the files
            $ProcessFilesStore = sys_get_temp_dir() . "/processFiles" . $requestObject->pid . ".txt";
            if ( file_exists( $ProcessFilesStore ) )
            {
                $filesToProcess = unserialize( file_get_contents( $ProcessFilesStore ) );
            }
            else
            {
                // Clean up first - removing temporary files and clearing cache
                try
                {
                    clearCache( $requestObject, $returnData );
                    clearTmp( $requestObject, $returnData );

                    if ($returnData->error)
                    {
                        $returnData->errormessages[] = "Unable to clear cache or clear temporary files - threw exception";
                        return;
                    }
                }
                catch ( \Throwable $e )
                {

                    $returnData->error           = 1;
                    $returnData->cloned          = 0;
                    $returnData->errormessages[] = "Unable to clear cache or clear temporary files - threw exception";
                    $returnData->errormessages[] = $e->getMessage();
                    return;
                }

                $iterator = new \RecursiveDirectoryIterator( $rootPath, FilesystemIterator::SKIP_DOTS );

                $filter = new \RecursiveCallbackFilterIterator( $iterator, function ( $current, $key, $iterator ) {
                    $skipSubSites  = YstsSiteArchive::skipSubSites;

                    // No links - if included they are processed separately
                    if ( $current->isLink() )
                    {
                        return false;
                    }

                    $entry = $current->getFilename();
                    $path  = $current->getRealPath();

                    // No project files
                    if ( $entry == ".idea" )
                    {
                        return false;
                    }

                    if ( $entry == "ysts_archive.php" || $entry == "ysts_removeArchive.php" || $entry == "rclibrary" )
                    {
                        return false;
                    }

                    // we need to recurse!
                    if ( $current->isDir() )
                    {
                        // if recursing into a subsite then skip this i.e. do not map across clone sites etc
                        if ( $skipSubSites && file_exists( $key . "/configuration.php" ) && file_exists( $key . "/components/com_content" ) && file_exists( $key . "/plugins" ) )
                        {
                            return false;
                        }

                        return true;
                    }

                    if ( strpos( $entry, YstsSiteArchive::$zip_file ) === 0 )
                    {
                        return false;
                    }


                    if ( strpos( $path, '/administrator/cache/' ) > 0 || strpos( $path, '/administrator/logs/' ) > 0 )
                    {
                        return false;
                    }

                    // Skip hidden files and directories.
                    /*
                    if ( $entry[0] === '.' )
                    {
                        return false;
                    }
                    */

                    $extension = pathinfo( $entry, PATHINFO_EXTENSION );

                    // Ingore any backups files
                    if ( in_array( $extension, array(
                            "jpa",
                            "zip",
                            "gz",
                            "targz",
                            "jps",
                            "bz",
                            "rar"
                        ) )
                         && ! in_array( $entry, array( 'angie.jpa', 'angie-generic.jpa', 'angie-joomla.jpa' ) ) )
                    {
                        return false;
                    }

                    if (
                        strpos( $extension, "j0" ) === 0
                        || strpos( $extension, "j1" ) === 0
                        || strpos( $extension, "j2" ) === 0
                        || strpos( $extension, "j3" ) === 0
                        || strpos( $extension, "j4" ) === 0
                        || strpos( $extension, "j5" ) === 0
                        || strpos( $extension, "j6" ) === 0
                        || strpos( $extension, "j7" ) === 0
                        || strpos( $extension, "j8" ) === 0
                    )
                    {
                        return false;
                    }

                    // Akeeba log files e.g. akeeba.json.id-20220309-142446-265428.log.php
                    if ( strpos( $entry, '.log.php' ) > 0 && strpos( $entry, 'akeeba' ) !== false )
                    {
                        return false;
                    }

                    return true;
                } );

                $iterator       = new \RecursiveIteratorIterator( $filter );
                $filesToProcess = array();
                foreach ( $iterator as $file )
                {
                    if ( ! $file->isDir() )
                    {

                        $filesToProcess[] = $file->getRealPath();
                    }
                }
                $processFilesData = serialize( $filesToProcess );
                $written          = file_put_contents( $ProcessFilesStore, $processFilesData );
            }

            try
            {
                /* Initialize archive object */
                $zip      = new ZipArchive;
                $zip_open = $zip->open( JPATH_BASE . YstsSiteArchive::ZIP_FOLDER . YstsSiteArchive::$zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE );
                if ( $encrypt !== self::YSTS_ENCRYPT_NONE )
                {
                    $zip->setPassword( $password );
                }

                $encryptionSupported = version_compare( PHP_VERSION, "8.0", "ge" ) && method_exists( $zip, "isEncryptionMethodSupported" ) && $zip->isEncryptionMethodSupported( ZipArchive::EM_AES_256 );
                if ( ! $encryptionSupported )
                {
                    $encrypt = self::YSTS_ENCRYPT_NONE;
                }

                $files   = array();
                $zipSize = 0;

                if ( $zipCount == 1 && isset( $linksToProcessData ) )
                {
                    $relativePath = 'ysts_softlinks.txt';
                    $added        = $zip->addFromString( $relativePath, $linksToProcessData );
                    $compression  = $zip->setCompressionName( $relativePath, ZipArchive::CM_DEFLATE, 7 );
                    if ( $encrypt === self::YSTS_ENCRYPT_ALL )
                    {
                        $encrypted = $zip->setEncryptionName( $relativePath, ZipArchive::EM_AES_256 );
                    }
                    // This is the uncompressed size - we can only get the compressed size after the archive is closed and compressed
                    $zipSize += $zip->statIndex( $zip->numFiles - 1 )['size'];
                }
            }
            catch (Throwable $e)
            {
                $returnData->error           = 1;
                $returnData->cloned          = 0;
                $returnData->errormessages[] = $e->getMessage();
                return $returnData;

            }


            foreach ( $filesToProcess as $filesIndex => $file )
            {
                if ( ! is_dir( $file ) && is_file( $file ) )
                {
                    $filePath     = $file;
                    $fileName     = basename( $file );
                    $relativePath = substr( $filePath, strlen( $rootPath ) + 1 );
                    try
                    {
                        // special handling of configuration.php file
                        $isConfigFile = false;
                        if ($fileName === 'configuration.php')
                        {
                            $configContent = file_get_contents($filePath);
                            if ( strpos($configContent, "class JConfig" ) !== false)
                            {
                                $isConfigFile = true;

                                // ToDo disable emails using code from com_config application model ConfigModelApplication and ::writeconfigfile method adapted to our needs here
                                $currentConfig = new JConfig();
                                $configArray = ArrayHelper::fromObject($currentConfig);

                                // Create the new configuration object, and unset the root_user property
                                $configRegistry = new Registry($configArray);

                                $dbOptions = [
                                    'host'   ,
                                    'user'   ,
                                    'password' ,
                                    'db' ,
                                    'dbprefix'
                                ];

                                foreach ($dbOptions as $dbKey)
                                {
                                    $configRegistry->set( $dbKey, "{DATABASE_" . strtoupper( $dbKey ) . "}" );
                                }

                                $pathOptions = [
                                    'cache_path' ,
                                    'tmp_path'   ,
                                    'log_path'   ,
                                ];
                                foreach ($pathOptions as $pathOption)
                                {
                                    if (isset($configArray[$pathOption]))
                                    {
                                        $configRegistry->set( $pathOption, str_replace(JPATH_ROOT, "{JPATH_ROOT}",  $configArray[$pathOption]));
                                    }
                                }

                                $disableOptions = [
                                    'mailonline'
                                ];
                                foreach ($disableOptions as $disableOption)
                                {
                                    $configRegistry->set( $disableOption, false );
                                }

                                $emptyOptions = [
                                    'live_site'
                                ];
                                foreach ($emptyOptions as $emptyOption)
                                {
                                    $configRegistry->set( $emptyOption, '');
                                }

                                $configRegistry->set("cookie_path", "{COOKIE_PATH}");

                                $configContent = $configRegistry->toString('PHP', ['class' => 'JConfig', 'closingtag' => false]);

                            }
                        }
                        if ($isConfigFile)
                        {
                            $added = $zip->addFromString($relativePath, $configContent);
                        }
                        else
                        {
                            $added       = $zip->addFile( $filePath, $relativePath );
                        }
                        $compression = $zip->setCompressionName( $relativePath, ZipArchive::CM_DEFLATE, 7 );
                        if ( $encrypt === self::YSTS_ENCRYPT_ALL )
                        {
                            $encrypted = $zip->setEncryptionName( $relativePath, ZipArchive::EM_AES_256 );
                        }
                        else if ( $encrypt === self::YSTS_ENCRYPT_CONFIG && $fileName === 'configuration.php' )
                        {
                            $encrypted = $zip->setEncryptionName( $relativePath, ZipArchive::EM_AES_256 );
                        }
                        // This is the uncompressed size - we can only get the compressed size after the archive is closed and compressed
                        $zipSize += $zip->statIndex( $zip->numFiles - 1 )['size'];

                        unset( $filesToProcess[$filesIndex] );
                    }
                    catch ( Throwable $e )
                    {
                        $returnData->errormessages[] = $e->getMessage();
                        $returnData->error           = 1;
                        $returnData->cloned          = 0;

                        return $returnData;
                    }

                    // Check size of zip file
                    if ( $zipSize > $maxSize )
                    {
                        // Close current zip and open a new one
                        $zip->close();

                        $processFilesData = serialize( $filesToProcess );
                        $written          = file_put_contents( $ProcessFilesStore, $processFilesData );

                        $zipSize              = 0;
                        $returnData->zipCount = $zipCount;

                        $returnData->cloned     = "filesongoing";
                        $returnData->messages[] = "File archiving and copying ongoing " . count( $filesToProcess ) . " files remain";
                        $returnData->pid        = $requestObject->pid;

                        return $returnData;
                    }
                }
            }
            $zip->close();

            $returnData->cloned = 'filescomplete';

            $returnData->messages[] = "File copying complete";

            return $returnData;
        }
        else if ( ! empty ( $requestObject->cloned ) && $requestObject->cloned === 'filescomplete' )
        {
            $returnData->cloned = 1;

            $returnData->messages[] = "Cloning is complete";

            return $returnData;
        }
        else if ( ! empty ( $requestObject->cloned ) && $requestObject->cloned === 'databaseongoing' )
        {
            $processedViews  = $requestObject->processedViews ?? [];
            $processedTables = $requestObject->processedTables ?? [];

            $oldPrefix        = $db->getPrefix();
            $oldPrefixEscaped = str_replace( "_", "\_", $oldPrefix );

            list($views, $tables) = self::getViewsTables($dbname, $oldPrefixEscaped);

            $views  = array_diff($views, $processedViews );
            $tables = array_diff($tables, $processedTables );

            $tables = array_slice($tables, 0, $tableGroupSize);
            if (count($tables) === $tableGroupSize)
            {
                $views = [];
            }
            else
            {
                $views = array_slice($views, 0, $tableGroupSize);
                if (count($views) == 1 && $views[0] === "djurybgkuhrfgiybdryubdr")
                {
                    $views = [];
                }
            }

            // views count of 1 means just the dummy file so no need for a dump!
            if (count($tables) == 0 && count($views) == 0)
            {

                // set a new request action using the cloned field - we don't set returnData since we are doing a direct request
                $requestObject->cloned = 'databasedumped';
                return self::createArchive($requestObject, $returnData);

            }

            $returnData->processedTables = array_merge($processedTables, $tables);
            $returnData->processedViews  = array_merge($processedViews,  $views);
            $returnData->processingTables = $tables;
            $returnData->processingViews  = $views;

            $config = new JConfig();

            include_once( "rclibrary/ysts_mysqldump.php" );

            // Set up the prefix rewriting in the database output script
            Ifsnop\Mysqldump\CompressCleaned::$newPrefix = $newPrefix;
            Ifsnop\Mysqldump\CompressCleaned::$oldPrefix = $oldPrefix;

            $dump = new Ifsnop\Mysqldump\Ysts_Mysqldump(
                'mysql:host=' . $config->host . ';dbname=' . $config->db,
                $config->user,
                $config->password,
                array(
                    'include-tables' => $tables,
                    'include-views'  => $views,
                    // Do not add comments to dump file
                    'skip-comments'  => true, // native
                    // Add DROP TABLE statement before each CREATE TABLE statement
                    'add-drop-table' => true,
                    // if rows are too long PHP loses connection to the database
                    // Buffer size for TCP/IP and socket communication
                    'net_buffer_length' => 250000,
                    // without this we get a time_zone variable not defined error
                    // Turn off tz-utc
                    'skip-tz-utc'    => false,
                    // Enclose the INSERT statements for each dumped table within SET autocommit = 0 and COMMIT statements
                    'no-autocommit'  => false,
                    // Use complete INSERT statements that include column names
                    //'complete-insert'=> true,
                    // Use multiple-row INSERT syntax
                    //'extended-insert' => false,
                    // Compress all information sent between client and server
                    'compress'       => Ifsnop\Mysqldump\Ysts_Mysqldump::CLEANED,

                )
            );
            if ( file_exists( JPATH_BASE . YstsSiteArchive::ZIP_FOLDER . '/dump.sql' ) )
            {
                unlink( JPATH_BASE . YstsSiteArchive::ZIP_FOLDER . '/dump.sql' );
            }
            $dump->start( JPATH_BASE . YstsSiteArchive::ZIP_FOLDER . '/dump.sql' );

            /* Initialize archive object */
            if ( file_exists( JPATH_BASE . YstsSiteArchive::ZIP_FOLDER . '/dump.sql' ) )
            {
                $zip      = new ZipArchive;
                $zip_open = $zip->open( JPATH_BASE . YstsSiteArchive::ZIP_FOLDER . $sql_zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE );
                if ( $encrypt !== self::YSTS_ENCRYPT_NONE )
                {
                    $zip->setPassword( $password );
                }

                $encryptionSupported = version_compare(PHP_VERSION, "8.0", "ge") && method_exists($zip, "isEncryptionMethodSupported") && $zip->isEncryptionMethodSupported( ZipArchive::EM_AES_256 );
                if ( ! $encryptionSupported )
                {
                    $returnData->messages[] = "COM_YOURSITES_REMOTE_ENCRYPTION_NOT_SUPPORTED";
                    $encrypt = self::YSTS_ENCRYPT_NONE;
                }

                $added       = $zip->addFile( JPATH_BASE . YstsSiteArchive::ZIP_FOLDER . '/dump.sql', 'dump.sql' );
                $compression = $zip->setCompressionName( 'dump.sql', ZipArchive::CM_DEFLATE, 7 );
                if ( $encrypt !== self::YSTS_ENCRYPT_NONE )
                {
                    $encrypted = $zip->setEncryptionName( 'dump.sql', ZipArchive::EM_AES_256 );
                }

                $relativePath = 'foreignConstraints.json';
                $oldPrefix        = $db->getPrefix();
                $added = $zip->addFromString($relativePath, str_replace($oldPrefix, $newPrefix, json_encode($dump->foreignConstraints)));
                $compression = $zip->setCompressionName( $relativePath, ZipArchive::CM_DEFLATE, 7 );
                if ( $encrypt === self::YSTS_ENCRYPT_ALL )
                {
                    $encrypted = $zip->setEncryptionName( $relativePath, ZipArchive::EM_AES_256 );
                }

                $zip->close();
                $returnData->messages[] = "COM_YOURSITES_REMOTE_CLONE_DATABASE_DUMP_CREATED";

                unlink( JPATH_BASE . YstsSiteArchive::ZIP_FOLDER . '/dump.sql' );

            }

            $returnData->messages[] = "COM_YOURSITES_REMOTE_CLONE_DATABASE_DUMP_CREATED_2";

            $returnData->cloned = "databaseongoing";
            $returnData->result = "Database Dump Ongoing";
            $returnData->pid    = $requestObject->pid;
            $returnData->sqlZipCount = $sqlZipCount;

            return $returnData;

        }
        else
        {
            // if only backing up files then first step is files so we simulate database as having been dumped
            if ($backupSelection === 'files')
            {
                // set a new request action using the cloned field - we don't set returnData since we are doing a direct request
                $requestObject->cloned = 'databasedumped';
                return self::createArchive($requestObject, $returnData);
            }

            $oldPrefix        = $db->getPrefix();
            $oldPrefixEscaped = str_replace( "_", "\_", $oldPrefix );

            list($views, $tables) = self::getViewsTables($dbname, $oldPrefixEscaped);

            $tables = array_slice($tables, 0, $tableGroupSize);
            $views  = [];

            $returnData->processedTables = $tables;
            $returnData->processedViews  = $views;
            $returnData->processingTables = $tables;
            $returnData->processingViews  = $views;

            $config = new JConfig();

            include_once( "rclibrary/ysts_mysqldump.php" );

            // Set up the prefix rewriting in the database output script
            Ifsnop\Mysqldump\CompressCleaned::$newPrefix = $newPrefix;
            Ifsnop\Mysqldump\CompressCleaned::$oldPrefix = $oldPrefix;

            $dump = new Ifsnop\Mysqldump\Ysts_Mysqldump(
                'mysql:host=' . $config->host . ';dbname=' . $config->db,
                $config->user,
                $config->password,
                array(
                    'include-tables' => $tables,
                    'include-views'  => $views,
                    // Do not add comments to dump file
                    'skip-comments'  => true, // native
                    // Add DROP TABLE statement before each CREATE TABLE statement
                    'add-drop-table' => true,
                    // if rows are too long PHP loses connection to the database
                    // Buffer size for TCP/IP and socket communication
                    'net_buffer_length' => 250000,
                    // without this we get a time_zone variable not defined error
                    // Turn off tz-utc
                    'skip-tz-utc'    => false,
                    // Enclose the INSERT statements for each dumped table within SET autocommit = 0 and COMMIT statements
                    'no-autocommit'  => false,
                    // Use complete INSERT statements that include column names
                    //'complete-insert'=> true,
                    // Use multiple-row INSERT syntax
                    //'extended-insert' => false,
                    // Compress all information sent between client and server
                    'compress'       => Ifsnop\Mysqldump\Ysts_Mysqldump::CLEANED,

                )
            );
            if ( file_exists( JPATH_BASE . YstsSiteArchive::ZIP_FOLDER . '/dump.sql' ) )
            {
                unlink( JPATH_BASE . YstsSiteArchive::ZIP_FOLDER . '/dump.sql' );
            }
            $dump->start( JPATH_BASE . YstsSiteArchive::ZIP_FOLDER . '/dump.sql' );

            /* Initialize archive object */
            if ( file_exists( JPATH_BASE . YstsSiteArchive::ZIP_FOLDER . '/dump.sql' ) )
            {
                $zip      = new ZipArchive;
                $zip_open = $zip->open( JPATH_BASE . YstsSiteArchive::ZIP_FOLDER . $sql_zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE );
                if ( $encrypt !== self::YSTS_ENCRYPT_NONE )
                {
                    $zip->setPassword( $password );
                }

                $encryptionSupported = version_compare(PHP_VERSION, "8.0", "ge") && method_exists($zip, "isEncryptionMethodSupported") && $zip->isEncryptionMethodSupported( ZipArchive::EM_AES_256 );
                if ( ! $encryptionSupported )
                {
                    $returnData->messages[] = "COM_YOURSITES_REMOTE_ENCRYPTION_NOT_SUPPORTED";
                    $encrypt = self::YSTS_ENCRYPT_NONE;
                }

                $added       = $zip->addFile( JPATH_BASE . YstsSiteArchive::ZIP_FOLDER . '/dump.sql', 'dump.sql' );
                $compression = $zip->setCompressionName( 'dump.sql', ZipArchive::CM_DEFLATE, 7 );
                if ( $encrypt !== self::YSTS_ENCRYPT_NONE )
                {
                    $encrypted = $zip->setEncryptionName( 'dump.sql', ZipArchive::EM_AES_256 );
                }

                $relativePath = 'foreignConstraints.json';
                $oldPrefix        = $db->getPrefix();
                $added = $zip->addFromString($relativePath, str_replace($oldPrefix, $newPrefix, json_encode($dump->foreignConstraints)));
                $compression = $zip->setCompressionName( $relativePath, ZipArchive::CM_DEFLATE, 7 );
                if ( $encrypt === self::YSTS_ENCRYPT_ALL )
                {
                    $encrypted = $zip->setEncryptionName( $relativePath, ZipArchive::EM_AES_256 );
                }

                $zip->close();
                $returnData->messages[] = "COM_YOURSITES_REMOTE_CLONE_DATABASE_DUMP_CREATED";

                unlink( JPATH_BASE . YstsSiteArchive::ZIP_FOLDER . '/dump.sql' );

            }

            $returnData->messages[] = "COM_YOURSITES_REMOTE_CLONE_DATABASE_DUMP_CREATED_2";

            $returnData->cloned = "databaseongoing";
            $returnData->result = "Database Dump Started";
            $returnData->pid    = $requestObject->pid;
            $returnData->sqlZipCount = $sqlZipCount;
            return $returnData;

        }

        $returnData->messages[] = "COM_YOURSITES_REMOTE_CLONE_DATABASE_DUMP_CREATED_2";

        $returnData->cloned = "databasedumped";
        $returnData->result = "Database Dump Created";
        $returnData->pid    = $requestObject->pid;
        $returnData->sqlZipCount = $sqlZipCount;

        return $returnData;

    }

    public static function clearArchive( $requestObject, &$returnData ) {

        if (isset($requestObject->archive) && !empty($requestObject->archive))
        {
            $filename = $requestObject->archive;
            $realfilename = JPATH_SITE . YstsSiteArchive::ZIP_FOLDER . $requestObject->archive;
            if (strpos($filename, ".zip") == strlen($filename)  - 4 && file_exists($realfilename) && strpos($filename, "ysts_") === 0)
            {
                unlink($realfilename);

                $returnData->messages[] = "Archive File $filename removed from source server";
                return;
            }

        }
        $returnData->error           = 1;
        $returnData->errormessages[] = "Archive File could not be removed from source server";
        return;
    }


    private static function getViewsTables($dbname, $oldPrefixEscaped) {
        $db     = Factory::getDbo();

        $db->setQuery( "SHOW FULL TABLES from `$dbname` WHERE `Tables_in_" . $dbname . "` like '" . $oldPrefixEscaped . "%' AND TABLE_TYPE LIKE 'VIEW'" );
        // Make sure we can count on the order of the columns!
        $views = $db->loadColumn( 0 );

        $db->setQuery( "SHOW FULL TABLES from `$dbname` WHERE `Tables_in_" . $dbname . "` like '" . $oldPrefixEscaped . "%' AND TABLE_TYPE NOT LIKE 'VIEW'" );
        // Make sure we can count on the order of the columns!
        $tables = $db->loadColumn( 0 );

        // re-order the tables so that tables referred to as foreign keys are created first
        $db->setQuery( "SELECT * FROM information_schema.key_column_usage WHERE REFERENCED_TABLE_NAME <> '' AND  TABLE_SCHEMA = '$dbname' AND TABLE_NAME like '" . $oldPrefixEscaped . "%'" );
        $foreignKeyTables = $db->loadObjectList();
        if ( $foreignKeyTables && count( $foreignKeyTables ) )
        {
            $fktCount = count( $foreignKeyTables );

            $referencedTables = array();
            for ( $fkt = 0; $fkt < $fktCount; $fkt ++ )
            {
                if ( ! in_array( $foreignKeyTables[$fkt]->REFERENCED_TABLE_NAME, $referencedTables ) )
                {
                    $referencedTables[$foreignKeyTables[$fkt]->REFERENCED_TABLE_NAME] = $foreignKeyTables[$fkt]->REFERENCED_TABLE_NAME;
                }
            }

            // Make sure $referencedTables are in the right order to avoid table => foreign_table1 => foreign_table2 problems
            $newReferencedTables = array();
            foreach ( $referencedTables as $referencedTableName => $referencedTableData )
            {
                for ( $fkt = 0; $fkt < $fktCount; $fkt ++ )
                {
                    // The table itself has a reference so extract it
                    if ( $foreignKeyTables[$fkt]->TABLE_NAME == $referencedTableName )
                    {
                        unset( $referencedTables[$referencedTableName] );
                        $newReferencedTables[$referencedTableName] = $referencedTableName;
                    }
                }
            }
            $referencedTables = array_merge( $newReferencedTables, $referencedTables );

            // we DONT need these in the reverse order since the unshift below reverses them already!
            //$referencedTables = array_reverse($referencedTables);

            foreach ( $referencedTables as $referencedTableName => $referencedTableData )
            {
                $tableIndex = array_search( $referencedTableName, $tables );
                if ( $tableIndex !== false )
                {
                    $tableToMove = $tables[$tableIndex];
                    unset( $tables[$tableIndex] );
                    array_unshift( $tables, $tableToMove );
                    //array_push($tables, $tableToMove);

                }
            }
            $tables = array_values( $tables );
        }

        // Add a dummy view to force the dump to ignore the other views in the database
        $views[] = "djurybgkuhrfgiybdryubdr";


        return array($views, $tables);
    }
}
