<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Admin\Model;

// Protect from unauthorized access
defined('_JEXEC') or die();

/**
 * RegEx File Filters model
 *
 * Handles the exclusion of files and folders using regular expressions
 */
class RegExFileFilters extends RegExDatabaseFilters
{
	/**
	 * Which RegEx filters are handled by this model?
	 *
	 * @var  array
	 */
	protected $knownRegExFilters = [
		'regexfiles',
		'regexdirectories',
		'regexskipdirs',
		'regexskipfiles'
	];
}
