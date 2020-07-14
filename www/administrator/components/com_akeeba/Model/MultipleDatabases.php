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
use Exception;
use FOF30\Model\Model;
use JText;

/**
 * Model for Include Multiple Databases.
 */
class MultipleDatabases extends Model
{
	use ExclusionFilter;

	/**
	 * Returns an array containing a list of database definitions
	 *
	 * @return  array  Array of definitions; The key contains the internal root name, the data is the database
	 *                 configuration data
	 */
	public function get_databases()
	{
		// Get database inclusion filters
		$filter        = Factory::getFilterObject('multidb');

		return $filter->getInclusions('db');
	}

	/**
	 * Delete a database definition
	 *
	 * @param   string  $root  The name of the database root key to remove
	 *
	 * @return  bool  True on success
	 */
	public function remove($root)
	{
		$ret = $this->applyExclusionFilter('multidb', $root, null, 'remove');

		return $ret['success'];
	}

	/**
	 * Creates a new database definition
	 *
	 * @param   string  $root  The name of the database root key
	 * @param   array   $data  The connection information
	 *
	 * @return  bool
	 */
	public function setFilter($root, $data)
	{
		$ret = $this->applyExclusionFilter('multidb', $root, $data, 'set');

		return $ret['success'];
	}

	/**
	 * Tests the connectivity to a database
	 *
	 * @param   array  $data  The connection information
	 *
	 * @return  array  Status array: 'status' is true on success, 'message' contains any error message while connecting
	 *                 to the database
	 */
	public function test($data)
	{
		try
		{
			$db      = Factory::getDatabase($data);
			$success = $db->getErrorNum() <= 0;
			$error   = $db->getErrorMsg();
		}
		catch (Exception $e)
		{
			$success = false;
			$error = $e->getMessage();
		}

		if (
			empty($data['driver']) || empty($data['host']) || empty($data['user']) || empty($data['password'])
			|| empty($data['database'])
		)
		{
			return array(
				'status'  => false,
				'message' => JText::_('COM_AKEEBA_MULTIDB_ERR_MISSINGINFO'),
			);
		}

		return array(
			'status'  => $success,
			'message' => $error
		);
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
				$ret_array = $this->setFilter($action['root'], $action['data']);
				break;

			// Remove a filter (used by the editor)
			case 'remove':
				$ret_array = array('success' => $this->remove($action['root']));
				break;

			// Test connection (used by the editor)
			case 'test':
				$ret_array = $this->test($action['data']);
				break;
		}

		return $ret_array;
	}
}
