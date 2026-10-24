<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Model;

defined('_JEXEC') || die;

#[\AllowDynamicProperties]
class RegexfilefiltersModel extends RegexdatabasefiltersModel
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
		'regexskipfiles',
	];
}