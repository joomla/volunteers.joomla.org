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
 * Model for Include Off-site Directories.
 */
class IncludeFolders extends Model
{
	use ExclusionFilter;

	/**
	 * Returns an array containing a list of directories definitions
	 *
	 * @return  array  Array of definitions; The key contains the internal root name, the data is the directory path
	 */
	public function get_directories()
	{
		// Get database inclusion filters
		$filter          = Factory::getFilterObject('extradirs');
		$includedFolders = $filter->getInclusions('dir');

		return $includedFolders;
	}

	/**
	 * Delete a database definition
	 *
	 * @param   string  $uuid  The external directory's filter root key (UUID) to remove
	 *
	 * @return  bool  True on success
	 */
	public function remove($uuid)
	{
		// Special case (empty UUID): New row is added, so the GUI tries to delete the default (empty) record
		if (empty($uuid))
		{
			return ['success' => true, 'newstate' => true];
		}

		return $this->applyExclusionFilter('extradirs', $uuid, null, 'remove');
	}

	/**
	 * Creates a new database definition
	 *
	 * @param   string  $uuid  The external directory's filter root key (UUID) to remove
	 * @param   array   $data  The absolute path to the external directory we're adding
	 *
	 * @return  bool
	 */
	public function setFilter($uuid, $data)
	{
		return $this->applyExclusionFilter('extradirs', $uuid, $data, 'set');
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
			// Set a filter (used by the editor)
			case 'set':
				$new_data = array(
					0 => $action['root'],
					1 => $action['data']
				);

				// Set the new root
				$ret_array = $this->setFilter($action['uuid'], $new_data);

				break;

			// Remove a filter (used by the editor)
			case 'remove':
				$ret_array = $this->remove($action['uuid']);

				break;
		}

		return $ret_array;
	}
}
