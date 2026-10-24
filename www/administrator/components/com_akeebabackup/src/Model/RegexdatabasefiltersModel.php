<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Model;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ModelExclusionFilterTrait;
use Akeeba\Engine\Factory;

#[\AllowDynamicProperties]
class RegexdatabasefiltersModel extends \Joomla\CMS\MVC\Model\BaseModel
{
	use ModelExclusionFilterTrait;

	/**
	 * Which RegEx filters are handled by this model?
	 *
	 * @var  array
	 */
	protected $knownRegExFilters = [
		'regextables',
		'regextabledata',
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
		// Filters already set
		$set_filters = [];

		// Loop all filter types
		foreach ($this->knownRegExFilters as $filter_name)
		{
			// Get this filter type's set filters
			$filter       = Factory::getFilterObject($filter_name);
			$temp_filters = $filter->getFilters($root);

			// Merge this filter type's regular expressions to the list
			if (
				(is_array($temp_filters) || $temp_filters instanceof \Countable)
					? count($temp_filters)
					: 0
			)
			{
				foreach ($temp_filters as $new_regex)
				{
					$set_filters[] = [
						'type' => $filter_name,
						'item' => $new_regex,
					];
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

		$ret_array = [];

		switch ($verb)
		{
			// Produce a list of regex filters
			case 'list':
				$ret_array = [
					'list' => $this->get_regex_filters($action['root'])
				];
				break;

			// Set a filter (used by the editor)
			case 'set':
				$ret_array = ['success' => $this->setFilter($action['type'], $action['root'], $action['node'])];
				break;

			// Remove a filter (used by the editor)
			case 'remove':
				$ret_array = ['success' => $this->remove($action['type'], $action['root'], $action['node'])];
				break;
		}

		return $ret_array;
	}

}