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
use Akeeba\Engine\Platform;
use InvalidArgumentException;
use Joomla\CMS\MVC\Model\BaseModel;

#[\AllowDynamicProperties]
class FilefiltersModel extends BaseModel
{
	use ModelExclusionFilterTrait;

	public function __construct($config = [])
	{
		parent::__construct($config);

		$this->knownFilterTypes = ['directories', 'files', 'skipdirs', 'skipfiles'];
	}

	/**
	 * Returns an array with the listing and filter status of a directory
	 *
	 * @param   string  $root    Root directory
	 * @param   array   $crumbs  Breadcrumbs in array format, defining the parent directory
	 * @param   string  $child   The child directory we want to scan
	 *
	 * @return  array
	 */
	public function makeListing(string $root, array $crumbs = [], string $child = ''): array
	{
		// Construct the full node
		$node = $this->glueCrumbs($crumbs, $child);

		// Create the new crumbs
		if (!is_array($crumbs))
		{
			$crumbs = [];
		}

		if (!empty($child))
		{
			$crumbs[] = $child;
		}

		// Get listing with the filter info
		$listing = $this->getListing($root, $node);

		// Assemble the array
		$listing['root']   = $root;
		$listing['crumbs'] = $crumbs;

		return $listing;
	}

	/**
	 * Toggle a filter
	 *
	 * @param   string  $root    Root directory
	 * @param   array   $crumbs  Components of the current directory relative to the root
	 * @param   string  $item    The child item of the current directory we want to toggle the filter for
	 * @param   string  $filter  The name of the filter to apply (directories, skipfiles, skipdirs, files)
	 *
	 * @return  array
	 */
	public function toggle(string $root, array $crumbs, string $item, string $filter): array
	{
		$node = $this->glueCrumbs($crumbs, $item);

		return $this->applyExclusionFilter($filter, $root, $node, 'toggle');
	}

	/**
	 * Set a filter
	 *
	 * @param   string  $root    Root directory
	 * @param   array   $crumbs  Components of the current directory relative to the root
	 * @param   string  $item    The child item of the current directory we want to set the filter for
	 * @param   string  $filter  The name of the filter to apply (directories, skipfiles, skipdirs, files)
	 *
	 * @return  array
	 */
	public function setFilter(string $root, array $crumbs, string $item, string $filter): array
	{
		$node = $this->glueCrumbs($crumbs, $item);

		return $this->applyExclusionFilter($filter, $root, $node, 'set');
	}

	/**
	 * Remove a filter
	 *
	 * @param   string  $root    Root directory
	 * @param   array   $crumbs  Components of the current directory relative to the root
	 * @param   string  $item    The child item of the current directory we want to remove the filter for
	 * @param   string  $filter  The name of the filter to apply (directories, skipfiles, skipdirs, files)
	 *
	 * @return  array
	 */
	public function remove(string $root, array $crumbs, string $item, string $filter): array
	{
		$node = $this->glueCrumbs($crumbs, $item);

		return $this->applyExclusionFilter($filter, $root, $node, 'remove');
	}

	/**
	 * Swap a filter
	 *
	 * @param   string  $root      Root directory
	 * @param   array   $crumbs    Components of the current directory relative to the root
	 * @param   string  $old_item  The child item of the current directory we want to remove a filter for
	 * @param   string  $new_item  The child item of the current directory we want to add a filter for
	 * @param   string  $filter    The name of the filter to apply (directories, skipfiles, skipdirs, files)
	 *
	 * @return  array
	 */
	public function swap(string $root, array $crumbs, string $old_item, string $new_item, string $filter): array
	{
		$new_node = $this->glueCrumbs($crumbs, $new_item);
		$old_node = $this->glueCrumbs($crumbs, $old_item);

		return $this->applyExclusionFilter($filter, $root, $new_node, 'swap', $old_node);
	}

	/**
	 * Retrieves the filters as an array. Used for the tabular filter editor.
	 *
	 * @param   string  $root  The root node to search filters on
	 *
	 * @return  array  A collection of hash arrays containing node and type for each filtered element
	 */
	public function getFilters(string $root): array
	{
		return $this->getTabularFilters($root);
	}

	/**
	 * Resets the filters
	 *
	 * @param   string  $root  Root directory
	 *
	 * @return  array
	 */
	public function resetFilters(string $root): array
	{
		$this->resetAllFilters($root);

		return $this->makeListing($root);
	}

	/**
	 * Handles a request coming in through AJAX. Basically, this is a simple proxy to the model methods.
	 *
	 * @return  array
	 */
	public function doAjax(): array
	{
		$action = $this->getState('action');
		$verb   = array_key_exists('verb', get_object_vars($action)) ? $action->verb : null;

		if (!array_key_exists('crumbs', get_object_vars($action)))
		{
			$action->crumbs = [];
		}

		$ret_array = [];

		switch ($verb)
		{
			// Return a listing for the normal view
			case 'list':
				$ret_array = $this->makeListing($action->root, $action->crumbs, $action->node);
				break;

			// Toggle a filter's state
			case 'toggle':
				$ret_array = $this->toggle($action->root, $action->crumbs, $action->node, $action->filter);
				break;

			// Set a filter (used by the editor)
			case 'set':
				$ret_array = $this->setFilter($action->root, $action->crumbs, $action->node, $action->filter);
				break;

			// Swap a filter (used by the editor)
			case 'swap':
				$ret_array =
					$this->swap($action->root, $action->crumbs, $action->old_node, $action->new_node, $action->filter);
				break;

			case 'tab':
				$ret_array = [
					'list' => $this->getFilters($action->root)
				];
				break;

			// Reset filters
			case 'reset':
				$ret_array = $this->resetFilters($action->root);
				break;
		}

		return $ret_array;
	}

	/**
	 * Returns a listing of contained directories and files, as well as their exclusion status
	 *
	 * @param   string  $root  The root directory
	 * @param   string  $node  The subdirectory to scan
	 *
	 * @return  array
	 */
	private function getListing(string $root, string $node): array
	{
		// Initialize the absolute directory root
		$directory = substr($root, 0);

		// Replace stock directory tags, like [SITEROOT]
		$stock_dirs = Platform::getInstance()->get_stock_directories();

		if (!empty($stock_dirs))
		{
			foreach ($stock_dirs as $key => $replacement)
			{
				$directory = str_replace($key, $replacement, $directory);
			}
		}

		$directory = Factory::getFilesystemTools()->TranslateWinPath($directory);

		// Clean and add the node
		$node = Factory::getFilesystemTools()->TranslateWinPath($node);

		// Just a directory separator is treated as no directory at all
		if (($node == '/'))
		{
			$node = '';
		}

		// Trim leading and trailing slashes
		$node = trim($node, '/');

		// Add node to directory
		if (!empty($node))
		{
			$directory .= '/' . $node;
		}

		// Add any required trailing slash to the node to be used below
		if (!empty($node))
		{
			$node .= '/';
		}

		// Get a filters instance
		$filters = Factory::getFilters();

		// Get a listing of folders and process it
		$folders     = Factory::getFileLister()->getFolders($directory);
		$folders_out = [];

		if (!empty($folders))
		{
			asort($folders);

			foreach ($folders as $folder)
			{
				$folder = Factory::getFilesystemTools()->TranslateWinPath($folder);

				// Filter out files whose names result to an empty JSON representation
				$json_folder = json_encode($folder);
				$folder      = json_decode($json_folder);

				if (empty($folder))
				{
					continue;
				}

				$test   = $node . $folder;
				$status = [];

				// Check dir/all filter (exclude)
				$result                = $filters->isFilteredExtended($test, $root, 'dir', 'all', $byFilter);
				$status['directories'] = (!$result) ? 0 : (($byFilter == 'directories') ? 1 : 2);

				// Check dir/content filter (skip_files)
				$result              = $filters->isFilteredExtended($test, $root, 'dir', 'content', $byFilter);
				$status['skipfiles'] = (!$result) ? 0 : (($byFilter == 'skipfiles') ? 1 : 2);

				// Check dir/children filter (skip_dirs)
				$result             = $filters->isFilteredExtended($test, $root, 'dir', 'children', $byFilter);
				$status['skipdirs'] = (!$result) ? 0 : (($byFilter == 'skipdirs') ? 1 : 2);

				$status['link'] = @is_link($directory . '/' . $folder);

				// Add to output array
				$folders_out[$folder] = $status;
			}
		}

		unset($folders);
		$folders = $folders_out;

		// Get a listing of files and process it
		$files     = Factory::getFileLister()->getFiles($directory);
		$files_out = [];

		if (!empty($files))
		{
			asort($files);

			foreach ($files as $file)
			{
				// Filter out files whose names result to an empty JSON representation
				$json_file = json_encode($file);
				$file      = json_decode($json_file);

				if (empty($file))
				{
					continue;
				}

				$test   = $node . $file;
				$status = [];

				// Check file/all filter (exclude)
				$result          = $filters->isFilteredExtended($test, $root, 'file', 'all', $byFilter);
				$status['files'] = (!$result) ? 0 : (($byFilter == 'files') ? 1 : 2);
				$status['size']  = $this->formatSize(@filesize($directory . '/' . $file), 1);
				$status['link']  = @is_link($directory . '/' . $file);

				// Add to output array
				$files_out[$file] = $status;
			}
		}

		unset($files);
		$files = $files_out;

		// Return a compiled array
		$retArray = [
			'folders' => $folders,
			'files'   => $files,
		];

		return $retArray;

		/* Return array format
		 * [array] :
		 * 		'folders' [array] :
		 * 			(folder_name) => [array]:
		 *				'directories'	=> 0|1|2
		 *				'skipfiles'		=> 0|1|2
		 *				'skipdirs'		=> 0|1|2
		 *		'files' [array] :
		 *			(file_name) => [array]:
		 *				'files'			=> 0|1|2
		 *
		 * Legend:
		 * 0 -> Not excluded
		 * 1 -> Excluded by the direct filter
		 * 2 -> Excluded by another filter (regex, api, an unknown plugin filter...)
		 */
	}

	/**
	 * Glues the current directory crumbs and the child directory into a node string
	 *
	 * @param   array   $crumbs  Breadcrumbs in array or JSON encoded array format
	 * @param   string  $child   The child folder (relative to the root defined by crumbs)
	 *
	 * @return  string  The absolute node (path) of the $child
	 */
	private function glueCrumbs(array $crumbs, string $child): string
	{
		// Construct the full node
		$node = '';

		array_walk($crumbs, function ($value, $index) {
			if (in_array(trim($value), ['.', '..']))
			{
				throw new InvalidArgumentException("Unacceptable folder crumbs");
			}
		});

		if ((stristr($child, '/..') !== false) || (stristr($child, '\..') !== false))
		{
			throw new InvalidArgumentException("Unacceptable child folder");
		}

		if (!empty($crumbs))
		{
			$node = implode('/', $crumbs);
		}

		if (!empty($node))
		{
			$node .= '/';
		}

		if (!empty($child))
		{
			$node .= $child;
		}

		return $node;
	}

	/**
	 * Format the size of the file (given in bytes) to something human readable, e.g. 123 MB
	 *
	 * @param   int  $bytes     The file size in bytes
	 * @param   int  $decimals  How many decimals you want (default: 0)
	 *
	 * @return  string  The human-readable, formatted size
	 */
	private function formatSize(int $bytes, int $decimals = 0): string
	{
		$bytes  = empty($bytes) ? 0 : (int) $bytes;
		$format = empty($decimals) ? '%0u' : '%0.' . $decimals . 'f';

		$uom = [
			'TB' => 1048576 * 1048576,
			'GB' => 1024 * 1048576,
			'MB' => 1048576,
			'KB' => 1024,
			'B'  => 1,
		];

		// Whole bytes cannot have decimal positions
		if (!empty($decimals))
		{
			unset($uom['B']);
		}

		foreach ($uom as $unit => $byteSize)
		{
			if (floatval($bytes) >= $byteSize)
			{
				return sprintf($format, $bytes / $byteSize) . ' ' . $unit;
			}
		}

		// If the number is either too big or too small,
		return sprintf('%0u B', $bytes);
	}
}