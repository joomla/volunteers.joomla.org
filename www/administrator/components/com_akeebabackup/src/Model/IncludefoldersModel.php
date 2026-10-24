<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ModelExclusionFilterTrait;
use Akeeba\Engine\Factory;
use Exception;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\CMS\MVC\Model\BaseModel;

#[\AllowDynamicProperties]
class IncludefoldersModel extends BaseModel
{
	use ModelExclusionFilterTrait;

	/**
	 * Method to get state variables. Uses application input if the state is not set.
	 *
	 * @param   null   $property  Optional parameter name
	 * @param   mixed  $default   Optional default value
	 *
	 * @return  mixed  The property where specified, the state object where omitted
	 *
	 * @since   4.0.0
	 */
	public function getState($property = null, $default = null)
	{
		try
		{
			$default = JoomlaFactory::getApplication()->getInput()
				->get($property, $default, is_array($default) ? 'array' : 'raw');
		}
		catch (Exception $e)
		{
		}

		return parent::getState($property, $default);
	}

	/**
	 * Returns an array containing a list of directories definitions
	 *
	 * @return  array  Array of definitions; The key contains the internal root name, the data is the directory path
	 */
	public function get_directories(): array
	{
		// Get database inclusion filters
		$filter = Factory::getFilterObject('extradirs');

		return $filter->getInclusions('dir');
	}

	/**
	 * Automatically rebase included folders to use path variables like [SITEROOT] and [ROOTPARENT]
	 *
	 * @return  void
	 * @since   7.3.3
	 */
	public function rebaseFiltersToSiteDirs(): void
	{
		$includeFolders = $this->get_directories();

		foreach ($includeFolders as $uuid => $def)
		{
			$originalDir  = $def[0];
			$convertedDir = Factory::getFilesystemTools()->rebaseFolderToStockDirs($originalDir);

			if ($originalDir == $convertedDir)
			{
				continue;
			}

			$def[0] = $convertedDir;
			$this->setFilter($uuid, $def);
		}
	}

	/**
	 * Delete a database definition
	 *
	 * @param   string  $uuid  The external directory's filter root key (UUID) to remove
	 *
	 * @return  array
	 */
	public function remove(string $uuid): array
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
	 * @param   array   $data  The root and path to the external directory we're adding
	 *
	 * @return  array
	 */
	public function setFilter(string $uuid, array $data): array
	{
		return $this->applyExclusionFilter('extradirs', $uuid, $data, 'set');
	}

	/**
	 * Handles a request coming in through AJAX. Basically, this is a simple proxy to the model methods.
	 *
	 * @return  array
	 */
	public function doAjax(): array
	{
		$action = $this->getState('action');
		$verb   = array_key_exists('verb', $action) ? $action['verb'] : null;

		$ret_array = [];

		switch ($verb)
		{
			// Set a filter (used by the editor)
			case 'set':
				$new_data = [
					0 => Factory::getFilesystemTools()->rebaseFolderToStockDirs($action['root']),
					1 => $action['data'],
				];

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