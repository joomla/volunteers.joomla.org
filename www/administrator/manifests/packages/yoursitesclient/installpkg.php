<?php

/**
 * @version    CVS: 1.26.0
 * @package    com_yoursites
 * @author     Geraint Edwards
 * @copyright  2017-2020 GWE Systems Ltd
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 */
// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

class pkg_YoursitesclientInstallerScript
{

	public function preflight($type, $parent)
	{

		if (version_compare(PHP_VERSION, "5.5.0", '<'))
		{
			throw new \RuntimeException(
			'Your host needs to use PHP ' . "5.5.0" . ' or later to use YourSites'
			);
			return false;
		}

		if (!in_array("sha256", hash_algos()))
		{
			$installer = $parent->getParent();
			$installer->set('extension_message', '<strong>' . JText::_("PKG_YOURSITESCLIENT_SHA256_HASH_ALGORITHM_NOT_SUPPORTED_PLEASE_ROLL_BACK") . '</strong>');
			//$installer->set('message', JText::_("PKG_YOURSITESCLIENT_SHA256_HASH_ALGORITHM_NOT_SUPPORTED_PLEASE_ROLL_BACK"));
			$installer->set('message', '');

			// The script failed, rollback changes
			throw new \RuntimeException(
				JText::_("PKG_YOURSITESCLIENT_SHA256_HASH_ALGORITHM_NOT_SUPPORTED_PLEASE_ROLL_BACK")
			);

			return false;
		}
	}

	public function update()
	{

		return true;
	}

	public function install($adapter)
	{
		return true;
	}

	public function uninstall($adapter)
	{

	}



	/*
	 * enable the plugins
	 */
	function postflight($type, $parent)
	{
		// Needed for Joomla 4
		if ($type !== 'install' && $type !== 'update')
		{
			return true;
		}

		JLog::addLogger(array('text_file' => 'yoursites.php'), JLog::ALL, array('yoursites'));

		//JLog::add("Starting postflight", JLog::INFO, 'yoursites');
		$this->diagnose("Starting postflight !", 'warning');

		// $parent is the class calling this method
		// $type is the type of change (install, update or discover_install)

		/*
		 * No longer needed
		 */
		/*
		if ($type == 'install' || $type == 'update')
		{
			// enable gwejson plugin - handler plugin is enabled later

			$db    = JFactory::getDbo();
			$query = "UPDATE #__extensions SET enabled=1 WHERE folder='system' and type='plugin' and element='gwejson'";
			$db->setQuery($query);
			$db->execute();
		}
		*/

		// Sepcific Token
		$specifictoken = '$2y$10$yG1vnR/TrUlr2qg9HmVW1eNyh.YCKGCIMsu7HzrykYyFAVMxbDGPW';

		// Generic Token
		$generictoken = '$2y$10$grDg8y.r2kSl1/nFBClJ3O9kPakSUEjqcv5QQkfa0rXoki7xJMRGm';

		$tokenToUse = empty($specifictoken) ? $generictoken : $specifictoken;

		if ($type == 'install')
		{
			$eparams = json_decode('{"checkserverdomain":"0","serverdomain":"","allowdirectlogin":"0","checkserverip":"0","serverip":"","checkservertoken":"1","servertoken":"' . $tokenToUse . '"}');

			$this->diagnose("Setting up the authentication 1 !", 'warning');
			// Set up generic parameters
			$db    = JFactory::getDbo();
			$query = "UPDATE #__extensions "
				. "SET enabled=1, state=1,"
				. ' params = ' . $db->quote(json_encode($eparams))
				. " WHERE folder='system' and type='plugin' and element='yoursites'";

			$db->setQuery($query);
			$db->execute();
		}

		if ($type == 'update')
		{
			// Do we have settings from old handler plugin which we need to migrate?
			$db    = JFactory::getDbo();
			$query = "SELECT * FROM #__extensions "
				. " WHERE folder='system' and type='plugin' and element='yoursites'";
			$db->setQuery($query);
			$newyoursites = $db->loadObject();

			// new site params may have been set at default values during install so we check servertoken value too
			$servertoken = "";
			if (!empty($newyoursites->params))
			{
				$nyp = new JRegistry($newyoursites->params);
				$servertoken = $nyp->get("servertoken", "");
			}

			if (empty($newyoursites->params) || empty ($servertoken))
			{

				$db    = JFactory::getDbo();
				$query = "SELECT * FROM #__extensions "
					. " WHERE folder='yoursites' and type='plugin' and element='handler'";
				$db->setQuery($query);
				$oldyoursites = $db->loadObject();

				if (!empty($oldyoursites->params))
				{
					$query = "UPDATE #__extensions "
						. " SET params = " . $db->quote($oldyoursites->params)
						. " WHERE folder='system' and type='plugin' and element='yoursites'";
					$db->setQuery($query);
					$db->execute();
				}
			}

			// move old gwejson handler - pending deletion at a later date
			if (JFile::exists(JPATH_PLUGINS . '/yoursites/handler/gwejson_getupdatedata.php'))
			{
				JFile::move(JPATH_PLUGINS . '/yoursites/handler/gwejson_getupdatedata.php', JPATH_PLUGINS . '/yoursites/handler/KEEPgwejson_getupdatedata.php');
			}

			// Replace generic headers (fetching data afresh in case its been updated)
			$db    = JFactory::getDbo();
			$query = "SELECT * FROM #__extensions "
				. " WHERE folder='system' and type='plugin' and element='yoursites'";
			$db->setQuery($query);
			$extension = $db->loadObject();

			if (empty($extension->params))
			{
				$this->diagnose("Extension params empty!", 'warning');

				$eparams = json_decode('{"checkserverdomain":"0","serverdomain":"","allowdirectlogin":"0","checkserverip":"0","serverip":"","checkservertoken":"1","servertoken":"' . $tokenToUse . '"}');

				$query = "UPDATE #__extensions "
					. "SET enabled=1, state=1,"
					. ' params = ' . $db->quote(json_encode($eparams))
					. " WHERE folder='system' and type='plugin' and element='yoursites'";
				$db->setQuery($query);
				$db->execute();
			}
			else
			{
				$eparams = json_decode($extension->params);
				// if disabled centrally then disable locally too but NOT the other way around
				if ("0" === "0")
				{
					$eparams->allowdirectlogin = "0";
				}
			}

			// Always replace server token if we have a non-generic one in the package
			if (!empty($specifictoken))
			{
				JFactory::getApplication()->enqueueMessage(JText::_("PKG_YOURSITESCLIENT_SETTING_UP_SITE_SPECIFIC_TOKEN"), 'info');

				// This is the specific token
				$eparams->servertoken = $specifictoken;
				$query                = "UPDATE #__extensions "
					. "SET enabled=1, state=1,"
					. ' params = ' . $db->quote(json_encode($eparams))
					. " WHERE folder='system' and type='plugin' and element='yoursites'";
				$db->setQuery($query);
				$db->execute();
			}
		}

		// Bootstrap connection to server

		// Extension Details
		$db    = JFactory::getDbo();
		$query = "SELECT * FROM #__extensions "
			. " WHERE folder='system' and type='plugin' and element='yoursites'";
		$db->setQuery($query);
		$extension       = $db->loadObject();
		$extensionParams = json_decode($extension->params);

		// Should we secure the connection?
		$secureTheConnection = false;

		// A new install
		if ($type == 'install')
		{
			$secureTheConnection = true;
		}

		//If the specifictoken has actually been set
		if (!empty($specifictoken))
		{
			$secureTheConnection = true;
		}

		// If the servertoken is set and doesn't match the existing value then either we are installing directly
		// OR we have passed the security check from YourSites.
		// In either case we don't need to call back for a new token!
		if (!empty($specifictoken)	&& (isset($extensionParams->servertoken) || $extensionParams->servertoken != $specifictoken))
		{
			$secureTheConnection = false;
		}

		// If the specific token has NOT been set i.e. this is a generic install
		if (empty($specifictoken))
		{
			$secureTheConnection = true;
		}

		JLog::add("Should we connect to server? connect to server && ". ($secureTheConnection ? 'true' : 'false') , JLog::INFO, 'yoursites');
		$this->diagnose("Should we connect to server? connect to server && ". ($secureTheConnection ? 'true' : 'false'), 'warning');

		// This process is secured using Generic Token only - but doing so created a specific connection!
		if ("connect to server" === "connect to server" && $secureTheConnection)
		{

			$debug = "&XDEBUG_SESSION_START=PHPSTORM";
			$debug = "";

			// Use JHttpFactory that allows using CURL and Sockets as alternative method when available
			// Adding a valid user agent string etc.
			$goptions = new JRegistry;
			$goptions->set('userAgent', 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:41.0) Gecko/20100101 Firefox/41.0');
			$http = JHttpFactory::getHTTP($goptions);

			$path = "index.php?option=com_yoursites&task=site.register&tmpl=component";
			// enable debug ?
			$path .= $debug;

			// could pass token in headers via $headers argument etc.
			$headers = array();

			$data             = array();
			$config           = new JConfig();
			$data["sitename"] = $config->sitename;

			if (isset($extensionParams->allowdirectlogin) && !$extensionParams->allowdirectlogin)
			{
				$data["superuser"] = -1;
			}
			else
			{
				if (isset($extensionParams->dluser) && intval($extensionParams->dluser) > 0 )
				{
					$data["superuser"] = intval($extensionParams->dluser);
				}
				else
				{
					$user = JFactory::getUser();
					$data["superuser"] = $user->get('id');

					// Anonymous installation e.g. Watchful or YourSites update
					if ($data["superuser"] == 0)
					{
						$db    = JFactory::getDbo();
						$query = "SELECT * FROM #__users"
							. " WHERE username = ". $db->quote('sodnliwurbeniouwnefp9wuinefpiubweifubperugbiw[0239rjpkrmv-98n23');
						$db->setQuery($query);
						$directloginuser = $db->loadObject();

						if ($directloginuser && $directloginuser->id)
						{
							$data["superuser"] = $directloginuser->id;
						}
					}
					else
					{
						$data["superuser"] = -1;
					}
				}
			}

			// $base needs to be frontend and not have /administrator at the end but does need the trailing /
			$base        = JURI::base(false);
			//JLog::add("base url is " . $base , JLog::INFO, 'yoursites');
			$this->diagnose("base url is " . $base, 'warning');
			if (strpos($base, "/administrator"))
			{
				$pos  = strrpos($base, "/administrator");
				$base = substr($base, 0, $pos) . "/";
				//JLog::add("modified base url is " . $base , JLog::INFO, 'yoursites');
				$this->diagnose("modified base url is " . $base, 'warning');
			}
			$data["url"] = $base;

			$data["token"] = password_hash(uniqid(mt_rand() . $generictoken , true), PASSWORD_DEFAULT, array('cost' => 10));
			if (!empty($specifictoken))
			{
				$data["hash"]  = password_hash(hash('sha256',$data["token"] . "combined with" . $specifictoken), PASSWORD_DEFAULT, array('cost' => 10));
				$data["generichash"]  = password_hash(hash('sha256',$data["token"] . "combined with" . $generictoken), PASSWORD_DEFAULT, array('cost' => 10));
			}
			else
			{
				$data["hash"]  = password_hash(hash('sha256',$data["token"] . "combined with" . $generictoken), PASSWORD_DEFAULT, array('cost' => 10));
				$data["generichash"]  = password_hash(hash('sha256',$data["token"] . "combined with" . $generictoken), PASSWORD_DEFAULT, array('cost' => 10));
			}
			if (defined('JVERSION'))
			{
				$data["coreversion"] = JVERSION;
			}
			$data["pluginversion"] = "1.26.0";

			$yoursitesUrl = "https://manage.joomla.org/";

			JLog::add("Connecting to https://manage.joomla.org/ YourSites server " . $yoursitesUrl . $path, JLog::INFO, 'yoursites');
			$this->diagnose("Connecting to https://manage.joomla.org/ YourSites server " . $yoursitesUrl . $path, 'warning');

			// This doesn't work if yoursites server is not in DNS
			try
			{
				$webpage = $http->post($yoursitesUrl . $path, $data); //, $headers);
				//$webpage = $http->post(str_replace('wp-yoursites.net', 'dockfgherslknfg9o34n.net', $yoursitesUrl) . $path, $data); //, $headers);
                JLog::add("Got response from https://manage.joomla.org/ YourSites server " . $webpage->code , JLog::INFO, 'yoursites');
				$this->diagnose("Got response from https://manage.joomla.org/ YourSites server " . $webpage->code, 'warning');
			}
			catch (Exception $e)
			{
				/*
				// Try a javascript/browser based connection
				?>
				<script type="text/javascript">
					function ystsProcessJson(data) {
                        console.log(data);
					}
				</script>
				<script type="text/javascript" id="jsregistration"  src="<?php echo $yoursitesUrl . str_replace('site.register', 'site.jsregister', $path) . "&XDEBUG_SESSION_START=PHPSTORM" . "&data=" . base64_encode(json_encode($data)); ?>"></script>
				<?php
				*/
				$webpage       = new stdClass();
				$webpage->body = "ERROR";
				JFactory::getApplication()->enqueueMessage(JText::sprintf("PKG_YOURSITESCLIENT_UNABLE_TO_LINK_THIS_SITE_TO_THE_YOURSITES_SERVER_AT", $yoursitesUrl), 'error');
				//JFactory::getApplication()->enqueueMessage(JText::sprintf("PKG_YOURSITESCLIENT_ATTEMPTING_JS_CONNECTION_TO_THE_YOURSITES_SERVER_AT", $yoursitesUrl), 'warning');
				JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');
				JLog::add("Unable to post to https://manage.joomla.org/ YourSites server ", JLog::INFO, 'yoursites');
			}

			JLog::add($webpage->body , JLog::INFO, 'yoursites');

			// ToDo - add meaningful completion message based on json return data
			//$this->diagnose(" url  = " . $yoursitesUrl . $path);
			//$this->diagnose(" code = " . $webpage->code);
			//$this->diagnose(" page = " . $webpage->body);

			if (strpos($webpage->body, "{") !== false)
			{
				try
				{
					//JLog::add("Decoding JSON from YourSites server", JLog::INFO, 'yoursites');
					//JLog::add($webpage->body, JLog::INFO, 'yoursites');
					$updatedata = json_decode($webpage->body);

					$returnToken = isset($updatedata->returnToken) ? $updatedata->returnToken : false;
					$returnHash = isset($updatedata->returnHash) ? $updatedata->returnHash: false;

					// $this->diagnose($returnToken  . " " . $returnHash);

					if (!$updatedata || $updatedata->error)
					{
						JFactory::getApplication()->enqueueMessage(JText::sprintf("PKG_YOURSITESCLIENT_UNABLE_TO_LINK_THIS_SITE_TO_THE_YOURSITES_SERVER_AT", $yoursitesUrl), 'error');
						if (isset($updatedata->errormessages))
						{
							foreach ($updatedata->errormessages as $errormessage)
							{
								JFactory::getApplication()->enqueueMessage(JText::_($errormessage, true));
							}
						}
					}
					else if ($updatedata->privatekey && $returnToken && $returnHash && password_verify(hash('sha256', $returnToken . " combined with " . $generictoken  ), $returnHash))
					{

						// JLog::add("Have private key from YourSites server", JLog::INFO, 'yoursites');
						// $this->diagnose($returnToken  . " " . $returnHash);

						// Replace generic headers
						$params              = json_decode($extension->params);
						$params->servertoken = $updatedata->privatekey;

						$query = "UPDATE #__extensions "
							. "SET enabled=1, state=1,"
							. " params = " . $db->quote(json_encode($params))
							. " WHERE folder='system' and type='plugin' and element='yoursites'";
						$db->setQuery($query);
						$db->execute();
						JFactory::getApplication()->enqueueMessage(JText::sprintf("PKG_YOURSITESCLIENT_SITE_SECURELY_CONNECTED", $yoursitesUrl));
					}
				}
				catch (Exception $e)
				{
					JLog::add("Decoding JSON from YourSites server FAILED", JLog::ERROR, 'yoursites');

					JFactory::getApplication()->enqueueMessage($e->getMessage());
					JFactory::getApplication()->enqueueMessage($webpage->body);
				}
			}
			else
				{
					JFactory::getApplication()->enqueueMessage(JText::sprintf("PKG_YOURSITESCLIENT_UNABLE_TO_LINK_THIS_SITE_TO_THE_YOURSITES_SERVER_AT", $yoursitesUrl), 'error');
					$this->diagnose($webpage->body, 'error');
				}

		}

		// enable yoursites system plugin - just in case its not enabled yet!
		$db    = JFactory::getDbo();
		$query = "UPDATE #__extensions SET enabled=1 WHERE folder='system' and type='plugin' and element='yoursites'";
		$db->setQuery($query);
		$db->execute();

		// disable old handler plugin pending uninstalling at later date
		$query = "UPDATE #__extensions SET enabled=0 WHERE folder='yoursites' and type='plugin' and element='handler'";
		$db->setQuery($query);
		$db->execute();

	    // Clear cache of com_config and com_plugins components
		$options = array(
			'defaultgroup' => '_system',
			'cachebase'    => JPATH_ADMINISTRATOR . '/cache'
		);
		$cache   = JCache::getInstance('callback', $options);
		$cache->clean();
		
		$options = array(
			'defaultgroup' => 'com_plugins',
			'cachebase'    => JPATH_ADMINISTRATOR . '/cache'
		);
		$cache   = JCache::getInstance('callback', $options);
		$cache->clean();

		// This is the key one!
		$cache = JFactory::getCache('com_plugins', 'callback');
		$cache->clean();

        return;
		$path = "index.php?option=com_yoursites&task=site.jsregister&tmpl=component";
		$path .= "&XDEBUG_SESSION_START=PHPSTORM"
		?>
		<script>
            // JSON/Cookie based solution to connecting sites to server
            let data =  new FormData ();
            <?php
            foreach ($data as $k => $v)
                {
                    ?>
                    data.append ('<?php echo $k; ?>', '<?php echo $v;?>');
                    <?php
                }
            ?>

            alert('https://manage.joomla.org/<?php echo  $path; ?>');

            fetch('https://manage.joomla.org/<?php echo $path; ?>',
	            {
		            method : 'POST',
		            mode : 'cors',
		            headers: {
			            'Accept': 'application/json'
                        // we must not set content-type: multipart/form-data since it won't do the boundaries correctly.  Leave it blank!
		            },
		            body: data
	            }
            )
	            .then(
		            function(response) {
			            // This step we get the data from the response
			            //console.log('then ' + response.status);
			            let body = response.text();
			            // Can't use response.body - its usually hidden by Firefox
			            //let body = response.body;
			            console.log(body);
			            //return response.json();
			            return  body;
		            }
	            )
	            .catch(
		            error => console.error('Error 1:', error.message)
	            )
	            .then(
		            function(myJson) {
			            console.log('myJson = ' + myJson);
		            }
                );
        </script>
        <!--
        <textarea name="testclipboard" id="testclipboard" >This is some special text.</textarea>
        <input type="button" id="clipboardbutton" value="Copy to clipboard" style="'display:none!important;"/>
        //-->
        <script>
            /*
		// Clipboard based solution to connecting sites to server
        try {
	        navigator.permissions.query({name: "clipboard-write"}).then(result => {
		        if (result.state == "granted" || result.state == "prompt") {
			        alert('permission prompt');
			        let input = document.getElementById('testclipboard');
			        input.focus();
			        // write to the clipboard now
			        navigator.clipboard.writeText("<empty clipboard>").then(function () {
				        // clipboard successfully set
				        alert(1);
			        })
                        .catch(err => {
				        // clipboard write failed
				        alert(err);
			        });
		        } else if (result.state == "denied") {
			        alert('permission denied falling back ');
		        }
	        })
            .catch(err => {
                // This can happen if the user denies clipboard permissions:
                alert('not denied but failed - need to use fall back')
                console.error('Could not copy text: ', err);

	            let button = document.getElementById('clipboardbutton');
	            button.style.display = 'inline-block';
	            button.addEventListener('click', e => {
		            let input = document.getElementById('testclipboard');
		            input.focus();
		            input.select();
		            const result = document.execCommand('copy');
		            if (result === 'unsuccessful') {
			            alert('Failed to copy text.');
		            } else {
			            alert('Managed to copy text.');
		            }
	            });
	            alert('click to copy setup details');
            });

        }
        catch (e) {

	        let button = document.getElementById('clipboardbutton');
	        button.style.display = 'inline-block';
	        button.addEventListener('click', e => {
		        let input = document.getElementById('testclipboard');
		        input.focus();
		        input.select();
		        const result = document.execCommand('copy');
		        if (result === 'unsuccessful') {
			        alert('Failed to copy text.');
		        } else {
			        alert('Managed to copy text.');
		        }
	        });
	        alert('click to copy setup details');
        }
             */
		</script>
		<?php


	}

	function diagnose($message, $type)
	{
//		JFactory::getApplication()->enqueueMessage($message, $type);
	}

}
