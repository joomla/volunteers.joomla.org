<?php die();?>
Akeeba Backup 8.1.3
================================================================================
+ Restoration: ANGIE now applies very high memory and execution time limits to prevent some timeout / memory outage issues on most hosts.
+ Restoration: ANGIE now warns you if you leave the database connection information empty
+ Option to set a really large PHP memory limit during backup
~ More helpful and forceful installation abortion message when you try to install this package on Joomla 4.1 or later.
# [LOW] The JPS archiver would show warnings about unreadable files when archiving directories without any files in them.

Akeeba Backup 8.1.2
================================================================================
~ Make it clearer that you need Akeeba Backup 9 on Joomla 4.
# [HIGH] Uploading to OVH is broken on many servers not using a proxy
# [HIGH] Wrong RewriteBase set up in the .htaccess Maker when restoring a Joomla site with Admin Tools Professional installed

Akeeba Backup 8.1.1
================================================================================
~ PHP 8.1 compatibility changes

Akeeba Backup 8.1.0
================================================================================
+ Allow using [REMOTESTATUS] in the email subject, not just the body
+ Joomla restoration: modify domains in the Admin Tools' Allowed Domains and server config maker features if necessary
# [HIGH] Problems restoring if a table name ends in 0 when another table with an identical name EXCEPT the trailing zero is also being backed up
# [HIGH] Backing up to SQL: indices would not have the correct table name prefix
# [HIGH] Backing up as SQL: the query for finder_taxonomy does not use the correct prefix
# [MEDIUM] Log Priorities global configuration option got mangled restoring a Joomla 4 site
# [HIGH] PHP fatal error on PHP 8 if the output directory does not exist
# [LOW] Occasional display issue on Chromium browsers with the database and file / folder filter pages.
# [LOW] RackSpace CloudFiles: some hosts change the case of HTTP headers

Akeeba Backup 8.0.15
================================================================================
+ Support for MySQL 8 invisible columns
# [LOW] Rare type error under PHP 8 during restoration
# [LOW] Backend still tries to load PieCon, causing an error to be logged

Akeeba Backup 8.0.14
================================================================================
- Remove piecon (pie graph favicon showing the backup progress)
~ JSON API: Forcibly use the ‘json’ origin everywhere
~ JSON API: Throw an error if the backup ID sent to stepBackup does not exist
~ JSON API: Improved backup IDs prevent a number of JSON API issues

Akeeba Backup 8.0.13
================================================================================
- Removed iDriveSync; the service has been discontinued by the provider.
- Removed the “Archive integrity check” feature.
~ Dropbox connector updated to require TLS v1.2
~ Improved the display of the files and folders filters page
# [LOW] Check failed backups: All Super Users were notified even when an email was supplied

Akeeba Backup 8.0.12
================================================================================
# [LOW] PHP 8 error if the output directory is empty

Akeeba Backup 8.0.11
================================================================================
~ Remove dash from automatically generated random values for archive naming
+ Increase the maximum Size Quota limit to 1Pb
+ Support for Joomla proxy configuration
# [MEDIUM] Cannot restore on PHP 8 if Two Factor Authentication is enabled in any user account
# [HIGH] Backing up to Box, Dropbox, Google Drive or OneDrive may not be possible if you are using an add-on Download ID

Akeeba Backup 8.0.10
================================================================================
# [HIGH] Uninstallation broken on Joomla 4 due to different installation script event handling (wow, they even broke components' uninstallation, not just the packages!).
# [LOW] Warning in Manage Backups page if you have deleted the backup profile used to take a backup listed there

Akeeba Backup 8.0.9
================================================================================
~ Changes in Joomla 4.0.0-RC5 broke the date and time input fields. Now using native HTML 5 controls.
# [HIGH] Uninstallation broken on Joomla 4 due to different installation script event handling.

Akeeba Backup 8.0.8
================================================================================
! Exception when you do not have the package extension installed on your site. Shouldn't happen unless you've messed with your database.

Akeeba Backup 8.0.7
================================================================================
+ Restoration: information about disabling the password protection.
~ Remove ROW_FORMAT during backup and restoration, makes it easier restoring sites using InnoDB across different MySQL server versions
~ Joomla changed the location of cacert.pem, breaking backup upload to remote storage on some servers
# [HIGH] Remote JSON API v2 fails on PHP 8
# [MEDIUM] Tables with only numbers in their names cause the backup to fail

Akeeba Backup 8.0.6
================================================================================
! Encrypted settings could not be read

Akeeba Backup 8.0.5
================================================================================
- Removed Upload to pCloud
+ Only show failed backups' log files in ALICE
+ Stealth Mode support in integrated restoration
# [MEDIUM] Backend backup may fail when using multiple profiles with different output directories.
# [LOW] MySQL spatial data might be impossible to restore if there is a collation mismatch between the origin and target server.
# [LOW] PHP 8 could still throw an error while backing up under some rare circumstances
# [LOW] Fixed fatal error while sending backup email notification under certain server configuration
# [LOW] PHP warning when the site's root is in an absolute root subdirectory (e.g. /site instead)

Akeeba Backup 8.0.4
================================================================================
# [MEDIUM] Failed backups check would show old failed backups again after visiting Akeeba Backup's Control Panel page
# [MEDIUM] Backup on Update message was never shown
# [LOW] JSON API could return additional information around the JSON content when XDebug is enabled
# [LOW] Backup on Update boolean controls appear inverted (Yes is No and vice-versa)

Akeeba Backup 8.0.3
================================================================================
~ Rewritten installer plugin
~ Converted all tables to InnoDB for better performance
# [HIGH] Cannot take split archive backups under PHP 8
# [HIGH] Backup on Update message shown to non-Super Users
# [HIGH] Latest backup restoration backend menu item didn't work
# [LOW] Annoying error message, without any real consequence, shown when clicking any feature button before checking the output folder security has completed in the background

Akeeba Backup 8.0.2
================================================================================
! Update fails on some hosts which use opcache if the any of our software's installer plugin is enabled, you have gone through the Joomla Control Panel (with the extension updates quickicon plugin enabled) or the Extensions Update page before installing the new version, either as an automatic update or by manual installation (upload & install or install from URL).
~ Will no longer uninstall FOF 3 even if it's no longer needed due to broken THIRD PARTY extensions using it.
~ Workaround for Joomla bug which may not install the included FEF version 2 framework completely, leading to the component being broken after the update.
~ Servers with opcache may report that FOF 4 classes are missing even though they are actually there.

Akeeba Backup 8.0.1
================================================================================
! Update could fail on sites with old plugins we have removed years ago still installed

Akeeba Backup 8.0.0
================================================================================
+ Rewritten with FOF 4
+ Now using FEF 2 with a common JavaScript library across all Akeeba extensions
+ Renamed ViewTemplates to tmpl (Joomla 4 convention, with fallback code for Joomla 3)
+ Yes/No options in the component and plugin options now work correctly under Joomla 4.0 beta 7 and later
# [HIGH] Dropbox for Business wouldn't work with the new scoped access tokens
# [HIGH] Dropbox refresh token would disappear after the first refresh, making it impossible to use Dropbox reliably

Akeeba Backup 7.5.2
================================================================================
+ Rewritten Backup on Update plugin for improved UX (gh-685)
+ Joomla 4: backup profile selection uses Choices.js for easier navigation among many backup profiles
~ Internals: normalised use JVERSION conditionals
~ Document Microsoft Edge “sleeping tabs” and workarounds for long-running backups in background browser tabs
~ Improved CHANGELOG layout in the Control Panel page
~ Code modernisation: using built-in random_bytes() instead of OpenSSL or mcrypt for random number generation
# [HIGH] Import from S3: you cannot select .jps files
# [MEDIUM] Frozen backups toggle wouldn't work on Joomla 4
# [LOW] Import from S3: invisible breadcrumbs in Dark Mode
# [LOW] Recommended PHP version was shown as 7.3 instead of 7.4
# [LOW] Unable to access the component on Joomla 4 when using the PDOMySQL database driver with Site Debug enabled, see https://github.com/joomla/joomla-cms/issues/32019

Akeeba Backup 7.5.1
================================================================================
+ Post-backup emails can now display the total backup size and the approximate size of each part file
# [HIGH] The Joomla 4 console plugin could not install due to a bug in Joomla 4's plugins installer code
# [HIGH] Backup failure to S3 with a PHP Type Error when the Dual Stack option has no value
# [HIGH] Uploading to Dropbox would fail if you linked your Dropbox account after December 2020
# [LOW] No list of backup files in the post-backup email when using a post-processing engine
# [LOW] Manage Remotely Stored Files actions could fail on Box, Dropbox, OneDrive and Google Drive if the access token had expired in the meantime.

Akeeba Backup 7.5.0.1
================================================================================
! [HIGH] The Backup on Update plugin can cause the site to fail to load

Akeeba Backup 7.5.0
================================================================================
- Dropped support for PHP 7.1.0
+ Dropbox is now using the scoped API access.
+ Amazon S3: Added support for Dual Stack option (use of IPv6 when available)
+ Joomla 4 CLI (joomla.php) support – full CLI client to Akeeba Backup
~ Add PHP 8.0 in the list of known PHP versions, recommend PHP 7.4 or later
~ Remove the JPS and ANGIE password fields from the Backup Now page. You can still configure these features in the backup profile's Configuration page.
# [MEDIUM] PHP 8: fatal error uploading to Amazon S3, CloudFiles
# [LOW] Using [SITENAME] in a backup archive name resulted in a single dash being output.
# [LOW] UI elements in the the Files and Folders Exclusion pages would still show native tooltips with HTML tags in them.
# [HIGH] Joomla 4 beta 6 changes how the session works, breaking everything.

Akeeba Backup 7.4.0.1
================================================================================
! Akeeba Backup Core: cannot access the plugin or take a backup because of a PHP error due to an incorrect reference to a Pro-only class.
# [LOW] Backup failure with an error if you import a profile that uses a post-processing engine created with the Pro version into the Core version which does not have this post-processing engine.

Akeeba Backup 7.4.0
================================================================================
+ Files and Directories Exclusion: mark folder and file symlinks as such [gh-676]
+ Automatically rewrite the Output Directory using site path variables such as [SITEROOT] for portability [gh-678]
+ Automatically rewrite the Off-site Folders Inclusion using site path variables for portability
+ Remote backup JSON API version 2
+ ANGIE: Added feature to resume restoring the database if an error occurs
~ Deprecated Upload to pCloud
~ Removed tooltips from Database Tables Exclusion and Files and Folders Exclusion pages to clean up the UI
~ Using nullable TIMESTAMP fields instead of zero dates
# [MEDIUM] Recent Chrome and Chromium-based browsers open OAuth2 windows without opener information, making linking to Google Drive, Dropbox etc impossible without manually copying the tokens (the button causes you to log out of the site)
# [LOW] Files and Directories Exclusion: the folder up is not clickable / doesn't do anything [gh-675]
# [LOW] Scheduling information button appears in the Configuration Wizard's finale page in the Core version

Akeeba Backup 7.3.2.1
================================================================================
! CLI backups broken in version 7.3.2
# [LOW] PHP notices from Joomla core code when running akeeba-check-failed.php in CLI

Akeeba Backup 7.3.2
================================================================================
- Removed update notifications inside the component
~ Normalized the default backup description under all backup methods (backend, frontend, CLI, JSON API)
# [HIGH] WebDAV fails to upload because of the wrong absolute URL being calculated
# [MEDIUM] The Resume and Cancel buttons in the backend backup didn't work due to a typo in the JavaScript
# [MEDIUM] Restoring a JPS backup archive through the integrated restoration was broken if it contained charactes other than a-z, A-Z, 0-9, dash, dot or underscore.
# [LOW] pCloud was erroneously listed in the free of charge Core version (it requires a paid subscription and was thus unusable)

Akeeba Backup 7.3.1
================================================================================
- Removed the System - Akeeba Backup Update Check plugin
~ Improved unhandled PHP exception error page
# [HIGH] Media query strings missing from JavaScript, causing issues to people upgrading from 7.2.2 or earlier.
# [LOW] Frontend backup URL does not work if the secret key contains the plus sign (+) character due to a PHP bug.

Akeeba Backup 7.3.0
================================================================================
+ S3: Add support for Cape Town and Milan regions
+ Inherit the base font size instead of defining a fixed one
+ Added feature to "freeze" some backup records to keep them indefinitely
- Removed support for Internet Explorer
~ Improve default header and body fonts for similar cross-platform "feel" without the need to use custom fonts.
~ Rendering improvements
~ Loading all JavaScript defered
~ Do not show the Backup on Update icon when Joomla is in record add / edit mode (main menu hidden and status bar locked).
~ Adjust size of control panel icons
~ More clarity in the in-component update notifications, explaining they come from Joomla itself
# [HIGH] Replacing (not just removing) AddHandler/SetHandler lines would fail during restoration
# [MEDIUM] Fetching back to server the archives from these provides would result in invalid archives: Amazon S3, Backblaze, Cloudfiles, OVH, Swift
# [MEDIUM] Greedy RegEx match in database dump could mess up views containing the literal ' view ' (word "view" surrounded by spaces) in their definition.

Akeeba Backup 7.2.2
================================================================================
+ Automatic UTF8MB4 character encoding downgrades from MySQL 8 to 5.7/5.6/5.5 on restoration.
# [LOW] The package would install on unsupported PHP versions 5.6 and 7.0 and Joomla 3.8, leading to errors
# [HIGH] The System - Akeeba Backup Update Check plugin throws a fatal error since version 7.1.4 when an update is available

Akeeba Backup 7.2.1
================================================================================
~ Small change in the FOF library to prevent harmless but confusing and annoying errors from appearing during upgrade
~ The following items are carried over from unpublished version 7.2.0
+ Restoration: Enable UTF8MB4 compatibility detection by default
~ Minimum requirements raised to PHP 7.1, Joomla 3.9
~ Using Joomla's cacert.pem instead of providing our own copy
~ Component Options page looks a bit nicer on Joomla 4
~ Joomla 4: fix profile selection drop-down display
# [HIGH] The restoration script can't read unquoted numeric values from the configuration.php file (used in Joomla 4)
# [HIGH] Joomla 4: Using the Smart Search filter during backup makes it impossible to use Smart Search on the restored site.
# [HIGH] Import from S3: infinite redirection loop
# [LOW] Very rare backup failures with a JS error
# [LOW] Unhandled exception page was incompatible with Joomla 4

Akeeba Backup 7.2.0
================================================================================
+ Restoration: Enable UTF8MB4 compatibility detection by default
~ Minimum requirements raised to PHP 7.1, Joomla 3.9
~ Using Joomla's cacert.pem instead of providing our own copy
~ Component Options page looks a bit nicer on Joomla 4
~ Joomla 4: fix profile selection drop-down display
# [HIGH] The restoration script can't read unquoted numeric values from the configuration.php file (used in Joomla 4)
# [HIGH] Joomla 4: Using the Smart Search filter during backup makes it impossible to use Smart Search on the restored site.
# [HIGH] Import from S3: infinite redirection loop
# [LOW] Very rare backup failures with a JS error
# [LOW] Unhandled exception page was incompatible with Joomla 4

Akeeba Backup 7.1.4
================================================================================
~ Now getting Super Users list using core Joomla API instead of direct database queries
# [LOW] Multipart upload to BackBlaze B2 might fail due to a silent B2 behavior change
# [LOW] OneDrive upload failure if a part upload starts >3600s after token issuance

Akeeba Backup 7.1.3
================================================================================
~ Got rid of the Optimize JavaScript feature.

Akeeba Backup 7.1.2
================================================================================
# [LOW] The Optimize JavaScript was not working properly on some low end servers due to the way browsers parse deferred scripts at the bottom of the HTML body

Akeeba Backup 7.1.1
================================================================================
~ Possible exception when the user has erroneously put their backup output directory to the site's root with open_basedir restrictions restricting access to its parent folder.
# [HIGH] The Optimize JavaScript option causes a missing class fatal error on Joomla! 3.8 sites
# [LOW] Missing icon in Manage Backups page, Import Archive toolbar button

Akeeba Backup 7.1.0
================================================================================
+ Automatic security check of the backup output directory
+ Automatic JavaScript bundling for improved performance
~ Improved storage of temporary data during backup [akeeba/engine#114]
~ Log files now have a .php extension to prevent unauthorized access in very rare cases
~ Enforce the recommended, sensible security measures when using the default backup output directory
~ Ongoing JavaScript refactoring
~ Google Drive: fetch up to 100 shared drives (previously: up to 10)
# [HIGH] An invalid output directory (e.g. by importing a backup profile) will cause a fatal exception in the Control Panel (gh-667)
# [MEDIUM] CloudFiles post-processing engine: Fixed file uploads
# [MEDIUM] Swift post-processing engine: Fixed file uploads
# [LOW] Send by Email reported a successful email sent as a warning
# [LOW] Database dump: foreign keys' (constraints) and local indices' names did not get their prefix replaced like tables, views etc do

Akeeba Backup 7.0.2
================================================================================
~ Log the full path to the computed site's root, without <root> replacement
~ Use Chosen in the Control Panel's profile selection page
# [HIGH] Core (free of charge) version only: the PayPal donation link included a tracking pixel. Changed to donation link, without tracking.
# [HIGH] Core (free of charge) version only: the system/akeebaupdatecheck plugin would always throw an error
# [HIGH] Restoration will fail if a table's name is a superset of another table's name e.g. foo_example_2020 being a superset of foo_example_2.
# [MEDIUM] WebDav post-processing engine: first backup archive was always uploaded on the remote root, ignoring any directory settings

Akeeba Backup 7.0.1
================================================================================
- pCloud: removing download to browser (cannot work properly due to undocumented API restrictions)
# [HIGH] An error about not being able to open a file with an empty name occurs when taking a SQL-only backup but there's a row over 1MB big
# [LOW] Schedule Automatic Backups shown in the Configuration page of the Core version
# [LOW] A secret work would not be proposed when one was not set or set to something insecure
# [LOW] The akeeba-altbackup.php and akeeba-altcheck-failed.php CRON scripts falsely report front-end backup is not enabled
# [LOW] Dark Mode: modal close icon was invisible both in the backup software and during restoration
# [LOW] Fixed automatically filling DropBox tokens after OAuth authentication

Akeeba Backup 7.0.0
================================================================================
+ Custom description for backups taken with the Backup on Update plugin
+ Remove TABLESPACE and DATA|INDEX DIRECTORY table options during backup
# [LOW] Fixed applying quotas for obsolete backups

Akeeba Backup 7.0.0.rc1
================================================================================
+ Upload to OVH now supports Keystone v3 authentication, mandatory starting mid-January 2020
# [HIGH] An error in an early backup domain could result in a forever-running backup
# [HIGH] DB connection errors wouldn't result in the backup failing, as it should be doing

Akeeba Backup 7.0.0.b3
================================================================================
+ Common PHP version warning scripts
+ Reinstated support for pCloud after they fixed their OAuth2 server
~ Improved Dark Mode
~ Improved PHP 7.4 compatibility
~ Improved Joomla 4 styling
~ Clearer message when setting decryption fails in CLI backup script
~ Remove JavaScript eval() from FileFilters page
# [HIGH] The database dump was broken with some versions of PCRE (e.g. the one distributed with Ubuntu 18.04)
# [HIGH] Site Transfer Wizard inaccessible on case-sensitive filesystems

Akeeba Backup 7.0.0.b2
================================================================================
- Removed pCloud support
+ ANGIE: Options to remove AddHandler lines on restoration
# [MEDIUM] Fixed OAuth authentication flow
# [MEDIUM] Fixed fatal error under Joomla 3.8.x

Akeeba Backup 7.0.0.b1
================================================================================
+ Amazon S3 now supports Bahrain and Stockholm regions
+ Amazon S3 now supports Intelligent Tiering, Glacier and Deep Archive storage classes
+ Google Storage now supports the nearline and coldline storage classes
+ Manage Backups: Improved performance of the Transfer (re-upload to remote storage) feature
+ Windows Azure BLOB Storage: download back to server and download to browser are now supported
+ New OneDrive integration supports both regular OneDrive and OneDrive for Business
+ pCloud support
+ Support for Dropbox for Business
+ Dark Mode support
+ Support for Joomla 4 Download Key management in the Update Sites page
+ Minimum required PHP version is now 5.6.0
~ All views have been converted to Blade for easier development and better future-proofing
~ The integrated restoration feature is now only available in the Professional version
~ The archive integrity check feature is now only available in the Professional version
~ The front-end legacy backup API and the Remote JSON API are now available only in the Professional version and can be enabled / disabled independently of each other
~ The Site Transfer Wizard is now only available in the Professional version
~ SugarSync integration: you now need to provide your own access keys following the documentation instructions
~ Backup error handling and reporting (to the log and to the interface) during backup has been improved.
~ The Test FTP/SFTP Connection buttons now return much more informative error messages.
~ Manage Backups: much more informative error messages if the Transfer to remote storage process fails.
~ The backup and log IDs will follow the numbering you see in the left hand column of the Manage Backups page.
~ Manage Backups: The Remote File Management page is now giving better, more accurate information.
~ Manage Backups: Fetch Back To Server was rewritten to gracefully deal with more problematic cases.
~ Joomla 4: The backup on update plugin no longer displayed correctly after J4 changed its template, again.
~ Joomla 4: The backup quick icon was displayed in the wrong place after J4 changed its template, again and also partially broke backwards compatibility to how quick icon plugins work.
~ Removed AES encapsulations from the JSON API for security reasons. We recommend you always use HTTPS with the JSON API.
# [HIGH] CLI (CRON) scripts could sometimes stop with a Joomla crash due to Joomla's mishandling of the session under CLI.
# [HIGH] Changing the database prefix would not change it in the referenced tables inside PROCEDUREs, FUNCTIONs and TRIGGERs
# [HIGH] Backing up PROCEDUREs, FUNCTIONs and TRIGGERs was broken
# [MEDIUM] Database only backup of PROCEDUREs, FUNCTIONs and TRIGGERs does not output the necessary DELIMITER commands to allow direct import
# [MEDIUM] PHP Notice at the end of each backup step due to double attempt to close the database connection.
# [MEDIUM] BackBlaze B2: upload error when chunk size is higher than the backup archive's file size
# [LOW] Manage Backups: downloading a part file from S3 beginning with text data would result in inline display of the file instead of download.
