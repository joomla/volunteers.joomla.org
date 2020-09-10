<?php

/**
 * @version    CVS: 1.12.1.1
 * @package    com_yoursites
 * @author     Geraint Edwards
 * @copyright  2017-2020 GWE Systems Ltd
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 */
defined('JPATH_BASE') or die;

/*
 * Joomla 3.7.x and earlier don't support these - leave them out until we MUST have them for Joomla 4.x
 */
/*
use Joomla\Registry\Registry;
use Joomla\CMS\Factory;
*/
class plgSystemYourSites extends JPlugin
{
	/**
	 * Application object.
	 *
	 * @var    JApplicationCms
	 * @since  3.5
	 */
	protected $app;

	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		$input = JFactory::getApplication()->input;
		/*
				if ($input->get('._ysts_diagnose', 0))
				{
					include_once 'yoursites_getupdatedata.php';
					set_exception_handler(array("YourSitesDiagnosis", 'captureException'));
					set_error_handler(array("YourSitesDiagnosis",'captureFatalError'));
				}
		*/
		$task = $input->get('task', $input->get('typeaheadtask', '', 'cmd'), 'cmd');

		if ($task != "gwejson" && $task != "yoursites")
		{
			return true;
		}

		// Special handling for migration
		$file   = $input->get('file', '', 'cmd');
		$folder = $input->get('folder', '', 'cmd');
		$plugin = $input->get('plugin', '', 'cmd');

		if ($file == "getupdatedata" && $folder == "yoursites" && $plugin == "handler")
		{
			$input->set('task', 'yoursites');
			$input->set('folder', 'system');
			$input->set('plugin', 'yoursites');
		}

		// Do we have uploaded files?
		if (isset($_FILES["install_package"]))
		{
			$this->uploadedFile = $_FILES["install_package"];
			unset($_FILES["install_package"]);
		}
		//$this->uploadedFile = $input->files->get("install_package", false, "raw");

	}

	/**
	 * Capture core Joomla error
	 *
	 * @since version 1.11
	 */
	public function onError($event)
	{
		$session = JFactory::getSession();
		if ($session->get('ysts_debug', 0))
		{

			$data               = new stdClass();
			$data->messages     = array('COM_YOURSITES_FATAL_ERROR_INFORMATION');
			$error              = $event->getError();
			$data->messages[]   = $error->getMessage();
			$data->messages[]   = $error->getFile() . " : " . $error->getLine();
			$data->log['file']  = $error->getFile();
			$data->log['line']  = $error->getLine();
			$data->log['trace'] = $error->getTrace();
			$data->warning      = 1;

			header("Content-Type: application/javascript");
			// Must suppress any error messages
			@ob_end_clean();
			echo json_encode($data);
			exit(0);
		}

		return true;
	}

	/**
	 * Method to catch the onAfterInitialise event.
	 *
	 * @return  boolean  True on success
	 *
	 */
	public
	function onAfterInitialise()
	{

		$input = JFactory::getApplication()->input;
		if (isset($this->uploadedFile))
		{
			$_FILES["install_package"] = $this->uploadedFile;
		}
		$task = $input->get('task', $input->get('typeaheadtask', '', 'cmd'), 'cmd');
		// in frontend SEF
		if ($task != "yoursites")
		{
			return true;
		}

		$file = $input->get('file', '', 'cmd');
		// Library file MUST start with "yoursites_" for security reasons to stop other files being included maliciously
		if ($file == "")
		{
			return true;
		}
		if (strpos($file, "yoursites_") !== 0)
		{
			$file = "yoursites_" . $file;
		}

		$path  = $input->get('path', 'site', 'cmd');
		$paths = array("site" => JPATH_SITE, "admin" => JPATH_ADMINISTRATOR, "plugin" => JPATH_SITE . "/plugins", "module" => JPATH_SITE . "/modules", "library" => JPATH_LIBRARIES);
		if (!in_array($path, array_keys($paths)))
		{
			return true;
		}
		$folder = $input->get('folder', '', 'string');
		if ($path == "plugin")
		{
			$plugin = $input->get('plugin', '', 'string');
			if ($folder == "" || $plugin == "")
			{
				return true;
			}
			$path = $paths[$path] . "/$folder/$plugin/";
		}
		else if ($path == "module" || $path == "library")
		{
			if ($folder == "")
			{
				return true;
			}
			$path = $paths[$path] . "/$folder/";
		}
		else
		{
			$extension = $input->get('option', $input->get('ttoption', '', 'cmd'), 'cmd');
			if ($extension == "")
			{
				return true;
			}
			if ($folder == "")
			{
				$path = $paths[$path] . "/components/$extension/libraries/";
			}
			else
			{
				$path = $paths[$path] . "/components/$extension/$folder/";
			}
		}

		jimport('joomla.filesystem.file');
		// Check for a custom version of the file first!
		$custom_file = str_replace("yoursites_", "yoursites_custom_", $file);
		if (JFile::exists($path . $custom_file . ".php"))
		{
			$file = $custom_file;
		}
		if (!JFile::exists($path . $file . ".php"))
		{
			PlgSystemyoursites::throwAjaxError("Whoops we could not find the file: " . $path . $file . ".php");

			return true;
		}

		include_once($path . $file . ".php");

		if (!function_exists("yoursites_skiptoken") || !yoursites_skiptoken())
		{
			$token = JSession::getFormToken();;
			if ($token != $input->get('token', '', 'string'))
			{
				if ($input->get('json', '', 'raw'))
				{

				}
				PlgSystemyoursites::throwAjaxError("There was an error - bad token.  Please refresh the page and try again.");
			}
		}

		// we don't want any modules etc.
		//$input->set('tmpl', 'component');
		$input->set('format', 'json');

		ini_set("display_errors", 0);

		// When setting typeahead in the post it overrides the GET value which the prepare function doesn't replace for some reason :(
		if ($input->get('typeahead', '', 'string') != "" || $input->get('prefetch', 0, 'int'))
		{
			try
			{
				$requestObject            = new stdClass();
				$requestObject->typeahead = $input->get('typeahead', '', 'string');
				$data                     = null;
				$data                     = ProcessJsonRequest($requestObject, $data);
			}
			catch (Exception $e)
			{
				//PlgSystemyoursites::throwAjaxError("There was an exception ".$e->getMessage()." ".var_export($e->getTrace()));
				PlgSystemyoursites::throwAjaxError("There was an exception " . addslashes($e->getMessage()));
			}
		}

		// Get JSON data
		else if ($input->get('json', '', 'raw') || $input->get('json64', '', 'raw'))
		{
			// Create JSON data structure
			$data         = new stdClass();
			$data->error  = 0;
			$data->result = "ERROR";
			$data->user   = "";

			$requestData = $input->get('json', '', 'raw');
			if (empty($requestData))
			{
				$requestData = @base64_decode($input->get('json64', '', 'raw'));
			}

			if (isset($requestData) && !empty($requestData))
			{
				try
				{
					if (ini_get("magic_quotes_gpc"))
					{
						$requestData = stripslashes($requestData);
					}

					$requestObject = @json_decode($requestData, 0);
					if (!$requestObject)
					{
						$requestObject = @json_decode(utf8_encode($requestData), 0);
					}
				}
				catch (Exception $e)
				{
					PlgSystemyoursites::throwAjaxError("There was an exception");
				}

				if (!$requestObject)
				{
					//file_put_contents(dirname(__FILE__) . "/cache/error.txt", var_export($requestData, true));
					PlgSystemyoursites::throwAjaxError("There was an error - no request object ");
				}
				else if (isset($requestObject->error) && $requestObject->error)
				{
					PlgSystemyoursites::throwAjaxError("There was an error - Request object error " . $requestObject->error);
				}
				else
				{
					try
					{
						$data = ProcessJsonRequest($requestObject, $data);
					}
					catch (Exception $e)
					{
						//PlgSystemyoursites::throwerror("There was an exception ".$e->getMessage()." ".var_export($e->getTrace()));
						PlgSystemyoursites::throwAjaxError("There was an exception " . $e->getMessage());
					}
				}
			}
			else
			{
				PlgSystemyoursites::throwAjaxError("Invalid Input");
			}
		}
		else
		{
			//PlgSystemyoursites::throwAjaxError("There was an error - no request data " . var_export($_REQUEST, true) . var_export($_FILES, true)  );
			PlgSystemyoursites::throwAjaxError("There was an error - no request data");
		}

		if (is_string($data) && $data === 'skip json')
		{
			return true;
		}
		header("Content-Type: application/javascript; charset=utf-8");

		if (is_object($data))
		{
			if (defined('_SC_START'))
			{
				list ($usec, $sec) = explode(" ", microtime());
				$time_end     = (float) $usec + (float) $sec;
				$data->timing = round($time_end - _SC_START, 4);
			}
			else
			{
				$data->timing = 0;
			}
		}

		// Must suppress any error messages
		@ob_end_clean();
		echo json_encode($data);

		exit(0);

	}

	public function onAjaxHandler()
	{
		include_once "yoursites_getupdatedata.php";

		$input = JFactory::getApplication()->input;

		$input->set('format', 'json');

		if ($input->get('json', '', 'raw'))
		{
			// Create JSON data structure
			$data         = new stdClass();
			$data->error  = 0;
			$data->result = "ERROR";
			$data->user   = "";

			$requestData = $input->get('json', '', 'raw');

			if (isset($requestData))
			{
				try
				{
					if (ini_get("magic_quotes_gpc"))
					{
						$requestData = stripslashes($requestData);
					}

					$requestObject = json_decode($requestData, 0);
					if (!$requestObject)
					{
						$requestObject = json_decode(utf8_encode($requestData), 0);
					}
				}
				catch (Exception $e)
				{
					plgSystemYourSites::throwAjaxError("There was an exception");
				}

				if (!$requestObject)
				{
					//file_put_contents(dirname(__FILE__) . "/cache/error.txt", var_export($requestData, true));
					plgSystemYourSites::throwAjaxError("There was an error - no request object ");
				}
				else if (isset($requestObject->error) && $requestObject->error)
				{
					plgSystemYourSites::throwAjaxError("There was an error - Request object error " . $requestObject->error);
				}
				else
				{
					try
					{
						$data = ProcessJsonRequest($requestObject, $data);
					}
					catch (Exception $e)
					{
						//plgSystemYourSites::throwAjaxError("There was an exception ".$e->getMessage()." ".var_export($e->getTrace()));
						plgSystemYourSites::throwAjaxError("There was an exception " . $e->getMessage());
					}
				}
			}
			else
			{
				plgSystemYourSites::throwAjaxError("Invalid Input");
			}
		}
		else
		{
			//plgSystemYourSites::throwAjaxError("There was an error - no request data " . var_export($_REQUEST, true));
			plgSystemYourSites::throwAjaxError("There was an error - no request data");
		}

		return $data;

		//header("Content-Type: application/javascript; charset=utf-8");

	}

	public static function throwAjaxError($msg)
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

	public static function throwerror($msg)
	{
		$data                = new stdClass();
		$data->errormessages = array($msg);
		$data->error         = 1;
		$data->user          = "";

		header("Content-Type: application/javascript");
		// Must suppress any error messages
		@ob_end_clean();
		echo json_encode($data);
		exit(0);
	}

	/*
	 *
	// This is how we configure diagnosis!

	$plugin = JPluginHelper::getPlugin("system" , "yoursites");
	if ($plugin)
	{
		$pluginparams = new JRegistry($plugin->params);
		$info = $pluginparams->get('yoursites.info', "nothing");
		$pluginparams->set('yoursites.info', "something");
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->update($db->quoteName('#__extensions'))
			->set($db->quoteName('params') . ' = ' . $db->quote($pluginparams->toString()))
			->where($db->quoteName('extension_id') . ' = ' . $plugin->id);
		$db->setQuery($query);
		$db->execute();
	}
	 */

	/**
	 * For cloned sites with images folder not copied we can use the parent images via this plugin
	 *
	 * @return  void
	 */
	public function onAfterRender()
	{
		if (!$this->app->isClient('site'))
		{
			return;
		}

		if ($this->app->input->get('layout') === 'edit')
		{
			return;
		}

		// only work for clone sites
		$base = JUri::base(true);
		if (strpos($base, "._ysts_") === false)
		{
			return;
		}

		$parts = explode("/._ysts_", $base);
		if (count($parts) !== 2)
		{
			return;
		}


		// No need for this code - we check the actual images
		if (false && file_exists(JPATH_SITE . "/images"))
		{
			// only looks one layer deep since $recurse is false by default but its still overkill so do it ourselves
			/*
			$imagefiles = JFolder::files(JPATH_SITE . "/images");
			if (count($imagefiles) > 1)
			{
				return;
			}
			*/

			$path = JPath::clean(JPATH_SITE . "/images");
			if (is_dir($path))
			{
				// Read the source directory
				if (($handle = @opendir($path)))
				{

					while (($file = readdir($handle)) !== false)
					{
						if ($file != '.' && $file != '..' && strpos($file, ".html") === false)
						{
							return;
						}
					}
				}

			}
		}

		$buffer  = $this->app->getBody();

		$matches = array();
		$pattern = "#['|\"]([^\s|\t|\r|\n]*?)\._ysts_" . $parts[1] . "(/images/[^)''\"\s]+\.(?:jpg|jpeg|gif|png))#";
		preg_match_all($pattern, $buffer, $matches);
		if (count($matches) == 3){
			$root = str_replace("/._ysts_" . $parts[1], "", JUri::root());
			for ($m = 0; $m < count($matches[2]); $m++)
			{
				$match = $matches[2][$m];
				if (!file_exists(JPATH_SITE . $match))
				{
					$char1 = substr($matches[0][$m], 0, 1);

					//echo "1. replace " . $matches[0][$m] . " with " . $char1 .  $root .  substr($matches[2][$m], 1). "<Br>";
					$buffer = str_replace( $matches[0][$m], $char1 . $root . substr($matches[2][$m], 1) , $buffer);
				}
			}
		}

		$matches = array();
		$pattern = "#['|\"](images/[^)''\"\s]+\.(?:jpg|jpeg|gif|png))#";
		preg_match_all($pattern, $buffer, $matches);
		if (count($matches) == 2){
			$root = str_replace("/._ysts_" . $parts[1], "", JUri::root());
			foreach ($matches[1] as $match)
			{
				if (!file_exists(JPATH_SITE . $match))
				{
					//echo "2. replace " . $match. " with " . $root . $match ."<Br>";
					$buffer = str_replace( $match, $root . $match, $buffer);
				}
			}
		}

		// Use the replaced HTML body.
		$this->app->setBody($buffer);

		return;
	}

}