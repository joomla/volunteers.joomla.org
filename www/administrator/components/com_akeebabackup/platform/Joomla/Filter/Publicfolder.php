<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Filter;

// Protection against direct access
defined('_JEXEC') || die();

use Akeeba\Component\AkeebaBackup\Administrator\Helper\JoomlaPublicFolder;
use Akeeba\Engine\Factory;

/**
 * Joomla! 5.0+ off-site public root inclusion
 */
class Publicfolder extends Base
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

		// This only makes sense in Joomla! 5 or later, where the JPATH_PUBLIC constant is defined.
		if (!defined('JPATH_PUBLIC'))
		{
			return;
		}

		$publicDir = Factory::getFilesystemTools()->TranslateWinPath(JoomlaPublicFolder::getPublicFolder());
		$rootDir   = Factory::getFilesystemTools()->TranslateWinPath(JPATH_ROOT);

		if ($publicDir === $rootDir || str_starts_with($publicDir, $rootDir))
		{
			return;
		}

		// The path differs, add it here
		$this->filter_data['JPATH_PUBLIC'] = [
			Factory::getFilesystemTools()->rebaseFolderToStockDirs($publicDir),
			'JPATH_PUBLIC',
		];
	}
}
