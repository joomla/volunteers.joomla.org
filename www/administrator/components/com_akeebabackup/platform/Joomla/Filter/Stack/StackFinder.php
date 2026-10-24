<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Filter\Stack;

// Protection against direct access
defined('_JEXEC') || die();

use Akeeba\Engine\Filter\Base as FilterBase;

/**
 * Date conditional filter
 *
 * It will only backup files modified after a specific date and time
 *
 * @since  3.4.0
 */
class StackFinder extends FilterBase
{
	/** @inheritDoc */
	public function __construct()
	{
		parent::__construct();

		$this->object  = 'dbobject';
		$this->subtype = 'content';
		$this->method  = 'api';
	}

	/**
	 * Exclude the Smart Search (Finder) tables from the main site's database.
	 *
	 * @param   string  $test  The object to test for exclusion
	 * @param   string  $root  The object's root
	 *
	 * @return  bool    Return true if it matches your filters
	 *
	 * @since   3.4.0
	 * @see     https://github.com/joomla/joomla-cms/issues/27913#issuecomment-1102954460
	 */
	protected function is_excluded_by_api($test, $root)
	{
		return ($root === '[SITEDB]') && in_array($test, [
				'#__finder_terms', '#__finder_links_terms', '#__finder_logging',
				'#__finder_tokens', '#__finder_tokens_aggregate',
			]);
	}

	public function filterDatabaseRowContent(string $root, string $tableAbstract, array &$row): void
	{
		if ($root !== '[SITEDB]' || $tableAbstract !== '#__finder_links')
		{
			return;
		}

		$row['md5sum'] = null;
		$row['object'] = null;
	}

}
