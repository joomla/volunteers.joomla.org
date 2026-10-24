<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Filter;

// Protection against direct access
defined('_JEXEC') || die();

use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;

/**
 * Joomla! 1.6 libraries off-site relocation workaround
 *
 * After the application of patch 23377
 * (http://joomlacode.org/gf/project/joomla/tracker/?action=TrackerItemEdit&tracker_item_id=23377)
 * it is possible for the webmaster to move the libraries directory of his Joomla!
 * site to an arbitrary location in the folder tree. This filter works around this
 * new feature by creating a new extra directory inclusion filter.
 */
class Libraries extends Base
{
	public function __construct()
	{
		$this->object      = 'dir';
		$this->subtype     = 'inclusion';
		$this->method      = 'direct';
		$this->filter_name = 'Libraries';

		parent::__construct();

		$this->initialise();
	}

	private function initialise()
	{
		// Bail out if the user has provided a custom (alternate) root to back up
		if (Factory::getConfiguration()->get('akeeba.platform.override_root', 0))
		{
			return;
		}

		if (defined('JPATH_LIBRARIES'))
		{
			$jLibrariesDir = JPATH_LIBRARIES;
		}
		/** @deprecated Deprecated since Joomla! 4.4, we can remove it in Joomla! 6.0 */
		elseif (defined('JPATH_PLATFORM'))
		{
			/** @noinspection PhpDeprecationInspection */
			$jLibrariesDir = JPATH_PLATFORM;
		}
		else
		{
			return;
		}

		$jLibrariesDir    = Factory::getFilesystemTools()->TranslateWinPath($jLibrariesDir);
		$defaultLibraries = Factory::getFilesystemTools()->TranslateWinPath(JPATH_ROOT . '/libraries');

		if ($defaultLibraries === $jLibrariesDir)
		{
			return;
		}

		// The path differs, add it here
		$this->filter_data['JPATH_LIBRARIES'] = 			[
			Factory::getFilesystemTools()->rebaseFolderToStockDirs($jLibrariesDir),
			'JPATH_LIBRARIES',
		];
	}
}
