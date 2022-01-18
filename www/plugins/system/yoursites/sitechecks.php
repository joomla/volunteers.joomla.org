<?php

/**
 * @version    CVS: 1.26.0
 * @package    com_yoursites
 * @author     Geraint Edwards <via website>
 * @copyright  2016-2020 GWE Systems Ltd
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

/*
 * Joomla 3.7.x and earlier don't support these - leave them out until we MUST have them for Joomla 4.x
 */
/*
use Joomla\CMS\Access\Access;
Use Joomla\Filesystem\Folder;
Use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Component\ComponentHelper;
*/
defined('_JEXEC') or die;

class YstsSiteChecks
{
	public static function missing2factor( & $returnData, $requestObject)
	{

		$db = JFactory::getDbo();

		// find list of super users and admin users NOT using 2 factor Authentication

		// Step 1 - is 2 factor authentication enabled
		$query = $db->getQuery(true);
		$query->select('*')
			->from($db->quoteName("#__extensions"))
			->where('folder = "twofactorauth" AND enabled = 1');

		$db->setQuery($query);
		$twofactorplugins = $db->loadObjectList();

		$returnData->checkinfo = array();
		$returnData->checkinfo['require2factortoken'] = 0;

		if (count($twofactorplugins) == 0)
		{
			$returnData->warning = 1;
			$returnData->messages[] = "COM_YOURSITES_ADVCHECK_MISSING2FACTOR_FAILED";
			$returnData->checkinfo['key']  = "COM_YOURSITES_ADVCHECK_MISSING2FACTOR_INCORRECT_NO_PLUGIN";
			$returnData->checkinfo['data'] = array();
			$returnData->checkinfo['status'] = -1;
			return;
		}

		// Step 2 - any super users or admin users without  2 factor authentication enabled

		$query = $db->getQuery(true);
		$query->select('id')
			->from('#__usergroups');
		$db->setQuery($query);
		$levels = $db->loadColumn();

		// Get members of core.admin able user groups
		$rules = JAccess::getAssetRules(null);
		$ruledata = $rules->getData();
		// super users and administrators
		// use array_keys with search value of 1 !!
		$manageGroups    = isset($ruledata['core.manage']) ? array_keys($ruledata['core.manage']->getData(), 1) : array();
		$adminGroups     = array_keys($ruledata['core.admin']->getData(), 1);
		$coreadminGroups = array_merge($manageGroups, $adminGroups);

		$query = $db->getQuery(true);
		$query->select('u.username')
			->from("#__users as u" )
			->innerJoin('#__user_usergroup_map as m on m.user_id = u.id')
			->where('m.group_id in (' . implode(",", $coreadminGroups) . ')')
			->where('u.otpKey = ""')
			->where('u.block = 0')
			->group('u.id');

		$db->setQuery($query);

		$adminusers = $db->loadColumn();

		if (count($adminusers) !== 0)
		{
			$returnData->warning = 1;
			$returnData->messages[] = "COM_YOURSITES_ADVCHECK_MISSING2FACTOR_INCORRECT";
			//$returnData->messages[] = "Checked admin groups " . implode(", ", $adminGroups);
			//$returnData->messages[] = "Checked manage groups " . implode(", ", $manageGroups);

			$returnData->checkinfo['key']  = "COM_YOURSITES_ADVCHECK_MISSING2FACTOR_INCORRECT_R";
			$returnData->checkinfo['data'] = $adminusers;
			$returnData->checkinfo['status'] = -1;
		}

		if (count($twofactorplugins) > 0 && count($adminusers) == 0)
		{
			$returnData->checkinfo['key']  = "COM_YOURSITES_ADVCHECK_MISSING2FACTOR_CORRECT";
			$returnData->checkinfo['data'] = array();
			$returnData->checkinfo['status'] = 1;
		}

		$plugin = JPluginHelper::getPlugin("system" , "yoursites");
		if ($plugin)
		{
			$params      = json_decode($plugin->params);
			if (isset($params->check2factor) && $params->check2factor == 1)
			{
				$returnData->checkinfo['require2factortoken'] = 1;
			}
		}

	}

	public static function dormantspecials( & $returnData, $requestObject)
	{

		$db = JFactory::getDbo();

		$returnData->checkinfo = array();
		$returnData->checkinfo['data'] = array();
		$returnData->checkinfo['status'] = 1;
		$returnData->checkinfo['key']  = "COM_YOURSITES_ADVCHECK_DORMANTSPECIALS_CORRECT";

		// find list of super users and admin users and authors

		$query = $db->getQuery(true);
		$query->select('id')
			->from('#__usergroups');
		$db->setQuery($query);
		$levels = $db->loadColumn();

		// Get members of core.admin able user groups
		$rules = JAccess::getAssetRules(null);
		$ruledata = $rules->getData();
		// super users and administrators
		$createGroups  = isset($ruledata['core.create'])  ? array_keys($ruledata['core.create']->getData(), 1)  : array();
		$editGroups    = isset($ruledata['core.edit'])    ? array_keys($ruledata['core.edit']->getData(), 1)    : array();
		$publishGroups = isset($ruledata['core.publish']) ? array_keys($ruledata['core.publish']->getData(), 1) : array();
		$manageGroups  = isset($ruledata['core.manage'])  ? array_keys($ruledata['core.manage']->getData(), 1)  : array();
		$adminGroups   = isset($ruledata['core.admin'])   ? array_keys($ruledata['core.admin']->getData(), 1)   : array();

		$specialGroups = array_unique(array_merge($createGroups, $editGroups, $publishGroups, $manageGroups, $adminGroups));
		$specialGroups[] = -1;

		$cutoff = new JDate("- " . (int) $requestObject->dormantspecialstime . " months");
		$query = $db->getQuery(true);
		$query->select('CONCAT(u.username, " (" ,  u.lastvisitDate, ")")')
			->from("#__users as u" )
			->leftJoin('#__user_usergroup_map as m on m.user_id = u.id')
			->where('m.group_id in (' . implode(",", $specialGroups) . ')')
			->where('u.otpKey = ""')
			->where('u.lastvisitDate < ' . $db->quote($cutoff->toSql()))
			->where('u.block = 0')
			->group('u.id');

		$db->setQuery($query);

		$specialusers = $db->loadColumn();

		if (count($specialusers) !== 0)
		{
			$returnData->warning = 1;
			$returnData->messages[] = "COM_YOURSITES_ADVCHECK_DORMANTSPECIALS_INCORRECT";
			$returnData->checkinfo['key']  = "COM_YOURSITES_ADVCHECK_DORMANTSPECIALS_INCORRECT_R";
			$returnData->checkinfo['data'] = $specialusers;
			$returnData->checkinfo['status'] = -1;
		}

	}

	public static function usercaptcha( & $returnData, $requestObject)
	{
		$userparams = JComponentHelper::getParams("com_users");

		if (!$userparams->get("allowUserRegistration" , 0))
		{
			$returnData->checkinfo['key']  = "COM_YOURSITES_ADVCHECK_USERCAPTCHA_SKIPPED";
			$returnData->checkinfo['data'] = array();
			$returnData->checkinfo['status'] = 0;
			return;
		}

		$captcha = $userparams->get("captcha" , "");

		// if set to global then check global parameter
		if ($captcha === "")
		{
			$captcha = JFactory::getConfig()->get('captcha', '');
		}

		if (empty($captcha))
		{
			$returnData->warning = 1;
			$returnData->checkinfo['key']  = "COM_YOURSITES_ADVCHECK_USERCAPTCHA_INCORRECT";
			$returnData->checkinfo['data'] = array();
			$returnData->checkinfo['status'] = -1;
			return;
		}

		// Make sure the plugin is enabled!
		$plugin = JPluginHelper::getPlugin('captcha', $captcha);
		if (!$plugin) {
			$returnData->warning = 1;
			$returnData->checkinfo['key']  = "COM_YOURSITES_ADVCHECK_USERCAPTCHA_PLUGIN_NOT_ENABLED";
			$returnData->checkinfo['data'] = array();
			$returnData->checkinfo['status'] = -1;
			return;
		}

		$returnData->messages[] = "COM_YOURSITES_ADVCHECK_USERCAPTCHA_CORRECT";
		$returnData->checkinfo['key']  = "COM_YOURSITES_ADVCHECK_USERCAPTCHA_CORRECT";
		$returnData->checkinfo['data'] = array();
		$returnData->checkinfo['status'] = 1;

	}

	public static function contentversioning( & $returnData, $requestObject)
	{
		$contentparams = JComponentHelper::getParams("com_content");

		if ($contentparams->get("save_history" , 0))
		{
			$returnData->checkinfo['key']  = "COM_YOURSITES_ADVCHECK_CONTENTVERSIONING_CORRECT";
			$returnData->checkinfo['data'] = array();
			$returnData->checkinfo['status'] = 1;
		}
		else
		{
			$returnData->warning = 1;
			$returnData->checkinfo['key']  = "COM_YOURSITES_ADVCHECK_CONTENTVERSIONING_INCORRECT";
			$returnData->checkinfo['data'] = array();
			$returnData->checkinfo['status'] = -1;
		}

	}

	public static function livesite( & $returnData, $requestObject)
	{
		$live_site = JFactory::getConfig()->get('live_site', '');

		if (empty($live_site))
		{
			$returnData->checkinfo['key']  = "COM_YOURSITES_ADVCHECK_LIVE_SITE_CORRECT";
			$returnData->checkinfo['data'] = array();
			$returnData->checkinfo['status'] = 1;
		}
		else
		{
			$returnData->warning = 1;
			$returnData->checkinfo['key']  = "COM_YOURSITES_ADVCHECK_LIVE_SITE_INCORRECT";
			$returnData->checkinfo['data'] = array($live_site);
			$returnData->checkinfo['status'] = -1;
		}

	}

	public static function joomlaupdatesites( & $returnData, $requestObject)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('us.name, us.enabled')
			->from('#__update_sites AS us')
			->join('inner', '#__update_sites_extensions AS usx ON usx.update_site_id = us.update_site_id')
			->join('inner', '#__extensions AS ex ON ex.extension_id = usx.extension_id')
			->where('(ex.type="component" AND ex.element="com_joomlaupdate") OR (ex.type="file" AND ex.element="joomla") AND us.enabled = 1');

		$db->setQuery($query);
		$results = $db->loadObjectList();

		if (!empty($results) && count($results) >= 2 && $results[0]->enabled && $results[1]->enabled )
		{
			$returnData->checkinfo['key']  = "COM_YOURSITES_ADVCHECK_JOOMLAUPDATESITES_CORRECT";
			$returnData->checkinfo['data'] = array();
			$returnData->checkinfo['status'] = 1;
		}
		else
		{
			$returnData->warning = 1;
			$returnData->checkinfo['key']  = "COM_YOURSITES_ADVCHECK_JOOMLAUPDATESITES_INCORRECT";
			$names = array();
			foreach ($results as $result)
			{
				$names[] = $result->name;
			}
			$returnData->checkinfo['data'] = $names;
			$returnData->checkinfo['status'] = -1;
		}

	}

	public static function extensionupdatesites( & $returnData, $requestObject)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('us.name, us.enabled, ex.type, ex.folder, ex.client_id, us.update_site_id')
			->from('#__update_sites AS us')
			->join('inner', '#__update_sites_extensions AS usx ON usx.update_site_id = us.update_site_id')
			->join('inner', '#__extensions AS ex ON ex.extension_id = usx.extension_id')
			->where('NOT((ex.type="component" AND ex.element="com_joomlaupdate") OR (ex.type="file" AND ex.element="joomla")) AND us.enabled = 0');

		if (isset($requestObject->checkdata->extensionupdatesites) && count($requestObject->checkdata->extensionupdatesites) > 0)
		{
			$query->where('ex.extension_id NOT IN (' . implode(',' , $requestObject->checkdata->extensionupdatesites) . ')');
		}
		$db->setQuery($query);
		$results = $db->loadObjectList();


		if (empty($results))
		{
			$returnData->checkinfo['key']  = "COM_YOURSITES_ADVCHECK_EXTENSIONUPDATESITES_CORRECT";
			$returnData->checkinfo['data'] = array();
			$returnData->checkinfo['status'] = 1;
		}
		else
		{
			$returnData->warning = 1;
			$returnData->messages[] = "COM_YOURSITES_ADVCHECK_EXTENSIONUPDATESITES_INCORRECT";
			$returnData->checkinfo['key']  = "COM_YOURSITES_ADVCHECK_EXTENSIONUPDATESITES_INCORRECT_R";
			$names = array();
			foreach ($results as $result)
			{
				$names[] = $result->name . " (" .  ($result->client_id ? 'Administrator' : 'Site') . ") : " .  $result->type. " - " .  $result->folder . " - " .  $result->update_site_id;
			}
			$returnData->checkinfo['data'] = $names;
			$returnData->checkinfo['status'] = -1;
		}

	}

	public static function sendpassword( & $returnData, $requestObject)
	{
		$userparams = JComponentHelper::getParams("com_users");

		$sendpassword = $userparams->get("sendpassword" , 0);
		$allowUserRegistration = $userparams->get("allowUserRegistration" , 0);

		if (!$allowUserRegistration)
		{
			$returnData->checkinfo['key']  = "COM_YOURSITES_ADVCHECK_SENDPASSWORD_SKIPPED";
			$returnData->checkinfo['data'] = array();
			$returnData->checkinfo['status'] = 0;
			return;
		}

		if ($sendpassword)
		{
			$returnData->warning = 1;
			$returnData->checkinfo['key']  = "COM_YOURSITES_ADVCHECK_SENDPASSWORD_INCORRECT";
			$returnData->checkinfo['data'] = array();
			$returnData->checkinfo['status'] = -1;
			return;
		}

		$returnData->checkinfo['key']  = "COM_YOURSITES_ADVCHECK_SENDPASSWORD_CORRECT";
		$returnData->checkinfo['data'] = array();
		$returnData->checkinfo['status'] = 1;

	}

	public static function weakpasswords( & $returnData, $requestObject)
	{
		$userparams = JComponentHelper::getParams("com_users");

		$minimum_length    = intval($userparams->get("minimum_length" , 0));
		$minimum_integers  = intval($userparams->get("minimum_integers" , 0)) > 0 ? 1 : 0;
		$minimum_symbols   = intval($userparams->get("minimum_symbols" , 0)) > 0 ? 1 : 0;
		$minimum_uppercase = intval($userparams->get("minimum_uppercase" , 0)) > 0 ? 1 : 0;

		$allowUserRegistration = $userparams->get("allowUserRegistration" , 0);

		if (!$allowUserRegistration)
		{
			$returnData->checkinfo['key']  = "COM_YOURSITES_ADVCHECK_WEAKPASSWORDS_SKIPPED";
			$returnData->checkinfo['data'] = array();
			$returnData->checkinfo['status'] = 0;
			return;
		}

		if ($minimum_length < 8 || ($minimum_integers + $minimum_symbols + $minimum_uppercase) < 2)
		{
			$returnData->warning = 1;
			$returnData->checkinfo['key']  = "COM_YOURSITES_ADVCHECK_WEAKPASSWORDS_INCORRECT";
			$returnData->checkinfo['data'] = array();
			$returnData->checkinfo['status'] = -1;
			return;
		}

		$returnData->checkinfo['key']  = "COM_YOURSITES_ADVCHECK_WEAKPASSWORDS_CORRECT";
		$returnData->checkinfo['data'] = array();
		$returnData->checkinfo['status'] = 1;

	}

	public static function writablefiles( & $returnData, $requestObject)
	{
		/*
		Check that the following paths can be written to

		JConfig::log_path
		JConfig::tmp_path

		Cache path - need to check the caching settings first

		*/

		$ftp_enable = JFactory::getConfig()->get('ftp_enable', 0);

		if ($ftp_enable)
		{
			$returnData->checkinfo['key']  = "COM_YOURSITES_ADVCHECK_WRITABLEFILES_SKIPPED";
			$returnData->checkinfo['data'] = array();
			$returnData->checkinfo['status'] = 0;
			return;
		}

		$tmpDir  = JFactory::getConfig()->get('tmp_path', JPATH_ROOT . '/tmp');
		$logDir  = JFactory::getConfig()->get('log_path', JPATH_ROOT . '/log');

		if (!is_writable($tmpDir ))
		{
			$returnData->warning = 1;
			$returnData->messages[] = "COM_YOURSITES_ADVCHECK_WRITABLEFILES_INCORRECT";
			$returnData->messages[] = "COM_YOURSITES_ADVCHECK_WRITABLEFILES_INCORRECT_TMPDIR";
			$returnData->checkinfo['key']  = "COM_YOURSITES_ADVCHECK_WRITABLEFILES_INCORRECT";
			$returnData->checkinfo['data'] = array();
			$returnData->checkinfo['status'] = -1;
		}

		if (!is_writable($logDir ))
		{
			if (!isset($returnData->warning) || !$returnData->warning)
			{
				$returnData->messages[] = "COM_YOURSITES_ADVCHECK_WRITABLEFILES_INCORRECT";
			}
			$returnData->messages[] = "COM_YOURSITES_ADVCHECK_WRITABLEFILES_INCORRECT_LOGDIR";
			$returnData->warning = 1;
			$returnData->checkinfo['key']  = "COM_YOURSITES_ADVCHECK_WRITABLEFILES_INCORRECT";
			$returnData->checkinfo['data'] = array();
			$returnData->checkinfo['status'] = -1;
		}

		if (!isset($returnData->warning) || !$returnData->warning)
		{
			$returnData->checkinfo['key']    = "COM_YOURSITES_ADVCHECK_WRITABLEFILES_CORRECT";
			$returnData->checkinfo['data']   = array();
			$returnData->checkinfo['status'] = 1;
		}

	}

	public static function customconfig( & $returnData, $requestObject)
	{
		if (isset($requestObject->customconfig))
		{
			$requestObject->customconfig = (array) @json_decode(base64_decode($requestObject->customconfig));
			if (!isset($returnData->customresults))
			{
				$returnData->customresults = array();
			}
			foreach ($requestObject->customconfig as $customconfigidx => $customconfig)
			{
				$customresult = new stdClass();
				$customresult->checkinfo = array();
				$customresult->messages = array();
				$customresult->fieldname = $customconfig->configfield;
				$customresult->fieldvalue = JFactory::getConfig()->get($customconfig->configfield, '');
				$customresult->testvalue = $customconfig->configfieldcontent;
				$customresult->valid = false;

				switch ($customconfig->configfieldoperator)
				{
					case "eq" :
						$customresult->valid = ($customresult->fieldvalue === $customresult->testvalue);
						break;
					case "neq" :
						$customresult->valid = ($customresult->fieldvalue !== $customresult->testvalue);
						break;
					case "gt" :
						$customresult->valid = ($customresult->fieldvalue > $customresult->testvalue);
						break;
					case "gte" :
						$customresult->valid = ($customresult->fieldvalue >= $customresult->testvalue);
						break;
					case "lt" :
						$customresult->valid = ($customresult->fieldvalue < $customresult->testvalue);
						break;
					case "lte" :
						$customresult->valid = ($customresult->fieldvalue <= $customresult->testvalue);
						break;
					case "contains" :
						$customresult->valid = strpos($customresult->fieldvalue, $customresult->testvalue) !== false;
						break;
					case "starts" :
						$customresult->valid = strpos($customresult->fieldvalue, $customresult->testvalue) === 0;
						break;
					case "notcontains" :
						$customresult->valid = strpos($customresult->fieldvalue, $customresult->testvalue) === false;
						break;
					case "notstarts" :
						$customresult->valid = strpos($customresult->fieldvalue, $customresult->testvalue) !== 0;
						break;
				}

				$customresult->checkinfo['data'] = array();

				if ($customresult->valid)
				{
					$customresult->messages[] = "COM_YOURSITES_ADVCHECK_CUSTOMCONFIG_CORRECT";
					$customresult->checkinfo['key']  = str_replace(array('{field}', '{value}'), array($customconfig->configfield, $customresult->fieldvalue),$customconfig->configfieldsuccess);
					$customresult->checkinfo['data']   = array();
					$customresult->checkinfo['status'] = 1;
				}
				else
				{
					$customresult->messages[] = "COM_YOURSITES_ADVCHECK_CUSTOMCONFIG_INCORRECT";
					$customresult->checkinfo['key']  = str_replace(array('{field}', '{value}'), array($customconfig->configfield, $customresult->fieldvalue),$customconfig->configfieldfailure);
					$customresult->checkinfo['data']   = array();
					$customresult->checkinfo['status'] = -1;
				}

				$returnData->customresults[$customconfigidx] = $customresult;
			}
		}
		else
		{
			$returnData->warning = 1;
			$returnData->checkinfo['key']  = "COM_YOURSITES_ADVCHECK_CUSTOMCONFIG_INCORRECT";
			$returnData->checkinfo['data'] = array();
			$returnData->checkinfo['status'] = -1;
		}

	}

	public static function extrachecks( & $returnData, $requestObject)
	{
		if (isset($requestObject->extrachecks))
		{
			$requestObject->extrachecks = (array) @json_decode(base64_decode($requestObject->extrachecks));
			if (!isset($returnData->customresults))
			{
				$returnData->customresults = array();
			}
			foreach ($requestObject->extrachecks as $extracheck)
			{
				$customresult = new stdClass();
				$customresult->checkinfo = array();
				$customresult->checkinfo['data'] = array();
				$customresult->checkinfo['key']  = "COM_YOURSITES_ADVCHECK_EXTRA_CHECK_INCORRECT";
				$customresult->checkinfo['status'] = -1;
				$customresult->messages = array();

				$extracheck = $extracheck;
				try
				{
					include_once 'customchecks/' . $extracheck;

					$classname = "Ysts" . ucfirst(basename($extracheck, '.php')) . "Checks";

					$customresult->checkname = $classname::$checkname;

					@$classname::performCheck($customresult);

				}
				catch (Throwable $e)
				{
					$customresult->error = 1;
					$customresult->warning = 1;
					$customresult->checkinfo['key']  = "COM_YOURSITES_ADVCHECK_EXTRA_CHECK_INCORRECT";
					$customresult->checkinfo['status'] = -1;
					$customresult->messages[] = $e->getMessage();
					$customresult->checkname = basename($extracheck, '.php');

				}
				$returnData->customresults[$customresult->checkname] = $customresult;
			}
		}
		else
		{
			$returnData->warning = 1;
			$returnData->checkinfo['key']  = "COM_YOURSITES_ADVCHECK_EXTRA_CHECK_INCORRECT";
			$returnData->checkinfo['data'] = array();
			$returnData->checkinfo['status'] = -1;
		}

	}

	public static function customfiles( & $returnData, $requestObject)
	{
		if (isset($requestObject->customfiles))
		{
			$requestObject->customfiles = (array) @json_decode(base64_decode($requestObject->customfiles));
			if (!isset($returnData->customresults))
			{
				$returnData->customresults = array();
			}
			foreach ($requestObject->customfiles as $customfilesidx => $customfiles)
			{
				$customresult = new stdClass();
				$customresult->checkinfo = array();
				$customresult->messages = array();
				$customresult->filepath = $customfiles->filepath;
				$customresult->filecontent = $customfiles->filecontent;
				$customresult->valid = false;

				switch ($customfiles->fileoperator)
				{
					case "exists" :
						$customresult->valid = JFile::exists(str_replace("//", "/", JPATH_SITE . "/" . $customresult->filepath));
						break;
					case "notexists" :
						$customresult->valid = !JFile::exists(str_replace("//", "/", JPATH_SITE . "/" . $customresult->filepath));
						break;
					case "contains" :
						$file_content = file_get_contents(str_replace("//", "/", JPATH_SITE . "/" . $customresult->filepath));
						$customresult->valid = strpos($file_content, $customresult->filecontent) !== false;
						break;
					case "notcontains" :
						$file_content = file_get_contents(str_replace("//", "/", JPATH_SITE . "/". $customresult->filepath));
						$customresult->valid = strpos($file_content, $customresult->filecontent) === false;
						break;
				}

				$customresult->checkinfo['data'] = array();

				if ($customresult->valid)
				{
					$customresult->messages[] = "COM_YOURSITES_ADVCHECK_CUSTOMFILES_CORRECT";
					$customresult->checkinfo['key']  = str_replace(array('{file}'), array($customfiles->filepath),$customfiles->filesuccess);
					$customresult->checkinfo['data']   = array();
					$customresult->checkinfo['status'] = 1;
				}
				else
				{
					$customresult->messages[] = "COM_YOURSITES_ADVCHECK_CUSTOMFILES_INCORRECT";
					$customresult->checkinfo['key']  = str_replace(array('{file}'), array($customfiles->filepath),$customfiles->filefailure);
					$customresult->checkinfo['data']   = array();
					$customresult->checkinfo['status'] = -1;
				}

				$returnData->customresults[$customfilesidx] = $customresult;
			}
		}
		else
		{
			$returnData->warning = 1;
			$returnData->checkinfo['key']  = "COM_YOURSITES_ADVCHECK_CUSTOMFILES_INCORRECT";
			$returnData->checkinfo['data'] = array();
			$returnData->checkinfo['status'] = -1;
		}

	}

}
