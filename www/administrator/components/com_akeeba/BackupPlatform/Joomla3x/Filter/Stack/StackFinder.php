<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Filter\Stack;

// Protection against direct access
defined('AKEEBAENGINE') or die();

use Akeeba\Engine\Factory;
use Akeeba\Engine\Filter\Base as FilterBase;

/**
 * Date conditional filter
 * 
 * It will only backup files modified after a specific date and time
 */
class StackFinder extends FilterBase
{	
	function __construct()
	{
		$this->object	= 'dbobject';
		$this->subtype	= 'content';
		$this->method	= 'api';
	}

	protected function is_excluded_by_api($test, $root)
	{
		static $finderTables = array(
			'#__finder_links', '#__finder_links_terms0', '#__finder_links_terms1',
			'#__finder_links_terms2', '#__finder_links_terms3', '#__finder_links_terms4',
			'#__finder_links_terms5', '#__finder_links_terms6', '#__finder_links_terms7',
			'#__finder_links_terms8', '#__finder_links_terms9', '#__finder_links_termsa',
			'#__finder_links_termsb', '#__finder_links_termsc', '#__finder_links_termsd',
			'#__finder_links_termse', '#__finder_links_termsf', '#__finder_taxonomy',
			'#__finder_taxonomy_map', '#__finder_terms'
		);
		
		// Not the site's database? Include the tables
		if($root != '[SITEDB]') return false;
		
		// Is it one of the blacklisted tables?
		if(in_array($test, $finderTables)) return true;

		// No match? Just include the file!
		return false;
	}

}
