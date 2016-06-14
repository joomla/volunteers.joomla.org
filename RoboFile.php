<?php

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks
{
    protected $toDir = null;

    public function initialise ($name)
    {
    	$this->taskFileSystemStack()
    		->mkdir('code')
     		->mkdir('code/administrator')
     		->mkdir('code/administrator')
     		->mkdir('code/administrator/components')
     		->mkdir('code/administrator/components/com_' . $name)
     		->mkdir('code/administrator/language')
     		->mkdir('code/administrator/language/en-GB')
     		->mkdir('code/administrator/language/de-DE')
     		->mkdir('code/components')
     		->mkdir('code/components/com_' . $name)
     		->mkdir('code/language')
     		->mkdir('code/language/en-GB')
     		->mkdir('code/language/de-DE')
     		->mkdir('code/libraries')
     		->mkdir('code/libraries/' . $name)
     		->mkdir('code/media')
     		->mkdir('code/media/' . $name)
     		->mkdir('code/modules')
     		->mkdir('code/modules/mod_' . $name)
     		->mkdir('code/plugins')
     		->run();
    }

	/**
	 * Maps all parts of an extension into a Joomla! installation
	 * 
	 * @param  type $toDir 
	 * 
	 */
	public function map($toDir)
	{
		$this->toDir = $toDir;

		$codeBase = __DIR__ . '/source';

		if ( ! is_dir($codeBase))
		{
			$this->say('Error: ' . $codeBase . 'is not available');

			return false;
		}	

		$dirHandle = opendir($codeBase);

		if ($dirHandle === false)
		{
			$this->say('Error: Can not open' . $codeBase . 'for parsing');

			return false;

		}	

		// This runs thru all main dirs
        while (false !== ($element = readdir($dirHandle)))
        {
            if ($element != "." && $element != "..") 
            {
	            $fileOrDir = $codeBase . '/' . $element;

	            if (is_dir($codeBase . '/' . $element))
	            {
	            	$method = 'process' . ucfirst($element);

	            	$this->say('check:' . $method);

	            	if (method_exists($this, $method))
	            	{
		            	$this->say('working:' . $method);
	            		$this->$method($codeBase, $toDir);
	            	}	
	            }
	        }     
        }

        closedir($dirHandle);

        return true;
	}

	private function processAdministrator($codeBase, $toDir = null) 
	{
		$toDir = $this->getToDir($toDir);

		// Component directory
		$this->processComponents($codeBase . '/administrator', $toDir . '/administrator');

		// Languages
		$this->processLanguage($codeBase . '/administrator', $toDir . '/administrator');

		// Modules
		$this->processModules($codeBase . '/administrator', $toDir . '/administrator');
	}

	private function processComponents($codeBase, $toDir = null) 
	{
		$toDir = $this->getToDir($toDir);
		$base  = $codeBase . '/components';

		// Component directory
		if (is_dir($base))
		{
			$dirHandle = opendir($base);

		    while (false !== ($element = readdir($dirHandle)))
		    {
		        if (false !== strpos($element, 'com_')) 
		        {
		        	$this->symlink($base . '/' . $element, $toDir . '/components/' . $element);
		        }
		    }    
		}	
	}

	private function processLanguage($codeBase, $toDir = null)
	{
		$toDir = $this->getToDir($toDir);
		$base  = $codeBase . '/language';

		if (is_dir($base))
		{
			$dirHandle = opendir($base);

		    while (false !== ($element = readdir($dirHandle)))
		    {
	            if ($element != "." && $element != "..") 
	            {
	            	if (is_dir($base . '/' .$element))
	            	{
	            		$langDirHandle = opendir($base . '/' . $element);
			
					    while (false !== ($file = readdir($langDirHandle)))
					    {
					    	if (is_file($base . '/' . $element . '/' . $file))
					    	{
					    		$this->symlink($base . '/' . $element . '/' . $file, $toDir . '/language/' . $element . '/' . $file);
					    	}	
					    }	
	            	}	
		        }
		    }    
		}	
	}

	private function processLibraries($codeBase, $toDir = null)
	{
		$this->mapDir('libraries', $codeBase, $toDir);
	}
	
	private function processMedia($codeBase, $toDir = null)
	{
		$this->mapDir('media', $codeBase, $toDir);
	}
	
	private function processLayouts($codeBase, $toDir = null)
	{
		$this->mapDir('layouts', $codeBase, $toDir);
	}

	private function processModules($codeBase, $toDir = null)
	{
		$this->mapDir('modules', $codeBase, $toDir);
	}

	private function processPlugins($codeBase, $toDir = null)
	{
		$toDir = $this->getToDir($toDir);
		$base  = $codeBase . '/plugins';

		if (is_dir($base))
		{
			$dirHandle = opendir($base);

		    while (false !== ($element = readdir($dirHandle)))
		    {
	            if ($element != "." && $element != "..") 
	            {
	            	if (is_dir($base . '/' . $element))
	            	{
	            		$this->mapDir($element, $base, $toDir . '/plugins');
	            	}
		        }
		    }    
		}	
	}

	private function mapDir($type, $codeBase, $toDir = null)
	{
		$toDir = $this->getToDir($toDir);
		$base  = $codeBase . '/' . $type;

		// Check if dir exists
		if (is_dir($base))
		{
			$dirHandle = opendir($base);

		    while (false !== ($element = readdir($dirHandle)))
		    {
		    	if ($element != "." && $element != "..") 
		    	{
		    		$this->symlink($base . '/' . $element, $toDir . '/' . $type . '/' . $element);
		    	}	
		    }
		}	
	}

	private function symlink($source, $target)
	{
        $this->say('Source: ' . $source);
        $this->say('Target: ' . $target);
        
        if (file_exists($target))
        {
        	$this->say('Delete Taget:' . $target);
            $this->_deleteDir($target);    
        }  

        try
        {
            $this->taskFileSystemStack()
                ->symlink($source, $target)
                ->run();
        }
        catch (Exception $e)
        {
           $this->say('ERROR: ' . $e->message()); 
        }
	}

	private function getToDir($toDir=null)
	{
		if (is_null($toDir))
		{
			$toDir = $this->toDir;
		}

		return $toDir;
	}
}