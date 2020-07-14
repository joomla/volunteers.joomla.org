<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Filter\Stack;

use Akeeba\Engine\Filter\Base;

// Protection against direct access
defined('AKEEBAENGINE') or die();

/**
 * Exclude MyJoomla tables
 */
class StackMyjoomla extends Base
{
    public function __construct()
    {
        $this->object  = 'dbobject';
        $this->subtype = 'content';
        $this->method  = 'api';

        parent::__construct();
    }

    protected function is_excluded_by_api($test, $root)
    {
	    static $myjoomlaTables = array(
		    'bf_core_hashes',
		    'bf_files',
		    'bf_files_last',
		    'bf_folders',
		    'bf_folders_to_scan'
	    );

	    // Is it one of the blacklisted tables?
	    if(in_array($test, $myjoomlaTables))
	    {
	    	return true;
	    }

	    // No match? Just include the file!
	    return false;
    }

}
