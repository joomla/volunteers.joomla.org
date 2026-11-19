Akeeba Backup 10.5.0
================================================================================
! Restoration: extraction accepted archive entries with dot paths in their name
! PHP and Joomla minimum and maximum version compatibility is now enforced
! Three exception-chain walkers looped forever instead of terminating once an exception had a previous exception
+ [HARDENING] Output directory root containment misidentified sibling folders sharing a name prefix
+ [HARDENING] Log viewer's profile switch now requires an anti-CSRF token and checks profile access
+ [HARDENING] Control Panel: profile switch now checks access
+ [HARDENING] Backup page: the profileid URL parameter now checks the user's access to that profile
+ [HARDENING] JSON API startBackup's overrides parameter has additional access checks under APIv3
+ [HARDENING] JSON API startBackup under APIv3 token auth now checks the caller's access to the target profile
+ [HARDENING] Engine finalisation queue now instantiates only classes implementing the expected interface
+ [HARDENING] Control Panel's Reset Secret Word action now requires core.admin, matching the Options page
+ [HARDENING] ALICE now renders the log content escaped
+ [HARDENING] Backup records list: freeze/unfreeze/archive/trash/report tasks now require akeebabackup.configure
+ [HARDENING] OAuth2 helper's step1/step2 flow now validates a single-use, session-bound anti-CSRF state
+ [HARDENING] Control Panel now warns when the OpenSSL extension is missing, since settings then store as plaintext
+ [HARDENING] Backup description is now escaped in the Control Panel's latest backup status cell
+ [HARDENING] Multipart archive part names are now escaped in the Manage view's archive info modal
+ [HARDENING] The backup comment reflected into the Backup view's textarea is now escaped
+ [HARDENING] Post-processing error messages are now escaped in the Upload view
+ [HARDENING] Four backend JS files now use textContent instead of innerHTML for server-supplied strings
+ [HARDENING] Manage and Restore views now escape the configurable date-format and time-zone-format output
+ [HARDENING] Uri::base() is now escaped for its HTML context and never interpolated into inline JS
+ [HARDENING] OAuth2 token display pages now mark themselves non-cacheable instead of publicly cacheable
+ [HARDENING] FTP download-to-browser no longer exposes the FTP username and password in a redirect header
+ [HARDENING] Control Panel no longer sends the Web Push VAPID private key to the browser
+ [HARDENING] Leftover files warning: Delete button now sends a POST with an anti-CSRF token, not a GET link
+ [HARDENING] Backup page: autostart=1 now requires an anti-CSRF token; without one the user starts the backup
+ [HARDENING] Quick Icon: the link no longer carries the session token or autostarts the backup
+ Added support for seven additional Amazon S3 regions
+ Import Archives from S3 now supports the custom endpoint of S3-compatible storage
+ Options: "Backend backup checks the anti-CSRF token" lets backups run on servers with broken session handling
- Removed legacy error handler
- Integrated restoration no longer offers the "Use the FTP layer" extraction option; Direct and Hybrid remain
- Removed "Upload to Microsoft OneDrive (LEGACY)" and "Upload to Microsoft OneDrive (App-Specific Folder)"
- Backup menu item no longer has a "Start immediately" option; it needs an anti-CSRF token menu items cannot carry
~ Declare the core.options permission so it can be granted per user group
~ Route every query through Helper\DbQuery::create(), keeping the createQuery() compatibility branch in one place
~ htmlentities() calls now pass explicit flags, so escaping no longer depends on the PHP version
~ Restoration: removed SFTP and "use FTP for all files" modes; Hybrid now falls back to FTP when a direct write fails
~ Restoration: the resume allow-list now derives from the extraction engine's class hierarchy, so it stays correct
~ PostgreSQL backup: only pass --no-policies to pg_dump 18+, older versions reject it
~ PostgreSQL backup: Exempt CREATE UNIQUE INDEX from the IF NOT EXISTS rewrite too
~ PostgreSQL backup: Quote jsonb values as text, not as hex-encoded BYTEA
~ PostgreSQL backup: Rewrite CREATE VIEW to OR REPLACE, not unsupported IF NOT EXISTS
~ Schedule Automatic Backups: warns about a weak Secret Word and strikes through the URLs and commands that need it
~ RegEx filters (database tables): warn when the saved regular expression is not valid
~ Backup Now: a backup step rejected for an invalid token shows its own error and records why in the backup log
# [HIGH] FilefiltersController was checking against the wrong privilege
# [HIGH] Front-end Akeeba Backup URLs caused a white page on PHP 7.4 sites with SEF URLs enabled
# [HIGH] "Check for failed backups/uploads" Scheduled Tasks fatally errored instead of logging their result
# [HIGH] Restoration: ZIP archives using a "fast" compression setting were reported corrupt after the first file
# [HIGH] Restoration: "delete files before extracting" fatally errored before deleting anything (progress reporter bug)
# [HIGH] Restoration: "delete files before extracting" also failed under restore.php via Kickstart-only zapper calls
# [HIGH] Restoration: Hybrid write mode could not extract multi-step archives; its snapshot was corrupted on save
# [HIGH] Restoration: removed the dead Mcrypt AES adapter; without OpenSSL, JPS decryption failed with a fatal error
# [HIGH] Restoration: a DB hostname with an underscore had its port number appended twice, breaking the connection
# [HIGH] akeeba:profile:modify/copy errored with "Incorrect integer value" unless --quickicon was explicitly truthy
# [HIGH] akeeba:filter:include-database always failed; it read two CLI options under names it never registered
# [HIGH] akeeba:filter:include-database stored the database username and dump file under keys nothing reads
# [HIGH] akeeba:profile:copy failed on every run with "Call to undefined method stdClass::getProperties()"
# [HIGH] Control Panel's first load after upgrading reset encrypted backup profiles to defaults when several existed
# [HIGH] Import Archives from S3 fatally errored: it used a connector class which no longer exists
# [HIGH] Backup Now failed with "The security token did not match" on servers which do not keep session data reliably
# [HIGH] Site Transfer Wizard skipped every other part of a multi-part backup, so Kickstart could not extract it
# [HIGH] Site Transfer Wizard: uploading a multi-part backup failed with an HTTP 500 error after the first part
# [MEDIUM] Backup Now could use another profile than the one shown if the active profile changed in another tab
# [MEDIUM] Schedule Automatic Backups: the front-end URL to check for failed uploads returned a 404 error
# [MEDIUM] startBackup's overrides key silently rejected every override key
# [MEDIUM] A failed PushBullet connection logged the configured API key
# [MEDIUM] Downgrading from Professional to Core left the Professional API application installed and reachable
# [MEDIUM] Downgrading from Professional to Core left Professional files behind on case-insensitive filesystems
# [MEDIUM] Restoration: extraction state was unserialized without an allow-list, letting a request name any loaded class
# [MEDIUM] akeeba:profile:modify/copy silently blanked the profile description when --description was not specified
# [MEDIUM] The layout URL parameter could make a page include a PHP file from outside the templates folder
# [MEDIUM] Restoration: "display_errors" warning appeared even when the host had display_errors turned off
# [MEDIUM] Schedule Automatic Backups: front-end URLs cut off or corrupted a Secret Word containing & # + or %
# [MEDIUM] Multiple Databases: editing a database definition blanked its port number
# [MEDIUM] Profiles: searching "id:N" caused an error page, and kept doing so until the search was cleared
# [MEDIUM] akeeba:filter:exclude/delete/list failed with "Unknown root" unless --root was given, despite its default
# [MEDIUM] Exclusion filters of an off-site directory were lost when its path was converted to [ROOTPARENT]/[SITEROOT]
# [MEDIUM] Exclusion filters (files, database tables): renaming one to an existing name deleted the edited filter
# [MEDIUM] Control Panel: leftover Akeeba Backup 8 notice offered a migration button leading to an error on Joomla 5+
# [MEDIUM] RegEx Database Tables: changing the root failed and kept showing the previous root's filters
# [MEDIUM] RegEx Database Tables: renaming a regex onto an existing one deleted the filter being edited
# [MEDIUM] RegEx Files and Directories: renaming an expression onto an existing one deleted the original filter
# [MEDIUM] Control Panel: the output directory web access check never ran, so its security warnings were never shown
# [MEDIUM] A backup that died in its first step stayed "pending" forever instead of being marked failed
# [MEDIUM] Configuration: the Test connection button of the DirectSFTP over cURL archiver did nothing
# [MEDIUM] FTP connection tests and site transfer failed with an HTTP 500 error when PHP's FTP extension is missing
# [MEDIUM] Configuration: an empty profile description was saved when the One-click icon setting changed too
# [MEDIUM] Configuration: a profile uploading to OneDrive for Business raised a JavaScript error on page load
# [MEDIUM] FTP tests and Site Transfer ended in an HTTP 500 error when PHP lacks the FTP extension
# [MEDIUM] Joomla 4: the restoration leftovers warning never appeared (it needs a constant only Joomla 5 has)
# [MEDIUM] Quick Icon: with "Warn on failed backups" off, a failed latest backup made the site look backed up
# [MEDIUM] Action Log: profile imports, backup starts, archive deletes/imports, remote file actions, transfers unlogged
# [MEDIUM] akeeba:profile:modify/export/reset/delete and option:get/set treated profile ID 0 as an existing profile
# [MEDIUM] akeeba:option:set stored values not among an option's choices, and read yes/true/on for on/off options as 0
# [MEDIUM] akeeba:log:list and akeeba:log:get always read the logs of profile 1, ignoring their profile argument
# [MEDIUM] akeeba:migrate left the site with no backup profiles if the migration failed halfway
# [MEDIUM] Backup Now: an error starting the backup was retried, then shown as a misleading security token error
# [MEDIUM] Site Transfer Wizard: an address whose domain does not resolve showed nothing instead of the warning
# [MEDIUM] Site Transfer Wizard: the same-site and unreachable-address warnings, and their Ignore button, never showed
# [MEDIUM] Site Transfer Wizard: a missing backup part file ended in an HTTP 500 error instead of a message
# [LOW] PHP notices could appear during backups, database filtering, missing-record actions and FTP transfers
# [LOW] Visiting Joomla's Control Panel reset the active Akeeba Backup profile to the default one
# [LOW] Database Tables Exclusion: a rejected filter stayed listed in the tabular view as if it had been saved
# [LOW] When cleaning one cache failed, the remaining caches were not cleaned either
# [LOW] Schedule Automatic Backups: a long front-end URL overflowed its table cell in the Check tabs
# [LOW] Schedule Automatic Backups: the Check tabs' PHP path note was chosen from the Run Backups tab's data
# [LOW] The Control Panel's missing OpenSSL extension warning was shown in English in non-English languages
# [LOW] Import Archives from S3: the credentials were never pre-filled from the profile's Amazon S3 settings
# [LOW] Import Archives: scanning a missing directory fatally errored instead of showing an error
# [LOW] Import Archives: a single-part archive was recorded as having one part instead of none
# [LOW] A typo disabled Amazon S3 custom endpoint scheme sanitisation
# [LOW] Core packages contained some Professional view templates and the Remote Files MVC files
# [LOW] Restoration: PHP 8.1 and later deprecation notices when extracting a JPS archive containing a symbolic link
# [LOW] Restoration: JPS decryption never trimmed padding off a block; harmless since every caller trimmed it itself
# [LOW] Restoration: PHP 8.5 deprecation notices when opening a JPS archive whose password is shorter than 16 characters
# [LOW] Restoration: PHP 8.1+ deprecation notice extracting into an existing folder with no extraction target configured
# [LOW] Restoration: PHP 8.1+ deprecation notices from the file zapper and finalisation with target/archive unconfigured
# [LOW] Restoration: PHP 8.1+ deprecation notices, and a PHP 9 fatal error, for UNIX socket or named pipe DB hostnames
# [LOW] Deleting an obsolete backup log file variant emitted a harmless "No such file or directory" PHP warning
# [LOW] Log viewer did not escape the timestamp field, and the engine did not neutralise the "|" field delimiter
# [LOW] Untagged log file's write probe truncated away its own die() guard, leaving it unprotected
# [LOW] Warnings were queued for the UI before newline/pipe/control-character sanitisation was applied
# [LOW] OAuth2 token refresh saved the new refresh token under a misspelled key, so it was discarded
# [LOW] OAuth2 provider errors showed the error_uri value instead of the actual error description
# [LOW] Alternate-backup CLI commands always treated the legacy API as enabled, ignoring the actual setting
# [LOW] Restoration ignored the passive-FTP preference and reused the FTP directory setting instead
# [LOW] akeeba:backup:list --sort-order asc had no effect; the requested direction was never applied
# [LOW] Site Transfer's backup-selection ordering was passed as a raw SQL string and silently ignored
# [LOW] AKWarn never recognised multipart archive leftovers (.z01, .p01, ...) due to a broken regex
# [LOW] BackupOnUpdate's toggle-token check used loose comparison instead of strict, unlike its AKWarn sibling
# [LOW] The Factory memory file's die() guard had its closing tag transposed, leaving only a parse error
# [LOW] Backup view's script options were double-escaped, corrupting descriptions/comments with backslashes
# [LOW] SQLite driver's escape() ignored the LIKE-wildcard flag, unlike every other database driver
# [LOW] Statistics UPDATE did not whitelist columns the way INSERT does, fatally erroring on an unknown key
# [LOW] renameTable() and SQLite schema introspection interpolated table names unquoted (no live caller)
# [LOW] Removed dead code that would have sent proxy credentials to the origin server instead of the proxy
# [LOW] Settings encryption key file lookup no longer depends on the process's current working directory
# [LOW] Pro package shipped the Page Cache plugin without actually installing it
# [LOW] Package build shipped vendor development cruft
# [LOW] Migration from Akeeba Backup 8 raised PHP warnings while carrying over the encryption key
# [LOW] Core package shipped unused Pro-only files which it promptly removed upon installation
# [LOW] Amazon S3: download to browser failed (301) outside us-east-1 with path-style or bucket-in-URL enabled
# [LOW] Site Transfer Wizard raised a JavaScript error when there was no backup to transfer
# [LOW] Profiles page raised PHP warnings: the selection checkbox label used a nonexistent profile name
# [LOW] Fetching a remotely stored backup back to the server could leave a stray .tmp file in the output folder
# [LOW] Site Transfer Wizard emitted a harmless "No such file or directory" PHP warning on every uploaded chunk
# [LOW] Folder browser: the Go button did nothing, so a typed-in folder path could not be browsed
# [LOW] Import Archives from S3: rejected credentials made the page fail with an error instead of a warning
# [LOW] Files and Directories Exclusion, tabular view: a filter refused by the server stayed listed as saved
# [LOW] RegEx Database Tables: a regex refused as a duplicate was shown as saved and a second add row appeared
# [LOW] RegEx Database Tables: saving a row without changes showed an error dialog
# [LOW] RegEx Database Tables: a just-saved regex containing < > or & was shown HTML-escaped until reload
# [LOW] RegEx Files and Directories: a just-saved label containing < > or & was shown HTML-escaped until reload
# [LOW] RegEx Files and Directories: a filter refused by the server stayed listed as saved with a second add row
# [LOW] Control Panel: the Switch Profiles button shown on narrow screens did nothing when clicked
# [LOW] Control Panel: the Desktop notifications option never took effect, so no permission was requested
# [LOW] Control Panel: the latest backup's origin showed a dash for JSON API and Joomla Scheduled Tasks backups
# [LOW] Control Panel: the CHANGELOG dialog omitted the newest version's heading and left its last list unclosed
# [LOW] Web Push: a failed Service Worker registration raised an uncaught JavaScript TypeError on the Control Panel
# [LOW] Configuration check warning q204 (disabled PHP functions) never appeared
# [LOW] Configuration: saving never showed the "configuration saved" message
# [LOW] Configuration: choosing Custom for a number option without typing saved the previous preset, not the value shown
# [LOW] Configuration: custom number boxes with a fractional unit flagged valid values as having a step mismatch
# [LOW] Configuration: Reload drives for Google Drive after an FTP/SFTP connection test queried the wrong URL
# [LOW] Configuration: switching engine before the Google Drive list arrived caused a JavaScript error
# [LOW] Folder browser: choosing the site root as the output folder showed no warning, or showed a raw language key
# [LOW] Configuration: the Test connection button of DirectSFTP over cURL (archiver) did nothing
# [LOW] Profiles: the Sort Table By list and the column headers did not sort the list
# [LOW] Profile edit form: Save as Copy created a profile with empty configuration and no filters
# [LOW] Profiles: deleting the active profile caused an error page instead of a message
# [LOW] Profiles: importing without choosing a file showed a raw language key instead of a message
# [LOW] Profiles: the edit form opened from a bare URL instead of returning to the list
# [LOW] Profiles: an exported profile was downloaded as Profile.json instead of profile.json
# [LOW] Profiles: Reset wiped the selected profiles' configuration and filters without asking for confirmation
# [LOW] Quick Icon: the "Stale backup time" option accepted values outside its allowed range, such as 0
# [LOW] Restoration leftovers warning ignored the parts of split JPA and JPS archives (.j01, .j02...)
# [LOW] Quick Icon: after the backup, you were left on the Backup page instead of returning to the administrator home
# [LOW] Akeeba Backup could not be chosen in the User Actions Log options (Events To Log) after installation
# [LOW] User Actions Log: freezing/unfreezing backup records showed a raw {$title} placeholder
# [LOW] User Actions Log: the Extension column and filter showed raw <small> HTML markup for Akeeba Backup
# [LOW] akeeba:filter:exclude/delete with an unknown --filterType fatally errored instead of reporting it
# [LOW] akeeba:filter:include-database's connection error recommended a --no-check option which does not exist
# [LOW] akeeba:profile:create --quickicon without a value created the profile with the One-click backup icon off
# [LOW] akeeba:option:list --sort-by with an unknown column raised PHP notices instead of leaving the list unsorted
# [LOW] akeeba:sysconfig:list --format=count always printed 1 instead of the number of options
# [LOW] akeeba:profile:delete 1 printed two error messages instead of one
# [LOW] akeeba:option:list --filter help said keys must begin with the filter, but it matches anywhere in the key
# [LOW] Backup on Update: the update could fail to resume after the backup if the site URL contained certain characters
# [LOW] Default backup descriptions lost their "(Joomla CLI)" and "(Frontend)" origin marker
# [LOW] akeeba:backup:info exited successfully, printing nothing, for a backup record which does not exist
# [LOW] akeeba:backup:download raised a PHP notice for unknown records and never hinted at remote storage
# [LOW] Deleting a backup record which does not exist reported success (CLI, JSON API) and raised PHP notices
# [LOW] akeeba:backup:fetch of a backup with no remote file fatally errored instead of reporting the problem
# [LOW] Uploading a backup with a profile which has no remote storage claimed success and marked it as uploaded
# [LOW] akeeba:migrate left migrated profiles pointing at Akeeba Backup 8's backup folder, after moving the archives out
# [LOW] akeeba:migrate copied the old encryption key to a path nothing reads; migrated settings stayed unreadable
# [LOW] Site Transfer Wizard error messages showed raw HTML tags such as <code> and <em>
# [LOW] Site Transfer Wizard: a thrown upload error was sent under the wrong key and displayed only by luck
# [LOW] Restoration: tables with a backtick in their name were not dropped, so restoring over them failed

Akeeba Backup 10.4.0
================================================================================
+ akeeba:profile:import into specific profile ID with --profile, overwriting profile with --force
~ Logs never created with .log extension; when .log.php is blocked we now use just .php
~ JSON API v2, and Secret Word authentication on the v3 API, are deprecated; removal in 11.0, October 2027
~ JSON API: support for non-Super-User accounts in the API
# [MEDIUM] POST requests to the v3 JSON API endpoint always failed
# [LOW] Action Log: Import from S3 logged an archive path with the folder name repeated
# [LOW] Action Log: downloading a single-part backup archive was logged as part #-1
# [LOW] Action Log: downloading a backup archive was logged as deleting its archives
# [LOW] Action Log: exporting, saving the configuration and downloading were logged twice, once without an ID
# [MEDIUM] akeeba:sysconfig:get and akeeba:sysconfig:set improper error when missing the argument
# [MEDIUM] Split archives could end in an empty part, failing post-processing with "Missing input parameters"
# [LOW] Failed JSON API authentication did not fire onUserLoginFailure
# [LOW] CLI commands treated mandatory arguments as optional
# [LOW] CLI command reference documented positional arguments as --options
# [LOW] akeeba:backup:alternate was missing from the CLI command reference
# [LOW] Some English, French, and Italian strings displayed a literal backslash before an apostrophe
# [LOW] Three Site Transfer Wizard strings were untranslated
# [LOW] The Options page "Check for failed backups" section header had no language string, showing the raw key instead
# [LOW] The OAuth2 token refresh had wrong cache-busting headers
# [LOW] Manage Backups, Site Transfer Wizard showed wrong path when output directory missing or outside open_basedir

Akeeba Backup 10.3.7
================================================================================
# [HIGH] Database dumps silently lost precision on DOUBLE columns (PDO MySQL and PostgreSQL)
# [HIGH] Box, Dropbox, Google Drive, OneDrive token refresh during upload could overwrite another profile's remote storage configuration
# [MEDIUM] Box, Dropbox, Google Drive, and OneDrive did not refresh an access token missing from the backup profile, failing with an opaque authorisation error
# [MEDIUM] Box reported a lapsed authorisation as an opaque HTTP 401 error
# [MEDIUM] Dropbox did not explain a missing or lapsed Download ID
# [MEDIUM] Google Drive and OneDrive did not report a rejected token refresh
# [MEDIUM] Test FTP Connection button ignored the "Use FTP over SSL (FTPS)" setting, causing spurious authentication errors against FTPS-only servers
# [MEDIUM] WebDAV did not surface failed upload reason to the UI or the log file
# [MEDIUM] Backend views did not all enforce their intended ACL privilege (Schedule, Push, Statistic, Profile, and deleting backup records)
# [MEDIUM] The Schedule page is now restricted to users allowed to edit the component's Options (it displays the front-end/JSON API secrets)
# [LOW] The backend backup AJAX endpoint did not send no-cache headers
# [LOW] Importing a profile without selecting a file caused an error page instead of a graceful redirect
# [LOW] Hardening: assorted backend control panel actions now require an anti-CSRF token (backup filter/exclusion/inclusion editors, remote archive delete/download, Transfer Wizard, Upload, S3 import, FTP/SFTP connection tests, custom data processing API, Configuration Wizard, output directory check, hide restoration modal, dismiss upsell)
# [LOW] Hardening: the backend backup AJAX flow now uses a per-backup anti-CSRF token, immune to session token rotation during long backups
# [LOW] Hardening: the front-end and JSON API Secret Word is now compared in constant time
# [LOW] Hardening: all database queries built from filter, user-state, and request values now use bound, typed query parameters across the backend, front-end JSON API, and the Akeeba Engine

Akeeba Backup 10.3.6
================================================================================
+ Site Transfer Wizard: transfer any backup archive present on the server, not just the latest one (gh-36)
# [LOW] Site Transfer Wizard: Dark mode layout issues (white-on-white in upload stats area)
# [HIGH] Box, Dropbox, OneDrive stored files under the local temp name
# [HIGH] Uploads failed when using a bucket-restricted BackBlaze B2 key
# [MEDIUM] Box, Dropbox, Google Drive, OneDrive token refresh raced expiry
# [MEDIUM] RackSpace CloudFiles validated the username, not the API key
# [MEDIUM] Dropbox public download URL embedded the access token
# [MEDIUM] OneDrive for Business signed download URL was broken on Graph
# [MEDIUM] S3 v4 pre-signed URLs failed (403) on path-style non-AWS hosts
# [MEDIUM] Google Storage pre-signed URLs duplicated the bucket name (403)
# [LOW] Box folder listing paginated incorrectly
# [LOW] BackBlaze cancelUpload sent the request body as form-data, not JSON
# [LOW] BackBlaze downloadFileById used the wrong API path (404)
# [LOW] WebDAV options() dropped capabilities from repeated DAV headers

Akeeba Backup 10.3.5
================================================================================
+ Translations for Greek (el-GR), French (fr-FR), German (de-DE), Spanish (es-ES), Portuguese (pt-PT), and Italian (it-IT)
~ Switched to BackBlaze B2 v4 API
~ Obfuscate kickstart.txt so that broken file scanners (OVH) don't cause problems by misidentifying it as "malicious"
# [HIGH] The Site Transfer Wizard was not working
# [HIGH] DirectFTP would not work due to setting erroneous directory permissions

Akeeba Backup 10.3.4
================================================================================
# [LOW] Configuration page: Count Quota value not saved when using spinner arrows or when saving without first clicking outside the custom value field
# [MEDIUM] Fix stdClass warning when reading akeeba.quota.logfiles configuration key

Akeeba Backup 10.3.3
================================================================================
+ Add "Delete obsolete log files" quota feature
~ Manage Backups: the View Log button is now disabled with a tooltip when the log file no longer exists on the server
# [MEDIUM] Configuration page: saving an SFTP password containing an angle bracket (e.g. `<F9`) would blank the profile description and deselect the one-click backup icon
# [HIGH] Check file upload: SQL error on all PHP versions when checking for failed uploads
# [HIGH] Check Backup Uploads: TypeError (null given to method_exists) when checking for failed uploads

Akeeba Backup 10.3.2
================================================================================
~ Obfuscate the PostgreSQL dump engine EVEN MORE AGGRESSIVELY so that certain antivirus don't throw a false positive.

Akeeba Backup 10.3.1
================================================================================
~ Obfuscate the PostgreSQL dump engine so that certain antivirus don't throw a false positive.

Akeeba Backup 10.3.0
================================================================================
+ PostgreSQL support

Akeeba Backup 10.2.2
================================================================================
+ Failed backup and failed uploads checks are now available as Joomla Scheduled Tasks
# [LOW] PHP Warning when deleting backup archives under PHP 8.4 and later versions
# [LOW] The AKWarn plugin may not work on hosts with open_basedir restrictions not allowing access to the site's root.

Akeeba Backup 10.2.1
================================================================================
# [HIGH] Joomla 5.4 and 6.0: The console plugin is raising a PHP warning

Akeeba Backup 10.2.0
================================================================================
+ System plugin to notify you of leftovers after restoration
+ Site Transfer Wizard now uploads Kickstart under a random filename
~ New archive extraction script (based on Kickstart 9)
~ PHP 8.5 compatibility
# [HIGH] MySQL to MariaDB: SQL errors when the collation is converted to
 `uca1400_*`
# [HIGH] Site Transfer Wizard: Would not accept SFTP using certificates
# [HIGH] Alternate backup and backup check no longer worked in Joomla 6
# [HIGH] Checking the output directory for direct web access no longer worked in Joomla 6
# [HIGH] The Site Transfer Wizard could fail in Joomla 6
# [MEDIUM] WebPush no longer worked in Joomla 6

Akeeba Backup 10.1.0
================================================================================
+ "Only back up tables installed by Joomla! and its extensions" filter
+ Failed backup upload check
~ Preliminary support for Joomla 6
~ PHP 8.5 compatibility: setAccessible is deprecated for reflection objects
~ PHP 8.5 compatibility: implicit nullable method parameters are deprecated
# [HIGH] "Normalise character set" can break the restoration
# [LOW] Dark Mode: Status row text had almost no contrast
# [LOW] Cannot filter Manage Backups by Frozen state
# [LOW] Moving from MariaDB to MySQL could result in SQL error.

Akeeba Backup 10.0.6
================================================================================
~ Preliminary support for Joomla 6
+ Using IMDSv2 for getting the credentials off EC2 instances
+ More inline text explaining the concept of backup profiles throughout the interface
# [LOW] The Manage Backups page's filter form has the wrong sorting filter

Akeeba Backup 10.0.5
================================================================================
! Problem with the integrated restoration
~ Preliminary support for Joomla 6 (continued)

Akeeba Backup 10.0.4
================================================================================
~ Preliminary support for Joomla 6
+ Warn about using bak_ as the database table name prefix
+ Amazon S3: Support for ACLs on the uploaded backup archives
# [MEDIUM] PHP Error doing a site DB only backup when additional database definitions are present
# [LOW] Error, or inconsistent output when deleting the last item of the last page of a list

Akeeba Backup 10.0.3
================================================================================
+ Support for tables with backticks in their names
~ Restoration: Eliminate deprecation notices under PHP 8.4
# [HIGH] Restoration: lack of otherwise optional mbstring would result in an error
# [HIGH] CLI restoration: error about the DB port being out of range
# [MEDIUM] Some configuration settings are inherited from the default profile when a profile is reset or created afresh
# [MEDIUM] System - Page Cache prevents the JSON API from working properly on some sites

Akeeba Backup 10.0.2
================================================================================
# [HIGH] Front-end legacy and API backup modes don't work when Strict Routing enabled in the System - SEF plugin
# [MEDIUM] Restoration: PHP error when the server reports the site's root as the filesystem root (chroot jail)
# [MEDIUM] Joomla restoration: mail online setting not respected in the web interface
# [LOW] Possible PHP error trying to parse invalid URLs
# [LOW] Deleting the items of the last page in Manage Backups page results in an empty display you can't easily get out of

Akeeba Backup 10.0.1
================================================================================
~ Automatically exclude the .cagefs directory present in some cPanel installations
# [HIGH] PHP Error resetting Joomla! 4 MFA
# [MEDIUM] Possible restoration issues if the upgrade code does not execute when installing the update
# [LOW] Restoration: PHP Deprecated warnings when checking for legacy magic quotes features on PHP 7

Akeeba Backup 10.0.0
================================================================================
+ New restoration script framework, with a minimum requirement of PHP 7.2
~ Maximum batch row size for database backup is now 10000 by default, with a maximum of 1000000
~ Fixed dark mode display of Configuration Wizard on Joomla! 5.2
# [HIGH] Box: cannot refresh the authentication token
# [LOW] The list of tables was no longer output
# [LOW] WebDAV: deleting backups may file on some servers

Akeeba Backup 9.9.11
================================================================================
~ Make accurate PHP CLI path detection optional
# [HIGH] Some OneDrive multipart uploads fail

Akeeba Backup 9.9.10
================================================================================
# [HIGH] Error in Manage Backups page: extra tab in layout file

================================================================================
! Could not work with MySQL 5.x and MariaDB 10.x

Akeeba Backup 9.9.7
================================================================================
+ Multi-row select and row show/hide in the Manage Backups and Profiles pages
+ Support for dumping MySQL EVENTs
+ More accurate information about PHP CLI in the Schedule Automatic Backups page
+ Improved database dump engine
# [LOW] Cannot transfer files to DreamObjects and Google Storage using the S3 API

Akeeba Backup 9.9.6
================================================================================
# [HIGH] Custom OAuth2 token refresh did not work reliably
# [HIGH] Custom OAuth2 set up could be blocked by missing files due to a packaging issue

Akeeba Backup 9.9.5
================================================================================
+ Edit and reset the cache directory (Joomla! 5.1+) on restoration
+ Remove MariaDB MyISAM option PAGE_CHECKSUM from the database dump
~ Improve database dump with table names similar to default values
~ Change the wording of the message when navigating to an off-site directory in the directory browser
~ Workaround for PRE element styling in Joomla! 5.1
~ Workaround for Joomla! 5.1 CSS in alert DIVs
~ Disable migration from Akeeba Backup 8 on Joomla! 5.0 and later
~ PHP 8.4 compatibility: MD5 and SHA-1 functions are deprecated
# [MEDIUM] Tables or databases named `0` can cause the database dump to stop prematurely, or not execute at all
# [MEDIUM] Some akeeba:profile CLI commands had non-functional options
# [LOW] The [PROFILENAME] tag in backup completion emails returns no or wrong labels
# [LOW] Backup on Update showed in more Joomla! Update pages than intended

Akeeba Backup 9.9.4
================================================================================
+ Option to avoid using `flush()` on broken servers

Akeeba Backup 9.9.3
================================================================================
- Remove the deprecated, ineffective CURLOPT_BINARYTRANSFER flag
+ New style for the Backup-on-Update information
+ Alternate Configuration page saving method which doesn't hit maximum POST parameter count limits
+ Improved support for Joomla! 5.1's backend colour schemes
# [HIGH] Custom OAuth2 helpers: cannot use refresh tokens with non-alphanumeric characters (e.g. slashes, plus sign, etc)

Akeeba Backup 9.9.2
================================================================================
! Packaging problem prevented new installations from working correctly

Akeeba Backup 9.9.1
================================================================================
+ Self-hosted OAuth2 helpers
~ Dark Mode color improvements for Joomla! 5.1
# [LOW] Restoration error when you have a newer Admin Tools version installed
# [LOW] Deprecation notice in Configuration Wizard
# [LOW] Exporting a backup profile doesn't automatically save the JSON file

Akeeba Backup 9.9.0
================================================================================
~ Prevent backup failure on push notification error
+ Separate remote and local quota settings
+ Expert options for the Upload to Amazon S3 configuration
+ Upload to OneDrive (app-specific folder)

Akeeba Backup 9.8.5
================================================================================
+ Joomla restoration: allows you to change the robots (search engine) option
# [LOW] CLI commands: fixed import of new profiles
# [LOW] Backup on Update status toggled after saving Joomla Update's Options

Akeeba Backup 9.8.4
================================================================================
+ Automatically downgrade utf8mb4_900_* collations to utf8mb4_unicode_520_ci on MariaDB
+ Updated environment stats collection code
# [MEDIUM] PHP error trying to use WebPush for the first time
# [LOW] Cannot delete backup archive from CLI
# [LOW] PHP 8.3 deprecated notice in ComponentParameters service (no functional issue)

Akeeba Backup 9.8.3
================================================================================
~ Identical to 9.8.2. Re-released as 9.8.3 because of an issue with the update server serving stale information.

Akeeba Backup 9.8.2
================================================================================
# [MEDIUM] Using WebPush leads to PHP error under Joomla! 5
# [LOW] The --force flag in akeeba:option:set was not working

Akeeba Backup 9.8.1
================================================================================
~ Joomla 5 Dark Mode workarounds
+ Support for Joomla 5 custom public folder
+ Restoration: Support Joomla 5 custom public folder
+ Restoration: Use transactions to speed up large table restoration
# [HIGH] The Quick Icon plugin does not show anything in the Joomla! control panel
# [HIGH] The (deprecated) JSON API command to export the configuration fails when the configuration is encrypted
# [HIGH] The akeeba:backup:delete CLI command threw an error due to a typo

Akeeba Backup 9.8.0
================================================================================
+ Use Composer to load all internal dependencies (backup engine, S3 library, WebPush library)
# [LOW] Joomla 5: Manage Backups page does not work when the b/c plugin is disabled.

Akeeba Backup 9.7.1
================================================================================
# [LOW] Possible PHP error when updating this along other extensions using the same post-installation script

Akeeba Backup 9.7.0
================================================================================
+ Notice about Joomla 4 End of Service
+ Workaround for Wasabi S3v4 signatures
+ Support for uploading to Shared With Me folders in Google Drive
- Remove the non-functional “Hide toolbar” option from the Backup Now backend menu item
~ Changed the plugins' namespace
~ Joomla 5 preparation: Use DatabaseInterface instead of DatabaseDriver
~ Joomla 5 preparation: Work around backwards incompatible changes in core plugin events
~ Joomla 5 preparation: Normalise plugin event calling
~ Joomla 5 preparation: Loading form data MUST NOT return a Table anymore
~ Improved error reporting, removing the unhelpful "(HTML containing script tags)" message
~ Improved mixed– and upper–case database prefix support at backup time
~ Normalised view names
# [MEDIUM] Upload to S3 would always use v2 signatures with a custom endpoint.
# [MEDIUM] Visiting the Control Panel page would always try to save the Output Directory, replacing most variables
# [MEDIUM] Resetting corrupt backups can cause a crash of the Control Panel page
# [LOW] Trying to delete a profile which cannot be deleted results in error page instead of an above-the-table error message
# [LOW] Cannot save an edited backup record leaving the comment blank
# [LOW] Users are not prompted to run the Configuration Wizard on new installation

Akeeba Backup 9.6.2
================================================================================
~ Block uninstallation of child extensions
# [LOW] CLI backups no longer record an end date and time due to a change in Joomla's behavior
# [LOW] Backup On Update: Would always use profile 1
# [LOW] Backup On Update: Inversion of logic of the switches in its options page

Akeeba Backup 9.6.1
================================================================================
# [MEDIUM] HTTP PUT might fail on some servers
# [LOW] opcache_invalidate may not invalidate a file
# [LOW] Would not work on 32-bit versions of PHP

Akeeba Backup 9.6.0
================================================================================
+ Support for files and archives over 2GiB (JPA file format 1.3)
+ New JSON API endpoint, using the Joomla API Application
~ Disabled deprecated API methods
~ Improve the Schedule Automatic Backups page
# [MEDIUM] JSON API: deleteFiles method throws an exception due to a typo

Akeeba Backup 9.5.1
================================================================================
+ Restoration: handle Joomla 4.2+ MFA options
# [MEDIUM] Plugins not enabled on clean installation
# [MEDIUM] JSON API cannot delete backup records and profiles

Akeeba Backup 9.5.0
================================================================================
+ Option to treat failed uploads as a backup error

Akeeba Backup 9.4.8
================================================================================
! A packaging issue broke the restoration script in backup archives

Akeeba Backup 9.4.7
================================================================================
# [MEDIUM] Fixed drive selection for Google Drive post processing engine

Akeeba Backup 9.4.6
================================================================================
# [HIGH] Some password managers prevent successful submission of the Site Setup page (you get an error about a missing email address)
# [LOW] Wrong grammatical case (nominative instead of genitive) in months in some languages e.g. Greek
# [LOW] Push messages may be untranslated strings when a backup is taken over the API or the frontend backup URL

Akeeba Backup 9.4.5
================================================================================
# [HIGH] Unexpected behaviour in the backend when Joomla cache is enabled
# [HIGH] BackBlaze B2 single file uploads were broken

Akeeba Backup 9.4.4
================================================================================
+ ALICE button in the log view
# [HIGH] Migration from Akeeba Backup 8 fails since 9.4.0 added an access setting in backup profiles

Akeeba Backup 9.4.3
================================================================================
# [HIGH] Migration from Akeeba Backup 8 always shows an erroneous message that no compatible version has been detected.
# [MEDIUM] Restoration. Administrator email appears as "undefined" in the Site Setup page
# [LOW] Restoration: Wrong message about the emial address when the administrator passwords don't match

Akeeba Backup 9.4.2
================================================================================
! No access control applied in Include and Exclude Information features
# [HIGH] Class not found errors when trying to access some pages in Akeeba Backup

Akeeba Backup 9.4.1
================================================================================
! Immediate error on PHP 7.4 due to a missing method in the released version

Akeeba Backup 9.4.0
================================================================================
~ Requires Joomla 4.2 or later
~ Requires PHP 7.4.0 or later
~ Much simpler message if you try to run Akeeba Backup on an unsupported (too low) version of PHP.
~ Changed all warnings to much more compact DETAILS elements
+ Access levels in backup profiles
+ Option about including the latest backup in remote quotas
- Removed the PHP version warning. Joomla already warns you about EOL versions of PHP.
# [HIGH] Site Transfer Wizard will fail on a target site using PHP 8.1 or later by default
# [LOW] ZIP Archiver, invalid CRC32 calculated for some small files in the installation folder

Akeeba Backup 9.3.4
================================================================================
# [LOW] ZIP Archiver, invalid CRC32 calculated for some small files in the installation folder

Akeeba Backup 9.3.3
================================================================================
~ Better warnings about CRC32 for ZIP files on 32-bit versions of PHP
# [HIGH] Quota settings and emails are not processed at the end of the backup process
# [HIGH] Joomla Scheduled Tasks for Akeeba Backup may fail with a PHP error

Akeeba Backup 9.3.2
================================================================================
~ PHP notices are now only logged when Debug Site is enabled
~ Notify the user when the server does not support Web Push instead of just failing to subscribe to push notifications
# [MEDIUM] WebPush code tries to run when not selected resulting in an annoying, but harmless, warning
# [MEDIUM] Possible PHP fatal error if the server does not meet the Web Push minimum requirements
# [LOW] PHP 8 deprecated notices from the WebPush library

Akeeba Backup 9.3.1
================================================================================
+ Push notifications through the browser's Push API
+ ANGIE for Joomla: reset session and cache options in Site Setup
+ Support for ShowOn to conditionally show options in the Configuration page
~ Save and Save & Close buttons are now separate, as per Joomla 4.2 UI guidelines
# [HIGH] Single part uploads to Azure stopped working
# [LOW] “Field 'extra_query' doesn't have a default value” error on some broken installations
# [LOW] PHP warning about undefined $id in the Manage Backups page on some versions of PHP

Akeeba Backup 9.3.0
================================================================================
+ Upload to Swift: Support for Keystone v3
# [HIGH] Joomla broke database-aware models under the CLI. Working around the latest Joomla borkage, as we have always done.
# [MEDIUM] Command line options overrides don't work because of a typo
# [LOW] PHP 8.1 deprecated notice when checking if FOF is still installed
# [LOW] "Test FTP connection" button was not correctly applying the passive mode
# [LOW] CLI akeeba:profile:list was broken

Akeeba Backup 9.2.7
================================================================================
+ More informative error messages for database connection issues during restoration
~ Workaround for utf8_encode and _decode being deprecated in PHP 8.2
# [LOW] Restoration: You were shown separate port and socket options which were not taken into account
# [MEDIUM] Restoration: Using a custom port or socket might result in the wrong hostname being written in the restored site's configuration file
# [MEDIUM] Possible infinite loop on PHP 8 during DB restoration if a SQL file is missing
# [LOW] Invalid SQL dump if we cannot get the create commands for a function, procedure or trigger

Akeeba Backup 9.2.6
================================================================================
+ Restoration: Warn about missing mysqli / PDO MySQL and REFUSE to proceed
# [HIGH] Cannot download file from Amazon S3
# [LOW] PHP Warning when backing up a database (purely cosmetic issue)
# [LOW] Missing language strings from the CLI commands

Akeeba Backup 9.2.5
================================================================================
+ Restoration: Warn about missing mysqli / PDO MySQL and REFUSE to proceed
# [HIGH] Cannot download file from Amazon S3
# [LOW] PHP Warning when backing up a database (purely cosmetic issue)
# [LOW] Missing language strings from the CLI commands

Akeeba Backup 9.2.4
================================================================================
# [HIGH] Cannot connect to databases on localhost using the default named pipe
# [MEDIUM] Custom Amazon S3 regions would not work with custom endpoints

Akeeba Backup 9.2.3
================================================================================
+ Support for custom Amazon S3 regions
+ Support for MySQL SSL/TLS connections for backed up sites
+ Add Show Inline Help support in component options for Joomla 4.1
# [LOW] Weird interface for the CLI backup Scheduled Task type

Akeeba Backup 9.2.2
================================================================================
+ Improved Smart Search table filtering
+ Much improved FTP functions for uploading backup archives and transferring sites
+ Upload to Azure BLOB Storage now supports chunked uploads, files up to 190.7TB (up from 64Mb)
+ OneDrive for Business: you can now use Drives other than your personal
~ Ignore whitespace in the new site's URL in the Site Transfer Wizard
~ Stricter conditions for determining when to show the “Manage remotely stored files” button in Manage Backups
# [LOW] Upload to Remote Storage would transfer the first part file twice
# [MEDIUM] Fixed download of remote archives back to the server
# [MEDIUM] OneDrive: Uploads may fail if they are between 4Mb and 100Mb

Akeeba Backup 9.2.1
================================================================================
+ Restoration: ANGIE now applies very high memory and execution time limits to prevent some timeout / memory outage issues on most hosts.
+ Restoration: ANGIE now warns you if you leave the database connection information empty
+ Option to set a really large PHP memory limit during backup
~ Show an error if the temp file cannot be opened when importing from S3
# [HIGH] Sometimes you would not see the error when the Upload to Remote Storage failed
# [MEDIUM] ALICE would not list any logs, even for failed backups
# [LOW] The JPS archiver would show warnings about unreadable files when archiving directories without any files in them.
# [LOW] The directory browser in the Configuration page doesn't open the defined folder when it contains variables
# [LOW] Extra whitespace in the Upload to Remote Storage pages
# [LOW] Configure and Export in the backup profiles manager do not work because of backwards incompatible changes in Joomla 4.1.1

Akeeba Backup 9.2.0
================================================================================
+ Integration with Joomla 4.1's Scheduled Tasks
# [HIGH] Uploading to OVH is broken on many servers not using a proxy
# [LOW] Popover content does not display in the Configuration page

Akeeba Backup 9.1.1
================================================================================
# [HIGH] Wrong RewriteBase set up in the .htaccess Maker when restoring a Joomla site with Admin Tools Professional installed

Akeeba Backup 9.1.0
================================================================================
+ Allow using [REMOTESTATUS] in the email subject, not just the body
+ Warn about the Console – Akeeba Backup plugin being disabled in the Schedule Automatic Backups page
+ Joomla restoration: modify domains in the Admin Tools' Allowed Domains and server config maker features if necessary
~ Force the Quickicon plugin to always show in the Notifications area instead of the 3rd Party area
# [HIGH] Problems restoring if a table name ends in 0 when another table with an identical name EXCEPT the trailing zero is also being backed up
# [HIGH] Backing up to SQL: indices would not have the correct table name prefix
# [HIGH] Backing up as SQL: the query for finder_taxonomy does not use the correct prefix
# [MEDIUM] Log Priorities global configuration option got mangled restoring a Joomla 4 site
# [LOW] Restore backup admin menu does not work correctly with multiple backup profiles
# [LOW] RackSpace CloudFiles: some hosts change the case of HTTP headers
# [LOW] Test FTP button was not working
# [LOW] Fixed displaying multi-line backup comments in the Manage Backups page

Akeeba Backup 9.0.11
================================================================================
+ Support for MySQL 8 invisible columns
# [LOW] Rare type error under PHP 8 during restoration
# [LOW] Wrong translation string in backend menu item type
# [LOW] Wrong controls in Backup and Restore backend menu item types

Akeeba Backup 9.0.10
================================================================================
- Remove piecon (pie graph favicon showing the backup progress)
~ JSON API: Forcibly use the ‘json’ origin everywhere
~ JSON API: Throw an error if the backup ID sent to stepBackup does not exist
~ JSON API: Improved backup IDs prevent a number of JSON API issues
~ Auto–publish the Console plugin in the Professional version
# [LOW] JSON API: The wrong origin (‘frontend’ instead of ‘json’) was recorded
# [LOW] Manage Backups: The View Log button didn't take you to the correct log file

Akeeba Backup 9.0.9
================================================================================
- Removed iDriveSync; the service has been discontinued by the provider.
- Removed the “Archive integrity check” feature.
~ Ensure the correct collation of all database tables and columns used by the extension
~ Dropbox connector updated to require TLS v1.2
+ API requests: Prevent server cache
+ Better support for custom database drivers provided by third party extensions
# [LOW] Bootstrap 5.1.2 included in Joomla 4.0.4 broke the CSS for Control Panel icons
# [LOW] Check failed backups: All Super Users were notified even when an email was supplied

Akeeba Backup 9.0.8
================================================================================
# [MEDIUM] Wrong ACL check wouldn't allow non–Super User accounts from accessing the component
# [LOW] PHP 8 error if the output directory is empty

Akeeba Backup 9.0.7
================================================================================
~ Remove dash from automatically generated random values for archive naming
~ Adjusted padding in download backup modal
+ Increase the maximum Size Quota limit to 1Pb
+ Support for Joomla proxy configuration
# [MEDIUM] Cannot restore on PHP 8 if Two Factor Authentication is enabled in any user account
# [HIGH] Backing up to Box, Dropbox, Google Drive or OneDrive may not be possible if you are using an add-on Download ID

Akeeba Backup 9.0.6
================================================================================
# [HIGH] Legacy front-end backup fails to execute when stepping through the backup with a 404 error
# [MEDIUM] Could not enable encryption for configuration settings
# [LOW] The usage statistics model is not loaded in the control panel page
# [LOW] PHP Warning from the TriggerEvent trait
# [LOW] Added back button after backup completion
# [LOW] Wrong use of double quotes in CLI language file

Akeeba Backup 9.0.5
================================================================================
+ You are given the option to rerun the migration or uninstall Akeeba Backup 8 (with a nifty link) after migrating settings from Akeeba Backup 8.
+ Migration now also imports the Download ID from Akeeba Backup 8
# [HIGH] JavaScript errors due to strict mode in Configuration, Database Filters, Include Folders, Restoration, S3 Import and Transfer Wizard pages

Akeeba Backup 9.0.4
================================================================================
+ CLI Migration command
~ Completely removing the use of the Joomla CMS Filesystem API for writing / copying / moving files because it's too buggy
# [MEDIUM] JSON API getProfiles returns an empty array
# [HIGH] CLI backups always run with profile #1, even if you use the --profile parameter
# [LOW] Downgrading from Pro to Core didn't work correctly
# [LOW] Warning in Manage Backups page if you have deleted the backup profile used to take a backup listed there

Akeeba Backup 9.0.3
================================================================================
# [MEDIUM] Joomla Filesystem API (File / Folder) doesn't work on some servers; preferring native PHP functions instead.
# [HIGH] Does not work on Windows on the latest Joomla 4 RC versions
# [HIGH] Some internal links do not work because of lower/uppercase mix in file names

Akeeba Backup 9.0.2
================================================================================
~ Prevent installation on Joomla 3.
# [HIGH] Core version, regression: Call to a member function rebaseFiltersToSiteDirs() on bool
# [HIGH] yet another last minute, undocumented, backwards incompatible change in Joomla is breaking things.
# [MEDIUM] Extensions not enabled automatically on installation.

Akeeba Backup 9.0.1
================================================================================
# [HIGH] Akeeba Backup Core: immediate error coming from the Dispatcher

Akeeba Backup 9.0.0
================================================================================
! Rewritten with Joomla 4 Core MVC and Bootstrap 5 styling
+ Reset the configuration and filters of backup profiles from the Profiles page
