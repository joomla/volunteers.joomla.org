# Migration of the Volunteers Portal from Joomla 3 to Joomla 6

## Preparation
- Prepare the server where the site is going to run
- Prepare the database where the site is going to run
- Setup DNS if needed

## Migration
On a local site perform the migration following the steps below.

### Successful migration
- Move the database to the live server
- Do a `git pull` on the server

## Extensions
### Uninstall Extensions
The following extensions must be uninstalled:
- Search package (`pkg_search`), this removes `com_search`, `mod_search` and the core search plugins
- Search - Volunteers - Departments
- Search - Volunteers - Reports
- Search - Volunteers - Teams
- Search - Volunteers - Volunteers

### Update Extensions
- Joomla Volunteer Portal package, this installs the Smart Search plugins:
  - Smart Search - Joomla Volunteer Portal - Departments
  - Smart Search - Joomla Volunteer Portal - Reports
  - Smart Search - Joomla Volunteer Portal - Teams
  - Smart Search - Joomla Volunteer Portal - Joomlers

## Search
The Joomla 3 search does not work on Joomla 6 and is replaced by Smart Search.

1. Install the Joomla Volunteer Portal package with the Smart Search plugins
2. Run `database/updates/2026-10-24.sql`, replace `#__` with the table prefix. This:
   - enables the Smart Search plugins and the Content - Smart Search plugin
   - points the hidden `/search` menu item to Smart Search
   - unpublishes the old search module and adds the Smart Search module to the header
   - removes the administrator menu item of `com_search`
3. Uninstall the old search extensions listed above
4. Build the index: `php cli/joomla.php finder:index`
5. Schedule the Smart Search indexer, as a scheduled task or a cron job running `php cli/joomla.php finder:index`, so reports created on the frontend are found
6. Check that a search from the header box returns results
