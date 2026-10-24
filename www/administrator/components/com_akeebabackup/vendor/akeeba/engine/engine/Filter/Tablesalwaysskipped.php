<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License version 3, or later
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program. If not, see
 * <https://www.gnu.org/licenses/>.
 */

/**
 * @package     Akeeba\Engine\Filter
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Akeeba\Engine\Filter;

defined('AKEEBAENGINE') || die();

class Tablesalwaysskipped extends Base
{
	private $skipTablesWithBackticks = false;

	public function __construct()
	{
		$this->object  = 'dbobject';
		$this->subtype = 'content';
		$this->method  = 'api';

		parent::__construct();
	}

	/**
	 * This method must be overridden by API-type exclusion filters.
	 *
	 * @param   string  $test  The object to test for exclusion
	 * @param   string  $root  The object's root
	 *
	 * @return  bool  Return true if it matches your filters
	 */
	protected function is_excluded_by_api($test, $root)
	{
		static $alwaysExcludeTables = [
			// Tables from a certain service connector
			'bf_core_hashes',
			'bf_files',
			'bf_files_last',
			'bf_folders',
			'bf_folders_to_scan',
		];

		// Is it one of the always excluded tables?
		if (in_array($test, $alwaysExcludeTables))
		{
			return true;
		}

		// Tables with backticks in their name
		if ($this->skipTablesWithBackticks && strpos($test, '`') !== false)
		{
			return true;
		}

		return false;
	}

}