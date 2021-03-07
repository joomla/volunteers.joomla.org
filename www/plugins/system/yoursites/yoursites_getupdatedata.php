<?php

/**
 * @version    CVS: 1.15.0
 * @package    com_yoursites
 * @author     Geraint Edwards <via website>
 * @copyright  2016-2020 GWE Systems Ltd
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

/*
 * Joomla 3.7.x and earlier don't support these - leave them out until we MUST have them for Joomla 4.x
 */
/*
use Joomla\CMS\Client\ClientHelper;
use Joomla\CMS\Client\FtpClient;
use Joomla\CMS\Factory;
Use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Version;
use Joomla\Registry\Registry;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Component\ComponentHelper;
*/

use Joomla\CMS\Extension\ExtensionHelper;

defined('_JEXEC') or die;

function yoursites_skiptoken()
{
	return true;
}

function ProcessYstsJsonRequest(&$requestObject, $returnData)
{
	if (!defined('_SC_START'))
	{
		list($usec, $sec) = explode(" ", microtime());
		define('_SC_START', ((float) $usec + (float) $sec));
	}

	if (is_string($requestObject) && strpos($requestObject, "AES256:") === 0)
	{
		$requestObject = decrypt($requestObject);
	}
	else if (is_string($requestObject))
	{
		$requestObject = json_decode($requestObject);
	}
	$returnData->error = 0;
	$returnData->warning = 0;
	$returnData->trace = '';
	$returnData->messages = array();
	$returnData->timing = array();
	$returnData->errormessages = array();

	// Can we use full AES256 encryption?
	$returnData->AES256 = isset($requestObject->AES256) ? $requestObject->AES256 : false;

	// JLog::addLogger(array('text_file' => 'yoursites.php'), JLog::ALL, array('yoursites'));

	if ($requestObject->task != "setupsecuritytoken" && !securityCheck($requestObject, $returnData))
	{
		$returnData->error = 1;
		$returnData->errormessages[] = 'COM_YOURSITES_SECURITY_CHECK_FAILURE';
		return $returnData;
	}

	if ($requestObject->task != "setupsecuritytoken" && isset($requestObject->base) && strpos($requestObject->base, '/._ysts_') > 0)
	{
		// This site may be the root/parent site of a clone that has been deleted and .htaccess may have pushed us here - so check this
		$parts     = explode("/", trim($requestObject->base, " /"));
		if (strpos(JPATH_SITE, "._ysts_") === false || strpos(JPATH_SITE, $parts[count($parts)-1]) === false)
		{
			$returnData->error = 1;
			$returnData->errormessages[] = 'COM_YOURSITES_CLONED_SITE_APPEARS_TO_HAVE_BEEN_DELETED';
			return $returnData;
		}
	}

	$plugin = JPluginHelper::getPlugin("system" , "yoursites");
	if ($plugin && $requestObject->task != "setupsecuritytoken"  )
	{
		$params = new JRegistry($plugin->params);
		$serverip = $params->get("serverip", false);
		$serverdomain = $params->get("serverdomain", false);
		$serverinput = JFactory::getApplication()->input->server;
		$remoteaddress = $serverinput->get('REMOTE_ADDR', $serverinput->get("HTTP_X_FORWARDED_FOR", 0));
		if ($params->get("checkserverip", 0) && $serverip && $requestObject->task !== "directlogin")
		{
			$serverips = explode(",", $serverip);
			if (!in_array($remoteaddress, $serverips))
			{
				$returnData->error = 1;
				$returnData->errormessages[] = 'COM_YOURSITES_SECURITY_INVALID_SERVER_IP_ADDRESS';
				// $returnData->errormessages[] = var_export($_SERVER);
				return $returnData;
			}
		}

		if ($params->get("checkip_directlogin", 1) && $serverip && $requestObject->task == "directlogin")
		{
			$serverips = explode(",", $serverip);
			if (!in_array($remoteaddress, $serverips))
			{
				$returnData->error = 1;
				$returnData->errormessages[] = 'COM_YOURSITES_SECURITY_INVALID_DIRECT_LOGIN_IP_ADDRESS';
				return $returnData;
			}
		}

		if ($params->get("checkserverdomain", 0) && $serverdomain)
		{
			$remotehost = JFactory::getApplication()->input->server->get('REMOTE_HOST', false);
			if (!$remotehost)
			{
				$remotehost = gethostbyaddr($remoteaddress);
			}
			if ($remotehost !== $serverdomain)
			{
				$returnData->error = 1;
				$returnData->errormessages[] = 'COM_YOURSITES_SECURITY_INVALID_SERVER_DOMAIN';
				return $returnData;
			}
		}
	}

	$jinput = JFactory::getApplication()->input;
	$headers = $jinput->getArray(array("HTTP_JSON" => "raw"), $_SERVER);

	// If this is a GET request we do it differently
	if ((!count($headers) || !isset($headers["HTTP_JSON"])) && $jinput->get("json"))
	{
		// Not yet encrypting requests
		//$headers["HTTP_JSON"] = decrypt($jinput->get("json"));
		$headers["HTTP_JSON"] = $jinput->get("json");
	}
	else if ((!count($headers) || !isset($headers["HTTP_JSON"])) && $jinput->get("json64"))
	{
		// Not yet encrypting requests
		$json64 = @base64_decode($jinput->get("json64", false));
		if ($json64)
		{
			$headers["HTTP_JSON"] = $json64;
		}
	}

	if (count($headers) && isset($headers["HTTP_JSON"]))
	{
		$returnData->result = "success";
		$returnData->json = $headers["HTTP_JSON"];

		//JModelLegacy::getInstance($modelName, $classPrefix, $config);
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . "/components/com_installer/models");

		// Unfortunately Joomla! MVC doesn't allow us to autoload classes
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_installer/models', 'InstallerModel');

		// JLog::add("Starting task " . $requestObject->task, JLog::INFO, 'yoursites');
		list($usec, $sec) = explode(" ", microtime());
		$starttime = (float) $usec + (float) $sec;

		// Catch death
		register_shutdown_function('onYstsDie');

		jimport("joomla.filesystem.file");
		jimport("joomla.filesystem.folder");

		//avoid litespeed (or apache) killing the php process - attempt to let the task finish
		@ignore_user_abort(true);

		switch ($requestObject->task)
		{
			case "setupsecuritytoken":
				setupSecurityToken($requestObject, $returnData);
				break;

			case "findupdates":
				findUpdates($requestObject, $returnData);
				break;

			case "versioncompatibility":
				versionCompatibility($requestObject, $returnData);
				break;

			case "findextensions":
				findExtensions($requestObject, $returnData);
				break;

			case "purge":
				purgeCache($requestObject, $returnData);
				break;

			case "findjoomlaupdates":
				findJoomlaUpdates($requestObject, $returnData);
				break;

			case "purgejoomla":
				purgeJoomla($requestObject, $returnData);
				break;

			case "upgradejoomla":
				upgradeJoomla($requestObject, $returnData);
				break;

			case "getfolderdata":
				getFolderData($requestObject, $returnData);
				break;

			case "finalisejoomlaupgrade":
				finaliseJoomlaUpgrade($requestObject, $returnData);
				break;

			case "updateextension":
				updateExtension($requestObject, $returnData);
				break;

			case "uninstallextension":
				uninstallExtension($requestObject, $returnData);
				break;

			case "disableextension":
				disableExtension($requestObject, $returnData);
				break;

			case "enableextension":
				enableExtension($requestObject, $returnData);
				break;

			case "findlogindetails":
				findLoginDetails($requestObject, $returnData);
				break;

			case "installextension":
				installExtension($requestObject, $returnData);
				break;

			case "directlogin":
				directLogin($requestObject, $returnData);
				break;

			case "getbackuptoken":
				getBackupToken($requestObject, $returnData);
				break;

			case "getbackups":
				getBackups($requestObject, $returnData);
				break;

			case "clearcache":
				clearCache($requestObject, $returnData);
				break;

			case "clearsessions":
				clearSessions($requestObject, $returnData);
				break;

			case "cleartmp":
				clearTmp($requestObject, $returnData);
				break;

			case "clonesite":
				cloneSite($requestObject, $returnData);
				break;

			case "deletesite":
				deleteSite($requestObject, $returnData);
				break;

			case "rebuildupdatesites" :
				rebuildUpdateSites($requestObject, $returnData);
				break;

			case "otherchecks" :
				otherChecks($requestObject, $returnData);
				break;

			case "missing2factor":
				include_once "sitechecks.php";
				YstsSiteChecks::missing2factor($returnData);
				break;

			case "dormantspecials":
				include_once "sitechecks.php";
				YstsSiteChecks::dormantspecials($returnData);
				break;

			case "livesite":
				include_once "sitechecks.php";
				YstsSiteChecks::livesite($returnData);
				break;

			case "joomlaupdatesites":
				include_once "sitechecks.php";
				YstsSiteChecks::joomlaupdatesites($returnData);
				break;

			case "extensionupdatesites":
				include_once "sitechecks.php";
				YstsSiteChecks::extensionupdatesites($returnData);
				break;

			case "usercaptcha":
				include_once "sitechecks.php";
				YstsSiteChecks::usercaptcha($returnData);
				break;

			case "contentversioning":
				include_once "sitechecks.php";
				YstsSiteChecks::contentversioning($returnData);
				break;

			case "sendpassword":
				include_once "sitechecks.php";
				YstsSiteChecks::sendpassword($returnData);
				break;

			case "backfatal":
			case "backfrontfatal":
			case "frontfatal":
				frontfatal($returnData);
				return 'skip json';
				break;

			default :
				$directory = __DIR__;
				$directory = realpath($directory);

				if (file_exists( $directory . "/customactions/" . basename($requestObject->task . ".php")))
				{
					include_once $directory . "/customactions/" . basename($requestObject->task . ".php");

					try {
						$className = "Ysts" . ucfirst($requestObject->task);
						$className::executeAction($returnData);

					}
					Catch (Exception $e)
					{
						$returnData->error           = 1;
						$returnData->result          = "No Such Method in YourSites client plugin";
						$returnData->errormessages[] = $e->getMessage();
					}
				}
				else
				{
					include_once "sitechecks.php";
					if (method_exists('YstsSiteChecks', $requestObject->task))
					{
						$task = $requestObject->task;
						YstsSiteChecks::$task($returnData);
					}
					else
					{
						$returnData->error           = 1;
						$returnData->result          = "No Such Method in YourSites client plugin";
						$returnData->errormessages[] = 'COM_YOURSITES_NO_SUCH_METHOD_IN_CLIENT_FILE';
						$returnData->errormessages[] = 'Missing method is ' . $requestObject->task;
					}
				}
				break;
		}

		list ($usec, $sec) = explode(" ", microtime());
		$time_end = (float) $usec + (float) $sec;
		$timing = round($time_end - $starttime, 4);
		// JLog::add("Time for task " . $requestObject->task . " " . $timing, JLog::INFO, 'yoursites');

	}
	else
	{
		$returnData->error = 1;
		$returnData->errormessages = array("No headers");

	}

	define ('CLEAN_YSTS_EXIT', 1);

	// $returnData->messages[] = number_format(memory_get_peak_usage() / 1024 / 1024, 2, '.', '');

//	JFactory::getApplication()->input->setVar("tmpl","component");
	// No need to return to gwejson - also we can wrap the JSON output here if we want
	return encodeResults($returnData);
}

function findUpdates($requestObject, & $returnData)
{
	// clear the Extensions cache
	purgeCache($requestObject, $returnData);

	$db = JFactory::getDbo();

	// Get the update model and retrieve the Joomla! core updates
	$model = JModelLegacy::getInstance("Update", "InstallerModel");
	if (!$model)
	{
		JLoader::import('Model.UpdateModel', JPATH_ADMINISTRATOR . '/components/com_installer');
		$model = new Joomla\Component\Installer\Administrator\Model\UpdateModel();
	}

	// I need this to force the state population - otherwise my setState gets ignored!
	$temp = $model->getState('list.limit');

	$cachetime = isset($requestObject->cachetime) ? $requestObject->cachetime : 3600;
	$listlimit = isset($requestObject->listlimit) ? $requestObject->listlimit : 999;

	// false at the end means we don't include current versions
	$jupdater = JUpdater::getInstance();
	$updates = $jupdater->findUpdates(0, $cachetime, JUpdater::STABILITY_STABLE, false);

	//$model->setState('filter.extension_id', $eid);
	$model->setState('list.limit', $listlimit);
	$items = $model->getItems();

	if ($items)
	{
		$returnData->result = "something";
		$returnData->updates = $items;
	}
	else
	{
		$returnData->result = "nothing";
		$returnData->updates = array();
	}

}

function versionCompatibility($requestObject, & $returnData)
{
	include_once "Compatibility.php";

	$joomlaTargetVersion = isset($requestObject->targetversion) ? $requestObject->targetversion : '4.0.0';
	$returnData->JVERSION = JVERSION;
	$joomlaCurrentVersion = JVERSION;

	$lang = JFactory::getLanguage();
	$db = JFactory::getDbo();

	// find list of extensions - that have an updater record
	// Find updater site entries and mapping to extensions table.
	$query = $db->getQuery(true);
	$query->select('DISTINCT a.update_site_id, a.type, a.location, a.last_check_timestamp, a.extra_query')
		->from($db->quoteName('#__update_sites', 'a'))
		->where('a.enabled = 1'); // Do we need enabled entries ??
	$db->setQuery($query);
	$updatesites = $db->loadObjectList('update_site_id');

	$returnData->updatesitescount = count($updatesites);

	// Now fetch list of extensions
	// Need to load com_installer language files
	$lang->load("com_installer", JPATH_ADMINISTRATOR, null, false, true);

	$query = $db->getQuery(true)
		->select('*')
		->select('2*protected+(1-protected)*enabled AS status')
		->from('#__extensions')
		// TODO find what state = 0 means!
		->where('state = 0')
		// leave out protected Joomla extensions?? Can't do this since it misses out on JoomlaUpdater Component
		//->having('status <> 2')
	;

	$db->setQuery($query);
	$extensions = $db->loadObjectList();

	$returnData->extensionscount = count($extensions);

	// Get Joomla files extension - the last one installed by Joomla as proxy for core software id
	$query = $db->getQuery(true)
		->select('*')
		->from('#__extensions')
		->where('type = "file"')
		->where('element = "joomla"')
		->where('name = "files_joomla"');

	$db->setQuery($query);
	$joomlaextension = $db->loadObject();

	//  Get the English language extension
	$query = $db->getQuery(true)
		->select('*')
		->from('#__extensions')
		->where('type = "package"')
		->where('element = "pkg_en-GB"');

	$db->setQuery($query);
	$joomlaenglish = $db->loadObject();

	// TODO confirm that extension_id < 10000 is a good proxy for core software
	$nonJoomlaExtensions = 10000;
	if ($joomlaextension)
	{
		$nonJoomlaExtensions = $joomlaextension->extension_id;
	}
	if ($joomlaenglish)
	{
		$nonJoomlaExtensions = $joomlaenglish->extension_id > $nonJoomlaExtensions ? $joomlaenglish->extension_id : $nonJoomlaExtensions;
	}

	// Get the mapping table
	$query = $db->getQuery(true);
	$query->select('*')
		->from($db->quoteName('#__update_sites_extensions', 'a'));
	$db->setQuery($query);
	// TODO can there every be more than one entry in this mapping table?
	$updatesites_extension_mapping = $db->loadObjectList('extension_id');

	$nonJoomlaExtensionList = array();
	foreach ($extensions as &$extension)
	{
		try {
			if (ExtensionHelper::checkIfCoreExtension($extension->type, $extension->element, $extension->client_id, $extension->folder))
			{
				continue;
			}
		}
		catch (Exception $e)
		{
		}

		if ($extension->extension_id <= $nonJoomlaExtensions)
		{
			continue;
		}
		if (strlen($extension->manifest_cache) )
		{
			try
			{
				$data = json_decode($extension->manifest_cache, true);
			}
			catch (Exception $e)
			{
				$data = array();
			}

			foreach ($data as $key => $value)
			{
				if ($key == 'type')
				{
					// Ignore the type field
					continue;
				}

				$extension->$key = $value;
			}
		}

		$extension->author_info = @$extension->authorEmail . '<br />' . @$extension->authorUrl;
		$extension->client = $extension->client_id ? JText::_('JADMINISTRATOR') : JText::_('JSITE');
		$extension->client_translated = $extension->client;
		$extension->type_translated = JText::_('COM_INSTALLER_TYPE_' . strtoupper($extension->type));
		$extension->folder_translated = @$extension->folder ? $extension->folder : JText::_('COM_INSTALLER_TYPE_NONAPPLICABLE');
		$extension->currentversion = @$extension->version ? @$extension->version : "0.0.0";

		$path = $extension->client_id ? JPATH_ADMINISTRATOR : JPATH_SITE;

		try
		{
			switch ($extension->type)
			{
				case 'component':
					$extensionelement = $extension->element;
					$source           = JPATH_ADMINISTRATOR . '/components/' . $extensionelement;
					$lang->load("$extensionelement.sys", JPATH_ADMINISTRATOR, null, false, true) || $lang->load("$extensionelement.sys", $source, null, false, true);
					break;
				case 'file':
					$extensionelement = 'files_' . $extension->element;
					$lang->load("$extensionelement.sys", JPATH_SITE, null, false, true);
					break;
				case 'library':
					$extensionelement = 'lib_' . $extension->element;
					$lang->load("$extensionelement.sys", JPATH_SITE, null, false, true);
					break;
				case 'module':
					$extensionelement = $extension->element;
					$source           = $path . '/modules/' . $extensionelement;
					$lang->load("$extensionelement.sys", $path, null, false, true) || $lang->load("$extensionelement.sys", $source, null, false, true);
					break;
				case 'plugin':
					$extensionelement = 'plg_' . $extension->folder . '_' . $extension->element;
					$source           = JPATH_PLUGINS . '/' . $extension->folder . '/' . $extensionelement;
					$lang->load("$extensionelement.sys", JPATH_ADMINISTRATOR, null, false, true) || $lang->load("$extensionelement.sys", $source, null, false, true);
					break;
				case 'template':
					$extensionelement = 'tpl_' . $extension->element;
					$source           = $path . '/templates/' . $extensionelement;
					$lang->load("$extensionelement.sys", $path, null, false, true) || $lang->load("$extensionelement.sys", $source, null, false, true);
					break;
				case 'package':
				default:
					$extensionelement = $extension->element;
					$lang->load("$extensionelement.sys", JPATH_SITE, null, false, true);
					break;
			}
		}
		catch (Exception $e)
		{
			if (!isset($returnData->log))
			{
				$returnData->log = array();
			}
			$returnData->log[] = 'Failed to load language for ' . $extension->element;
		}

		// Translate the extension name if possible
		$extension->name = JText::_($extension->name);

		settype($extension->description, 'string');

		if (!in_array($extension->type, array('language'))
			&& strpos($extension->description, " ") === false
			&& strtoupper($extension->description) == $extension->description
		)
		{
			// ONLY translate capitalised non-spaced content and json_encode for safety
			$extension->description = json_encode(JText::_($extension->description));
		}

		settype($extension->update_location, 'string');
		if (isset($updatesites_extension_mapping[$extension->extension_id]) && isset($updatesites[$updatesites_extension_mapping[$extension->extension_id]->update_site_id]))
		{

			$extension->update_location = $updatesites[$updatesites_extension_mapping[$extension->extension_id]->update_site_id]->location;
		}

		if (!empty($extension->update_location))
		{

			// JUpdater::getInstance()->findUpdates($eid, $cache_timeout, JUpdater::STABILITY_STABLE);

			$update = new Joomla\CMS\Updater\Compatibility();
			$update->set('jversion.full', $joomlaTargetVersion);
			$update->loadFromXML($extension->update_location);

			$downloadUrl = $update->get('downloadurl');

			// This could be a collection not an extension xml file so find the relevant URL
			if (!$downloadUrl && count($update->get('extensions', array())) > 0)
			{
				$extensionUpdates = $update->get('extensions', array());

				foreach ($extensionUpdates as $extensionUpdate)
				{
					if ($extensionUpdate->element == $extension->element
						&& $extensionUpdate->type == $extension->type
						&& (!isset($extensionUpdate->client) || $extensionUpdate->client == ($extension->client_id ? 'administrator' : 'site'))
						&& isset($extensionUpdate->detailsurl)
					)
					{
						$update = new Joomla\CMS\Updater\Compatibility();
						$update->loadFromXML($extensionUpdate->detailsurl);
						$downloadUrl = $update->get('downloadurl');
						break;
					}
				}
			}

			$compatibleVersion = !empty($downloadUrl) && !empty($downloadUrl->_data) ? $update->get('version') : false;

			if ($compatibleVersion)
			{
				$extension->compatibility =  (object) array('state' => 1, 'compatibleVersion' => $compatibleVersion->_data);
			}
			else
			{
				$extension->compatibility =  (object) array('state' => 0);
			}

			$update = new Joomla\CMS\Updater\Compatibility();
			$update->set('jversion.full', $joomlaCurrentVersion);
			$update->loadFromXML($extension->update_location);

			$downloadUrl = $update->get('downloadurl');

			// This could be a collection not an extension xml file so find the relevant URL
			if (!$downloadUrl && count($update->get('extensions', array())) > 0)
			{
				$extensionUpdates = $update->get('extensions', array());

				foreach ($extensionUpdates as $extensionUpdate)
				{
					if ($extensionUpdate->element == $extension->element
						&& $extensionUpdate->type == $extension->type
						&& $extensionUpdate->client == ($extension->client_id ? 'administrator' : 'site')
						&& isset($extensionUpdate->detailsurl)
					)
					{
						$update = new Joomla\CMS\Updater\Compatibility();
						$update->loadFromXML($extensionUpdate->detailsurl);
						$downloadUrl = $update->get('downloadurl');
						break;
					}
				}
			}

			$compatibleVersion = !empty($downloadUrl) && !empty($downloadUrl->_data) ? $update->get('version') : false;

			if ($compatibleVersion)
			{
				$extension->currentcompatibility =  (object) array('state' => 1, 'compatibleVersion' => $compatibleVersion->_data);
			}
			else
			{
				$extension->currentcompatibility =  (object) array('state' => 0);
			}

		}
		else
		{
			$extension->compatibility = (object) array('state' => 2);
			$extension->currentcompatibility = (object) array('state' => 2);
		}

		$nonJoomlaExtensionList[] = $extension;

		unset($extension);
	}

	unset($extensions);

	$returnData = getSiteInfo($returnData);

	if (count($nonJoomlaExtensionList))
	{
		$returnData->result = "something";
		$returnData->versioninfo = $nonJoomlaExtensionList;
	}
	else
	{
		$returnData->result = "nothing";
		$returnData->versioninfo = array();
	}
}

function findExtensions($requestObject, & $returnData)
{
	// clear the Extensions cache
	purgeCache($requestObject, $returnData);

	$lang = JFactory::getLanguage();
	$db = JFactory::getDbo();

	// find list of extensions - that have an updater record
	// Find updater site entries and mapping to extensions table.
	$query = $db->getQuery(true);
	$query->select('DISTINCT a.update_site_id, a.type, a.location, a.last_check_timestamp, a.extra_query')
		->from($db->quoteName('#__update_sites', 'a'))
		->where('a.enabled = 1'); // Do we need enabled entries ??
	$db->setQuery($query);
	$updatesites = $db->loadObjectList('update_site_id');

	$returnData->updatesitescount = count($updatesites);

	// Now fetch list of extensions
	// Need to load com_installer language files
	$lang->load("com_installer", JPATH_ADMINISTRATOR, null, false, true);

	$query = $db->getQuery(true)
		->select('*')
		->select('2*protected+(1-protected)*enabled AS status')
		->from('#__extensions')
		// TODO find what state = 0 means!
		->where('state = 0')
		// leave out protected Joomla extensions?? Can't do this since it misses out on JoomlaUpdater Component
		//->having('status <> 2')
	;

	$db->setQuery($query);
	$extensions = $db->loadObjectList();

	$returnData->extensionscount = count($extensions);

	// Get Joomla files extension - the last one installed by Joomla as proxy for core software id
	$query = $db->getQuery(true)
		->select('*')
		->from('#__extensions')
		->where('type = "file"')
		->where('element = "joomla"')
		->where('name = "files_joomla"');

	$db->setQuery($query);
	$joomlaextension = $db->loadObject();

	//  Get the English language extension
	$query = $db->getQuery(true)
		->select('*')
		->from('#__extensions')
		->where('type = "package"')
		->where('element = "pkg_en-GB"');

	$db->setQuery($query);
	$joomlaenglish = $db->loadObject();

	// TODO confirm that extension_id < 10000 is a good proxy for core software
	$nonJoomlaExtensions = 10000;
	if ($joomlaextension)
	{
		$nonJoomlaExtensions = $joomlaextension->extension_id;
	}
	if ($joomlaenglish)
	{
		$nonJoomlaExtensions = $joomlaenglish->extension_id > $nonJoomlaExtensions ? $joomlaenglish->extension_id : $nonJoomlaExtensions;
	}

	// Get the mapping table
	$query = $db->getQuery(true);
	$query->select('*')
		->from($db->quoteName('#__update_sites_extensions', 'a'));
	$db->setQuery($query);
	// TODO can there every be more than one entry in this mapping table?
	$updatesites_extension_mapping = $db->loadObjectList('extension_id');

	foreach ($extensions as &$extension)
	{
		if (strlen($extension->manifest_cache) )
		{
			try
			{
				$data = json_decode($extension->manifest_cache, true);
			}
			catch (Exception $e)
			{
				$data = array();
			}

			foreach ($data as $key => $value)
			{
				if ($key == 'type')
				{
					// Ignore the type field
					continue;
				}

				$extension->$key = $value;
			}
		}

		// Skip params - we don't do anything with them at present and cleaning them takes time.
		if (strlen($extension->params) && strpos($extension->params, "{") ===0 )
		{
			$extension->params = "{}";
		}

		$extension->author_info = @$extension->authorEmail . '<br />' . @$extension->authorUrl;
		$extension->client = $extension->client_id ? JText::_('JADMINISTRATOR') : JText::_('JSITE');
		$extension->client_translated = $extension->client;
		$extension->type_translated = JText::_('COM_INSTALLER_TYPE_' . strtoupper($extension->type));
		$extension->folder_translated = @$extension->folder ? $extension->folder : JText::_('COM_INSTALLER_TYPE_NONAPPLICABLE');

		try {
			$extension->coresoftware = ExtensionHelper::checkIfCoreExtension($extension->type, $extension->element, $extension->client_id, $extension->folder) ? 1 : 0;
		}
		catch (Exception $e)
		{
			$extension->coresoftware = $extension->extension_id < $nonJoomlaExtensions ? 1 : 0;
		}
		$extension->currentversion = @$extension->version ? @$extension->version : "0.0.0";

		$path = $extension->client_id ? JPATH_ADMINISTRATOR : JPATH_SITE;

		try
		{
			switch ($extension->type)
			{
				case 'component':
					$extensionelement = $extension->element;
					$source           = JPATH_ADMINISTRATOR . '/components/' . $extensionelement;
					$lang->load("$extensionelement.sys", JPATH_ADMINISTRATOR, null, false, true) || $lang->load("$extensionelement.sys", $source, null, false, true);
					break;
				case 'file':
					$extensionelement = 'files_' . $extension->element;
					$lang->load("$extensionelement.sys", JPATH_SITE, null, false, true);
					break;
				case 'library':
					$extensionelement = 'lib_' . $extension->element;
					$lang->load("$extensionelement.sys", JPATH_SITE, null, false, true);
					break;
				case 'module':
					$extensionelement = $extension->element;
					$source           = $path . '/modules/' . $extensionelement;
					$lang->load("$extensionelement.sys", $path, null, false, true) || $lang->load("$extensionelement.sys", $source, null, false, true);
					break;
				case 'plugin':
					$extensionelement = 'plg_' . $extension->folder . '_' . $extension->element;
					$source           = JPATH_PLUGINS . '/' . $extension->folder . '/' . $extensionelement;
					$lang->load("$extensionelement.sys", JPATH_ADMINISTRATOR, null, false, true) || $lang->load("$extensionelement.sys", $source, null, false, true);
					break;
				case 'template':
					$extensionelement = 'tpl_' . $extension->element;
					$source           = $path . '/templates/' . $extensionelement;
					$lang->load("$extensionelement.sys", $path, null, false, true) || $lang->load("$extensionelement.sys", $source, null, false, true);
					break;
				case 'package':
				default:
					$extensionelement = $extension->element;
					$lang->load("$extensionelement.sys", JPATH_SITE, null, false, true);
					break;
			}
		}
		catch (Exception $e)
		{
			if (!isset($returnData->log))
			{
				$returnData->log = array();
			}
			$returnData->log[] = 'Failed to load language for ' . $extension->element;
		}

		// Translate the extension name if possible
		$extension->name = JText::_($extension->name);

		settype($extension->description, 'string');

		if (!in_array($extension->type, array('language'))
			&& strpos($extension->description, " ") === false
			&& strtoupper($extension->description) == $extension->description
		)
		{
			// ONLY translate capitalised non-spaced content and json_encode for safety
			$extension->description = json_encode(JText::_($extension->description));
		}

		settype($extension->update_location, 'string');
		if (isset($updatesites_extension_mapping[$extension->extension_id]) && isset($updatesites[$updatesites_extension_mapping[$extension->extension_id]->update_site_id]))
		{

			$extension->update_location = $updatesites[$updatesites_extension_mapping[$extension->extension_id]->update_site_id]->location;
		}

		unset($extension);
	}

	if ($extensions)
	{
		$returnData->result = "something";
		$returnData->extensions = $extensions;
		$returnData->updatesites = $updatesites;
	}
	else
	{
		$returnData->result = "nothing";
	}
}

function purgeCache($requestObject, &$returnData)
{
	// TODO check messages returned from model
	// Get the update model and retrieve the Joomla! core updates
	$model = JModelLegacy::getInstance("Update", "InstallerModel");
	if (!$model)
	{
		JLoader::import('Model.UpdateModel', JPATH_ADMINISTRATOR . '/components/com_installer');
		$model = new Joomla\Component\Installer\Administrator\Model\UpdateModel();
	}

	// I need this to force the state population - otherwise my setState gets ignored!
	$temp = $model->getState('list.limit');

	$model->purge();
	$returnData->result = "Cache Cleared";
	$returnData->messages[] = "COM_YOURSITES_EXTENSION_VERSION_CACHE_CLEARED";

	$returnData->messages[] = $model->get("_message", "");

	return $returnData;
}

function findJoomlaUpdates($requestObject, $returnData)
{
	// clear the Joomla cache first
	purgeJoomla($requestObject, $returnData);

	$lang = JFactory::getLanguage();
	$lang->load("com_joomlaupdate", JPATH_ADMINISTRATOR);

	// Do we need to change the update server settings
	$updateParams = JComponentHelper::getParams('com_joomlaupdate');
	$updateSource = $updateParams->get("updatesource", 'default');
	$customURL    = $updateParams->get("customurl", '');

	$serverChanged = false;
	if (!isset($requestObject->updateserver) || empty($requestObject->updateserver))
	{
		if ($updateSource !== 'default')
		{
			$serverChanged = true;
		}
		$updateParams->set("updatesource", 'default');
		$updateParams->set("customurl", '');
	}
	else
	{
		if ($updateSource !== 'custom' || $customURL != $requestObject->updateserver)
		{
			$serverChanged = true;
		}
		$updateParams->set("updatesource", 'custom');
		$updateParams->set("customurl", $requestObject->updateserver);
	}
	if ($serverChanged)
	{
		$joomlaUpdate = JComponentHelper::getComponent('com_joomlaupdate');
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->update($db->quoteName('#__extensions'))
			->set($db->quoteName('params') . ' = ' . $db->quote($updateParams->toString()))
			->where($db->quoteName('extension_id') . ' = ' . $joomlaUpdate->id);
		$db->setQuery($query);
		$db->execute();
	}

	JLoader::import('models.default', JPATH_ADMINISTRATOR . '/components/com_joomlaupdate');
	if (class_exists("JoomlaupdateModelDefault", true))
	{
		$joomlaModel = new JoomlaupdateModelDefault();
	}
	else
	{
		// \Joomla\Component\Joomlaupdate\Administrator\Model\UpdateModel
		JLoader::import('Model.UpdateModel', JPATH_ADMINISTRATOR . '/components/com_joomlaupdate');
		$joomlaModel = new Joomla\Component\Joomlaupdate\Administrator\Model\UpdateModel();
	}

	// Joomla Model purge assumes Joomla update site id is 1 - which it may not be so belts and braces
	$db = JFactory::getDbo();
	$query = $db->getQuery(true);
	$query->update('#__update_sites as us')
		->set('last_check_timestamp = 0')
		->innerJoin('#__update_sites_extensions as usex ON usex.update_site_id = us.update_site_id')
		->innerJoin('#__extensions as ex ON ex.extension_id = usex.extension_id')
		->where('ex.element = ' . $db->quote('joomla') . ' or ex.name = ' . $db->quote('joomla_files'));
	$db->setQuery($query);
	$sql = (string) $db->getQuery();
	$res = $db->execute();
	/*
	  $query = $db->getQuery(true);
	  $query->delete('#__updates')
	  ->where('extension_id = 700');
	  $db->setQuery($query);
	  $sql = (string) $db->getQuery();
	  $res = $db->execute();
	 */
	// Perform update source preference check and refresh update information.
	$joomlaModel->applyUpdateSite();
	$joomlaModel->refreshUpdates();

	$updateInfo = $joomlaModel->getUpdateInformation();
	$returnData->joomlaUpdateInfo = $updateInfo;

	$returnData = getSiteInfo($returnData);

	$plugin = JPluginHelper::getPlugin("system" , "yoursites");
	if ($plugin)
	{
		$params      = json_decode($plugin->params);
		if (isset($params->allowdirectlogin) && !$params->allowdirectlogin)
		{
			$returnData->dluser = -1;
		}
		// Overrule direct login user if its set
		if (isset($params->dluser) && $params->dluser > 0)
		{
			$returnData->dluser = -999;
		}
	}

	// Pre Joomla 3.6.x
	if (!isset($updateInfo['hasUpdate']) && isset($updateInfo['installed'])  && isset($updateInfo['latest']))
	{
		$updateInfo['hasUpdate'] = version_compare($updateInfo['latest'], $updateInfo['installed'], ">");
	}

	if (!isset($updateInfo['object']) || !$updateInfo['hasUpdate'])
	{
		$returnData->result = "Joomla Already Up to date";
		$returnData->messages[] = "COM_YOURSITES_CORE_VERSION_UP_TO_DATE";
		if (isset($updateInfo['installed']))
		{
			$returnData->messages[] = "Version " . $updateInfo['installed'] . " is installed";
			$returnData->installedVersion = $updateInfo['installed'];
		}

	}
	else if (isset($updateInfo['object']->get('infourl')->_data) && isset($updateInfo['object']->get('infourl')->title))
	{
		$returnData->infourl = $updateInfo['object']->get('infourl');
		$returnData->result = "Joomla Update Data Found";
		$returnData->messages[] = "COM_YOURSITES_CORE_VERSION_UPDATE_AVAILABLE";
		$returnData->messages[] = "Update available from " . $updateInfo['installed'] .   " to " . $updateInfo['latest'];
		$returnData->installedVersion = $updateInfo['installed'];
	}
}

function upgradeJoomla($requestObject, & $returnData)
{
	// need logged in session
	//$requestObject->userid = 61;
	//directLogin($requestObject, $returnData, false);

	// Recent Joomla upgrades have required more memory for the unpacking
	$memory_limit = trim(ini_get('memory_limit'));
	$unit         = strtoupper(substr($memory_limit, -1));

	if ($unit == 'G')
	{
		$memory_limit = substr($memory_limit , 0, strlen($memory_limit) - 1 );
		$memory_limit = $memory_limit*1024*1024*1024 ;
	}
	else if ($unit == 'M')
	{
		$memory_limit = substr($memory_limit , 0, strlen($memory_limit) - 1 );
		$memory_limit = $memory_limit*1024*1024 ;
	}
	if ($memory_limit < 64 * 1024 * 1024)
	{
		@ini_set('memory_limit', '64M');
	}

	// TODO must handle FTP credentials etc.
	$lang = JFactory::getLanguage();
	$lang->load("com_joomlaupdate", JPATH_ADMINISTRATOR);

	// TODO check for other undefined constants!
	if (!defined('JPATH_COMPONENT_ADMINISTRATOR'))
	{
		define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/com_joomlaupdate');
	}

	JLoader::import('models.default', JPATH_ADMINISTRATOR . '/components/com_joomlaupdate');
	if (class_exists("JoomlaupdateModelDefault", true))
	{
		$joomlaModel = new JoomlaupdateModelDefault();
	}
	else
	{
		// \Joomla\Component\Joomlaupdate\Administrator\Model\UpdateModel
		JLoader::import('Model.UpdateModel', JPATH_ADMINISTRATOR . '/components/com_joomlaupdate');
		$joomlaModel = new Joomla\Component\Joomlaupdate\Administrator\Model\UpdateModel();
	}

	try
	{
		// Fetch Joomla! updates
		$updateInfo = $joomlaModel->getUpdateInformation();

		// Did we find an update?!?! - Client site may have been updated manually prior to Joomla! Version check
		if (!$updateInfo["hasUpdate"])
		{
			// So cache could be out of date, lets double check... refresh updates
			$joomlaModel->refreshUpdates();

			// Refresh Joomla! Updates Info
			$updateInfo2 = $joomlaModel->getUpdateInformation();

			// Did we find an update?!?! - Client site may have been updated manually prior to Joomla! Version check
			if (!$updateInfo2["hasUpdate"]) {

				$returnData->error           = 0;
				$returnData->result          = "No Joomla Update Available";
				$returnData->messages[] = 'COM_YOURSITES_NO_UPDATE_AVAILABLE';
				$returnData->updateInfo      = $updateInfo2;

				return;
			}
		}

		if (isset($requestObject->blockedversion) && $requestObject->blockedversion && version_compare($updateInfo["latest"], $requestObject->blockedversion, "ge"))
		{
			$returnData->error = 1;
			$returnData->errormessages[] = 'COM_1.15.0_UPGRADE_HAS_BEEN_BLOCKED';
			return;
		}

		$file = $joomlaModel->download();

		// Joomla 3.9 checksum checks
		if (is_array($file) && isset($file['basename']))
		{
			if (isset($file['check']) && !$file['check'])
			{
				$file = false;
			}
			else
			{
				$file = $file['basename'];
			}
		}

	}
	catch (Exception $e)
	{
		$x = 1;
	}

	if (!$file)
	{
		JFactory::getApplication()->setUserState('com_joomlaupdate.file', null);
		$url = 'index.php?option=com_joomlaupdate';
		// TODO HANDLE THIS FAILURE
		$returnData->error = 1;
		$returnData->result = "Joomla NOT Downloaded";
		$returnData->errormessages[] = 'COM_JOOMLAUPDATE_VIEW_UPDATE_DOWNLOADFAILED';
		return;
	}

	// Now move on to Install phase - mimicking controller

	$joomlaModel->createRestorationFile($file);

	$password = JFactory::getApplication()->getUserState('com_joomlaupdate.password', null);
	$filesize = JFactory::getApplication()->getUserState('com_joomlaupdate.filesize', null);

	$returnData->password = $password;
	$returnData->filesize = $filesize;

	$returnData->error = 1;
	$returnData->result = "Joomla NOT Downloaded";

	// This is now when Joomla displays the progress window
	// restore.php checks the password before doing the restore
	// Encrypting the JSON didn't work so replace the password in the restoration file with blank value as workaround
	// If we do this TAKE CARE  - it may leave the restore active if the cleanup doesn't work!

	//JLoader::import('joomla.filesystem.file');
	//$restorationFile = JPATH_ADMINISTRATOR . '/components/com_joomlaupdate/restoration.php';
	//$restorationFilecontent = file_get_contents($restorationFile);
	//$restorationFilecontent = preg_replace("#'kickstart\.security\.password'\ \=\> '.*?'#", "'kickstart.security.password' => ''", $restorationFilecontent);
	//JFile::write($restorationFile, $restorationFilecontent);

	// TODO Trap errors etc
	$returnData->messages[] = "restore.php " . JUri::base() . "/administrator/components/com_joomlaupdate/restore.php";
	$returnData->messages[] = JFile::exists(JPATH_SITE . "/administrator/components/com_joomlaupdate/restore.php");

	// Use JHttpFactory that allows using CURL and Sockets as alternative method when available
	// Adding a valid user agent string etc.
	$goptions = new JRegistry;
	$goptions->set('userAgent', 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:41.0) Gecko/20100101 Firefox/41.0');
	$goptions->set('timeout', '120');

	/*
	$goptions->set("transport.curl", array(
			CURLOPT_COOKIE => "anotherverylongcookiecode=averylongcookiecode;",
		)
	);
	*/

	if (isset($requestObject->htuser) && !empty($requestObject->htuser) && isset($requestObject->htpwd) && !empty($requestObject->htpwd))
	{
		$goptions->set('userauth', $requestObject->htuser);
		$goptions->set('passwordauth', $requestObject->htpwd);
	}

	$http = JHttpFactory::getHTTP($goptions);

	// could pass session information and cookies via $headers argument etc.
	$headers = array();

	$data = array();
	$updatejson = json_encode(array('task' => "startRestore"));
	$data["task"] = "startRestore";
	$data["json"] = AKEncryptionAES::AESEncryptCtr($updatejson, $password, 128);

	$debug = "?XDEBUG_SESSION_START=PHPSTORM";
	$debug = "";

	$restoreURL = JUri::base() . "administrator/components/com_joomlaupdate/restore.php";

	$webpage = $http->post($restoreURL . $debug, $data, $headers);
	//$webpage = $http->get($restoreURL ."?task=" . $data["task"] . "&json=" . $data["json"], $headers);

	// catch the output
	// TODO need method to parse the output from KickStart
	$returnData->joomlaUpdateOutput = $webpage->body;
	$requestObject->restoreData = trim($returnData->joomlaUpdateOutput, "#");
	$updatejsonraw = AKEncryptionAES::AESDecryptCtr($requestObject->restoreData, $password, 128);
	$updatejson = json_decode($updatejsonraw);

	if (!$updatejson->status)
	{
		$returnData->error = 1;
		$returnData->password = $password;
		$returnData->result = "Joomla Upgrade Failed";
		$returnData->errormessages[] = 'COM_YOURSITES_JOOMLAUPDATE_STARTRESTORE_FAILED';
		$returnData->errormessages[] = 'Restore Update Code ' . $webpage->code;
		if ($webpage->code == 401)
		{
			$returnData->errormessages[] = 'COM_YOURSITES_JOOMLAUPDATE_STARTRESTORE_FAILED_ADMIN_HTAUTH_NEEDED';
		}
		if (!empty($updatejson->message))
		{
			$returnData->errormessages[] = $updatejson->message;
		}
		if (!isset($returnData->log))
		{
			$returnData->log = array();
		}
		$returnData->log['updatejson'] = $updatejson;
		$returnData->log['updatejsonraw'] = $updatejson;
		$returnData->log['restoreData'] = $requestObject->restoreData;

		return;
	}
	else
	{
		$returnData->error = 0;
	}

	return;
}

function finaliseJoomlaUpgrade($requestObject, & $returnData)
{
	// need logged in session
	//$requestObject->userid = 61;
	//directLogin($requestObject, $returnData, false);

	$user = JFactory::getUser();

	// TODO must handle FTP credentials etc.
	$lang = JFactory::getLanguage();
	$lang->load("com_joomlaupdate", JPATH_ADMINISTRATOR);

	// TODO check for other undefined constants!
	if (!defined('JPATH_COMPONENT_ADMINISTRATOR'))
	{
		define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/com_joomlaupdate');
	}

	JLoader::import('models.default', JPATH_ADMINISTRATOR . '/components/com_joomlaupdate');
	if (class_exists("JoomlaupdateModelDefault", true))
	{
		$joomlaModel = new JoomlaupdateModelDefault();
	}
	else
	{
		// \Joomla\Component\Joomlaupdate\Administrator\Model\UpdateModel
		JLoader::import('Model.UpdateModel', JPATH_ADMINISTRATOR . '/components/com_joomlaupdate');
		$joomlaModel = new Joomla\Component\Joomlaupdate\Administrator\Model\UpdateModel();
	}

	$joomlaModel->finaliseUpgrade();

	// prepare for clean up - need to find the package file in order to remove it
	$config = JFactory::getConfig();
	$tempdir = $config->get('tmp_path');

	// Calling cleaup isn't enough to remove the package since the session variable isn't stored
	$packagefiles = JFolder::files($tempdir, 'Joomla_[0-9.]*.*\.zip');
	if (count($packagefiles))
	{
		JFactory::getApplication()->setUserState('com_joomlaupdate.file', current($packagefiles));
	}

	$joomlaModel->cleanUp();

	$returnData->error = 0;
	$returnData->result = "Joomla Upgrade Success";
	$returnData->messages[] = 'COM_YOURSITES_JOOMLAUPDATE_FINISHED';

	return;
}

function purgeJoomla($requestObject, &$returnData)
{
	$lang = JFactory::getLanguage();
	$lang->load("com_joomlaupdate", JPATH_ADMINISTRATOR);
	$lang->load("lib_joomla", JPATH_ADMINISTRATOR);

	JLoader::import('models.default', JPATH_ADMINISTRATOR . '/components/com_joomlaupdate');
	if (class_exists("JoomlaupdateModelDefault", true))
	{
		$joomlaModel = new JoomlaupdateModelDefault();
	}
	else
	{
		// \Joomla\Component\Joomlaupdate\Administrator\Model\UpdateModel
		JLoader::import('Model.UpdateModel', JPATH_ADMINISTRATOR . '/components/com_joomlaupdate');
		$joomlaModel = new Joomla\Component\Joomlaupdate\Administrator\Model\UpdateModel();
	}

	// TODO check messages returned from model
	$success = $joomlaModel->purge();
	if ($success)
	{
		$returnData->result = "COM_YOURSITES_CORE_VERSION_CACHE_CLEARED";
	}
	else
	{
		$returnData->result = "COM_YOURSITES_CORE_VERSION_CLEAR_CLEAR_FAILED";
	}
	$returnData->messages[] = $joomlaModel->get("_message", "");
}

function updateExtension($requestObject, & $returnData)
{
	// Need to load com_installer language files
	$lang = JFactory::getLanguage();
	$lang->load("com_installer", JPATH_ADMINISTRATOR, null, false, true);

	// Get the update_id for the extension being updated!
	$db    = JFactory::getDbo();
	$query = $db->getQuery(true);
	$query->select('a.*')
		->from($db->quoteName('#__updates', 'a'))
		->where('a.extension_id = ' . (int) $requestObject->extension_id)
		->order('a.update_id DESC');
	$db->setQuery($query);
	$update = $db->loadObject();

	if (!$update)
	{
		$returnData->result          = "update failed - no update available";
		$returnData->error           = true;
		$returnData->errormessages[] = "COM_YOURSITES_UPDATE_FAILED_NO_UPDATE_AVAILABLE";

		return $returnData;
	}

	// TODO data integrity check
	$update_id = $update->update_id;

	$model = JModelLegacy::getInstance("Update", "InstallerModel");
	if (!$model)
	{
		JLoader::import('Model.UpdateModel', JPATH_ADMINISTRATOR . '/components/com_installer');
		$model = new Joomla\Component\Installer\Administrator\Model\UpdateModel();
	}
	// I need this to force the state population - otherwise my setState gets ignored!
	$temp = $model->getState('list.limit');

	// do I need to set the extra_query in the update URL?
	$query = $db->getQuery(true);
	$query->select('a.*')
		->from($db->quoteName('#__extensions', 'a'))
		->where('a.extension_id = ' . (int) $requestObject->extension_id);
	$db->setQuery($query);
	$extension = $db->loadObject();

	// if this is a package we need to find the embedded elements and $component
	$packagePlugins = array();
	$component       = false;
	if ($extension && $extension->type == "package")
	{
		// This doesn't work in Joomla 3.6.x - use try/catch
		try
		{
			$query = $db->getQuery(true);
			$query->select('a.*')
				->from($db->quoteName('#__extensions', 'a'))
				->where('a.package_id = ' . (int) $extension->extension_id)
				->where('a.type = "component"');
			$db->setQuery($query);
			$component = $db->loadObject();
			if ($component)
			{
				$extension = $component;
			}

			$query = $db->getQuery(true);
			$query->select('a.*')
				->from($db->quoteName('#__extensions', 'a'))
				->where('a.package_id = ' . (int) $extension->extension_id)
				->where('a.type = "plugin"');
			$db->setQuery($query);
			$packagePlugins = $db->loadObjectList();
		}
		catch (Exception $e)
		{

		}
	}

	if ($extension && isset($extension->params))
	{
		try
		{
			$extensionparams = json_decode($extension->params);
			if (isset($extensionparams->update_dlid) && !empty($extensionparams->update_dlid))
			{
				$extraquery = "dlid=" . $extensionparams->update_dlid;
			}
			else if (isset($extensionparams->dlid) && !empty($extensionparams->dlid))
			{
				$extraquery = "dlid=" . $extensionparams->dlid;
			}
			else if (isset($extensionparams->downloadid) && !empty($extensionparams->downloadid))
			{
				$extraquery = "dlid=" . $extensionparams->downloadid;
			}

			// Special cases like Regular Labs!
			//$args->dlids[] = array('locationfilter' => 'download.regularlabs.com', 'namefilter' => '', 'extraquery' => 'k=' . $params->get("regularlabs_dlid", false));
			if (empty($extraquery) && isset($requestObject->dlids) && !empty($requestObject->dlids))
			{
				foreach ($requestObject->dlids as $dlid)
				{
					if (!empty($dlid->locationfilter) && strpos($update->detailsurl, $dlid->locationfilter) !== false)
					{
						if (!empty($dlid->extraquery))
						{
							$extraquery = "";
							foreach (get_object_vars($dlid->extraquery) as $k => $v)
							{
								if (!empty($extraquery))
								{
									$extraquery .= "&";
								}
								$extraquery .= $k . "=" . $v;
							}
						}

						if (!empty($dlid->params))
						{
							$componentParams = &JComponentHelper::getComponent($extension->element)->params;
							foreach (get_object_vars($dlid->params) as $k => $v)
							{
								$componentParams->set($k, $v);
							}
						}
						break;
					}
					else if (!empty($dlid->namefilter) && strpos($update->name, $dlid->namefilter) !== false)
					{
						if (!empty($dlid->extraquery))
						{
							$extraquery = $dlid->extraquery;
						}
						else if (!empty($dlid->params))
						{
							$componentParams = &JComponentHelper::getComponent($extension->element)->params;
							foreach (get_object_vars($dlid) as $k => $v)
							{
								$componentParams->set($k, $v);
							}
						}
						break;
					}
				}
			}
		}
		catch (Exception $ex)
		{
			$extensionparams = json_decode("{}");
		}
	}
	// Special case for extensions like YooTheme that set parameters of a plugin to trigger the update
	if (!$component && count($packagePlugins))
	{
		foreach($packagePlugins as $packagePlugin)
		{
			$plugin = JPluginHelper::getPlugin($packagePlugin->folder , $packagePlugin->element);
			if (isset($plugin->params))
			{
				$pluginParams = new JRegistry($plugin->params);
				foreach ($requestObject->dlids as $dlid)
				{
					if (!empty($dlid->locationfilter) && strpos($update->detailsurl, $dlid->locationfilter) !== false)
					{
						if (!empty($dlid->params))
						{
							foreach (get_object_vars($dlid->params) as $k => $v)
							{
								$pluginParams->set($k, $v);
							}
						}
					}
				}
				$plugin->params = $pluginParams->toString();
			}
		}
	}

	$minimum_stability = JUpdater::STABILITY_STABLE;

	$update = new JUpdate;
	$instance = JTable::getInstance('update');
	$instance->load($update_id);
	$success = $update->loadFromXml($instance->detailsurl, $minimum_stability);
	if (!$success)
	{
		$returnData->error = true;
		$errormessages = JFactory::getApplication()->getMessageQueue();
		$returnData->errormessages[] = "COM_YOURSITES_UNABLE_TO_PARSE_UPDATE_XML_FILE";
		if (count($errormessages))
		{
			foreach ($errormessages as $errormessage)
			{
				$returnData->errormessages[] = $errormessage["type"] . " : " . $errormessage["message"] ;
			}
		}
		$returnData->result = "update failed";
		return $returnData;

	}

	$extra_query = $instance->get('extra_query', "");
	if (empty($extra_query) && isset($extraquery))
	{
		$update->set('extra_query', $extraquery);
		$instance->set('extra_query', $extraquery);
	}
	else if ($instance->get('extra_query', "") == "dlid=" && isset($extraquery))
	{
		$update->set('extra_query', $extraquery);
		$instance->set('extra_query', $extraquery);
	}
	else
	{
		$update->set('extra_query', $instance->get('extra_query', ""));
	}

	// Local version of preparePreUpdate($update, $instance);
	preparePreUpdate($update, $instance);

	// Some extensions assume they are being updates from within com_installer so we must make them think that they are
	$option = "com_installer";
	if (!defined('JPATH_COMPONENT'))
	{
		define('JPATH_COMPONENT', JPATH_BASE . '/components/' . $option);
	}

	if (!defined('JPATH_COMPONENT_SITE'))
	{
		define('JPATH_COMPONENT_SITE', JPATH_SITE . '/components/' . $option);
	}

	if (!defined('JPATH_COMPONENT_ADMINISTRATOR'))
	{
		define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/' . $option);
	}

	// set the option value for the action logging
	JFactory::getApplication()->input->set('option', 'com_installer');
	// spoof the username for action logs
	$user = JFactory::getUser();
	$user->username = "YourSites";

	// Install sets state and enqueues messages
	$result = doInstallExtension($update, $returnData);

	// reset username
	$user->username = "";

	if ($result)
	{
		$instance->delete($update_id);
	}

	//	$model->update(array($update_id));
	//	$result = $model->getState('result');

	if ($result)
	{
		// Thisis done by the model update - check though!
		//$model->delete($items[0]->update_id);
		$returnData->result = "updated";

		$app = JFactory::getApplication();
		$messageQueue     = $app->getMessageQueue();
		$installermessage = $app->getUserState('com_installer.message', "");
		$extensionmessage = $app->getUserState('com_installer.extension_message', "");
		$redirect_url     = $app->getUserState('com_installer.redirect_url', "");

		// Some extensions use bad URLs - correct these here
		$extensionmessage = str_replace('//administrator', '/administrator', $extensionmessage);
		$redirect_url = str_replace('//administrator', '/administrator', $redirect_url);

		$returnData->redirect_url = $redirect_url;
		if (!empty($returnData->redirect_url))
		{
			$returnData->warning = 1;
		}

		// Get Message Queue
		if (count($messageQueue))
		{
			foreach ($messageQueue as $msg)
			{
				if ($msg["message"] == "COM_INSTALLER_INSTALL_SUCCESS")
				{
					continue;
				}
				if (strpos($msg["message"], "<script") !== false)
				{
					$returnData->warning = 1;
					$returnData->hasscript = 1;
				}
				$returnData->messages[] = '<div class="installermessage">' . preg_replace('#<script(.*?)>(.*?)</script>#is', '', $msg["message"]) . '</div>';
			}
		}

		if (!empty($installermessage))
		{
			if (strpos($installermessage, "<script") !== false)
			{
				$returnData->warning = 1;
				$returnData->hasscript = 1;
			}
			$returnData->messages[] = '<div class="installermessage">' . preg_replace('#<script(.*?)>(.*?)</script>#is', '', $installermessage) . '</div>';
		}
		if (!empty($extensionmessage))
		{
			if (strpos($extensionmessage, "<script") !== false)
			{
				$returnData->warning = 1;
				$returnData->hasscript = 1;
			}
			$returnData->messages[] = '<div class="extensionmessage">' . preg_replace('#<script(.*?)>(.*?)</script>#is', '', $extensionmessage) . '</div>';
		}
	}
	else
	{
		$returnData->error = true;

		$app = JFactory::getApplication();
		$installermessage = $app->getUserState('com_installer.message', "");
		$extensionmessage = $app->getUserState('com_installer.extension_message', "");

		$errormessages = JFactory::getApplication()->getMessageQueue();
		if (count($errormessages))
		{
			foreach ($errormessages as $errormessage)
			{
				$returnData->errormessages[] = $errormessage["type"] . " : " . $errormessage["message"] ;
			}
		}
		$returnData->result = "update failed";
	}

	$query = $db->getQuery(true)
		->select('*')
		->from('#__extensions')
		->where('extension_id = ' . (int) $requestObject->extension_id);

	$db->setQuery($query);
	$extension = $db->loadObject();

	if (strlen($extension->manifest_cache) && $data = json_decode($extension->manifest_cache))
	{
		foreach ($data as $key => $value)
		{
			if ($key == 'type')
			{
				// Ignore the type field
				continue;
			}

			$extension->$key = $value;
		}
	}

	$returnData->currentversion = @$extension->version ? @$extension->version : "0.0.0";

	return $returnData;
}

function uninstallExtension($requestObject, & $returnData)
{
	// Need to load com_installer language files
	$lang = JFactory::getLanguage();
	$lang->load("com_installer", JPATH_ADMINISTRATOR, null, false, true);

	$db = JFactory::getDbo();
	$query = $db->getQuery(true);
	$query->select('a.*')
		->from($db->quoteName('#__extensions', 'a'))
		->where('a.extension_id = ' . (int) $requestObject->extension_id);
	$db->setQuery($query);
	$extension = $db->loadObject();

	if ($extension && $extension->type)
	{
		try
		{
			// Replicate ManageInstallerModel method logic

			// Get an installer object for the extension type
			$installer = JInstaller::getInstance();

			// Uninstall the chosen extensions
			$langstring    = 'COM_INSTALLER_TYPE_TYPE_' . strtoupper($extension->type);
			$extensiontype = JText::_($langstring);

			if (strpos($extensiontype, $langstring) !== false)
			{
				$extensiontype = $extension->type;
			}

			// set the option value for the action logging
			JFactory::getApplication()->input->set('option', 'com_installer');
			// spoof the username for action logs
			$user = JFactory::getUser();
			$user->username = "YourSites";

			$result = $installer->uninstall($extension->type, (int) $requestObject->extension_id);

			$user->username = "";

			// Build an array of extensions that failed to uninstall
			if ($result === false)
			{
				// There was an error in uninstalling the package
				$returnData->error            = true;
				$returnData->errormessages[] = JText::sprintf('COM_INSTALLER_UNINSTALL_ERROR', $extensiontype);
				if ($installer->message)
				{
					$returnData->errormessages[] = $installer->message;
				}
				if ($installer->get('extension_message'))
				{
					$returnData->errormessages[] = $installer->get('extension_message');
				}
			}
			else
			{
				// Package uninstalled successfully
				$returnData->error      = false;
				$returnData->messages[] = JText::sprintf('COM_INSTALLER_UNINSTALL_SUCCESS', $extensiontype);
				if ($installer->message)
				{
					$returnData->messages[] = $installer->message;
				}
				if ($installer->get('extension_message'))
				{
					$returnData->messages[] = $installer->get('extension_message');
				}
			}

			// Clear the cached extension data and menu cache
			cleanCache('_system', 0);
			cleanCache('_system', 1);
			cleanCache('com_modules', 0);
			cleanCache('com_modules', 1);
			cleanCache('com_plugins', 0);
			cleanCache('com_plugins', 1);
			cleanCache('mod_menu', 0);
			cleanCache('mod_menu', 1);
		}

		catch (Exception $e)
		{
			// There was an error in uninstalling the package
			$result = false;
			$returnData->error = true;
			$returnData->errrormessages[] = JText::sprintf('COM_INSTALLER_UNINSTALL_ERROR', $extension->type);
			$returnData->errrormessages[] = $e->getMessage();
		}
	}
	else
	{
		// There was an error in uninstalling the package
		$result = false;
		$returnData->error = true;
		$returnData->errrormessages[] = JText::sprintf('COM_INSTALLER_UNINSTALL_ERROR', $extension->type);
		$returnData->errrormessages[] = "No such extension";
	}


	return $returnData;
}

function disableExtension($requestObject, & $returnData)
{
	// Need to load com_installer language files
	$lang = JFactory::getLanguage();
	$lang->load("com_installer", JPATH_ADMINISTRATOR, null, false, true);


	$db = JFactory::getDbo();
	$query = $db->getQuery(true);
	$query->update($db->quoteName('#__extensions', 'a'))
		->set('enabled = 0')
		->where('a.extension_id = ' . (int) $requestObject->extension_id);
	$db->setQuery($query);
	$db->execute();


	return $returnData;
}

function enableExtension($requestObject, & $returnData)
{
	// Need to load com_installer language files
	$lang = JFactory::getLanguage();
	$lang->load("com_installer", JPATH_ADMINISTRATOR, null, false, true);


	$db = JFactory::getDbo();
	$query = $db->getQuery(true);
	$query->update($db->quoteName('#__extensions', 'a'))
		->set('enabled = 1')
		->where('a.extension_id = ' . (int) $requestObject->extension_id);
	$db->setQuery($query);
	$db->execute();


	return $returnData;
}

function clearCache($requestObject, & $returnData)
{
	// Need to load com_cache language files
	$lang = JFactory::getLanguage();
	$lang->load("com_cache", JPATH_ADMINISTRATOR, null, false, true);

	$conf = JFactory::getConfig();
	$allCleared = true;
	$clients    = array(1, 0);

	foreach ($clients as $client_id) {

		$options = array(
			'defaultgroup' => '',
			'storage'      => $conf->get('cache_handler', ''),
			'caching'      => true,
			'cachebase'    => (int) $client_id === 1 ? JPATH_ADMINISTRATOR . '/cache' : $conf->get('cache_path', JPATH_SITE . '/cache')
		);

		$mCache    = JCache::getInstance('', $options);

		$clientStr = JText::_($client_id ? 'JADMINISTRATOR' : 'JSITE') .' > ';

		foreach ($mCache->getAll() as $cache)
		{
			if ($mCache->clean($cache->group) === false)
			{
				$returnData->errormessages[] = JText::sprintf('COM_CACHE_EXPIRED_ITEMS_DELETE_ERROR', $clientStr . $cache->group);
				$allCleared = false;
			}
		}
	}

	if ($allCleared)
	{
		$returnData->messages[]  = JText::_('COM_CACHE_MSG_ALL_CACHE_GROUPS_CLEARED');
	}
	else
	{
		$returnData->error = 1;
		$returnData->errormessages[] =  JText::_('COM_CACHE_MSG_SOME_CACHE_GROUPS_CLEARED');
	}

	$returnData->litespeedkey = '';
	$plugin = JPluginHelper::getPlugin("system" , "lscache");
	if ($plugin)
	{
		$returnData->messages[]  = JText::_('COM_YOURSITES_HAS_LITESPEED_CACHE');
		try {
			/*
					jimport('joomla.application.component.controller');

					// TODO check for other undefined constants!
				if (!defined('JPATH_COMPONENT_ADMINISTRATOR'))
				{
					define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/com_lscache');
				}
				if (!defined('JPATH_COMPONENT'))
				{
					define('JPATH_COMPONENT', JPATH_SITE . '/components/com_lscache');
				}

					include_once(JPATH_ADMINISTRATOR . '/components/com_lscache/controllers/modules.php');

			ob_start();
			$LSCacheControllerModules = new LSCacheControllerModules();
			$LSCacheControllerModules->purgeall();
			$LSCacheControllerModules->rebuild();
			$returnData->log['litespeed'] = ob_get_clean();

			$dispatcher = JEventDispatcher::getInstance();
			$LiteSpeedCache = new plgSystemLSCache($dispatcher, (array)$plugin);
			$LiteSpeedCache->purgeObject->purgeAll = true;
			$LiteSpeedCache->purgeAction();

			$LiteSpeedCache->purgeObject->purgeAll = false;
			$LiteSpeedCache->purgeObject->autoRecache = true;
			$LiteSpeedCache->purgeAction();

			$returnData->messages[]  = JText::_('COM_YOURSITES_LITESPEED_CACHE_CLEARED_AND_REBUILT');

			$app = JFactory::getApplication();
			if (count($app->getMessageQueue())) {
				foreach ($app->getMessageQueue() as $message)
				{
					$returnData->messages[] = $message["type"] . " : " . $message["message"] ;
				}
			}
			*/
			$params = JComponentHelper::getParams('com_lscache');

			$returnData->litespeedkey = $params->get('cleanCache', '');

		}
		catch (Exception $e)
		{
			$returnData->errormessages[]  = JText::_('COM_YOURSITES_LITESPEED_CACHE_NOT_CLEARED_AND_REBUILT');
			$returnData->log['error'] = $e->getMessage();
			$returnData->log['errortrace'] = $e->getTraceAsString();
		}
	}

	// Cache usage
	$jconfig = JFactory::getConfig();

	$clientId = 0;
	$options = array(
		'defaultgroup' => '',
		'storage'      => $jconfig->get('cache_handler', ''),
		'caching'      => true,
		'cachebase'    => (int) $clientId === 1 ? JPATH_ADMINISTRATOR . '/cache' : $jconfig->get('cache_path', JPATH_SITE . '/cache')
	);
	$cache = JCache::getInstance('', $options);
	$cacheData  = $cache->getAll();
	$totalCache = 0;
	foreach ($cacheData as $cachefolder)
	{
		$totalCache += $cachefolder->size;
	}

	$returnData->siteInfo = array();
	$returnData->siteInfo["cacheusage"]  = number_format($totalCache / 1024 / 1024, 2, '.', '');

	return $returnData;
}

function clearSessions($requestObject, & $returnData)
{
	$app = JFactory::getApplication();

	// Need to load com_cache language files
	$lang = JFactory::getLanguage();
	$lang->load("com_login", JPATH_ADMINISTRATOR, null, false, true);

	$db = JFactory::getDbo();
	$query = $db->getQuery(true);
	$query->select("userid")
		->from('#__session')
		->where('userid > 0');
	$db->setQuery($query);

	$userids = $db->loadColumn();
	foreach ($userids as $userid)
	{
		$options = array(
			'clientid' => null,
		);

		$app->logout($userid, $options);
	}

	// Now delete all the anonymous sessions
	$query = $db->getQuery(true);
	$query->delete('#__session')
		->where('userid = 0');
	$db->setQuery($query);
	$db->execute();

	return $returnData;
}

function clearTmp($requestObject, & $returnData)
{
	// prepare for clean up - need to find the package file in order to remove it
	$config = JFactory::getConfig();
	$tempdir = $config->get('tmp_path');

	// remove package files from tmp folder
	$packagefiles = JFolder::files($tempdir, '\.zip');
	if (count($packagefiles))
	{
		foreach ($packagefiles as $packagefile)
		{
			@unlink($tempdir . "/" . $packagefile);
		}
	}

	try
	{
		$di = new RecursiveDirectoryIterator($tempdir, FilesystemIterator::SKIP_DOTS);
		$ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
		foreach ( $ri as $file ) {
			$rawfile = $file->getRealPath();
			if ($rawfile == $tempdir . DIRECTORY_SEPARATOR . "index.html")
			{
				continue;
			}
			$file->isDir() ? rmdir($file) : unlink($file);
		}
	}
	catch (Exception $e)
	{

	}

	$returnData->messages[]  = JText::_('COM_YOURSITES_TMP_PACKAGE_FILES_CLEANED');

	$returnData->siteInfo = array();
	$returnData->siteInfo["tmpusage"]  = number_format(0, 2, '.', '');

	return $returnData;
}

function cloneSite($requestObject, & $returnData)
{
	$diagnostics = true;

	// make sure we have enough time to handle slow tasks
	@ini_set("max_execution_time", 600);

	// Clean up first - removing temporary files and clearing cache
	clearCache($requestObject, $returnData);
	clearTmp($requestObject, $returnData);

	$returnData->messages[]  = "I am copying the site into " .$requestObject->prefix;

	if ($diagnostics)
	{
		$returnData->log['diagnostics'] = array();
		$returnData->log['diagnostics'][]  = "I am copying the site into " .$requestObject->prefix;
	}

	$returnData->cloned = 1;

	if (!empty ($requestObject->prefix))
	{
		JLoader::import('joomla.filesystem.file');
		JLoader::import('joomla.filesystem.folder');

		try
		{
			if ($diagnostics)
			{
				$returnData->log['diagnostics'][]  = "Starting to copy files";
			}

			// hidden in unix
			$success = copyr2(JPATH_SITE, JPATH_SITE . "/._" . $requestObject->prefix, "._" . $requestObject->prefix, $returnData, $requestObject->exclusions);

			if ($diagnostics)
			{
				$returnData->log['diagnostics'][]  = "Files copied successfully";
			}

		}
		catch (Exception $e)
		{

			$returnData->error  = 1;
			$returnData->cloned = 0;
			$returnData->errormessages[]  = "Files coping failed - threw exception";
			$returnData->errormessages[]  = $e->getMessage();

			if (count($returnData->messages))
			{
				$returnData->errormessages = array_merge($returnData->messages, $returnData->errormessages);
			}
			cleanUpClone($requestObject, $returnData);
			$returnData->errormessages[] = "Partial Clone site and data cleaned up";

			if ($diagnostics)
			{
				$returnData->log['diagnostics'][]  = "Files coping failed - threw exception";
			}
			return $returnData;
		}
		if (!$success)
		{
			$returnData->error  = 1;
			$returnData->cloned = 0;
			$returnData->errormessages[]  = 'COM_YOURSITES_UNABLE_TO_CLONE_FILES';
			if ($diagnostics)
			{
				$returnData->log['diagnostics'][]  = "Files coping failed";
			}
			return $returnData;
		}
	}
	else
	{
		$returnData->error  = 1;
		$returnData->cloned = 0;
		$returnData->errormessages[]  = 'COM_YOURSITES_MISSING_CLONE_PREFIX';
		return $returnData;
	}


	if (JFile::exists(JPATH_SITE . "/._" . $requestObject->prefix . "/configuration.php"))
	{
		$configfile = JPATH_SITE . "/._" . $requestObject->prefix . "/configuration.php";

		// Get the new FTP credentials.
		$ftp = JClientHelper::getCredentials('ftp', true);

		// Attempt to make the file writeable if using FTP.
		if (!$ftp['enabled'] && JPath::isOwner($configfile) && !JPath::setPermissions($configfile, '0644'))
		{
			$returnData->errormessages[]  = JText::_('COM_CONFIG_ERROR_CONFIGURATION_PHP_NOTWRITABLE');
			if ($diagnostics)
			{
				$returnData->log['diagnostics'][]  = JText::_('COM_CONFIG_ERROR_CONFIGURATION_PHP_NOTWRITABLE');
			}
		}

		// Attempt to write the configuration file as a PHP class named JConfig.
		$configuration = file_get_contents($configfile);

		// replace old paths with new
		// ToDo - figure out how to deal with this if log, tmp and cache files are one level up e.g. in private folder
		$configuration = str_replace(JPATH_SITE, JPATH_SITE . "/._" . $requestObject->prefix, $configuration);

		// ToDo disable emails using code from com_config application model ConfigModelApplication and ::writeconfigfile method adapted to our needs here
		$db = JFactory::getDbo();
		$oldPrefix = $db->getPrefix();
		$configuration = str_replace($oldPrefix, $requestObject->prefix . '_', $configuration);

		if (!JFile::write($configfile, $configuration))
		{
			$returnData->errormessages[]  = JText::_('COM_CONFIG_ERROR_WRITE_FAILED');
			if ($diagnostics)
			{
				$returnData->log['diagnostics'][]  = JText::_('COM_CONFIG_ERROR_WRITE_FAILED');
			}
		}

		// Attempt to make the file unwriteable if using FTP.
		if (!$ftp['enabled'] && JPath::isOwner($configfile) && !JPath::setPermissions($configfile, '0444'))
		{
			$returnData->messages[]  = JText::_('COM_CONFIG_ERROR_CONFIGURATION_PHP_NOTUNWRITABLE');
			if ($diagnostics)
			{
				$returnData->log['diagnostics'][]  = JText::_('COM_CONFIG_ERROR_CONFIGURATION_PHP_NOTUNWRITABLE');
			}
		}

		// Now copy the database tables
		// see https://stackoverflow.com/questions/3755591/mysql-copy-table-structure-including-foreign-keys

		$config = JFactory::getConfig();
		$dbname = $config->get('db', '');

		// MyISAM
		// $db->setQuery("SET FOREIGN_KEY_CHECKS = 0")

		// InnoDb
		try
		{
			$db->setQuery("SET unique_checks=0");
			$db->execute();
			$db->setQuery("SET foreign_key_checks=0");
			$db->execute();
		}
		catch (Exception $e)
		{

			$returnData->error = 1;
			$returnData->cloned = 0;
			$returnData->errormessages[] = "Unable to release DB table locks";

			if (count($returnData->messages))
			{
				$returnData->errormessages = array_merge($returnData->messages, $returnData->errormessages);
			}
			cleanUpClone($requestObject, $returnData);
			$returnData->errormessages[] = "Partial Clone site and data cleaned up";

			if ($diagnostics)
			{
				$returnData->log['diagnostics'][]  = "Unable to release DB table locks";
			}

			return $returnData;
		}

		$db->setQuery("SHOW FULL TABLES from `$dbname` WHERE `Tables_in_" .  $dbname ."` like '". $oldPrefix . "%' AND TABLE_TYPE LIKE 'VIEW'");
		// Make sure we can count on the order of the columns!
		$views = $db->loadColumn(0);

		$db->setQuery("SHOW FULL TABLES from `$dbname` WHERE `Tables_in_" .  $dbname ."` like '". $oldPrefix . "%' AND TABLE_TYPE NOT LIKE 'VIEW'");
		// Make sure we can count on the order of the columns!
		$tables = $db->loadColumn(0);

		// old version that needs $tablefield
		//$db->setQuery("SHOW TABLES from `$dbname` WHERE `Tables_in_" .  $dbname ."` like '". $oldPrefix . "%'");
		//$tables = $db->loadObjectList();

		// Copy the views last!
		$tablesAndViews = array_merge($tables, $views);

		foreach ($tablesAndViews as $table)
		{
			// See https://stackoverflow.com/questions/3755591/mysql-copy-table-structure-including-foreign-keys
			// and https://medium.com/@igorsantos07/a-not-so-small-rant-on-mysql-based-techniques-to-copy-database-structures-4333f8ff8384

			// Get the create statement - if not using FULL
			// $tablefield = "Tables_in_" . $dbname;
			// $retrieve = "ccccccfcnkihftdrdSHOW CREATE TABLE `" . $table->$tablefield ."`";
			if (in_array($table, $tables)) {
				$retrieve = "SHOW CREATE TABLE `" . $table . "`";
			}
			else
			{
				$retrieve = "SHOW CREATE VIEW `" . $table . "`";
			}
			$db->setQuery($retrieve);

			try {
				$create = $db->loadAssoc();
			}
			catch (Exception $e)
			{
				$x = 1;
			}

			if (in_array($table, $tables)) {
				// Isolate the "Create Table" index
				$create = $create['Create Table'];
			}
			else
			{
				// Isolate the "Create View" index
				$create = $create['Create View'];
			}

			// Replace old table name with new table name everywhere
			$create = preg_replace("/" . $oldPrefix . "/m", $requestObject->prefix . "_", $create);

			// You may need to rename foreign keys to prevent name re-use error.
			$fkcheck = "SELECT * FROM information_schema.key_column_usage WHERE REFERENCED_TABLE_NAME <> '' AND  TABLE_SCHEMA = '$dbname' AND TABLE_NAME = '$table'";
			$db->setQuery($fkcheck);
			//$db->setQuery($create);
			try
			{
				$fkeys = $db->loadObjectList();

				if ($fkeys && count($fkeys))
				{
					$returnData->log['diagnostics'][]  = "Foreign keys found " . count($fkeys);
					foreach ($fkeys as $fkey)
					{
						$create = preg_replace("#\b" . $fkey->CONSTRAINT_NAME . "\b#", $fkey->CONSTRAINT_NAME . "_" . $requestObject->prefix, $create);
						$returnData->log['diagnostics'][]  = "Replaced  " . $fkey->CONSTRAINT_NAME . " with " . $fkey->CONSTRAINT_NAME . "_" . $requestObject->prefix;
					}
				}
			}
			catch (Exception $e)
			{

				$returnData->error = 1;
				$returnData->cloned = 0;
				$returnData->errormessages[] = "Unable to check for foreign keys on table " . $table;

				if (count($returnData->messages))
				{
					$returnData->errormessages = array_merge($returnData->messages, $returnData->errormessages);
				}
				cleanUpClone($requestObject, $returnData);
				$returnData->errormessages[] = "Partial Clone site and data cleaned up";
				if ($diagnostics)
				{
					$returnData->log['diagnostics'][]  = "Unable to check for foreign keys on table " . $table;
					$returnData->log['diagnostics'][]  = $fkcheck;
				}

				return $returnData;
			}


			// Create the new table
			$db->setQuery($create);
			try {
				$create = $db->execute();
			}
			catch (Exception $e)
			{
				$x = 1;

				$returnData->error = 1;
				$returnData->cloned = 0;
				$returnData->errormessages[]  = "Create table/view failed :";
				$returnData->errormessages[]  = $create;
				$returnData->errormessages[]  = $e->getMessage();

				if (count($returnData->messages))
				{
					$returnData->errormessages = array_merge($returnData->messages, $returnData->errormessages);
				}
				cleanUpClone($requestObject, $returnData);
				$returnData->errormessages[] = "Partial Clone site and data cleaned up";
				if ($diagnostics)
				{
					$returnData->log['diagnostics'][]  = "Create table/view failed " . $create;
				}

				return $returnData;

			}
		}
		/*
				$returnData->messages[] = "Files Copied";
				$returnData->messages[] = "Config reset";
				$returnData->messages[] = "Tables Created";
				return $returnData;
		*/

		// TODO Lock the tables and/or shut down Joomla temporarily
		// TODO DISABLE SENDING EMAILS

		// See https://stackoverflow.com/questions/2943400/fastest-way-to-copy-a-table-in-mysql

		// Now copy the data with dropping foreign key checks and then reinstating them

		foreach ($tables as $table)
		{

			try
			{
				//$db->setQuery("INSERT INTO " . str_replace($oldPrefix , $requestObject->prefix ."_", $table->$tablefield)
				//. " SELECT * FROM " . $table->$tablefield);
				$db->setQuery("INSERT INTO " . str_replace($oldPrefix , $requestObject->prefix ."_", $table)
					. " SELECT * FROM " . $table);

				$db->execute();
			}
			catch (Exception $e)
			{
				if ($diagnostics)
				{
					$returnData->log['diagnostics'][]  = "Insert INTO/SELECT FROM failed : " . (string) $db->getQuery();
					$returnData->log['diagnostics'][]  = "Exception was  : " . $e->getMessage();
				}

				// I need to look at the columns names to exclude generated columns
				//$sql = "SHOW COLUMNS FROM " . str_replace($oldPrefix , $requestObject->prefix ."_", $table->$tablefield);
				$sql = "SHOW COLUMNS FROM " . str_replace($oldPrefix , $requestObject->prefix ."_", $table);
				$db->setQuery($sql);
				$cols = @$db->loadObjectList("Field");

				$insertcols = array();

				foreach ($cols as $col)
				{
					// See  https://stackoverflow.com/questions/55525248/alter-mysqls-show-colums-extra-value
					if (empty($col->Extra) || (strpos($col->Extra, "VIRTUAL" ) !== 0 && strpos($col->Extra, "DEFAULT") !== 0))
					{
						$insertcols[] = $col->Field;
					}
				}
				//$db->setQuery("INSERT INTO " . str_replace($oldPrefix , $requestObject->prefix ."_", $table->$tablefield)
				//. " (" . implode($insertcols, ",") . ")"
				//. " SELECT " . implode($insertcols, ",") . " FROM " . $table->$tablefield);
				$db->setQuery("INSERT INTO " . str_replace($oldPrefix , $requestObject->prefix ."_", $table)
					. " (" . implode($insertcols, ",") . ")"
					. " SELECT " . implode($insertcols, ",") . " FROM " . $table);
				try {
					if ($db->execute())
					{
						if ($diagnostics)
						{
							$returnData->log['diagnostics'][] = "Fall back to Insert Into with specific columns succeeded ";
						}
					}
				}
				catch (Exception $e)
				{

					$x = 1;

					if ($diagnostics)
					{
						$returnData->log['diagnostics'][] = "Insert Into with columns Failed " . (string) $db->getQuery();
					}
					$returnData->error = 1;
					$returnData->cloned = 0;
					$returnData->errormessages[] = "Failed to insert data for table " . $table;
					$returnData->errormessages[] = $e->getMessage();

					if (count($returnData->messages))
					{
						$returnData->errormessages = array_merge($returnData->messages, $returnData->errormessages);
					}

					cleanUpClone($requestObject, $returnData);
					$returnData->errormessages[] = "Partial Clone site and data cleaned up";

					return $returnData;

				}
			}

			// After the insert we re-implement the triggers
			// Like is based on table name not the name of the trigger! See https://dev.mysql.com/doc/refman/5.7/en/show-triggers.html
			$db->setQuery("SHOW TRIGGERS FROM `$dbname` LIKE '" . $table . "'");
			try {
				$triggers = $db->loadObjectList();
				if (count($triggers))
				{
					foreach ($triggers as $trigger) {
						$sql = 'CREATE TRIGGER '
							. $trigger->Trigger . ' '
							. $trigger->Timing . ' '
							. $trigger->Event . ' '
							. ' ON ' . $trigger->Table . ' FOR EACH ROW '
							. $trigger->Statement;
						$sql = str_replace($oldPrefix , $requestObject->prefix ."_", $sql);
						$db->setQuery($sql);
						$db->execute();
					}
				}
			}
			catch (Exception $e)
			{
				if ($diagnostics)
				{
					$returnData->log['diagnostics'][]  = "Create database triggers failed  : " . $create;
				}

				$x = 1;
			}
		}

		$db->setQuery("SHOW FUNCTION STATUS WHERE Name LIKE '" . $oldPrefix. "%' AND dB='" . $dbname . "'");
		$functions = $db->loadObjectList();

		foreach ($functions as $function) {
			$db->setQuery("SHOW CREATE FUNCTION $function->Name");
			try {
				$create = $db->loadAssoc();
			}
			catch (Exception $e)
			{
				$x = 1;
				if ($diagnostics)
				{
					$returnData->log['diagnostics'][]  = "Failed to get create function declaration : " . $create;
				}
			}

			// Isolate the "Create Function" index
			$create = $create['Create Function'];

			// Replace old table name with new table name everywhere
			$create = preg_replace("/" . $oldPrefix . "/m", $requestObject->prefix . "_", $create);

			// You may need to rename foreign keys to prevent name re-use error.
			// See http://stackoverflow.com/questions/12623651/
			$create = preg_replace("/FK_/", "FK_" .$requestObject->prefix ."_" , $create);

			// Create the new table
			$db->setQuery($create);
			try {
				$create = $db->execute();
			}
			catch (Exception $e)
			{
				if ($diagnostics)
				{
					$returnData->log['diagnostics'][]  = "Failed to create functions : " . $create;
				}

			}
		}

		$db->setQuery("SHOW PROCEDURE STATUS WHERE Name LIKE '" . $oldPrefix. "%' AND dB='" . $dbname . "'");
		$procedures = $db->loadObjectList();

		foreach ($procedures as $procedure) {
			$db->setQuery("SHOW CREATE PROCEDURE $procedure->Name");
			try {
				$create = $db->loadAssoc();
			}
			catch (Exception $e)
			{
				$x = 1;
				if ($diagnostics)
				{
					$returnData->log['diagnostics'][]  = "Failed to get create procedure declaration : " . $create;
				}
			}

			// Isolate the "Create Function" index
			$create = $create['Create Procedure'];

			// Replace old table name with new table name everywhere
			$create = preg_replace("/" . $oldPrefix . "/m", $requestObject->prefix . "_", $create);

			// You may need to rename foreign keys to prevent name re-use error.
			// See http://stackoverflow.com/questions/12623651/
			$create = preg_replace("/FK_/", "FK_" .$requestObject->prefix ."_" , $create);

			// Create the new table
			$db->setQuery($create);
			try {
				$create = $db->execute();
			}
			catch (Exception $e)
			{
				$x = 1;
				if ($diagnostics)
				{
					$returnData->log['diagnostics'][]  = "Failed to create procedure : " . $create;
				}
			}
		}


		// MyISAM
		// $db->setQuery("SET FOREIGN_KEY_CHECKS = 1");
		// InnoDb
		try {
			$db->setQuery("SET unique_checks=1");
			$db->execute();
			$db->setQuery("SET foreign_key_checks=1");
			$db->execute();
		}
		catch (Exception $e)
		{
			$returnData->error = 1;
			$returnData->cloned = 0;
			$returnData->errormessages[] = "Unable to reset DB table locks";
			if ($diagnostics)
			{
				$returnData->log['diagnostics'][]  = "Unable to reset DB table locks";
			}

			return $returnData;
		}


		// Todo update the YourSites Client plugin on the clone site


	}
	else
	{
		$returnData->cloned = 0;
		$returnData->error = 1;
		$returnData->errormessages[]  = JText::_('COM_YOURSITES_COULD_NOT_CLONE_SITE_CONFIGURATION_FILE_MISSING');
		if ($diagnostics)
		{
			$returnData->log['diagnostics'][]  = JText::_('COM_YOURSITES_COULD_NOT_CLONE_SITE_CONFIGURATION_FILE_MISSING');
		}
		return $returnData;
	}
	return $returnData;
}

function cleanUpClone($requestObject, & $returnData)
{
	return deleteSite($requestObject, $returnData, true);
}

function deleteSite($requestObject, & $returnData, $specificClone = false)
{
	// Only delete cloned sites
	$config = new JConfig();
	$db_prefix = trim($config->dbprefix, "_");
	if ($specificClone)
	{
		$db_prefix = $requestObject->prefix;
	}

	$basepath = JPATH_SITE;
	if ($specificClone)
	{
		$basepath .= '/._' . $db_prefix;
	}

	if (strpos($db_prefix, 'ysts_') !== 0 || strpos($basepath, '._' . $db_prefix) === false) {
		$returnData->error = 1;
		$returnData->errormessages[] = "COM_YOURSITES_DELETE_SITE_SITE_NOT_A_CLONE";
		$returnData->errormessages[] = $db_prefix;
		$returnData->errormessages[] = $basepath;
		$returnData->errormessages[] = strpos($basepath, '._' . $db_prefix);

		return $returnData;
	}

	JLoader::import('joomla.filesystem.file');
	JLoader::import('joomla.filesystem.folder');

	// ToDo disable emails using code from com_config application model ConfigModelApplication and ::writeconfigfile method adapted to our needs here
	$db = JFactory::getDbo();
	$oldPrefix = $db->getPrefix();
	if ($specificClone)
	{
		$oldPrefix = $requestObject->prefix;
	}

	$config = JFactory::getConfig();
	$dbname = $config->get('db', '');

	//$db->setQuery("SHOW TABLES from `$dbname` WHERE `Tables_in_" .  $dbname ."` like '". $oldPrefix . "%'");
	$db->setQuery("SHOW FULL TABLES from `$dbname` WHERE `Tables_in_" .  $dbname ."` like '". $oldPrefix . "%' AND TABLE_TYPE NOT LIKE 'VIEW'");
	// Make sure we can count on the order of the columns!
	$tables = $db->loadColumn(0);

	// InnoDb
	try
	{
		$db->setQuery("SET unique_checks=0");
		$db->execute();
		$db->setQuery("SET foreign_key_checks=0");
		$db->execute();
	}

	catch (Exception $e)
	{
		$returnData->error = 1;
		$returnData->deleted = 0;
		$returnData->errormessages[] = "Unable to release DB table locks";
		$returnData->log[] = $e->getMessage();
		return $returnData;
	}

	$db->setQuery("SHOW FULL TABLES from `$dbname` WHERE `Tables_in_" .  $dbname ."` like '". $oldPrefix . "%' AND TABLE_TYPE LIKE 'VIEW'");
	// Make sure we can count on the order of the columns!
	$views = $db->loadColumn(0);

	foreach ($views as $view)
	{

		// Drop the views
		$dropSql = "DROP VIEW IF EXISTS `" . $view ."`";
		$db->setQuery($dropSql);

		$deleted = $db->execute();
	}

	$db->setQuery("SHOW FUNCTION STATUS WHERE Name LIKE '" . $oldPrefix. "%' AND dB='" . $dbname . "'");
	$functions = $db->loadObjectList();

	foreach ($functions as $function) {
		$db->setQuery("DROP FUNCTION $function->Name");
		$db->execute();
	}

	$db->setQuery("SHOW PROCEDURE STATUS WHERE Name LIKE '" . $oldPrefix. "%' AND dB='" . $dbname . "'");
	$procedures = $db->loadObjectList();

	foreach ($procedures as $procedure) {
		$db->setQuery("DROP PROCEDURE $procedure->Name");
		$db->execute();
	}

	foreach ($tables as $table)
	{

		// Drop the tables
		$dropSql = "DROP TABLE IF EXISTS `" . $table ."`";
		$db->setQuery($dropSql);

		$deleted = $db->execute();
	}

	// MyISAM
	// $db->setQuery("SET FOREIGN_KEY_CHECKS = 1");
	// InnoDb
	try {
		$db->setQuery("SET unique_checks=1");
		$db->execute();
		$db->setQuery("SET foreign_key_checks=1");
		$db->execute();
	}
	catch (Exception $e)
	{
		$returnData->error = 1;
		$returnData->cloned = 0;
		$returnData->errormessages[] = "Unable to reset DB table locks";
		return $returnData;
	}

	try
	{

		// hidden in unix
		$success = deleter2($basepath, $returnData);
		// finally remove the root folder
		$FTPOptions = JClientHelper::getCredentials('ftp');

		// If we're using ftp
		if ($FTPOptions['enabled'] == 1 ) {
			// Connect the FTP client
			$ftp = JFtpClient::getInstance($FTPOptions['host'], $FTPOptions['port'], array(), $FTPOptions['user'], $FTPOptions['pass']);
			// Now delete the folder
			if (!$ftp->delete($basepath))
			{
				throw new \RuntimeException('Delete folder failed ' . $basepath, -1);
			}
		}
		else {
			@rmdir($basepath);
		}

	}
	catch (Exception $e)
	{
		$returnData->error  = 1;
		$returnData->deleted = 0;
		$returnData->errormessages[]  = $e->getMessage();
		return $returnData;
	}
	if (!$success)
	{
		$returnData->error  = 1;
		$returnData->deleted = 0;
		$returnData->errormessages[]  = 'COM_YOURSITES_UNABLE_TO_DELETE_FILES';
		return $returnData;
	}

	$returnData->deleted = 1;

	return $returnData;
}


function otherChecks($requestObject, & $returnData)
{
	include_once "sitechecks.php";
	$returnData->checkResults = array();
	foreach ($requestObject->checks as $check)
	{
		$checkReturnData = new stdClass();
		$checkReturnData->error = 0;
		$checkReturnData->warning = 0;
		$checkReturnData->messages = array();
		$checkReturnData->log = array();
		$checkReturnData->timing = array();
		$checkReturnData->errormessages = array();
		$checkReturnData->checkinfo = array();

		YstsSiteChecks::$check($checkReturnData, $requestObject);

		// special case for multi-test
		if (($check == "customconfig" || $check == "extrachecks"  || $check == "customfiles") && is_array($checkReturnData->customresults))
		{
			foreach ($checkReturnData->customresults as $customresultidx => $customresult)
			{
				$multiCheckData = clone($checkReturnData);
				unset($multiCheckData->customresults);
				$multiCheckData->warning = !$customresult->valid ? 1 : 0;
				$multiCheckData->checkinfo = $customresult->checkinfo;
				$multiCheckData->messages = $customresult->messages;
				$returnData->checkResults[$customresultidx] = $multiCheckData;
			}
		}
		else
		{
			$returnData->checkResults[$check] = $checkReturnData;
		}

	}

	return $returnData;
}

function rebuildUpdateSites($requestObject, & $returnData)
{
	// clear the Extensions cache
	purgeCache($requestObject, $returnData);

	$db = JFactory::getDbo();

	// Get the update sites model so we can rebuild
	$model = JModelLegacy::getInstance("Updatesites", "InstallerModel");
	// Rebuild the update sites.
	// $model->rebuild();

	$lang = JFactory::getLanguage();
	// Need to load com_installer language files
	$lang->load("com_installer", JPATH_ADMINISTRATOR, null, false, true);

	// code replicated from model since the model has ACL check built in!
	// some tweaks
	$db  = JFactory::getDbo();
	$app = JFactory::getApplication();

	// Check if Joomla Extension plugin is enabled.
	if (!JPluginHelper::isEnabled('extension', 'joomla'))
	{
		$query = $db->getQuery(true)
			->select($db->quoteName('extension_id'))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
			->where($db->quoteName('element') . ' = ' . $db->quote('joomla'))
			->where($db->quoteName('folder') . ' = ' . $db->quote('extension'));
		$db->setQuery($query);

		$pluginId = (int) $db->loadResult();

		$link = JRoute::_('index.php?option=com_plugins&task=plugin.edit&extension_id=' . $pluginId);

		$returnData->error = 1;
		$returnData->errormessages[] =JText::sprintf('COM_INSTALLER_MSG_UPDATESITES_REBUILD_EXTENSION_PLUGIN_NOT_ENABLED', $link);
		return $returnData;
	}

	$clients               = array(JPATH_SITE, JPATH_ADMINISTRATOR);
	$extensionGroupFolders = array('components', 'modules', 'plugins', 'templates', 'language', 'manifests');

	$pathsToSearch = array();

	// Identifies which folders to search for manifest files.
	foreach ($clients as $clientPath)
	{
		foreach ($extensionGroupFolders as $extensionGroupFolderName)
		{
			// Components, modules, plugins, templates, languages and manifest (files, libraries, etc)
			if ($extensionGroupFolderName != 'plugins')
			{
				foreach (glob($clientPath . '/' . $extensionGroupFolderName . '/*', GLOB_NOSORT | GLOB_ONLYDIR) as $extensionFolderPath)
				{
					$pathsToSearch[] = $extensionFolderPath;
				}
			}

			// Plugins (another directory level is needed)
			else
			{
				foreach (glob($clientPath . '/' . $extensionGroupFolderName . '/*', GLOB_NOSORT | GLOB_ONLYDIR) as $pluginGroupFolderPath)
				{
					foreach (glob($pluginGroupFolderPath . '/*', GLOB_NOSORT | GLOB_ONLYDIR) as $extensionFolderPath)
					{
						$pathsToSearch[] = $extensionFolderPath;
					}
				}
			}
		}
	}

	// Gets Joomla core update sites Ids.
	$joomlaUpdateSitesIds = implode(', ', getJoomlaUpdateSitesIds(0));

	// Delete from all tables (except joomla core update sites).
	$query = $db->getQuery(true)
		->delete($db->quoteName('#__update_sites'))
		->where($db->quoteName('update_site_id') . ' NOT IN (' . $joomlaUpdateSitesIds . ')');
	$db->setQuery($query);
	$db->execute();

	$query = $db->getQuery(true)
		->delete($db->quoteName('#__update_sites_extensions'))
		->where($db->quoteName('update_site_id') . ' NOT IN (' . $joomlaUpdateSitesIds . ')');
	$db->setQuery($query);
	$db->execute();

	$query = $db->getQuery(true)
		->delete($db->quoteName('#__updates'))
		->where($db->quoteName('update_site_id') . ' NOT IN (' . $joomlaUpdateSitesIds . ')');
	$db->setQuery($query);
	$db->execute();

	$count = 0;

	// Gets Joomla core extension Ids.
	$joomlaCoreExtensionIds = implode(', ', getJoomlaUpdateSitesIds(1));

	// Search for updateservers in manifest files inside the folders to search.
	foreach ($pathsToSearch as $extensionFolderPath)
	{
		$tmpInstaller = new JInstaller;

		$tmpInstaller->setPath('source', $extensionFolderPath);

		// Main folder manifests (higher priority)
		$parentXmlfiles = JFolder::files($tmpInstaller->getPath('source'), '.xml$', false, true);

		// Search for children manifests (lower priority)
		$allXmlFiles    = JFolder::files($tmpInstaller->getPath('source'), '.xml$', 1, true);

		// Create an unique array of files ordered by priority
		$xmlfiles = array_unique(array_merge($parentXmlfiles, $allXmlFiles));

		if (!empty($xmlfiles))
		{
			foreach ($xmlfiles as $file)
			{
				// Is it a valid Joomla installation manifest file?
				$manifest = $tmpInstaller->isManifest($file);

				if (!is_null($manifest))
				{
					// Search if the extension exists in the extensions table. Excluding joomla core extensions (id < 10000) and discovered extensions.
					$query = $db->getQuery(true)
						->select($db->quoteName('extension_id'))
						->from($db->quoteName('#__extensions'))
						->where('('
							. $db->quoteName('name') . ' = ' . $db->quote($manifest->name)
							. ' OR ' . $db->quoteName('name') . ' = ' . $db->quote($manifest->packagename)
							. ')' )
						->where($db->quoteName('type') . ' = ' . $db->quote($manifest['type']))
						->where($db->quoteName('extension_id') . ' NOT IN (' . $joomlaCoreExtensionIds . ')')
						->where($db->quoteName('state') . ' != -1');
					$db->setQuery($query);

					$eid = (int) $db->loadResult();

					if ($eid && $manifest->updateservers)
					{
						// Set the manifest object and path
						$tmpInstaller->manifest = $manifest;
						$tmpInstaller->setPath('manifest', $file);

						// Load the extension plugin (if not loaded yet).
						JPluginHelper::importPlugin('extension', 'joomla');

						// Fire the onExtensionAfterUpdate
						JFactory::getApplication()->triggerEvent('onExtensionAfterUpdate', array('installer' => $tmpInstaller, 'eid' => $eid));

						$count++;
					}
				}
			}
		}
	}

	if ($count > 0)
	{
		$returnData->messages[]  = JText::_('COM_INSTALLER_MSG_UPDATESITES_REBUILD_SUCCESS');
	}
	else
	{
		$returnData->messages[]  = JText::_('COM_INSTALLER_MSG_UPDATESITES_REBUILD_MESSAGE');
	}
	// end of replicated code

	$returnData->result = "something";

	return $returnData;
}

// from InstallerModelUpdate
function preparePreUpdate($update, $instance)
{
	jimport('joomla.filesystem.file');
	switch ($instance->type)
	{
		// Components could have a helper which adds additional data
		case 'component':
			$ename = str_replace('com_', '', $instance->element);
			$fname = $ename . '.php';
			$cname = ucfirst($ename) . 'Helper';

			$path = JPATH_ADMINISTRATOR . '/components/' . $instance->element . '/helpers/' . $fname;

			if (JFile::exists($path))
			{
				require_once $path;

				if (class_exists($cname) && is_callable(array($cname, 'prepareUpdate')))
				{
					call_user_func_array(array($cname, 'prepareUpdate'), array(&$update, &$instance));
				}
			}

			break;

		// Modules could have a helper which adds additional data
		case 'module':
			$cname = str_replace('_', '', $instance->element) . 'Helper';
			$path = ($instance->client_id ? JPATH_ADMINISTRATOR : JPATH_SITE) . '/modules/' . $instance->element . '/helper.php';

			if (JFile::exists($path))
			{
				require_once $path;

				if (class_exists($cname) && is_callable(array($cname, 'prepareUpdate')))
				{
					call_user_func_array(array($cname, 'prepareUpdate'), array(&$update, &$instance));
				}
			}

			break;

		// If we have a plugin, we can use the plugin trigger "onInstallerBeforePackageDownload"
		// But we should make sure, that our plugin is loaded, so we don't need a second "installer" plugin
		case 'plugin':
			$cname = str_replace('plg_', '', $instance->element);
			JPluginHelper::importPlugin($instance->folder, $cname);
			break;
	}
}

function installExtension($requestObject, &$returnData)
{
	// Some extensions assume they are being installed from within com_installer so we must make them think that they are
	$option = "com_installer";
	if (!defined('JPATH_COMPONENT'))
	{
		define('JPATH_COMPONENT', JPATH_BASE . '/components/' . $option);
	}

	if (!defined('JPATH_COMPONENT_SITE'))
	{
		define('JPATH_COMPONENT_SITE', JPATH_SITE . '/components/' . $option);
	}

	if (!defined('JPATH_COMPONENT_ADMINISTRATOR'))
	{
		define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/' . $option);
	}

	// TODO data integrity check
	$fileurl = isset($requestObject->fileurl) ? $requestObject->fileurl : false;
	$files = JFactory::getApplication()->input->files;
	$uploadedFile = $files->get("install_package", false, "raw");

	$installmodel = JModelLegacy::getInstance("Install", "InstallerModel");
	if (!$installmodel)
	{
		JLoader::import('Model.InstallModel', JPATH_ADMINISTRATOR . '/components/com_installer');
		$installmodel = new Joomla\Component\Installer\Administrator\Model\InstallModel();
	}

	// I need this to force the state population - otherwise my setState gets ignored!
	$temp = $installmodel->getState('list.limit');

	// TODO - spin our own version of this!
	// Fool Joomla
	if ($fileurl)
	{
		JFactory::getApplication()->input->set('installtype', "url");
		JFactory::getApplication()->input->set('install_url', $fileurl);
	}
	else {
		JFactory::getApplication()->input->set('installtype', "upload");
	}

	// set the option value for the action logging
	JFactory::getApplication()->input->set('option', 'com_installer');
	// spoof the username for action logs
	$user = JFactory::getUser();
	$user->username = "YourSites";

	// Install sets state and enqueues messages
	$installmodel->install();

	$user->username = "";

	/*
	  $this->setState('name', $installer->get('name'));
	  $this->setState('result', $result);
	  $app->setUserState('com_installer.message', $installer->message);
	  $app->setUserState('com_installer.extension_message', $installer->get('extension_message'));
	  $app->setUserState('com_installer.redirect_url', $installer->get('redirect_url'));

	 */
	$res = $installmodel->getState('result');

	$app = JFactory::getApplication();

	$messageQueue     = $app->getMessageQueue();
	$installermessage = $app->getUserState('com_installer.message', "");
	$extensionmessage = $app->getUserState('com_installer.extension_message', "");
	$redirect_url     = $app->getUserState('com_installer.redirect_url', "");

	// Some extensions use bad URLs - correct these here
	$extensionmessage = str_replace('//administrator', '/administrator', $extensionmessage);
	$redirect_url = str_replace('//administrator', '/administrator', $redirect_url);

	$returnData->redirect_url = $redirect_url;
	if (!empty($returnData->redirect_url))
	{
		$returnData->warning = 1;
	}

	if ($res)
	{
		// Get Message Queue
		if (count($messageQueue))
		{
			foreach ($messageQueue as $msg)
			{
				if ($msg["message"] == "COM_INSTALLER_INSTALL_SUCCESS")
				{
					continue;
				}
				if (strpos($msg["message"], "<script") !== false)
				{
					$returnData->warning = 1;
					$returnData->hasscript = 1;
				}
				$returnData->messages[] = '<div class="installermessage">' . preg_replace('#<script(.*?)>(.*?)</script>#is', '', $msg["message"]) . '</div>';
			}
		}

		// This is done by the model update - check though!
		//$model->delete($items[0]->update_id);
		$returnData->result = "installed";
		if (!empty($installermessage))
		{
			if (strpos($installermessage, "<script") !== false)
			{
				$returnData->warning = 1;
				$returnData->hasscript = 1;
			}
			$returnData->messages[] = '<div class="installermessage">' . preg_replace('#<script(.*?)>(.*?)</script>#is', '', $installermessage) . '</div>';
		}
		if (!empty($extensionmessage))
		{
			if (strpos($extensionmessage, "<script") !== false)
			{
				$returnData->warning = 1;
				$returnData->hasscript = 1;
			}
			$returnData->messages[] = '<div class="extensionmessage">' . preg_replace('#<script(.*?)>(.*?)</script>#is', '', $extensionmessage) . '</div>';
		}
	}
	else
	{
		$returnData->error = 1;
		$returnData->result = "install failed";

		// Get Message Queue
		if (count($messageQueue))
		{
			foreach ($messageQueue as $msg)
			{
				if ($msg["message"] == "COM_INSTALLER_INSTALL_ERROR")
				{
					continue;
				}

				if (strpos($msg["message"], "<script") !== false)
				{
					$returnData->hasscript = 1;
				}
				$returnData->errormessages[] = '<div class="installermessage">' . preg_replace('#<script(.*?)>(.*?)</script>#is', '', $msg["message"]) . '</div>';
			}
		}

		if (!empty($installermessage))
		{
			$returnData->errormessages[] = '<div class="installermessage">' . preg_replace('#<script(.*?)>(.*?)</script>#is', '', $installermessage) . '</div>';
		}
		if (!empty($extensionmessage))
		{
			$returnData->errormessages[] = '<div class="extensionmessage">' . preg_replace('#<script(.*?)>(.*?)</script>#is', '', $extensionmessage) . '</div>';
		}
	}

	return $returnData;

}

function directLogin($requestObject, & $returnData, $redirect = true)
{
	// Uses http://user:password@www.domain.com/administrator for htaccess password

	$plugin = JPluginHelper::getPlugin("system" , "yoursites");
	if ($plugin)
	{
		$params      = json_decode($plugin->params);
		if (isset($params->allowdirectlogin) && !$params->allowdirectlogin)
		{
			return false;
		}
		// Overrule direct login user if its set
		if (isset($params->dluser) && $params->dluser > 0)
		{
			$adminuser = $params->dluser;
		}
	}
	if (!isset($adminuser) || $adminuser == 0)
	{
		$adminuser = $requestObject->userid;
		if (intval($adminuser) <= 0)
		{
			$adminuser = $requestObject->username;
		}
	}

	if ($adminuser === 0)
	{
		return false;
	}

	$session = JFactory::getSession();
	$user = JFactory::getUser();
	if ($user->id == 0)
	{
		// delete the session from the database straight away so that it can't be resused at all!
		// BELTS AND BRACES
		$db = JFactory::getDbo();
		$sessid = $session->getId();
		$db->setQuery(
			'DELETE  FROM ' . $db->quoteName('#__session') .
			' WHERE ' . $db->quoteName('session_id') . ' = ' . $db->quote($sessid));
		$exists = $db->execute();
	}

	// DANGEROUS - it logs in this user so we need suitable security!
	$adminuser = JUser::getInstance($adminuser);

	if (!$adminuser)
	{
		$returnData->result = "Invalid User";
		return false;
	}

	// Do we require a 2 factor authentication code
	JLoader::registerAlias('JAuthenticationHelper', '\\Joomla\\CMS\\Helper\\AuthenticationHelper', '5.0');
	$twofactormethods = JAuthenticationHelper::getTwoFactorMethods();

	if (isset($params->check2factor) && $params->check2factor == 1 && count($twofactormethods) >= 1)
	{
		$twofa = "";
		if (isset($requestObject->twofa) && !empty($requestObject->twofa))
		{
			$twofa = $requestObject->twofa;
		}

		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_users/models', 'UsersModel');

		/** @var UsersModelUser $model */
		$model = JModelLegacy::getInstance('User', 'UsersModel', array('ignore_request' => true));

		$otpConfig = $model->getOtpConfig($adminuser->id);

		// Check if the user has enabled two factor authentication
		if (!empty($otpConfig->method) && ($otpConfig->method !== 'none'))
		{

			// Try to validate the OTP
			JPluginHelper::importPlugin('twofactorauth');

			$credentials = array('secretkey' => @base64_decode($twofa));
			$options     = array('otp_config' => $otpConfig);

			$otpAuthReplies = JFactory::getApplication()->triggerEvent('onUserTwofactorAuthenticate', array($credentials, $options));

			$check = false;

			/*
			 * This looks like noob code but DO NOT TOUCH IT and do not convert
			 * to in_array(). During testing in_array() inexplicably returned
			 * null when the OTEP begins with a zero! o_O
			 */
			if (!empty($otpAuthReplies))
			{
				foreach ($otpAuthReplies as $authReply)
				{
					$check = $check || $authReply;
				}
			}

			if (!$check)
			{
				return false;
			}

		}
	}

	// Mark the user as logged in
	$adminuser->guest = 0;

	// Grab the current session ID
	$oldSessionId = $session->getId();

	// Fork the session
	$session->fork();

	$session->set('user', $adminuser);

	// Ensure the new session's metadata is written to the database
	JFactory::getApplication()->checkSession();

	// Purge the old session
	$db = JFactory::getDbo();
	$query = $db->getQuery(true)
		->delete('#__session')
		->where($db->quoteName('session_id') . ' = ' . $db->quote($oldSessionId));

	try
	{
		$db->setQuery($query)->execute();
	}
	catch (RuntimeException $e)
	{
		// The old session is already invalidated, don't let this block logging in
	}

	// Hit the user last visit field
	$adminuser->setLastVisit();

	// Add "user state" cookie used for reverse caching proxies like Varnish, Nginx etc.
	$conf = JFactory::getConfig();
	$cookie_domain = $conf->get('cookie_domain', '');
	$cookie_path = $conf->get('cookie_path', '/');

	if (JFactory::getApplication()->isClient('site'))
	{
		JFactory::getApplication()->input->cookie->set("joomla_user_state", "logged_in", 0, $cookie_path, $cookie_domain, 0);
	}

	$user = JFactory::getUser();

	if ($redirect)
	{
		$redirectURL = isset($requestObject->redirectURL) ? base64_decode($requestObject->redirectURL) : "index.php";
		JFactory::getApplication()->redirect($redirectURL);
		exit(0);
	}
}

/**
// from InstallerModelUpdate
 * Handles the actual update installation.
 *
 * @param   JUpdate  $update  An update definition
 *
 * @return  boolean   Result of install
 *
 * @since   1.6
 */
function doInstallExtension($update, &$returnData)
{
	$app = JFactory::getApplication();

	if (!isset($update->get('downloadurl')->_data))
	{
		$app->enqueueMessage(JText::_('COM_INSTALLER_INVALID_EXTENSION_UPDATE'), "warning");

		return false;
	}

	$url = $update->downloadurl->_data;
	$url = trim($url);

	$sources = $update->get('downloadSources', array());

	if ($extra_query = $update->get('extra_query'))
	{
		$url .= (strpos($url, '?') === false) ? '?' : '&amp;';
		$url .= $extra_query;
	}

	$mirror = 0;

	while (!($p_file = JInstallerHelper::downloadPackage($url)) && isset($sources[$mirror]))
	{
		$name = $sources[$mirror];
		$url = $name->url;

		if ($extra_query)
		{
			$url .= (strpos($url, '?') === false) ? '?' : '&amp;';
			$url .= $extra_query;
		}

		$mirror++;
	}

	// Was the package downloaded?
	if (!$p_file)
	{
		$app->enqueueMessage(JText::sprintf('COM_INSTALLER_PACKAGE_DOWNLOAD_FAILED', $url), "warning");

		return false;
	}

	$config = JFactory::getConfig();
	$tmp_dest = $config->get('tmp_path');

	// Sometimes file names are invalid
	$extensions = array(".zip", ".targz", ".tar.gz");
	foreach ($extensions as $extension)
	{
		$pos = strrpos($p_file, $extension);
		if ($pos !== false && $pos != (strlen($p_file) - strlen($extension)))
		{
			JFile::move($tmp_dest . '/' . $p_file, $tmp_dest . '/' . $p_file . $extension);
			$p_file .= $extension;
		}
	}
	if (strpos($p_file, "?") !== false || strpos($p_file, "&") !== false)
	{

		$tmpFileName = 'test.zip';

		JFile::move($tmp_dest . '/' . $p_file, $tmp_dest . '/' . $tmpFileName);

		$p_file = $tmpFileName;
	}

	// Unpack the downloaded package file
	$package = JInstallerHelper::unpack($tmp_dest . '/' . $p_file);

	// Get an installer instance as long as package unpacked properly
	$installer = JInstaller::getInstance();
	if ($package)
	{
		$update->set('type', $package['type']);
	}

	// Install the package
	if (!$package  || !$installer->update($package['dir']))
	{
		// There was an error updating the package
		if (isset($package['type']))
		{
			$msg = JText::sprintf('COM_INSTALLER_MSG_UPDATE_ERROR', JText::_('COM_INSTALLER_TYPE_TYPE_' . strtoupper($package['type'])));
		}
		else
		{
			$returnData->errormessages[] = JText::_('COM_YOURSITES_UNABLE_TO_UNPACK_UPDATE_PACKAGE');
			$returnData->errormessages[] = $tmp_dest . '/' . $p_file;
			$msg = 'Unable to unpack update package';
		}

		$result = false;
	}
	else
	{
		// Package updated successfully
		$msg = JText::sprintf('COM_INSTALLER_MSG_UPDATE_SUCCESS', JText::_('COM_INSTALLER_TYPE_TYPE_' . strtoupper($package['type'])));
		$result = true;
	}

	// Quick change
	//$this->type = $package['type'];
	// Set some model state values
	$app->enqueueMessage($msg);

	// TODO: Reconfigure this code when you have more battery life left
	//$this->setState('name', $installer->get('name'));
	//$this->setState('result', $result);

	$app->setUserState('com_installer.message', $installer->message);
	$app->setUserState('com_installer.extension_message', $installer->get('extension_message'));

	// Cleanup the install files
	if (!is_file($package['packagefile']))
	{
		$config = JFactory::getConfig();
		$package['packagefile'] = $config->get('tmp_path') . '/' . $package['packagefile'];
	}

	JInstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);

	return $result;
}

function getBackupToken($requestObject, $returnData)
{
	try
	{
		$akeebaParams = JComponentHelper::getParams("com_akeeba");
		if ($akeebaParams && $akeebaParams->get("frontend_secret_word", ""))
		{
			$returnData->error      = 0;
			$returnData->result = 'COM_YOURSITES_AKEEBA_TOKEN_FOUND';
			$returnData->messages[] = 'COM_YOURSITES_AKEEBA_TOKEN_FOUND';

			// If the Factory is not already loaded we have to load the
			if (!class_exists('Akeeba\Engine\Factory'))
			{
				if (!defined('FOF30_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof30/include.php'))
				{
					$returnData->error = 1;
					$returnData->errormessages[] = "missing FOF30 libraries";
					$returnData->akeebatoken = $akeebaParams->get("frontend_secret_word", "");
					return $returnData;
				}

				$container = \FOF30\Container\Container::getInstance('com_akeeba', array(), 'admin');

				/** @var \Akeeba\Backup\Admin\Dispatcher\Dispatcher $dispatcher */
				$dispatcher = $container->dispatcher;

				try
				{
					if (is_callable(array($dispatcher, "loadAkeebaEngine")))
					{
						$dispatcher->loadAkeebaEngine();
						$dispatcher->loadAkeebaEngineConfiguration();
					}
					else
					{
						$returnData->error = 0;
						$returnData->errormessages[] = "Could not decrypt secret work - this is an Akeeba Pro feature?";
						$returnData->akeebatoken = $akeebaParams->get("frontend_secret_word", "");
						return $returnData;
					}
				}
				catch (Exception $e)
				{
					$returnData->error = 1;
					$returnData->errormessages[] = "Problems with FOF30 libraries";
					$returnData->akeebatoken = $akeebaParams->get("frontend_secret_word", "");
					return $returnData;
				}
			}

			$secureSettings = \Akeeba\Engine\Factory::getSecureSettings();

			$returnData->akeebatoken = $secureSettings->decryptSettings($akeebaParams->get("frontend_secret_word", ""));
			return $returnData;
		}
		else
		{
			$returnData->warning    = 1;
			$returnData->messages[] = 'COM_YOURSITES_NO_AKEEBA_TOKEN';
			return $returnData;

		}
	}
	catch (Exception $e)
	{
		$returnData->error           = 1;
		$returnData->errormessages[] = 'COM_YOURSITES_NO_AKEEBA_TOKEN';
		$returnData->errormessages[] = $e->getMessage();
		return $returnData;

	}

}
/*
function getBackups($requestObject, $returnData)
{
	try
	{
		$akeebaParams = JComponentHelper::getParams("com_akeeba");
		if ($akeebaParams && $akeebaParams->get("frontend_secret_word", ""))
		{
			$returnData->error      = 0;
			$returnData->result = 'COM_YOURSITES_AKEEBA_TOKEN_FOUND';
			$returnData->messages[] = 'COM_YOURSITES_AKEEBA_TOKEN_FOUND';

			// If the Factory is not already loaded we have to load the
			if (!class_exists('Akeeba\Engine\Factory'))
			{
				if (!defined('FOF30_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof30/include.php'))
				{
					$returnData->error = 1;
					$returnData->errormessages[] = "missing FOF30 libraries";
					$returnData->akeebatoken = $akeebaParams->get("frontend_secret_word", "");
					return $returnData;
				}

				$container = \FOF30\Container\Container::getInstance('com_akeeba', array(), 'admin');

				// @var \Akeeba\Backup\Admin\Dispatcher\Dispatcher $dispatcher
				$dispatcher = $container->dispatcher;

				try
				{
					$dispatcher->loadAkeebaEngine();
					$dispatcher->loadAkeebaEngineConfiguration();
				}
				catch (Exception $e)
				{
					$returnData->error = 1;
					$returnData->errormessages[] = "Problems with FOF30 libraries";
					$returnData->akeebatoken = $akeebaParams->get("frontend_secret_word", "");
					return $returnData;
				}
			}

			$secureSettings = \Akeeba\Engine\Factory::getSecureSettings();

			$returnData->akeebatoken = $secureSettings->decryptSettings($akeebaParams->get("frontend_secret_word", ""));

			// Now to get the backups!




			return $returnData;
		}
		else
		{
			$returnData->error           = 1;
			$returnData->errormessages[] = 'COM_YOURSITES_NO_AKEEBA_TOKEN';
			return $returnData;

		}
	}
	catch (Exception $e)
	{
		$returnData->error           = 1;
		$returnData->errormessages[] = 'COM_YOURSITES_NO_AKEEBA_TOKEN';
		return $returnData;

	}

}
*/

function securityCheck( & $requestObject, $returnData)
{
	$session = JFactory::getSession();

	if ($session->get("checksecurity",  1))
	{
		$plugin = JPluginHelper::getPlugin("system" , "yoursites");
		if ($plugin)
		{
			$input = JFactory::getApplication()->input;

			$params = json_decode($plugin->params);
			$servertoken = $params->servertoken;
			$randomtoken = $input->get("t", isset($requestObject->token) ? $requestObject->token : "", 'string');
			$securitytoken = $input->get("st", isset($requestObject->securityToken) ? $requestObject->securityToken : "", 'string');

			if (empty($randomtoken) || empty($securitytoken))
			{
				return false;
			}
			// Make sure the token exists and hasn't been used before and not expired
			$expires = new JDate( "-10 minutes");
			$now = $expires->toSql();

			$db = JFactory::getDbo();

			// Delete expired tokens
			$query = $db->getQuery(true);
			$query->delete("#__ysts_tokens")
				->where ('expires < '. $db->quote($now));
			$db->setQuery($query);
			$db->execute();

			$query = $db->getQuery(true);
			$query->select('tokenvalue')
				->from("#__ysts_tokens")
				->where ('tokenvalue = '. $db->quote($randomtoken));

			$db->setQuery($query);
			$dbtoken = $db->loadResult();
			if (!$dbtoken)
			{
				// THIS SHOULD NOT BE RETURNED - ONLY FOR DEBUGGING
				//$returnData->errormessages[]="source token not in database";
				$returnData->errormessages[] = "Invalid token";
				return false;
			}

			// Token is available and hasn't been used so we now delete it
			$query = $db->getQuery(true);
			$query->delete("#__ysts_tokens")
				->where ('tokenvalue = '. $db->quote($randomtoken) );
			$db->setQuery($query);
			$db->execute();

			$files = JFactory::getApplication()->input->files;
			$filecount = $files->count();
			if ($filecount)
			{
				$install_package = $files->get('install_package', false, 'raw');
				$filehash = hash_file('sha256', realpath($install_package["tmp_name"]));
			}
			else
			{
				$json = $input->get('json', '', 'raw');
				$json64 = $input->get('json64', '', 'raw');

				if (empty($json) && !empty($json64))
				{
					$json = $json64;
				}
				$filehash = !empty($json) ? hash('sha256' , $json) : '';
			}

			if ($securitytoken && password_verify(hash('sha256',$randomtoken  . " combined with " . $servertoken . $filehash), $securitytoken) )
			{
				return true;
			}
			// THIS SHOULD NOT BE RETURNED - ONLY FOR DEBUGGING
			//$returnData->errormessages[]="source token = ".$requestObject->token;
			//$returnData->errormessages[]="server token = ".$servertoken;
			//$returnData->errormessages[]="requiredToken = ".$requiredToken;
			//$returnData->errormessages[]="providedToken = ".$requestObject->securityToken;
		}
		return false;
	}
	else
	{
		return true;
	}
}

function setupSecurityToken($requestObject, & $returnData)
{
	$session = JFactory::getSession();
	$session->set("checksecurity",  1);
	$returnData->token = password_hash(uniqid(mt_rand(), true), PASSWORD_DEFAULT, array('cost' => 10));
	//$returnData->token =  uniqid('fred', true);;

	$expires = new JDate( "0 seconds");
	$now = $expires->toSql();

	// Store new and unused token in database to make sure it can only be used once!
	$db = JFactory::getDbo();
	$query = $db->getQuery(true);
	$query->insert("#__ysts_tokens")
		->set($db->quoteName("tokenvalue") . " = " . $db->quote($returnData->token))
		->set($db->quoteName("expires") . " = " . $db->quote($now));

	$db->setQuery($query);
	$db->execute();
}

function cleanCache($group, $client_id = 0)
{
	$conf = JFactory::getConfig();

	$options = array(
		'defaultgroup' => $group ,
		'cachebase' => $client_id ? JPATH_ADMINISTRATOR . '/cache' : $conf->get('cache_path', JPATH_SITE . '/cache'),
		'result' => true,
	);

	try
	{
		/** @var \JCacheControllerCallback $cache */
		$cache = \JCache::getInstance('callback', $options);
		$cache->clean();
		return true;
	}
	catch (Exception $exception)
	{
		return false;
	}
}


function encodeResults($returnData)
{
	header("Content-Type: application/javascript; charset=utf-8");

	if (is_object($returnData))
	{
		if (defined('_SC_START'))
		{
			list ($usec, $sec) = explode(" ", microtime());
			$time_end           = (float) $usec + (float) $sec;
			$returnData->timing = round($time_end - _SC_START, 4);
		}
		else
		{
			$returnData->timing = 0;
		}
	}

	// Must suppress any error messages
	@ob_end_clean();

	// See https://www.php.net/manual/en/json.constants.php
	if (version_compare(PHP_VERSION, '7.2.0') >= 0)
	{
		$plaintext = '#$£' . json_encode($returnData, JSON_INVALID_UTF8_IGNORE) . '#$£';
	}
	else
	{
		$plaintext = '#$£' . json_encode($returnData) . '#$£';
	}

	$plugin = JPluginHelper::getPlugin("system", "yoursites");

	// supported ciphers
	$supportedciphers = getCiphers();
	if ((isset($returnData->AES256) && $returnData->AES256 == true) && (in_array("AES256", $supportedciphers) || in_array("aes256", $supportedciphers)) && $plugin)
	{

		$cipher = "AES256";
		$ivlen = openssl_cipher_iv_length($cipher);
		$iv = openssl_random_pseudo_bytes($ivlen);

		$params = json_decode($plugin->params);
		$servertoken = $params->servertoken;

		$ciphertext = openssl_encrypt($plaintext, $cipher, $servertoken, $options=0, $iv);

		//store $cipher, $iv for decryption later
		//$original_plaintext = openssl_decrypt($ciphertext, $cipher, $servertoken, $options=0, $iv);

		//echo $original_plaintext;
		echo "AES256:" . base64_encode($iv) . ":" . $ciphertext;

	}
	else if ($plugin)
	{
		$params = json_decode($plugin->params);
		$servertoken = $params->servertoken;

		$iv = password_hash(uniqid(mt_rand(), true), PASSWORD_DEFAULT, array('cost' => 10));

		$encrypted = AKEncryptionAES::AESEncryptCtr($plaintext, $iv . $servertoken, 256);

		echo "AK256:" . base64_encode($iv) . ":" . $encrypted;
	}
	else
	{

		echo $plaintext;
	}

	exit(0);

}

function decrypt($requestString)
{
	$plugin = JPluginHelper::getPlugin("system" , "yoursites");

	// supported ciphers
	$supportedciphers = getCiphers();
	if ((in_array("AES256", $supportedciphers) || in_array("aes256", $supportedciphers)) && $plugin)
	{

		$parts = explode(":", $requestString);

		if (count($parts) >= 3)
		{
			unset($parts[0]);
			$iv = base64_decode($parts[1]);
			unset($parts[1]);
			$encryptedString = implode(":", $parts);

			$cipher = "AES256";

			$params      = json_decode($plugin->params);
			$servertoken = $params->servertoken;

			//store $cipher, $iv for decryption later
			$decryptedString = openssl_decrypt($encryptedString, $cipher, $servertoken, $options = 0, $iv);
			$requestObject = json_decode($decryptedString);
			return $requestObject;
		}
	}
	return $requestString;

}
/**
 * Akeeba Restore
 * A JSON-powered JPA, JPS and ZIP archive extraction library
 *
 * @copyright   2008-2017 Nicholas K. Dionysopoulos / Akeeba Ltd.
 * @license     GNU GPL v2 or - at your option - any later version
 * @package     akeebabackup
 * @subpackage  kickstart
 */

/**
 * AES implementation in PHP (c) Chris Veness 2005-2016.
 * Right to use and adapt is granted for under a simple creative commons attribution
 * licence. No warranty of any form is offered.
 *
 * Heavily modified for Akeeba Backup by Nicholas K. Dionysopoulos
 * Also added AES-128 CBC mode (with mcrypt and OpenSSL) on top of AES CTR
 */
class AKEncryptionAES
{
	// Sbox is pre-computed multiplicative inverse in GF(2^8) used in SubBytes and KeyExpansion [�5.1.1]
	protected static $Sbox =
		array(0x63, 0x7c, 0x77, 0x7b, 0xf2, 0x6b, 0x6f, 0xc5, 0x30, 0x01, 0x67, 0x2b, 0xfe, 0xd7, 0xab, 0x76,
			0xca, 0x82, 0xc9, 0x7d, 0xfa, 0x59, 0x47, 0xf0, 0xad, 0xd4, 0xa2, 0xaf, 0x9c, 0xa4, 0x72, 0xc0,
			0xb7, 0xfd, 0x93, 0x26, 0x36, 0x3f, 0xf7, 0xcc, 0x34, 0xa5, 0xe5, 0xf1, 0x71, 0xd8, 0x31, 0x15,
			0x04, 0xc7, 0x23, 0xc3, 0x18, 0x96, 0x05, 0x9a, 0x07, 0x12, 0x80, 0xe2, 0xeb, 0x27, 0xb2, 0x75,
			0x09, 0x83, 0x2c, 0x1a, 0x1b, 0x6e, 0x5a, 0xa0, 0x52, 0x3b, 0xd6, 0xb3, 0x29, 0xe3, 0x2f, 0x84,
			0x53, 0xd1, 0x00, 0xed, 0x20, 0xfc, 0xb1, 0x5b, 0x6a, 0xcb, 0xbe, 0x39, 0x4a, 0x4c, 0x58, 0xcf,
			0xd0, 0xef, 0xaa, 0xfb, 0x43, 0x4d, 0x33, 0x85, 0x45, 0xf9, 0x02, 0x7f, 0x50, 0x3c, 0x9f, 0xa8,
			0x51, 0xa3, 0x40, 0x8f, 0x92, 0x9d, 0x38, 0xf5, 0xbc, 0xb6, 0xda, 0x21, 0x10, 0xff, 0xf3, 0xd2,
			0xcd, 0x0c, 0x13, 0xec, 0x5f, 0x97, 0x44, 0x17, 0xc4, 0xa7, 0x7e, 0x3d, 0x64, 0x5d, 0x19, 0x73,
			0x60, 0x81, 0x4f, 0xdc, 0x22, 0x2a, 0x90, 0x88, 0x46, 0xee, 0xb8, 0x14, 0xde, 0x5e, 0x0b, 0xdb,
			0xe0, 0x32, 0x3a, 0x0a, 0x49, 0x06, 0x24, 0x5c, 0xc2, 0xd3, 0xac, 0x62, 0x91, 0x95, 0xe4, 0x79,
			0xe7, 0xc8, 0x37, 0x6d, 0x8d, 0xd5, 0x4e, 0xa9, 0x6c, 0x56, 0xf4, 0xea, 0x65, 0x7a, 0xae, 0x08,
			0xba, 0x78, 0x25, 0x2e, 0x1c, 0xa6, 0xb4, 0xc6, 0xe8, 0xdd, 0x74, 0x1f, 0x4b, 0xbd, 0x8b, 0x8a,
			0x70, 0x3e, 0xb5, 0x66, 0x48, 0x03, 0xf6, 0x0e, 0x61, 0x35, 0x57, 0xb9, 0x86, 0xc1, 0x1d, 0x9e,
			0xe1, 0xf8, 0x98, 0x11, 0x69, 0xd9, 0x8e, 0x94, 0x9b, 0x1e, 0x87, 0xe9, 0xce, 0x55, 0x28, 0xdf,
			0x8c, 0xa1, 0x89, 0x0d, 0xbf, 0xe6, 0x42, 0x68, 0x41, 0x99, 0x2d, 0x0f, 0xb0, 0x54, 0xbb, 0x16);

	// Rcon is Round Constant used for the Key Expansion [1st col is 2^(r-1) in GF(2^8)] [�5.2]
	protected static $Rcon = array(
		array(0x00, 0x00, 0x00, 0x00),
		array(0x01, 0x00, 0x00, 0x00),
		array(0x02, 0x00, 0x00, 0x00),
		array(0x04, 0x00, 0x00, 0x00),
		array(0x08, 0x00, 0x00, 0x00),
		array(0x10, 0x00, 0x00, 0x00),
		array(0x20, 0x00, 0x00, 0x00),
		array(0x40, 0x00, 0x00, 0x00),
		array(0x80, 0x00, 0x00, 0x00),
		array(0x1b, 0x00, 0x00, 0x00),
		array(0x36, 0x00, 0x00, 0x00));

	protected static $passwords = array();

	/**
	 * The algorithm to use for PBKDF2. Must be a supported hash_hmac algorithm. Default: sha1
	 *
	 * @var  string
	 */
	private static $pbkdf2Algorithm = 'sha1';

	/**
	 * Number of iterations to use for PBKDF2
	 *
	 * @var  int
	 */
	private static $pbkdf2Iterations = 1000;

	/**
	 * Should we use a static salt for PBKDF2?
	 *
	 * @var  int
	 */
	private static $pbkdf2UseStaticSalt = 0;

	/**
	 * The static salt to use for PBKDF2
	 *
	 * @var  string
	 */
	private static $pbkdf2StaticSalt = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";

	/**
	 * Encrypt a text using AES encryption in Counter mode of operation
	 *  - see http://csrc.nist.gov/publications/nistpubs/800-38a/sp800-38a.pdf
	 *
	 * Unicode multi-byte character safe
	 *
	 * @param   string $plaintext Source text to be encrypted
	 * @param   string $password  The password to use to generate a key
	 * @param   int    $nBits     Number of bits to be used in the key (128, 192, or 256)
	 *
	 * @return  string  Encrypted text
	 */
	public static function AESEncryptCtr($plaintext, $password, $nBits)
	{
		$blockSize = 16;  // block size fixed at 16 bytes / 128 bits (Nb=4) for AES
		if (!($nBits == 128 || $nBits == 192 || $nBits == 256))
		{
			return '';
		}  // standard allows 128/192/256 bit keys
		// note PHP (5) gives us plaintext and password in UTF8 encoding!

		// use AES itself to encrypt password to get cipher key (using plain password as source for
		// key expansion) - gives us well encrypted key
		$nBytes  = $nBits / 8;  // no bytes in key
		$pwBytes = array();
		for ($i = 0; $i < $nBytes; $i++)
		{
			$pwBytes[$i] = ord(substr($password, $i, 1)) & 0xff;
		}
		$key = self::Cipher($pwBytes, self::KeyExpansion($pwBytes));
		$key = array_merge($key, array_slice($key, 0, $nBytes - 16));  // expand key to 16/24/32 bytes long

		// initialise counter block (NIST SP800-38A �B.2): millisecond time-stamp for nonce in
		// 1st 8 bytes, block counter in 2nd 8 bytes
		$counterBlock = array();
		$nonce        = floor(microtime(true) * 1000);   // timestamp: milliseconds since 1-Jan-1970
		$nonceSec     = floor($nonce / 1000);
		$nonceMs      = $nonce % 1000;
		// encode nonce with seconds in 1st 4 bytes, and (repeated) ms part filling 2nd 4 bytes
		for ($i = 0; $i < 4; $i++)
		{
			$counterBlock[$i] = self::urs($nonceSec, $i * 8) & 0xff;
		}
		for ($i = 0; $i < 4; $i++)
		{
			$counterBlock[$i + 4] = $nonceMs & 0xff;
		}
		// and convert it to a string to go on the front of the ciphertext
		$ctrTxt = '';
		for ($i = 0; $i < 8; $i++)
		{
			$ctrTxt .= chr($counterBlock[$i]);
		}

		// generate key schedule - an expansion of the key into distinct Key Rounds for each round
		$keySchedule = self::KeyExpansion($key);

		$blockCount = ceil(strlen($plaintext) / $blockSize);
		$ciphertxt  = array();  // ciphertext as array of strings

		for ($b = 0; $b < $blockCount; $b++)
		{
			// set counter (block #) in last 8 bytes of counter block (leaving nonce in 1st 8 bytes)
			// done in two stages for 32-bit ops: using two words allows us to go past 2^32 blocks (68GB)
			for ($c = 0; $c < 4; $c++)
			{
				$counterBlock[15 - $c] = self::urs($b, $c * 8) & 0xff;
			}
			for ($c = 0; $c < 4; $c++)
			{
				$counterBlock[15 - $c - 4] = self::urs($b / 0x100000000, $c * 8);
			}

			$cipherCntr = self::Cipher($counterBlock, $keySchedule);  // -- encrypt counter block --

			// block size is reduced on final block
			$blockLength = $b < $blockCount - 1 ? $blockSize : (strlen($plaintext) - 1) % $blockSize + 1;
			$cipherByte  = array();

			for ($i = 0; $i < $blockLength; $i++)
			{  // -- xor plaintext with ciphered counter byte-by-byte --
				$cipherByte[$i] = $cipherCntr[$i] ^ ord(substr($plaintext, $b * $blockSize + $i, 1));
				$cipherByte[$i] = chr($cipherByte[$i]);
			}
			$ciphertxt[$b] = implode('', $cipherByte);  // escape troublesome characters in ciphertext
		}

		// implode is more efficient than repeated string concatenation
		$ciphertext = $ctrTxt . implode('', $ciphertxt);
		$ciphertext = base64_encode($ciphertext);

		return $ciphertext;
	}

	/**
	 * AES Cipher function: encrypt 'input' with Rijndael algorithm
	 *
	 * @param   array $input    Message as byte-array (16 bytes)
	 * @param   array $w        key schedule as 2D byte-array (Nr+1 x Nb bytes) -
	 *                          generated from the cipher key by KeyExpansion()
	 *
	 * @return  string  Ciphertext as byte-array (16 bytes)
	 */
	protected static function Cipher($input, $w)
	{    // main Cipher function [�5.1]
		$Nb = 4;                 // block size (in words): no of columns in state (fixed at 4 for AES)
		$Nr = count($w) / $Nb - 1; // no of rounds: 10/12/14 for 128/192/256-bit keys

		$state = array();  // initialise 4xNb byte-array 'state' with input [�3.4]
		for ($i = 0; $i < 4 * $Nb; $i++)
		{
			$state[$i % 4][floor($i / 4)] = $input[$i];
		}

		$state = self::AddRoundKey($state, $w, 0, $Nb);

		for ($round = 1; $round < $Nr; $round++)
		{  // apply Nr rounds
			$state = self::SubBytes($state, $Nb);
			$state = self::ShiftRows($state, $Nb);
			$state = self::MixColumns($state);
			$state = self::AddRoundKey($state, $w, $round, $Nb);
		}

		$state = self::SubBytes($state, $Nb);
		$state = self::ShiftRows($state, $Nb);
		$state = self::AddRoundKey($state, $w, $Nr, $Nb);

		$output = array(4 * $Nb);  // convert state to 1-d array before returning [�3.4]
		for ($i = 0; $i < 4 * $Nb; $i++)
		{
			$output[$i] = $state[$i % 4][floor($i / 4)];
		}

		return $output;
	}

	protected static function AddRoundKey($state, $w, $rnd, $Nb)
	{  // xor Round Key into state S [�5.1.4]
		for ($r = 0; $r < 4; $r++)
		{
			for ($c = 0; $c < $Nb; $c++)
			{
				$state[$r][$c] ^= $w[$rnd * 4 + $c][$r];
			}
		}

		return $state;
	}

	protected static function SubBytes($s, $Nb)
	{    // apply SBox to state S [�5.1.1]
		for ($r = 0; $r < 4; $r++)
		{
			for ($c = 0; $c < $Nb; $c++)
			{
				$s[$r][$c] = self::$Sbox[$s[$r][$c]];
			}
		}

		return $s;
	}

	protected static function ShiftRows($s, $Nb)
	{    // shift row r of state S left by r bytes [�5.1.2]
		$t = array(4);
		for ($r = 1; $r < 4; $r++)
		{
			for ($c = 0; $c < 4; $c++)
			{
				$t[$c] = $s[$r][($c + $r) % $Nb];
			}  // shift into temp copy
			for ($c = 0; $c < 4; $c++)
			{
				$s[$r][$c] = $t[$c];
			}         // and copy back
		}          // note that this will work for Nb=4,5,6, but not 7,8 (always 4 for AES):
		return $s;  // see fp.gladman.plus.com/cryptography_technology/rijndael/aes.spec.311.pdf
	}

	protected static function MixColumns($s)
	{
		// combine bytes of each col of state S [�5.1.3]
		for ($c = 0; $c < 4; $c++)
		{
			$a = array(4);  // 'a' is a copy of the current column from 's'
			$b = array(4);  // 'b' is a�{02} in GF(2^8)

			for ($i = 0; $i < 4; $i++)
			{
				$a[$i] = $s[$i][$c];
				$b[$i] = $s[$i][$c] & 0x80 ? $s[$i][$c] << 1 ^ 0x011b : $s[$i][$c] << 1;
			}

			// a[n] ^ b[n] is a�{03} in GF(2^8)
			$s[0][$c] = $b[0] ^ $a[1] ^ $b[1] ^ $a[2] ^ $a[3]; // 2*a0 + 3*a1 + a2 + a3
			$s[1][$c] = $a[0] ^ $b[1] ^ $a[2] ^ $b[2] ^ $a[3]; // a0 * 2*a1 + 3*a2 + a3
			$s[2][$c] = $a[0] ^ $a[1] ^ $b[2] ^ $a[3] ^ $b[3]; // a0 + a1 + 2*a2 + 3*a3
			$s[3][$c] = $a[0] ^ $b[0] ^ $a[1] ^ $a[2] ^ $b[3]; // 3*a0 + a1 + a2 + 2*a3
		}

		return $s;
	}

	/**
	 * Key expansion for Rijndael Cipher(): performs key expansion on cipher key
	 * to generate a key schedule
	 *
	 * @param   array $key Cipher key byte-array (16 bytes)
	 *
	 * @return  array  Key schedule as 2D byte-array (Nr+1 x Nb bytes)
	 */
	protected static function KeyExpansion($key)
	{
		// generate Key Schedule from Cipher Key [�5.2]

		// block size (in words): no of columns in state (fixed at 4 for AES)
		$Nb = 4;
		// key length (in words): 4/6/8 for 128/192/256-bit keys
		$Nk = (int) (count($key) / 4);
		// no of rounds: 10/12/14 for 128/192/256-bit keys
		$Nr = $Nk + 6;

		$w    = array();
		$temp = array();

		for ($i = 0; $i < $Nk; $i++)
		{
			$r     = array($key[4 * $i], $key[4 * $i + 1], $key[4 * $i + 2], $key[4 * $i + 3]);
			$w[$i] = $r;
		}

		for ($i = $Nk; $i < ($Nb * ($Nr + 1)); $i++)
		{
			$w[$i] = array();
			for ($t = 0; $t < 4; $t++)
			{
				$temp[$t] = $w[$i - 1][$t];
			}
			if ($i % $Nk == 0)
			{
				$temp = self::SubWord(self::RotWord($temp));
				for ($t = 0; $t < 4; $t++)
				{
					$rConIndex = (int) ($i / $Nk);
					$temp[$t] ^= self::$Rcon[$rConIndex][$t];
				}
			}
			else if ($Nk > 6 && $i % $Nk == 4)
			{
				$temp = self::SubWord($temp);
			}
			for ($t = 0; $t < 4; $t++)
			{
				$w[$i][$t] = $w[$i - $Nk][$t] ^ $temp[$t];
			}
		}

		return $w;
	}

	protected static function SubWord($w)
	{    // apply SBox to 4-byte word w
		for ($i = 0; $i < 4; $i++)
		{
			$w[$i] = self::$Sbox[$w[$i]];
		}

		return $w;
	}

	/*
	 * Unsigned right shift function, since PHP has neither >>> operator nor unsigned ints
	 *
	 * @param a  number to be shifted (32-bit integer)
	 * @param b  number of bits to shift a to the right (0..31)
	 * @return   a right-shifted and zero-filled by b bits
	 */

	protected static function RotWord($w)
	{    // rotate 4-byte word w left by one byte
		$tmp = $w[0];
		for ($i = 0; $i < 3; $i++)
		{
			$w[$i] = $w[$i + 1];
		}
		$w[3] = $tmp;

		return $w;
	}

	protected static function urs($a, $b)
	{
		$a &= 0xffffffff;
		$b &= 0x1f;  // (bounds check)
		if ($a & 0x80000000 && $b > 0)
		{   // if left-most bit set
			$a = ($a >> 1) & 0x7fffffff;   //   right-shift one bit & clear left-most bit
			$a = $a >> ($b - 1);           //   remaining right-shifts
		}
		else
		{                       // otherwise
			$a = ($a >> $b);               //   use normal right-shift
		}

		return $a;
	}

	/**
	 * Decrypt a text encrypted by AES in counter mode of operation
	 *
	 * @param   string  $ciphertext  Source text to be decrypted
	 * @param   string  $password    The password to use to generate a key
	 * @param   int     $nBits       Number of bits to be used in the key (128, 192, or 256)
	 *
	 * @return  string  Decrypted text
	 */
	public static function AESDecryptCtr($ciphertext, $password, $nBits)
	{
		$blockSize = 16;  // block size fixed at 16 bytes / 128 bits (Nb=4) for AES

		if (!($nBits == 128 || $nBits == 192 || $nBits == 256))
		{
			return '';
		}

		// standard allows 128/192/256 bit keys
		$ciphertext = base64_decode($ciphertext);

		// use AES to encrypt password (mirroring encrypt routine)
		$nBytes  = $nBits / 8;  // no bytes in key
		$pwBytes = array();

		for ($i = 0; $i < $nBytes; $i++)
		{
			$pwBytes[$i] = ord(substr($password, $i, 1)) & 0xff;
		}

		$key = self::Cipher($pwBytes, self::KeyExpansion($pwBytes));
		$key = array_merge($key, array_slice($key, 0, $nBytes - 16));  // expand key to 16/24/32 bytes long

		// recover nonce from 1st element of ciphertext
		$counterBlock = array();
		$ctrTxt       = substr($ciphertext, 0, 8);

		for ($i = 0; $i < 8; $i++)
		{
			$counterBlock[$i] = ord(substr($ctrTxt, $i, 1));
		}

		// generate key schedule
		$keySchedule = self::KeyExpansion($key);

		// separate ciphertext into blocks (skipping past initial 8 bytes)
		$nBlocks = ceil((strlen($ciphertext) - 8) / $blockSize);
		$ct      = array();

		for ($b = 0; $b < $nBlocks; $b++)
		{
			$ct[$b] = substr($ciphertext, 8 + $b * $blockSize, 16);
		}

		$ciphertext = $ct;  // ciphertext is now array of block-length strings

		// plaintext will get generated block-by-block into array of block-length strings
		$plaintxt = array();

		for ($b = 0; $b < $nBlocks; $b++)
		{
			// set counter (block #) in last 8 bytes of counter block (leaving nonce in 1st 8 bytes)
			for ($c = 0; $c < 4; $c++)
			{
				$counterBlock[15 - $c] = self::urs($b, $c * 8) & 0xff;
			}

			for ($c = 0; $c < 4; $c++)
			{
				$counterBlock[15 - $c - 4] = self::urs(($b + 1) / 0x100000000 - 1, $c * 8) & 0xff;
			}

			$cipherCntr = self::Cipher($counterBlock, $keySchedule);  // encrypt counter block

			$plaintxtByte = array();

			for ($i = 0; $i < strlen($ciphertext[$b]); $i++)
			{
				// -- xor plaintext with ciphered counter byte-by-byte --
				$plaintxtByte[$i] = $cipherCntr[$i] ^ ord(substr($ciphertext[$b], $i, 1));
				$plaintxtByte[$i] = chr($plaintxtByte[$i]);

			}

			$plaintxt[$b] = implode('', $plaintxtByte);
		}

		// join array of blocks into single plaintext string
		$plaintext = implode('', $plaintxt);

		return $plaintext;
	}

	/**
	 * AES decryption in CBC mode. This is the standard mode (the CTR methods
	 * actually use Rijndael-128 in CTR mode, which - technically - isn't AES).
	 *
	 * It supports AES-128 only. It assumes that the last 4 bytes
	 * contain a little-endian unsigned long integer representing the unpadded
	 * data length.
	 *
	 * @since  3.0.1
	 * @author Nicholas K. Dionysopoulos
	 *
	 * @param   string $ciphertext The data to encrypt
	 * @param   string $password   Encryption password
	 *
	 * @return  string  The plaintext
	 */
	public static function AESDecryptCBC($ciphertext, $password)
	{
		$adapter = self::getAdapter();

		if (!$adapter->isSupported())
		{
			return false;
		}

		// Read the data size
		$data_size = unpack('V', substr($ciphertext, -4));

		// Do I have a PBKDF2 salt?
		$salt             = substr($ciphertext, -92, 68);
		$rightStringLimit = -4;

		$params        = self::getKeyDerivationParameters();
		$keySizeBytes  = $params['keySize'];
		$algorithm     = $params['algorithm'];
		$iterations    = $params['iterations'];
		$useStaticSalt = $params['useStaticSalt'];

		if (substr($salt, 0, 4) == 'JPST')
		{
			// We have a stored salt. Retrieve it and tell decrypt to process the string minus the last 44 bytes
			// (4 bytes for JPST, 16 bytes for the salt, 4 bytes for JPIV, 16 bytes for the IV, 4 bytes for the
			// uncompressed string length - note that using PBKDF2 means we're also using a randomized IV per the
			// format specification).
			$salt             = substr($salt, 4);
			$rightStringLimit -= 68;

			$key          = self::pbkdf2($password, $salt, $algorithm, $iterations, $keySizeBytes);
		}
		elseif ($useStaticSalt)
		{
			// We have a static salt. Use it for PBKDF2.
			$key = self::getStaticSaltExpandedKey($password);
		}
		else
		{
			// Get the expanded key from the password. THIS USES THE OLD, INSECURE METHOD.
			$key = self::expandKey($password);
		}

		// Try to get the IV from the data
		$iv               = substr($ciphertext, -24, 20);

		if (substr($iv, 0, 4) == 'JPIV')
		{
			// We have a stored IV. Retrieve it and tell mdecrypt to process the string minus the last 24 bytes
			// (4 bytes for JPIV, 16 bytes for the IV, 4 bytes for the uncompressed string length)
			$iv               = substr($iv, 4);
			$rightStringLimit -= 20;
		}
		else
		{
			// No stored IV. Do it the dumb way.
			$iv = self::createTheWrongIV($password);
		}

		// Decrypt
		$plaintext = $adapter->decrypt($iv . substr($ciphertext, 0, $rightStringLimit), $key);

		// Trim padding, if necessary
		if (strlen($plaintext) > $data_size)
		{
			$plaintext = substr($plaintext, 0, $data_size);
		}

		return $plaintext;
	}

	/**
	 * That's the old way of creating an IV that's definitely not cryptographically sound.
	 *
	 * DO NOT USE, EVER, UNLESS YOU WANT TO DECRYPT LEGACY DATA
	 *
	 * @param   string $password The raw password from which we create an IV in a super bozo way
	 *
	 * @return  string  A 16-byte IV string
	 */
	public static function createTheWrongIV($password)
	{
		static $ivs = array();

		$key = md5($password);

		if (!isset($ivs[$key]))
		{
			$nBytes  = 16;  // AES uses a 128 -bit (16 byte) block size, hence the IV size is always 16 bytes
			$pwBytes = array();
			for ($i = 0; $i < $nBytes; $i++)
			{
				$pwBytes[$i] = ord(substr($password, $i, 1)) & 0xff;
			}
			$iv    = self::Cipher($pwBytes, self::KeyExpansion($pwBytes));
			$newIV = '';
			foreach ($iv as $int)
			{
				$newIV .= chr($int);
			}

			$ivs[$key] = $newIV;
		}

		return $ivs[$key];
	}

	/**
	 * Expand the password to an appropriate 128-bit encryption key
	 *
	 * @param   string $password
	 *
	 * @return  string
	 *
	 * @since   5.2.0
	 * @author  Nicholas K. Dionysopoulos
	 */
	public static function expandKey($password)
	{
		// Try to fetch cached key or create it if it doesn't exist
		$nBits     = 128;
		$lookupKey = md5($password . '-' . $nBits);

		if (array_key_exists($lookupKey, self::$passwords))
		{
			$key = self::$passwords[$lookupKey];

			return $key;
		}

		// use AES itself to encrypt password to get cipher key (using plain password as source for
		// key expansion) - gives us well encrypted key.
		$nBytes  = $nBits / 8; // Number of bytes in key
		$pwBytes = array();

		for ($i = 0; $i < $nBytes; $i++)
		{
			$pwBytes[$i] = ord(substr($password, $i, 1)) & 0xff;
		}

		$key    = self::Cipher($pwBytes, self::KeyExpansion($pwBytes));
		$key    = array_merge($key, array_slice($key, 0, $nBytes - 16)); // expand key to 16/24/32 bytes long
		$newKey = '';

		foreach ($key as $int)
		{
			$newKey .= chr($int);
		}

		$key = $newKey;

		self::$passwords[$lookupKey] = $key;

		return $key;
	}

	/**
	 * Returns the correct AES-128 CBC encryption adapter
	 *
	 * @return  AKEncryptionAESAdapterInterface
	 *
	 * @since   5.2.0
	 * @author  Nicholas K. Dionysopoulos
	 */
	public static function getAdapter()
	{
		static $adapter = null;

		if (is_object($adapter) && ($adapter instanceof AKEncryptionAESAdapterInterface))
		{
			return $adapter;
		}

		$adapter = new OpenSSL();

		if (!$adapter->isSupported())
		{
			$adapter = new Mcrypt();
		}

		return $adapter;
	}

	/**
	 * @return string
	 */
	public static function getPbkdf2Algorithm()
	{
		return self::$pbkdf2Algorithm;
	}

	/**
	 * @param string $pbkdf2Algorithm
	 * @return void
	 */
	public static function setPbkdf2Algorithm($pbkdf2Algorithm)
	{
		self::$pbkdf2Algorithm = $pbkdf2Algorithm;
	}

	/**
	 * @return int
	 */
	public static function getPbkdf2Iterations()
	{
		return self::$pbkdf2Iterations;
	}

	/**
	 * @param int $pbkdf2Iterations
	 * @return void
	 */
	public static function setPbkdf2Iterations($pbkdf2Iterations)
	{
		self::$pbkdf2Iterations = $pbkdf2Iterations;
	}

	/**
	 * @return int
	 */
	public static function getPbkdf2UseStaticSalt()
	{
		return self::$pbkdf2UseStaticSalt;
	}

	/**
	 * @param int $pbkdf2UseStaticSalt
	 * @return void
	 */
	public static function setPbkdf2UseStaticSalt($pbkdf2UseStaticSalt)
	{
		self::$pbkdf2UseStaticSalt = $pbkdf2UseStaticSalt;
	}

	/**
	 * @return string
	 */
	public static function getPbkdf2StaticSalt()
	{
		return self::$pbkdf2StaticSalt;
	}

	/**
	 * @param string $pbkdf2StaticSalt
	 * @return void
	 */
	public static function setPbkdf2StaticSalt($pbkdf2StaticSalt)
	{
		self::$pbkdf2StaticSalt = $pbkdf2StaticSalt;
	}

	/**
	 * Get the parameters fed into PBKDF2 to expand the user password into an encryption key. These are the static
	 * parameters (key size, hashing algorithm and number of iterations). A new salt is used for each encryption block
	 * to minimize the risk of attacks against the password.
	 *
	 * @return  array
	 */
	public static function getKeyDerivationParameters()
	{
		return array(
			'keySize'       => 16,
			'algorithm'     => self::$pbkdf2Algorithm,
			'iterations'    => self::$pbkdf2Iterations,
			'useStaticSalt' => self::$pbkdf2UseStaticSalt,
			'staticSalt'    => self::$pbkdf2StaticSalt,
		);
	}

	/**
	 * PBKDF2 key derivation function as defined by RSA's PKCS #5: https://www.ietf.org/rfc/rfc2898.txt
	 *
	 * Test vectors can be found here: https://www.ietf.org/rfc/rfc6070.txt
	 *
	 * This implementation of PBKDF2 was originally created by https://defuse.ca
	 * With improvements by http://www.variations-of-shadow.com
	 * Modified for Akeeba Engine by Akeeba Ltd (removed unnecessary checks to make it faster)
	 *
	 * @param   string  $password    The password.
	 * @param   string  $salt        A salt that is unique to the password.
	 * @param   string  $algorithm   The hash algorithm to use. Default is sha1.
	 * @param   int     $count       Iteration count. Higher is better, but slower. Default: 1000.
	 * @param   int     $key_length  The length of the derived key in bytes.
	 *
	 * @return  string  A string of $key_length bytes
	 */
	public static function pbkdf2($password, $salt, $algorithm = 'sha1', $count = 1000, $key_length = 16)
	{
		if (function_exists("hash_pbkdf2"))
		{
			return hash_pbkdf2($algorithm, $password, $salt, $count, $key_length, true);
		}

		$hash_length = akstringlen(hash($algorithm, "", true));
		$block_count = ceil($key_length / $hash_length);

		$output = "";

		for ($i = 1; $i <= $block_count; $i++)
		{
			// $i encoded as 4 bytes, big endian.
			$last = $salt . pack("N", $i);

			// First iteration
			$xorResult = hash_hmac($algorithm, $last, $password, true);
			$last      = $xorResult;

			// Perform the other $count - 1 iterations
			for ($j = 1; $j < $count; $j++)
			{
				$last = hash_hmac($algorithm, $last, $password, true);
				$xorResult ^= $last;
			}

			$output .= $xorResult;
		}

		return aksubstr($output, 0, $key_length);
	}

	/**
	 * Get the expanded key from the user supplied password using a static salt. The results are cached for performance
	 * reasons.
	 *
	 * @param   string  $password  The user-supplied password, UTF-8 encoded.
	 *
	 * @return  string  The expanded key
	 */
	private static function getStaticSaltExpandedKey($password)
	{
		$params        = self::getKeyDerivationParameters();
		$keySizeBytes  = $params['keySize'];
		$algorithm     = $params['algorithm'];
		$iterations    = $params['iterations'];
		$staticSalt    = $params['staticSalt'];

		$lookupKey = "PBKDF2-$algorithm-$iterations-" . md5($password . $staticSalt);

		if (!array_key_exists($lookupKey, self::$passwords))
		{
			self::$passwords[$lookupKey] = self::pbkdf2($password, $staticSalt, $algorithm, $iterations, $keySizeBytes);
		}

		return self::$passwords[$lookupKey];
	}

}


function return_bytes ($val)
{
	if(empty($val))return 0;

	$val = trim($val);

	preg_match('#([0-9]+)[\s]*([a-z]+)#i', $val, $matches);

	$last = '';
	if(isset($matches[2])){
		$last = $matches[2];
	}

	if(isset($matches[1])){
		$val = (int) $matches[1];
	}

	switch (strtolower($last))
	{
		case 'g':
		case 'gb':
			$val *= 1024;
		case 'm':
		case 'mb':
			$val *= 1024;
		case 'k':
		case 'kb':
			$val *= 1024;
	}

	return (int) $val;
}

function getCiphers()
{
	if (is_callable("openssl_get_cipher_methods"))
	{
		$ciphers             = openssl_get_cipher_methods(true);

		$ciphers = array_filter($ciphers, function ($c) {
			return (stripos($c, "rc2") === false
				|| stripos($c, "rc4") === false
				|| stripos($c, "md5") === false
				|| stripos($c, "ecb") === false
				|| stripos($c, "des") === false);
		});

	}
	else
	{
		$ciphers = array();
	}
	return $ciphers;
}

function onYstsDie(){
	if (!defined('CLEAN_YSTS_EXIT')) {
		define('CLEAN_YSTS_EXIT', 1);

		$last_error = error_get_last();
		if(isset($last_error['type']) && ($last_error['type'] === E_ERROR || $last_error['type'] === E_COMPILE_ERROR))
		{
			$message = ob_get_clean(); // Capture output buffer and clean it

			$returnData = new stdClass();
			$returnData->error = 1;
			$returnData->messages = array();
			$returnData->log = array();
			$returnData->timing = array();
			$returnData->errormessages = array();

			$returnData->log[] = $message;
			$returnData->log['error'] = $last_error;
			$returnData->errormessages[] = "COM_YOURSITES_HELP_WE_DIED";
			$returnData->errormessages[] = $last_error['message'];
			if (isset($last_error['file']) && !empty($last_error['file']))
			{
				$returnData->errormessages[] = " @ " . $last_error['file'] . " : " . $last_error['line'];
			}

			echo encodeResults($returnData);
			exit(0);
		}
	}
}

// needed for rebuildsites method
function getJoomlaUpdateSitesIds($column = 0)
{
	$db  = JFactory::getDbo();

	// Fetch the Joomla core update sites ids and their extension ids. We search for all except the core joomla extension with update sites.
	$query = $db->getQuery(true)
		->select($db->quoteName(array('use.update_site_id', 'e.extension_id')))
		->from($db->quoteName('#__update_sites_extensions', 'use'))
		->join('LEFT', $db->quoteName('#__update_sites', 'us') . ' ON ' . $db->qn('us.update_site_id') . ' = ' . $db->qn('use.update_site_id'))
		->join('LEFT', $db->quoteName('#__extensions', 'e') . ' ON ' . $db->qn('e.extension_id') . ' = ' . $db->qn('use.extension_id'))
		->where('('
			. '(' . $db->qn('e.type') . ' = ' . $db->quote('file') . ' AND ' . $db->qn('e.element') . ' = ' . $db->quote('joomla') . ')'
			. ' OR (' . $db->qn('e.type') . ' = ' . $db->quote('package') . ' AND ' . $db->qn('e.element') . ' = ' . $db->quote('pkg_en-GB') . ')'
			. ' OR (' . $db->qn('e.type') . ' = ' . $db->quote('component') . ' AND ' . $db->qn('e.element') . ' = ' . $db->quote('com_joomlaupdate') . ')'
			. ')'
		);

	$db->setQuery($query);

	return $db->loadColumn($column);
}

/**
 * Copy a folder - customised from Joomla JFolder method
 *
 * @param   string   $src          The path to the source folder.
 * @param   string   $dest         The path to the destination folder.
 *
 * @return  boolean  True on success.
 *
 * @since   1.15.0
 * @throws  \RuntimeException
 */
function copyr2($src, $dest, $prefix = "", & $returnData, $exclusions = array())
{

	$FTPOptions = JClientHelper::getCredentials('ftp');

	// Eliminate trailing directory separators, if any
	$src = rtrim($src, DIRECTORY_SEPARATOR);
	$dest = rtrim($dest, DIRECTORY_SEPARATOR);

	//$returnData->error = 1;
	//$returnData->errormessages[] = "Copying $src to $dest";

	if (!JFolder::exists($src))
	{
		throw new \RuntimeException('Source folder not found ' . $src, -1);
	}

	if (JFolder::exists($dest) )
	{
		throw new \RuntimeException('Destination folder already exists ' . $dest, -1);
	}
	// Make sure the destination exists
	if (!JFolder::create($dest))
	{
		throw new \RuntimeException('Cannot create destination folder ' . $dest, -1);
	}


	// If we're using ftp
	if ($FTPOptions['enabled'] == 1 )
	{
		// Connect the FTP client
		$ftp = JFtpClient::getInstance($FTPOptions['host'], $FTPOptions['port'], array(), $FTPOptions['user'], $FTPOptions['pass']);

		if (!($dh = @opendir($src)))
		{
			throw new \RuntimeException('Cannot open source folder', -1);
		}
		// Walk through the directory copying files and recursing into folders.
		while (($file = readdir($dh)) !== false)
		{
			$entry = pathinfo($file, PATHINFO_BASENAME);

			// Skip itself and other clones
			if (strpos($entry, $prefix) !== false || (strpos($entry , "._ysts_")  === 0 && strlen($entry) == 12) )
			{
				continue;
			}

			// Ingore any backups files
			if (in_array(pathinfo($entry, PATHINFO_EXTENSION), array("jpa", "zip", "gz", "targz")))
			{
				continue;
			}

			$sfid = $src . '/' . $file;
			$dfid = $dest . '/' . $file;

			switch (filetype($sfid))
			{
				case 'dir':
					if (in_array($file, $exclusions))
					{
						// should make directory
						continue 2;
					}

					if ($file != '.' && $file != '..')
					{
						// recursive call
						$ret = copyr2($sfid, $dfid, $prefix, $returnData);

						if ($ret !== true)
						{
							return $ret;
						}
					}
					break;

				case 'file':
					// Translate path for the FTP account
					$dfid = JPath::clean(str_replace(JPATH_ROOT, $FTPOptions['root'], $dfid), '/');

					if (!$ftp->store($sfid, $dfid))
					{
						throw new \RuntimeException('Copy file failed', -1);
					}
					break;
			}
		}
	}
	else
	{
		if (!($dh = @opendir($src)))
		{
			throw new \RuntimeException('Cannot open source folder', -1);
		}
		// Walk through the directory copying files and recursing into folders.
		while (($file = readdir($dh)) !== false)
		{
			$entry = pathinfo($file, PATHINFO_BASENAME);

			// Skip itself and other clones
			if (strpos($entry, $prefix) !== false || (strpos($entry , "._")  === 0 && strlen($entry) == 12) )
			{
				continue;
			}

			// Ingore any backups files
			if (in_array(pathinfo($entry, PATHINFO_EXTENSION), array("jpa", "zip", "gz", "targz", "jps", "bz", "rar")))
			{
				continue;
			}

			$sfid = $src . '/' . $file;
			$dfid = $dest . '/' . $file;

			switch (filetype($sfid))
			{
				case 'dir':
					if (in_array($file, $exclusions))
					{
						mkdir($sfid);
						continue 2;
					}

					if ($file != '.' && $file != '..')
					{
						// recursive call
						$ret = copyr2($sfid, $dfid, $prefix, $returnData);

						if ($ret !== true)
						{
							return $ret;
						}
					}
					break;

				case 'file':
					if (!@copy($sfid, $dfid))
					{
						throw new \RuntimeException('Copy file failed', -1);
					}
					break;
			}
		}
	}

	return true;
}

/**
 * Delete a folder - customised from Joomla JFolder method
 *
 * @param   string   $src          The path to the source folder.
 *
 * @return  boolean  True on success.
 *
 * @since   1.15.0
 * @throws  \RuntimeException
 */
function deleter2($src, & $returnData)
{

	if (!isset( $returnData->log['files']))
	{
		$returnData->log['files'] = array();
	}

	$FTPOptions = JClientHelper::getCredentials('ftp');

	// Eliminate trailing directory separators, if any
	$src = rtrim($src, DIRECTORY_SEPARATOR);

	if (!JFolder::exists($src))
	{
		throw new \RuntimeException('Source folder not found ' . $src, -1);
	}

	// If we're using ftp
	if ($FTPOptions['enabled'] == 1 )
	{
		// Connect the FTP client
		$ftp = JFtpClient::getInstance($FTPOptions['host'], $FTPOptions['port'], array(), $FTPOptions['user'], $FTPOptions['pass']);

		if (!($dh = @opendir($src)))
		{
			throw new \RuntimeException('Cannot open source folder', -1);
		}
		// Walk through the directory deleting files and recursing into folders.
		while (($file = readdir($dh)) !== false)
		{

			$sfid = $src . '/' . $file;

			switch (filetype($sfid))
			{
				case 'dir':
					if ($file != '.' && $file != '..')
					{
						// recursive call
						$ret = deleter2($sfid, $returnData);

						if ($ret !== true)
						{
							return $ret;
						}
						// Now delete the folder
						if (!$ftp->delete($sfid))
						{
							throw new \RuntimeException('Delete folder failed ' . $sfid, -1);
						}
					}
					break;

				case 'file':

					if (!$ftp->delete($sfid))
					{
						throw new \RuntimeException('Delete file failed ' . $sfid, -1);
					}
					break;
			}
		}
	}
	else
	{
		if (!($dh = @opendir($src)))
		{
			throw new \RuntimeException('Cannot open source folder', -1);
		}
		// Walk through the directory copying files and recursing into folders.
		while (($file = readdir($dh)) !== false)
		{

			$sfid = $src . '/' . $file;

			switch (filetype($sfid))
			{
				case 'dir':
					if ($file != '.' && $file != '..')
					{
						// recursive call
						$ret = deleter2($sfid, $returnData);

						if ($ret !== true)
						{
							return $ret;
						}

						//$returnData->log['files'][] = 'Dir = ' . $sfid;
						// Delete the folder

						if (!@rmdir($sfid))
						{
							throw new \RuntimeException('Delete folder failed ' . $sfid, -1);

						}

					}
					break;

				case 'file':
					//$returnData->log['files'][] = 'Folder ' . $sfid;

					if (!@unlink($sfid))
					{
						throw new \RuntimeException('Delete file failed ' . $sfid, -1);
					}

					break;
			}
		}
	}

	return true;
}

function getSiteInfo($returnData)
{
	JLoader::import('models.sysinfo', JPATH_ADMINISTRATOR . '/components/com_admin');
	if (class_exists('AdminModelSysInfo', true))
	{
		$infoModel = new AdminModelSysInfo();
	}
	else
	{
		$infoModel = new \Joomla\Component\Admin\Administrator\Model\SysinfoModel();
	}
	$returnData->siteInfo = $infoModel->getInfo();

	// Other useful information
	$jconfig = JFactory::getConfig();

	// DB size
	$db = JFactory::getDbo();
	$sql = 'SELECT  SUM(ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024 ), 2))  
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = "' . $jconfig->get("db"). '"
AND TABLE_NAME LIKE "' . $jconfig->get("dbprefix") . '%"';
	$db->setQuery($sql);
	$returnData->siteInfo["dbsize"]  = $db->loadResult();

	// single tables like this
	/*
	$sqlsingletables = 'SELECT TABLE_NAME, ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024 ), 2) as size
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = "' . $jconfig->get("db"). '"
AND TABLE_NAME LIKE "' . $jconfig->get("dbprefix") . '%"
ORDER BY size DESC';
	$db->setQuery($sqlsingletables);
	$returnData->siteInfo["dbsizetables"]  = $db->loadObjectList('TABLE_NAME');
	*/

	// Cache usage
	$clientId = 0;
	$options = array(
		'defaultgroup' => '',
		'storage'      => $jconfig->get('cache_handler', ''),
		'caching'      => true,
		'cachebase'    => (int) $clientId === 1 ? JPATH_ADMINISTRATOR . '/cache' : $jconfig->get('cache_path', JPATH_SITE . '/cache')
	);
	$cache = JCache::getInstance('', $options);
	$cacheData  = $cache->getAll();
	$totalCache = 0;
	foreach ($cacheData as $cachefolder)
	{
		$totalCache += $cachefolder->size;
	}
	$returnData->siteInfo["cacheusage"]  = number_format($totalCache / 1024 / 1024, 2, '.', '');

	// tmp file usage
	$config = JFactory::getConfig();
	$tempdir = $config->get('tmp_path');

	$bytestotal = 0;
	$tempdir = realpath($tempdir);
	try
	{
		if ($tempdir !== false && $tempdir != '' && file_exists($tempdir))
		{
			foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tempdir, FilesystemIterator::SKIP_DOTS)) as $object)
			{
				$bytestotal += $object->getSize();
			}
		}
	}
	catch (Exception $e)
	{
		$returnData->warning  = 1;
		$returnData->log      = $tempdir;
		$returnData->log      = $e->getMessage();
	}

	$returnData->siteInfo["tmpusage"]  = number_format($bytestotal / 1024 / 1024, 2, '.', '');

	// key ini data
	$returnData->siteInfo["inidata"] = array();
	$returnData->siteInfo["inidata"]["post_max_size"]       = return_bytes(ini_get('post_max_size'));
	$returnData->siteInfo["inidata"]["upload_max_filesize"] = return_bytes(ini_get('upload_max_filesize'));
	$returnData->siteInfo["inidata"]["memory_limit"]        = ini_get('memory_limit');

	$returnData->siteInfo["recommended"]["display_errors"]      = (bool) ini_get('display_errors');
	$returnData->siteInfo["recommended"]["file_uploads"]        = (bool) ini_get('file_uploads');
	$returnData->siteInfo["recommended"]["output_buffering"]    = (int) ini_get('output_buffering');
	$returnData->siteInfo["recommended"]["session_auto_start"]  = ini_get('session.auto_start');

	$returnData->siteInfo["recommended"]["zip_open"]            = function_exists('zip_open');
	$returnData->siteInfo["recommended"]["zip_read"]            = function_exists('zip_read');

	$returnData->siteInfo["required"]["zlib"]         = (bool) extension_loaded('zlib');
	$returnData->siteInfo["required"]["xml"]          = (bool) extension_loaded('xml');
	if (extension_loaded('mbstring'))
	{
		$returnData->siteInfo["required"]["mbstring_language_neutral"]  = strtolower(ini_get('mbstring.language')) === 'neutral';
		$returnData->siteInfo["required"]["mbstring_func_overload"]  = ini_get('mbstring.func_overload') == 0;
	}
	$returnData->siteInfo["required"]["json_encode"]  = function_exists('json_encode');
	$returnData->siteInfo["required"]["json_decode"]  = function_exists('json_decode');

	$returnData->siteInfo["required"]["parse_ini_string"]  =  function_exists('parse_ini_string') && !in_array('parse_ini_string', explode(',', ini_get('disable_functions')));

	$returnData->siteInfo["required"]["phpversion"]   =  version_compare(PHP_VERSION, '7.2.5', '>=');
	$returnData->siteInfo["required"]["dbsupported"]  =  !in_array(JFactory::getApplication()->get('dbtype'), array('sqlsrv', 'sqlazure'));

	/*
	$returnData->siteInfo["inidata"]["post_max_size (RAW)"] = ini_get('post_max_size');
	$returnData->siteInfo["inidata"]["upload_max_filesize  (RAW)"] = ini_get('upload_max_filesize');
	ob_start();
	phpinfo();
	$returnData->siteInfo["inidata"]["phpinfo"] = ob_get_clean();
	*/
	$returnData->JVERSION = JVERSION;

	return $returnData;
}

function getFolderData($requestObject, & $returnData)
{
	$basedir = JPATH_SITE;

	$returnData->error  = 0;
	$returnData->warning  = 0;

	$folderdata = array();
	$outputdata = array();
	try
	{
		if ($basedir !== false && $basedir != '' && file_exists($basedir))
		{
			foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($basedir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $object)
			{
				$path = $object->getPathname();
				// no need to pick up embedded clone sites - we won't copy these!
				if (strpos($path, '._ysts_') !== false)
				{
					continue;
				}
				if ($object->isDir())
				{
					$bytestotal = 0;
					// Go one level down only here - i.e. no subfolders
					foreach (new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS) as $object2)
					{
						$path2 = $object2->getPathname();
						$path2 = str_replace($basedir, '', $path2);
						if ($object2->isFile())
						{
							$bytestotal += $object2->getSize();
						}
						else if ($object2->isDir() && isset($folderdata[$path2]))
						{
							$bytestotal += $folderdata[$path2]['size'];
						}
					}

					$path = str_replace($basedir, '', $path);
					// site in megabytes
					$folderdata[$path] = array('size' => $bytestotal);
					$path              = trim($path, "/");
					if (count(explode("/", $path)) < 3)
					{
						$outputdata[$path] = array('size' => $bytestotal);
					}
				}
				else if (strpos($path , '/tmp') === false)
				{
					$x = 1;
				}
			}
		}
	}
	catch (Exception $e)
	{
		$returnData->warning  = 1;
		$returnData->folders  = array();
		$returnData->log      = $basedir;
		$returnData->log      = $e->getMessage();
	}

	ksort($outputdata);
	$returnData->basedir  = $basedir;
	$returnData->folders  = $outputdata;
}

function frontfatal($returnData)
{
	// This is how we could do the diagnosis in Joomla 3.x
	$jversion = new JVersion;

	$input = JFactory::getApplication()->input;

	// Skip Chosen in Joomla 4.x+
	if (!$jversion->isCompatible('4.0'))
	{
		//set_error_handler(array("YourSitesDiagnosis",'captureError'));
		$oldHandler = set_exception_handler(array("YourSitesDiagnosis",'captureException'));
		//register_shutdown_function(array("YourSitesDiagnosis",'captureShutdown'));

		$data = $input->getArray();
		foreach ($data as $k => $v)
		{
			// crude attempt to clear input which could affect the rendering of the home page!
			$input->set($k, '');
		}
	}

	// force HTML output here
	$input->set('format', 'html');
	JFactory::getDocument();

	$session = JFactory::getSession();
	$session->set('ysts_debug', 1);
}

if (!class_exists('YourSitesDiagnosis', false))
{
	class YourSitesDiagnosis
	{

		public static function captureError($errorCode, $message, $file, $line)
		{
			if (!(error_reporting() & $errorCode))
			{
				// This error code is not included in error_reporting, so let it fall
				// through to the standard PHP error handler
				//		YourSitesDiagnosis::throwerror('We have an unknown error = Code:' . $errorCode . " (" .$message . ') in file '. $file . ' at line: '. $line);
			}

			$errorCodes = array(
				E_ERROR             => "Fatal run-time error",
				E_WARNING           => "Run-time warning",
				E_PARSE             => "Compile-time parse error",
				E_NOTICE            => "Run-time notice",
				E_CORE_ERROR        => "PHP Core error",
				E_CORE_WARNING      => "PHP Core warning",
				E_COMPILE_ERROR     => "PHP Compile error",
				E_COMPILE_WARNING   => "PHP Compile warning",
				E_USER_ERROR        => "Fatal Error",
				E_USER_WARNING      => "PHP Warning",
				E_USER_NOTICE       => "PHP Notice",
				E_STRICT            => "Strict PHP warning",
				E_RECOVERABLE_ERROR => "Recoverable Error",
				E_DEPRECATED        => "Run time deprecation notice",
				E_USER_DEPRECATED   => "Deprecated Warning");

			$msg      = 'We have an error = Code:' . $errorCode;
			$messages = array();
			foreach ($errorCodes as $code => $errorCodeMessage)
			{
				if ($errorCode & $code)
				{
					$messages[] = $errorCodeMessage;
				}
			}
			if (count($messages))
			{
				$msg .= " [" . implode(", ", $messages) . "] ";
			}
			$msg .= ' : "' . $message . '", in file ' . $file . ' at line: ' . $line . "<br>";
			if (E_DEPRECATED & $errorCode || E_USER_DEPRECATED & $errorCode)
			{
				return;
			}
			if (E_ERROR & $errorCode || E_USER_ERROR & $errorCode)
			{
				YourSitesDiagnosis::throwerror($msg);
			}
		}

		// Attempt to catch the warning prior to the fatal error
		public static function captureFatalError($errorCode, $message, $file, $line)
		{
			if (!(error_reporting() & $errorCode))
			{
				// This error code is not included in error_reporting, so let it fall
				// through to the standard PHP error handler
				//		YourSitesDiagnosis::throwerror('We have an unknown error = Code:' . $errorCode . " (" .$message . ') in file '. $file . ' at line: '. $line);
			}

			if (E_DEPRECATED & $errorCode || E_USER_DEPRECATED & $errorCode)
			{
				return;
			}

			$errorCodes = array(
				E_ERROR             => "Fatal run-time error",
				E_WARNING           => "Run-time warning",
				E_PARSE             => "Compile-time parse error",
				E_NOTICE            => "Run-time notice",
				E_CORE_ERROR        => "PHP Core error",
				E_CORE_WARNING      => "PHP Core warning",
				E_COMPILE_ERROR     => "PHP Compile error",
				E_COMPILE_WARNING   => "PHP Compile warning",
				E_USER_ERROR        => "Fatal Error",
				E_USER_WARNING      => "PHP Warning",
				E_USER_NOTICE       => "PHP Notice",
				E_STRICT            => "Strict PHP warning",
				E_RECOVERABLE_ERROR => "Recoverable Error",
				E_DEPRECATED        => "Run time deprecation notice",
				E_USER_DEPRECATED   => "Deprecated Warning");

			$messages = array();
			foreach ($errorCodes as $code => $errorCodeMessage)
			{
				if ($errorCode & $code)
				{
					$messages[] = $errorCodeMessage;
				}
			}
			$messages[] = $message;
			$messages[] = "File $file at line $line";
			$msg        = implode("<br>", $messages);

			if (E_ERROR & $errorCode || E_USER_ERROR & $errorCode || E_WARNING & $errorCode || E_USER_WARNING & $errorCode)
			{
				YourSitesDiagnosis::throwerror($msg);
			}
		}

		public static function captureException(Throwable $exception)
		{
			if ($exception->getCode() === 0 || $exception->getCode() >= 500)
			{
				$data               = new stdClass();
				$data->messages     = array('COM_YOURSITES_FATAL_ERROR_INFORMATION');
				$data->messages[]   = $exception->getMessage();
				$data->messages[]   = $exception->getFile() . " : " . $exception->getLine();
				$data->log['trace'] = $exception->getTrace();
				$data->log['file']  = $exception->getFile();
				$data->log['line']  = $exception->getLine();

				$data->warning = 1;

				header("Content-Type: application/javascript");
				// Must suppress any error messages
				@ob_end_clean();
				echo json_encode($data);
				exit(0);
			}
			else
			{
				restore_exception_handler();

				$newException = new Exception('Chained Exception From YourSites', $exception->getCode(), $exception);

				throw $newException;
			}
		}

		public static function captureShutdown()
		{
			//YourSitesDiagnosis::throwerror( "Script is finishing now");
		}

		public static function throwerror($msg)
		{
			$data                = new stdClass();
			$data->errormessages = array($msg);
			$data->error         = 1;

			header("Content-Type: application/javascript");
			// Must suppress any error messages
			@ob_end_clean();
			echo json_encode($data);
			exit(0);
		}

	}
}