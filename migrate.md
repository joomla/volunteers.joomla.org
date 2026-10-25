# Migration of the Volunteers Portal from Joomla 3 to Joomla 6

## Preparation
- Prepare the server where the site is going to run
- Prepare the database where the site is going to run
- Setup DNS if needed

## Migration
On a local site perform the migration following the steps below.

### Successful migration
- Move the database to the live server, see [Move the database](#move-the-database)
- Before the `git pull`, run `git diff www/.htaccess` on the server. The `.htaccess` is rebuilt from the Joomla 6 `htaccess.txt`, so changes made on the server must be added to the new file first.
- Do a `git pull` on the server
- Check the security headers and the Content-Security-Policy on the live site, they are only sent for the host `volunteers.joomla.org`

## Move the database
The migrated local database replaces the Joomla 3 database. The data that Joomlers change on the live site is copied from the Joomla 3 database afterwards. Everything else comes from the local database, so do not change articles, menus, modules or settings on the live site after the local database is dumped.

The data that is copied by `database/updates/2026-10-24-copy-live-data.sql`:
- Users: `#__users`, `#__user_usergroup_map`, `#__user_notes`, and `#__user_profiles` without overwriting the Joomla 6 profile values
- Joomla Volunteer Portal: `#__volunteers_departments`, `#__volunteers_members`, `#__volunteers_positions`, `#__volunteers_reports`, `#__volunteers_roles`, `#__volunteers_teams`, `#__volunteers_volunteers`
- Privacy: `#__privacy_consents`, `#__privacy_requests`
- User actions log: `#__action_logs`

1. Put the live site offline in the Global Configuration
2. Dump the local database without the old `vol_` tables:
   `mariadb-dump --single-transaction volunteers $(mariadb -N -e "SHOW TABLES LIKE 'pa763\_%'" volunteers) > volunteers-j6.sql`
3. Create a new database on the live server, for example `volj_j6`, and give the database user of the site access to it. Keep the Joomla 3 database `volj_live` as it is, it is the fallback.
4. Import the dump: `mariadb volj_j6 < volunteers-j6.sql`
5. Copy the live data, with a database user that can read `volj_live` and write `volj_j6`. In DBeaver: open `database/updates/2026-10-24-copy-live-data.sql` in an SQL editor, select `volj_j6` as the active database, replace `#__` with `pa763_` and run it as a script (Execute SQL Script, Alt+X)
6. Move the members of the Board of Directors to the roles of the board department: run `database/updates/2026-10-25-board-roles.sql` on `volj_j6` the same way, replace `#__` with `pa763_`. The copied live data still has the old roles President (33), Vice President (36), Secretary (35) and Treasurer (34) without a department.
7. Set `$db` in `configuration.php` to `volj_j6` and check the other database settings
8. Rebuild the Smart Search index, so it has the reports made after the local database was made: `php cli/joomla.php finder:index purge`
9. Check that the newest reports and Joomlers of the live site are on the new site, then put the site online

## Extensions
### Uninstall Extensions
The following extensions must be uninstalled:
- Search package (`pkg_search`), this removes `com_search`, `mod_search` and the core search plugins
- Search - Volunteers - Departments
- Search - Volunteers - Reports
- Search - Volunteers - Teams
- Search - Volunteers - Volunteers
- Perfect Image Form Field (`plg_content_perfectimage`), the Joomla 3 image cropper of the profile edit and the registration form. Joomlers edit their profile and photo on the Joomla! Identity portal.
- Captcha - Invisible reCAPTCHA (`plg_captcha_recaptcha_invisible`), a Joomla 3 core plugin that is no longer part of Joomla 6
- FOF (`lib_fof`), the Joomla 3 version 2 of the Akeeba framework
- Copy Me (`plg_content_copyme`), run `database/updates/2026-10-24-copy-code.sql` first, see [Banners page](#banners-page)
- Authentication - Email package (`pkg_email`), this removes the Authentication - Email and System - Email plugins. Joomlers log in through RO Single Sign On.
- Sample Data - JVP (`plg_sampledata_jvp`), the test data is now a CLI seeder in the joomla-volunteer-portal repository that is not installed on the live site

### Update Extensions
- Joomla Volunteer Portal package, this installs the Smart Search plugins:
  - Smart Search - Joomla Volunteer Portal - Departments
  - Smart Search - Joomla Volunteer Portal - Reports
  - Smart Search - Joomla Volunteer Portal - Teams
  - Smart Search - Joomla Volunteer Portal - Joomlers
- Akeeba Backup 10 (`pkg_akeebabackup`), replaces Akeeba Backup 8, see [Akeeba Backup](#akeeba-backup)
- RO Single Sign On (`pkg_sso`) to 2.3.1, see [RO Single Sign On](#ro-single-sign-on)
- System - Joomla Identity Client (`plg_system_joomlaidentityclient`) to 2.0.0 and System - Joomla Volunteer Portal Identity (`plg_system_joomlaidentityvolunteers`) to 6.0.0, see [Joomla Identity plugins](#joomla-identity-plugins)
- YourSites (`plg_system_yoursites`) to 1.73.0, the Joomla 6 version of the plugin. Install it over the Joomla 3 version, the settings are kept.

## Search
The Joomla 3 search does not work on Joomla 6 and is replaced by Smart Search.

1. Install the Joomla Volunteer Portal package with the Smart Search plugins
2. Run `database/updates/2026-10-24.sql`, replace `#__` with the table prefix. This:
   - enables the Smart Search plugins and the Content - Smart Search plugin
   - points the hidden `/search` menu item to Smart Search
   - unpublishes the old search module and adds the Smart Search module to the header
   - removes the administrator menu item of `com_search`
   - shows Smart Search in the Components menu again and adds its Index, Maps, Filters and Searches submenu items
3. Rebuild the administrator menu: Menus → All Menu Items, set the filter to Administrator and click Rebuild
4. Uninstall the old search extensions listed above
5. Build the index: `php cli/joomla.php finder:index`
6. Schedule the Smart Search indexer, as a scheduled task or a cron job running `php cli/joomla.php finder:index`, so reports created on the frontend are found
7. Check that a search from the header box returns results

## Mail template for emailing members
The "E-mail current selection" button of the members list sends its mail through the mail template `com_volunteers.members.mail`, so the site mail settings and the HTML mail layout apply.

1. Run `database/updates/2026-10-24-mail-template.sql`, replace `#__` with the table prefix. This:
   - adds the mail template
   - sets the Mail Format of System > Mail Templates > Options to "Both", so the message from the editor is sent as HTML. This applies to all mail templates of the site.
2. Check that "Volunteers: Email to Members" is listed in System > Mail Templates and that the Mail Format is "Both"

## Banners page
The banner code on the Banners page (`/banners`) used the Copy Me plugin for its copy buttons. The `joomla` template now adds a copy button to every `<pre class="copy-code">` block.

1. Run `database/updates/2026-10-24-copy-code.sql`, replace `#__` with the table prefix. This replaces the `{copyme}` tags in the "Spread the Joomla! love" article with `<pre class="copy-code">` blocks
2. Uninstall the Copy Me plugin (`plg_content_copyme`), this also removes `media/hepta`
3. Check that each banner code on `/banners` has a Copy button that copies the code

## Akeeba Backup
Akeeba Backup 8 (`pkg_akeeba`) does not run on Joomla 6 and cannot be uninstalled there. Akeeba Backup 10 (`pkg_akeebabackup`) replaces it and takes over its quickicon, actionlog and backuponupdate plugins. The files of Akeeba Backup 8 and of the FOF 3, FOF 4 and FEF frameworks it used are removed in the repository.

1. Install Akeeba Backup 10 (`pkg_akeebabackup`)
2. Migrate the backup profiles, settings and backup records: `php cli/joomla.php akeeba:migrate`
3. Check with `php cli/joomla.php akeeba:profile:list` and `php cli/joomla.php akeeba:backup:list` that the profiles and backup records are there
4. Run `database/updates/2026-10-24-akeeba.sql`, replace `#__` with the table prefix. This removes the extensions, update sites, administrator menu item and asset of Akeeba Backup 8, FOF 3, FOF 4 and FEF, and drops the `#__ak_profiles`, `#__ak_stats` and `#__ak_storage` tables
5. Point the backup cron jobs to the Joomla CLI of Akeeba Backup 10, the old `cli/akeeba-*.php` scripts are removed: `php cli/joomla.php akeeba:backup:take --profile=<id>`
6. Check the S3 settings of the backup profiles and take a test backup

## RO Single Sign On
RO Single Sign On 2.3.1 (`pkg_sso`) is installed over 1.3.0 and comes with SimpleSAMLphp 2 as the `simplesamlphp` library. SimpleSAMLphp 2 serves its pages from `libraries/simplesamlphp/public` instead of `libraries/simplesamlphp/www`. The files of 1.3.0 and SimpleSAMLphp 1 and the SSO - Joomla plugin (`plg_sso_joomla`) are removed in the repository.

1. Install RO Single Sign On 2.3.1 (`pkg_sso`) over 1.3.0
2. Run `database/updates/2026-10-24-sso.sql`, replace `#__` with the table prefix. This removes the SSO - Joomla plugin and the schema version of 1.3.0
3. Check that the `sso` symlink in the webroot points to `libraries/simplesamlphp/public`
4. Open Components > RO Single Sign On > Configuration and save it, this writes `libraries/simplesamlphp/config/config.php` for SimpleSAMLphp 2. The config, metadata and cert folders are not in the repository.
5. Check the Identity Provider profile and that logging in and out through the Login and Logout menu items works

## System - Volunteers plugin
The System - Volunteers plugin (`plg_system_volunteers`) is enabled on the live site: it redirects old Joomler URLs to the right URL and marks My Profile as active on the own Joomler page. It is disabled in the migrated database.

1. Run `database/updates/2026-10-24-volunteers-plugin.sql`, replace `#__` with the table prefix. This enables the plugin
2. Check that `index.php?option=com_volunteers&view=volunteer&id=<id>` redirects to the Joomler page

## Joomla Identity plugins
The identity.joomla.org portal sends profile changes to this site. The System - Joomla Identity Client plugin receives them and triggers `onProcessIdentity`, the System - Joomla Volunteer Portal Identity plugin stores them in the Joomler profile. Both plugins come from the `plugins/joomlaidentityclient` and `plugins/joomlaidentityvolunteers` folders of the identity.joomla.org repository. The Joomla 3 version of the client plugin does not work on Joomla 6, and the Volunteer Portal Identity plugin is no longer part of the Joomla Volunteer Portal package.

1. Zip both plugin folders of the identity.joomla.org repository and install them, the API key of the client plugin is kept
2. Run `database/updates/2026-10-24-identity-plugins.sql`, replace `#__` with the table prefix. This removes the Volunteer Portal Identity plugin from the Joomla Volunteer Portal package
3. Change a test profile on identity.joomla.org and check that the change shows on the Joomler page and is logged in `administrator/logs/idp-calls.php`

## Board of Directors options
The department of the Board of Directors, the positions of the other board members and the roles of the officers are options of the Joomla Volunteer Portal component instead of IDs in the code. They are set in the migrated database: Board Department "Board of Directors" (58), Board Positions "Department Coordinator" (11) and Officer Roles "President", "Vice President", "Secretary" and "Treasurer" of the board department (286 to 289). A Joomler cannot be an officer and a member with a board position at the same time, saving such a membership gives an error.

1. Check the options in Components > Joomla! Volunteers > Options > Board of Directors
2. Check that `/board-of-directors` lists the officers and the Department Coordinators, and that `/groups` lists the teams of the board department
3. The Board of Directors menu item has no department any more, its link is `index.php?option=com_volunteers&view=board`. The Groups menu item keeps the board department in its link (`&id=58`): a Teams menu item with a department shows the teams of that department.

## FAQ page
The FAQ page (`/faq`) is the "Frequently Asked Questions and Answers" article, it is maintained in Content > Articles. The `faq` view of the Joomla Volunteer Portal with the FAQ text in the language files is removed, no menu item used it.

1. Run `database/updates/2026-10-25-faq-article.sql`, replace `#__` with the table prefix. This updates the FAQ article for the Joomla Identity portal, the current button and tab names of the team pages and removes the Glip chat channel
2. Check that `/faq` shows the updated answers
