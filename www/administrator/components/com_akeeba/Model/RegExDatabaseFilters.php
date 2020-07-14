<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Admin\Model;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\Backup\Admin\Model\Mixin\ExclusionFilter;
use Akeeba\Engine\Factory;
use FOF30\Model\Model;

/**
 * RegEx Database Filters model
 *
 * Handles the exclusion of database tables (whole or just their data) using regular expressions
 */
class RegExDatabaseFilters extends Model
{
	use ExclusionFilter;

	/**
	 * Which RegEx filters are handled by this model?
	 *
	 * @var  array
	 */
	protected $knownRegExFilters = [
		'regextables',
		'regextabledata'
	];

	/**
	 * Returns an array containing a list of regex filters and their respective type for a given root
	 *
	 * @param   string  $root  The database root to list
	 *
	 * @return  array  Array of definitions
	 */
	public function get_regex_filters($root)
	{
		// These are the regex filters I know of

		// Filters already set
		$set_filters = array();

		// Loop all filter types
		foreach ($this->knownRegExFilters as $filter_name)
		{
			// Get this filter type's set filters
			$filter       = Factory::getFilterObject($filter_name);
			$temp_filters = $filter->getFilters($root);

			// Merge this filter type's regular expressions to the list
			if (count($temp_filters))
			{
				foreach ($temp_filters as $new_regex)
				{
					$set_filters[] = array(
						'type' => $filter_name,
						'item' => $new_regex
					);
				}
			}
		}

		return $set_filters;
	}

	/**
	 * Delete a regex filter
	 *
	 * @param   string  $type    Filter type
	 * @param   string  $root    The filter's root
	 * @param   string  $string  The filter string to remove
	 *
	 * @return  bool  True on success
	 */
	public function remove($type, $root, $string)
	{
		$ret = $this->applyExclusionFilter($type, $root, $string, 'remove');

		return $ret['success'];
	}

	/**
	 * Creates a new regex filter
	 *
	 * @param   string  $type    Filter type
	 * @param   string  $root    The filter's root
	 * @param   string  $string  The filter string to remove
	 *
	 * @return  bool  True on success
	 */
	public function setFilter($type, $root, $string)
	{
		$ret = $this->applyExclusionFilter($type, $root, $string, 'set');

		return $ret['success'];
	}

	/**
	 * Handles a request coming in through AJAX. Basically, this is a simple proxy to the model methods.
	 *
	 * @return  array
	 */
	public function doAjax()
	{
		$action = $this->getState('action');
		$verb   = array_key_exists('verb', $action) ? $action['verb'] : null;

		$ret_array = array();

		switch ($verb)
		{
			// Produce a list of regex filters
			case 'list':
				$ret_array = $this->get_regex_filters($action['root']);
				break;

			// Set a filter (used by the editor)
			case 'set':
				$ret_array = array('success' => $this->setFilter($action['type'], $action['root'], $action['node']));
				break;

			// Remove a filter (used by the editor)
			case 'remove':
				$ret_array = array('success' => $this->remove($action['type'], $action['root'], $action['node']));
				break;
		}

		return $ret_array;
	}
}
