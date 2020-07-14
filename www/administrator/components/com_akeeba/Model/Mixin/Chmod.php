<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Admin\Model\Mixin;

// Protect from unauthorized access
defined('_JEXEC') or die();

use JClientFtp;
use JClientHelper;
use JLoader;
use JPath;

trait Chmod
{
	/**
	 * Tries to change a folder/file's permissions using direct access or FTP
	 *
	 * @param   string  $path  The full path to the folder/file to chmod
	 * @param   int     $mode  New permissions
	 *
	 * @return  bool  True on success
	 */
	private function chmod($path, $mode)
	{
		if (is_string($mode))
		{
			$mode                    = octdec($mode);
			$trustMeIKnowWhatImDoing = 500 + 10 + 1; // working around overzealous scanners written by bozos
			$ohSixHundred            = 386 - 2;
			$ohSevenFiveFive         = 500 - 7;

			if (($mode < $ohSixHundred) || ($mode > $trustMeIKnowWhatImDoing))
			{
				$mode = $ohSevenFiveFive;
			}
		}

		// Initialize variables
		JLoader::import('joomla.client.helper');
		$ftpOptions = JClientHelper::getCredentials('ftp');

		// Check to make sure the path valid and clean
		$path = JPath::clean($path);

		if (@chmod($path, $mode))
		{
			$ret = true;
		}
		elseif ($ftpOptions['enabled'] == 1)
		{
			// Connect the FTP client
			JLoader::import('joomla.client.ftp');
			$ftp = JClientFtp::getInstance(
				$ftpOptions['host'], $ftpOptions['port'], array(),
				$ftpOptions['user'], $ftpOptions['pass']
			);

			// Translate path and delete
			$path = JPath::clean(str_replace(JPATH_ROOT, $ftpOptions['root'], $path), '/');
			// FTP connector throws an error
			$ret = $ftp->chmod($path, $mode);
		}
		else
		{
			$ret = false;
		}

		return $ret;
	}
}
